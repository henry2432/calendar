# Kayarine 系統診斷指南

## 問題症狀整理

| 症狀 | 說明 | 涉及模組 |
|------|------|--------|
| 會員中心看不到訂單 | 包括 pending, processing, on-hold 等所有狀態 | 會員中心 |
| processing 訂單不顯示 | 手動轉換訂單狀態後仍不顯示 | 會員中心 + 庫存系統 |
| Default 積分無法應用 | 一開始自動使用積分不生效，需要取消後重新勾選 | 結帳系統 |
| 積分未被扣除 | 使用積分結帳後，會員中心積分餘額沒有減少 | 積分系統 + 訂單狀態 |
| 新訂單未獲得回饋 | 訂單完成後沒有獲得積分回饋 | 積分系統 + 訂單狀態 |

---

## 診斷步驟 1: 驗證訂單是否真的存在

### 1.1 使用 WP-CLI 檢查訂單

```bash
# SSH 到 GCP 服務器
gcloud compute ssh kayarine_server_gmail_com@wordpress-2025-vm \
    --zone=asia-east1-b

# 進入 WordPress 目錄
cd /opt/bitnami/wordpress

# 使用 wp-cli 查詢特定用戶的訂單
wp post list --post_type=shop_order --posts_per_page=100 --format=table

# 查詢特定用戶的訂單
wp post list --post_type=shop_order --meta_key=_customer_user --meta_value=<USER_ID> --format=table

# 查詢特定訂單的詳細信息（用訂單 ID）
wp post get <ORDER_ID> --format=json
```

### 1.2 直接查詢數據庫

```sql
-- 連接 WordPress 數據庫
mysql -u bitnami -p kayarinetemp

-- 查詢所有訂單及其狀態
SELECT ID, post_status, post_date, post_author 
FROM wp_posts 
WHERE post_type = 'shop_order' 
ORDER BY post_date DESC 
LIMIT 20;

-- 查詢特定用戶的訂單
SELECT ID, post_status, post_date 
FROM wp_posts 
WHERE post_type = 'shop_order' 
  AND post_author = <USER_ID>
ORDER BY post_date DESC;

-- 檢查訂單是否有庫存相關的元數據
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE post_id = <ORDER_ID> 
  AND meta_key LIKE '%kayarine%'
ORDER BY meta_key;
```

---

## 診斷步驟 2: 驗證會員中心查詢邏輯

### 2.1 添加調試日誌

修改 `class-kayarine-member-dashboard.php`：

```php
public function render_dashboard() {
    if ( ! is_user_logged_in() ) {
        return '<div class="woocommerce-info">請登入...</div>';
    }

    $user_id = get_current_user_id();
    
    // 📍 新增：詳細日誌
    error_log( "[Kayarine Dashboard] User ID: $user_id" );
    
    // Get all orders
    $args = array(
        'customer' => $user_id,
        'limit'    => -1,
        'orderby'  => 'date',
        'order'    => 'DESC',
    );
    
    // 📍 新增：日誌查詢參數和結果
    error_log( "[Kayarine Dashboard] Query args: " . json_encode($args) );
    
    $orders = wc_get_orders( $args );
    
    error_log( "[Kayarine Dashboard] Orders found: " . count($orders) );
    foreach ( $orders as $order ) {
        error_log( "[Kayarine Dashboard] Order ID: " . $order->get_id() . 
                   ", Status: " . $order->get_status() . 
                   ", User: " . $order->get_user_id() );
    }
    
    // 繼續原有邏輯...
}
```

### 2.2 查看 WordPress 調試日誌

```bash
# SSH 到服務器
gcloud compute ssh kayarine_server_gmail_com@wordpress-2025-vm \
    --zone=asia-east1-b

# 查看最後 100 行日誌
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine Dashboard"

# 實時監控日誌
tail -f /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine"
```

---

## 診斷步驟 3: 驗證積分系統 Hooks

### 3.1 添加 Hook 觸發日誌

修改 `class-kayarine-checkout-manager.php`：

```php
public function __construct() {
    // ... 現有 hooks ...
    
    // 📍 新增：Hook 觸發日誌
    add_action( 'woocommerce_order_status_pending', function($order_id) {
        error_log( "[Kayarine Hooks] woocommerce_order_status_pending: $order_id" );
    });
    
    add_action( 'woocommerce_order_status_on-hold', function($order_id) {
        error_log( "[Kayarine Hooks] woocommerce_order_status_on-hold: $order_id" );
    });
    
    add_action( 'woocommerce_order_status_processing', function($order_id) {
        error_log( "[Kayarine Hooks] woocommerce_order_status_processing: $order_id" );
    });
    
    add_action( 'woocommerce_order_status_completed', function($order_id) {
        error_log( "[Kayarine Hooks] woocommerce_order_status_completed: $order_id" );
    });
}
```

修改 `class-kayarine-membership.php`：

```php
public function process_order_rewards( $order_id ) {
    error_log( "[Kayarine Rewards] process_order_rewards called: $order_id" );
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        error_log( "[Kayarine Rewards] Order not found" );
        return;
    }

    $user_id = $order->get_user_id();
    error_log( "[Kayarine Rewards] User ID: $user_id" );
    
    if ( $order->get_meta( '_kayarine_points_awarded' ) ) {
        error_log( "[Kayarine Rewards] Points already awarded" );
        return;
    }
    
    // ... 繼續邏輯 ...
    
    error_log( "[Kayarine Rewards] Points earned: $points_earned" );
}
```

### 3.2 查看積分 Hooks 日誌

```bash
# 查看積分相關日誌
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine"
```

---

## 診斷步驟 4: 驗證積分應用（結帳時）

### 4.1 添加 Session 和 AJAX 日誌

修改 `class-kayarine-checkout-manager.php`：

```php
public function ajax_apply_points() {
    error_log( "[Kayarine AJAX] ajax_apply_points called" );
    error_log( "[Kayarine AJAX] _POST: " . json_encode($_POST) );
    
    // Nonce 驗證
    if ( ! isset( $_POST['_wpnonce'] ) ) {
        error_log( "[Kayarine AJAX] No nonce found" );
        wp_send_json_error( array( 'message' => '無 nonce' ) );
    }
    
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'kayarine_checkout_nonce' ) ) {
        error_log( "[Kayarine AJAX] Nonce verification failed" );
        wp_send_json_error( array( 'message' => 'Nonce 驗證失敗' ) );
    }
    
    error_log( "[Kayarine AJAX] Nonce verified" );
    
    $user_id = get_current_user_id();
    $amount = isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : 0;
    
    error_log( "[Kayarine AJAX] User: $user_id, Amount requested: $amount" );
    
    // 應用到 session
    WC()->session->set( 'kayarine_points_applied', $amount );
    
    $session_value = WC()->session->get( 'kayarine_points_applied' );
    error_log( "[Kayarine AJAX] Session set. Session value: " . $session_value );
    
    wp_send_json_success( array( 'message' => "已套用 {$amount} 積分" ) );
}
```

### 4.2 檢查費用是否被正確添加

```php
public function apply_discounts( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( ! is_user_logged_in() ) return;

    $points = WC()->session->get( 'kayarine_points_applied', 0 );
    error_log( "[Kayarine Fees] apply_discounts called. Points from session: $points" );
    
    if ( $points > 0 ) {
        $cart_total = $cart->subtotal + $cart->shipping_total;
        $discount = min( $points, $cart_total );
        
        error_log( "[Kayarine Fees] Adding fee. Discount: $discount" );
        
        // 檢查是否已添加
        $has_fee = false;
        foreach ( $cart->get_fees() as $fee ) {
            if ( $fee->get_name() == '會員積分折抵' ) {
                $has_fee = true;
                break;
            }
        }
        
        if ( ! $has_fee ) {
            $cart->add_fee( '會員積分折抵', -$discount );
            error_log( "[Kayarine Fees] Fee added successfully" );
        } else {
            error_log( "[Kayarine Fees] Fee already exists, skipping" );
        }
    } else {
        error_log( "[Kayarine Fees] No points applied, skipping" );
    }
}
```

---

## 診斷步驟 5: 檢查庫存系統的影響

### 5.1 查看庫存系統相關的 Hooks

檢查 `class-kayarine-inventory.php` 是否有改變訂單狀態或元數據的邏輯：

```bash
# 搜尋所有訂單狀態相關的操作
grep -n "set_status\|order_status\|post_status" \
    /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/*.php
```

### 5.2 檢查是否有其他 Plugin 干擾

```bash
# 列出所有啟用的插件
wp plugin list --status=active --format=table
```

---

## 完整的測試流程

### Test 1: 驗證會員中心訂單顯示

1. **建立測試訂單**
   - 以測試用戶身份，購買一個產品
   - 不選擇使用積分
   - 完成支付

2. **檢查訂單是否顯示**
   - 訪問會員中心：`https://kayarine.com.hk/account`
   - 檢查「我的預約」是否顯示該訂單
   - 記錄訂單 ID

3. **檢查數據庫**
   ```sql
   SELECT ID, post_status FROM wp_posts 
   WHERE ID = <ORDER_ID>;
   ```

4. **檢查查詢邏輯**
   - 查看 `debug.log` 中的 `[Kayarine Dashboard]` 日誌
   - 驗證 `wc_get_orders()` 是否返回該訂單

### Test 2: 驗證積分應用

1. **結帳時應用積分**
   - 購物車中添加產品
   - 進入結帳頁面
   - 確認看到「自動使用積分折抵」複選框
   - 確認複選框已勾選
   - 打開瀏覽器控制台 (F12)
   - 監控 Console 輸出，查找 `[Kayarine]` 日誌

2. **檢查 AJAX 請求**
   - F12 → Network 標籤
   - 搜尋 `admin-ajax.php` 請求
   - 驗證是否有 `action=kayarine_apply_points` 請求
   - 檢查 Response 是否為成功

3. **檢查費用是否被添加**
   - 在結帳頁面，查看「訂單小計」下是否有「會員積分折抵」費用行

4. **檢查 WordPress 日誌**
   ```bash
   tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep -E "AJAX|Fees|Session"
   ```

### Test 3: 驗證積分扣除

1. **完成訂單**
   - 在結帳頁面確認使用了積分
   - 完成支付

2. **檢查訂單中的費用**
   - 在 WordPress 後台訂單頁面，查看訂單詳情
   - 確認「會員積分折抵」費用是否被保存到訂單

3. **檢查積分是否被扣除**
   - 進入會員中心，查看「積分餘額」
   - 驗證積分是否減少了

4. **檢查數據庫**
   ```sql
   -- 查詢積分日誌
   SELECT user_id, type, amount, balance_after, description 
   FROM wp_kayarine_points_log 
   WHERE user_id = <USER_ID> 
   ORDER BY date_created DESC;
   ```

5. **檢查 WordPress 日誌**
   ```bash
   tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep -E "Checkout|Points|Deduct"
   ```

### Test 4: 驗證積分回饋

1. **標記訂單為完成**
   - 在 WordPress 後台，將訂單狀態改為「完成」

2. **檢查積分是否增加**
   - 進入會員中心，查看「積分餘額」
   - 驗證是否增加了

3. **檢查 WordPress 日誌**
   ```bash
   tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "Rewards"
   ```

---

## 常見問題排查

### Q: 會員中心顯示「您還沒有預約」

**可能原因**：
1. 訂單確實沒有被建立
2. 訂單被建立但關聯了錯誤的用戶 ID
3. `wc_get_orders()` 查詢參數有誤

**排查**：
```bash
# 檢查是否有 Kayarine Dashboard 日誌
tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "Dashboard"

# 如果沒有日誌，說明頁面加載失敗或未執行
```

### Q: 積分未被扣除

**可能原因**：
1. Nonce 驗證失敗
2. Session 未正確持久化
3. 訂單沒有進入 `processing` 狀態

**排查**：
```bash
# 檢查 AJAX 相關日誌
tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "AJAX"

# 檢查訂單狀態流轉
tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "order_status"

# 檢查費用是否被添加到訂單
mysql -u bitnami -p kayarinetemp
SELECT * FROM wp_woocommerce_order_items 
WHERE order_id = <ORDER_ID> AND order_item_type = 'fee';
```

### Q: 一開始的積分無法應用，但重新勾選後可以

**根本原因**：
1. JavaScript 初始化時 Nonce 驗證失敗
2. 重新勾選時觸發 `change` 事件，重新生成 Nonce

**解決**：
- 在後端改進 Nonce 生成和驗證機制
- 在前端改進 AJAX 錯誤處理

