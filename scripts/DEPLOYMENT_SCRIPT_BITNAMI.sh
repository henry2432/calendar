#!/bin/bash
################################################################################
# Kayarine Booking 插件 - Bitnami WordPress GCP 部署腳本
# 此腳本在 GCP 服務器上執行
# 用途：複製修改的插件文件到 WordPress 插件目錄
################################################################################

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Kayarine Booking 插件部署開始 ===${NC}"
echo "時間：$(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 配置
WP_PATH="/opt/bitnami/wordpress"
PLUGIN_PATH="$WP_PATH/wp-content/plugins/kayarine-booking"

# 檢查 WordPress 路徑
if [ ! -d "$WP_PATH" ]; then
    echo -e "${RED}✗ WordPress 路徑不存在：$WP_PATH${NC}"
    exit 1
fi

echo -e "${YELLOW}[步驟 1/4] 驗證插件路徑...${NC}"
if [ ! -d "$PLUGIN_PATH" ]; then
    echo -e "${RED}✗ 插件路徑不存在：$PLUGIN_PATH${NC}"
    echo "請先安裝 Kayarine Booking 插件"
    exit 1
fi
echo "✓ 插件路徑已確認"
echo ""

# 備份現有插件
echo -e "${YELLOW}[步驟 2/4] 備份現有插件...${NC}"
BACKUP_DIR="$HOME/kayarine_backup_$(date '+%Y%m%d_%H%M%S')"
mkdir -p "$BACKUP_DIR"
cp -r "$PLUGIN_PATH" "$BACKUP_DIR/kayarine-booking-backup"
echo "✓ 備份已保存至：$BACKUP_DIR"
echo ""

# 複製新文件（臨時文件應該已上傳到 /tmp）
echo -e "${YELLOW}[步驟 3/4] 部署新的插件文件...${NC}"

# 檢查臨時目錄中是否有新文件
if [ -d "/tmp/kayarine-booking" ]; then
    echo "發現臨時插件文件，開始複製..."
    cp -r /tmp/kayarine-booking/* "$PLUGIN_PATH/"
    echo "✓ 新文件已複製"
else
    echo "⚠ 臨時目錄中未找到新文件"
    echo "假設從本地部署腳本已上傳所有文件"
fi

# 設置權限
echo -e "${YELLOW}[步驟 4/4] 設置文件權限...${NC}"
chown -R www-data:www-data "$PLUGIN_PATH"
chmod -R 755 "$PLUGIN_PATH"
echo "✓ 權限已設置"
echo ""

# 驗證關鍵文件
echo -e "${YELLOW}驗證關鍵文件...${NC}"
if [ -f "$PLUGIN_PATH/kayarine-booking.php" ]; then
    echo "✓ 主插件文件存在"
else
    echo -e "${RED}✗ 主插件文件丟失${NC}"
    exit 1
fi

if [ -f "$PLUGIN_PATH/includes/class-kayarine-member-dashboard.php" ]; then
    echo "✓ 會員中心文件存在"
else
    echo -e "${RED}✗ 會員中心文件丟失${NC}"
    exit 1
fi

if [ -f "$PLUGIN_PATH/includes/class-kayarine-checkout-manager.php" ]; then
    echo "✓ 結帳管理器文件存在"
else
    echo -e "${RED}✗ 結帳管理器文件丟失${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=== 部署完成！ ===${NC}"
echo ""
echo "📝 部署信息："
echo "  • 插件路徑：$PLUGIN_PATH"
echo "  • 備份位置：$BACKUP_DIR"
echo "  • 部署時間：$(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 清除 WordPress 快取
echo -e "${YELLOW}清除 WordPress 快取...${NC}"
if command -v wp-cli &> /dev/null; then
    wp cache flush --allow-root || true
    echo "✓ WP-CLI 快取已清除"
elif [ -f "$WP_PATH/wp-cli.phar" ]; then
    php "$WP_PATH/wp-cli.phar" cache flush --allow-root || true
    echo "✓ WP-CLI 快取已清除"
else
    echo "⚠ 未找到 WP-CLI，跳過快取清除"
fi

echo ""
echo "✅ Kayarine Booking 插件部署成功！"
echo ""
echo "🔍 後續驗證："
echo "  1. 登入 WordPress 後台：https://kayarine.com.hk/wp-admin"
echo "  2. 檢查插件列表，確保 'Kayarine Booking' 已啟用"
echo "  3. 造訪會員中心：https://kayarine.com.hk/account"
echo "  4. 檢查結帳頁面：https://kayarine.com.hk/checkout"
echo ""
echo "🐛 如遇到問題，可查看錯誤日誌："
echo "  tail -50 $WP_PATH/wp-content/debug.log"
echo ""
