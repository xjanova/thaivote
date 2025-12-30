#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Installation Script v3.0
#
# Usage:
#   ./install.sh              # Interactive installation
#   ./install.sh --auto       # Non-interactive with defaults (SQLite)
#   ./install.sh --mysql      # Non-interactive with MySQL
#   ./install.sh --help       # Show help
#===============================================================================

set -e

# Script version
VERSION="3.0"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m'

# Get script directory
APP_DIR=$(cd "$(dirname "$0")" && pwd)

# Default values
AUTO_MODE=false
USE_MYSQL=false
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="thaivote"
DB_USER="root"
DB_PASS=""
APP_URL="http://localhost"
APP_ENV="local"
SKIP_FRONTEND=false
SKIP_ADMIN=false
VERBOSE=false

#===============================================================================
# Helper Functions
#===============================================================================

show_banner() {
    clear
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}████████╗██╗  ██╗ █████╗ ██╗██╗   ██╗ ██████╗ ████████╗███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}╚══██╔══╝██║  ██║██╔══██╗██║██║   ██║██╔═══██╗╚══██╔══╝██╔════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ███████║███████║██║██║   ██║██║   ██║   ██║   █████╗${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██╔══██║██╔══██║██║╚██╗ ██╔╝██║   ██║   ██║   ██╔══╝${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██║  ██║██║  ██║██║ ╚████╔╝ ╚██████╔╝   ██║   ███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═════╝    ╚═╝   ╚══════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${CYAN}Election Results Tracker${NC}                              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${GREEN}Installation Script v${VERSION}${NC}                              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

show_help() {
    echo -e "${CYAN}ThaiVote Installation Script v${VERSION}${NC}"
    echo ""
    echo "Usage: ./install.sh [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --auto              Non-interactive mode with SQLite (fastest)"
    echo "  --mysql             Non-interactive mode with MySQL"
    echo "  --db-host=HOST      MySQL host (default: 127.0.0.1)"
    echo "  --db-port=PORT      MySQL port (default: 3306)"
    echo "  --db-name=NAME      Database name (default: thaivote)"
    echo "  --db-user=USER      Database username (default: root)"
    echo "  --db-pass=PASS      Database password"
    echo "  --app-url=URL       Application URL (default: http://localhost)"
    echo "  --env=ENV           Environment: local/production (default: local)"
    echo "  --skip-frontend     Skip frontend build"
    echo "  --skip-admin        Skip admin user creation"
    echo "  --verbose           Show detailed output"
    echo "  --help, -h          Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./install.sh                                    # Interactive mode"
    echo "  ./install.sh --auto                             # Quick install with SQLite"
    echo "  ./install.sh --mysql --db-pass=secret           # MySQL with password"
    echo "  ./install.sh --auto --app-url=https://vote.th   # Custom URL"
    echo ""
}

log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 STEP $1:${NC} ${WHITE}$2${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

log_debug() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${CYAN}[DEBUG]${NC} $1"
    fi
}

spinner() {
    local pid=$1
    local delay=0.1
    local spinstr='⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏'
    while [ "$(ps a | awk '{print $1}' | grep $pid)" ]; do
        local temp=${spinstr#?}
        printf " ${CYAN}%c${NC}  " "$spinstr"
        local spinstr=$temp${spinstr%"$temp"}
        sleep $delay
        printf "\b\b\b\b"
    done
    printf "    \b\b\b\b"
}

run_cmd() {
    local cmd="$1"
    local msg="$2"

    if [ "$VERBOSE" = true ]; then
        echo -e "${BLUE}[>]${NC} $msg"
        eval "$cmd"
    else
        echo -ne "${BLUE}[>]${NC} $msg "
        if eval "$cmd" > /tmp/install_output.log 2>&1; then
            echo -e "${GREEN}✓${NC}"
            return 0
        else
            echo -e "${RED}✗${NC}"
            echo -e "${RED}Error output:${NC}"
            cat /tmp/install_output.log
            return 1
        fi
    fi
}

check_command() {
    command -v "$1" &> /dev/null
}

confirm() {
    local prompt="$1"
    local default="${2:-Y}"

    if [ "$AUTO_MODE" = true ]; then
        return 0
    fi

    if [ "$default" = "Y" ]; then
        read -p "$prompt [Y/n]: " response
        response=${response:-Y}
    else
        read -p "$prompt [y/N]: " response
        response=${response:-N}
    fi

    [[ "$response" =~ ^[Yy]$ ]]
}

prompt_input() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    local is_password="${4:-false}"

    if [ "$AUTO_MODE" = true ]; then
        eval "$var_name=\"$default\""
        return
    fi

    if [ "$is_password" = true ]; then
        read -sp "$prompt (default: hidden): " value
        echo ""
    else
        read -p "$prompt (default: $default): " value
    fi

    eval "$var_name=\"${value:-$default}\""
}

#===============================================================================
# Parse Arguments
#===============================================================================

parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --auto)
                AUTO_MODE=true
                shift
                ;;
            --mysql)
                AUTO_MODE=true
                USE_MYSQL=true
                shift
                ;;
            --db-host=*)
                DB_HOST="${1#*=}"
                shift
                ;;
            --db-port=*)
                DB_PORT="${1#*=}"
                shift
                ;;
            --db-name=*)
                DB_NAME="${1#*=}"
                shift
                ;;
            --db-user=*)
                DB_USER="${1#*=}"
                shift
                ;;
            --db-pass=*)
                DB_PASS="${1#*=}"
                shift
                ;;
            --app-url=*)
                APP_URL="${1#*=}"
                shift
                ;;
            --env=*)
                APP_ENV="${1#*=}"
                shift
                ;;
            --skip-frontend)
                SKIP_FRONTEND=true
                shift
                ;;
            --skip-admin)
                SKIP_ADMIN=true
                shift
                ;;
            --verbose|-v)
                VERBOSE=true
                shift
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                echo "Use --help to see available options"
                exit 1
                ;;
        esac
    done
}

#===============================================================================
# Check Requirements
#===============================================================================

check_requirements() {
    log_step "1" "Checking System Requirements"

    local has_errors=false

    # Check PHP
    if check_command php; then
        PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
        PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")

        if [ "$PHP_MAJOR" -ge 8 ] && [ "$PHP_MINOR" -ge 2 ]; then
            log "PHP ${PHP_VERSION} ✓"
        else
            log_error "PHP ${PHP_VERSION} (requires 8.2+)"
            has_errors=true
        fi

        # Check PHP extensions
        local REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "curl" "fileinfo")
        local MISSING_EXTENSIONS=()

        for ext in "${REQUIRED_EXTENSIONS[@]}"; do
            if php -m 2>/dev/null | grep -qi "^${ext}$"; then
                log_debug "PHP extension: ${ext} ✓"
            else
                MISSING_EXTENSIONS+=("$ext")
            fi
        done

        # Check database extensions
        if php -m 2>/dev/null | grep -qi "^pdo_mysql$"; then
            log "PHP PDO MySQL extension ✓"
        else
            log_warning "PHP PDO MySQL extension not found (needed for MySQL)"
        fi

        if php -m 2>/dev/null | grep -qi "^pdo_sqlite$"; then
            log "PHP PDO SQLite extension ✓"
        else
            log_warning "PHP PDO SQLite extension not found (needed for SQLite)"
        fi

        if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
            log_warning "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
            echo -e "  ${CYAN}Install with:${NC} sudo apt install ${MISSING_EXTENSIONS[*]/#/php-}"
        fi
    else
        log_error "PHP is not installed"
        echo -e "  ${CYAN}Install with:${NC} sudo apt install php8.2 php8.2-cli php8.2-common"
        has_errors=true
    fi

    # Check Composer
    if check_command composer; then
        COMPOSER_VERSION=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
        log "Composer ${COMPOSER_VERSION} ✓"
    else
        log_warning "Composer is not installed"
        if confirm "Install Composer now?"; then
            echo "Installing Composer..."
            curl -sS https://getcomposer.org/installer | php
            sudo mv composer.phar /usr/local/bin/composer
            log "Composer installed"
        else
            has_errors=true
        fi
    fi

    # Check Node.js
    if check_command node; then
        NODE_VERSION=$(node --version)
        log "Node.js ${NODE_VERSION} ✓"
    else
        log_warning "Node.js is not installed (required for frontend)"
        if [ "$SKIP_FRONTEND" = false ]; then
            echo -e "  ${CYAN}Install with:${NC} curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install nodejs"
        fi
    fi

    # Check npm
    if check_command npm; then
        NPM_VERSION=$(npm --version)
        log "npm ${NPM_VERSION} ✓"
    else
        if [ "$SKIP_FRONTEND" = false ]; then
            log_warning "npm is not installed"
        fi
    fi

    # Check MySQL (optional)
    if check_command mysql; then
        MYSQL_VERSION=$(mysql --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1 || echo "unknown")
        log "MySQL client ${MYSQL_VERSION} ✓"
    else
        log_info "MySQL client not found (optional for SQLite mode)"
    fi

    if [ "$has_errors" = true ]; then
        echo ""
        log_error "Some requirements are missing. Please install them and try again."
        exit 1
    fi
}

#===============================================================================
# Choose Installation Mode
#===============================================================================

choose_installation_mode() {
    if [ "$AUTO_MODE" = true ]; then
        return
    fi

    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}Choose Installation Mode${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "  1) ${GREEN}Quick Install (SQLite)${NC} - Fastest, no database setup needed"
    echo "  2) ${BLUE}Full Install (MySQL)${NC}   - Production-ready with MySQL"
    echo "  3) ${PURPLE}Web Wizard${NC}             - Configure via browser interface"
    echo ""
    read -p "Select mode [1/2/3] (default: 1): " INSTALL_MODE
    INSTALL_MODE=${INSTALL_MODE:-1}

    case $INSTALL_MODE in
        1)
            USE_MYSQL=false
            ;;
        2)
            USE_MYSQL=true
            ;;
        3)
            start_web_wizard
            exit 0
            ;;
        *)
            log_error "Invalid option"
            exit 1
            ;;
    esac
}

#===============================================================================
# Web Wizard Mode
#===============================================================================

start_web_wizard() {
    log_step "WEB" "Starting Web Installation Wizard"

    cd "${APP_DIR}"

    # Create .env from example if needed
    if [ ! -f ".env" ]; then
        cp .env.example .env
        log "Created .env file"
    fi

    # Install dependencies
    run_cmd "composer install --no-interaction --quiet" "Installing PHP dependencies..."

    if check_command npm; then
        run_cmd "npm install --silent 2>/dev/null" "Installing frontend dependencies..."
        run_cmd "npm run build 2>/dev/null || true" "Building frontend..."
    fi

    # Generate key
    php artisan key:generate --force --quiet
    log "Application key generated"

    # Create installer flag
    mkdir -p "${APP_DIR}/storage/app"
    touch "${APP_DIR}/storage/app/installing"

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${CYAN}Web Installation Wizard is ready!${NC}                                       ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Start the server with:                                                   ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${YELLOW}php artisan serve${NC}                                                        ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Then open your browser:                                                  ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${YELLOW}http://localhost:8000/install${NC}                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

#===============================================================================
# Setup Environment
#===============================================================================

setup_environment() {
    log_step "2" "Setting Up Environment"

    cd "${APP_DIR}"

    # Create .env from example if it doesn't exist
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            log "Created .env from .env.example"
        else
            log_error ".env.example not found"
            exit 1
        fi
    else
        log ".env already exists"
        if confirm "Overwrite existing .env file?" "N"; then
            cp .env.example .env
            log "Overwritten .env file"
        fi
    fi
}

#===============================================================================
# Configure Database
#===============================================================================

configure_database() {
    log_step "3" "Database Configuration"

    if [ "$USE_MYSQL" = true ]; then
        configure_mysql
    else
        configure_sqlite
    fi
}

configure_sqlite() {
    echo -e "${GREEN}Using SQLite database${NC} (simple, no setup required)"
    echo ""

    # Create database directory and file
    mkdir -p "${APP_DIR}/database"
    touch "${APP_DIR}/database/database.sqlite"

    # Update .env
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
    # Comment out MySQL settings
    sed -i 's/^DB_HOST=.*/#DB_HOST=127.0.0.1/' .env
    sed -i 's/^DB_PORT=.*/#DB_PORT=3306/' .env
    sed -i 's/^DB_DATABASE=.*/#DB_DATABASE=thaivote/' .env
    sed -i 's/^DB_USERNAME=.*/#DB_USERNAME=root/' .env
    sed -i 's/^DB_PASSWORD=.*/#DB_PASSWORD=/' .env

    log "SQLite database configured"
}

configure_mysql() {
    echo -e "${BLUE}Configuring MySQL database${NC}"
    echo ""

    prompt_input "Database host" "$DB_HOST" "DB_HOST"
    prompt_input "Database port" "$DB_PORT" "DB_PORT"
    prompt_input "Database name" "$DB_NAME" "DB_NAME"
    prompt_input "Database username" "$DB_USER" "DB_USER"
    prompt_input "Database password" "$DB_PASS" "DB_PASS" true

    # Update .env file - handle special characters in password
    # Use temp file approach for safety
    local TEMP_ENV=$(mktemp)

    while IFS= read -r line || [[ -n "$line" ]]; do
        case "$line" in
            DB_CONNECTION=*|"#DB_CONNECTION="*|"# DB_CONNECTION="*)
                echo "DB_CONNECTION=mysql" >> "$TEMP_ENV"
                ;;
            DB_HOST=*|"#DB_HOST="*|"# DB_HOST="*)
                echo "DB_HOST=${DB_HOST}" >> "$TEMP_ENV"
                ;;
            DB_PORT=*|"#DB_PORT="*|"# DB_PORT="*)
                echo "DB_PORT=${DB_PORT}" >> "$TEMP_ENV"
                ;;
            DB_DATABASE=*|"#DB_DATABASE="*|"# DB_DATABASE="*)
                echo "DB_DATABASE=${DB_NAME}" >> "$TEMP_ENV"
                ;;
            DB_USERNAME=*|"#DB_USERNAME="*|"# DB_USERNAME="*)
                echo "DB_USERNAME=${DB_USER}" >> "$TEMP_ENV"
                ;;
            DB_PASSWORD=*|"#DB_PASSWORD="*|"# DB_PASSWORD="*)
                # Quote password to handle special characters
                echo "DB_PASSWORD=\"${DB_PASS}\"" >> "$TEMP_ENV"
                ;;
            *)
                echo "$line" >> "$TEMP_ENV"
                ;;
        esac
    done < .env

    mv "$TEMP_ENV" .env

    log "MySQL configuration saved"
}

#===============================================================================
# Test MySQL Connection (without requiring database to exist)
#===============================================================================

test_mysql_connection() {
    local host="$1"
    local port="$2"
    local user="$3"
    local pass="$4"

    # Build mysql command with proper password handling
    local MYSQL_CMD="mysql -h \"${host}\" -P \"${port}\" -u \"${user}\""
    if [ -n "$pass" ]; then
        MYSQL_CMD="${MYSQL_CMD} -p\"${pass}\""
    fi

    # Test connection by running simple query
    if eval "${MYSQL_CMD} -e 'SELECT 1' 2>/dev/null >/dev/null"; then
        return 0
    else
        return 1
    fi
}

#===============================================================================
# Create MySQL Database if not exists
#===============================================================================

create_mysql_database() {
    local host="$1"
    local port="$2"
    local user="$3"
    local pass="$4"
    local dbname="$5"

    # Build mysql command
    local MYSQL_CMD="mysql -h \"${host}\" -P \"${port}\" -u \"${user}\""
    if [ -n "$pass" ]; then
        MYSQL_CMD="${MYSQL_CMD} -p\"${pass}\""
    fi

    # Create database with proper charset
    local CREATE_SQL="CREATE DATABASE IF NOT EXISTS \\\`${dbname}\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    if eval "${MYSQL_CMD} -e \"${CREATE_SQL}\" 2>/tmp/mysql_create.log"; then
        return 0
    else
        return 1
    fi
}

#===============================================================================
# Configure Application
#===============================================================================

configure_application() {
    log_step "4" "Application Configuration"

    prompt_input "App URL" "$APP_URL" "APP_URL"
    prompt_input "Environment (local/production)" "$APP_ENV" "APP_ENV"

    # Update .env
    sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    sed -i "s/^APP_ENV=.*/APP_ENV=${APP_ENV}/" .env
    sed -i "s/^APP_NAME=.*/APP_NAME=ThaiVote/" .env

    if [ "${APP_ENV}" = "production" ]; then
        sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
    else
        sed -i "s/^APP_DEBUG=.*/APP_DEBUG=true/" .env
    fi

    log "Application configured"
}

#===============================================================================
# Install Dependencies
#===============================================================================

install_dependencies() {
    log_step "5" "Installing Dependencies"

    cd "${APP_DIR}"

    # Composer install
    if [ "$APP_ENV" = "production" ]; then
        run_cmd "composer install --no-interaction --no-dev --optimize-autoloader" "Installing PHP dependencies (production)..."
    else
        run_cmd "composer install --no-interaction" "Installing PHP dependencies..."
    fi

    # NPM install
    if [ "$SKIP_FRONTEND" = false ] && check_command npm; then
        run_cmd "npm install" "Installing frontend dependencies..."
    else
        log_info "Skipping frontend dependencies"
    fi
}

#===============================================================================
# Setup Application
#===============================================================================

setup_application() {
    log_step "6" "Application Setup"

    cd "${APP_DIR}"

    # Generate application key
    php artisan key:generate --force --quiet
    log "Application key generated"

    # Ensure directories exist first
    mkdir -p storage/app/public/images/parties
    mkdir -p storage/app/public/uploads
    mkdir -p storage/backups
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    mkdir -p public_html

    # Create storage link (using public_html instead of public)
    if [ ! -L "public_html/storage" ]; then
        if ! php artisan storage:link --quiet 2>/dev/null; then
            # Manual fallback
            ln -sf "${APP_DIR}/storage/app/public" "${APP_DIR}/public_html/storage" 2>/dev/null || true
        fi
        log "Storage link created"
    else
        log "Storage link already exists"
    fi

    log "Required directories created"

    # Set permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    log "Permissions set"
}

#===============================================================================
# Setup Database
#===============================================================================

setup_database() {
    log_step "7" "Database Setup"

    cd "${APP_DIR}"

    # Clear config cache first
    php artisan config:clear 2>/dev/null || true

    # For SQLite, just run migrations
    if [ "$USE_MYSQL" = false ]; then
        echo "Setting up SQLite database..."
        if php artisan migrate --force 2>/tmp/migrate_error.log; then
            log "Database migrations completed"
        else
            log_error "Migration failed"
            cat /tmp/migrate_error.log
        fi
        return
    fi

    # MySQL Setup
    echo -e "${BLUE}Setting up MySQL database...${NC}"
    echo ""

    local MAX_RETRIES=3
    local RETRY_COUNT=0

    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        # Step 1: Check if MySQL client is available
        if ! check_command mysql; then
            log_warning "MySQL client not installed. Trying PHP connection test..."
            # Fall back to PHP-based connection test
            if php -r "
                \$pdo = new PDO(
                    'mysql:host=${DB_HOST};port=${DB_PORT}',
                    '${DB_USER}',
                    '${DB_PASS}'
                );
                echo 'OK';
            " 2>/tmp/php_mysql_error.log | grep -q "OK"; then
                log "MySQL connection successful (via PHP)"
            else
                RETRY_COUNT=$((RETRY_COUNT + 1))
                log_error "MySQL connection failed (attempt ${RETRY_COUNT}/${MAX_RETRIES})"

                if grep -q "Access denied" /tmp/php_mysql_error.log 2>/dev/null; then
                    echo -e "${YELLOW}  → Username หรือ password ไม่ถูกต้อง${NC}"
                elif grep -q "Connection refused" /tmp/php_mysql_error.log 2>/dev/null; then
                    echo -e "${YELLOW}  → ไม่สามารถเชื่อมต่อ MySQL server ได้${NC}"
                    echo -e "${CYAN}  → ตรวจสอบว่า MySQL service กำลังทำงาน: sudo systemctl status mysql${NC}"
                else
                    echo -e "${YELLOW}  → Error: $(cat /tmp/php_mysql_error.log 2>/dev/null | head -1)${NC}"
                fi

                if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
                    if confirm "ต้องการแก้ไขการตั้งค่า database ไหม?" "Y"; then
                        configure_mysql
                        php artisan config:clear 2>/dev/null || true
                        continue
                    fi
                fi

                if confirm "ข้ามการติดตั้ง database? (ต้องตั้งค่าเองทีหลัง)" "N"; then
                    log_warning "Skipping database setup"
                    return
                else
                    exit 1
                fi
            fi
        else
            # Step 1: Test MySQL connection (without database)
            echo -ne "  ${BLUE}[1/4]${NC} Testing MySQL connection... "
            if test_mysql_connection "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS"; then
                echo -e "${GREEN}✓${NC}"
            else
                echo -e "${RED}✗${NC}"
                RETRY_COUNT=$((RETRY_COUNT + 1))
                log_error "MySQL connection failed (attempt ${RETRY_COUNT}/${MAX_RETRIES})"

                echo ""
                echo -e "${YELLOW}ไม่สามารถเชื่อมต่อ MySQL ได้ กรุณาตรวจสอบ:${NC}"
                echo -e "  1. MySQL service กำลังทำงาน: ${CYAN}sudo systemctl status mysql${NC}"
                echo -e "  2. Host: ${CYAN}${DB_HOST}:${DB_PORT}${NC}"
                echo -e "  3. Username: ${CYAN}${DB_USER}${NC}"
                echo -e "  4. Password ถูกต้อง"
                echo ""

                if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
                    if confirm "ต้องการแก้ไขการตั้งค่า database ไหม?" "Y"; then
                        configure_mysql
                        php artisan config:clear 2>/dev/null || true
                        continue
                    fi
                fi

                if confirm "ข้ามการติดตั้ง database? (ต้องตั้งค่าเองทีหลัง)" "N"; then
                    log_warning "Skipping database setup"
                    return
                else
                    exit 1
                fi
            fi
        fi

        # Step 2: Create database if not exists
        echo -ne "  ${BLUE}[2/4]${NC} Creating database '${DB_NAME}'... "
        if check_command mysql; then
            if create_mysql_database "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS" "$DB_NAME"; then
                echo -e "${GREEN}✓${NC}"
            else
                echo -e "${YELLOW}(may already exist)${NC}"
            fi
        else
            # Use PHP to create database
            if php -r "
                \$pdo = new PDO(
                    'mysql:host=${DB_HOST};port=${DB_PORT}',
                    '${DB_USER}',
                    '${DB_PASS}'
                );
                \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                echo 'OK';
            " 2>/dev/null | grep -q "OK"; then
                echo -e "${GREEN}✓${NC}"
            else
                echo -e "${YELLOW}(may already exist)${NC}"
            fi
        fi

        # Step 3: Test connection to database
        echo -ne "  ${BLUE}[3/4]${NC} Verifying database connection... "
        php artisan config:clear 2>/dev/null || true

        if php artisan db:show 2>/dev/null >/dev/null || php -r "
            new PDO(
                'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}',
                '${DB_USER}',
                '${DB_PASS}'
            );
            echo 'OK';
        " 2>/dev/null | grep -q "OK"; then
            echo -e "${GREEN}✓${NC}"
        else
            echo -e "${RED}✗${NC}"
            log_error "Cannot connect to database '${DB_NAME}'"
            RETRY_COUNT=$((RETRY_COUNT + 1))

            if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
                if confirm "ต้องการลองใหม่ไหม?" "Y"; then
                    continue
                fi
            fi

            if confirm "ข้ามการติดตั้ง database?" "N"; then
                log_warning "Skipping database setup"
                return
            else
                exit 1
            fi
        fi

        # Step 4: Run migrations
        echo -ne "  ${BLUE}[4/4]${NC} Running migrations... "
        if php artisan migrate --force 2>/tmp/migrate_error.log; then
            echo -e "${GREEN}✓${NC}"
            log "Database setup completed successfully"
        else
            echo -e "${RED}✗${NC}"
            log_error "Migration failed"
            echo ""
            cat /tmp/migrate_error.log 2>/dev/null | head -10
            echo ""

            if ! confirm "Continue anyway?" "N"; then
                exit 1
            fi
        fi

        break
    done

    log_info "To seed demo data, run: ./deploy.sh --seed"
}

#===============================================================================
# Create Admin User
#===============================================================================

create_admin_user() {
    log_step "8" "Admin User Setup"

    if [ "$SKIP_ADMIN" = true ]; then
        log_info "Skipping admin user creation"
        return
    fi

    if [ "$AUTO_MODE" = true ]; then
        log_info "Skipping admin user creation in auto mode"
        echo -e "  ${CYAN}Create admin later with:${NC} php artisan user:create-admin"
        return
    fi

    if ! confirm "Create admin user?" "Y"; then
        return
    fi

    read -p "Admin name: " ADMIN_NAME
    if [ -z "$ADMIN_NAME" ]; then
        log_warning "Skipping admin user (no name provided)"
        return
    fi

    read -p "Admin email: " ADMIN_EMAIL
    if [ -z "$ADMIN_EMAIL" ]; then
        log_warning "Skipping admin user (no email provided)"
        return
    fi

    while true; do
        read -sp "Admin password (min 8 characters): " ADMIN_PASSWORD
        echo ""
        if [ ${#ADMIN_PASSWORD} -lt 8 ]; then
            log_warning "Password must be at least 8 characters"
            continue
        fi
        read -sp "Confirm password: " ADMIN_PASSWORD_CONFIRM
        echo ""
        if [ "$ADMIN_PASSWORD" != "$ADMIN_PASSWORD_CONFIRM" ]; then
            log_warning "Passwords do not match"
            continue
        fi
        break
    done

    # Create admin using artisan command
    if php artisan user:create-admin --name="$ADMIN_NAME" --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD" 2>/dev/null; then
        log "Admin user created successfully"
    else
        # Fallback: try using tinker with proper escaping
        ESCAPED_NAME=$(printf '%s' "$ADMIN_NAME" | sed "s/'/\\\\'/g")
        ESCAPED_EMAIL=$(printf '%s' "$ADMIN_EMAIL" | sed "s/'/\\\\'/g")
        ESCAPED_PASS=$(printf '%s' "$ADMIN_PASSWORD" | sed "s/'/\\\\'/g")

        php artisan tinker --execute="
            try {
                \$user = new App\Models\User();
                \$user->name = '${ESCAPED_NAME}';
                \$user->email = '${ESCAPED_EMAIL}';
                \$user->password = bcrypt('${ESCAPED_PASS}');
                if (property_exists(\$user, 'is_admin') || in_array('is_admin', \$user->getFillable())) {
                    \$user->is_admin = true;
                }
                \$user->email_verified_at = now();
                \$user->save();
                echo 'Admin user created!';
            } catch (\Exception \$e) {
                echo 'Error: ' . \$e->getMessage();
            }
        " 2>/dev/null && log "Admin user created" || log_warning "Could not create admin user"
    fi
}

#===============================================================================
# Build Frontend
#===============================================================================

build_frontend() {
    log_step "9" "Building Frontend Assets"

    if [ "$SKIP_FRONTEND" = true ]; then
        log_info "Skipping frontend build"
        return
    fi

    if ! check_command npm; then
        log_warning "npm not found, skipping frontend build"
        return
    fi

    cd "${APP_DIR}"

    if run_cmd "npm run build" "Building frontend assets..."; then
        log "Frontend assets built"
    else
        log_warning "Frontend build failed (you can build later with: npm run build)"
    fi
}

#===============================================================================
# Configure Reverb (WebSocket)
#===============================================================================

configure_reverb() {
    log_step "10" "WebSocket Configuration (Laravel Reverb)"

    # Check if Reverb is installed
    if ! grep -q "laravel/reverb" composer.json 2>/dev/null; then
        log_info "Laravel Reverb not found in dependencies"
        return
    fi

    # Generate Reverb keys if not set
    if ! grep -q "^REVERB_APP_ID=" .env || grep -q "^REVERB_APP_ID=$" .env; then
        # Generate random values
        REVERB_APP_ID=$((RANDOM * RANDOM))
        REVERB_APP_KEY=$(openssl rand -hex 16 2>/dev/null || cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
        REVERB_APP_SECRET=$(openssl rand -hex 32 2>/dev/null || cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 64 | head -n 1)

        # Check if Reverb settings exist in .env
        if grep -q "^REVERB_APP_ID" .env; then
            sed -i "s/^REVERB_APP_ID=.*/REVERB_APP_ID=${REVERB_APP_ID}/" .env
            sed -i "s/^REVERB_APP_KEY=.*/REVERB_APP_KEY=${REVERB_APP_KEY}/" .env
            sed -i "s/^REVERB_APP_SECRET=.*/REVERB_APP_SECRET=${REVERB_APP_SECRET}/" .env
        else
            # Add Reverb settings
            cat >> .env << EOF

# Laravel Reverb (WebSocket)
REVERB_APP_ID=${REVERB_APP_ID}
REVERB_APP_KEY=${REVERB_APP_KEY}
REVERB_APP_SECRET=${REVERB_APP_SECRET}
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="\${REVERB_HOST}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"
EOF
        fi
        log "Reverb WebSocket configured"
    else
        log "Reverb already configured"
    fi
}

#===============================================================================
# Finalize Installation
#===============================================================================

finalize_installation() {
    cd "${APP_DIR}"

    # Clear and cache config for production
    if [ "$APP_ENV" = "production" ]; then
        php artisan config:cache --quiet 2>/dev/null || true
        php artisan route:cache --quiet 2>/dev/null || true
        php artisan view:cache --quiet 2>/dev/null || true
        log "Configuration cached for production"
    else
        php artisan config:clear --quiet 2>/dev/null || true
    fi

    # Create installed marker file
    mkdir -p "${APP_DIR}/storage/app"
    cat > "${APP_DIR}/storage/app/installed" << EOF
installed_at=$(date '+%Y-%m-%d %H:%M:%S')
installed_by=cli
version=${VERSION}
php_version=$(php -r "echo PHP_VERSION;")
database=${USE_MYSQL:+mysql}${USE_MYSQL:-sqlite}
EOF

    # Remove installer flag if exists
    rm -f "${APP_DIR}/storage/app/installing"
}

#===============================================================================
# Show Summary
#===============================================================================

show_summary() {
    echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${GREEN}✅ INSTALLATION COMPLETED SUCCESSFULLY!${NC}                                  ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}"

    echo -e "\n${CYAN}Configuration Summary:${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    if [ "$USE_MYSQL" = true ]; then
        echo -e "  Database:    ${BLUE}MySQL${NC} - ${DB_NAME}@${DB_HOST}:${DB_PORT}"
    else
        echo -e "  Database:    ${GREEN}SQLite${NC} - database/database.sqlite"
    fi
    echo -e "  App URL:     ${APP_URL}"
    echo -e "  Environment: ${APP_ENV}"
    echo ""

    echo -e "${CYAN}Quick Start:${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo -e "  ${YELLOW}# Start development server${NC}"
    echo -e "  php artisan serve"
    echo ""
    echo -e "  ${YELLOW}# Or start all services (server + queue + vite)${NC}"
    echo -e "  composer dev"
    echo ""

    echo -e "${CYAN}Available Commands:${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "  ${CYAN}php artisan serve${NC}         Start Laravel server"
    echo -e "  ${CYAN}npm run dev${NC}               Start Vite dev server"
    echo -e "  ${CYAN}php artisan reverb:start${NC}  Start WebSocket server"
    echo -e "  ${CYAN}php artisan queue:work${NC}    Start queue worker"
    echo ""

    echo -e "  ${CYAN}./deploy.sh${NC}               Full production deployment"
    echo -e "  ${CYAN}./deploy.sh quick${NC}         Quick deployment"
    echo ""

    echo -e "Access your application at: ${CYAN}${APP_URL}${NC}"
    echo ""
}

#===============================================================================
# Main
#===============================================================================

main() {
    # Parse command line arguments
    parse_args "$@"

    # Show banner
    show_banner

    # Run installation steps
    check_requirements
    choose_installation_mode
    setup_environment
    configure_database
    configure_application
    install_dependencies
    setup_application
    setup_database
    create_admin_user
    build_frontend
    configure_reverb
    finalize_installation
    show_summary
}

# Run main function
main "$@"
