#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Installation Script v2.0
#
# ติดตั้งครั้งแรกสำหรับ ThaiVote (MySQL Default)
#===============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

APP_DIR=$(cd "$(dirname "$0")" && pwd)

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
echo -e "${BLUE}║${NC}                    ${GREEN}Installation Script v2.0${NC}                              ${BLUE}║${NC}"
echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 STEP $1:${NC} $2"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

#===============================================================================
# Check Requirements
#===============================================================================

log_step "1" "Checking System Requirements"

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    log "PHP $PHP_VERSION installed"

    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "curl")
    MISSING_EXTENSIONS=()

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^${ext}$"; then
            log "PHP extension: ${ext} ✓"
        else
            log_warning "PHP extension missing: ${ext}"
            MISSING_EXTENSIONS+=("${ext}")
        fi
    done

    if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
        echo ""
        log_warning "Missing extensions: ${MISSING_EXTENSIONS[*]}"
        echo "Install with: sudo apt install php-${MISSING_EXTENSIONS[*]// / php-}"
    fi
else
    log_error "PHP is not installed"
    echo "Please install PHP 8.2 or higher"
    exit 1
fi

# Check MySQL
if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    log "MySQL ${MYSQL_VERSION} installed"
else
    log_warning "MySQL client not found"
fi

# Check Composer
if command -v composer &> /dev/null; then
    log "Composer installed"
else
    log_error "Composer is not installed"
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    log "Node.js ${NODE_VERSION} installed"
else
    log_warning "Node.js is not installed (recommended for frontend)"
fi

# Check npm
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    log "npm ${NPM_VERSION} installed"
else
    log_warning "npm is not installed"
fi

#===============================================================================
# Installation Mode Selection
#===============================================================================

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${PURPLE}Installation Mode${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "  1) CLI Installation (Configure here in terminal)"
echo "  2) Web Wizard (Configure via browser)"
echo ""
read -p "Select installation mode [1/2] (default: 1): " INSTALL_MODE
INSTALL_MODE=${INSTALL_MODE:-1}

if [ "$INSTALL_MODE" = "2" ]; then
    #===============================================================================
    # Web Wizard Mode
    #===============================================================================
    log_step "WEB" "Starting Web Installation Wizard"

    cd "${APP_DIR}"

    # Create .env from example if needed
    if [ ! -f ".env" ]; then
        cp .env.example .env
        log "Created .env file"
    fi

    # Install dependencies
    echo "Installing dependencies..."
    composer install --no-interaction --quiet

    if command -v npm &> /dev/null; then
        npm install --silent
        npm run build 2>/dev/null || true
    fi

    # Generate key
    php artisan key:generate --force

    # Create installer flag
    touch "${APP_DIR}/storage/app/installing"

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${CYAN}Web Installation Wizard is ready!${NC}                                       ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Start the development server:                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${YELLOW}php artisan serve${NC}                                                        ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Then open your browser:                                                  ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${YELLOW}http://localhost:8000/install${NC}                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""

    exit 0
fi

#===============================================================================
# CLI Installation Mode
#===============================================================================

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
fi

#===============================================================================
# Database Configuration (MySQL Default)
#===============================================================================

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${PURPLE}MySQL Database Configuration${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

read -p "Database host (default: 127.0.0.1): " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "Database port (default: 3306): " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Database name (default: thaivote): " DB_NAME
DB_NAME=${DB_NAME:-thaivote}

read -p "Database username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Database password: " DB_PASS
echo ""

# Update .env file
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
sed -i "s/DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env

log "MySQL database configured"

# Test connection and create database if needed
echo ""
echo "Testing database connection..."
if mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASS}" -e "SELECT 1" &>/dev/null; then
    log "Database connection successful"

    # Create database if not exists
    mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASS}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    log "Database '${DB_NAME}' ready"
else
    log_warning "Could not connect to database. Please ensure MySQL is running and credentials are correct."
    read -p "Continue anyway? [y/N]: " CONTINUE
    if [[ ! "$CONTINUE" =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

#===============================================================================
# App Configuration
#===============================================================================

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${PURPLE}Application Configuration${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

read -p "App URL (default: http://localhost): " APP_URL
APP_URL=${APP_URL:-http://localhost}
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" .env

read -p "App Environment [local/production] (default: local): " APP_ENV
APP_ENV=${APP_ENV:-local}
sed -i "s/APP_ENV=.*/APP_ENV=${APP_ENV}/" .env

if [ "${APP_ENV}" = "production" ]; then
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
else
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=true/" .env
fi

log "Application configured"

#===============================================================================
# Install Dependencies
#===============================================================================

log_step "3" "Installing PHP Dependencies"

composer install --no-interaction
log "Composer dependencies installed"

log_step "4" "Installing Frontend Dependencies"

if command -v npm &> /dev/null; then
    npm install
    log "NPM dependencies installed"
else
    log_warning "Skipping NPM dependencies (npm not found)"
fi

#===============================================================================
# Generate Keys and Setup
#===============================================================================

log_step "5" "Application Setup"

# Generate application key
php artisan key:generate
log "Application key generated"

# Create storage link
php artisan storage:link 2>/dev/null || log_warning "Storage link already exists"
log "Storage link created"

# Set permissions
chmod -R 775 storage bootstrap/cache
log "Permissions set"

#===============================================================================
# Database Setup
#===============================================================================

log_step "6" "Database Setup"

# Run migrations
php artisan migrate --force
log "Database migrations completed"

# Ask about seeding
read -p "Run database seeders? [y/N]: " RUN_SEED
if [[ "$RUN_SEED" =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
    log "Database seeded"
fi

#===============================================================================
# Admin User Setup
#===============================================================================

log_step "7" "Admin User Setup"

read -p "Create admin user? [Y/n]: " CREATE_ADMIN
CREATE_ADMIN=${CREATE_ADMIN:-Y}

if [[ "$CREATE_ADMIN" =~ ^[Yy]$ ]]; then
    read -p "Admin name: " ADMIN_NAME
    read -p "Admin email: " ADMIN_EMAIL
    read -sp "Admin password: " ADMIN_PASSWORD
    echo ""

    # Create admin user via artisan command
    php artisan tinker --execute="
        \$user = new App\Models\User();
        \$user->name = '${ADMIN_NAME}';
        \$user->email = '${ADMIN_EMAIL}';
        \$user->password = bcrypt('${ADMIN_PASSWORD}');
        \$user->is_admin = true;
        \$user->email_verified_at = now();
        \$user->save();
        echo 'Admin user created successfully!';
    " 2>/dev/null || log_warning "Could not create admin user (User model may not have is_admin field)"
fi

#===============================================================================
# Build Frontend
#===============================================================================

log_step "8" "Building Frontend Assets"

if command -v npm &> /dev/null; then
    npm run build
    log "Frontend assets built"
else
    log_warning "Skipping frontend build (npm not found)"
fi

#===============================================================================
# Create Required Directories
#===============================================================================

log_step "9" "Creating Required Directories"

mkdir -p storage/app/public/images/parties
mkdir -p storage/app/public/uploads
mkdir -p storage/backups
mkdir -p storage/logs

log "Directories created"

#===============================================================================
# Mark Installation Complete
#===============================================================================

# Create installed marker file
echo "installed_at=$(date '+%Y-%m-%d %H:%M:%S')" > "${APP_DIR}/storage/app/installed"
echo "installed_by=cli" >> "${APP_DIR}/storage/app/installed"

#===============================================================================
# Final Summary
#===============================================================================

echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}   ${GREEN}✅ INSTALLATION COMPLETED SUCCESSFULLY!${NC}                                  ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${CYAN}Configuration Summary:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Database: MySQL - ${DB_NAME}@${DB_HOST}:${DB_PORT}"
echo "  App URL:  ${APP_URL}"
echo "  Environment: ${APP_ENV}"
echo ""

echo -e "${CYAN}Next Steps:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. Configure your web server (Nginx/Apache) to point to: ${APP_DIR}/public"
echo ""
echo "2. For development, run:"
echo "   ${CYAN}php artisan serve${NC}                    # Start Laravel server"
echo "   ${CYAN}npm run dev${NC}                          # Start Vite dev server"
echo "   ${CYAN}php artisan reverb:start${NC}             # Start WebSocket server"
echo ""
echo "3. For production, run:"
echo "   ${CYAN}./deploy.sh${NC}                          # Full deployment"
echo ""
echo "4. Access your application at: ${CYAN}${APP_URL}${NC}"
echo ""
