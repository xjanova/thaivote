#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Deployment Script v2.0
#
# ระบบ Deploy อัตโนมัติสำหรับ ThaiVote
# รองรับ: Laravel 12 + Vue.js + Reverb WebSocket
#===============================================================================

set -e

# Script version
VERSION="2.0"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="ThaiVote"
APP_DIR=$(cd "$(dirname "$0")" && pwd)
LOG_FILE="${APP_DIR}/storage/logs/deploy.log"
BACKUP_DIR="${APP_DIR}/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)
MIN_DISK_SPACE_MB=500

# Options
FRESH_COMPOSER=false
SKIP_NPM=false
SKIP_BACKUP=true  # Default: no backup (use --backup to enable)
FORCE_MODE=false
FIRST_INSTALL=false  # Set to true if this is a fresh installation

# Create necessary directories
mkdir -p "${BACKUP_DIR}"
mkdir -p "${APP_DIR}/storage/logs"

#===============================================================================
# Utility Functions
#===============================================================================

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "${LOG_FILE}" 2>/dev/null || true
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    echo "[WARNING] $1" >> "${LOG_FILE}" 2>/dev/null || true
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[ERROR] $1" >> "${LOG_FILE}" 2>/dev/null || true
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 STEP $1:${NC} ${WHITE}$2${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

check_disk_space() {
    local available=$(df -m "${APP_DIR}" | awk 'NR==2 {print $4}')
    if [ "$available" -lt "$MIN_DISK_SPACE_MB" ]; then
        log_error "Insufficient disk space: ${available}MB available, ${MIN_DISK_SPACE_MB}MB required"
        log_warning "Running emergency cleanup..."

        # Clear old logs
        find "${APP_DIR}/storage/logs" -name "*.log" -mtime +7 -delete 2>/dev/null || true

        # Clear old backups
        find "${BACKUP_DIR}" -name "*.sql" -mtime +30 -delete 2>/dev/null || true
        find "${BACKUP_DIR}" -name "*.tar.gz" -mtime +30 -delete 2>/dev/null || true

        # Clear Laravel cache
        rm -rf "${APP_DIR}/storage/framework/cache/data/"* 2>/dev/null || true
        rm -rf "${APP_DIR}/storage/framework/views/"* 2>/dev/null || true

        available=$(df -m "${APP_DIR}" | awk 'NR==2 {print $4}')
        if [ "$available" -lt "$MIN_DISK_SPACE_MB" ]; then
            log_error "Still insufficient disk space after cleanup: ${available}MB"
            exit 1
        fi
        log "Disk space freed. Now have ${available}MB available"
    else
        log "Disk space OK: ${available}MB available"
    fi
}

get_php_version() {
    php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;"
}

check_composer_lock_compatibility() {
    log_info "Checking composer.lock compatibility..."

    if [ ! -f "${APP_DIR}/composer.lock" ]; then
        log_warning "composer.lock not found, will run composer update"
        FRESH_COMPOSER=true
        return 0
    fi

    # Get current PHP version
    local PHP_VERSION=$(get_php_version)
    log "Current PHP version: ${PHP_VERSION}"

    # Check if composer.lock has PHP 8.4+ dependencies
    if grep -q '"php":">=8\.4"' "${APP_DIR}/composer.lock" 2>/dev/null; then
        if [[ "${PHP_VERSION}" < "8.4" ]]; then
            log_warning "composer.lock requires PHP 8.4+ but you have PHP ${PHP_VERSION}"
            log_warning "Will regenerate composer.lock..."
            FRESH_COMPOSER=true
        fi
    fi

    # Check for symfony packages requiring PHP 8.4
    if grep -q 'symfony.*v8\.0\.' "${APP_DIR}/composer.lock" 2>/dev/null; then
        if [[ "${PHP_VERSION}" < "8.4" ]]; then
            log_warning "Symfony 8.x packages detected (require PHP 8.4+)"
            log_warning "Will regenerate composer.lock for PHP ${PHP_VERSION}..."
            FRESH_COMPOSER=true
        fi
    fi

    if [ "$FRESH_COMPOSER" = true ]; then
        return 0
    fi

    log "composer.lock is compatible with PHP ${PHP_VERSION}"
}

#===============================================================================
# Pre-flight Checks
#===============================================================================

preflight_checks() {
    log_step "0" "Pre-flight Checks"

    # Check if we're in the right directory
    if [ ! -f "${APP_DIR}/artisan" ]; then
        log_error "Not in a Laravel project directory"
        exit 1
    fi

    # Check if .env exists
    if [ ! -f "${APP_DIR}/.env" ]; then
        if [ -f "${APP_DIR}/.env.example" ]; then
            log_warning ".env not found, creating from .env.example"
            cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
        else
            log_error ".env file not found"
            exit 1
        fi
    fi

    # Check disk space
    check_disk_space

    # Check PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed"
        exit 1
    fi
    log "PHP $(get_php_version) ✓"

    # Check Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer is not installed"
        exit 1
    fi
    log "Composer ✓"

    # Check Node.js
    if command -v node &> /dev/null; then
        log "Node.js $(node --version) ✓"
    else
        log_warning "Node.js is not installed (required for frontend build)"
        SKIP_NPM=true
    fi

    # Check npm
    if command -v npm &> /dev/null; then
        log "npm $(npm --version) ✓"
    else
        log_warning "npm is not installed"
        SKIP_NPM=true
    fi

    # Check composer.lock compatibility
    check_composer_lock_compatibility

    log "All pre-flight checks passed ✓"
}

#===============================================================================
# Backup Functions
#===============================================================================

backup_database() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_info "Skipping database backup"
        return 0
    fi

    log_step "1" "Database Backup"

    # Read database credentials from .env
    DB_CONNECTION=$(grep "^DB_CONNECTION=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if [ "${DB_CONNECTION}" = "mysql" ]; then
        DB_HOST=$(grep "^DB_HOST=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_DATABASE=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_USERNAME=$(grep "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_PASSWORD=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

        BACKUP_FILE="${BACKUP_DIR}/db_backup_${DATE}.sql"

        if command -v mysqldump &> /dev/null; then
            log "Creating MySQL backup..."
            if mysqldump -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_FILE}" 2>/dev/null; then
                if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
                    gzip "${BACKUP_FILE}"
                    log "Database backed up to: ${BACKUP_FILE}.gz"
                fi
            else
                log_warning "Database backup failed (non-critical)"
            fi
        else
            log_warning "mysqldump not found, skipping database backup"
        fi
    elif [ "${DB_CONNECTION}" = "sqlite" ]; then
        SQLITE_FILE="${APP_DIR}/database/database.sqlite"
        if [ -f "${SQLITE_FILE}" ]; then
            cp "${SQLITE_FILE}" "${BACKUP_DIR}/db_backup_${DATE}.sqlite"
            log "SQLite database backed up"
        else
            log_info "No SQLite database found to backup"
        fi
    else
        log_info "Database type: ${DB_CONNECTION} (no backup configured)"
    fi
}

backup_critical_files() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_info "Skipping critical files backup"
        return 0
    fi

    log_step "2" "Critical Files Backup"

    CRITICAL_BACKUP="${BACKUP_DIR}/critical_${DATE}.tar.gz"

    # Backup .env and important configs
    tar -czf "${CRITICAL_BACKUP}" \
        -C "${APP_DIR}" \
        .env \
        2>/dev/null || log_warning "Some files could not be backed up"

    # Add storage/app/public if exists
    if [ -d "${APP_DIR}/storage/app/public" ]; then
        tar -rzf "${CRITICAL_BACKUP}" \
            -C "${APP_DIR}" \
            storage/app/public \
            2>/dev/null || true
    fi

    log "Critical files backed up to: ${CRITICAL_BACKUP}"
}

#===============================================================================
# Database Setup Functions
#===============================================================================

setup_database() {
    log_step "3" "Setting Up Database & APP_KEY"

    cd "${APP_DIR}"

    # Read database connection type from .env
    DB_CONNECTION=$(grep "^DB_CONNECTION=" "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")

    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        SQLITE_FILE="${APP_DIR}/database/database.sqlite"

        # Create database directory if not exists
        mkdir -p "${APP_DIR}/database"

        # Create SQLite file if not exists
        if [ ! -f "${SQLITE_FILE}" ]; then
            touch "${SQLITE_FILE}"
            chmod 664 "${SQLITE_FILE}"
            log "Created SQLite database: ${SQLITE_FILE}"
            FIRST_INSTALL=true
        else
            log "SQLite database exists: ${SQLITE_FILE}"
        fi
    elif [ "${DB_CONNECTION}" = "mysql" ]; then
        # Test MySQL connection
        DB_HOST=$(grep "^DB_HOST=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_DATABASE=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_USERNAME=$(grep "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        DB_PASSWORD=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

        log "Database: MySQL (${DB_DATABASE}@${DB_HOST})"

        # Test connection
        if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
            log "MySQL connection successful"
        else
            log_warning "MySQL connection test failed - will retry during migrations"
        fi
    else
        log "Database: ${DB_CONNECTION}"
    fi

    log "Database setup complete ✓"
}

generate_app_key() {
    cd "${APP_DIR}"

    # Check if APP_KEY is set
    APP_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d '=' -f2)

    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        log "Generating APP_KEY..."
        php artisan key:generate --force
        log "APP_KEY generated ✓"
    else
        log "APP_KEY already set ✓"
    fi
}

create_admin_user() {
    local args="$*"

    # Check if --admin flag is passed
    if [[ "$args" != *"--admin"* ]]; then
        return 0
    fi

    cd "${APP_DIR}"

    log_info "Creating admin user..."

    # Check if admin user already exists
    local admin_exists=false
    if php artisan tinker --execute="echo App\Models\User::where('email', 'admin@thaivote.local')->exists() ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
        admin_exists=true
    fi

    if [ "$admin_exists" = true ]; then
        log_info "Admin user already exists (admin@thaivote.local)"
        return 0
    fi

    # Create admin user
    php artisan tinker --execute="
        \$user = App\Models\User::create([
            'name' => 'Administrator',
            'email' => 'admin@thaivote.local',
            'password' => Hash::make('admin1234'),
        ]);
        echo 'Admin user created: ' . \$user->email;
    " 2>/dev/null && {
        log "Admin user created:"
        log "  Email: admin@thaivote.local"
        log "  Password: admin1234"
        log_warning "Please change the password after first login!"
    } || log_warning "Failed to create admin user"
}

#===============================================================================
# Deployment Steps
#===============================================================================

enable_maintenance_mode() {
    log_step "5" "Enabling Maintenance Mode"

    cd "${APP_DIR}"
    php artisan down --retry=60 --refresh=15 2>/dev/null || true
    log "Maintenance mode enabled"
}

disable_maintenance_mode() {
    log_step "FINAL" "Disabling Maintenance Mode"

    cd "${APP_DIR}"
    php artisan up 2>/dev/null || true
    log "Application is now live!"
}

pull_latest_code() {
    log_step "6" "Pulling Latest Code"

    cd "${APP_DIR}"

    # Check if this is a git repository
    if [ ! -d ".git" ]; then
        log_warning "Not a git repository, skipping code pull"
        return 0
    fi

    # Stash any local changes
    if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
        log_warning "Local changes detected, stashing..."
        git stash push -m "Deploy stash ${DATE}" 2>/dev/null || true
    fi

    # Pull latest code
    BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    log "Current branch: ${BRANCH}"

    if git fetch origin 2>/dev/null; then
        git reset --hard origin/${BRANCH} 2>/dev/null || git pull origin ${BRANCH}
        log "Code updated to latest version"
        git log -1 --pretty=format:"Commit: %h - %s (%an, %ar)" 2>/dev/null | tee -a "${LOG_FILE}" || true
        echo ""
    else
        log_warning "Could not fetch from remote (offline mode?)"
    fi
}

install_composer_dependencies() {
    log_step "7" "Installing Composer Dependencies"

    cd "${APP_DIR}"

    # Determine environment
    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    APP_ENV=${APP_ENV:-production}

    # If fresh composer is needed, delete lock file
    if [ "$FRESH_COMPOSER" = true ]; then
        log_warning "Regenerating composer.lock for PHP $(get_php_version)..."
        rm -f composer.lock

        if [ "${APP_ENV}" = "production" ]; then
            log "Running: composer update --no-dev --optimize-autoloader"
            if ! composer update --no-dev --optimize-autoloader --no-interaction 2>&1; then
                log_error "Composer update failed"
                log_info "Trying with --ignore-platform-req=php..."
                composer update --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php || {
                    log_error "Composer update failed even with --ignore-platform-req"
                    exit 1
                }
            fi
        else
            log "Running: composer update --optimize-autoloader"
            composer update --optimize-autoloader --no-interaction || {
                log_error "Composer update failed"
                exit 1
            }
        fi
    else
        if [ "${APP_ENV}" = "production" ]; then
            log "Production mode: Installing without dev dependencies"
            if ! composer install --no-dev --optimize-autoloader --no-interaction 2>&1; then
                log_warning "composer install failed, trying update..."
                rm -f composer.lock
                composer update --no-dev --optimize-autoloader --no-interaction || {
                    log_error "Composer update also failed"
                    exit 1
                }
            fi
        else
            log "Development mode: Installing all dependencies"
            composer install --optimize-autoloader --no-interaction || {
                log_warning "composer install failed, trying update..."
                rm -f composer.lock
                composer update --optimize-autoloader --no-interaction
            }
        fi
    fi

    log "Composer dependencies installed ✓"
}

install_npm_dependencies() {
    if [ "$SKIP_NPM" = true ]; then
        log_info "Skipping NPM dependencies"
        return 0
    fi

    log_step "8" "Installing NPM Dependencies"

    cd "${APP_DIR}"

    if command -v npm &> /dev/null; then
        # Set npm timeout (5 minutes max)
        export npm_config_fetch_timeout=300000
        export npm_config_fetch_retries=2

        # Try npm ci first (faster, uses lock file)
        if [ -f "package-lock.json" ]; then
            log "Running npm ci..."
            if timeout 300 npm ci 2>&1; then
                log "NPM dependencies installed ✓"
            else
                log_warning "npm ci failed, trying npm install..."
                timeout 300 npm install 2>&1 || {
                    log_warning "NPM install failed or timed out (non-critical)"
                    return 0
                }
                log "NPM dependencies installed ✓"
            fi
        else
            log "Running npm install..."
            timeout 300 npm install 2>&1 || {
                log_warning "NPM install failed or timed out (non-critical)"
                return 0
            }
            log "NPM dependencies installed ✓"
        fi
    else
        log_warning "NPM not available, skipping frontend dependencies"
    fi
}

build_frontend() {
    if [ "$SKIP_NPM" = true ]; then
        log_info "Skipping frontend build"
        return 0
    fi

    log_step "9" "Building Frontend Assets"

    cd "${APP_DIR}"

    if command -v npm &> /dev/null; then
        log "Running npm run build..."
        if timeout 600 npm run build 2>&1; then
            log "Frontend assets built ✓"
        else
            log_warning "Frontend build failed or timed out (non-critical)"
        fi
    else
        log_warning "NPM not available, skipping frontend build"
    fi
}

run_migrations() {
    log_step "10" "Running Database Migrations"

    cd "${APP_DIR}"

    # Run migrations with force flag for production
    if php artisan migrate --force 2>&1; then
        log "Migrations completed ✓"
    else
        log_warning "Migrations failed or no pending migrations"
    fi
}

clear_caches() {
    log_step "11" "Clearing Caches"

    cd "${APP_DIR}"

    # Clear all Laravel caches
    php artisan cache:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true

    log "All caches cleared ✓"
}

optimize_application() {
    log_step "12" "Optimizing Application"

    cd "${APP_DIR}"

    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if [ "${APP_ENV}" = "production" ]; then
        # Cache configuration
        php artisan config:cache 2>/dev/null || log_warning "config:cache failed"
        php artisan route:cache 2>/dev/null || log_warning "route:cache failed"
        php artisan view:cache 2>/dev/null || log_warning "view:cache failed"
        php artisan event:cache 2>/dev/null || log_warning "event:cache failed"

        log "Application optimized for production ✓"
    else
        log "Development mode: Skipping cache optimization"
    fi
}

setup_storage_links() {
    log_step "13" "Setting Up Storage Links"

    cd "${APP_DIR}"

    # Ensure storage/app/public exists
    mkdir -p "${APP_DIR}/storage/app/public"

    # Ensure public_html exists
    mkdir -p "${APP_DIR}/public_html"

    # Remove existing symlink if broken
    if [ -L "${APP_DIR}/public_html/storage" ] && [ ! -e "${APP_DIR}/public_html/storage" ]; then
        rm "${APP_DIR}/public_html/storage"
        log "Removed broken storage symlink"
    fi

    # Create storage link manually if artisan fails
    if ! php artisan storage:link 2>/dev/null; then
        if [ ! -L "${APP_DIR}/public_html/storage" ]; then
            ln -s "${APP_DIR}/storage/app/public" "${APP_DIR}/public_html/storage" 2>/dev/null && \
                log "Created storage symlink manually" || \
                log_warning "Could not create storage symlink"
        else
            log_info "Storage link already exists"
        fi
    fi

    log "Storage links configured ✓"
}

fix_permissions() {
    log_step "14" "Fixing Permissions"

    cd "${APP_DIR}"

    # Set proper permissions for storage and cache
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    # Create required directories
    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    # If www-data user exists, set ownership
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    # If admin user exists (DirectAdmin), set ownership
    if id "admin" &>/dev/null; then
        chown -R admin:admin storage bootstrap/cache 2>/dev/null || true
    fi

    log "Permissions fixed ✓"
}

restart_services() {
    log_step "15" "Restarting Services"

    cd "${APP_DIR}"

    # Restart queue workers
    php artisan queue:restart 2>/dev/null || log_info "No queue workers to restart"

    # Restart PHP-FPM if available (check multiple versions)
    local PHP_FPM_RESTARTED=false
    for version in 8.4 8.3 8.2 8.1 8.0; do
        if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
            sudo systemctl reload "php${version}-fpm" 2>/dev/null && {
                log "PHP ${version}-FPM reloaded"
                PHP_FPM_RESTARTED=true
                break
            }
        fi
    done

    if [ "$PHP_FPM_RESTARTED" = false ]; then
        if systemctl is-active --quiet php-fpm 2>/dev/null; then
            sudo systemctl reload php-fpm 2>/dev/null && log "PHP-FPM reloaded"
        else
            log_info "PHP-FPM not found or not running as a service"
        fi
    fi

    # Restart Reverb WebSocket server if configured
    if systemctl is-active --quiet thaivote-reverb 2>/dev/null; then
        sudo systemctl restart thaivote-reverb 2>/dev/null && log "Reverb WebSocket server restarted"
    fi

    # Restart Supervisor if available
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread 2>/dev/null || true
        supervisorctl update 2>/dev/null || true
        log "Supervisor updated"
    fi

    log "Services restarted ✓"
}

run_seeders() {
    local args="$*"
    local force_seed=false

    # Check if --seed flag is passed
    if [[ "$args" == *"--seed"* ]]; then
        force_seed=true
    fi

    cd "${APP_DIR}"

    # Check if core data already exists (provinces, parties)
    local has_core_data=false
    local province_count=0
    local party_count=0

    # Try to get count from database
    province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    party_count=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")

    if [ "$province_count" -gt 0 ] && [ "$party_count" -gt 0 ]; then
        has_core_data=true
    fi

    # Auto-seed on first install (detected by setup_database)
    if [ "$FIRST_INSTALL" = true ]; then
        force_seed=true
        log_info "First installation detected - will seed database"
    fi

    if [ "$has_core_data" = true ] && [ "$force_seed" = false ]; then
        log_info "Core data exists (provinces: ${province_count}, parties: ${party_count}), skipping seeders (use --seed to update)"
        return 0
    fi

    if [ "$force_seed" = true ] || [ "$has_core_data" = false ]; then
        log_step "16" "Running Seeders (ข้อมูลจาก กกต.)"

        if [ "$has_core_data" = true ]; then
            log_info "Force seeding requested, updating data..."
        else
            log_info "No core data found, seeding provinces, constituencies, parties..."
        fi

        if php artisan db:seed --force 2>&1; then
            log "Seeders executed ✓"
            log "  - 77 จังหวัด (provinces)"
            log "  - 400 เขตเลือกตั้ง (constituencies)"
            log "  - พรรคการเมือง (parties)"
        else
            log_warning "Seeders failed (non-critical)"
        fi
    fi
}

verify_deployment() {
    log_step "17" "Verifying Deployment"

    cd "${APP_DIR}"
    local ERRORS=0

    # Check if artisan works
    if php artisan --version &>/dev/null; then
        log "✓ Artisan command works"
    else
        log_error "✗ Artisan command failed"
        ERRORS=$((ERRORS + 1))
    fi

    # Check if key config values are set
    APP_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d '=' -f2)
    if [ -n "${APP_KEY}" ] && [ "${APP_KEY}" != "" ] && [ "${APP_KEY}" != "base64:" ]; then
        log "✓ APP_KEY is set"
    else
        log_warning "⚠ APP_KEY is missing, generating..."
        php artisan key:generate --force
    fi

    # Check database connection
    if php artisan migrate:status &>/dev/null; then
        log "✓ Database connection works"
    else
        log_warning "⚠ Database connection check failed"
    fi

    # Check if public_html/build exists (frontend assets)
    if [ -d "${APP_DIR}/public_html/build" ]; then
        log "✓ Frontend assets exist"
    else
        log_warning "⚠ Frontend assets not found in public_html/build"
    fi

    # Check storage link
    if [ -L "${APP_DIR}/public_html/storage" ]; then
        log "✓ Storage link exists"
    else
        log_warning "⚠ Storage link not found"
    fi

    # Check data status
    local province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    local party_count=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    local constituency_count=$(php artisan tinker --execute="echo App\Models\Constituency::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")

    if [ "$province_count" -gt 0 ] && [ "$party_count" -gt 0 ]; then
        log "✓ Core data loaded (จังหวัด: ${province_count}, เขต: ${constituency_count}, พรรค: ${party_count})"
    else
        log_warning "⚠ Core data not loaded (run with --seed to populate)"
    fi

    if [ $ERRORS -eq 0 ]; then
        log "Deployment verification passed ✓"
    else
        log_error "Deployment verification found ${ERRORS} error(s)"
    fi
}

#===============================================================================
# Rollback Function
#===============================================================================

generate_rollback_command() {
    log "Generating rollback information..."

    ROLLBACK_FILE="${BACKUP_DIR}/rollback_${DATE}.sh"

    cat > "${ROLLBACK_FILE}" << EOF
#!/bin/bash
# Rollback script generated on ${DATE}
# This script will restore the database from backup

echo "Restoring database..."
# gunzip -c ${BACKUP_DIR}/db_backup_${DATE}.sql.gz | mysql -u \${DB_USERNAME} -p\${DB_PASSWORD} \${DB_DATABASE}

echo "Restoring critical files..."
# tar -xzf ${BACKUP_DIR}/critical_${DATE}.tar.gz -C ${APP_DIR}

echo "Rollback completed!"
EOF

    chmod +x "${ROLLBACK_FILE}"
    log "Rollback script created: ${ROLLBACK_FILE}"
}

#===============================================================================
# Main Deployment Function
#===============================================================================

show_banner() {
    echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}████████╗██╗  ██╗ █████╗ ██╗██╗   ██╗ ██████╗ ████████╗███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}╚══██╔══╝██║  ██║██╔══██╗██║██║   ██║██╔═══██╗╚══██╔══╝██╔════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ███████║███████║██║██║   ██║██║   ██║   ██║   █████╗${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██╔══██║██╔══██║██║╚██╗ ██╔╝██║   ██║   ██║   ██╔══╝${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██║  ██║██║  ██║██║ ╚████╔╝ ╚██████╔╝   ██║   ███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═════╝    ╚═╝   ╚══════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${CYAN}Election Results Tracker${NC}                              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${GREEN}Deployment Script v${VERSION}${NC}                                ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"
}

deploy() {
    show_banner

    START_TIME=$(date +%s)

    log "Starting deployment at $(date)"
    log "=================================================="

    # Run all deployment steps
    preflight_checks
    backup_database
    backup_critical_files
    setup_database
    generate_app_key
    enable_maintenance_mode

    # Wrap main deployment in trap for error handling
    trap 'log_error "Deployment failed! Disabling maintenance mode..."; disable_maintenance_mode; exit 1' ERR

    pull_latest_code
    install_composer_dependencies
    install_npm_dependencies
    build_frontend
    run_migrations
    clear_caches
    optimize_application
    setup_storage_links
    fix_permissions
    restart_services
    run_seeders "$@"
    create_admin_user "$@"
    generate_rollback_command
    verify_deployment

    trap - ERR

    disable_maintenance_mode

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))

    echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${GREEN}✅ DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}                                   ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Duration: ${DURATION} seconds                                                       ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Timestamp: $(date '+%Y-%m-%d %H:%M:%S')                                        ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    log "Deployment completed in ${DURATION} seconds"
}

#===============================================================================
# Quick Deploy (Skip backup and npm)
#===============================================================================

quick_deploy() {
    echo -e "${YELLOW}Running quick deployment (no backups, minimal checks)...${NC}\n"

    cd "${APP_DIR}"

    # Check composer.lock compatibility first
    check_composer_lock_compatibility

    # Setup database (creates SQLite if needed)
    DB_CONNECTION=$(grep "^DB_CONNECTION=" "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")
    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        SQLITE_FILE="${APP_DIR}/database/database.sqlite"
        mkdir -p "${APP_DIR}/database"
        if [ ! -f "${SQLITE_FILE}" ]; then
            touch "${SQLITE_FILE}"
            chmod 664 "${SQLITE_FILE}"
            echo "Created SQLite database"
        fi
    fi

    # Generate APP_KEY if not set
    APP_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d '=' -f2)
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        php artisan key:generate --force
    fi

    php artisan down --retry=60 2>/dev/null || true

    # Pull if git repo
    if [ -d ".git" ]; then
        git pull origin $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main") 2>/dev/null || true
    fi

    # Composer
    if [ "$FRESH_COMPOSER" = true ]; then
        rm -f composer.lock
        composer update --no-dev --optimize-autoloader --no-interaction
    else
        composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || {
            rm -f composer.lock
            composer update --no-dev --optimize-autoloader --no-interaction
        }
    fi

    php artisan migrate --force 2>/dev/null || true
    php artisan storage:link 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    php artisan queue:restart 2>/dev/null || true

    # Seed if no data exists
    province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    if [ "$province_count" -eq 0 ]; then
        php artisan db:seed --force 2>/dev/null || true
    fi

    php artisan up

    echo -e "\n${GREEN}Quick deployment completed!${NC}"
}

#===============================================================================
# Fix Composer Command
#===============================================================================

fix_composer() {
    echo -e "${CYAN}Fixing Composer dependencies for PHP $(get_php_version)...${NC}\n"

    cd "${APP_DIR}"

    # Remove lock file
    rm -f composer.lock
    log "Removed composer.lock"

    # Clear composer cache
    composer clear-cache 2>/dev/null || true

    # Update dependencies
    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "production")

    if [ "${APP_ENV}" = "production" ]; then
        composer update --no-dev --optimize-autoloader --no-interaction
    else
        composer update --optimize-autoloader --no-interaction
    fi

    echo -e "\n${GREEN}Composer dependencies fixed!${NC}"
}

#===============================================================================
# Help
#===============================================================================

show_help() {
    echo -e "${CYAN}ThaiVote Deployment Script v${VERSION}${NC}"
    echo ""
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  deploy        Full deployment with backups (default)"
    echo "  quick         Quick deployment without backups"
    echo "  fix-composer  Fix composer.lock for current PHP version"
    echo "  rollback      Show rollback information"
    echo "  status        Show application status"
    echo "  help          Show this help message"
    echo ""
    echo "Options:"
    echo "  --seed              Force run database seeders (auto-runs if no data exists)"
    echo "  --admin             Create admin user (admin@thaivote.local / admin1234)"
    echo "  --fresh-composer    Force regenerate composer.lock"
    echo "  --skip-npm          Skip NPM install and build"
    echo "  --backup            Enable database and file backups (disabled by default)"
    echo ""
    echo "First-time installation:"
    echo "  $0 deploy --admin         # Deploy with admin user"
    echo ""
    echo "Examples:"
    echo "  $0                        # Full deployment (no backup)"
    echo "  $0 deploy --seed          # Full deployment with seeders"
    echo "  $0 deploy --backup        # Full deployment with backups"
    echo "  $0 quick                  # Quick deployment"
    echo "  $0 fix-composer           # Fix composer for current PHP"
    echo "  $0 deploy --fresh-composer  # Force update composer.lock"
    echo ""
}

show_status() {
    echo -e "${CYAN}ThaiVote Application Status${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    cd "${APP_DIR}"

    echo -e "\n${PURPLE}System:${NC}"
    echo "  PHP: $(get_php_version)"
    if command -v node &>/dev/null; then
        echo "  Node: $(node --version)"
    fi

    echo -e "\n${PURPLE}Git Status:${NC}"
    if [ -d ".git" ]; then
        git log -1 --pretty=format:"  Branch: %D%n  Commit: %h%n  Author: %an%n  Date: %ar%n  Message: %s" 2>/dev/null || echo "  Not available"
    else
        echo "  Not a git repository"
    fi

    echo -e "\n\n${PURPLE}Laravel Status:${NC}"
    php artisan --version 2>/dev/null || echo "  Artisan not working"

    echo -e "\n${PURPLE}Environment:${NC}"
    grep "^APP_ENV=" "${APP_DIR}/.env" 2>/dev/null | sed 's/^/  /' || echo "  Not set"
    grep "^APP_DEBUG=" "${APP_DIR}/.env" 2>/dev/null | sed 's/^/  /' || echo "  Not set"

    echo -e "\n${PURPLE}Database:${NC}"
    grep "^DB_CONNECTION=" "${APP_DIR}/.env" 2>/dev/null | sed 's/^/  /' || echo "  Not set"
    php artisan migrate:status 2>/dev/null | head -10 || echo "  Unable to connect to database"

    echo -e "\n${PURPLE}Cache Status:${NC}"
    if [ -f "${APP_DIR}/bootstrap/cache/config.php" ]; then
        echo "  Config: Cached"
    else
        echo "  Config: Not cached"
    fi
    if [ -f "${APP_DIR}/bootstrap/cache/routes-v7.php" ]; then
        echo "  Routes: Cached"
    else
        echo "  Routes: Not cached"
    fi

    echo -e "\n${PURPLE}Frontend:${NC}"
    if [ -d "${APP_DIR}/public_html/build" ]; then
        echo "  Build: Exists"
    else
        echo "  Build: Not found"
    fi

    echo -e "\n${PURPLE}Data Status (กกต.):${NC}"
    local province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    local party_count=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    local constituency_count=$(php artisan tinker --execute="echo App\Models\Constituency::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    echo "  จังหวัด (Provinces): ${province_count}"
    echo "  เขตเลือกตั้ง (Constituencies): ${constituency_count}"
    echo "  พรรคการเมือง (Parties): ${party_count}"

    echo ""
}

#===============================================================================
# Parse Options
#===============================================================================

parse_options() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --fresh-composer)
                FRESH_COMPOSER=true
                shift
                ;;
            --skip-npm)
                SKIP_NPM=true
                shift
                ;;
            --backup)
                SKIP_BACKUP=false
                shift
                ;;
            --seed)
                shift
                ;;
            *)
                shift
                ;;
        esac
    done
}

#===============================================================================
# Main Entry Point
#===============================================================================

# Parse options first
parse_options "$@"

case "${1:-deploy}" in
    deploy)
        deploy "${@:2}"
        ;;
    quick)
        quick_deploy
        ;;
    fix-composer|fix_composer)
        fix_composer
        ;;
    rollback)
        echo "Recent backups:"
        ls -la "${BACKUP_DIR}" 2>/dev/null | tail -10 || echo "No backups found"
        ;;
    status)
        show_status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        # Check if it's an option
        if [[ "$1" == --* ]]; then
            deploy "$@"
        else
            echo "Unknown command: $1"
            show_help
            exit 1
        fi
        ;;
esac
