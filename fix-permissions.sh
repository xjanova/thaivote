#!/bin/bash
#===============================================================================
# ThaiVote - Fix Permissions Script
# แก้ไข permissions สำหรับ Laravel
#===============================================================================

set -e

APP_DIR=$(cd "$(dirname "$0")" && pwd)
cd "${APP_DIR}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🔧 Fixing ThaiVote permissions...${NC}"
echo ""

# Determine web server user
WEB_USER="www-data"
if id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "httpd" &>/dev/null; then
    WEB_USER="httpd"
fi

echo "Web server user: ${WEB_USER}"
echo ""

# Set directory permissions
echo "Setting directory permissions (755)..."
find "${APP_DIR}" -type d -exec chmod 755 {} \; 2>/dev/null || true

# Set file permissions
echo "Setting file permissions (644)..."
find "${APP_DIR}" -type f -exec chmod 644 {} \; 2>/dev/null || true

# Set special permissions for storage and cache
echo "Setting storage/cache permissions (775)..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Make scripts executable
echo "Making shell scripts executable..."
chmod +x *.sh 2>/dev/null || true

# Set ownership if running as root
if [ "$(id -u)" = "0" ]; then
    echo "Setting ownership to ${WEB_USER}..."
    chown -R ${WEB_USER}:${WEB_USER} storage bootstrap/cache 2>/dev/null || true
else
    echo -e "${YELLOW}[!] Not running as root, skipping ownership change${NC}"
fi

# Verify permissions
echo ""
echo -e "${CYAN}Verifying permissions:${NC}"
echo "  storage/: $(stat -c '%a' storage 2>/dev/null || stat -f '%OLp' storage)"
echo "  bootstrap/cache/: $(stat -c '%a' bootstrap/cache 2>/dev/null || stat -f '%OLp' bootstrap/cache)"
echo "  .env: $(stat -c '%a' .env 2>/dev/null || stat -f '%OLp' .env)"

echo ""
echo -e "${GREEN}✅ Permissions fixed successfully!${NC}"
echo ""
