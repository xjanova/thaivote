#!/bin/bash
#===============================================================================
# ThaiVote - Quick Install Script v2.0
#
# Fast installation with SQLite (no configuration needed)
# For full installation options, use: ./install.sh
#===============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

APP_DIR=$(cd "$(dirname "$0")" && pwd)

# Helper functions
log() { echo -e "${GREEN}[✓]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[!]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }
log_info() { echo -e "${CYAN}[i]${NC} $1"; }

check_command() { command -v "$1" &> /dev/null; }

#===============================================================================
# Banner
#===============================================================================

echo ""
echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}                                                               ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}   ${GREEN}ThaiVote - Quick Install${NC}                                   ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}   Fast setup with SQLite database                            ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}                                                               ${CYAN}║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

#===============================================================================
# Check Requirements
#===============================================================================

echo -e "${CYAN}Checking requirements...${NC}"

# Check PHP
if ! check_command php; then
    log_error "PHP is not installed"
    echo "Install with: sudo apt install php8.2 php8.2-cli"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
log "PHP ${PHP_VERSION}"

# Check Composer
if ! check_command composer; then
    log_warn "Composer not found, installing..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer 2>/dev/null || {
        mkdir -p ~/bin
        mv composer.phar ~/bin/composer
        export PATH="$HOME/bin:$PATH"
    }
fi
log "Composer ready"

# Check Node.js (optional)
HAS_NPM=false
if check_command npm; then
    HAS_NPM=true
    log "npm $(npm --version)"
else
    log_warn "npm not found (frontend will not be built)"
fi

echo ""

#===============================================================================
# Installation
#===============================================================================

cd "${APP_DIR}"

echo -e "${CYAN}Installing ThaiVote...${NC}"
echo ""

# Step 1: Create .env
if [ ! -f ".env" ]; then
    cp .env.example .env
    log "Created .env file"
else
    log ".env already exists"
fi

# Step 2: Configure SQLite
mkdir -p database
touch database/database.sqlite
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/^DB_HOST=.*/#DB_HOST=127.0.0.1/' .env 2>/dev/null || true
sed -i 's/^DB_PORT=.*/#DB_PORT=3306/' .env 2>/dev/null || true
sed -i 's/^DB_DATABASE=.*/#DB_DATABASE=thaivote/' .env 2>/dev/null || true
sed -i 's/^DB_USERNAME=.*/#DB_USERNAME=root/' .env 2>/dev/null || true
sed -i 's/^DB_PASSWORD=.*/#DB_PASSWORD=/' .env 2>/dev/null || true
log "SQLite database configured"

# Step 3: Install Composer dependencies
echo -ne "${CYAN}[>]${NC} Installing PHP dependencies... "
if composer install --no-interaction --quiet 2>/dev/null; then
    echo -e "${GREEN}done${NC}"
else
    echo -e "${YELLOW}retrying...${NC}"
    composer install --no-interaction
fi

# Step 4: Generate app key
php artisan key:generate --force --quiet
log "Application key generated"

# Step 5: Run migrations
echo -ne "${CYAN}[>]${NC} Running database migrations... "
if php artisan migrate --force --quiet 2>/dev/null; then
    echo -e "${GREEN}done${NC}"
else
    echo ""
    php artisan migrate --force
fi

# Step 6: Create storage link
php artisan storage:link --quiet 2>/dev/null || true
log "Storage linked"

# Step 7: Create directories
mkdir -p storage/app/public/images/parties
mkdir -p storage/app/public/uploads
mkdir -p storage/backups
mkdir -p storage/logs
mkdir -p bootstrap/cache
log "Directories created"

# Step 8: Set permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
log "Permissions set"

# Step 9: Install NPM & build frontend
if [ "$HAS_NPM" = true ]; then
    echo -ne "${CYAN}[>]${NC} Installing frontend dependencies... "
    if npm install --silent 2>/dev/null; then
        echo -e "${GREEN}done${NC}"
    else
        npm install
    fi

    echo -ne "${CYAN}[>]${NC} Building frontend assets... "
    if npm run build --silent 2>/dev/null; then
        echo -e "${GREEN}done${NC}"
    else
        npm run build 2>/dev/null || log_warn "Frontend build failed (you can build later)"
    fi
else
    log_info "Skipping frontend (npm not available)"
fi

# Step 10: Create installed marker
mkdir -p storage/app
cat > storage/app/installed << EOF
installed_at=$(date '+%Y-%m-%d %H:%M:%S')
installed_by=quick-install
version=2.0
database=sqlite
EOF

#===============================================================================
# Complete
#===============================================================================

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}                                                               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}   ${GREEN}Installation completed successfully!${NC}                       ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                               ${GREEN}║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${CYAN}Quick Start:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "  ${YELLOW}# Start the development server${NC}"
echo -e "  php artisan serve"
echo ""
echo -e "  ${YELLOW}# Or start all services${NC}"
echo -e "  composer dev"
echo ""
echo -e "  ${YELLOW}# Create an admin user${NC}"
echo -e "  php artisan user:create-admin"
echo ""
echo -e "Then open: ${CYAN}http://localhost:8000${NC}"
echo ""
echo -e "${CYAN}For more options:${NC} ./install.sh --help"
echo ""
