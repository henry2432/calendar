# Kayarine 預約系統 - 10 個關鍵問題修復總結

日期：2026-01-28
版本：1.4.14+

## 修復概述

本文檔列出了 Kayarine 預約系統的 10 個關鍵問題及其修復方案。

---

## 問題 1: 定價系統 Metadata Key 不匹配

### 問題描述
- **症狀**：購物車中的項目價格不更新
- **根本原因**：`class-kayarine-pricing.php` 使用錯誤的 metadata key `booking_date` 而非 `kayarine_booking_date`
- **影響**：動態定價邏輯無法正確應用

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-pricing.php` (Line 30)

```php
// 修改前
if ( isset( $cart_item['booking_date'] ) ) {

// 修改後
if ( isset( $cart_item['kayarine_booking_date'] ) ) {
```

---

## 問題 2: 訂單狀態同步 - 新訂單未在會員中心顯示

### 問題描述
- **症狀**：新建訂單在轉為 `processing` 狀態前不在會員中心可見
- **根本原因**：缺少會員中心類別，且未查詢所有訂單狀態
- **影響**：會員無法看到未處理的訂單

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-member-dashboard.php` (NEW)

新建完整的會員中心類別，包含：
- ✅ 查詢所有訂單狀態（pending, processing, completed, cancelled）
- ✅ 顯示訂單編號、日期、項目數量
- ✅ 實時顯示會員積分和儲值金
- ✅ 會員等級信息

**修改**：`class-kayarine-booking.php` (Line 47)
```php
new Kayarine_Member_Dashboard(); // 初始化會員中心
```

---

## 問題 3: 購物車數量同步 - 結帳頁不更新

### 問題描述
- **症狀**：更改購物車數量後，結帳頁總額未更新
- **根本原因**：缺少訂單狀態變更的 hooks，導致快取未清除

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-booking.php` (Lines 52-60)

添加了多個狀態變更 hooks：
```php
add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'on_order_status_change' ), 10, 1 );
add_action( 'woocommerce_order_status_pending_to_completed', array( $this, 'on_order_status_change' ), 10, 1 );
add_action( 'woocommerce_order_status_pending_to_on_hold', array( $this, 'on_order_status_change' ), 10, 1 );
add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'on_order_status_change' ), 10, 1 );
add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'on_order_status_change' ), 10, 1 );
```

新增方法 `on_order_status_change()` 來統一處理庫存快取清除。

---

## 問題 4: 積分系統 - 結帳頁無法使用積分

### 問題描述
- **症狀**：結帳時積分折抵無效
- **根本原因**：缺少完整的積分應用和驗證邏輯

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-checkout-manager.php`

#### 4.1 強化積分申請驗證 (Lines 135-170)
```php
// 添加 Nonce 驗證（防止 CSRF 攻擊）
if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'kayarine_checkout_nonce' ) ) {
    wp_send_json_error( array( 'message' => '安全驗證失敗' ) );
}

// 多層驗證：
// 1. 確保金額非負數
// 2. 確保不超過用戶餘額
// 3. 確保不超過購物車總額
// 4. 確保金額為整數
```

#### 4.2 添加 Nonce 到結帳 UI (Lines 45-130)
```php
$nonce = wp_create_nonce( 'kayarine_checkout_nonce' );
// 在 JavaScript 中傳送 nonce 到 AJAX
```

---

## 問題 5: 積分系統 - 取消訂單未退還積分

### 問題描述
- **症狀**：訂單取消後，已使用的積分未返還給會員
- **根本原因**：缺少退款邏輯和訂單取消 hooks

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-checkout-manager.php` (Lines 18-20, 227-275)

#### 5.1 添加取消和退款 hooks (Lines 18-20)
```php
add_action( 'woocommerce_order_status_cancelled', array( $this, 'refund_loyalty_balance' ) );
add_action( 'woocommerce_order_status_refunded', array( $this, 'refund_loyalty_balance' ) );
```

#### 5.2 實現退款邏輯 (Lines 227-275)
```php
public function refund_loyalty_balance( $order_id ) {
    // 1. 檢查訂單是否已處理過
    // 2. 查找訂單中使用的積分金額
    // 3. 將積分退還給會員
    // 4. 記錄日誌
}
```

#### 5.3 在會員中心添加取消功能 (`class-kayarine-member-dashboard.php`, Lines 386-424)
```php
public function ajax_cancel_booking() {
    // 驗證權限
    // 更新訂單狀態
    // 計算並退還積分
    // 清除庫存快取
}
```

---

## 問題 6: 改期庫存驗證

### 問題描述
- **症狀**：允許改期到 blocking date 或庫存不足的日期
- **根本原因**：缺少改期驗證邏輯

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-member-dashboard.php` (Lines 429-493)

#### 6.1 改期驗證流程
```php
public function ajax_reschedule_booking() {
    // 步驟 1: 驗證用戶權限
    // 步驟 2: 檢查目標日期是否被 blackout
    if ( Kayarine_Inventory::is_blackout( $new_date ) ) {
        return error: '日期不可預訂'
    }
    
    // 步驟 3: 驗證所有項目的庫存
    $limits = Kayarine_Inventory::get_limits();
    $usage = Kayarine_Inventory::get_daily_usage( $new_date );
    
    // 步驟 4: 檢查 (已用 + 訂單數量) <= 限制
    if ( ( $used + $qty ) > $limit ) {
        return error: '庫存不足'
    }
    
    // 步驟 5: 更新訂單日期
    // 步驟 6: 清除新舊日期的快取
}
```

#### 6.2 UI 實現
- 會員中心中添加了「改期」按鈕
- Date picker 模態框用於選擇新日期
- 即時驗證錯誤提示

---

## 問題 7: 會員中心訂單顯示優化

### 問題描述
- **症狀**：訂單列表缺少關鍵信息
- **根本原因**：缺少完整的會員中心實現

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-member-dashboard.php`

#### 7.1 顯示的信息
- ✅ 訂單編號 (Order ID)
- ✅ 預約日期 (Booking Date)
- ✅ 項目摘要及數量 (Items with quantity)
- ✅ 訂單狀態 (Status with color coding)
- ✅ 訂單金額 (Total amount)
- ✅ 會員積分餘額 (Points balance)
- ✅ 儲值金 (Wallet balance)
- ✅ 會員等級 (Membership tier)

#### 7.2 訂單狀態顏色編碼
- Pending (待確認)：橙色 #f39c12
- Processing (處理中)：藍色 #3498db
- Completed (已完成)：綠色 #27ae60
- Cancelled (已取消)：紅色 #e74c3c

#### 7.3 行動按鈕
- 「查看詳情」：進入完整訂單頁面
- 「改期」：允許改期（pending/processing/on-hold）
- 「取消」：取消訂單

---

## 問題 8: 積分系統安全 - 防止漏洞

### 問題描述
- **症狀**：積分系統容易被濫用
- **根本原因**：缺少完整的驗證和邊界檢查

### 修復
**檔案**：`kayarine-booking/includes/class-kayarine-membership.php` (Lines 187-232)

#### 8.1 調整積分驗證
```php
public function adjust_points( $user_id, $amount, $type, $ref = '', $desc = '' ) {
    // 驗證 1: 用戶 ID 有效
    if ( ! $user_id || $user_id <= 0 ) return false;
    
    // 驗證 2: 操作類型有效
    $allowed_types = array( 'earn', 'redeem', 'refund', 'adjust' );
    if ( ! in_array( $type, $allowed_types ) ) return false;
    
    // 驗證 3: 防止負餘額
    if ( $new_balance < 0 ) $new_balance = 0;
    
    // 驗證 4: 防止整數溢出
    $max_balance = 999999.99;
    if ( $new_balance > $max_balance ) $new_balance = $max_balance;
    
    // 驗證 5: 安全日誌記錄
    // 詳細的操作日誌用於審計
}
```

#### 8.2 應用積分驗證加強 (Lines 170-208)
```php
public function apply_discounts( $cart ) {
    // 檢查 1: 防止超過用戶餘額
    if ( $points > $max_points ) {
        $points = $max_points;
        WC()->session->set( 'kayarine_points_applied', $max_points );
    }
    
    // 檢查 2: 防止負數
    if ( $points < 0 ) {
        $points = 0;
        WC()->session->set( 'kayarine_points_applied', 0 );
    }
    
    // 檢查 3: 防止超過購物車金額
    if ( $points > $cart_total ) {
        $points = intval( $cart_total );
        WC()->session->set( 'kayarine_points_applied', $points );
    }
    
    // 檢查 4: 防止重複費用
    // 確保只添加一次費用，不會重複
    $has_points_fee = false;
    foreach ( $cart->get_fees() as $fee ) {
        if ( $fee->get_name() == '會員積分折抵' ) {
            $has_points_fee = true;
            break;
        }
    }
}
```

#### 8.3 AJAX 積分申請驗證 (Lines 135-170)
```php
public function ajax_apply_points() {
    // 驗證 1: CSRF Nonce 檢查
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'kayarine_checkout_nonce' ) ) {
        return error;
    }
    
    // 驗證 2: 用戶登入檢查
    if ( ! is_user_logged_in() ) {
        return error;
    }
    
    // 驗證 3: 金額範圍檢查
    // 驗證 4: 餘額檢查
    // 驗證 5: 購物車檢查
    // 驗證 6: 整數驗證
}
```

---

## 問題 9-10: 其他相關修復

### 問題 9: 庫存快取管理
**檔案**：`kayarine-booking/includes/class-kayarine-booking.php`

- ✅ 訂單創建時清除快取
- ✅ 訂單狀態變更時清除快取
- ✅ 多個狀態變更 hooks 確保全覆蓋

### 問題 10: 購物車驗證優化
**檔案**：`kayarine-booking/includes/class-kayarine-cart-manager.php`

- ✅ 已實現的批量庫存驗證
- ✅ 購物車變更時的實時檢查
- ✅ 防止「殭屍庫存」（zombie stock）

---

## 安全增強總結

| 安全功能 | 實現位置 | 說明 |
|---------|---------|------|
| CSRF Nonce 驗證 | `class-kayarine-checkout-manager.php` | 防止跨網站請求偽造 |
| 用戶權限檢查 | `class-kayarine-member-dashboard.php` | 確保只能操作自己的訂單 |
| 邊界檢查 | `class-kayarine-membership.php` | 防止負餘額、整數溢出 |
| 庫存驗證 | `class-kayarine-member-dashboard.php` | 改期時驗證庫存可用性 |
| Blackout 檢查 | `class-kayarine-inventory.php` | 防止改期到禁止日期 |
| 類型驗證 | `class-kayarine-membership.php` | 驗證操作類型有效性 |
| 日誌記錄 | 所有修改 | 詳細的操作審計 |

---

## 測試檢查清單

在部署前，請測試以下場景：

### 積分系統
- [ ] 購物車中正確顯示積分折抵
- [ ] 積分不超過用戶餘額
- [ ] 積分不超過購物車金額
- [ ] 訂單完成後積分被正確扣除
- [ ] 訂單取消後積分被正確退還
- [ ] Nonce 驗證有效

### 改期系統
- [ ] 無法改期到 blackout 日期
- [ ] 無法改期到庫存不足的日期
- [ ] 成功改期後庫存正確更新
- [ ] 改期後舊日期的快取被清除
- [ ] 改期後新日期的快取被清除

### 會員中心
- [ ] 所有訂單狀態都可見（pending, processing, completed, cancelled）
- [ ] 訂單編號、日期、項目信息正確顯示
- [ ] 積分和儲值金正確顯示
- [ ] 改期和取消按鈕只在允許的狀態出現
- [ ] 移動設備上的響應式設計正確

### 庫存管理
- [ ] 訂單創建時庫存被正確標記
- [ ] 訂單狀態變更時快取被正確清除
- [ ] 新訂單不會導致超賣

---

## 更新說明

### 更新文件
1. `class-kayarine-pricing.php` - 修復 metadata key
2. `class-kayarine-booking.php` - 添加狀態變更 hooks
3. `class-kayarine-checkout-manager.php` - 強化積分驗證
4. `class-kayarine-membership.php` - 安全邊界檢查
5. `class-kayarine-member-dashboard.php` - 新建會員中心類別

### 新增文件
- `class-kayarine-member-dashboard.php` - 完整的會員中心實現

### 向後兼容性
✅ 所有修復都與現有系統向後兼容，無需數據遷移

### 推薦部署順序
1. 更新 `class-kayarine-pricing.php`
2. 更新 `class-kayarine-booking.php`
3. 更新 `class-kayarine-checkout-manager.php`
4. 更新 `class-kayarine-membership.php`
5. 新增 `class-kayarine-member-dashboard.php`
6. 清除所有快取（WP Cache、Transients）
7. 測試各項功能

---

## 支援和監控

### 啟用調試模式
在 WP 後台 Kayarine Booking 設置中啟用「Debug Mode」來查看詳細日誌。

日誌位置：`wp-content/kayarine-debug.log`

### 監控指標
- 積分調整操作
- 訂單狀態變更
- 庫存變更
- 改期操作
- 取消操作

---

## 版本更新
- **v1.4.14** - 修復 10 個關鍵問題
  - 定價系統 metadata fix
  - 訂單狀態同步
  - 購物車數量同步
  - 積分系統完整實現
  - 改期庫存驗證
  - 會員中心優化
  - 安全性加強

---

*本文檔由系統自動生成。如有問題，請聯繫技術支援。*
