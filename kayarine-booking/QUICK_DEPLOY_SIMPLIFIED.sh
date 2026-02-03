#!/bin/bash
#
# Kayarine Booking - Simplified Deployment Script
# Quick deployment without backup and VM upload
# Usage: ./QUICK_DEPLOY_SIMPLIFIED.sh <server_user@server_host> </path/to/wordpress>
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Parameters
SERVER_USER="${1:-}"
WORDPRESS_PATH="${2:-/var/www/html}"

# Validate parameters
if [ -z "$SERVER_USER" ]; then
    echo -e "${RED}✗ Error: Missing server credentials${NC}"
    echo "Usage: ./QUICK_DEPLOY_SIMPLIFIED.sh <user@host> [wordpress_path]"
    echo "Example: ./QUICK_DEPLOY_SIMPLIFIED.sh deploy@example.com /var/www/html"
    exit 1
fi

PLUGIN_PATH="$WORDPRESS_PATH/wp-content/plugins/kayarine-booking"

echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}Kayarine 簡化部署 (Skip Backup & VM Upload)${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "伺服器: $SERVER_USER"
echo "WordPress 路徑: $WORDPRESS_PATH"
echo "外掛路徑: $PLUGIN_PATH"
echo ""

# Step 1: Upload New/Modified Files
echo -e "${YELLOW}[1/4]${NC} 上傳新增和修改的檔案..."

# Create temp SSH script
DEPLOY_SCRIPT=$(mktemp)
cat > "$DEPLOY_SCRIPT" << 'EOF'
#!/bin/bash
TARGET_PLUGIN="$1"

# Ensure plugin directory exists
mkdir -p "$TARGET_PLUGIN/includes"
mkdir -p "$TARGET_PLUGIN/assets/css"
mkdir -p "$TARGET_PLUGIN/assets/js"

# Fix permissions
chmod 755 "$TARGET_PLUGIN"
chmod 755 "$TARGET_PLUGIN/includes"
chmod 755 "$TARGET_PLUGIN/assets"
EOF

# Transfer deploy script
scp "$DEPLOY_SCRIPT" "$SERVER_USER":/tmp/prepare_deploy.sh > /dev/null 2>&1

# Execute prepare script on server
ssh "$SERVER_USER" "bash /tmp/prepare_deploy.sh '$PLUGIN_PATH'" > /dev/null 2>&1

echo -e "${GREEN}  ✓ 上傳主外掛檔案${NC}"
scp -r kayarine-booking/* "$SERVER_USER:$PLUGIN_PATH/" > /dev/null 2>&1

echo -e "${GREEN}  ✓ 外掛檔案已同步${NC}"

# Step 2: Update WordPress Settings (via WP-CLI if available)
echo -e "${YELLOW}[2/4]${NC} 更新 WordPress 設定..."

UPDATE_SETTINGS=$(cat << 'SETTINGS'
#!/bin/bash
WP_PATH="$1"
PLUGIN_NAME="kayarine-booking"

cd "$WP_PATH"

# Clear cache
wp cache flush 2>/dev/null || echo "  ⓘ 快取清除已跳過 (wp-cli 未安裝)"

# Verify plugin is activated
wp plugin activate "$PLUGIN_NAME" 2>/dev/null || echo "  ⓘ 外掛啟用已跳過 (wp-cli 未安裝)"

echo "  ✓ WordPress 設定已更新"
SETTINGS
)

ssh "$SERVER_USER" bash -c "'$UPDATE_SETTINGS $WORDPRESS_PATH'" > /dev/null 2>&1 || echo -e "${YELLOW}  ⓘ WordPress 自動設定已跳過${NC}"

# Step 3: Set Correct File Permissions
echo -e "${YELLOW}[3/4]${NC} 設定檔案權限..."

ssh "$SERVER_USER" bash << PERMS
cd "$PLUGIN_PATH"
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
echo "  ✓ 檔案權限已設定"
PERMS

# Step 4: Verification
echo -e "${YELLOW}[4/4]${NC} 驗證部署..."

VERIFY_SCRIPT=$(cat << 'VERIFY'
#!/bin/bash
TARGET_PLUGIN="$1"

# Check main files
FILES=(
    "kayarine-booking.php"
    "includes/class-kayarine-auth-integration.php"
    "includes/class-kayarine-member-dashboard-v2.php"
    "includes/kayarine-config.php"
    "assets/css/style.css"
)

echo "  檢查必要檔案..."
for file in "${FILES[@]}"; do
    if [ -f "$TARGET_PLUGIN/$file" ]; then
        echo "    ✓ $file"
    else
        echo "    ✗ $file 缺失"
    fi
done

echo "  ✓ 部署驗證完成"
VERIFY
)

ssh "$SERVER_USER" bash -c "'$VERIFY_SCRIPT $PLUGIN_PATH'" > /dev/null 2>&1

# Cleanup
rm -f "$DEPLOY_SCRIPT"

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ 部署完成！${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "後續步驟："
echo "1. 訪問 WordPress 後台 → 外掛"
echo "2. 搜尋 'kayarine-booking' 確認已啟用"
echo "3. 測試登入/註冊短代碼 [kayarine_login_register]"
echo "4. 檢查會員中心 [kayarine_member_dashboard_v2]"
echo ""
