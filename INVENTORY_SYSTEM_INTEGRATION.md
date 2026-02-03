# Kayarine 庫存系統整合文檔

整合自 GCP WordPress 實際部署環境的庫存系統邏輯。

## 系統概述

庫存系統透過 [`class-kayarine-inventory.php`](kayarine-booking/includes/class-kayarine-inventory.php:1) 管理所有預訂設備的可用性、限制和使用計算。

## 核心儲存機制

### 1. 待處理使用記錄（Pending Usage Storage）

**儲存位置**：WordPress 選項（`kayarine_pending_usage`）

**數據結構**：
```php
[order_id][date][product_id] = qty
```

**用途**：記錄正在建立但未完成的訂單佔用的設備。

**關鍵方法**：
- [`record_pending_usage()`](kayarine-booking/includes/class-kayarine-inventory.php:47) - 記錄待處理使用
- [`get_pending_usage()`](kayarine-booking/includes/class-kayarine-inventory.php:89) - 檢索特定日期的待處理使用
- [`clear_pending_usage()`](kayarine-booking/includes/class-kayarine-inventory.php:119) - 清除訂單的待處理記錄

---

## 庫存限制管理

### 預設限制

每個產品有預設的限制數量（[lines 135-154](kayarine-booking/includes/class-kayarine-inventory.php:135)）：

| 產品 | 預設限制 |
|------|--------|
| 單人獨木舟 (ID_SINGLE_KAYAK) | 50 |
| 雙人獨木舟 (ID_DOUBLE_KAYAK) | 20 |
| 家庭獨木舟 (ID_FAMILY_KAYAK) | 10 |
| 立式滑水板 (ID_SUP) | 20 |
| 浮潛租賃 (ID_SNORKEL_RENT) | 50 |
| 手機保護套 (ID_PHONE_CASE) | 50 |
| 日出導覽 (ID_TOUR_SUNRISE) | 20 |
| 日落導覽 (ID_TOUR_SUNSET) | 20 |
| 初級課程 (ID_COURSE_BEGINNER) | 16 |
| 浮潛導覽 (ID_TOUR_SNORKEL) | 20 |
| 威士忌課程 (ID_COURSE_WHISKEY) | 10 |
| 銅級課程 (ID_COURSE_BRONZE) | 10 |
| 銀級課程 (ID_COURSE_SILVER) | 10 |
| 瑜伽導覽 (ID_TOUR_YOGA) | 20 |

### 自訂限制

通過 WordPress 選項覆蓋預設值：

**選項鍵格式**：`kayarine_limit_{product_id}`

**方法**：[`get_limits()`](kayarine-booking/includes/class-kayarine-inventory.php:135)

```php
// 例如：自訂 ID 123 的限制為 30
update_option( 'kayarine_limit_123', 30 );
```

---

## 黑名單日期管理

### 黑名單規則語法

**儲存位置**：WordPress 選項 `kayarine_blackout_dates`（多行文本區）

**規則格式**：
```
日期 | 條件 | 標籤
```

### 支援的日期格式

1. **單一日期**
   ```
   2026-02-15
   ```

2. **日期範圍**
   ```
   2026-02-15 to 2026-02-20
   ```

3. **循環日期**
   ```
   Every Monday
   Every Friday
   ```

### 條件類型

| 條件 | 格式 | 說明 |
|------|------|------|
| 產品特定 | `ID:123` | 只對產品 ID 123 適用 |
| 標籤特定 | `Tag:sunrise` | 只對帶有「sunrise」標籤的產品適用 |
| 全域 | 無條件 | 對所有產品適用 |

### 特殊標籤

- **`限時活動`** - 標記為有限時間事件的白名單（allowlist）

**白名單規則範例**：
```
2026-02-14 | ID:123 | 限時活動
2026-02-15 to 2026-02-16 | ID:456 | 限時活動
```

當產品設置了白名單規則時，**只有** 在白名單中的日期才可預訂。

**相關方法**：
- [`get_blackout_dates()`](kayarine-booking/includes/class-kayarine-inventory.php:174) - 獲取所有黑名單規則
- [`get_allowlist_dates()`](kayarine-booking/includes/class-kayarine-inventory.php:194) - 獲取特定產品的白名單日期
- [`is_blackout()`](kayarine-booking/includes/class-kayarine-inventory.php:233) - 檢查日期是否被黑名單

---

## 日常使用計算（日庫存查詢）

### 方法：[`get_daily_usage()`](kayarine-booking/includes/class-kayarine-inventory.php:341)

計算特定日期的設備使用情況。

### 三層快取策略

為了平衡性能與實時性，系統使用三層快取：

```
1. 運行時快取（Per Request）
   ↓
2. 瞬態快取（5秒 TTL）
   ↓
3. 數據庫查詢（如果快取未命中）
```

**快取禁用**：在 `wp-config.php` 中添加以下代碼以禁用快取進行診斷：

```php
define('KAYARINE_DISABLE_CACHE', true);
```

### 訂單狀態支援

系統查詢以下訂單狀態的設備使用：

- `wc-pending` 或 `pending` - 待支付
- `wc-processing` 或 `processing` - 處理中
- `wc-completed` 或 `completed` - 已完成
- `wc-on-hold` 或 `on-hold` - 待確認

**已取消（cancelled）的訂單不計入庫存佔用**。

### 訂單狀態格式自動檢測

系統自動檢測數據庫中使用的訂單狀態格式（帶或不帶 `wc-` 前綴），並相應調整查詢。

```php
// 自動檢測邏輯 (lines 377-390)
$use_wc_prefix = false;
if (!empty($db_statuses) && strpos($db_statuses[0], 'wc-') === 0) {
    $use_wc_prefix = true;
}
```

### 使用計算流程

1. **檢查運行時快取** - 如果存在則立即返回
2. **檢查瞬態快取** - 如果存在則返回並更新運行時快取
3. **查詢數據庫** - 執行 SQL 查詢計算使用
4. **添加待處理使用** - 合併正在建立的訂單
5. **保存快取** - 存入瞬態快取（5秒）和運行時快取

### 數據庫查詢

查詢 `wp_woocommerce_order_itemmeta` 表，連接相關訂單和商品信息：

```sql
SELECT
    item_meta_product.meta_value as product_id,
    SUM(CAST(item_meta_qty.meta_value AS UNSIGNED)) as total_qty
FROM wp_woocommerce_order_itemmeta as item_meta_date
INNER JOIN wp_woocommerce_order_items as items
    ON item_meta_date.order_item_id = items.order_item_id
INNER JOIN wp_posts as orders
    ON items.order_id = orders.ID
INNER JOIN wp_woocommerce_order_itemmeta as item_meta_product
    ON items.order_item_id = item_meta_product.order_item_id
    AND item_meta_product.meta_key = '_product_id'
INNER JOIN wp_woocommerce_order_itemmeta as item_meta_qty
    ON items.order_item_id = item_meta_qty.order_item_id
    AND item_meta_qty.meta_key = '_qty'
WHERE
    item_meta_date.meta_key = '_kayarine_booking_date'
    AND item_meta_date.meta_value = %s
    AND orders.post_type = 'shop_order'
    AND orders.post_status IN (...)
    AND items.order_item_type = 'line_item'
GROUP BY product_id
```

### 調試日誌

系統在以下情況產生詳細日誌：

- 記錄待處理使用 - 驗證所有參數
- 計算日使用 - 記錄 SQL 查詢和結果
- 快取操作 - 記錄快取命中/未命中
- 黑名單檢查 - 記錄規則匹配

所有日誌寫入 `/wp-content/debug.log`（如啟用 `WP_DEBUG`）

---

## 可用性報告

### 方法：[`get_availability()`](kayarine-booking/includes/class-kayarine-inventory.php:547)

返回特定日期的完整可用性信息。

**返回格式**：
```php
[
    product_id => [
        'name'      => 'product_name',
        'limit'     => 50,
        'used'      => 12,
        'remaining' => 38
    ],
    ...
]
```

### 邏輯流程

1. 獲取所有產品的限制
2. 獲取特定日期的使用情況
3. 檢查全域黑名單
4. 對每個產品檢查特定黑名單
5. 計算剩餘數量

---

## 快取管理

### 方法：[`clear_cache()`](kayarine-booking/includes/class-kayarine-inventory.php:582)

清除特定日期的瞬態快取。

**用途**：新訂單建立時調用，確保下一次可用性檢查是最新的。

**調用位置**：
- 訂單建立時
- 訂單改期時
- 訂單取消時

---

## 日誌系統

### 日誌記錄方法：[`log()`](kayarine-booking/includes/class-kayarine-inventory.php:525)

記錄到文件系統的簡單日誌機制。

**日誌位置**：`/wp-content/kayarine-debug.log`

**日誌查詢方法**：[`get_log_tail()`](kayarine-booking/includes/class-kayarine-inventory.php:535)

獲取最後 N 行日誌（預設 50 行）。

---

## 與積分系統的整合

庫存系統與積分系統通過以下方式整合：

1. **訂單建立時**
   - 積分系統記錄積分使用請求
   - 庫存系統記錄待處理使用

2. **訂單完成時**
   - 積分系統計算並新增回饋積分
   - 庫存系統快取被清除以反映最新使用

3. **訂單改期時**
   - 庫存系統更新舊/新日期的快取
   - 積分金額無變化（仍基於訂單總額）

4. **訂單取消時**
   - 積分系統退還所有已使用的積分和回饋
   - 庫存系統清除待處理記錄，快取被清除

---

## 與會員中心的整合

會員中心顯示訂單時，庫存系統提供：

1. **可用性檢查**
   - 改期時驗證新日期是否有庫存
   - 防止超額預訂

2. **日期限制**
   - 黑名單日期不可選擇
   - 白名單日期限制選擇範圍

---

## 關鍵文件引用

| 檔案 | 路徑 | 用途 |
|------|------|------|
| 庫存管理類 | `includes/class-kayarine-inventory.php` | 核心庫存邏輯 |
| 配置類 | `includes/kayarine-config.php` | 產品 ID 定義 |
| 結帳管理 | `includes/class-kayarine-improved-checkout.php` | 積分與庫存整合 |
| 會員中心 | `includes/class-kayarine-member-dashboard.php` | 訂單管理與改期 |

---

## 故障排查

### 問題：庫存數字不正確

**檢查清單**：

1. 驗證數據庫中的訂單狀態格式
   ```sql
   SELECT DISTINCT post_status FROM wp_posts 
   WHERE post_type = 'shop_order' LIMIT 5;
   ```

2. 檢查 meta keys 是否正確
   ```sql
   SELECT DISTINCT meta_key FROM wp_woocommerce_order_itemmeta 
   LIMIT 20;
   ```

3. 禁用快取進行診斷
   ```php
   // 在 wp-config.php 添加
   define('KAYARINE_DISABLE_CACHE', true);
   ```

4. 查看詳細日誌
   ```
   tail -100 /opt/bitnami/wordpress/wp-content/debug.log
   ```

### 問題：改期/取消失敗

**檢查事項**：

1. 用戶權限 - 確認用戶擁有該訂單
2. 訂單狀態 - 確認訂單狀態支援改期/取消
3. 庫存可用性 - 確認新日期有足夠庫存
4. 黑名單規則 - 確認新日期未被黑名單

---

## 部署檢查清單

- [ ] 確認所有產品 ID 在 `kayarine-config.php` 中定義
- [ ] 設置預設庫存限制（或通過 WordPress 選項自訂）
- [ ] 配置黑名單日期（如需要）
- [ ] 配置白名單日期以支援限時事件（如需要）
- [ ] 驗證訂單狀態格式與數據庫一致
- [ ] 在 wp-config.php 啟用 WP_DEBUG 以記錄日誌
- [ ] 測試可用性檢查端點
- [ ] 測試快取清除機制
