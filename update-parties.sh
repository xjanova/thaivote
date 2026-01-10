#!/bin/bash
#===============================================================================
# ThaiVote - Political Party Data Updater
# อัพเดทข้อมูลพรรคการเมืองจาก กกต.
#
# Version: 1.0
# Usage:
#   ./update-parties.sh           # Update all party data
#   ./update-parties.sh --verbose # Show detailed output
#===============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration
APP_DIR=$(cd "$(dirname "$0")" && pwd)
VERBOSE=false

# Logging Functions
log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${PURPLE}📌${NC} ${WHITE}$1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Parse options
while [[ $# -gt 0 ]]; do
    case $1 in
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Update political party data from Electoral Commission of Thailand (กกต.)"
            echo ""
            echo "Options:"
            echo "  --verbose, -v    Show detailed output"
            echo "  --help, -h       Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                # Update all party data"
            echo "  $0 --verbose      # Update with detailed output"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Main execution
cd "${APP_DIR}"

log_step "อัพเดทข้อมูลพรรคการเมือง (Political Party Data Update)"

# Check database connection
log_info "Checking database connection..."
if ! php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
    log_error "Database connection failed!"
    exit 1
fi
log "Database connection OK"

# Get current party count
log_info "Fetching current party count..."
party_count_before=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
log "Current parties in database: ${party_count_before}"

# Run PartySeeder
log_step "Running PartySeeder..."
log_info "Source: Electoral Commission of Thailand (กกต.)"
log_info "URL: https://party.ect.go.th/"

if [ "$VERBOSE" = true ]; then
    php artisan db:seed --class=PartySeeder --force
else
    SEED_OUTPUT=$(php artisan db:seed --class=PartySeeder --force 2>&1)
fi
SEED_EXIT=$?

if [ $SEED_EXIT -eq 0 ]; then
    # Get updated count
    party_count_after=$(php artisan tinker --execute="echo App\Models\Party::count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")

    # Parse output for statistics
    if [ "$VERBOSE" = false ]; then
        created=$(echo "$SEED_OUTPUT" | grep -oP "Parties: \K[0-9]+ created" | grep -oP "^[0-9]+" || echo "0")
        updated=$(echo "$SEED_OUTPUT" | grep -oP ", \K[0-9]+ updated" | grep -oP "^[0-9]+" || echo "0")
    else
        # For verbose mode, parse from visible output
        sleep 1
        created=$(php artisan tinker --execute="echo App\Models\Party::where('created_at', '>', now()->subMinutes(1))->count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
        updated=$((party_count_after - party_count_before - created))
    fi

    log_step "📊 Summary / สรุปผลการอัพเดท"
    log "✅ Party data updated successfully!"
    log ""
    log "  📈 Statistics:"
    log "     • Total parties: ${party_count_after}"
    log "     • New parties created: ${created}"
    log "     • Existing parties updated: ${updated}"
    log ""

    # Get detailed statistics
    active_count=$(php artisan tinker --execute="echo App\Models\Party::where('is_active', true)->count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")
    with_number=$(php artisan tinker --execute="echo App\Models\Party::whereNotNull('party_number')->count();" 2>/dev/null | grep -E "^[0-9]+$" | head -1 || echo "0")

    log "  📋 Party Status:"
    log "     • Active parties: ${active_count}"
    log "     • Parties with ballot numbers: ${with_number}"
    log "     • Inactive parties: $((party_count_after - active_count))"
    log ""
    log "  📅 Updated: $(date '+%Y-%m-%d %H:%M:%S')"
    log "  📍 Data source: กกต. (Electoral Commission of Thailand)"
    log ""

    echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   ${GREEN}✅ UPDATE COMPLETED SUCCESSFULLY!${NC}                         ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}   Updated ${party_count_after} political parties from กกต.                   ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

    exit 0
else
    log_error "Failed to update party data!"

    if [ "$VERBOSE" = false ]; then
        log_error "Error output:"
        echo "$SEED_OUTPUT"
    fi

    # Check for common errors
    if echo "$SEED_OUTPUT" | grep -q "Unknown column"; then
        UNKNOWN_COL=$(echo "$SEED_OUTPUT" | grep -oP "Unknown column '\K[^']+" | head -1 || echo "unknown")
        log_warning "Database schema mismatch: Column '$UNKNOWN_COL' not found"
        log_warning "Try running migrations: php artisan migrate"
    fi

    if echo "$SEED_OUTPUT" | grep -q "Duplicate entry"; then
        log_warning "Duplicate data detected"
        log_info "This usually happens when updating existing data"
    fi

    exit 1
fi
