#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Smart Automated Deployment Script v4.1
#
# Features:
#   - Smart migration handling (detects and reports errors)
#   - Intelligent seeding (skip existing data)
#   - Detailed error logging and reporting
#   - Automatic rollback on failure
#   - PHP version compatibility checks
#   - Force Reset - Nuclear option to fix broken installations
#
# Usage:
#   ./deploy.sh              # Full deployment
#   ./deploy.sh quick        # Quick deployment (no backups)
#   ./deploy.sh force-reset  # Nuclear option - delete & reinstall all (keeps DB)
#   ./deploy.sh repair       # Auto-repair system issues
#   ./deploy.sh doctor       # Diagnose and auto-fix
#   ./deploy.sh --seed       # Force run seeders
#   ./deploy.sh --admin      # Create admin user
#   ./deploy.sh status       # Show application status
#===============================================================================

set -e

# Script version
VERSION="4.1"

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
DB_AVAILABLE=true  # Will be set by check_database_availability

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
# Helper Functions for Tools
#===============================================================================

# Get composer command (supports local composer.phar)
get_composer_cmd() {
    if command -v composer &> /dev/null; then
        echo "composer"
    elif [ -f "${APP_DIR}/composer.phar" ]; then
        echo "php ${APP_DIR}/composer.phar"
    else
        echo "composer"
    fi
}

# Run composer with auto-detection
run_composer() {
    local COMPOSER_CMD=$(get_composer_cmd)
    $COMPOSER_CMD "$@"
}

#===============================================================================
# Auto-install Missing Tools
#===============================================================================

install_composer() {
    log_info "Composer not found. Installing Composer..."

    cd "${APP_DIR}"

    # Download composer installer
    local EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    local ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_warning "Composer installer checksum mismatch, trying alternative method..."
        rm -f composer-setup.php

        # Alternative: download composer.phar directly
        curl -sS https://getcomposer.org/composer-stable.phar -o composer.phar 2>/dev/null || \
        wget -q https://getcomposer.org/composer-stable.phar -O composer.phar 2>/dev/null

        if [ -f "composer.phar" ]; then
            chmod +x composer.phar
            # Try to move to global location
            if [ -w "/usr/local/bin" ]; then
                mv composer.phar /usr/local/bin/composer
                log "Composer installed globally ✓"
            else
                # Keep local
                log "Composer installed locally as composer.phar ✓"
                # Create wrapper function
                alias composer="php ${APP_DIR}/composer.phar"
            fi
        else
            log_error "Failed to download Composer"
            return 1
        fi
    else
        php composer-setup.php --quiet
        rm composer-setup.php

        if [ -f "composer.phar" ]; then
            chmod +x composer.phar
            if [ -w "/usr/local/bin" ]; then
                mv composer.phar /usr/local/bin/composer
                log "Composer installed globally ✓"
            else
                log "Composer installed locally as composer.phar ✓"
            fi
        fi
    fi

    return 0
}

install_node_npm() {
    log_info "Node.js/npm not found. Attempting to install..."

    # Check if we can use package manager
    if command -v apt-get &> /dev/null; then
        log_info "Installing Node.js via apt..."
        sudo apt-get update -qq 2>/dev/null || true
        sudo apt-get install -y nodejs npm 2>/dev/null || {
            # Try NodeSource repository
            curl -fsSL https://deb.nodesource.com/setup_20.x 2>/dev/null | sudo -E bash - 2>/dev/null || true
            sudo apt-get install -y nodejs 2>/dev/null || {
                log_warning "Could not install Node.js automatically"
                return 1
            }
        }
        log "Node.js installed ✓"
        return 0
    elif command -v yum &> /dev/null; then
        log_info "Installing Node.js via yum..."
        sudo yum install -y nodejs npm 2>/dev/null || {
            log_warning "Could not install Node.js automatically"
            return 1
        }
        log "Node.js installed ✓"
        return 0
    elif command -v dnf &> /dev/null; then
        log_info "Installing Node.js via dnf..."
        sudo dnf install -y nodejs npm 2>/dev/null || {
            log_warning "Could not install Node.js automatically"
            return 1
        }
        log "Node.js installed ✓"
        return 0
    fi

    log_warning "No package manager found. Please install Node.js manually."
    return 1
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

    # Check Composer (install if missing)
    if ! command -v composer &> /dev/null; then
        # Check for local composer.phar
        if [ -f "${APP_DIR}/composer.phar" ]; then
            log "Composer (local) ✓"
            # Create function to use local composer
            composer() {
                php "${APP_DIR}/composer.phar" "$@"
            }
            export -f composer
        else
            install_composer || {
                log_error "Composer is required but could not be installed"
                exit 1
            }
        fi
    else
        log "Composer ✓"
    fi

    # Check Node.js (try to install if missing)
    if command -v node &> /dev/null; then
        log "Node.js $(node --version) ✓"
    else
        log_warning "Node.js not installed (required for frontend)"
        install_node_npm || {
            log_warning "Will skip frontend build"
            SKIP_NPM=true
        }
        # Re-check after install attempt
        if command -v node &> /dev/null; then
            log "Node.js $(node --version) ✓"
            SKIP_NPM=false
        fi
    fi

    # Check npm
    if command -v npm &> /dev/null; then
        log "npm $(npm --version) ✓"
    else
        if [ "$SKIP_NPM" = false ]; then
            log_warning "npm not found, will skip frontend build"
        fi
        SKIP_NPM=true
    fi

    # Check composer.lock compatibility
    check_composer_lock_compatibility

    log "All pre-flight checks passed ✓"
}

#===============================================================================
# Laravel Bootstrap (First-time Setup)
#===============================================================================

bootstrap_laravel() {
    log_step "1.5" "Bootstrapping Laravel Application"

    cd "${APP_DIR}"

    # Create all required directories
    log_info "Creating Laravel directories..."

    # Storage directories
    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/testing
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    # Bootstrap cache directory
    mkdir -p bootstrap/cache

    # Database directory (for SQLite)
    mkdir -p database

    # Public directories
    mkdir -p public_html/build

    log "Laravel directories created ✓"

    # Check PHP extensions
    log_info "Checking PHP extensions..."
    local MISSING_EXT=""

    for ext in pdo mbstring openssl tokenizer xml ctype json bcmath fileinfo; do
        if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
            MISSING_EXT="${MISSING_EXT} ${ext}"
        fi
    done

    if [ -n "$MISSING_EXT" ]; then
        log_warning "Missing PHP extensions:${MISSING_EXT}"
        log_info "Install with: sudo apt install php-{pdo,mbstring,xml,bcmath,fileinfo}"
    else
        log "All required PHP extensions installed ✓"
    fi

    # Create .env if not exists
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            log_info "Creating .env from .env.example..."
            cp .env.example .env
            FIRST_INSTALL=true
            log ".env file created ✓"
        else
            log_error ".env.example not found!"
            exit 1
        fi
    fi

    # Set basic APP settings
    local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        log_info "Generating application key..."
        php artisan key:generate --force 2>/dev/null || {
            # If artisan fails (no vendor), we'll generate key after composer install
            log_info "Will generate APP_KEY after composer install"
        }
    fi

    # Set file permissions
    log_info "Setting file permissions..."
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod 644 .env 2>/dev/null || true

    # Set ownership if www-data exists
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    # Create .gitignore for storage if not exists
    if [ ! -f "storage/.gitignore" ]; then
        echo "*
!public/
!.gitignore" > storage/.gitignore
    fi

    if [ ! -f "storage/app/.gitignore" ]; then
        echo "*
!public/
!.gitignore" > storage/app/.gitignore
    fi

    # Create log file placeholder
    touch storage/logs/laravel.log 2>/dev/null || true
    chmod 664 storage/logs/laravel.log 2>/dev/null || true

    log "Laravel bootstrap completed ✓"
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

# Update .env variable safely (same as install.sh)
update_env_var() {
    local key="$1"
    local value="$2"
    local env_file="${APP_DIR}/.env"

    local needs_quoting=false
    if [[ "$value" == *" "* ]] || [[ "$value" == *"	"* ]]; then needs_quoting=true; fi
    if [[ "$value" == *'$'* ]]; then needs_quoting=true; fi
    if [[ "$value" == *'"'* ]]; then needs_quoting=true; fi
    if [[ "$value" == *"'"* ]]; then needs_quoting=true; fi
    if [[ "$value" == *'`'* ]]; then needs_quoting=true; fi
    if [[ "$value" == *'\'* ]]; then needs_quoting=true; fi
    if [[ "$value" == *'@'* ]]; then needs_quoting=true; fi

    if [ "$needs_quoting" = true ]; then
        value="${value//\\/\\\\}"
        value="${value//\"/\\\"}"
        value="${value//\$/\\\$}"
        value="${value//\`/\\\`}"
        value="\"$value\""
    fi

    local new_line="${key}=${value}"

    if grep -qE "^#?\s*${key}=" "$env_file" 2>/dev/null; then
        local temp_file=$(mktemp)
        grep -vE "^#?\s*${key}=" "$env_file" > "$temp_file"
        echo "$new_line" >> "$temp_file"
        mv "$temp_file" "$env_file"
    else
        echo "$new_line" >> "$env_file"
    fi
}

# Check if database is available and configure fallback drivers
check_database_availability() {
    cd "${APP_DIR}"

    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")
    local db_available=false

    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        # Check if pdo_sqlite is available
        if php -m 2>/dev/null | grep -qi "pdo_sqlite"; then
            db_available=true
        else
            log_warning "pdo_sqlite driver not available"
        fi
    elif [ "${DB_CONNECTION}" = "mysql" ]; then
        # Check if we can connect to MySQL
        set +e
        local DB_CHECK=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'ok'; } catch(\Exception \$e) { echo 'fail'; }" 2>/dev/null | tail -1)
        set -e
        if [ "$DB_CHECK" = "ok" ]; then
            db_available=true
        else
            log_warning "MySQL connection not available"
        fi
    fi

    if [ "$db_available" = false ]; then
        log_warning "Database not available - switching to file-based drivers"
        log_info "SESSION_DRIVER → file"
        log_info "CACHE_STORE → file"
        log_info "QUEUE_CONNECTION → sync"

        update_env_var "SESSION_DRIVER" "file"
        update_env_var "CACHE_STORE" "file"
        update_env_var "QUEUE_CONNECTION" "sync"

        DB_AVAILABLE=false
    else
        DB_AVAILABLE=true
        log "Database connection available ✓"
    fi
}

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

    # Check database availability and configure fallback if needed
    check_database_availability

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
    local BEFORE_COMMIT=$(git rev-parse --short HEAD 2>/dev/null)
    local BEFORE_MESSAGE=$(git log -1 --pretty=format:"%s" 2>/dev/null)

    log_info "📍 Current branch: ${CYAN}${BRANCH}${NC}"
    log_info "📦 Current commit: ${CYAN}${BEFORE_COMMIT}${NC} - ${BEFORE_MESSAGE}"

    # Fetch updates
    set +e
    local GIT_OUTPUT=$(git fetch origin 2>&1)
    local GIT_EXIT=$?
    set -e

    if [ $GIT_EXIT -ne 0 ]; then
        log_warning "Git fetch failed (offline mode?)"
        log_error_detail "Git fetch output: $GIT_OUTPUT"
        return 0
    fi
    log "Fetched updates from remote ✓"

    # Check if remote branch exists
    if git ls-remote --exit-code --heads origin "${BRANCH}" >/dev/null 2>&1; then
        local REMOTE_BRANCH="origin/${BRANCH}"
    else
        log_warning "Remote branch '${BRANCH}' not found, using 'origin/main' instead"
        local REMOTE_BRANCH="origin/main"
    fi

    # Check if there are updates
    local BEHIND_COUNT=$(git rev-list --count HEAD..${REMOTE_BRANCH} 2>/dev/null || echo "0")

    if [ "$BEHIND_COUNT" = "0" ]; then
        log "Already up to date with ${REMOTE_BRANCH} ✓"
        return 0
    fi

    log_info "🔄 Found ${BEHIND_COUNT} new commit(s) from ${REMOTE_BRANCH}"

    # Pull updates
    set +e
    GIT_OUTPUT=$(git pull origin "${BRANCH}" 2>&1)
    GIT_EXIT=$?
    set -e

    if [ $GIT_EXIT -ne 0 ]; then
        log_warning "Git pull had issues"
        log_error_detail "Git pull output: $GIT_OUTPUT"
        return 0
    fi

    # Show what was pulled
    local AFTER_COMMIT=$(git rev-parse --short HEAD 2>/dev/null)
    local AFTER_MESSAGE=$(git log -1 --pretty=format:"%s" 2>/dev/null)

    echo ""
    log "✅ Code updated successfully!"
    log_info "📦 New commit: ${CYAN}${AFTER_COMMIT}${NC} - ${AFTER_MESSAGE}"

    # Show summary of changes
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📝 Changes pulled:${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Show commit log
    git log --oneline --decorate --graph "${BEFORE_COMMIT}..${AFTER_COMMIT}" 2>/dev/null | head -10 || true

    # Show file changes summary
    local CHANGED_FILES=$(git diff --stat "${BEFORE_COMMIT}..${AFTER_COMMIT}" 2>/dev/null | tail -1 || echo "")
    if [ -n "$CHANGED_FILES" ]; then
        echo ""
        echo -e "${PURPLE}📊 Files changed:${NC}"
        echo "$CHANGED_FILES"
    fi
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

#===============================================================================
# Composer Dependencies
#===============================================================================

install_composer_dependencies() {
    log_step "6" "Installing Composer Dependencies"

    cd "${APP_DIR}"
    local APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    APP_ENV=${APP_ENV:-production}

    # Log which composer we're using
    log_info "Using: $(get_composer_cmd)"

    if [ "$FRESH_COMPOSER" = true ]; then
        log_warning "Regenerating composer.lock for PHP $(get_php_version)..."
        rm -f composer.lock

        set +e
        local COMPOSER_OUTPUT
        if [ "${APP_ENV}" = "production" ]; then
            COMPOSER_OUTPUT=$(run_composer update --no-dev --optimize-autoloader --no-interaction 2>&1)
        else
            COMPOSER_OUTPUT=$(run_composer update --optimize-autoloader --no-interaction 2>&1)
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
            COMPOSER_OUTPUT=$(run_composer install --no-dev --optimize-autoloader --no-interaction 2>&1)
        else
            COMPOSER_OUTPUT=$(run_composer install --optimize-autoloader --no-interaction 2>&1)
        fi
        local COMPOSER_EXIT=$?
        set -e

        if [ $COMPOSER_EXIT -ne 0 ]; then
            log_warning "composer install failed, trying update..."
            rm -f composer.lock
            if [ "${APP_ENV}" = "production" ]; then
                run_composer update --no-dev --optimize-autoloader --no-interaction || {
                    log_error "Composer update also failed"
                    generate_error_report "install_composer" "Composer failed" "$COMPOSER_OUTPUT"
                    exit 1
                }
            else
                run_composer update --optimize-autoloader --no-interaction
            fi
        fi
    fi

    log "Composer dependencies installed ✓"

    # CRITICAL: Clear bootstrap cache IMMEDIATELY after composer install
    # This prevents "Class not found" errors from stale cached routes
    log_info "Clearing bootstrap cache (prevent stale class references)..."
    rm -f "${APP_DIR}/bootstrap/cache/config.php" 2>/dev/null || true
    rm -f "${APP_DIR}/bootstrap/cache/routes-v7.php" 2>/dev/null || true
    rm -f "${APP_DIR}/bootstrap/cache/services.php" 2>/dev/null || true
    rm -f "${APP_DIR}/bootstrap/cache/packages.php" 2>/dev/null || true
    rm -f "${APP_DIR}/bootstrap/cache/events.php" 2>/dev/null || true
    log "Bootstrap cache cleared ✓"

    # Generate APP_KEY if not set (after composer install, artisan is available)
    local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        log_info "Generating APP_KEY..."
        php artisan key:generate --force
        log "APP_KEY generated ✓"
    fi
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

    # Skip if database not available
    if [ "$DB_AVAILABLE" = false ]; then
        log_warning "Database not available - skipping migrations"
        log_info "App will work with limited functionality (no database)"
        return 0
    fi

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

    # Skip if database not available
    if [ "$DB_AVAILABLE" = false ]; then
        log_info "Database not available - skipping seeders"
        return 0
    fi

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

        run_composer dump-autoload --optimize 2>&1

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
    bootstrap_laravel
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

    # Bootstrap Laravel directories
    echo -e "${BLUE}[i]${NC} Creating Laravel directories..."
    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/testing
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    mkdir -p database
    mkdir -p public_html/build
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    # Create .env if not exists
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            echo -e "${BLUE}[i]${NC} Creating .env from .env.example..."
            cp .env.example .env
            FIRST_INSTALL=true
        fi
    fi

    # Setup database
    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")
    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        if [ ! -f "database/database.sqlite" ]; then
            touch "database/database.sqlite"
            chmod 664 "database/database.sqlite"
            echo -e "${GREEN}[✓]${NC} Created SQLite database"
        fi
    fi

    php artisan down --retry=60 2>/dev/null || true

    # Pull if git repo
    if [ -d ".git" ]; then
        echo -e "${BLUE}[i]${NC} Pulling latest code..."
        git pull origin main 2>/dev/null || true
    fi

    # Install Composer if missing
    if ! command -v composer &> /dev/null && [ ! -f "composer.phar" ]; then
        echo -e "${BLUE}[i]${NC} Installing Composer..."
        install_composer || {
            echo -e "${RED}[✗]${NC} Failed to install Composer"
            exit 1
        }
    fi

    # Composer
    echo -e "${BLUE}[i]${NC} Installing Composer dependencies..."
    if [ "$FRESH_COMPOSER" = true ]; then
        rm -f composer.lock
        run_composer update --no-dev --optimize-autoloader --no-interaction
    else
        run_composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || {
            rm -f composer.lock
            run_composer update --no-dev --optimize-autoloader --no-interaction
        }
    fi

    # CRITICAL: Clear bootstrap cache immediately after composer install
    echo -e "${BLUE}[i]${NC} Clearing bootstrap cache..."
    rm -f bootstrap/cache/config.php 2>/dev/null || true
    rm -f bootstrap/cache/routes-v7.php 2>/dev/null || true
    rm -f bootstrap/cache/services.php 2>/dev/null || true
    rm -f bootstrap/cache/packages.php 2>/dev/null || true
    rm -f bootstrap/cache/events.php 2>/dev/null || true

    # Generate APP_KEY after composer install
    local APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        echo -e "${BLUE}[i]${NC} Generating APP_KEY..."
        php artisan key:generate --force
    fi

    # NPM & Build (if node available)
    if command -v npm &> /dev/null; then
        echo -e "${BLUE}[i]${NC} Installing NPM dependencies..."
        npm ci 2>/dev/null || npm install 2>/dev/null || true
        echo -e "${BLUE}[i]${NC} Building frontend assets..."
        npm run build 2>/dev/null || true
    fi

    echo -e "${BLUE}[i]${NC} Running migrations..."
    php artisan migrate --force 2>/dev/null || true

    echo -e "${BLUE}[i]${NC} Setting up storage links..."
    php artisan storage:link 2>/dev/null || true

    echo -e "${BLUE}[i]${NC} Clearing and optimizing caches..."
    php artisan cache:clear 2>/dev/null || true
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    php artisan queue:restart 2>/dev/null || true

    # Seed if no data
    local province_count=$(php artisan tinker --execute="echo App\Models\Province::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    if [ "$province_count" -eq 0 ]; then
        echo -e "${BLUE}[i]${NC} Seeding database with initial data..."
        php artisan db:seed --force 2>/dev/null || true
    fi

    php artisan up

    echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}   ${GREEN}✅ QUICK DEPLOYMENT COMPLETED!${NC}                                          ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"
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
# Diagnose - ตรวจสอบปัญหาแบบละเอียด
#===============================================================================

diagnose() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}   ${PURPLE}🔍 ThaiVote System Diagnosis${NC}                                            ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    cd "${APP_DIR}"
    local ISSUES=0
    local WARNINGS=0

    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}1. ตรวจสอบไฟล์พื้นฐาน (Core Files)${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Check artisan
    if [ -f "artisan" ]; then
        echo -e "${GREEN}   ✓${NC} artisan"
    else
        echo -e "${RED}   ✗${NC} artisan - ไม่พบไฟล์ artisan (นี่ไม่ใช่โปรเจค Laravel)"
        ISSUES=$((ISSUES + 1))
    fi

    # Check composer.json
    if [ -f "composer.json" ]; then
        echo -e "${GREEN}   ✓${NC} composer.json"
    else
        echo -e "${RED}   ✗${NC} composer.json - ไม่พบไฟล์"
        ISSUES=$((ISSUES + 1))
    fi

    # Check .env
    if [ -f ".env" ]; then
        echo -e "${GREEN}   ✓${NC} .env"

        # Check APP_KEY
        local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
            echo -e "${RED}   ✗${NC} APP_KEY - ไม่ได้ตั้งค่า"
            ISSUES=$((ISSUES + 1))
        else
            echo -e "${GREEN}   ✓${NC} APP_KEY ตั้งค่าแล้ว"
        fi
    else
        if [ -f ".env.example" ]; then
            echo -e "${RED}   ✗${NC} .env - ไม่พบ (ต้องสร้างจาก .env.example)"
        else
            echo -e "${RED}   ✗${NC} .env - ไม่พบ (ไม่มี .env.example ด้วย)"
        fi
        ISSUES=$((ISSUES + 1))
    fi

    # Check vendor
    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        echo -e "${GREEN}   ✓${NC} vendor/ (Composer dependencies)"
    else
        echo -e "${RED}   ✗${NC} vendor/ - ยังไม่ได้รัน composer install"
        ISSUES=$((ISSUES + 1))
    fi

    # Check node_modules
    if [ -d "node_modules" ]; then
        echo -e "${GREEN}   ✓${NC} node_modules/ (NPM dependencies)"
    else
        echo -e "${YELLOW}   ⚠${NC} node_modules/ - ยังไม่ได้รัน npm install"
        WARNINGS=$((WARNINGS + 1))
    fi

    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}2. ตรวจสอบโฟลเดอร์ Storage${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Check storage directories
    local storage_dirs=("storage/app" "storage/app/public" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "storage/logs")
    for dir in "${storage_dirs[@]}"; do
        if [ -d "$dir" ]; then
            if [ -w "$dir" ]; then
                echo -e "${GREEN}   ✓${NC} $dir (writable)"
            else
                echo -e "${RED}   ✗${NC} $dir (not writable)"
                ISSUES=$((ISSUES + 1))
            fi
        else
            echo -e "${RED}   ✗${NC} $dir - ไม่พบโฟลเดอร์"
            ISSUES=$((ISSUES + 1))
        fi
    done

    # Check bootstrap/cache
    if [ -d "bootstrap/cache" ]; then
        if [ -w "bootstrap/cache" ]; then
            echo -e "${GREEN}   ✓${NC} bootstrap/cache (writable)"
        else
            echo -e "${RED}   ✗${NC} bootstrap/cache (not writable)"
            ISSUES=$((ISSUES + 1))
        fi
    else
        echo -e "${RED}   ✗${NC} bootstrap/cache - ไม่พบโฟลเดอร์"
        ISSUES=$((ISSUES + 1))
    fi

    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}3. ตรวจสอบฐานข้อมูล (Database)${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ -f ".env" ]; then
        local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        echo -e "   Database Driver: ${CYAN}${DB_CONNECTION:-sqlite}${NC}"

        if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
            if [ -f "database/database.sqlite" ]; then
                echo -e "${GREEN}   ✓${NC} database/database.sqlite"
            else
                echo -e "${RED}   ✗${NC} database/database.sqlite - ไม่พบไฟล์"
                ISSUES=$((ISSUES + 1))
            fi
        elif [ "${DB_CONNECTION}" = "mysql" ]; then
            echo -e "   Host: $(grep '^DB_HOST=' .env 2>/dev/null | cut -d '=' -f2 || echo 'N/A')"
            echo -e "   Database: $(grep '^DB_DATABASE=' .env 2>/dev/null | cut -d '=' -f2 || echo 'N/A')"

            # Try database connection (only if vendor exists)
            if [ -f "vendor/autoload.php" ]; then
                set +e
                local DB_CHECK=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'ok'; } catch(\Exception \$e) { echo 'fail'; }" 2>/dev/null | tail -1)
                set -e
                if [ "$DB_CHECK" = "ok" ]; then
                    echo -e "${GREEN}   ✓${NC} สามารถเชื่อมต่อ MySQL ได้"
                else
                    echo -e "${RED}   ✗${NC} ไม่สามารถเชื่อมต่อ MySQL"
                    ISSUES=$((ISSUES + 1))
                fi
            fi
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} ไม่มีไฟล์ .env - ไม่สามารถตรวจสอบฐานข้อมูล"
    fi

    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}4. ตรวจสอบ Frontend Assets${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ -d "public_html/build" ]; then
        local asset_count=$(find public_html/build -type f 2>/dev/null | wc -l)
        if [ "$asset_count" -gt 0 ]; then
            echo -e "${GREEN}   ✓${NC} public_html/build/ (${asset_count} files)"
        else
            echo -e "${YELLOW}   ⚠${NC} public_html/build/ - โฟลเดอร์ว่าง (ต้อง npm run build)"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} public_html/build/ - ยังไม่ได้ build frontend"
        WARNINGS=$((WARNINGS + 1))
    fi

    # Check storage symlink
    if [ -L "public_html/storage" ]; then
        if [ -e "public_html/storage" ]; then
            echo -e "${GREEN}   ✓${NC} public_html/storage symlink"
        else
            echo -e "${RED}   ✗${NC} public_html/storage - symlink หัก (broken)"
            ISSUES=$((ISSUES + 1))
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} public_html/storage - ยังไม่มี symlink"
        WARNINGS=$((WARNINGS + 1))
    fi

    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}5. ตรวจสอบ PHP Extensions${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    local required_extensions=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "fileinfo")
    for ext in "${required_extensions[@]}"; do
        if php -m 2>/dev/null | grep -qi "^${ext}$"; then
            echo -e "${GREEN}   ✓${NC} ${ext}"
        else
            echo -e "${RED}   ✗${NC} ${ext} - ไม่พบ extension"
            ISSUES=$((ISSUES + 1))
        fi
    done

    # Check pdo_sqlite or pdo_mysql
    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "sqlite")
    if [ "${DB_CONNECTION}" = "sqlite" ]; then
        if php -m 2>/dev/null | grep -qi "pdo_sqlite"; then
            echo -e "${GREEN}   ✓${NC} pdo_sqlite"
        else
            echo -e "${RED}   ✗${NC} pdo_sqlite - ไม่พบ extension"
            ISSUES=$((ISSUES + 1))
        fi
    elif [ "${DB_CONNECTION}" = "mysql" ]; then
        if php -m 2>/dev/null | grep -qi "pdo_mysql"; then
            echo -e "${GREEN}   ✓${NC} pdo_mysql"
        else
            echo -e "${RED}   ✗${NC} pdo_mysql - ไม่พบ extension"
            ISSUES=$((ISSUES + 1))
        fi
    fi

    # Summary
    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}📊 สรุปผลการวิเคราะห์${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ $ISSUES -eq 0 ] && [ $WARNINGS -eq 0 ]; then
        echo -e "\n${GREEN}   ✅ ระบบปกติ ไม่พบปัญหา${NC}\n"
    else
        if [ $ISSUES -gt 0 ]; then
            echo -e "\n${RED}   ❌ พบปัญหาร้ายแรง: ${ISSUES} รายการ${NC}"
        fi
        if [ $WARNINGS -gt 0 ]; then
            echo -e "${YELLOW}   ⚠️  พบ warnings: ${WARNINGS} รายการ${NC}"
        fi
        echo -e "\n${CYAN}   💡 แนะนำ: รัน ./deploy.sh repair เพื่อซ่อมแซมอัตโนมัติ${NC}\n"
    fi

    return $ISSUES
}

#===============================================================================
# Force Reset - รีเซ็ตทุกอย่างให้ Laravel พร้อมใช้งาน (ไม่ลบข้อมูลฐานข้อมูล)
#===============================================================================

force_reset() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}   ${RED}⚡ ThaiVote FORCE RESET - Nuclear Option${NC}                                ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}   ${YELLOW}ลบและสร้างใหม่ทุกอย่าง ยกเว้นฐานข้อมูล${NC}                                    ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    cd "${APP_DIR}"

    # Confirm with user
    if [ -t 0 ]; then
        echo -e "${RED}⚠️  คำเตือน: จะลบและสร้างใหม่:${NC}"
        echo "   - vendor/ (Composer dependencies)"
        echo "   - node_modules/ (NPM dependencies)"
        echo "   - bootstrap/cache/*"
        echo "   - storage/framework/cache/*"
        echo "   - storage/framework/sessions/*"
        echo "   - storage/framework/views/*"
        echo "   - public_html/build/*"
        echo "   - composer.lock"
        echo ""
        echo -e "${GREEN}✓ จะไม่ลบ:${NC}"
        echo "   - ฐานข้อมูล (database.sqlite หรือ MySQL data)"
        echo "   - .env (configuration)"
        echo "   - storage/app/* (uploaded files)"
        echo "   - storage/logs/*"
        echo ""
        read -p "ต้องการดำเนินการ Force Reset? (y/N): " confirm
        if [[ ! "$confirm" =~ ^[Yy](es)?$ ]]; then
            echo -e "\n${YELLOW}ยกเลิกการ Force Reset${NC}\n"
            exit 0
        fi
    fi

    echo -e "\n${PURPLE}เริ่มต้น Force Reset...${NC}\n"

    local STEP=0
    local TOTAL_STEPS=12

    #---------------------------------------------------------------------------
    # Step 1: หยุด services ที่กำลังทำงาน
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} หยุด services ที่กำลังทำงาน..."

    # Stop queue workers and reverb if running
    pkill -f "artisan queue:work" 2>/dev/null || true
    pkill -f "artisan reverb:start" 2>/dev/null || true
    php artisan down 2>/dev/null || true
    echo -e "${GREEN}   ✓${NC} หยุด services เรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 2: ลบ cache และ compiled files ทั้งหมด
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ลบ cache และ compiled files..."

    rm -rf bootstrap/cache/*.php 2>/dev/null || true
    rm -rf bootstrap/cache/.gitignore 2>/dev/null || true
    rm -rf storage/framework/cache/data/* 2>/dev/null || true
    rm -rf storage/framework/sessions/* 2>/dev/null || true
    rm -rf storage/framework/views/*.php 2>/dev/null || true
    rm -rf storage/framework/testing/* 2>/dev/null || true
    echo -e "${GREEN}   ✓${NC} ลบ cache เรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 3: ลบ vendor directory
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ลบ vendor/ และ composer.lock..."

    rm -rf vendor 2>/dev/null || true
    rm -f composer.lock 2>/dev/null || true
    echo -e "${GREEN}   ✓${NC} ลบ vendor/ เรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 4: ลบ node_modules และ build
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ลบ node_modules/ และ build files..."

    rm -rf node_modules 2>/dev/null || true
    rm -f package-lock.json 2>/dev/null || true
    rm -rf public_html/build 2>/dev/null || true
    rm -rf public_html/hot 2>/dev/null || true
    echo -e "${GREEN}   ✓${NC} ลบ node_modules/ เรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 5: สร้างโครงสร้างโฟลเดอร์ใหม่
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} สร้างโครงสร้างโฟลเดอร์..."

    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/app/private
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/testing
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    mkdir -p database
    mkdir -p public_html/build

    # Create .gitignore files
    echo "*
!.gitignore" > bootstrap/cache/.gitignore

    echo -e "${GREEN}   ✓${NC} สร้างโฟลเดอร์เรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 6: ตรวจสอบและสร้าง .env
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ตรวจสอบ .env..."

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            echo -e "${GREEN}   ✓${NC} สร้าง .env จาก .env.example"
        else
            echo -e "${RED}   ✗${NC} ไม่พบ .env และ .env.example"
            exit 1
        fi
    else
        echo -e "${GREEN}   ✓${NC} มี .env อยู่แล้ว"
    fi

    #---------------------------------------------------------------------------
    # Step 7: ติดตั้ง Composer dependencies ใหม่
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ติดตั้ง Composer dependencies..."

    # Install composer if missing
    if ! command -v composer &> /dev/null && [ ! -f "composer.phar" ]; then
        echo -e "${YELLOW}   ⚠${NC} กำลังติดตั้ง Composer..."
        install_composer || {
            echo -e "${RED}   ✗${NC} ไม่สามารถติดตั้ง Composer"
            exit 1
        }
    fi

    local APP_ENV=$(grep "^APP_ENV=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "local")

    set +e
    local COMPOSER_OUTPUT
    if [ "${APP_ENV}" = "production" ]; then
        COMPOSER_OUTPUT=$(run_composer install --no-dev --optimize-autoloader --no-interaction 2>&1)
    else
        COMPOSER_OUTPUT=$(run_composer install --optimize-autoloader --no-interaction 2>&1)
    fi
    local COMPOSER_EXIT=$?
    set -e

    if [ $COMPOSER_EXIT -eq 0 ]; then
        echo -e "${GREEN}   ✓${NC} ติดตั้ง Composer dependencies เรียบร้อย"
    else
        echo -e "${YELLOW}   ⚠${NC} Composer install ล้มเหลว ลองใหม่ด้วย update..."
        log_error_detail "Composer output: $COMPOSER_OUTPUT"

        set +e
        if [ "${APP_ENV}" = "production" ]; then
            COMPOSER_OUTPUT=$(run_composer update --no-dev --optimize-autoloader --no-interaction 2>&1)
        else
            COMPOSER_OUTPUT=$(run_composer update --optimize-autoloader --no-interaction 2>&1)
        fi
        COMPOSER_EXIT=$?
        set -e

        if [ $COMPOSER_EXIT -eq 0 ]; then
            echo -e "${GREEN}   ✓${NC} Composer update สำเร็จ"
        else
            echo -e "${RED}   ✗${NC} Composer ล้มเหลว กรุณาตรวจสอบ error:"
            echo "$COMPOSER_OUTPUT" | tail -20
            exit 1
        fi
    fi

    #---------------------------------------------------------------------------
    # Step 8: สร้าง APP_KEY
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ตรวจสอบและสร้าง APP_KEY..."

    local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
        php artisan key:generate --force 2>/dev/null && {
            echo -e "${GREEN}   ✓${NC} สร้าง APP_KEY ใหม่"
        } || {
            echo -e "${RED}   ✗${NC} ไม่สามารถสร้าง APP_KEY"
        }
    else
        echo -e "${GREEN}   ✓${NC} มี APP_KEY อยู่แล้ว"
    fi

    #---------------------------------------------------------------------------
    # Step 9: ตั้งค่าฐานข้อมูล (SQLite) หากจำเป็น
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ตรวจสอบฐานข้อมูล..."

    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
        if [ ! -f "database/database.sqlite" ]; then
            touch database/database.sqlite
            chmod 664 database/database.sqlite
            echo -e "${GREEN}   ✓${NC} สร้างไฟล์ database.sqlite ใหม่"
        else
            echo -e "${GREEN}   ✓${NC} มี database.sqlite อยู่แล้ว (ข้อมูลยังอยู่)"
        fi
    else
        echo -e "${GREEN}   ✓${NC} ใช้ ${DB_CONNECTION} database"
    fi

    # รัน migrations
    echo -e "${YELLOW}   ⚠${NC} กำลังรัน migrations..."
    set +e
    local MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
    local MIGRATION_EXIT=$?
    set -e

    if [ $MIGRATION_EXIT -eq 0 ]; then
        echo -e "${GREEN}   ✓${NC} Migrations สำเร็จ"
    else
        if echo "$MIGRATION_OUTPUT" | grep -q "Nothing to migrate"; then
            echo -e "${GREEN}   ✓${NC} ไม่มี migration ใหม่"
        else
            echo -e "${YELLOW}   ⚠${NC} Migration มีปัญหา (อาจมีอยู่แล้ว):"
            echo "$MIGRATION_OUTPUT" | grep -E "error|Error|SQLSTATE" | head -3 || true
        fi
    fi

    #---------------------------------------------------------------------------
    # Step 10: ติดตั้ง NPM และ build frontend
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} ติดตั้ง NPM dependencies และ build..."

    if command -v npm &> /dev/null; then
        set +e
        local NPM_OUTPUT=$(npm install 2>&1)
        local NPM_EXIT=$?
        set -e

        if [ $NPM_EXIT -eq 0 ]; then
            echo -e "${GREEN}   ✓${NC} NPM install สำเร็จ"

            # Build for production or dev
            if [ "${APP_ENV}" = "production" ]; then
                echo -e "${YELLOW}   ⚠${NC} กำลัง build production..."
                set +e
                NPM_OUTPUT=$(npm run build 2>&1)
                NPM_EXIT=$?
                set -e
            else
                echo -e "${YELLOW}   ⚠${NC} กำลัง build..."
                set +e
                NPM_OUTPUT=$(npm run build 2>&1)
                NPM_EXIT=$?
                set -e
            fi

            if [ $NPM_EXIT -eq 0 ]; then
                echo -e "${GREEN}   ✓${NC} Frontend build สำเร็จ"
            else
                echo -e "${YELLOW}   ⚠${NC} Frontend build ล้มเหลว (เว็บอาจยังใช้งานได้)"
                log_error_detail "NPM build output: $NPM_OUTPUT"
            fi
        else
            echo -e "${YELLOW}   ⚠${NC} NPM install ล้มเหลว"
            log_error_detail "NPM install output: $NPM_OUTPUT"
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} ไม่พบ npm - ข้ามการ build frontend"
    fi

    #---------------------------------------------------------------------------
    # Step 11: สร้าง storage link และตั้งค่า permissions
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} สร้าง storage link และตั้งค่า permissions..."

    # Remove old storage link
    rm -f public_html/storage 2>/dev/null || true

    # Create storage link
    php artisan storage:link 2>/dev/null || {
        # Manual symlink if artisan fails
        ln -sf "${APP_DIR}/storage/app/public" "${APP_DIR}/public_html/storage" 2>/dev/null || true
    }

    # Set permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod 644 .env 2>/dev/null || true

    # Set ownership if www-data exists
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    echo -e "${GREEN}   ✓${NC} Storage link และ permissions ตั้งค่าเรียบร้อย"

    #---------------------------------------------------------------------------
    # Step 12: Optimize Laravel
    #---------------------------------------------------------------------------
    STEP=$((STEP + 1))
    echo -e "${PURPLE}[$STEP/$TOTAL_STEPS]${NC} Optimize Laravel..."

    # Clear all caches first
    php artisan cache:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true

    # Restart queue (if using)
    php artisan queue:restart 2>/dev/null || true

    # Cache config and routes for production
    if [ "${APP_ENV}" = "production" ]; then
        php artisan config:cache 2>/dev/null || true
        php artisan route:cache 2>/dev/null || true
        php artisan view:cache 2>/dev/null || true
    fi

    # Bring the application back up
    php artisan up 2>/dev/null || true

    echo -e "${GREEN}   ✓${NC} Laravel optimized เรียบร้อย"

    #---------------------------------------------------------------------------
    # Summary
    #---------------------------------------------------------------------------
    echo -e "\n${CYAN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}   ${GREEN}✅ Force Reset เสร็จสมบูรณ์!${NC}                                             ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    # Quick health check
    echo -e "${PURPLE}ตรวจสอบสถานะ:${NC}"

    # Check vendor
    if [ -f "vendor/autoload.php" ]; then
        echo -e "${GREEN}   ✓${NC} Composer dependencies พร้อม"
    else
        echo -e "${RED}   ✗${NC} vendor/ ไม่พร้อม"
    fi

    # Check frontend build
    if [ -d "public_html/build" ] && [ -f "public_html/build/manifest.json" ]; then
        echo -e "${GREEN}   ✓${NC} Frontend build พร้อม"
    else
        echo -e "${YELLOW}   ⚠${NC} Frontend build ไม่สมบูรณ์"
    fi

    # Check database connection
    set +e
    local DB_CHECK=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'ok'; } catch(\Exception \$e) { echo 'fail'; }" 2>/dev/null | tail -1)
    set -e

    if [ "$DB_CHECK" = "ok" ]; then
        echo -e "${GREEN}   ✓${NC} Database connection พร้อม"
    else
        echo -e "${YELLOW}   ⚠${NC} Database connection มีปัญหา"
    fi

    # Check APP_KEY
    local FINAL_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ -n "${FINAL_KEY}" ] && [ "${FINAL_KEY}" != "base64:" ]; then
        echo -e "${GREEN}   ✓${NC} APP_KEY ตั้งค่าแล้ว"
    else
        echo -e "${RED}   ✗${NC} APP_KEY ไม่ถูกตั้งค่า"
    fi

    echo ""
    echo -e "${WHITE}เว็บไซต์พร้อมใช้งานแล้ว!${NC}"
    echo -e "ทดสอบด้วย: ${CYAN}php artisan serve${NC}"
    echo ""
}

#===============================================================================
# Repair - ซ่อมแซมระบบอัตโนมัติ
#===============================================================================

repair() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}   ${PURPLE}🔧 ThaiVote Auto-Repair System${NC}                                          ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    cd "${APP_DIR}"
    local REPAIRED=0
    local FAILED=0

    echo -e "${YELLOW}กำลังซ่อมแซมระบบ...${NC}\n"

    #---------------------------------------------------------------------------
    # Step 1: สร้างโฟลเดอร์ที่จำเป็น
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[1/8]${NC} สร้างโครงสร้างโฟลเดอร์..."

    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/uploads
    mkdir -p storage/app/private
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/testing
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    mkdir -p database
    mkdir -p public_html/build

    echo -e "${GREEN}   ✓${NC} สร้างโฟลเดอร์เรียบร้อย"
    REPAIRED=$((REPAIRED + 1))

    #---------------------------------------------------------------------------
    # Step 2: สร้าง .env ถ้าไม่มี
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[2/8]${NC} ตรวจสอบไฟล์ .env..."

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            echo -e "${GREEN}   ✓${NC} สร้าง .env จาก .env.example"
            REPAIRED=$((REPAIRED + 1))
        else
            echo -e "${RED}   ✗${NC} ไม่พบ .env.example"
            FAILED=$((FAILED + 1))
        fi
    else
        echo -e "${GREEN}   ✓${NC} มี .env อยู่แล้ว"
    fi

    #---------------------------------------------------------------------------
    # Step 3: ติดตั้ง Composer dependencies
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[3/8]${NC} ติดตั้ง Composer dependencies..."

    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        # Install composer if missing
        if ! command -v composer &> /dev/null && [ ! -f "composer.phar" ]; then
            echo -e "${YELLOW}   ⚠${NC} กำลังติดตั้ง Composer..."
            install_composer || {
                echo -e "${RED}   ✗${NC} ไม่สามารถติดตั้ง Composer"
                FAILED=$((FAILED + 1))
            }
        fi

        echo -e "${YELLOW}   ⚠${NC} กำลังรัน composer install..."
        set +e
        local COMPOSER_OUTPUT
        COMPOSER_OUTPUT=$(run_composer install --no-interaction --optimize-autoloader 2>&1)
        local COMPOSER_EXIT=$?
        set -e

        if [ $COMPOSER_EXIT -eq 0 ]; then
            echo -e "${GREEN}   ✓${NC} ติดตั้ง Composer dependencies เรียบร้อย"
            REPAIRED=$((REPAIRED + 1))
        else
            echo -e "${RED}   ✗${NC} Composer install ล้มเหลว"
            log_error_detail "Composer output: $COMPOSER_OUTPUT"

            # Try without lock file
            echo -e "${YELLOW}   ⚠${NC} ลองใหม่โดยไม่ใช้ lock file..."
            rm -f composer.lock
            COMPOSER_OUTPUT=$(run_composer update --no-interaction --optimize-autoloader 2>&1)
            COMPOSER_EXIT=$?

            if [ $COMPOSER_EXIT -eq 0 ]; then
                echo -e "${GREEN}   ✓${NC} Composer update สำเร็จ"
                REPAIRED=$((REPAIRED + 1))
            else
                echo -e "${RED}   ✗${NC} Composer update ล้มเหลว"
                FAILED=$((FAILED + 1))
            fi
        fi
    else
        echo -e "${GREEN}   ✓${NC} มี vendor/ อยู่แล้ว"
    fi

    #---------------------------------------------------------------------------
    # Step 4: สร้าง APP_KEY
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[4/8]${NC} ตรวจสอบ APP_KEY..."

    if [ -f ".env" ] && [ -f "vendor/autoload.php" ]; then
        local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
        if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ] || [ "${APP_KEY}" = "base64:" ]; then
            php artisan key:generate --force 2>/dev/null && {
                echo -e "${GREEN}   ✓${NC} สร้าง APP_KEY เรียบร้อย"
                REPAIRED=$((REPAIRED + 1))
            } || {
                echo -e "${RED}   ✗${NC} ไม่สามารถสร้าง APP_KEY"
                FAILED=$((FAILED + 1))
            }
        else
            echo -e "${GREEN}   ✓${NC} มี APP_KEY อยู่แล้ว"
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} ข้าม - ต้องติดตั้ง dependencies ก่อน"
    fi

    #---------------------------------------------------------------------------
    # Step 5: สร้าง/ซ่อมแซม Database
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[5/8]${NC} ตรวจสอบฐานข้อมูล..."

    if [ -f ".env" ]; then
        local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")

        if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
            if [ ! -f "database/database.sqlite" ]; then
                touch database/database.sqlite
                chmod 664 database/database.sqlite
                echo -e "${GREEN}   ✓${NC} สร้างไฟล์ database.sqlite"
                REPAIRED=$((REPAIRED + 1))
            else
                echo -e "${GREEN}   ✓${NC} มี database.sqlite อยู่แล้ว"
            fi

            # Check pdo_sqlite
            if ! php -m 2>/dev/null | grep -qi "pdo_sqlite"; then
                echo -e "${YELLOW}   ⚠${NC} ไม่พบ pdo_sqlite - กำลังเปลี่ยนเป็น file-based drivers"
                update_env_var "SESSION_DRIVER" "file"
                update_env_var "CACHE_STORE" "file"
                update_env_var "QUEUE_CONNECTION" "sync"
            fi
        fi
    fi

    #---------------------------------------------------------------------------
    # Step 6: Clear และสร้าง cache ใหม่
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[6/8]${NC} ล้าง cache..."

    if [ -f "vendor/autoload.php" ]; then
        # Clear bootstrap cache files
        rm -f bootstrap/cache/config.php 2>/dev/null || true
        rm -f bootstrap/cache/routes-v7.php 2>/dev/null || true
        rm -f bootstrap/cache/services.php 2>/dev/null || true
        rm -f bootstrap/cache/packages.php 2>/dev/null || true
        rm -f bootstrap/cache/events.php 2>/dev/null || true

        # Clear Laravel caches
        php artisan cache:clear 2>/dev/null || true
        php artisan config:clear 2>/dev/null || true
        php artisan route:clear 2>/dev/null || true
        php artisan view:clear 2>/dev/null || true

        echo -e "${GREEN}   ✓${NC} ล้าง cache เรียบร้อย"
        REPAIRED=$((REPAIRED + 1))
    else
        echo -e "${YELLOW}   ⚠${NC} ข้าม - ต้องติดตั้ง dependencies ก่อน"
    fi

    #---------------------------------------------------------------------------
    # Step 7: รัน Migrations
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[7/8]${NC} รัน database migrations..."

    if [ -f "vendor/autoload.php" ]; then
        set +e
        local MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
        local MIGRATION_EXIT=$?
        set -e

        if [ $MIGRATION_EXIT -eq 0 ]; then
            echo -e "${GREEN}   ✓${NC} Migrations เรียบร้อย"
            REPAIRED=$((REPAIRED + 1))
        else
            if echo "$MIGRATION_OUTPUT" | grep -q "Nothing to migrate"; then
                echo -e "${GREEN}   ✓${NC} ไม่มี pending migrations"
            elif echo "$MIGRATION_OUTPUT" | grep -q "already exists"; then
                echo -e "${YELLOW}   ⚠${NC} บางตารางมีอยู่แล้ว (ปกติ)"
            else
                echo -e "${RED}   ✗${NC} Migration ล้มเหลว"
                log_error_detail "Migration output: $MIGRATION_OUTPUT"
                FAILED=$((FAILED + 1))
            fi
        fi
    else
        echo -e "${YELLOW}   ⚠${NC} ข้าม - ต้องติดตั้ง dependencies ก่อน"
    fi

    #---------------------------------------------------------------------------
    # Step 8: แก้ไข Permissions
    #---------------------------------------------------------------------------
    echo -e "${PURPLE}[8/8]${NC} แก้ไข permissions..."

    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod 644 .env 2>/dev/null || true

    # Set ownership if www-data exists
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    # Create storage symlink if missing
    if [ ! -L "public_html/storage" ]; then
        ln -sf "${APP_DIR}/storage/app/public" "${APP_DIR}/public_html/storage" 2>/dev/null || true
    fi

    echo -e "${GREEN}   ✓${NC} แก้ไข permissions เรียบร้อย"
    REPAIRED=$((REPAIRED + 1))

    #---------------------------------------------------------------------------
    # Summary
    #---------------------------------------------------------------------------
    echo -e "\n${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${WHITE}📊 สรุปผลการซ่อมแซม${NC}"
    echo -e "${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ $FAILED -eq 0 ]; then
        echo -e "\n${GREEN}   ✅ ซ่อมแซมสำเร็จ ${REPAIRED} รายการ${NC}"
        echo -e "\n${CYAN}   💡 ขั้นตอนถัดไป:${NC}"
        echo -e "   1. รัน ${WHITE}./deploy.sh${NC} เพื่อ deploy แบบเต็ม"
        echo -e "   2. หรือรัน ${WHITE}./deploy.sh quick${NC} เพื่อ deploy แบบเร็ว"
        echo -e "   3. รัน ${WHITE}./deploy.sh diagnose${NC} เพื่อตรวจสอบอีกครั้ง\n"
    else
        echo -e "\n${RED}   ❌ ซ่อมแซมไม่สำเร็จ ${FAILED} รายการ${NC}"
        echo -e "${GREEN}   ✅ ซ่อมแซมสำเร็จ ${REPAIRED} รายการ${NC}"
        echo -e "\n${CYAN}   💡 ลองตรวจสอบ:${NC}"
        echo -e "   1. ตรวจสอบ PHP version และ extensions"
        echo -e "   2. ตรวจสอบ Composer installation"
        echo -e "   3. ดู error log ที่ ${ERROR_LOG}\n"
    fi

    return $FAILED
}

#===============================================================================
# Doctor - ตรวจสอบและซ่อมแซมอัตโนมัติ
#===============================================================================

doctor() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}   ${PURPLE}👨‍⚕️ ThaiVote Doctor - Diagnose & Auto-Fix${NC}                               ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    echo -e "${WHITE}กำลังวิเคราะห์ระบบ...${NC}\n"

    # Run diagnosis first
    set +e
    diagnose
    local ISSUES=$?
    set -e

    if [ $ISSUES -gt 0 ]; then
        echo ""
        if [ -t 0 ]; then
            read -p "พบปัญหา ${ISSUES} รายการ ต้องการซ่อมแซมอัตโนมัติหรือไม่? (y/N): " confirm
            if [[ "$confirm" =~ ^[Yy](es)?$ ]]; then
                repair
            else
                echo -e "\n${YELLOW}ยกเลิกการซ่อมแซม${NC}"
                echo -e "รัน ${WHITE}./deploy.sh repair${NC} เมื่อต้องการซ่อมแซม\n"
            fi
        else
            echo -e "\n${YELLOW}โหมด non-interactive - กำลังซ่อมแซมอัตโนมัติ...${NC}\n"
            repair
        fi
    else
        echo -e "\n${GREEN}ระบบปกติ ไม่จำเป็นต้องซ่อมแซม${NC}\n"
    fi
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
    echo "  repair        Auto-repair system issues (ซ่อมแซมอัตโนมัติ)"
    echo "  diagnose      Diagnose system issues (ตรวจสอบปัญหา)"
    echo "  doctor        Diagnose and auto-fix (ตรวจสอบและซ่อมแซม)"
    echo "  force-reset   Nuclear option - ลบและติดตั้งใหม่ทั้งหมด (ไม่ลบ DB)"
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
    echo "  $0 repair           # Auto-repair (ซ่อมแซมอัตโนมัติ)"
    echo "  $0 diagnose         # Check for issues (ตรวจสอบปัญหา)"
    echo "  $0 doctor           # Diagnose + repair"
    echo "  $0 force-reset      # Nuclear option - ลบและติดตั้งใหม่ทั้งหมด"
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
    repair|fix)
        repair
        ;;
    diagnose|check)
        diagnose
        ;;
    doctor)
        doctor
        ;;
    force-reset|forcereset|nuclear)
        force_reset
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
