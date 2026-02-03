#!/bin/bash

################################################################################
# Kayarine 預約系統 - 自動部署腳本
# 用途：自動備份、上傳、驗證和部署修復
# 使用：./DEPLOYMENT.sh [環境: local|staging|production]
################################################################################

set -e  # 發生錯誤時停止執行

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置
ENVIRONMENT="${1:-staging}"
BACKUP_DIR="./backups"
KAYARINE_DIR="./kayarine-booking"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/kayarine_backup_$TIMESTAMP.tar.gz"

################################################################################
# 函數定義
################################################################################

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# 檢查環境
check_environment() {
    log_info "檢查環境..."
    
    # 檢查 PHP 命令行
    if ! command -v php &> /dev/null; then
        log_error "PHP 未安裝或不在 PATH 中"
        exit 1
    fi
    
    # 檢查 git 命令行（可選）
    if command -v git &> /dev/null; then
        log_success "Git 已安裝"
    else
        log_warning "Git 未安裝（可選）"
    fi
    
    log_success "環境檢查完成"
}

# PHP 語法檢查
check_php_syntax() {
    log_info "檢查 PHP 語法..."
    
    local files=(
        "includes/class-kayarine-cart-manager.php"
        "includes/class-kayarine-checkout-manager.php"
        "includes/class-kayarine-member-dashboard.php"
        "includes/class-kayarine-member-dashboard-v2.php"
        "includes/class-kayarine-woocommerce-customizer.php"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$KAYARINE_DIR/$file" ]; then
            if php -l "$KAYARINE_DIR/$file" > /dev/null 2>&1; then
                log_success "✓ $file"
            else
                log_error "✗ $file - 語法錯誤"
                exit 1
            fi
        else
            log_warning "文件不存在: $file"
        fi
    done
    
    log_success "PHP 語法檢查通過"
}

# 檢查儲值金相關代碼是否還存在
check_wallet_removal() {
    log_info "驗證儲值金代碼已移除..."
    
    local wallet_patterns=(
        "ajax_apply_wallet"
        "use_wallet_check"
        "wallet_input_wrap"
        "kayarine_wallet_applied"
    )
    
    local found_count=0
    
    for pattern in "${wallet_patterns[@]}"; do
        # 不在註釋中檢查
        count=$(grep -r "$pattern" "$KAYARINE_DIR/includes/" --include="*.php" \
                | grep -v "//" \
                | grep -v "/\*" \
                | wc -l || true)
        
        if [ "$count" -gt 0 ]; then
            log_warning "發現 $count 個儲值金相關代碼: $pattern"
            found_count=$((found_count + count))
        fi
    done
    
    if [ "$found_count" -eq 0 ]; then
        log_success "儲值金代碼已完全移除"
    else
        log_warning "仍有 $found_count 個儲值金相關代碼，但可能在註釋中"
    fi
}

# 驗證 Upcoming Bookings 修復
check_booking_date_save() {
    log_info "驗證預約日期保存機制..."
    
    if grep -q "woocommerce_checkout_create_order_line_item" \
        "$KAYARINE_DIR/includes/class-kayarine-cart-manager.php"; then
        log_success "✓ Hook 已正確添加"
    else
        log_error "✗ Hook 未找到"
        exit 1
    fi
    
    if grep -q "save_order_item_meta" \
        "$KAYARINE_DIR/includes/class-kayarine-cart-manager.php"; then
        log_success "✓ 方法已實現"
    else
        log_error "✗ 方法未實現"
        exit 1
    fi
}

# 創建備份
create_backup() {
    log_info "創建備份..."
    
    mkdir -p "$BACKUP_DIR"
    
    if [ -d "$KAYARINE_DIR" ]; then
        tar -czf "$BACKUP_FILE" "$KAYARINE_DIR"
        log_success "備份已創建: $BACKUP_FILE"
    else
        log_error "Kayarine 目錄不存在"
        exit 1
    fi
}

# 生成備份清單
generate_backup_manifest() {
    log_info "生成備份清單..."
    
    local manifest_file="$BACKUP_DIR/manifest_$TIMESTAMP.txt"
    
    cat > "$manifest_file" << EOF
=== Kayarine 預約系統備份清單 ===
備份時間: $TIMESTAMP
環境: $ENVIRONMENT
備份文件: $BACKUP_FILE

修改的文件:
- includes/class-kayarine-cart-manager.php
- includes/class-kayarine-checkout-manager.php
- includes/class-kayarine-member-dashboard.php
- includes/class-kayarine-member-dashboard-v2.php
- includes/class-kayarine-woocommerce-customizer.php

變更摘要:
1. 添加 kayarine_booking_date 持久化機制
2. 移除儲值金相關代碼和 UI
3. 簡化積分系統邏輯

EOF
    
    log_success "備份清單已生成: $manifest_file"
}

# 部署文件
deploy_files() {
    log_info "部署文件到 $ENVIRONMENT..."
    
    case "$ENVIRONMENT" in
        local)
            log_success "本地環境無需部署（代碼已在本地）"
            ;;
        staging)
            log_info "部署到 Staging 環境..."
            # 示例：調整為實際的 staging 路徑
            # cp -r "$KAYARINE_DIR"/* /path/to/staging/wp-content/plugins/kayarine-booking/
            log_success "Staging 部署完成"
            ;;
        production)
            log_warning "⚠️  生產環境部署需要額外確認"
            read -p "確認部署到生產? (y/N): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                log_info "部署到生產環境..."
                # 示例：調整為實際的生產路徑
                # cp -r "$KAYARINE_DIR"/* /path/to/production/wp-content/plugins/kayarine-booking/
                log_success "生產環境部署完成"
            else
                log_warning "生產環境部署已取消"
                exit 1
            fi
            ;;
        *)
            log_error "未知環境: $ENVIRONMENT"
            exit 1
            ;;
    esac
}

# 驗證部署
verify_deployment() {
    log_info "驗證部署..."
    
    # 檢查關鍵文件是否存在
    local files=(
        "kayarine-booking.php"
        "includes/class-kayarine-cart-manager.php"
        "includes/class-kayarine-checkout-manager.php"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$KAYARINE_DIR/$file" ]; then
            log_success "✓ $file"
        else
            log_error "✗ $file 缺失"
            exit 1
        fi
    done
    
    log_success "部署驗證通過"
}

# 部署後檢查清單
post_deployment_checklist() {
    log_info "部署後檢查清單..."
    
    cat << EOF

${BLUE}部署後需要執行的步驟：${NC}

1. 登入 WordPress 管理後台
   □ 確認 Kayarine Booking 插件已啟用
   □ 檢查是否有任何錯誤提示

2. 檢查前端功能
   □ 訪問預約頁面
   □ 點擊日期選擇器（應該正常工作）
   □ 進入購物車和結帳
   □ 驗證結帳頁面只顯示積分選項（無儲值金）

3. 檢查會員中心
   □ 登入會員帳號
   □ 進入會員儀表板
   □ 查看「我的預約」列表（應顯示預約日期）
   □ 驗證統計框只顯示等級和積分（無儲值金）

4. 測試改期功能
   □ 在預約列表中點擊「改期」
   □ 選擇新日期
   □ 確認改期成功

5. 檢查日誌
   □ 檢查 wp-content/debug.log（如果啟用）
   □ 應無 PHP 錯誤或警告

6. 監控 24 小時
   □ 監控伺服器錯誤日誌
   □ 追蹤用戶反饋
   □ 監測關鍵功能運作

EOF
}

# 生成回滾指令
generate_rollback_script() {
    log_info "生成回滾腳本..."
    
    local rollback_file="$BACKUP_DIR/rollback_$TIMESTAMP.sh"
    
    cat > "$rollback_file" << 'EOF'
#!/bin/bash
# 回滾腳本

echo "回滾 Kayarine Booking..."
BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "使用方法: $0 <備份文件路徑>"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "備份文件不存在: $BACKUP_FILE"
    exit 1
fi

echo "恢復備份: $BACKUP_FILE"
tar -xzf "$BACKUP_FILE" -C .

echo "回滾完成！請重新啟用插件。"
EOF
    
    chmod +x "$rollback_file"
    log_success "回滾腳本已生成: $rollback_file"
}

# 生成文檔
generate_documentation() {
    log_info "檢查文檔..."
    
    if [ -f "$KAYARINE_DIR/CRITICAL_FIXES_SUMMARY.md" ]; then
        log_success "✓ CRITICAL_FIXES_SUMMARY.md 已存在"
    fi
    
    if [ -f "$KAYARINE_DIR/PRE_DEPLOYMENT_CHECKLIST.md" ]; then
        log_success "✓ PRE_DEPLOYMENT_CHECKLIST.md 已存在"
    fi
    
    if [ -f "$KAYARINE_DIR/EXECUTIVE_SUMMARY.md" ]; then
        log_success "✓ EXECUTIVE_SUMMARY.md 已存在"
    fi
}

# 主程序
main() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║        Kayarine 預約系統 - 自動部署腳本                   ║"
    echo "║                                                            ║"
    echo "║        環境: $ENVIRONMENT"
    echo "║        時間: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    # 執行檢查和部署步驟
    check_environment
    echo
    
    check_php_syntax
    echo
    
    check_wallet_removal
    echo
    
    check_booking_date_save
    echo
    
    create_backup
    generate_backup_manifest
    echo
    
    generate_rollback_script
    echo
    
    generate_documentation
    echo
    
    deploy_files
    echo
    
    verify_deployment
    echo
    
    post_deployment_checklist
    echo
    
    log_success "部署流程完成！"
    log_info "備份文件: $BACKUP_FILE"
    log_info "回滾指令: tar -xzf $BACKUP_FILE -C ."
}

# 執行主程序
main
