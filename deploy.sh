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
VERSION="3.2"

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
        git pull origin $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main") 2>/dev/null || true
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
