# Kayarine 系統整合總結

本文檔整合了從 GCP 實際部署環境中讀取的所有系統邏輯。

---

## 📋 文檔索引

### 核心部署文檔

| 文檔 | 用途 | 最後更新 |
|------|------|--------|
| [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) | GCP 標準部署指南（含 SSH key 路徑） | 2026-01-31 |
| [`CLOUDFLARE_DNS_REFERENCE.md`](CLOUDFLARE_DNS_REFERENCE.md) | DNS 配置參考 | - |
| [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md) | 開發日誌和診斷參考 | - |

### 系統邏輯文檔

| 文檔 | 說明 |
|------|------|
| [`INVENTORY_SYSTEM_INTEGRATION.md`](INVENTORY_SYSTEM_INTEGRATION.md) | 庫存系統完整邏輯（從 GCP 讀取） |
| [`IMPROVED_POINTS_SYSTEM_DESIGN.md`](kayarine-booking/IMPROVED_POINTS_SYSTEM_DESIGN.md) | 改進的積分系統設計 |
| [`SYSTEM_WORKFLOW_ANALYSIS.md`](kayarine-booking/SYSTEM_WORKFLOW_ANALYSIS.md) | 系統工作流分析 |

---

## 🔧 三個核心系統

### 1. 庫存管理系統

**檔案**：[`class-kayarine-inventory.php`](kayarine-booking/includes/class-kayarine-inventory.php)

**核心功能**：
- 產品限制管理（預設值 + 自訂選項）
- 黑名單/白名單日期管理
- 日常使用計算（三層快取策略）
- 待處理使用記錄

**關鍵類方法**：
- `get_limits()` - 獲取產品限制
- `get_blackout_dates()` - 獲取黑名單規則
- `get_daily_usage()` - 計算特定日期使用
- `get_availability()` - 獲取可用性報告
- `clear_cache()` - 清除日期快取

**儲存機制**：
- WordPress 選項：產品限制、黑名單規則
- WordPress 選項：待處理使用（`kayarine_pending_usage`）
- 瞬態快取：5秒 TTL 的日使用快取
- 運行時快取：請求範圍內的快取

**更詳細信息**：見 [`INVENTORY_SYSTEM_INTEGRATION.md`](INVENTORY_SYSTEM_INTEGRATION.md)

---

### 2. 積分系統

**檔案**：[`class-kayarine-improved-checkout.php`](kayarine-booking/includes/class-kayarine-improved-checkout.php)

**核心改進**（相對於原始系統）：
- ✅ 不依賴 Session（改用訂單元數據）
- ✅ 不依賴 AJAX Nonce 驗證（改用隱藏表單欄位）
- ✅ 多重 Hook 觸發以確保執行
- ✅ 完整的積分生命週期跟蹤

**六階段處理流程**：

```
1. woocommerce_review_order_before_payment
   └─ 顯示積分選項 + 隱藏表單欄位

2. woocommerce_checkout_order_processed (Priority 10)
   └─ 記錄積分請求（從 POST 數據）

3. woocommerce_checkout_order_processed (Priority 20)
   └─ 添加費用項目到訂單

4. 多個狀態轉換 Hook (優先級：20)
   ├─ woocommerce_order_status_pending_to_processing
   ├─ woocommerce_order_status_pending_to_completed
   ├─ woocommerce_order_status_on-hold_to_processing
   └─ woocommerce_order_status_on-hold_to_completed
   └─ 扣除積分

5. woocommerce_order_status_completed (優先級：20)
   └─ 新增回饋積分

6. 訂單取消/退款 Hook
   ├─ woocommerce_order_status_cancelled
   └─ woocommerce_order_status_refunded
   └─ 退還所有積分
```

**訂單元數據鍵**：
- `_kayarine_points_requested` - 請求使用的積分
- `_kayarine_points_deducted` - 已扣除的積分
- `_kayarine_points_awarded` - 獲得的回饋積分
- `_kayarine_points_status` - 處理狀態

**詳細信息**：見 [`class-kayarine-improved-checkout.php`](kayarine-booking/includes/class-kayarine-improved-checkout.php:1)

---

### 3. 會員中心系統

**檔案**：[`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php)

**修復項目**：

1. ✅ **訂單查詢修復**
   - 改正 WooCommerce 參數：`customer_id` → `customer`
   - 添加完整訂單狀態篩選：`pending, processing, on-hold, completed, refunded`

2. ✅ **訂單顯示優化**
   - 清楚顯示訂單編號、設備數量、預訂日期
   - 根據訂單狀態的顏色編碼
   - on-hold 訂單標記為「未確認 (待支付)」

3. ✅ **改期與取消功能**
   - 改期前驗證新日期庫存
   - 改期前檢查黑名單規則
   - 取消時正確處理已使用的積分退款

**關鍵修復**（[lines 36-52](kayarine-booking/includes/class-kayarine-member-dashboard.php:36)）：

```php
$orders = wc_get_orders( array(
    'customer' => $user_id,  // ✅ 正確參數名稱
    'status'   => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded' ),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );
```

**詳細信息**：見 [`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php:1)

---

## 🔗 系統整合點

### 庫存 ↔ 積分

| 操作 | 庫存系統 | 積分系統 |
|------|--------|--------|
| 訂單建立 | 記錄待處理使用 | 記錄積分請求 |
| 訂單完成 | 清除快取 | 新增回饋積分 |
| 訂單改期 | 更新日期、清除快取 | 積分金額無變化 |
| 訂單取消 | 清除待處理、清除快取 | 退還積分 |

### 會員中心 ↔ 庫存

| 操作 | 會員中心 | 庫存系統 |
|------|--------|--------|
| 顯示訂單 | 查詢訂單 | 不涉及 |
| 改期操作 | 驗證新日期 | 檢查庫存、黑名單、快取清除 |
| 取消操作 | 更新訂單狀態 | 快取清除 |

### 會員中心 ↔ 積分

| 操作 | 會員中心 | 積分系統 |
|------|--------|--------|
| 顯示積分餘額 | 讀取用戶 meta | 維護積分餘額 |
| 訂單取消 | 觸發取消動作 | 退還積分 |

---

## 🚀 部署檢查清單

### 前置準備

- [ ] SSH 金鑰設置正確（路徑：`/Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key`）
- [ ] gcloud CLI 配置完成
- [ ] GCP 實例處於運行狀態
- [ ] 防火牆規則允許 SSH 連接

### 文件部署

- [ ] 備份原始文件
- [ ] 上傳新的庫存系統邏輯
- [ ] 上傳改進的積分系統
- [ ] 上傳修復的會員中心
- [ ] 修改主插件文件以加載新類

### 配置檢查

- [ ] 產品 ID 在 `kayarine-config.php` 中正確定義
- [ ] 預設庫存限制設置
- [ ] 黑名單日期配置（如需要）
- [ ] 白名單日期配置（如需要）
- [ ] WordPress 調試日誌啟用（`WP_DEBUG = true`）

### 功能測試

- [ ] 會員中心顯示所有訂單狀態
- [ ] 積分自動應用在結帳時
- [ ] 積分在訂單完成時扣除
- [ ] 回饋積分在訂單完成時新增
- [ ] 訂單取消時正確退款
- [ ] 改期功能驗證新日期庫存
- [ ] 改期功能尊重黑名單規則

### 性能驗證

- [ ] 庫存查詢響應時間 < 100ms（有快取）
- [ ] 快取清除機制正常工作
- [ ] 數據庫查詢在合理時間內完成

---

## 📊 數據模型參考

### 訂單 Meta 數據

| Meta Key | 用途 | 所有者 |
|----------|------|------|
| `_kayarine_points_requested` | 請求使用的積分 | 積分系統 |
| `_kayarine_points_deducted` | 已扣除的積分 | 積分系統 |
| `_kayarine_points_awarded` | 獲得的回饋積分 | 積分系統 |
| `_kayarine_points_status` | 積分處理狀態 | 積分系統 |
| `_kayarine_booking_date` | 預訂日期 | 庫存系統 |

### 訂單項目 Meta 數據

| Meta Key | 用途 |
|----------|------|
| `_product_id` | 產品 ID |
| `_qty` | 數量 |
| `_kayarine_booking_date` | 預訂日期 |

### WordPress 選項

| 選項鍵 | 用途 |
|--------|------|
| `kayarine_pending_usage` | 待處理使用記錄 |
| `kayarine_limit_{ID}` | 產品自訂限制 |
| `kayarine_blackout_dates` | 黑名單日期規則 |
| `kayarine_debug_mode` | 調試模式開關 |

### 瞬態快取

| 快取鍵 | TTL | 用途 |
|--------|-----|------|
| `kayarine_usage_{date}` | 5 秒 | 日庫存使用快取 |

---

## 🔍 診斷指南

### 檢查積分是否正確應用

```bash
# SSH 連接
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 查看日誌
tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep Kayarine

# 檢查特定訂單的 meta 數據
sudo -u www-data wp post meta list <order_id> --format=table
```

### 檢查庫存計算

```bash
# 禁用快取進行診斷
# 在 wp-config.php 添加
define('KAYARINE_DISABLE_CACHE', true);

# 查看庫存計算日誌
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Inventory"
```

### 檢查會員中心訂單

```bash
# 查詢特定用戶的訂單
sudo -u www-data wp woocommerce order list --customer=<customer_id> --format=table
```

---

## 📝 後續改進建議

1. **性能優化**
   - 考慮增加快取 TTL（目前 5 秒）
   - 實現批量快取清除

2. **功能擴展**
   - 支持日期範圍的白名單
   - 按用戶等級的動態庫存限制
   - 積分有效期管理

3. **監控**
   - 添加實時監控儀表板
   - 設置庫存預警
   - 積分使用趨勢分析

4. **測試**
   - 自動化端到端測試
   - 負載測試驗證性能
   - 灾難恢復測試

---

## 📞 支援資源

- **部署指南**：[`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md)
- **庫存系統詳解**：[`INVENTORY_SYSTEM_INTEGRATION.md`](INVENTORY_SYSTEM_INTEGRATION.md)
- **源代碼**：
  - 庫存：[`class-kayarine-inventory.php`](kayarine-booking/includes/class-kayarine-inventory.php)
  - 積分：[`class-kayarine-improved-checkout.php`](kayarine-booking/includes/class-kayarine-improved-checkout.php)
  - 會員中心：[`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php)

---

**生成日期**：2026-01-31  
**數據來源**：GCP 實際部署環境  
**版本**：Kayarine Booking v1.4.14
