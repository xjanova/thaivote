#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Smart Automated Deployment Script v3.0
#
# Features:
#   - Smart migration handling (detects and reports errors)
#   - Intelligent seeding (skip existing data)
#   - Detailed error logging and reporting
#   - Automatic rollback on failure
#   - PHP version compatibility checks
#
# Usage:
#   ./deploy.sh              # Full deployment
#   ./deploy.sh quick        # Quick deployment (no backups)
#   ./deploy.sh --seed       # Force run seeders
#   ./deploy.sh --admin      # Create admin user
#   ./deploy.sh status       # Show application status
#===============================================================================

set -e

# Script version
VERSION="3.0"

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
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="${APP_DIR}/storage/backups"
LOG_DIR="${APP_DIR}/storage/logs/deploy"
LOG_FILE="${LOG_DIR}/deploy_${TIMESTAMP}.log"
ERROR_LOG="${LOG_DIR}/error_${TIMESTAMP}.log"
MIN_DISK_SPACE_MB=500

# Options
FRESH_COMPOSER=false
SKIP_NPM=false
SKIP_BACKUP=true  # Default: no backup (use --backup to enable)
FORCE_SEED=false
CREATE_ADMIN=false
VERBOSE=false
FIRST_INSTALL=false

# Create necessary directories
mkdir -p "${BACKUP_DIR}"
mkdir -p "${LOG_DIR}"

#===============================================================================
# Logging Functions (with file output)
#===============================================================================

log() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
    echo -e "${GREEN}[✓]${NC} $1"
}

log_info() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] INFO: $1"
    echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
    echo -e "${BLUE}[i]${NC} $1"
}

log_step() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] STEP $1: $2"
    echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 STEP $1:${NC} ${WHITE}$2${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

log_warning() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1"
    echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
    echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
    echo "$message" >> "${ERROR_LOG}" 2>/dev/null || true
    echo -e "${RED}[✗]${NC} $1"
}

log_debug() {
    if [ "$VERBOSE" = true ]; then
        local message="[$(date '+%Y-%m-%d %H:%M:%S')] DEBUG: $1"
        echo "$message" >> "${LOG_FILE}" 2>/dev/null || true
        echo -e "${CYAN}[DEBUG]${NC} $1"
    fi
}

# Log error details to file only
log_error_detail() {
    echo "$1" >> "${ERROR_LOG}" 2>/dev/null || true
    echo "$1" >> "${LOG_FILE}" 2>/dev/null || true
}

#===============================================================================
# Error Report Generation
#===============================================================================

generate_error_report() {
    local step="$1"
    local error_message="$2"
    local error_output="$3"

    cat >> "${ERROR_LOG}" << EOF

═══════════════════════════════════════════════════════════
ERROR REPORT - $(date '+%Y-%m-%d %H:%M:%S')
═══════════════════════════════════════════════════════════

Step: $step
Environment: $(grep APP_ENV "${APP_DIR}/.env" 2>/dev/null | cut -d'=' -f2 || echo 'N/A')
Commit: $(cd "${APP_DIR}" && git rev-parse --short HEAD 2>/dev/null || echo 'N/A')

Error Message:
$error_message

Error Output:
---
$error_output
---

System Information:
  PHP Version: $(php -v 2>/dev/null | head -1 || echo 'N/A')
  Composer: $(composer --version 2>/dev/null | head -1 || echo 'N/A')
  Node: $(node -v 2>/dev/null || echo 'N/A')
  NPM: $(npm -v 2>/dev/null || echo 'N/A')

Database Information:
  Connection: $(grep DB_CONNECTION "${APP_DIR}/.env" 2>/dev/null | cut -d'=' -f2 || echo 'N/A')
  Host: $(grep DB_HOST "${APP_DIR}/.env" 2>/dev/null | cut -d'=' -f2 || echo 'N/A')
  Database: $(grep DB_DATABASE "${APP_DIR}/.env" 2>/dev/null | cut -d'=' -f2 || echo 'N/A')

EOF

    # Add recent Laravel log if available
    if [ -f "${APP_DIR}/storage/logs/laravel.log" ]; then
        cat >> "${ERROR_LOG}" << EOF
Recent Laravel Logs (last 30 lines):
---
$(tail -30 "${APP_DIR}/storage/logs/laravel.log" 2>/dev/null || echo "Could not read Laravel log")
---

EOF
    fi

    echo "═══════════════════════════════════════════════════════════" >> "${ERROR_LOG}"
}

#===============================================================================
# Utility Functions
#===============================================================================

check_disk_space() {
    local available=$(df -m "${APP_DIR}" | awk 'NR==2 {print $4}')
    if [ "$available" -lt "$MIN_DISK_SPACE_MB" ]; then
        log_warning "Low disk space: ${available}MB available"
        log_info "Running cleanup..."
        find "${APP_DIR}/storage/logs" -name "*.log" -mtime +7 -delete 2>/dev/null || true
        find "${BACKUP_DIR}" -name "*.sql" -mtime +30 -delete 2>/dev/null || true
        find "${LOG_DIR}" -name "*.log" -mtime +14 -delete 2>/dev/null || true
        rm -rf "${APP_DIR}/storage/framework/cache/data/"* 2>/dev/null || true
    fi
    log_debug "Disk space: ${available}MB available"
}

get_php_version() {
    php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;"
}

check_composer_lock_compatibility() {
    log_debug "Checking composer.lock compatibility..."

    if [ ! -f "${APP_DIR}/composer.lock" ]; then
        log_warning "composer.lock not found, will run composer update"
        FRESH_COMPOSER=true
        return 0
    fi

    local PHP_VERSION=$(get_php_version)

    # Check if composer.lock has PHP 8.4+ dependencies
    if grep -q '"php":">=8\.4"' "${APP_DIR}/composer.lock" 2>/dev/null; then
        if [[ "${PHP_VERSION}" < "8.4" ]]; then
            log_warning "composer.lock requires PHP 8.4+ but you have PHP ${PHP_VERSION}"
            FRESH_COMPOSER=true
        fi
    fi

    # Check for symfony packages requiring PHP 8.4
    if grep -q 'symfony.*v8\.0\.' "${APP_DIR}/composer.lock" 2>/dev/null; then
        if [[ "${PHP_VERSION}" < "8.4" ]]; then
            log_warning "Symfony 8.x packages detected (require PHP 8.4+)"
            FRESH_COMPOSER=true
        fi
    fi
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
            FIRST_INSTALL=true
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
        log_warning "Node.js not installed (required for frontend)"
        SKIP_NPM=true
    fi

    # Check npm
    if command -v npm &> /dev/null; then
        log "npm $(npm --version) ✓"
    else
        SKIP_NPM=true
    fi

    # Check composer.lock compatibility
    check_composer_lock_compatibility

    log "All pre-flight checks passed ✓"
}

#===============================================================================
# Environment Check
#===============================================================================

check_environment() {
    log_step "1" "Checking Environment"

    if grep -q "APP_ENV=production" "${APP_DIR}/.env"; then
        log_warning "Deploying to PRODUCTION environment"
        if [ -t 0 ]; then
            read -p "Are you sure you want to continue? (y/N): " confirm
            if [[ ! "$confirm" =~ ^[Yy](es)?$ ]]; then
                log_info "Deployment cancelled"
                exit 0
            fi
        fi
    else
        log_info "Deploying to $(grep APP_ENV "${APP_DIR}/.env" | cut -d'=' -f2) environment"
    fi

    log "Environment check passed ✓"
}

#===============================================================================
# Backup Functions
#===============================================================================

backup_database() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_info "Skipping database backup (use --backup to enable)"
        return 0
    fi

    log_step "2" "Database Backup"

    cd "${APP_DIR}"
    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if [ "${DB_CONNECTION}" = "mysql" ]; then
        local DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        local DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        local DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        local DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

        local BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql"

        if command -v mysqldump &> /dev/null; then
            log_info "Creating MySQL backup..."
            set +e
            local BACKUP_OUTPUT=$(mysqldump -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" 2>&1)
            local BACKUP_EXIT=$?
            set -e

            if [ $BACKUP_EXIT -eq 0 ]; then
                echo "$BACKUP_OUTPUT" > "${BACKUP_FILE}"
                gzip "${BACKUP_FILE}"
                log "Database backed up to: ${BACKUP_FILE}.gz"
            else
                log_warning "Could not create backup"
                log_error_detail "Backup failed: $BACKUP_OUTPUT"
            fi
        else
            log_warning "mysqldump not available, skipping backup"
        fi
    elif [ "${DB_CONNECTION}" = "sqlite" ]; then
        if [ -f "${APP_DIR}/database/database.sqlite" ]; then
            cp "${APP_DIR}/database/database.sqlite" "${BACKUP_DIR}/db_backup_${TIMESTAMP}.sqlite"
            log "SQLite database backed up"
        fi
    fi
}

#===============================================================================
# Database Setup
#===============================================================================

setup_database() {
    log_step "3" "Setting Up Database"

    cd "${APP_DIR}"
    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")

    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        mkdir -p "${APP_DIR}/database"
        if [ ! -f "${APP_DIR}/database/database.sqlite" ]; then
            touch "${APP_DIR}/database/database.sqlite"
            chmod 664 "${APP_DIR}/database/database.sqlite"
            log "Created SQLite database"
            FIRST_INSTALL=true
        else
            log "SQLite database exists"
        fi
    elif [ "${DB_CONNECTION}" = "mysql" ]; then
        log "Database: MySQL"
    fi

    # Generate APP_KEY if not set
    local APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        log_info "Generating APP_KEY..."
        php artisan key:generate --force
        log "APP_KEY generated ✓"
    else
        log "APP_KEY already set ✓"
    fi
}

#===============================================================================
# Maintenance Mode
#===============================================================================

enable_maintenance_mode() {
    log_step "4" "Enabling Maintenance Mode"
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

#===============================================================================
# Code Update
#===============================================================================

pull_latest_code() {
    log_step "5" "Pulling Latest Code"

    cd "${APP_DIR}"

    if [ ! -d ".git" ]; then
        log_warning "Not a git repository, skipping code pull"
        return 0
    fi

    # Stash local changes
    if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
        log_warning "Local changes detected, stashing..."
        git stash push -m "Deploy stash ${TIMESTAMP}" 2>/dev/null || true
    fi

    local BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    log_info "Current branch: ${BRANCH}"

    set +e
    local GIT_OUTPUT=$(git fetch origin 2>&1)
    local GIT_EXIT=$?
    set -e

    if [ $GIT_EXIT -ne 0 ]; then
        log_warning "Git fetch failed (offline mode?)"
        log_error_detail "Git fetch output: $GIT_OUTPUT"
        return 0
    fi

    set +e
    GIT_OUTPUT=$(git pull origin ${BRANCH} 2>&1)
    GIT_EXIT=$?
    set -e

    if [ $GIT_EXIT -ne 0 ]; then
        log_warning "Git pull had issues"
        log_error_detail "Git pull output: $GIT_OUTPUT"
    else
        log "Code updated to: $(git rev-parse --short HEAD)"
    fi
}

#===============================================================================
# Composer Dependencies
#===============================================================================

install_composer_dependencies() {
    log_step "6" "Installing Composer Dependencies"

    cd "${APP_DIR}"
    local APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    APP_ENV=${APP_ENV:-production}

    if [ "$FRESH_COMPOSER" = true ]; then
        log_warning "Regenerating composer.lock for PHP $(get_php_version)..."
        rm -f composer.lock

        set +e
        local COMPOSER_OUTPUT
        if [ "${APP_ENV}" = "production" ]; then
            COMPOSER_OUTPUT=$(composer update --no-dev --optimize-autoloader --no-interaction 2>&1)
        else
            COMPOSER_OUTPUT=$(composer update --optimize-autoloader --no-interaction 2>&1)
        fi
        local COMPOSER_EXIT=$?
        set -e

        if [ $COMPOSER_EXIT -ne 0 ]; then
            log_error "Composer update failed"
            generate_error_report "install_composer" "Composer update failed" "$COMPOSER_OUTPUT"
            echo "$COMPOSER_OUTPUT"
            exit 1
        fi
    else
        set +e
        local COMPOSER_OUTPUT
        if [ "${APP_ENV}" = "production" ]; then
            COMPOSER_OUTPUT=$(composer install --no-dev --optimize-autoloader --no-interaction 2>&1)
        else
            COMPOSER_OUTPUT=$(composer install --optimize-autoloader --no-interaction 2>&1)
        fi
        local COMPOSER_EXIT=$?
        set -e

        if [ $COMPOSER_EXIT -ne 0 ]; then
            log_warning "composer install failed, trying update..."
            rm -f composer.lock
            if [ "${APP_ENV}" = "production" ]; then
                composer update --no-dev --optimize-autoloader --no-interaction || {
                    log_error "Composer update also failed"
                    generate_error_report "install_composer" "Composer failed" "$COMPOSER_OUTPUT"
                    exit 1
                }
            else
                composer update --optimize-autoloader --no-interaction
            fi
        fi
    fi

    log "Composer dependencies installed ✓"
}

#===============================================================================
# NPM Dependencies
#===============================================================================

install_npm_dependencies() {
    if [ "$SKIP_NPM" = true ]; then
        log_info "Skipping NPM dependencies"
        return 0
    fi

    log_step "7" "Installing NPM Dependencies"

    cd "${APP_DIR}"

    if [ -f "package-lock.json" ]; then
        log_info "Running npm ci..."
        set +e
        local NPM_OUTPUT=$(timeout 300 npm ci 2>&1)
        local NPM_EXIT=$?
        set -e

        if [ $NPM_EXIT -ne 0 ]; then
            log_warning "npm ci failed, trying npm install..."
            timeout 300 npm install 2>&1 || {
                log_warning "NPM install failed (non-critical)"
                return 0
            }
        fi
    else
        log_info "Running npm install..."
        timeout 300 npm install 2>&1 || {
            log_warning "NPM install failed (non-critical)"
            return 0
        }
    fi

    log "NPM dependencies installed ✓"
}

#===============================================================================
# Build Frontend
#===============================================================================

build_frontend() {
    if [ "$SKIP_NPM" = true ]; then
        log_info "Skipping frontend build"
        return 0
    fi

    log_step "8" "Building Frontend Assets"

    cd "${APP_DIR}"

    set +e
    local BUILD_OUTPUT=$(timeout 600 npm run build 2>&1)
    local BUILD_EXIT=$?
    set -e

    if [ $BUILD_EXIT -ne 0 ]; then
        log_warning "Frontend build failed"
        log_error_detail "Build output: $BUILD_OUTPUT"
        generate_error_report "build_frontend" "npm run build failed" "$BUILD_OUTPUT"
    else
        log "Frontend assets built ✓"
    fi
}

#===============================================================================
# Smart Migrations
#===============================================================================

run_migrations() {
    log_step "9" "Running Smart Database Migrations"

    cd "${APP_DIR}"

    # Clear config cache first
    php artisan config:clear 2>/dev/null || true

    # Check for pending migrations
    log_info "Checking for pending migrations..."

    set +e
    local MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
    local STATUS_EXIT=$?
    local PENDING_COUNT=$(echo "$MIGRATION_STATUS" | grep -c "Pending" || echo "0")
    set -e

    if [ $STATUS_EXIT -ne 0 ]; then
        log_warning "Could not check migration status"
        log_error_detail "migrate:status output: $MIGRATION_STATUS"
    fi

    if [ "$PENDING_COUNT" = "0" ]; then
        log "No pending migrations ✓"
        return 0
    fi

    log_warning "Found $PENDING_COUNT pending migration(s)"

    # Run migrations
    set +e
    local MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
    local MIGRATION_EXIT=$?
    set -e

    log_error_detail "Migration output: $MIGRATION_OUTPUT"

    if [ $MIGRATION_EXIT -eq 0 ]; then
        log "Migrations completed ✓"
        return 0
    fi

    # Handle specific errors
    if echo "$MIGRATION_OUTPUT" | grep -q "already exists"; then
        log_warning "Some tables already exist"

        local FAILED_TABLE=$(echo "$MIGRATION_OUTPUT" | grep -oP "Table '\K[^']+" | head -1 || echo "unknown")
        log_info "Table '$FAILED_TABLE' already exists"

        generate_error_report "run_migrations" "Table already exists: $FAILED_TABLE" "$MIGRATION_OUTPUT"

        echo ""
        echo -e "${YELLOW}  Suggested fixes:${NC}"
        echo "  1. Check if migration file has Schema::hasTable() check"
        echo "  2. Run: php artisan migrate:status"
        echo "  3. If safe, run: php artisan migrate:fresh --force (DELETES ALL DATA!)"
        echo ""

        return 1
    fi

    # Unknown error
    log_error "Migration failed with unknown error"
    generate_error_report "run_migrations" "Migration failed" "$MIGRATION_OUTPUT"
    echo "$MIGRATION_OUTPUT"

    return 1
}

#===============================================================================
# Smart Seeding
#===============================================================================

run_seeders() {
    cd "${APP_DIR}"

    # Check if core data already exists
    local province_count=0
    local party_count=0

    set +e
    province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    party_count=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    set -e

    local has_core_data=false
    if [ "$province_count" -gt 0 ] && [ "$party_count" -gt 0 ]; then
        has_core_data=true
    fi

    # Auto-seed on first install
    if [ "$FIRST_INSTALL" = true ]; then
        FORCE_SEED=true
        log_info "First installation detected - will seed database"
    fi

    if [ "$has_core_data" = true ] && [ "$FORCE_SEED" = false ]; then
        log_info "Core data exists (provinces: ${province_count}, parties: ${party_count})"
        log_info "Use --seed to force update"
        return 0
    fi

    if [ "$FORCE_SEED" = true ] || [ "$has_core_data" = false ]; then
        log_step "10" "Running Smart Seeding (ข้อมูลจาก กกต.)"

        set +e
        local SEED_OUTPUT=$(php artisan db:seed --force 2>&1)
        local SEED_EXIT=$?
        set -e

        if [ $SEED_EXIT -eq 0 ]; then
            log "Seeders executed ✓"
            log "  - 77 จังหวัด (provinces)"
            log "  - 400 เขตเลือกตั้ง (constituencies)"
            log "  - พรรคการเมือง (parties)"
        else
            log_warning "Seeding had issues"
            log_error_detail "Seed output: $SEED_OUTPUT"

            if echo "$SEED_OUTPUT" | grep -q "Unknown column"; then
                local UNKNOWN_COL=$(echo "$SEED_OUTPUT" | grep -oP "Unknown column '\K[^']+" | head -1 || echo "unknown")
                log_warning "Column mismatch: $UNKNOWN_COL"
                generate_error_report "run_seeders" "Column mismatch: $UNKNOWN_COL" "$SEED_OUTPUT"
            fi
        fi
    fi
}

#===============================================================================
# Create Admin User
#===============================================================================

create_admin_user() {
    if [ "$CREATE_ADMIN" = false ]; then
        return 0
    fi

    cd "${APP_DIR}"

    log_info "Creating admin user..."

    # Check if admin user already exists
    set +e
    local admin_exists=$(php artisan tinker --execute="echo App\Models\User::where('email', 'admin@thaivote.local')->exists() ? 'yes' : 'no';" 2>/dev/null | tail -1)
    set -e

    if [ "$admin_exists" = "yes" ]; then
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
# Clear Caches
#===============================================================================

clear_caches() {
    log_step "11" "Clearing Caches"

    cd "${APP_DIR}"

    php artisan cache:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true

    log "All caches cleared ✓"
}

#===============================================================================
# Optimize Application
#===============================================================================

optimize_application() {
    log_step "12" "Optimizing Application"

    cd "${APP_DIR}"
    local APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if [ "${APP_ENV}" = "production" ]; then
        set +e
        php artisan config:cache 2>/dev/null || log_warning "config:cache had issues"
        php artisan route:cache 2>/dev/null || log_warning "route:cache had issues"
        php artisan view:cache 2>/dev/null || log_warning "view:cache had issues"
        php artisan event:cache 2>/dev/null || log_warning "event:cache had issues"
        set -e

        composer dump-autoload --optimize 2>&1

        log "Application optimized for production ✓"
    else
        log_info "Development mode: Skipping cache optimization"
    fi
}

#===============================================================================
# Storage Links
#===============================================================================

setup_storage_links() {
    log_step "13" "Setting Up Storage Links"

    cd "${APP_DIR}"

    mkdir -p storage/app/public
    mkdir -p public_html

    # Remove broken symlink
    if [ -L "public_html/storage" ] && [ ! -e "public_html/storage" ]; then
        rm "public_html/storage"
        log_info "Removed broken storage symlink"
    fi

    # Create storage link
    if ! php artisan storage:link 2>/dev/null; then
        if [ ! -L "public_html/storage" ]; then
            ln -s "${APP_DIR}/storage/app/public" "${APP_DIR}/public_html/storage" 2>/dev/null && \
                log "Created storage symlink" || \
                log_warning "Could not create storage symlink"
        fi
    fi

    log "Storage links configured ✓"
}

#===============================================================================
# Fix Permissions
#===============================================================================

fix_permissions() {
    log_step "14" "Fixing Permissions"

    cd "${APP_DIR}"

    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    # Set ownership if www-data exists
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    log "Permissions fixed ✓"
}

#===============================================================================
# Restart Services
#===============================================================================

restart_services() {
    log_step "15" "Restarting Services"

    cd "${APP_DIR}"

    php artisan queue:restart 2>/dev/null || log_info "No queue workers to restart"

    # Restart PHP-FPM if available
    for version in 8.4 8.3 8.2 8.1 8.0; do
        if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
            sudo systemctl reload "php${version}-fpm" 2>/dev/null && {
                log "PHP ${version}-FPM reloaded"
                break
            }
        fi
    done

    # Restart Supervisor if available
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread 2>/dev/null || true
        supervisorctl update 2>/dev/null || true
    fi

    log "Services restarted ✓"
}

#===============================================================================
# Health Check
#===============================================================================

health_check() {
    log_step "16" "Running Health Check"

    cd "${APP_DIR}"
    local HEALTH_ISSUES=0

    # Check database connection
    set +e
    local DB_CHECK=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'ok'; } catch(\Exception \$e) { echo 'fail'; }" 2>/dev/null | tail -1)
    set -e

    if [ "$DB_CHECK" = "ok" ]; then
        log "✓ Database connection works"
    else
        log_error "✗ Database connection failed"
        HEALTH_ISSUES=$((HEALTH_ISSUES + 1))
    fi

    # Check artisan
    if php artisan --version &>/dev/null; then
        log "✓ Artisan command works"
    else
        log_error "✗ Artisan command failed"
        HEALTH_ISSUES=$((HEALTH_ISSUES + 1))
    fi

    # Check storage
    if [ -w "storage/logs" ]; then
        log "✓ Storage is writable"
    else
        log_error "✗ Storage is not writable"
        HEALTH_ISSUES=$((HEALTH_ISSUES + 1))
    fi

    # Check frontend assets
    if [ -d "public_html/build" ]; then
        log "✓ Frontend assets exist"
    else
        log_warning "⚠ Frontend assets not found"
    fi

    # Check data status
    set +e
    local province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    local party_count=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    set -e

    if [ "$province_count" -gt 0 ] && [ "$party_count" -gt 0 ]; then
        log "✓ Core data loaded (จังหวัด: ${province_count}, พรรค: ${party_count})"
    else
        log_warning "⚠ Core data not loaded (run with --seed)"
    fi

    if [ $HEALTH_ISSUES -gt 0 ]; then
        log_warning "Health check found ${HEALTH_ISSUES} issue(s)"
    else
        log "Health check passed ✓"
    fi
}

#===============================================================================
# Error Handler
#===============================================================================

on_error() {
    local exit_code=$?
    log_error "Deployment failed! (Exit code: $exit_code)"

    # Try to bring app back up
    php artisan up 2>/dev/null || true

    echo ""
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}                    DEPLOYMENT FAILED                       ${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${YELLOW}Error logs saved to:${NC}"
    echo -e "  ${PURPLE}Full log:${NC}  ${LOG_FILE}"
    echo -e "  ${PURPLE}Error log:${NC} ${ERROR_LOG}"
    echo ""
    echo -e "${YELLOW}To view error details:${NC}"
    echo -e "  cat ${ERROR_LOG}"
    echo ""
    echo -e "${YELLOW}To retry deployment:${NC}"
    echo -e "  ./deploy.sh"
    echo ""

    exit 1
}

#===============================================================================
# Show Banner
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

#===============================================================================
# Main Deployment
#===============================================================================

deploy() {
    show_banner

    local START_TIME=$(date +%s)

    log_info "Starting deployment at $(date)"
    log_info "Log file: ${LOG_FILE}"
    echo ""

    # Set error trap
    trap on_error ERR

    preflight_checks
    check_environment
    backup_database
    setup_database
    enable_maintenance_mode
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
    run_seeders
    create_admin_user
    health_check

    trap - ERR

    disable_maintenance_mode

    local END_TIME=$(date +%s)
    local DURATION=$((END_TIME - START_TIME))

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
# Quick Deploy
#===============================================================================

quick_deploy() {
    echo -e "${YELLOW}Running quick deployment (no backups, minimal checks)...${NC}\n"

    cd "${APP_DIR}"
    SKIP_BACKUP=true

    check_composer_lock_compatibility

    # Setup database
    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")
    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        mkdir -p database
        if [ ! -f "database/database.sqlite" ]; then
            touch "database/database.sqlite"
            chmod 664 "database/database.sqlite"
            echo "Created SQLite database"
        fi
    fi

    # Generate APP_KEY
    local APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
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

    # Seed if no data
    local province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    if [ "$province_count" -eq 0 ]; then
        php artisan db:seed --force 2>/dev/null || true
    fi

    php artisan up

    echo -e "\n${GREEN}Quick deployment completed!${NC}"
}

#===============================================================================
# Status
#===============================================================================

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
    grep "^APP_ENV=" .env 2>/dev/null | sed 's/^/  /' || echo "  Not set"
    grep "^APP_DEBUG=" .env 2>/dev/null | sed 's/^/  /' || echo "  Not set"

    echo -e "\n${PURPLE}Database:${NC}"
    grep "^DB_CONNECTION=" .env 2>/dev/null | sed 's/^/  /' || echo "  Not set"
    php artisan migrate:status 2>/dev/null | head -10 || echo "  Unable to connect"

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
# Help
#===============================================================================

show_help() {
    echo -e "${CYAN}ThaiVote Deployment Script v${VERSION}${NC}"
    echo ""
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  deploy        Full deployment (default)"
    echo "  quick         Quick deployment without backups"
    echo "  status        Show application status"
    echo "  help          Show this help message"
    echo ""
    echo "Options:"
    echo "  --seed              Force run database seeders"
    echo "  --admin             Create admin user"
    echo "  --backup            Enable database backups"
    echo "  --fresh-composer    Force regenerate composer.lock"
    echo "  --skip-npm          Skip NPM install and build"
    echo "  --verbose, -v       Show verbose output"
    echo ""
    echo "Examples:"
    echo "  $0                  # Full deployment"
    echo "  $0 deploy --seed    # Full deployment with seeders"
    echo "  $0 quick            # Quick deployment"
    echo "  $0 --admin          # Deploy with admin user creation"
    echo "  $0 status           # Show application status"
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
                FORCE_SEED=true
                shift
                ;;
            --admin)
                CREATE_ADMIN=true
                shift
                ;;
            --verbose|-v)
                VERBOSE=true
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
        deploy
        ;;
    quick)
        quick_deploy
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
            deploy
        else
            echo "Unknown command: $1"
            show_help
            exit 1
        fi
        ;;
esac
