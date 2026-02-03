#!/bin/bash

################################################################################
# Kayarine v1.4.14 - 3月庫存測試自動化腳本
# 無需SSH - 純粹基於HTTP API測試
# 用法: bash run_march_inventory_test.sh
################################################################################

set -o pipefail

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 配置
SITE_URL="https://kayarine.club"
PRODUCT_ID="6954"  # 單人皮艇
TEST_DATE_1="2026-03-05"
TEST_DATE_2="2026-03-10"

# 結果記錄
RESULTS_FILE="test_results_$(date +%Y%m%d_%H%M%S).txt"

echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     Kayarine v1.4.14 - 3月庫存功能自動化測試                  ║${NC}"
echo -e "${BLUE}║            (基於HTTP API - 無需SSH連接)                      ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# 記錄開始時間
START_TIME=$(date '+%Y-%m-%d %H:%M:%S')
echo "開始時間: $START_TIME"
echo "網站URL: $SITE_URL"
echo "測試日期: $TEST_DATE_1 (新訂單) 和 $TEST_DATE_2 (改期)"
echo ""

################################################################################
# 函數：檢查AJAX端點是否可用
################################################################################
check_ajax_endpoint() {
    echo -e "${CYAN}[檢查] AJAX端點可用性...${NC}"
    
    local response=$(curl -s -m 5 -w "\n%{http_code}" \
        "${SITE_URL}/wp-admin/admin-ajax.php?action=kayarine_proxy_check&date=${TEST_DATE_1}" \
        -H "X-Requested-With: XMLHttpRequest" 2>/dev/null)
    
    local http_code=$(echo "$response" | tail -n 1)
    local body=$(echo "$response" | head -n -1)
    
    if [[ "$http_code" == "200" ]] && echo "$body" | grep -q "status"; then
        echo -e "${GREEN}✅ AJAX端點正常 (HTTP $http_code)${NC}"
        return 0
    else
        echo -e "${RED}❌ AJAX端點異常 (HTTP $http_code)${NC}"
        echo "   提示: 如果返回403，可能是CloudFlare保護"
        echo "   請在瀏覽器中直接訪問測試"
        return 1
    fi
}

################################################################################
# 函數：查詢庫存
################################################################################
query_inventory() {
    local date=$1
    local date_display=$(echo "$date" | sed 's/-/\//g')
    
    echo -e "${CYAN}[查詢] $date_display 的庫存...${NC}"
    
    local response=$(curl -s -m 5 \
        "${SITE_URL}/wp-admin/admin-ajax.php?action=kayarine_proxy_check&date=${date}" \
        -H "X-Requested-With: XMLHttpRequest" 2>/dev/null)
    
    if echo "$response" | grep -q "status.*success"; then
        # 提取庫存數據
        local used=$(echo "$response" | grep -oP '"used":\K[0-9]+' | head -1)
        local remaining=$(echo "$response" | grep -oP '"remaining":\K[0-9]+' | head -1)
        local total=$(echo "$response" | grep -oP '"total":\K[0-9]+' | head -1)
        
        echo -e "${GREEN}✅ 庫存查詢成功${NC}"
        echo "   Total: $total | Used: $used | Remaining: $remaining"
        
        # 返回值用於比較
        echo "$used|$remaining"
    else
        echo -e "${RED}❌ 庫存查詢失敗${NC}"
        echo "   回應: $(echo "$response" | head -c 100)..."
        return 1
    fi
}

################################################################################
# 函數：等待用戶完成操作
################################################################################
wait_for_action() {
    local action=$1
    local date=${2:-"N/A"}
    
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}【人工操作所需】${NC}"
    echo -e "${YELLOW}請在會員中心執行以下操作：${NC}"
    
    case $action in
        "create_order")
            echo -e "  1. 訪問 ${CYAN}https://kayarine.club/shop${NC}"
            echo -e "  2. 選擇日期: ${CYAN}$date${NC}"
            echo -e "  3. 選擇產品: ${CYAN}單人皮艇 (Product ID: $PRODUCT_ID)${NC}"
            echo -e "  4. 數量: ${CYAN}2${NC}"
            echo -e "  5. 完成結帳"
            echo -e "  ${YELLOW}然後按 ${CYAN}Enter${NC} ${YELLOW}繼續...${NC}"
            ;;
        "reschedule")
            echo -e "  1. 訪問 ${CYAN}https://kayarine.club/account${NC}"
            echo -e "  2. 找到日期為 ${CYAN}${TEST_DATE_1}${NC} 的訂單"
            echo -e "  3. 點擊 ${CYAN}\"改期\"${NC} 按鈕"
            echo -e "  4. 選擇新日期: ${CYAN}${TEST_DATE_2}${NC}"
            echo -e "  5. 確認改期"
            echo -e "  ${YELLOW}然後按 ${CYAN}Enter${NC} ${YELLOW}繼續...${NC}"
            ;;
        "cancel")
            echo -e "  1. 訪問 ${CYAN}https://kayarine.club/account${NC}"
            echo -e "  2. 找到日期為 ${CYAN}${TEST_DATE_2}${NC} 的訂單"
            echo -e "  3. 點擊 ${CYAN}\"取消訂單\"${NC} 按鈕"
            echo -e "  4. 確認取消"
            echo -e "  ${YELLOW}然後按 ${CYAN}Enter${NC} ${YELLOW}繼續...${NC}"
            ;;
    esac
    
    read -p ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

################################################################################
# 主測試流程
################################################################################

echo "════════════════════════════════════════════════════════════════"
echo "【階段1】檢查前置條件"
echo "════════════════════════════════════════════════════════════════"
echo ""

# 檢查curl是否可用
if ! command -v curl &> /dev/null; then
    echo -e "${RED}❌ 錯誤: 未找到curl命令${NC}"
    exit 1
fi
echo -e "${GREEN}✅ curl已安裝${NC}"

# 檢查jq（可選）
if ! command -v jq &> /dev/null; then
    echo -e "${YELLOW}⚠️  提示: 未安裝jq，部分格式化功能不可用${NC}"
    echo "   (但不影響測試進行)"
fi

# 檢查AJAX端點
if ! check_ajax_endpoint; then
    echo ""
    echo -e "${YELLOW}AJAX端點連接失敗，但可以在瀏覽器中進行測試。${NC}"
    echo -e "${YELLOW}請按以下步驟操作：${NC}"
    echo ""
    echo "1. 打開瀏覽器，訪問: https://kayarine.club"
    echo "2. 按 F12 打開Developer Tools"
    echo "3. 進入 Console 標籤"
    echo "4. 執行本文件中提供的JavaScript命令"
    echo ""
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "【階段2】新訂單測試"
echo "════════════════════════════════════════════════════════════════"
echo ""

# 查詢初始庫存
echo -e "${CYAN}[步驟2.1] 查詢初始庫存${NC}"
INITIAL_INVENTORY=$(query_inventory "$TEST_DATE_1")
INITIAL_USED=$(echo "$INITIAL_INVENTORY" | cut -d'|' -f1)
INITIAL_REMAINING=$(echo "$INITIAL_INVENTORY" | cut -d'|' -f2)

echo -e "${GREEN}初始庫存已記錄:${NC}"
echo -e "  Used: $INITIAL_USED | Remaining: $INITIAL_REMAINING"
echo ""

# 等待用戶建立訂單
wait_for_action "create_order" "$TEST_DATE_1"

# 查詢更新後的庫存
echo -e "${CYAN}[步驟2.2] 查詢更新後的庫存${NC}"
sleep 2  # 給服務器時間處理
AFTER_ORDER_INVENTORY=$(query_inventory "$TEST_DATE_1")
AFTER_ORDER_USED=$(echo "$AFTER_ORDER_INVENTORY" | cut -d'|' -f1)
AFTER_ORDER_REMAINING=$(echo "$AFTER_ORDER_INVENTORY" | cut -d'|' -f2)

echo -e "${GREEN}訂單後庫存:${NC}"
echo -e "  Used: $AFTER_ORDER_USED | Remaining: $AFTER_ORDER_REMAINING"
echo ""

# 驗證第一階段
echo -e "${CYAN}[驗證] 新訂單測試${NC}"
EXPECTED_USED=$((INITIAL_USED + 2))
EXPECTED_REMAINING=$((INITIAL_REMAINING - 2))

if [[ "$AFTER_ORDER_USED" -eq "$EXPECTED_USED" ]] && [[ "$AFTER_ORDER_REMAINING" -eq "$EXPECTED_REMAINING" ]]; then
    echo -e "${GREEN}✅ 通過: 庫存正確反映新訂單${NC}"
    PHASE1_PASS=1
else
    echo -e "${RED}❌ 失敗: 庫存未正確更新${NC}"
    echo "   預期: Used=$EXPECTED_USED, Remaining=$EXPECTED_REMAINING"
    echo "   實際: Used=$AFTER_ORDER_USED, Remaining=$AFTER_ORDER_REMAINING"
    PHASE1_PASS=0
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "【階段3】改期測試"
echo "════════════════════════════════════════════════════════════════"
echo ""

# 等待用戶改期
wait_for_action "reschedule" "$TEST_DATE_1"

# 查詢舊日期庫存（應該恢復）
echo -e "${CYAN}[步驟3.1] 查詢舊日期庫存${NC}"
sleep 2
OLD_DATE_INVENTORY=$(query_inventory "$TEST_DATE_1")
OLD_DATE_USED=$(echo "$OLD_DATE_INVENTORY" | cut -d'|' -f1)
OLD_DATE_REMAINING=$(echo "$OLD_DATE_INVENTORY" | cut -d'|' -f2)

echo -e "${GREEN}舊日期庫存:${NC}"
echo -e "  Used: $OLD_DATE_USED | Remaining: $OLD_DATE_REMAINING"
echo ""

# 查詢新日期庫存（應該被扣除）
echo -e "${CYAN}[步驟3.2] 查詢新日期庫存${NC}"
NEW_DATE_INVENTORY=$(query_inventory "$TEST_DATE_2")
NEW_DATE_USED=$(echo "$NEW_DATE_INVENTORY" | cut -d'|' -f1)
NEW_DATE_REMAINING=$(echo "$NEW_DATE_INVENTORY" | cut -d'|' -f2)

echo -e "${GREEN}新日期庫存:${NC}"
echo -e "  Used: $NEW_DATE_USED | Remaining: $NEW_DATE_REMAINING"
echo ""

# 驗證改期
echo -e "${CYAN}[驗證] 改期測試${NC}"
PHASE2_PASS=0

# 檢查舊日期是否恢復
if [[ "$OLD_DATE_USED" -eq "$INITIAL_USED" ]] && [[ "$OLD_DATE_REMAINING" -eq "$INITIAL_REMAINING" ]]; then
    echo -e "${GREEN}✅ 舊日期庫存恢復正確${NC}"
    PHASE2_PASS=1
else
    echo -e "${RED}❌ 舊日期庫存未正確恢復${NC}"
    echo "   預期: Used=$INITIAL_USED, Remaining=$INITIAL_REMAINING"
    echo "   實際: Used=$OLD_DATE_USED, Remaining=$OLD_DATE_REMAINING"
    PHASE2_PASS=0
fi

# 檢查新日期是否扣除
if [[ "$NEW_DATE_USED" -eq "2" ]]; then
    echo -e "${GREEN}✅ 新日期庫存扣除正確${NC}"
else
    echo -e "${RED}❌ 新日期庫存未正確扣除${NC}"
    echo "   預期: Used=2"
    echo "   實際: Used=$NEW_DATE_USED"
    PHASE2_PASS=0
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "【階段4】取消測試"
echo "════════════════════════════════════════════════════════════════"
echo ""

# 等待用戶取消
wait_for_action "cancel" "$TEST_DATE_2"

# 查詢取消後的庫存
echo -e "${CYAN}[步驟4.1] 查詢取消後的庫存${NC}"
sleep 2
AFTER_CANCEL_INVENTORY=$(query_inventory "$TEST_DATE_2")
AFTER_CANCEL_USED=$(echo "$AFTER_CANCEL_INVENTORY" | cut -d'|' -f1)
AFTER_CANCEL_REMAINING=$(echo "$AFTER_CANCEL_INVENTORY" | cut -d'|' -f2)

echo -e "${GREEN}取消後庫存:${NC}"
echo -e "  Used: $AFTER_CANCEL_USED | Remaining: $AFTER_CANCEL_REMAINING"
echo ""

# 驗證取消
echo -e "${CYAN}[驗證] 取消測試${NC}"
PHASE3_PASS=0

if [[ "$AFTER_CANCEL_USED" -eq "0" ]] && [[ "$AFTER_CANCEL_REMAINING" -eq "50" ]]; then
    echo -e "${GREEN}✅ 取消後庫存正確恢復${NC}"
    PHASE3_PASS=1
else
    echo -e "${RED}❌ 取消後庫存未正確恢復${NC}"
    echo "   預期: Used=0, Remaining=50"
    echo "   實際: Used=$AFTER_CANCEL_USED, Remaining=$AFTER_CANCEL_REMAINING"
    PHASE3_PASS=0
fi

################################################################################
# 最終結論
################################################################################

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "【測試結論】"
echo "════════════════════════════════════════════════════════════════"
echo ""

TOTAL_PASS=0
if [[ $PHASE1_PASS -eq 1 ]]; then
    echo -e "${GREEN}✅ 階段1 (新訂單測試): 通過${NC}"
    ((TOTAL_PASS++))
else
    echo -e "${RED}❌ 階段1 (新訂單測試): 失敗${NC}"
fi

if [[ $PHASE2_PASS -eq 1 ]]; then
    echo -e "${GREEN}✅ 階段2 (改期測試): 通過${NC}"
    ((TOTAL_PASS++))
else
    echo -e "${RED}❌ 階段2 (改期測試): 失敗${NC}"
fi

if [[ $PHASE3_PASS -eq 1 ]]; then
    echo -e "${GREEN}✅ 階段3 (取消測試): 通過${NC}"
    ((TOTAL_PASS++))
else
    echo -e "${RED}❌ 階段3 (取消測試): 失敗${NC}"
fi

echo ""

if [[ $TOTAL_PASS -eq 3 ]]; then
    echo -e "${GREEN}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}🎉 所有測試通過！系統運作正常。${NC}"
    echo -e "${GREEN}════════════════════════════════════════════════════════════════${NC}"
    FINAL_STATUS=0
elif [[ $TOTAL_PASS -ge 1 ]]; then
    echo -e "${YELLOW}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}⚠️  部分測試失敗，需要進一步調查。${NC}"
    echo -e "${YELLOW}════════════════════════════════════════════════════════════════${NC}"
    FINAL_STATUS=1
else
    echo -e "${RED}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}❌ 測試失敗，系統可能有問題。${NC}"
    echo -e "${RED}════════════════════════════════════════════════════════════════${NC}"
    FINAL_STATUS=2
fi

echo ""
echo "測試完成時間: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

exit $FINAL_STATUS
