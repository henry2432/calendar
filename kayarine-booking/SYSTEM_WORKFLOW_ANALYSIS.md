# Kayarine Booking 系統 Workflow 完整分析

## 1️⃣ 會員中心訂單顯示 Workflow

### 1.1 數據查詢流程
**文件**：`class-kayarine-member-dashboard.php` - 第37-42行

```php
$orders = wc_get_orders( array(
    'customer' => $user_id,      // ✅ 已修復：customer 而非 customer_id
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );
```

**問題 1: 沒有明確指定狀態過濾**
- 不指定 `status` 參數可能返回所有狀態（包括 draft, pending 等）
- 但應該只顯示有效的訂單狀態

**應該修改為**：
```php
$orders = wc_get_orders( array(
    'customer' => $user_id,
    'status'   => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded' ),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );
```

### 1.2 on-hold 狀態標籤轉換
**文件**：`class-kayarine-member-dashboard.php` - 第473-477行

```php
$status_label = wc_get_order_status_name( $status );
if ( $status === 'on-hold' ) {
    $status_label = '未確認 (待支付)';
}
```

✅ 代碼正確，on-hold 會顯示為「未確認 (待支付)」

### 1.3 改期和取消條件判斷
**文件**：`class-kayarine-member-dashboard.php` - 第482-483行

```php
$can_cancel = in_array( $status, array( 'pending', 'processing', 'on-hold' ) );
$can_reschedule = $can_cancel && ! empty( $booking_dates );
```

✅ 邏輯正確

### 1.4 問題診斷
現象：processing 訂單轉換後不顯示

**可能原因**：
1. 訂單在轉換為 processing 時，可能被其他 hook 刪除或移動
2. 訂單可能存在於舊的表或不同的用戶 ID
3. 庫存系統可能改變了訂單的創建或儲存方式

**診斷步驟**：
```sql
-- 檢查特定用戶的所有訂單
SELECT ID, post_status, post_date FROM wp_posts 
WHERE post_author = {user_id} AND post_type = 'shop_order'
ORDER BY post_date DESC;

-- 檢查訂單元數據
SELECT post_id, meta_key, meta_value FROM wp_postmeta 
WHERE post_id = {order_id} AND meta_key LIKE '_kayarine%';
```

---

## 2️⃣ 改期和取消 Workflow

### 2.1 改期流程 - AJAX Handler
**文件**：`class-kayarine-member-dashboard.php` - 第573-654行

**流程**：
1. 前端：用戶點擊「改期」按鈕 → 顯示日期選擇器
2. 確認新日期 → 調用 `kayarine_reschedule_booking` AJAX
3. 後端驗證：
   - 檢查訂單是否存在
   - 檢查用戶權限
   - 驗證新日期不是黑名單日期
   - 檢查新日期是否有足夠庫存
4. 更新：
   - 先收集舊日期 → 更新所有 item 的 `_kayarine_booking_date`
   - 清除舊日期和新日期的快取

### 2.2 取消流程 - AJAX Handler
**文件**：`class-kayarine-member-dashboard.php` - 第522-568行

**流程**：
1. 前端：用戶點擊「取消」→ 確認對話框
2. 調用 `kayarine_cancel_booking` AJAX
3. 後端流程：
   - 檢查訂單 + 用戶權限
   - 訂單狀態改為 `cancelled`
   - **檢查是否有積分折抵費用**
   - 如果有積分用掉，調用 `adjust_points(..., 'refund', ...)`
   - 清除相關日期的庫存快取

✅ 取消時有退還積分邏輯

### 2.3 問題檢查
**取消時的積分退還邏輯** (第547-557行)：
```php
$points_used = 0;
foreach ( $order->get_fees() as $fee ) {
    if ( $fee->get_name() == '會員積分折抵' ) {
        $points_used += abs( $fee->get_total() );
    }
}

if ( $points_used > 0 ) {
    $membership->adjust_points( get_current_user_id(), $points_used, 'refund', ... );
}
```

✅ 邏輯正確

---

## 3️⃣ 積分使用及更新 Workflow - 完整流程圖

### 3.1 結帳頁面 - 積分展示和應用

#### 步驟 1: 頁面加載 (`woocommerce_review_order_before_payment`)
**文件**：`class-kayarine-checkout-manager.php` - 第59-191行

```php
// 1. 獲取用戶積分 (第66行)
$points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );

// 2. 計算可用積分 (第74行)
$auto_points = min( $points, $cart_total );

// 3. HTML 顯示複選框 (第87行)
<input type="checkbox" id="use_points_check" checked>
自動使用積分折抵 (現有: <?php echo $points; ?> 分)
```

**JavaScript 初始化** (第120-124行)：
```javascript
if ($checkbox.is(':checked') && auto_points > 0) {
    trigger_ajax('points', auto_points);
}
```

❌ **問題**：用戶說「一開始的 default 使用時無法正確應用」
- 初始化代碼應該是正確的
- 但可能 `auto_points` 值在 PHP 中計算錯誤
- 或者 AJAX 本身失敗

#### 步驟 2: AJAX - 應用積分 (`wp_ajax_kayarine_apply_points`)
**文件**：`class-kayarine-checkout-manager.php` - 第215-270行

```php
// 1. 驗證 nonce
if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'kayarine_checkout_nonce' ) ) {
    return error; // ❌ 可能失敗點
}

// 2. 從 DB 讀取用戶真實積分（防止 session 作弊）
$max_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );

// 3. 驗證積分額度（4 個檢查）
if ( $amount < 0 ) return error;
if ( $amount > $max_points ) return error;
if ( $amount > $cart_total ) $amount = $cart_total;
if ( $amount != intval($amount) ) return error;

// 4. 設置 Session
WC()->session->set( 'kayarine_points_applied', $amount );
```

❌ **已知問題點**：
- Nonce 驗證可能失敗（session 超時、跨域等）
- Session 可能沒有正確持久化

#### 步驟 3: 費用計算 (`woocommerce_cart_calculate_fees`)
**文件**：`class-kayarine-checkout-manager.php` - 第276-323行

```php
$points = WC()->session->get( 'kayarine_points_applied', 0 );

if ( $points > 0 ) {
    $discount = min( $points, $cart_total );
    $cart->add_fee( '會員積分折抵', -$discount );  // ✅ 負值 = 折扣
}
```

✅ 邏輯正確

### 3.2 訂單提交時

#### 步驟 4: 訂單建立時 (`woocommerce_checkout_order_processed`)
**文件**：`class-kayarine-checkout-manager.php` - 第399-420行

```php
public function process_checkout_points( $order_id, $posted_data, $order ) {
    $points_applied = WC()->session->get( 'kayarine_points_applied', 0 );
    
    if ( $points_applied > 0 ) {
        // 標記訂單：需要扣除 $points_applied 積分
        $order->update_meta_data( '_kayarine_points_to_deduct', $points_applied );
        $order->save();
    }
}
```

✅ 邏輯正確（只是標記，不扣除）

#### 步驟 5: 訂單進入 Processing/Completed 時 - **積分扣除**
**Hook**：`woocommerce_order_status_processing` 和 `woocommerce_order_status_completed`
**文件**：`class-kayarine-checkout-manager.php` - 第328-358行

```php
public function deduct_loyalty_balance( $order_id ) {
    $order = wc_get_order( $order_id );
    
    // ❌ 問題：檢查是否已扣除
    if ( $order->get_meta( '_kayarine_loyalty_deducted' ) ) return;
    
    $user_id = $order->get_user_id();
    
    // 從訂單費用中找出積分折抵金額
    $points_used = 0;
    foreach ( $order->get_fees() as $fee ) {
        if ( $fee->get_name() == '會員積分折抵' ) {
            $points_used += abs( $fee->get_total() );
        }
    }
    
    // ✅ 扣除積分
    if ( $points_used > 0 ) {
        $membership = new Kayarine_Membership();
        $membership->adjust_points( $user_id, -$points_used, 'redeem', $order_id, "訂單 #{$order_id} 折抵" );
        $order->update_meta_data( '_kayarine_loyalty_deducted', 1 );
    }
}
```

❌ **實際問題**：
1. 該函數依賴訂單進入 `processing` 或 `completed` 狀態
2. 如果訂單停留在 `on-hold` 狀態，積分永遠不會被扣除
3. 庫存系統可能改變了訂單狀態流轉

### 3.3 訂單完成時 - 積分回饋

#### 步驟 6: 訂單完成時獲得回饋積分
**Hook**：`woocommerce_order_status_completed`
**文件**：`class-kayarine-membership.php` - 第136-182行

```php
public function process_order_rewards( $order_id ) {
    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();
    
    // ❌ 問題：檢查是否已獲得
    if ( $order->get_meta( '_kayarine_points_awarded' ) ) return;
    
    // 取得用戶等級和回饋率
    $current_tier = self::get_tier( $user_id );
    $rate = self::get_tier_info( $current_tier )['rate'];  // bronze=0.01, silver=0.02, gold=0.03
    
    // 計算回饋積分
    $paid_amount = $order->get_total();
    $points_earned = floor( $paid_amount * $rate );
    
    // ✅ 新增積分
    if ( $points_earned > 0 ) {
        $membership->adjust_points( $user_id, $points_earned, 'earn', $order_id, "訂單 #{$order_id} 回饋" );
    }
}
```

❌ **實際問題**：
1. 訂單必須進入 `completed` 狀態才會觸發
2. 用戶說「確認新訂單後，積分也沒有新增」
3. 說明訂單可能沒有進入 completed 狀態

---

## 4️⃣ 根本問題分析

### 問題 1: 會員中心訂單不顯示

**原因分析**：
- 庫存系統可能在訂單建立後有不同的狀態流程
- 訂單可能被建立但沒有被正確保存到 `wp_posts`
- 用戶 ID 綁定可能有問題

### 問題 2: 一開始的 default 積分無法應用

**根本原因**：
1. AJAX Nonce 驗證失敗 → 積分無法應用到 session
2. Session 沒有持久化
3. JavaScript 初始化時機問題（可能在 checkout 更新前執行）

### 問題 3: 積分沒有被扣除

**根本原因**：
1. 訂單沒有進入 `processing` 狀態
   - 庫存系統可能改變了狀態流程
   - 訂單可能停留在 `on-hold`
2. 即使進入 processing，也需要 payment 完成
3. `deduct_loyalty_balance()` 沒有被觸發

### 問題 4: 新訂單完成後沒有獲得回饋積分

**根本原因**：
1. 訂單沒有進入 `completed` 狀態
2. `process_order_rewards()` hook 沒有觸發

---

## 5️⃣ 修復策略

### 策略 1: 確保正確的訂單狀態流轉

需要查明庫存系統如何改變了訂單狀態流程。

### 策略 2: 改進 AJAX 初始化

- 確保 Nonce 在前端和後端一致
- 添加重試機制
- 改進錯誤日誌

### 策略 3: 改進會員中心訂單查詢

- 明確指定訂單狀態
- 添加詳細日誌

### 策略 4: 重構積分系統

- 不依賴 session，改用訂單元數據
- 明確所有觸發點
- 確保不重複計算

