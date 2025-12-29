#!/bin/bash
#===============================================================================
# ThaiVote - Rollback Script
# ย้อนกลับ deployment ก่อนหน้า
#===============================================================================

set -e

APP_DIR=$(cd "$(dirname "$0")" && pwd)
BACKUP_DIR="${APP_DIR}/storage/backups"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}           ThaiVote Rollback Tool                               ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

#===============================================================================
# Functions
#===============================================================================

list_backups() {
    echo -e "${CYAN}Available Backups:${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""

    if [ ! -d "${BACKUP_DIR}" ] || [ -z "$(ls -A ${BACKUP_DIR} 2>/dev/null)" ]; then
        echo -e "${YELLOW}No backups found.${NC}"
        return 1
    fi

    echo -e "${YELLOW}Database Backups:${NC}"
    ls -lh "${BACKUP_DIR}"/db_backup_*.sql.gz 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'  || echo "  None found"
    echo ""

    echo -e "${YELLOW}Critical File Backups:${NC}"
    ls -lh "${BACKUP_DIR}"/critical_*.tar.gz 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}' || echo "  None found"
    echo ""

    echo -e "${YELLOW}Rollback Scripts:${NC}"
    ls -lh "${BACKUP_DIR}"/rollback_*.sh 2>/dev/null | awk '{print "  " $9}' || echo "  None found"
    echo ""
}

rollback_database() {
    local BACKUP_FILE="$1"

    if [ ! -f "${BACKUP_FILE}" ]; then
        echo -e "${RED}Backup file not found: ${BACKUP_FILE}${NC}"
        return 1
    fi

    echo -e "${YELLOW}⚠ WARNING: This will replace your current database!${NC}"
    read -p "Are you sure you want to continue? [y/N]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        return 0
    fi

    # Read database credentials
    DB_HOST=$(grep "^DB_HOST=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d '=' -f2)

    echo "Restoring database from: ${BACKUP_FILE}"

    # Check if file is gzipped
    if [[ "${BACKUP_FILE}" == *.gz ]]; then
        gunzip -c "${BACKUP_FILE}" | mysql -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"
    else
        mysql -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" < "${BACKUP_FILE}"
    fi

    echo -e "${GREEN}✅ Database restored successfully!${NC}"
}

rollback_files() {
    local BACKUP_FILE="$1"

    if [ ! -f "${BACKUP_FILE}" ]; then
        echo -e "${RED}Backup file not found: ${BACKUP_FILE}${NC}"
        return 1
    fi

    echo -e "${YELLOW}⚠ WARNING: This will replace your current critical files!${NC}"
    read -p "Are you sure you want to continue? [y/N]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        return 0
    fi

    echo "Restoring files from: ${BACKUP_FILE}"
    tar -xzf "${BACKUP_FILE}" -C "${APP_DIR}"

    echo -e "${GREEN}✅ Files restored successfully!${NC}"
}

rollback_git() {
    local COMMITS="${1:-1}"

    echo -e "${YELLOW}⚠ WARNING: This will revert the last ${COMMITS} commit(s)!${NC}"
    echo ""
    echo "Commits to be reverted:"
    git log --oneline -n "${COMMITS}"
    echo ""

    read -p "Are you sure you want to continue? [y/N]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        return 0
    fi

    cd "${APP_DIR}"

    # Enable maintenance mode
    php artisan down --retry=60 || true

    # Revert commits
    git revert --no-commit HEAD~${COMMITS}..HEAD
    git commit -m "Rollback: Reverted last ${COMMITS} commit(s)"

    # Run migrations
    php artisan migrate --force

    # Clear caches
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache

    # Disable maintenance mode
    php artisan up

    echo -e "${GREEN}✅ Git rollback completed!${NC}"
}

#===============================================================================
# Main Menu
#===============================================================================

show_menu() {
    echo -e "${CYAN}Rollback Options:${NC}"
    echo ""
    echo "  1) List available backups"
    echo "  2) Rollback database from backup"
    echo "  3) Rollback critical files from backup"
    echo "  4) Rollback last git commit"
    echo "  5) Rollback multiple git commits"
    echo "  6) Run migration rollback"
    echo "  0) Exit"
    echo ""
}

#===============================================================================
# Main
#===============================================================================

cd "${APP_DIR}"

case "${1:-menu}" in
    list|--list|-l)
        list_backups
        ;;
    database|db)
        if [ -n "$2" ]; then
            rollback_database "$2"
        else
            list_backups
            echo ""
            read -p "Enter database backup file path: " DB_FILE
            rollback_database "${DB_FILE}"
        fi
        ;;
    files)
        if [ -n "$2" ]; then
            rollback_files "$2"
        else
            list_backups
            echo ""
            read -p "Enter critical backup file path: " CRIT_FILE
            rollback_files "${CRIT_FILE}"
        fi
        ;;
    git)
        COMMITS="${2:-1}"
        rollback_git "${COMMITS}"
        ;;
    migrate)
        STEPS="${2:-1}"
        echo "Rolling back ${STEPS} migration(s)..."
        php artisan migrate:rollback --step="${STEPS}"
        echo -e "${GREEN}✅ Migration rollback completed!${NC}"
        ;;
    menu|*)
        while true; do
            show_menu
            read -p "Select option: " CHOICE

            case "${CHOICE}" in
                1)
                    list_backups
                    ;;
                2)
                    list_backups
                    echo ""
                    read -p "Enter database backup file path: " DB_FILE
                    rollback_database "${DB_FILE}"
                    ;;
                3)
                    list_backups
                    echo ""
                    read -p "Enter critical backup file path: " CRIT_FILE
                    rollback_files "${CRIT_FILE}"
                    ;;
                4)
                    rollback_git 1
                    ;;
                5)
                    read -p "How many commits to rollback? " NUM_COMMITS
                    rollback_git "${NUM_COMMITS}"
                    ;;
                6)
                    read -p "How many migrations to rollback? [1]: " NUM_MIGRATIONS
                    NUM_MIGRATIONS=${NUM_MIGRATIONS:-1}
                    php artisan migrate:rollback --step="${NUM_MIGRATIONS}"
                    echo -e "${GREEN}✅ Migration rollback completed!${NC}"
                    ;;
                0|q|exit)
                    echo "Goodbye!"
                    exit 0
                    ;;
                *)
                    echo -e "${RED}Invalid option${NC}"
                    ;;
            esac
            echo ""
        done
        ;;
esac
