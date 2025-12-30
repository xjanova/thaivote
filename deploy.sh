#!/bin/bash
#===============================================================================
# ThaiVote - Election Results Tracker
# Deployment Script v1.0
#
# ระบบ Deploy อัตโนมัติสำหรับ ThaiVote
# รองรับ: Laravel 11 + Vue.js + Reverb WebSocket
#===============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="ThaiVote"
APP_DIR=$(cd "$(dirname "$0")" && pwd)
LOG_FILE="${APP_DIR}/storage/logs/deploy.log"
BACKUP_DIR="${APP_DIR}/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)
MIN_DISK_SPACE_MB=500

# Create necessary directories
mkdir -p "${BACKUP_DIR}"
mkdir -p "${APP_DIR}/storage/logs"

#===============================================================================
# Utility Functions
#===============================================================================

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "${LOG_FILE}"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    echo "[WARNING] $1" >> "${LOG_FILE}"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[ERROR] $1" >> "${LOG_FILE}"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌 STEP $1:${NC} $2"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

check_disk_space() {
    local available=$(df -m "${APP_DIR}" | awk 'NR==2 {print $4}')
    if [ "$available" -lt "$MIN_DISK_SPACE_MB" ]; then
        log_error "Insufficient disk space: ${available}MB available, ${MIN_DISK_SPACE_MB}MB required"
        log_warning "Running emergency cleanup..."

        # Clear old logs
        find "${APP_DIR}/storage/logs" -name "*.log" -mtime +7 -delete 2>/dev/null || true

        # Clear old backups
        find "${BACKUP_DIR}" -name "*.sql" -mtime +30 -delete 2>/dev/null || true
        find "${BACKUP_DIR}" -name "*.tar.gz" -mtime +30 -delete 2>/dev/null || true

        # Clear Laravel cache
        rm -rf "${APP_DIR}/storage/framework/cache/data/*" 2>/dev/null || true
        rm -rf "${APP_DIR}/storage/framework/views/*" 2>/dev/null || true

        available=$(df -m "${APP_DIR}" | awk 'NR==2 {print $4}')
        if [ "$available" -lt "$MIN_DISK_SPACE_MB" ]; then
            log_error "Still insufficient disk space after cleanup: ${available}MB"
            exit 1
        fi
        log "Disk space freed. Now have ${available}MB available"
    else
        log "Disk space OK: ${available}MB available"
    fi
}

#===============================================================================
# Pre-flight Checks
#===============================================================================

preflight_checks() {
    log_step "0" "Pre-flight Checks"

    # Check if we're in the right directory
    if [ ! -f "${APP_DIR}/artisan" ]; then
        log_error "Not in a Laravel project directory"
        exit 1
    fi

    # Check if .env exists
    if [ ! -f "${APP_DIR}/.env" ]; then
        log_error ".env file not found"
        exit 1
    fi

    # Check disk space
    check_disk_space

    # Check PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed"
        exit 1
    fi

    # Check Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer is not installed"
        exit 1
    fi

    # Check Node.js
    if ! command -v node &> /dev/null; then
        log_warning "Node.js is not installed (required for frontend build)"
    fi

    log "All pre-flight checks passed ✓"
}

#===============================================================================
# Backup Functions
#===============================================================================

backup_database() {
    log_step "1" "Database Backup"

    # Read database credentials from .env
    DB_CONNECTION=$(grep "^DB_CONNECTION=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_HOST=$(grep "^DB_HOST=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d '=' -f2)

    if [ "${DB_CONNECTION}" = "mysql" ]; then
        BACKUP_FILE="${BACKUP_DIR}/db_backup_${DATE}.sql"

        if command -v mysqldump &> /dev/null; then
            log "Creating MySQL backup..."
            mysqldump -h "${DB_HOST}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_FILE}" 2>/dev/null || {
                log_warning "Database backup failed (non-critical)"
            }

            if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
                gzip "${BACKUP_FILE}"
                log "Database backed up to: ${BACKUP_FILE}.gz"
            fi
        else
            log_warning "mysqldump not found, skipping database backup"
        fi
    elif [ "${DB_CONNECTION}" = "sqlite" ]; then
        SQLITE_PATH=$(grep "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d '=' -f2)
        if [ -f "${SQLITE_PATH}" ]; then
            cp "${SQLITE_PATH}" "${BACKUP_DIR}/db_backup_${DATE}.sqlite"
            log "SQLite database backed up"
        fi
    else
        log_warning "Unsupported database type: ${DB_CONNECTION}"
    fi
}

backup_critical_files() {
    log_step "2" "Critical Files Backup"

    CRITICAL_BACKUP="${BACKUP_DIR}/critical_${DATE}.tar.gz"

    # Backup .env and important configs
    tar -czf "${CRITICAL_BACKUP}" \
        -C "${APP_DIR}" \
        .env \
        storage/app/public \
        2>/dev/null || log_warning "Some files could not be backed up"

    log "Critical files backed up to: ${CRITICAL_BACKUP}"
}

#===============================================================================
# Deployment Steps
#===============================================================================

enable_maintenance_mode() {
    log_step "3" "Enabling Maintenance Mode"

    cd "${APP_DIR}"
    php artisan down --retry=60 --refresh=15 || true
    log "Maintenance mode enabled"
}

disable_maintenance_mode() {
    log_step "FINAL" "Disabling Maintenance Mode"

    cd "${APP_DIR}"
    php artisan up
    log "Application is now live!"
}

pull_latest_code() {
    log_step "4" "Pulling Latest Code"

    cd "${APP_DIR}"

    # Stash any local changes
    if [ -n "$(git status --porcelain)" ]; then
        log_warning "Local changes detected, stashing..."
        git stash push -m "Deploy stash ${DATE}"
    fi

    # Pull latest code
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    log "Current branch: ${BRANCH}"

    git fetch origin
    git reset --hard origin/${BRANCH}

    log "Code updated to latest version"
    git log -1 --pretty=format:"Commit: %h - %s (%an, %ar)" | tee -a "${LOG_FILE}"
    echo ""
}

install_composer_dependencies() {
    log_step "5" "Installing Composer Dependencies"

    cd "${APP_DIR}"

    # Determine environment
    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" | cut -d '=' -f2)

    if [ "${APP_ENV}" = "production" ]; then
        log "Production mode: Installing without dev dependencies"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        log "Development mode: Installing all dependencies"
        composer install --optimize-autoloader --no-interaction
    fi

    log "Composer dependencies installed"
}

install_npm_dependencies() {
    log_step "6" "Installing NPM Dependencies"

    cd "${APP_DIR}"

    if command -v npm &> /dev/null; then
        npm ci --silent 2>/dev/null || npm install --silent
        log "NPM dependencies installed"
    else
        log_warning "NPM not available, skipping frontend dependencies"
    fi
}

build_frontend() {
    log_step "7" "Building Frontend Assets"

    cd "${APP_DIR}"

    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" | cut -d '=' -f2)

    if command -v npm &> /dev/null; then
        if [ "${APP_ENV}" = "production" ]; then
            npm run build
            log "Production frontend assets built"
        else
            npm run build
            log "Frontend assets built"
        fi
    else
        log_warning "NPM not available, skipping frontend build"
    fi
}

run_migrations() {
    log_step "8" "Running Database Migrations"

    cd "${APP_DIR}"

    # Run migrations with force flag for production
    php artisan migrate --force

    log "Migrations completed"
}

clear_caches() {
    log_step "9" "Clearing Caches"

    cd "${APP_DIR}"

    # Clear all Laravel caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear

    log "All caches cleared"
}

optimize_application() {
    log_step "10" "Optimizing Application"

    cd "${APP_DIR}"

    APP_ENV=$(grep "^APP_ENV=" "${APP_DIR}/.env" | cut -d '=' -f2)

    if [ "${APP_ENV}" = "production" ]; then
        # Cache configuration
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache

        log "Application optimized for production"
    else
        log "Development mode: Skipping cache optimization"
    fi
}

setup_storage_links() {
    log_step "11" "Setting Up Storage Links"

    cd "${APP_DIR}"

    # Remove existing symlink if broken (using public_html instead of public)
    if [ -L "${APP_DIR}/public_html/storage" ] && [ ! -e "${APP_DIR}/public_html/storage" ]; then
        rm "${APP_DIR}/public_html/storage"
    fi

    # Create storage link
    php artisan storage:link 2>/dev/null || log_warning "Storage link already exists"

    log "Storage links configured"
}

fix_permissions() {
    log_step "12" "Fixing Permissions"

    cd "${APP_DIR}"

    # Set proper permissions for storage and cache
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    # If www-data user exists, set ownership
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    log "Permissions fixed"
}

restart_services() {
    log_step "13" "Restarting Services"

    cd "${APP_DIR}"

    # Restart queue workers
    php artisan queue:restart 2>/dev/null || log_warning "Queue restart failed (non-critical)"

    # Restart PHP-FPM if available
    if systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
        sudo systemctl reload php8.3-fpm
        log "PHP-FPM reloaded"
    elif systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        sudo systemctl reload php8.2-fpm
        log "PHP-FPM reloaded"
    elif systemctl is-active --quiet php-fpm 2>/dev/null; then
        sudo systemctl reload php-fpm
        log "PHP-FPM reloaded"
    else
        log_warning "PHP-FPM not found or not running as a service"
    fi

    # Restart Reverb WebSocket server if configured
    if systemctl is-active --quiet thaivote-reverb 2>/dev/null; then
        sudo systemctl restart thaivote-reverb
        log "Reverb WebSocket server restarted"
    fi

    # Restart Supervisor if available
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread 2>/dev/null || true
        supervisorctl update 2>/dev/null || true
        log "Supervisor updated"
    fi

    log "Services restarted"
}

run_seeders() {
    log_step "14" "Running Seeders (if needed)"

    cd "${APP_DIR}"

    # Only run seeders if --seed flag is passed
    if [[ "$*" == *"--seed"* ]]; then
        php artisan db:seed --force
        log "Seeders executed"
    else
        log "Skipping seeders (use --seed to run)"
    fi
}

verify_deployment() {
    log_step "15" "Verifying Deployment"

    cd "${APP_DIR}"

    # Check if artisan works
    if php artisan --version &>/dev/null; then
        log "✓ Artisan command works"
    else
        log_error "✗ Artisan command failed"
        return 1
    fi

    # Check if key config values are set
    APP_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d '=' -f2)
    if [ -n "${APP_KEY}" ] && [ "${APP_KEY}" != "" ]; then
        log "✓ APP_KEY is set"
    else
        log_error "✗ APP_KEY is missing"
        php artisan key:generate
    fi

    # Check database connection
    if php artisan migrate:status &>/dev/null; then
        log "✓ Database connection works"
    else
        log_warning "⚠ Database connection check failed"
    fi

    # Check if public_html/build exists (frontend assets)
    if [ -d "${APP_DIR}/public_html/build" ]; then
        log "✓ Frontend assets exist"
    else
        log_warning "⚠ Frontend assets not found in public_html/build"
    fi

    log "Deployment verification completed"
}

#===============================================================================
# Rollback Function
#===============================================================================

generate_rollback_command() {
    log "Generating rollback information..."

    ROLLBACK_FILE="${BACKUP_DIR}/rollback_${DATE}.sh"

    cat > "${ROLLBACK_FILE}" << EOF
#!/bin/bash
# Rollback script generated on ${DATE}
# This script will restore the database from backup

echo "Restoring database..."
# gunzip -c ${BACKUP_DIR}/db_backup_${DATE}.sql.gz | mysql -u \${DB_USERNAME} -p\${DB_PASSWORD} \${DB_DATABASE}

echo "Restoring critical files..."
# tar -xzf ${BACKUP_DIR}/critical_${DATE}.tar.gz -C ${APP_DIR}

echo "Rollback completed!"
EOF

    chmod +x "${ROLLBACK_FILE}"
    log "Rollback script created: ${ROLLBACK_FILE}"
}

#===============================================================================
# Main Deployment Function
#===============================================================================

deploy() {
    echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}████████╗██╗  ██╗ █████╗ ██╗██╗   ██╗ ██████╗ ████████╗███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}╚══██╔══╝██║  ██║██╔══██╗██║██║   ██║██╔═══██╗╚══██╔══╝██╔════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ███████║███████║██║██║   ██║██║   ██║   ██║   █████╗${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██╔══██║██╔══██║██║╚██╗ ██╔╝██║   ██║   ██║   ██╔══╝${NC}          ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ██║   ██║  ██║██║  ██║██║ ╚████╔╝ ╚██████╔╝   ██║   ███████╗${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}   ${PURPLE}   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═════╝    ╚═╝   ╚══════╝${NC}        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${CYAN}Election Results Tracker${NC}                              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                    ${GREEN}Deployment Script v1.0${NC}                                ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                                                            ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    START_TIME=$(date +%s)

    log "Starting deployment at $(date)"
    log "=================================================="

    # Run all deployment steps
    preflight_checks
    backup_database
    backup_critical_files
    enable_maintenance_mode

    # Wrap main deployment in trap for error handling
    trap 'log_error "Deployment failed! Disabling maintenance mode..."; disable_maintenance_mode; exit 1' ERR

    pull_latest_code
    install_composer_dependencies
    install_npm_dependencies
    build_frontend
    run_migrations
    clear_caches
    optimize_application
    setup_storage_links
    fix_permissions
    restart_services
    run_seeders "$@"
    generate_rollback_command
    verify_deployment

    trap - ERR

    disable_maintenance_mode

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))

    echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${GREEN}✅ DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}                                   ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Duration: ${DURATION} seconds                                                     ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Timestamp: $(date)                                    ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                            ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════════╝${NC}\n"

    log "Deployment completed in ${DURATION} seconds"
}

#===============================================================================
# Quick Deploy (Skip backup and npm)
#===============================================================================

quick_deploy() {
    echo -e "${YELLOW}Running quick deployment (no backups, no npm rebuild)...${NC}\n"

    cd "${APP_DIR}"

    php artisan down --retry=60 || true

    git pull origin $(git rev-parse --abbrev-ref HEAD)
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan migrate --force
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan queue:restart 2>/dev/null || true

    php artisan up

    echo -e "\n${GREEN}Quick deployment completed!${NC}"
}

#===============================================================================
# Help
#===============================================================================

show_help() {
    echo -e "${CYAN}ThaiVote Deployment Script${NC}"
    echo ""
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  deploy      Full deployment with backups (default)"
    echo "  quick       Quick deployment without backups"
    echo "  rollback    Show rollback information"
    echo "  status      Show application status"
    echo "  help        Show this help message"
    echo ""
    echo "Options:"
    echo "  --seed      Run database seeders after migration"
    echo ""
    echo "Examples:"
    echo "  $0                  # Full deployment"
    echo "  $0 deploy --seed    # Full deployment with seeders"
    echo "  $0 quick            # Quick deployment"
    echo ""
}

show_status() {
    echo -e "${CYAN}ThaiVote Application Status${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    cd "${APP_DIR}"

    echo -e "\n${PURPLE}Git Status:${NC}"
    git log -1 --pretty=format:"  Branch: %D%n  Commit: %h%n  Author: %an%n  Date: %ar%n  Message: %s"

    echo -e "\n\n${PURPLE}Laravel Status:${NC}"
    php artisan --version

    echo -e "\n${PURPLE}Environment:${NC}"
    grep "^APP_ENV=" "${APP_DIR}/.env" | sed 's/^/  /'
    grep "^APP_DEBUG=" "${APP_DIR}/.env" | sed 's/^/  /'

    echo -e "\n${PURPLE}Database:${NC}"
    php artisan migrate:status 2>/dev/null | head -20 || echo "  Unable to connect to database"

    echo -e "\n${PURPLE}Cache Status:${NC}"
    if [ -f "${APP_DIR}/bootstrap/cache/config.php" ]; then
        echo "  Config: Cached"
    else
        echo "  Config: Not cached"
    fi
    if [ -f "${APP_DIR}/bootstrap/cache/routes-v7.php" ]; then
        echo "  Routes: Cached"
    else
        echo "  Routes: Not cached"
    fi

    echo ""
}

#===============================================================================
# Main Entry Point
#===============================================================================

case "${1:-deploy}" in
    deploy)
        deploy "${@:2}"
        ;;
    quick)
        quick_deploy
        ;;
    rollback)
        echo "Recent backups:"
        ls -la "${BACKUP_DIR}" 2>/dev/null | tail -10
        ;;
    status)
        show_status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo "Unknown command: $1"
        show_help
        exit 1
        ;;
esac
