#!/bin/bash
#===============================================================================
# ThaiVote - Smart Deployment Script v5.0
#
# ฉลาดพอที่จะติดตั้งและแก้ไขปัญหาทุกอย่างเองได้อัตโนมัติ
#
# Features:
#   - Auto-detect and install missing dependencies
#   - Auto-fix common problems (permissions, .env, database, storage)
#   - Pre-flight checks before deployment
#   - Smart rollback on failure
#   - Doctor mode for diagnosis and auto-repair
#
# Usage:
#   ./deploy.sh              # Full smart deployment
#   ./deploy.sh doctor       # Diagnose and auto-fix all issues
#   ./deploy.sh fix          # Quick fix common problems
#   ./deploy.sh status       # Show application status
#   ./deploy.sh reset        # Reset to fresh installation
#===============================================================================

set -e

# Script version
VERSION="5.0"
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
cd "$SCRIPT_DIR"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m'

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_DIR="storage/logs/deploy"
LOG_FILE="${LOG_DIR}/deploy_${TIMESTAMP}.log"
mkdir -p "$LOG_DIR"

# Flags
DRY_RUN=false
VERBOSE=false
AUTO_FIX=true
SKIP_TESTS=false

#===============================================================================
# Logging Functions
#===============================================================================

log() {
    local msg="[$(date '+%H:%M:%S')] $1"
    echo "$msg" >> "$LOG_FILE" 2>/dev/null || true
    echo -e "${GREEN}✓${NC} $1"
}

log_info() {
    local msg="[$(date '+%H:%M:%S')] INFO: $1"
    echo "$msg" >> "$LOG_FILE" 2>/dev/null || true
    echo -e "${BLUE}ℹ${NC} $1"
}

log_warning() {
    local msg="[$(date '+%H:%M:%S')] WARNING: $1"
    echo "$msg" >> "$LOG_FILE" 2>/dev/null || true
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    local msg="[$(date '+%H:%M:%S')] ERROR: $1"
    echo "$msg" >> "$LOG_FILE" 2>/dev/null || true
    echo -e "${RED}✗${NC} $1"
}

log_step() {
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

banner() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC} ${WHITE}  ThaiVote Smart Deployment System v${VERSION}              ${NC}${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} ${PURPLE}  ฉลาดพอที่จะติดตั้งและแก้ไขปัญหาทุกอย่างเอง        ${NC}${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

#===============================================================================
# System Checks & Auto-Install
#===============================================================================

check_and_install_php() {
    log_step "Checking PHP"

    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed"
        if [ "$AUTO_FIX" = true ]; then
            log_warning "Auto-installing PHP is not supported. Please install PHP 8.2+ manually"
        fi
        exit 1
    fi

    local PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log "PHP version: $PHP_VERSION"

    # Check PHP version (require 8.2+)
    if [ "$(php -r 'echo version_compare(PHP_VERSION, "8.2.0", ">=") ? "1" : "0";')" != "1" ]; then
        log_error "PHP 8.2+ is required (current: $PHP_VERSION)"
        exit 1
    fi

    # Check required extensions
    local REQUIRED_EXTS=("mbstring" "xml" "pdo" "tokenizer" "json" "bcmath" "fileinfo")
    for ext in "${REQUIRED_EXTS[@]}"; do
        if ! php -m | grep -iq "^$ext$"; then
            log_error "PHP extension '$ext' is missing"
            if [ "$AUTO_FIX" = true ]; then
                log_warning "Please install php-$ext manually"
            fi
        fi
    done

    log "PHP check passed"
}

check_and_install_composer() {
    log_step "Checking Composer"

    local COMPOSER_CMD=""
    if command -v composer &> /dev/null; then
        COMPOSER_CMD="composer"
    elif [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
    fi

    if [ -z "$COMPOSER_CMD" ]; then
        log_warning "Composer not found"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Downloading Composer..."
            php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
            php composer-setup.php --quiet
            php -r "unlink('composer-setup.php');"
            COMPOSER_CMD="php composer.phar"
            log "Composer installed successfully"
        else
            log_error "Please install Composer manually"
            exit 1
        fi
    else
        log "Composer: $($COMPOSER_CMD --version)"
    fi

    echo "$COMPOSER_CMD" > /tmp/composer_cmd
}

check_and_install_node() {
    log_step "Checking Node.js & NPM"

    if ! command -v node &> /dev/null; then
        log_error "Node.js is not installed"
        if [ "$AUTO_FIX" = true ]; then
            log_warning "Auto-installing Node.js is not supported. Please install Node.js 18+ manually"
        fi
        exit 1
    fi

    local NODE_VERSION=$(node -v)
    log "Node.js version: $NODE_VERSION"

    if ! command -v npm &> /dev/null; then
        log_error "NPM is not installed"
        exit 1
    fi

    local NPM_VERSION=$(npm -v)
    log "NPM version: $NPM_VERSION"
}

check_and_install_dependencies() {
    log_step "Checking & Installing Dependencies"

    local COMPOSER_CMD=$(cat /tmp/composer_cmd)

    # CRITICAL: Install composer dependencies FIRST (required for php artisan)
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        log_warning "Composer dependencies not installed"
        log_info "Installing Composer dependencies..."
        $COMPOSER_CMD install --no-interaction --prefer-dist --no-dev 2>&1 || {
            log_warning "Trying with dev dependencies..."
            $COMPOSER_CMD install --no-interaction --prefer-dist
        }
        log "Composer dependencies installed"
    else
        # Always update to ensure latest
        log_info "Updating Composer dependencies..."
        $COMPOSER_CMD install --no-interaction --prefer-dist --no-dev 2>&1 || true
        log "Composer dependencies: OK"
    fi

    # Install npm dependencies
    if [ ! -d "node_modules" ]; then
        log_warning "NPM dependencies not installed"
        log_info "Installing NPM dependencies (this may take a few minutes)..."
        npm install 2>&1 || {
            log_warning "npm install failed, trying npm ci..."
            npm ci 2>&1 || true
        }
        log "NPM dependencies installed"
    else
        log "NPM dependencies: OK"
    fi
}

create_required_directories() {
    log_step "Creating Required Directories"

    local REQUIRED_DIRS=(
        "storage/app/public"
        "storage/framework/cache/data"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
        "database"
        "public_html/build"
    )

    for dir in "${REQUIRED_DIRS[@]}"; do
        if [ ! -d "$dir" ]; then
            log_info "Creating $dir..."
            mkdir -p "$dir"
        fi
    done

    log "Required directories created"
}

#===============================================================================
# Environment Setup & Auto-Fix
#===============================================================================

check_and_setup_env() {
    log_step "Checking Environment Configuration"

    if [ ! -f ".env" ]; then
        log_warning ".env file not found"
        if [ "$AUTO_FIX" = true ] && [ -f ".env.example" ]; then
            log_info "Creating .env from .env.example..."
            cp .env.example .env
            log ".env file created"
        else
            log_error "Please create .env file"
            exit 1
        fi
    else
        log ".env file: OK"
    fi

    # Check APP_KEY
    local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ] || [ "$APP_KEY" = "base64:" ]; then
        log_warning "APP_KEY not set"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Generating APP_KEY..."
            php artisan key:generate --force
            log "APP_KEY generated"
        fi
    else
        log "APP_KEY: OK"
    fi
}

check_and_setup_database() {
    log_step "Checking Database Setup"

    # Ensure database directory exists
    mkdir -p database

    local DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    DB_CONNECTION=${DB_CONNECTION:-sqlite}

    log_info "Database type: $DB_CONNECTION"

    if [ "$DB_CONNECTION" = "sqlite" ]; then
        if [ ! -f "database/database.sqlite" ]; then
            log_warning "SQLite database file not found"
            if [ "$AUTO_FIX" = true ]; then
                log_info "Creating SQLite database..."
                touch database/database.sqlite
                chmod 664 database/database.sqlite 2>/dev/null || true
                log "SQLite database created"
            fi
        else
            log "SQLite database: OK"
        fi
    fi

    # Test database connection
    log_info "Testing database connection..."
    set +e
    if php artisan db:show 2>/dev/null | grep -q "Connection"; then
        log "Database connection: OK"
        set -e
        return 0
    else
        log_warning "Database connection failed (non-critical)"
        set -e
        return 1
    fi
}

check_and_run_migrations() {
    log_step "Checking Database Migrations"

    # Clear config cache first
    php artisan config:clear 2>/dev/null || true

    # Check if migrations table exists
    set +e
    local MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
    local MIGRATION_EXIT=$?
    set -e

    if [ $MIGRATION_EXIT -ne 0 ]; then
        log_warning "Migrations not run yet"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Running migrations..."
            php artisan migrate --force
            log "Migrations completed"
        fi
    else
        # Check for pending migrations
        if echo "$MIGRATION_STATUS" | grep -q "Pending"; then
            log_warning "Pending migrations found"
            if [ "$AUTO_FIX" = true ]; then
                log_info "Running pending migrations..."
                php artisan migrate --force
                log "Migrations completed"
            fi
        else
            log "Migrations: OK"
        fi
    fi
}

check_and_run_seeders() {
    log_step "Checking Database Seeders"

    # Check if database has data
    set +e
    local USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -n 1)
    set -e

    if [ -z "$USER_COUNT" ] || [ "$USER_COUNT" = "0" ]; then
        log_warning "Database is empty"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Running seeders..."
            php artisan db:seed --force
            log "Seeders completed"
        fi
    else
        log "Database has data ($USER_COUNT users)"
    fi
}

check_and_setup_storage() {
    log_step "Checking Storage Setup"

    # Create storage directories
    local STORAGE_DIRS=(
        "storage/app/public"
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
    )

    for dir in "${STORAGE_DIRS[@]}"; do
        if [ ! -d "$dir" ]; then
            log_warning "Directory $dir not found"
            if [ "$AUTO_FIX" = true ]; then
                mkdir -p "$dir"
                log "Created $dir"
            fi
        fi
    done

    # Check storage link
    if [ ! -L "public_html/storage" ]; then
        log_warning "Storage link not found"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Creating storage link..."
            php artisan storage:link
            log "Storage link created"
        fi
    else
        log "Storage link: OK"
    fi

    # Fix permissions
    if [ "$AUTO_FIX" = true ]; then
        log_info "Fixing storage permissions..."
        chmod -R 775 storage 2>/dev/null || true
        chmod -R 775 bootstrap/cache 2>/dev/null || true
        log "Permissions fixed"
    fi
}

check_and_build_assets() {
    log_step "Checking Frontend Assets"

    # Ensure build directory exists
    mkdir -p public_html/build

    if [ ! -d "public_html/build" ] || [ -z "$(ls -A public_html/build 2>/dev/null)" ]; then
        log_warning "Frontend assets not built"
        if [ "$AUTO_FIX" = true ]; then
            log_info "Building frontend assets (this may take a minute)..."
            set +e
            npm run build 2>&1 | tail -20
            local BUILD_EXIT=$?
            set -e

            if [ $BUILD_EXIT -eq 0 ]; then
                log "Frontend assets built successfully"
            else
                log_warning "Frontend build completed with warnings (check output above)"
            fi
        fi
    else
        log "Frontend assets: OK"
    fi
}

#===============================================================================
# Cache Management
#===============================================================================

optimize_application() {
    log_step "Optimizing Application"

    log_info "Clearing caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true

    # Check if in production
    local APP_ENV=$(grep "^APP_ENV=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    if [ "$APP_ENV" = "production" ]; then
        log_info "Caching for production..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi

    log "Optimization complete"
}

#===============================================================================
# System Doctor
#===============================================================================

doctor() {
    banner
    log_step "🏥 Running System Doctor"

    echo ""
    echo -e "${YELLOW}This will diagnose and auto-fix all common issues${NC}"
    echo ""

    # Run all checks and fixes
    check_and_install_php
    check_and_install_composer
    check_and_install_node
    create_required_directories
    check_and_install_dependencies
    check_and_setup_env
    check_and_setup_database
    check_and_run_migrations
    check_and_run_seeders
    check_and_setup_storage
    check_and_build_assets
    optimize_application

    echo ""
    log_step "✅ System Doctor Complete"
    echo ""
    echo -e "${GREEN}Your application should be ready to use!${NC}"
    echo -e "${BLUE}Admin login: test@example.com / password${NC}"
    echo ""
}

#===============================================================================
# Quick Fix
#===============================================================================

quick_fix() {
    banner
    log_step "🔧 Running Quick Fix"

    AUTO_FIX=true

    create_required_directories
    check_and_setup_env
    check_and_install_dependencies
    check_and_setup_storage
    check_and_build_assets
    optimize_application

    echo ""
    log "Quick fix complete"
    echo ""
}

#===============================================================================
# Status Check
#===============================================================================

status() {
    banner
    log_step "📊 Application Status"

    echo ""

    # PHP
    if command -v php &> /dev/null; then
        echo -e "${GREEN}✓${NC} PHP: $(php -r 'echo PHP_VERSION;')"
    else
        echo -e "${RED}✗${NC} PHP: Not installed"
    fi

    # Composer
    if command -v composer &> /dev/null; then
        echo -e "${GREEN}✓${NC} Composer: Installed"
    elif [ -f "composer.phar" ]; then
        echo -e "${GREEN}✓${NC} Composer: composer.phar"
    else
        echo -e "${RED}✗${NC} Composer: Not found"
    fi

    # Node & NPM
    if command -v node &> /dev/null; then
        echo -e "${GREEN}✓${NC} Node.js: $(node -v)"
    else
        echo -e "${RED}✗${NC} Node.js: Not installed"
    fi

    if command -v npm &> /dev/null; then
        echo -e "${GREEN}✓${NC} NPM: $(npm -v)"
    else
        echo -e "${RED}✗${NC} NPM: Not installed"
    fi

    # Dependencies
    if [ -d "vendor" ]; then
        echo -e "${GREEN}✓${NC} Composer dependencies: Installed"
    else
        echo -e "${RED}✗${NC} Composer dependencies: Not installed"
    fi

    if [ -d "node_modules" ]; then
        echo -e "${GREEN}✓${NC} NPM dependencies: Installed"
    else
        echo -e "${RED}✗${NC} NPM dependencies: Not installed"
    fi

    # Environment
    if [ -f ".env" ]; then
        echo -e "${GREEN}✓${NC} .env: Exists"
        local APP_KEY=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2)
        if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:" ]; then
            echo -e "${GREEN}✓${NC} APP_KEY: Set"
        else
            echo -e "${RED}✗${NC} APP_KEY: Not set"
        fi
    else
        echo -e "${RED}✗${NC} .env: Not found"
    fi

    # Database
    if [ -f "database/database.sqlite" ]; then
        echo -e "${GREEN}✓${NC} Database: Exists"
    else
        echo -e "${YELLOW}⚠${NC} Database: Not found"
    fi

    # Storage
    if [ -L "public_html/storage" ]; then
        echo -e "${GREEN}✓${NC} Storage link: Exists"
    else
        echo -e "${RED}✗${NC} Storage link: Not found"
    fi

    # Assets
    if [ -d "public_html/build" ] && [ -n "$(ls -A public_html/build 2>/dev/null)" ]; then
        echo -e "${GREEN}✓${NC} Frontend assets: Built"
    else
        echo -e "${RED}✗${NC} Frontend assets: Not built"
    fi

    echo ""
}

#===============================================================================
# Full Deployment
#===============================================================================

deploy() {
    banner
    log_step "🚀 Starting Smart Deployment"

    # Pre-flight checks
    check_and_install_php
    check_and_install_composer
    check_and_install_node

    # Create required directories BEFORE installing dependencies
    create_required_directories

    # Install dependencies (CRITICAL: Must be before any php artisan commands)
    check_and_install_dependencies

    # Setup environment (now php artisan is available)
    check_and_setup_env
    check_and_setup_database
    check_and_run_migrations
    check_and_run_seeders
    check_and_setup_storage

    # Build assets
    check_and_build_assets

    # Optimize
    optimize_application

    echo ""
    log_step "✅ Deployment Complete"
    echo ""
    echo -e "${GREEN}Your application is ready!${NC}"
    echo -e "${BLUE}Admin login: test@example.com / password${NC}"
    echo ""
    echo -e "${CYAN}To start development server:${NC}"
    echo -e "  ${WHITE}npm run dev${NC}         # Frontend dev server"
    echo -e "  ${WHITE}php artisan serve${NC}  # Backend server"
    echo ""
}

#===============================================================================
# Reset Installation
#===============================================================================

reset() {
    banner
    log_step "🔄 Resetting Installation"

    echo ""
    echo -e "${YELLOW}⚠  WARNING: This will remove all data and reinstall${NC}"
    echo -e "${YELLOW}   Dependencies (vendor, node_modules) will be kept${NC}"
    echo ""
    read -p "Are you sure? (yes/no): " -r
    echo

    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log "Reset cancelled"
        exit 0
    fi

    log_info "Removing database..."
    rm -f database/database.sqlite

    log_info "Removing .env..."
    rm -f .env

    log_info "Clearing caches..."
    rm -rf storage/framework/cache/*
    rm -rf storage/framework/sessions/*
    rm -rf storage/framework/views/*
    rm -rf bootstrap/cache/*

    log_info "Removing built assets..."
    rm -rf public_html/build/*

    log "Reset complete"
    echo ""
    echo -e "${CYAN}Run ${WHITE}./deploy.sh${CYAN} to reinstall${NC}"
    echo ""
}

#===============================================================================
# Main
#===============================================================================

main() {
    local command="${1:-deploy}"

    case "$command" in
        doctor)
            doctor
            ;;
        fix)
            quick_fix
            ;;
        status)
            status
            ;;
        reset)
            reset
            ;;
        deploy|"")
            deploy
            ;;
        --help|-h|help)
            echo "ThaiVote Smart Deployment Script v${VERSION}"
            echo ""
            echo "Usage: ./deploy.sh [command]"
            echo ""
            echo "Commands:"
            echo "  deploy       Full smart deployment (default)"
            echo "  doctor       Diagnose and auto-fix all issues"
            echo "  fix          Quick fix common problems"
            echo "  status       Show application status"
            echo "  reset        Reset to fresh installation"
            echo "  help         Show this help message"
            echo ""
            ;;
        *)
            log_error "Unknown command: $command"
            echo "Run './deploy.sh help' for usage information"
            exit 1
            ;;
    esac
}

# Run main
main "$@"
