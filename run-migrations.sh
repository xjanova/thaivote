#!/bin/bash
#===============================================================================
# ThaiVote - Run Migrations Script
# รัน database migrations อย่างปลอดภัย
#===============================================================================

set -e

APP_DIR=$(cd "$(dirname "$0")" && pwd)
BACKUP_DIR="${APP_DIR}/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

cd "${APP_DIR}"

echo -e "${CYAN}🗃️  ThaiVote Migration Runner${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

#===============================================================================
# Functions
#===============================================================================

backup_database() {
    echo -e "${YELLOW}Creating database backup before migration...${NC}"

    mkdir -p "${BACKUP_DIR}"

    DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)

    if [ "${DB_CONNECTION}" = "mysql" ] && command -v mysqldump &> /dev/null; then
        BACKUP_FILE="${BACKUP_DIR}/pre_migration_${DATE}.sql"
        mysqldump -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_FILE}" 2>/dev/null
        if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
            gzip "${BACKUP_FILE}"
            echo -e "${GREEN}✓ Backup created: ${BACKUP_FILE}.gz${NC}"
        fi
    elif [ "${DB_CONNECTION}" = "sqlite" ]; then
        SQLITE_PATH=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
        if [ -f "${SQLITE_PATH}" ]; then
            cp "${SQLITE_PATH}" "${BACKUP_DIR}/pre_migration_${DATE}.sqlite"
            echo -e "${GREEN}✓ SQLite backup created${NC}"
        fi
    fi
}

show_status() {
    echo -e "${CYAN}Current Migration Status:${NC}"
    php artisan migrate:status
    echo ""
}

show_pending() {
    echo -e "${CYAN}Pending Migrations:${NC}"
    php artisan migrate:status | grep -E "^\| No " || echo "  No pending migrations"
    echo ""
}

#===============================================================================
# Main
#===============================================================================

case "${1:-run}" in
    status|-s|--status)
        show_status
        ;;
    pending|-p|--pending)
        show_pending
        ;;
    fresh)
        echo -e "${RED}⚠ WARNING: This will DROP all tables and recreate them!${NC}"
        read -p "Are you absolutely sure? Type 'YES' to confirm: " CONFIRM
        if [ "${CONFIRM}" = "YES" ]; then
            backup_database
            php artisan migrate:fresh --force
            echo -e "${GREEN}✅ Fresh migration completed!${NC}"

            read -p "Run seeders? [y/N]: " RUN_SEED
            if [[ "$RUN_SEED" =~ ^[Yy]$ ]]; then
                php artisan db:seed --force
                echo -e "${GREEN}✅ Seeders completed!${NC}"
            fi
        else
            echo "Cancelled."
        fi
        ;;
    rollback)
        STEPS="${2:-1}"
        echo -e "${YELLOW}Rolling back ${STEPS} migration(s)...${NC}"
        backup_database
        php artisan migrate:rollback --step="${STEPS}" --force
        echo -e "${GREEN}✅ Rollback completed!${NC}"
        ;;
    refresh)
        echo -e "${YELLOW}⚠ WARNING: This will rollback and re-run all migrations!${NC}"
        read -p "Continue? [y/N]: " CONFIRM
        if [[ "$CONFIRM" =~ ^[Yy]$ ]]; then
            backup_database
            php artisan migrate:refresh --force
            echo -e "${GREEN}✅ Refresh completed!${NC}"
        else
            echo "Cancelled."
        fi
        ;;
    run|*)
        show_pending

        # Check if there are pending migrations
        PENDING=$(php artisan migrate:status 2>/dev/null | grep -c "No" || echo "0")

        if [ "$PENDING" = "0" ]; then
            echo -e "${GREEN}No pending migrations.${NC}"
            exit 0
        fi

        # Backup before migration
        backup_database

        echo -e "${CYAN}Running migrations...${NC}"
        php artisan migrate --force

        echo ""
        echo -e "${GREEN}✅ Migrations completed successfully!${NC}"

        # Show updated status
        echo ""
        show_status
        ;;
esac
