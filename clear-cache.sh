#!/bin/bash
#===============================================================================
# ThaiVote - Clear Cache Script
# ล้าง cache ทั้งหมดของ Laravel
#===============================================================================

set -e

APP_DIR=$(cd "$(dirname "$0")" && pwd)
cd "${APP_DIR}"

# Colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🧹 Clearing ThaiVote caches...${NC}"
echo ""

# Clear Laravel caches
echo "Clearing application cache..."
php artisan cache:clear

echo "Clearing config cache..."
php artisan config:clear

echo "Clearing route cache..."
php artisan route:clear

echo "Clearing view cache..."
php artisan view:clear

echo "Clearing event cache..."
php artisan event:clear

echo "Clearing compiled classes..."
php artisan clear-compiled 2>/dev/null || true

# Clear OPcache if available
if php -m | grep -q "Zend OPcache"; then
    echo "Clearing OPcache..."
    php -r "opcache_reset();" 2>/dev/null || true
fi

# Clear storage cache files
echo "Clearing storage cache files..."
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf storage/framework/views/*.php 2>/dev/null || true
rm -rf storage/framework/sessions/* 2>/dev/null || true

# Clear npm cache if requested
if [[ "$1" == "--all" ]]; then
    echo "Clearing npm cache..."
    npm cache clean --force 2>/dev/null || true

    echo "Clearing composer cache..."
    composer clear-cache 2>/dev/null || true
fi

echo ""
echo -e "${GREEN}✅ All caches cleared successfully!${NC}"
echo ""

# Show cache status
echo -e "${CYAN}Cache Status:${NC}"
if [ -f "bootstrap/cache/config.php" ]; then
    echo "  Config: Cached"
else
    echo "  Config: Not cached"
fi
if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo "  Routes: Cached"
else
    echo "  Routes: Not cached"
fi
echo ""
