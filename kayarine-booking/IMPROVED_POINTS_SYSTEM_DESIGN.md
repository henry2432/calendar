# 改進的積分系統設計 - 完全可靠版本

## 核心問題

當前設計的問題：
1. **過度依賴 Session**
   - Session 在不同環境下可能不持久化（GCP、多伺服器等）
   - Session 超時導致積分信息丟失
   - 無法跨頁面/跨請求保持狀態

2. **AJAX Nonce 驗證問題**
   - Nonce 一開始驗證失敗，之後才生效
   - 缺少重試機制和降級方案

3. **Hook 觸發不確定性**
   - `woocommerce_order_status_processing` 可能不觸發
   - `woocommerce_order_status_completed` 可能被其他 Plugin 干擾
   - 沒有備選觸發機制

4. **缺少原子操作**
   - 積分扣除和費用添加之間可能不同步
   - 沒有事務保證

---

## 改進設計原則

### 原則 1: 使用訂單元數據而非 Session
- 所有積分信息都持久化到訂單元數據
- Session 只用於臨時 UI 顯示

### 原則 2: 明確的狀態機制
- 使用訂單元數據明確記錄每個步驟的狀態
- 允許重試和恢復

### 原則 3: 多重觸發點
- 不依賴單一 Hook
- 多個檢查點確保最終一致性

### 原則 4: 清晰的 Workflow
- 每個步驟必須明確可觀測
- 必須有詳細的日誌

---

## 改進的積分系統 Workflow

### 第 1 階段: 結帳頁面 - 用戶選擇積分

**觸發**：`woocommerce_review_order_before_payment`

**當前問題**：
- 複選框預設勾選，但 AJAX 可能失敗
- 用戶看不到失敗狀態

**改進方案**：

```php
// 前端：不依賴 AJAX 初始化，而是在提交時驗證
<form id="kayarine-checkout-form">
    <label>
        <input type="hidden" id="kayarine_points_request" name="kayarine_points_request" value="0">
        <input type="checkbox" id="use_points_check" data-max-points="<?php echo $auto_points; ?>">
        自動使用積分折抵
    </label>
</form>

<script>
jQuery(document).ready(function($) {
    $('#use_points_check').change(function() {
        var points = this.checked ? parseInt($(this).data('max-points')) : 0;
        $('#kayarine_points_request').val(points);
        console.log('[Kayarine] Points requested: ' + points);
    });
    
    // 頁面加載時，如果複選框已勾選，設置值
    if ($('#use_points_check').is(':checked')) {
        $('#use_points_check').trigger('change');
    }
});
</script>
```

**改進**：
- ✅ 不依賴 AJAX 初始化
- ✅ 積分請求值存儲在隱藏欄位中，隨表單提交
- ✅ 不需要複雜的 Nonce 驗證

---

### 第 2 階段: 訂單建立時 - 記錄積分請求

**觸發**：`woocommerce_checkout_create_order_line_item` 或 `woocommerce_checkout_order_processed`

**改進方案**：

```php
/**
 * 在訂單建立時記錄用戶的積分請求
 * Hook: woocommerce_checkout_order_processed
 */
public function record_points_request_on_checkout( $order_id, $posted_data, $order ) {
    // 1. 從表單獲取積分請求值
    $points_requested = isset($_POST['kayarine_points_request']) 
        ? intval($_POST['kayarine_points_request']) 
        : 0;
    
    error_log("[Kayarine Points] Order $order_id created. Points requested: $points_requested");
    
    if ($points_requested <= 0) {
        return;
    }
    
    $user_id = $order->get_user_id();
    
    // 2. 驗證：用戶是否有足夠的積分
    $user_points = (int) get_user_meta($user_id, 'kayarine_points_balance', true);
    
    if ($points_requested > $user_points) {
        error_log("[Kayarine Points] Insufficient points. Requested: $points_requested, Available: $user_points");
        // 這裡可以選擇拒絕或限制積分
        $points_requested = $user_points;
    }
    
    // 3. 驗證：積分不能超過訂單總額
    $order_total = (float)$order->get_total();
    if ($points_requested > $order_total) {
        error_log("[Kayarine Points] Points exceed order total. Requested: $points_requested, Total: $order_total");
        $points_requested = intval($order_total);
    }
    
    // 4. 記錄到訂單元數據
    $order->update_meta_data('_kayarine_points_requested', $points_requested);
    
    // 5. 重要：這裡 NOT 扣除，只是記錄請求
    $order->update_meta_data('_kayarine_points_status', 'requested');  // 狀態：已請求
    
    $order->save();
    
    error_log("[Kayarine Points] Order $order_id recorded points request: $points_requested");
}
```

**狀態機**：
- `requested` → 用戶已請求使用積分
- `pending_deduction` → 等待支付完成
- `deducted` → 已扣除
- `failed` → 失敗（用於恢復）

---

### 第 3 階段: 費用添加 - 確保費用被正確記錄到訂單

**當前問題**：
- 費用添加可能不穩定
- 訂單可能沒有記錄費用

**改進方案**：

不依賴 `woocommerce_cart_calculate_fees`，而是在訂單建立後直接添加訂單項目：

```php
/**
 * 在訂單建立後，根據請求添加費用項目
 * Hook: woocommerce_checkout_order_processed (優先級: 20)
 */
public function add_points_fee_to_order( $order_id, $posted_data, $order ) {
    $points_requested = $order->get_meta('_kayarine_points_requested');
    
    if (!$points_requested || $points_requested <= 0) {
        return;
    }
    
    error_log("[Kayarine Fee] Adding fee to order $order_id for $points_requested points");
    
    // 檢查是否已添加過費用
    $has_fee = false;
    foreach ($order->get_items('fee') as $fee_item) {
        if (strpos($fee_item->get_name(), '會員積分') !== false) {
            $has_fee = true;
            error_log("[Kayarine Fee] Fee already exists, skipping");
            break;
        }
    }
    
    if (!$has_fee) {
        // 添加費用項目（負值 = 折扣）
        $fee = new WC_Order_Item_Fee();
        $fee->set_name('會員積分折抵');
        $fee->set_amount(-$points_requested);  // 負值表示折扣
        $fee->set_tax_class('');
        $fee->set_tax_status('none');
        
        $order->add_item($fee);
        $order->save();
        
        error_log("[Kayarine Fee] Fee added successfully");
    }
}
```

**優勢**：
- ✅ 直接添加到訂單項目，而非依賴 Cart 費用
- ✅ 確保費用被持久化
- ✅ 不受 Cart 更新影響

---

### 第 4 階段: 支付完成 - 積分扣除

**當前問題**：
- 依賴 `woocommerce_order_status_processing` Hook
- 可能不觸發或被其他 Plugin 干擾

**改進方案**：

使用多個觸發點 + 最終驗證：

```php
/**
 * 多重觸發點：支付完成時扣除積分
 */
public function __construct() {
    // 多重 Hook：確保至少一個會被觸發
    add_action('woocommerce_order_status_pending_to_processing', [$this, 'deduct_points_on_payment']);
    add_action('woocommerce_order_status_pending_to_completed', [$this, 'deduct_points_on_payment']);
    add_action('woocommerce_order_status_on-hold_to_processing', [$this, 'deduct_points_on_payment']);
    add_action('woocommerce_order_status_on-hold_to_completed', [$this, 'deduct_points_on_payment']);
    
    // 備選：訂單總價變更時（某些支付方式使用）
    add_action('woocommerce_order_refunded', [$this, 'deduct_points_on_payment']);
    
    // 終極備選：管理員定時檢查未處理的積分
    add_action('wp_scheduled_event_check_pending_points', [$this, 'check_and_deduct_pending_points']);
}

/**
 * 當訂單支付完成時扣除積分
 */
public function deduct_points_on_payment($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // 檢查是否已扣除
    if ($order->get_meta('_kayarine_points_deducted')) {
        error_log("[Kayarine Deduct] Order $order_id already processed");
        return;
    }
    
    $points_requested = (int)$order->get_meta('_kayarine_points_requested');
    if (!$points_requested || $points_requested <= 0) {
        error_log("[Kayarine Deduct] No points requested for order $order_id");
        return;
    }
    
    $user_id = $order->get_user_id();
    
    error_log("[Kayarine Deduct] Processing deduction for order $order_id. User: $user_id, Points: $points_requested");
    
    // 再次驗證用戶積分充足
    $user_points = (int)get_user_meta($user_id, 'kayarine_points_balance', true);
    $actual_deduction = min($points_requested, $user_points);
    
    if ($actual_deduction <= 0) {
        error_log("[Kayarine Deduct] Insufficient points at deduction time");
        $order->update_meta_data('_kayarine_points_status', 'failed_insufficient_points');
        $order->save();
        return;
    }
    
    // 執行扣除
    $membership = new Kayarine_Membership();
    $result = $membership->adjust_points(
        $user_id,
        -$actual_deduction,
        'redeem',
        $order_id,
        "訂單 #{$order_id} - 積分折抵"
    );
    
    if ($result) {
        $order->update_meta_data('_kayarine_points_deducted', $actual_deduction);
        $order->update_meta_data('_kayarine_points_status', 'deducted');
        $order->save();
        
        error_log("[Kayarine Deduct] Successfully deducted $actual_deduction points");
    } else {
        error_log("[Kayarine Deduct] Deduction failed");
        $order->update_meta_data('_kayarine_points_status', 'failed');
        $order->save();
    }
}
```

**狀態值**：
- `requested` → 用戶請求
- `deducted` → 已成功扣除
- `failed_insufficient_points` → 扣除時積分不足
- `failed` → 其他失敗原因

---

### 第 5 階段: 訂單完成 - 積分回饋

**改進方案**：

```php
/**
 * 訂單完成時，獲得回饋積分
 * Hook: woocommerce_order_status_completed
 */
public function add_reward_points_on_completion($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // 檢查是否已獎勵
    if ($order->get_meta('_kayarine_points_awarded')) {
        error_log("[Kayarine Reward] Order $order_id already awarded");
        return;
    }
    
    $user_id = $order->get_user_id();
    if (!$user_id) return;
    
    // 只計算 "淨額"（收取金額 - 積分折扣）
    $order_total = (float)$order->get_total();
    $points_used = (int)$order->get_meta('_kayarine_points_deducted');
    
    // 回饋基礎 = 收取金額（包括扣除的積分視為「收入」）
    $earning_base = $order_total + $points_used;
    
    // 獲得等級和回饋率
    $tier = Kayarine_Membership::get_tier($user_id);
    $rate = Kayarine_Membership::get_tier_info($tier)['rate'];
    
    $points_earned = floor($earning_base * $rate);
    
    error_log("[Kayarine Reward] Order $order_id. Base: $earning_base, Rate: $rate, Earned: $points_earned");
    
    if ($points_earned <= 0) {
        $order->update_meta_data('_kayarine_points_awarded', 0);
        $order->save();
        return;
    }
    
    // 新增積分
    $membership = new Kayarine_Membership();
    $result = $membership->adjust_points(
        $user_id,
        $points_earned,
        'earn',
        $order_id,
        "訂單 #{$order_id} 回饋 ({$tier}級)"
    );
    
    if ($result) {
        $order->update_meta_data('_kayarine_points_awarded', $points_earned);
        $order->save();
        error_log("[Kayarine Reward] Successfully awarded $points_earned points");
    }
}
```

**邏輯說明**：
- 訂單本身顯示的 total 已經包含了積分折扣（作為負費用）
- 回饋計算時，應該將積分折扣視為「銷售額」的一部分
- 例如：訂單原價 HK$100，用了 50 積分 → 實際支付 HK$50 → 但回饋應基於 HK$100

---

### 第 6 階段: 訂單取消 - 積分退還

**改進方案**：

```php
/**
 * 訂單取消時，退還已扣除的積分
 * Hook: woocommerce_order_status_cancelled
 */
public function refund_points_on_cancellation($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // 檢查是否已退款
    if ($order->get_meta('_kayarine_points_refunded')) {
        error_log("[Kayarine Refund] Order $order_id already refunded");
        return;
    }
    
    $user_id = $order->get_user_id();
    if (!$user_id) return;
    
    $points_deducted = (int)$order->get_meta('_kayarine_points_deducted');
    $points_awarded = (int)$order->get_meta('_kayarine_points_awarded');
    
    error_log("[Kayarine Refund] Order $order_id. Deducted: $points_deducted, Awarded: $points_awarded");
    
    $membership = new Kayarine_Membership();
    
    // 1. 退還已扣除的積分
    if ($points_deducted > 0) {
        $membership->adjust_points(
            $user_id,
            $points_deducted,
            'refund',
            $order_id,
            "訂單 #{$order_id} 取消 - 退還扣除的積分"
        );
    }
    
    // 2. 扣除已獲得的回饋積分
    if ($points_awarded > 0) {
        $membership->adjust_points(
            $user_id,
            -$points_awarded,
            'adjust',  // 使用 'adjust' 以區別回饋
            $order_id,
            "訂單 #{$order_id} 取消 - 扣除回饋積分"
        );
    }
    
    $order->update_meta_data('_kayarine_points_refunded', 1);
    $order->save();
    
    error_log("[Kayarine Refund] Refund completed");
}
```

---

## 改進的會員中心顯示 Workflow

### 問題 1: 查詢條件

**當前**：未指定狀態
**改進**：
```php
$orders = wc_get_orders( array(
    'customer' => $user_id,
    'status'   => array(
        'pending',     // 待支付
        'on-hold',     // 待確認
        'processing',  // 處理中
        'completed',   // 已完成
        'refunded',    // 已退款
    ),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );
```

### 問題 2: 訂單編號和狀態

**改進**：在會員中心查詢時添加積分信息

```php
private function render_booking_row( $order, $user_id ) {
    $order_id = $order->get_id();
    
    // 📍 新增：積分信息
    $points_used = (int)$order->get_meta('_kayarine_points_deducted');
    $points_earned = (int)$order->get_meta('_kayarine_points_awarded');
    
    // HTML 中顯示
    if ($points_used > 0) {
        echo "使用積分: $points_used 分";
    }
    if ($points_earned > 0) {
        echo "獲得積分: $points_earned 分";
    }
}
```

---

## 數據庫變更

### 訂單元數據字段

```
_kayarine_points_requested          int     用戶請求的積分數量
_kayarine_points_deducted           int     已扣除的積分數量
_kayarine_points_awarded            int     已獲得的積分數量
_kayarine_points_status             string  狀態: requested, deducted, failed
_kayarine_points_refunded           int     是否已退款
```

---

## 改進的優勢

| 項目 | 當前 | 改進 |
|------|------|------|
| 數據持久化 | Session（易丟失） | 訂單元數據（持久） |
| AJAX Nonce | 一開始失敗 | 無需 AJAX，表單提交 |
| 多重觸發 | 單一 Hook | 多個 Hook + 定期檢查 |
| 狀態追蹤 | 無 | 有明確的狀態機 |
| 錯誤恢復 | 無 | 可重試 |
| 可觀測性 | 較差 | 詳細日誌 + 元數據 |

