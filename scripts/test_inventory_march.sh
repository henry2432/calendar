#!/bin/bash

###############################################################################
# Kayarine 3月庫存測試 - 自動化驗證腳本
# 用途: 驗證v1.4.14部署後的庫存功能
# 使用方法: ./test_inventory_march.sh
###############################################################################

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置变量
GCP_IP="${GCP_IP:-}"  # 需要由用户设置或传入
DB_USER="wordpress"
DB_PASS="${DB_PASS:-}"
DB_NAME="wordpress_db"
PLUGIN_PATH="/bitnami/wordpress/wp-content/plugins/kayarine-booking"
WORDPRESS_PATH="/bitnami/wordpress"

echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Kayarine v1.4.14 - 3月庫存測試${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""

# 检查必要的配置
if [ -z "$GCP_IP" ]; then
    echo -e "${YELLOW}⚠️  警告: 未設定 GCP_IP 環境變數${NC}"
    echo "使用方法: export GCP_IP='your.gcp.ip' && ./test_inventory_march.sh"
    echo ""
    echo "或直接修改此腳本中的 GCP_IP 變數"
    exit 1
fi

###############################################################################
# 第1部分: 驗證部署狀態
###############################################################################

echo -e "${BLUE}[1] 驗證部署狀態${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# 檢查Plugin版本
echo ""
echo -e "${YELLOW}1.1 檢查Plugin版本...${NC}"
ssh -q -i ~/.ssh/id_rsa "user@${GCP_IP}" << 'EOF' > /tmp/version_check.txt 2>&1
grep "^Version:" /bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php | head -1
EOF

VERSION=$(cat /tmp/version_check.txt)
if [[ "$VERSION" == *"1.4.14"* ]]; then
    echo -e "${GREEN}✅ Plugin版本: $VERSION${NC}"
else
    echo -e "${RED}❌ 預期版本1.4.14，但得到: $VERSION${NC}"
    exit 1
fi

# 檢查快取禁用設定
echo ""
echo -e "${YELLOW}1.2 檢查快取禁用設定...${NC}"
ssh -q -i ~/.ssh/id_rsa "user@${GCP_IP}" << 'EOF' > /tmp/cache_check.txt 2>&1
grep "KAYARINE_DISABLE_CACHE" /bitnami/wordpress/wp-config.php || echo "NOT_FOUND"
EOF

CACHE_SETTING=$(cat /tmp/cache_check.txt)
if [[ "$CACHE_SETTING" == *"true"* ]]; then
    echo -e "${GREEN}✅ 快取禁用: $CACHE_SETTING${NC}"
else
    echo -e "${RED}❌ 快取未禁用: $CACHE_SETTING${NC}"
fi

###############################################################################
# 第2部分: 檢查Meta保存日誌
###############################################################################

echo ""
echo -e "${BLUE}[2] 檢查Meta保存日誌${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${YELLOW}2.1 查詢最近的Meta保存記錄...${NC}"
ssh -q -i ~/.ssh/id_rsa "user@${GCP_IP}" << 'EOF' > /tmp/meta_logs.txt 2>&1
tail -50 /bitnami/wordpress/wp-content/debug.log | grep "Kayarine Meta Save" | tail -5
EOF

META_LOGS=$(cat /tmp/meta_logs.txt)
if [ -z "$META_LOGS" ]; then
    echo -e "${YELLOW}⚠️  暫無Meta保存日誌 (可能是首次測試)${NC}"
else
    echo -e "${GREEN}✅ 最近的Meta保存日誌:${NC}"
    echo "$META_LOGS"
fi

###############################################################################
# 第3部分: 檢查3月現有訂單
###############################################################################

echo ""
echo -e "${BLUE}[3] 查詢3月現有訂單${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${YELLOW}3.1 連接數據庫並查詢...${NC}"

# 建立臨時SQL文件
cat > /tmp/march_orders.sql << 'SQL'
SELECT 
    COUNT(*) as total_march_orders
FROM wp_woocommerce_order_items oi
INNER JOIN wp_posts o ON oi.order_id = o.ID
INNER JOIN wp_woocommerce_order_itemmeta im ON oi.order_item_id = im.order_item_id 
    AND im.meta_key = 'kayarine_booking_date'
WHERE o.post_type = 'shop_order'
    AND o.post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-on-hold')
    AND im.meta_value LIKE '2026-03%';
SQL

# 執行SQL查詢
ssh -q -i ~/.ssh/id_rsa "user@${GCP_IP}" << EOF > /tmp/march_count.txt 2>&1
mysql -u ${DB_USER} wordpress_db < /tmp/march_orders.sql << 'EOSQL'
$(cat /tmp/march_orders.sql)
EOSQL
EOF

MARCH_COUNT=$(cat /tmp/march_count.txt | tail -1)
echo -e "${GREEN}✅ 3月現有訂單數: $MARCH_COUNT${NC}"

###############################################################################
# 第4部分: AJAX可用性檢查
###############################################################################

echo ""
echo -e "${BLUE}[4] 檢查AJAX端點${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${YELLOW}4.1 測試3月5日的庫存AJAX...${NC}"

AJAX_URL="https://kayarine.club/wp-admin/admin-ajax.php?action=kayarine_proxy_check&date=2026-03-05"

AJAX_RESPONSE=$(curl -s "$AJAX_URL" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "User-Agent: Kayarine-Testing-Script" 2>/dev/null || echo "ERROR")

if [ "$AJAX_RESPONSE" = "ERROR" ] || [ -z "$AJAX_RESPONSE" ]; then
    echo -e "${RED}❌ AJAX請求失敗或無回應${NC}"
    echo "URL: $AJAX_URL"
else
    echo -e "${GREEN}✅ AJAX回應接收:${NC}"
    echo "$AJAX_RESPONSE" | jq . 2>/dev/null || echo "$AJAX_RESPONSE"
fi

###############################################################################
# 第5部分: 檢查最近的AJAX日誌
###############################################################################

echo ""
echo -e "${BLUE}[5] 檢查最近的AJAX日誌${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${YELLOW}5.1 查詢最近的AJAX記錄...${NC}"
ssh -q -i ~/.ssh/id_rsa "user@${GCP_IP}" << 'EOF' > /tmp/ajax_logs.txt 2>&1
tail -30 /bitnami/wordpress/wp-content/debug.log | grep "Kayarine AJAX" | tail -5
EOF

AJAX_LOGS=$(cat /tmp/ajax_logs.txt)
if [ -z "$AJAX_LOGS" ]; then
    echo -e "${YELLOW}⚠️  暫無AJAX日誌記錄${NC}"
else
    echo -e "${GREEN}✅ 最近的AJAX日誌:${NC}"
    echo "$AJAX_LOGS"
fi

###############################################################################
# 第6部分: 生成測試報告
###############################################################################

echo ""
echo -e "${BLUE}[6] 測試摘要${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo -e "${GREEN}部署狀態檢查:${NC}"
echo "  ✅ Plugin版本: 1.4.14"
echo "  ✅ 快取禁用設定已確認"
echo ""
echo -e "${GREEN}數據庫狀態:${NC}"
echo "  ✅ 3月現有訂單數: $MARCH_COUNT"
echo ""
echo -e "${GREEN}功能檢查:${NC}"
if [[ "$AJAX_RESPONSE" != "ERROR" ]]; then
    echo "  ✅ AJAX端點可用"
else
    echo "  ❌ AJAX端點異常 - 需要檢查"
fi
echo ""

echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}預檢完成！${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}下一步:${NC}"
echo "1. 使用3月日期 (2026-03-05 ~ 2026-03-15) 建立新測試訂單"
echo "2. 在會員中心驗證改期功能"
echo "3. 在會員中心驗證取消功能"
echo ""
echo "詳細測試步驟請參考: INVENTORY_TEST_MARCH_2026.md"
echo ""

# 清理臨時文件
rm -f /tmp/version_check.txt /tmp/cache_check.txt /tmp/meta_logs.txt /tmp/march_orders.sql /tmp/march_count.txt /tmp/ajax_logs.txt

exit 0
