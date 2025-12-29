#!/bin/bash
#===============================================================================
# ThaiVote - Quick Install Script
# ติดตั้งแบบรวดเร็ว (ใช้ค่า default)
#===============================================================================

set -e

APP_DIR=$(cd "$(dirname "$0")" && pwd)

# Colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}⚡ ThaiVote Quick Install${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cd "${APP_DIR}"

# Create .env if not exists
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo -e "${GREEN}✓${NC} Created .env file"
fi

# Install Composer dependencies
echo "Installing PHP dependencies..."
composer install --no-interaction --quiet
echo -e "${GREEN}✓${NC} Composer dependencies installed"

# Install NPM dependencies
if command -v npm &> /dev/null; then
    echo "Installing frontend dependencies..."
    npm install --silent
    echo -e "${GREEN}✓${NC} NPM dependencies installed"
fi

# Generate app key
php artisan key:generate --force
echo -e "${GREEN}✓${NC} Application key generated"

# Create SQLite database
mkdir -p database
touch database/database.sqlite
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's|DB_DATABASE=.*|DB_DATABASE=database/database.sqlite|' .env
echo -e "${GREEN}✓${NC} SQLite database configured"

# Run migrations
php artisan migrate --force
echo -e "${GREEN}✓${NC} Database migrated"

# Create storage link
php artisan storage:link 2>/dev/null || true
echo -e "${GREEN}✓${NC} Storage linked"

# Build frontend
if command -v npm &> /dev/null; then
    echo "Building frontend assets..."
    npm run build --silent 2>/dev/null || npm run build
    echo -e "${GREEN}✓${NC} Frontend built"
fi

# Set permissions
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓${NC} Permissions set"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  ✅ Quick install completed!                                   ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "Start the development server:"
echo "  ${CYAN}php artisan serve${NC}"
echo ""
echo "Then open: ${CYAN}http://localhost:8000${NC}"
echo ""
