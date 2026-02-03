#!/bin/bash

################################################################################
# Kayarine GCP gcloud 快速部署腳本
# 使用：./deploy.sh [環境] [選項]
# 選項：--clear-cache
################################################################################

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_DIR/deploy.conf"

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 函數定義
log_info() {
    echo -e "${BLUE}[ℹ]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
    exit 1
}

# 檢查配置文件
if [ ! -f "$CONFIG_FILE" ]; then
    log_error "配置文件不存在: $CONFIG_FILE"
fi

# 加載配置
source "$CONFIG_FILE"

# 參數解析
ENVIRONMENT="${1:-$ENVIRONMENT}"
CLEAR_CACHE=false

shift || true
for arg in "$@"; do
    case $arg in
        --clear-cache) CLEAR_CACHE=true ;;
    esac
done

# 標題
echo -e "${BLUE}"
echo "╔═══════════════════════════════════════════════╗"
echo "║   Kayarine GCP gcloud 快速部署"
echo "║   環境: $ENVIRONMENT"
echo "║   時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "╚═══════════════════════════════════════════════╝"
echo -e "${NC}\n"

# 驗證 gcloud 連接
log_info "驗證 gcloud 連接 ($GCP_INSTANCE@$GCP_ZONE)..."
if gcloud compute ssh "$GCP_INSTANCE" --zone="$GCP_ZONE" --command="echo OK" 2>/dev/null | grep -q "OK"; then
    log_success "gcloud SSH 連接成功"
else
    log_error "gcloud SSH 連接失敗，請確保 gcloud 已配置並實例存在"
fi

# 打包文件
log_info "打包插件文件..."
TEMP_TAR="/tmp/kayarine-booking-deploy-$(date +%s).tar.gz"
tar --exclude='._*' --exclude='.DS_Store' --exclude='*.tar.gz' --exclude='.git' \
    -czf "$TEMP_TAR" -C "$SCRIPT_DIR" .
log_success "打包完成: $TEMP_TAR"

# 上傳文件
log_info "上傳至服務器..."
gcloud compute scp "$TEMP_TAR" "$GCP_INSTANCE:/tmp/" --zone="$GCP_ZONE" --quiet 2>/dev/null
log_success "文件上傳完成"

# 提取並部署
log_info "提取並部署插件..."
REMOTE_DEPLOY_CMD="
set -e
TEMP_TAR_FILE=\$(ls -t /tmp/kayarine-booking-deploy-*.tar.gz | head -1)
echo '=== Backup current version ==='
sudo mv $PLUGIN_PATH ${PLUGIN_PATH}-backup 2>/dev/null || true
echo '=== Extract new version ==='
sudo mkdir -p $PLUGIN_PATH
sudo tar -xzf \$TEMP_TAR_FILE -C $PLUGIN_PATH
echo '=== Fix permissions ==='
sudo chown -R daemon:daemon $PLUGIN_PATH
echo '=== Remove temp file ==='
rm \$TEMP_TAR_FILE
echo '=== Verify version ==='
grep 'Version:' $PLUGIN_PATH/kayarine-booking.php | head -1
echo '=== Done ==='
"

if gcloud compute ssh "$GCP_INSTANCE" --zone="$GCP_ZONE" --command="$REMOTE_DEPLOY_CMD" 2>/dev/null | tail -20; then
    log_success "插件部署完成"
else
    log_error "插件部署失敗"
fi

# 清除緩存
if [ "$CLEAR_CACHE" = true ]; then
    log_info "清除 WordPress 緩存..."
    
    CACHE_CMD="
        cd '$WP_PATH'
        if command -v wp &> /dev/null; then
            wp cache flush
            wp plugin deactivate kayarine-booking --allow-root 2>/dev/null || true
            wp plugin activate kayarine-booking --allow-root 2>/dev/null || true
            echo '✓ 緩存已清除，插件已重新啟用'
        else
            echo '⚠ wp-cli 未安裝'
        fi
    "
    
    if gcloud compute ssh "$GCP_INSTANCE" --zone="$GCP_ZONE" --command="$CACHE_CMD" 2>/dev/null; then
        log_success "緩存已清除"
    else
        log_warning "緩存清除失敗（wp-cli 未安裝或未啟用）"
    fi
fi

# Slack 通知
if [ -n "$SLACK_WEBHOOK" ]; then
    log_info "發送 Slack 通知..."
    
    DEPLOY_STATUS="✅ 部署成功"
    DEPLOY_TIME=$(date '+%Y-%m-%d %H:%M:%S')
    
    curl -X POST "$SLACK_WEBHOOK" \
        -H 'Content-Type: application/json' \
        -d "{
            \"text\": \"$DEPLOY_STATUS\",
            \"attachments\": [{
                \"color\": \"good\",
                \"fields\": [
                    {\"title\": \"環境\", \"value\": \"$ENVIRONMENT\", \"short\": true},
                    {\"title\": \"時間\", \"value\": \"$DEPLOY_TIME\", \"short\": true},
                    {\"title\": \"實例\", \"value\": \"$GCP_INSTANCE\", \"short\": true}
                ]
            }]
        }" > /dev/null 2>&1
    
    log_success "Slack 通知已發送"
fi

# 清理本地臨時文件
rm -f "$TEMP_TAR"
log_success "本地臨時文件已清理"

# 完成
echo -e "\n${GREEN}"
echo "╔═══════════════════════════════════════════════╗"
echo "║   ✓ 部署完成"
echo "║   部署時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo "║   實例: $GCP_INSTANCE ($GCP_ZONE)"
echo "╚═══════════════════════════════════════════════╝"
echo -e "${NC}"

echo ""
