#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Installation Script v1.0
#
# ติดตั้งครั้งแรกสำหรับ ThaiVote
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
echo -e "${BLUE}║${NC}                    ${GREEN}Installation Script v1.0${NC}                              ${BLUE}║${NC}"
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
    REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^${ext}$"; then
            log "PHP extension: ${ext} ✓"
        else
            log_warning "PHP extension missing: ${ext}"
        fi
    done
else
    log_error "PHP is not installed"
    echo "Please install PHP 8.2 or higher"
    exit 1
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
# Environment Setup
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

# Ask for database configuration
echo ""
echo -e "${CYAN}Database Configuration${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

read -p "Database type [mysql/sqlite] (default: mysql): " DB_TYPE
DB_TYPE=${DB_TYPE:-mysql}

if [ "$DB_TYPE" = "sqlite" ]; then
    # Configure SQLite
    sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
    sed -i 's/DB_DATABASE=.*/DB_DATABASE=database\/database.sqlite/' .env

    # Create SQLite database file
    touch database/database.sqlite
    log "SQLite database created"
else
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
fi

# Configure app settings
read -p "App URL (default: http://localhost): " APP_URL
APP_URL=${APP_URL:-http://localhost}
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" .env

log "Environment configured"

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
# Build Frontend
#===============================================================================

log_step "7" "Building Frontend Assets"

if command -v npm &> /dev/null; then
    npm run build
    log "Frontend assets built"
else
    log_warning "Skipping frontend build (npm not found)"
fi

#===============================================================================
# Create Required Directories
#===============================================================================

log_step "8" "Creating Required Directories"

mkdir -p storage/app/public/images/parties
mkdir -p storage/app/public/uploads
mkdir -p storage/backups
mkdir -p storage/logs

log "Directories created"

#===============================================================================
# Supervisor Configuration (Optional)
#===============================================================================

log_step "9" "Queue & WebSocket Configuration"

if command -v supervisorctl &> /dev/null; then
    echo -e "${YELLOW}Supervisor detected. Creating configuration...${NC}"

    # Create supervisor config directory
    mkdir -p deployment/supervisor

    # Create queue worker config
    cat > deployment/supervisor/thaivote-worker.conf << EOF
[program:thaivote-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

    # Create Reverb WebSocket config
    cat > deployment/supervisor/thaivote-reverb.conf << EOF
[program:thaivote-reverb]
process_name=%(program_name)s
command=php ${APP_DIR}/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/reverb.log
stopwaitsecs=3600
EOF

    log "Supervisor configurations created in deployment/supervisor/"
    echo -e "${YELLOW}To activate, copy configs to /etc/supervisor/conf.d/ and run: supervisorctl reread && supervisorctl update${NC}"
else
    log_warning "Supervisor not found. Queue workers and WebSocket need manual setup."
fi

#===============================================================================
# Final Summary
#===============================================================================

echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}   ${GREEN}✅ INSTALLATION COMPLETED SUCCESSFULLY!${NC}                                  ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${CYAN}Next Steps:${NC}"
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
echo -e "${PURPLE}Documentation:${NC} Check README.md for detailed instructions"
echo ""
