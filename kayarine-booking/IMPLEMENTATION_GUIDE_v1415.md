# Kayarine v1.4.15 - 改進實施指南

## 已完成的改進

### 1️⃣ 會員中心訂單顯示修復
**文件**：`includes/class-kayarine-member-dashboard.php`

**修改**：
```php
// 行 37-48：修改訂單查詢
$orders = wc_get_orders( array(
    'customer' => $user_id,
    'status'   => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded' ),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );
```

**效果**：
- ✅ 會員中心現在能看到所有狀態的訂單（包括 on-hold）
- ✅ 添加了詳細的調試日誌

---

### 2️⃣ 改進的積分系統（新文件）
**文件**：`includes/class-kayarine-improved-checkout.php`

**核心改進**：
1. **不依賴 Session** → 使用表單隱藏欄位和訂單元數據
2. **不依賴 AJAX** → 改用 POST 表單直接提交
3. **多重 Hook 觸發** → 確保積分扣除和回饋
4. **明確的狀態機** → 追蹤積分流轉

**工作流程**：

| 階段 | Hook | 動作 |
|------|------|------|
| 1 | `woocommerce_review_order_before_payment` | 顯示積分UI + 隱藏欄位 |
| 2 | `woocommerce_checkout_order_processed` | 記錄積分請求到訂單元數據 |
| 3 | `woocommerce_checkout_order_processed` | 添加費用項目到訂單 |
| 4 | `pending_to_processing` / `pending_to_completed` | 扣除積分 |
| 5 | `woocommerce_order_status_completed` | 獲得回饋積分 |
| 6 | `woocommerce_order_status_cancelled` | 退還積分 |

---

## 手動部署步驟

### 步驟 1: 上傳新文件

```bash
# 在本地機器上
scp includes/class-kayarine-improved-checkout.php \
    kayarine@kayarine.com.hk:/home/kayarine/tmp/

# 或使用 GCP
gcloud compute scp includes/class-kayarine-improved-checkout.php \
    root@wordpress-2025-vm:/tmp/ --zone=asia-east1-b
```

### 步驟 2: 在服務器上移動文件

```bash
# SSH 進入服務器
ssh kayarine@kayarine.com.hk

# 或使用 GCP
gcloud compute ssh root@wordpress-2025-vm --zone=asia-east1-b

# 移動文件
sudo mv /tmp/class-kayarine-improved-checkout.php \
    /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/

# 設置權限
sudo chown www-data:www-data \
    /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
sudo chmod 644 \
    /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
```

### 步驟 3: 更新主插件文件

需要修改 `kayarine-booking.php`，在第 28-29 行添加：

```php
// ✅ 新增 v1.4.15：改進的積分系統（不依賴 Session，改用訂單元數據）
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-improved-checkout.php';
```

並在 `kayarine_booking_init()` 函數中添加（約第 49 行後）：

```php
// ✅ 初始化改進的積分系統 (v1.4.15)
new Kayarine_Improved_Checkout();
```

### 步驟 4: 更新會員中心文件

需要修改 `class-kayarine-member-dashboard.php` 第 34-42 行，改為：

```php
$user_id = get_current_user_id();

// ✅ 修復 1: 使用正確的參數名稱 'customer' 而不是 'customer_id'
// ✅ 修復 2: 明確指定訂單狀態，包括 on-hold
// Get all orders (not just completed) - FIX for issue #3
$orders = wc_get_orders( array(
    'customer' => $user_id,
    'status'   => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded' ),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );

// 調試日誌
error_log( "[Kayarine Dashboard] User: $user_id | Orders queried with statuses: pending, processing, on-hold, completed, refunded | Total found: " . count( $orders ) );
if ( count( $orders ) > 0 ) {
    foreach ( $orders as $order ) {
        error_log( "[Kayarine Dashboard] Order ID: " . $order->get_id() . " | Status: " . $order->get_status() . " | Total: " . $order->get_total() );
    }
}
```

### 步驟 5: 清除緩存和重新啟用插件

```bash
# 清除 WordPress 插件緩存
wp plugin deactivate kayarine-booking
wp plugin activate kayarine-booking
wp cache flush
```

或在 WordPress 後台：
1. 停用「Kayarine Booking」插件
2. 啟用「Kayarine Booking」插件
3. 訪問「設定 > 一般」，保存一次以更新 WordPress 快取

---

## 測試檢查清單

### ✅ 會員中心測試

1. **訪問會員中心**
   - URL: `https://kayarine.com.hk/account`
   - 應該能看到所有訂單（所有狀態）

2. **檢查日誌**
   ```bash
   tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "Dashboard"
   ```
   應該看到類似：
   ```
   [Kayarine Dashboard] User: 123 | Orders queried ... | Total found: 5
   [Kayarine Dashboard] Order ID: 456 | Status: processing | Total: 500
   ```

### ✅ 積分系統測試

1. **進入結帳頁面**
   - 添加產品到購物車
   - 進入結帳頁面
   - 應該看到「自動使用積分折抵」複選框
   - 複選框應該預設勾選
   - 應該顯示「將折抵: X 分」

2. **檢查隱藏欄位**
   - 打開瀏覽器開發者工具（F12）
   - 查看 HTML 源碼
   - 搜尋「kayarine_points_request」
   - 應該看到隱藏欄位已設置值

3. **完成訂單**
   - 勾選複選框
   - 完成支付
   - 不應再需要「取消後重新勾選」

4. **檢查積分是否扣除**
   - 訪問會員中心
   - 查看「積分餘額」是否減少
   - 或查看數據庫：
   ```sql
   SELECT * FROM wp_kayarine_points_log 
   WHERE user_id = 123 
   ORDER BY date_created DESC 
   LIMIT 10;
   ```

5. **檢查日誌**
   ```bash
   tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine"
   ```
   應該看到：
   ```
   [Kayarine Checkout] Order 789 created. POST data - Points requested: 50
   [Kayarine Fee] Adding fee to order 789 for 50 points
   [Kayarine Deduct] Processing deduction for order 789 - User: 123, Points: 50
   [Kayarine Deduct] Successfully deducted 50 points
   [Kayarine Reward] Order 789 - Base: 550, Rate: 0.01, Earned: 5
   ```

---

## 預期改進結果

| 問題 | 之前 | 之後 |
|------|------|------|
| 會員中心訂單 | 不顯示所有狀態 | ✅ 顯示所有狀態（pending, processing, on-hold, completed, refunded） |
| 一開始積分無法應用 | ❌ 需要取消後重新勾選 | ✅ 預設勾選時直接應用 |
| 積分未被扣除 | ❌ 有時不扣除 | ✅ 確保扣除（多重 Hook） |
| 新訂單未獲得回饋 | ❌ 有時沒有回饋 | ✅ 確保回饋（明確 Hook） |
| 積分資料可靠性 | 低（Session 易丟失） | ✅ 高（訂單元數據持久） |
| 狀態追蹤 | 無 | ✅ 有（元數據記錄完整過程） |

---

## 數據庫變更

新增訂單元數據欄位（自動創建，無需手動操作）：

```
_kayarine_points_requested     訂單中請求的積分數量
_kayarine_points_deducted      已扣除的積分數量
_kayarine_points_awarded       已獲得的回饋積分數量
_kayarine_points_status        狀態：requested, deducted, failed 等
_kayarine_points_refunded      是否已退款
```

---

## 回滾步驟（如有問題）

1. 刪除新文件：
   ```bash
   rm /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
   ```

2. 恢復 `kayarine-booking.php` 和 `class-kayarine-member-dashboard.php` 的原始版本

3. 重新啟用插件

---

## 文檔清單

已提供的分析文檔：

1. **SYSTEM_WORKFLOW_ANALYSIS.md** - 系統完整 workflow 分析
2. **DIAGNOSTIC_WORKFLOW.md** - 診斷步驟和測試指南
3. **IMPROVED_POINTS_SYSTEM_DESIGN.md** - 改進設計詳細說明
4. **IMPLEMENTATION_GUIDE_v1415.md** - 本文檔

---

## 支援和問題

如有問題，檢查：

1. **日誌文件**
   ```bash
   tail -100 /opt/bitnami/wordpress/wp-content/debug.log
   ```

2. **積分日誌表**
   ```sql
   SELECT * FROM wp_kayarine_points_log ORDER BY date_created DESC LIMIT 20;
   ```

3. **訂單元數據**
   ```sql
   SELECT * FROM wp_postmeta 
   WHERE post_id = 789 
   AND meta_key LIKE '_kayarine%'
   ORDER BY meta_key;
   ```

