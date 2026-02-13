# 訂單創建失敗診斷與修復指南

## 📋 問題總結

訂單創建功能的 WordPress REST API 端點 **已可用且正常運作**，但訂單創建可能因以下原因失敗：

### ✅ 已確認正常運作
- WordPress REST API 端點：`/wp-json/kayarine/v1/orders/create` ✓
- WooCommerce 訂單創建功能 ✓
- 庫存驗證邏輯 ✓
- 前端 API 調用邏輯 ✓

### ❌ 常見失敗原因

1. **黑名單日期** - 所選日期被設置為不可預訂
2. **庫存不足** - 產品在該日期的剩餘庫存為 0
3. **產品 ID 錯誤** - 使用臨時 ID 而非真實 WooCommerce 產品 ID
4. **WooCommerce 產品不存在** - 產品未在 WordPress 後台創建

---

## 🔍 診斷步驟

### 步驟 1：測試 REST API 端點

```bash
# 測試訂單創建（使用未來日期）
curl -X POST "http://104.199.144.122:80/wp-json/kayarine/v1/orders/create" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "test@example.com",
    "customer_phone": "91234567",
    "items": [
      {
        "id": 6954,
        "name": "單人獨木舟",
        "quantity": 1,
        "bookingDate": "2026-03-15"
      }
    ],
    "payment_method": "fps"
  }'
```

**成功響應**：
```json
{
  "success": true,
  "order_id": 7264,
  "order_number": "7264",
  "order_key": "wc_order_xxx",
  "total": "100.00",
  "status": "pending"
}
```

**失敗響應（黑名單）**：
```json
{
  "code": "blackout_date",
  "message": "所選日期不可預訂",
  "data": {"status": 400}
}
```

**失敗響應（庫存不足）**：
```json
{
  "code": "insufficient_inventory",
  "message": "單人獨木舟 庫存不足",
  "data": {"status": 400}
}
```

### 步驟 2：檢查庫存可用性

```bash
# 查詢特定日期的庫存狀態
curl -s "http://104.199.144.122:80/wp-json/kayarine/v1/inventory/availability?date=2026-02-15" | python3 -m json.tool
```

**檢查要點**：
- `remaining`: 剩餘庫存數量（如果為 0 則無法預訂）
- `used`: 已使用數量（如果等於 limit 則表示已滿）
- `limit`: 該產品的每日限制

### 步驟 3：檢查黑名單日期配置

**SSH 登入伺服器**：
```bash
ssh kayarine.server@104.199.144.122
```

**查詢黑名單設置**（WordPress CLI）：
```bash
cd /opt/bitnami/wordpress
wp option get kayarine_blackout_dates --allow-root
```

**常見黑名單格式**：
```
2026-02-15 | | 春節假期
2026-02-15 to 2026-02-20 | | 春節連假
Every Monday | | 週一休息
```

### 步驟 4：檢查產品是否存在

```bash
# 使用 WordPress CLI 查詢產品
wp post list --post_type=product --fields=ID,post_title --allow-root | grep "單人獨木舟"
```

**驗證產品 ID**：
- 6954 - 單人獨木舟 ✓
- 6955 - 雙人獨木舟 ✓
- 999991 - 防水袋 ❌（臨時 ID，需要替換為真實產品 ID）
- 999992 - 沙灘巾 ❌（臨時 ID，需要替換為真實產品 ID）

---

## 🔧 修復方案

### 方案 1：清除特定日期的黑名單

**步驟**：
1. 登入 WordPress 後台：`https://kayarine.club/wp-admin`
2. 側邊欄 → **Kayarine 庫存**
3. 切換到 **黑名單日期管理** Tab
4. 找到並刪除或修改該日期的規則
5. 點擊 **保存變更**

**或使用 WordPress CLI**：
```bash
# 獲取當前黑名單
wp option get kayarine_blackout_dates --allow-root > /tmp/blackout.txt

# 編輯文件移除不需要的日期
nano /tmp/blackout.txt

# 更新黑名單
wp option update kayarine_blackout_dates "$(cat /tmp/blackout.txt)" --allow-root

# 清除快取
wp cache flush --allow-root
```

### 方案 2：增加產品庫存限制

**步驟**：
1. WordPress 後台 → **Kayarine 庫存**
2. **產品庫存限制** Tab
3. 找到產品（例如：單人獨木舟）
4. 修改 **每日限制** 數值（例如：50 → 100）
5. 點擊 **保存變更**

**或使用 WordPress CLI**：
```bash
# 設置產品 6954 的限制為 100
wp option update kayarine_limit_6954 100 --allow-root

# 清除快取
wp transient delete kayarine_inventory_2026-02-15 --allow-root
```

### 方案 3：修復前端臨時產品 ID

**問題文件**：
- [`components/journey/JourneyBooking.tsx`](../kayarine-nextjs-frontend/components/journey/JourneyBooking.tsx:577)

**需要修改**：
```typescript
// ❌ 錯誤：使用臨時 ID
if (addOns.waterproofBag > 0) {
  items.push({
    id: 999991, // 臨時 ID
    name: '防水袋',
    ...
  });
}

// ✅ 正確：使用真實產品 ID
if (addOns.waterproofBag > 0) {
  items.push({
    id: 6967, // 真實 WooCommerce 產品 ID
    name: '手機防水袋',
    ...
  });
}
```

**修復步驟**：
1. 查詢真實產品 ID（參考步驟 4）
2. 更新前端代碼中的產品 ID
3. 重新構建並部署 Next.js 應用

### 方案 4：在 WooCommerce 創建缺失產品

**如果產品不存在**：
1. WordPress 後台 → **產品** → **新增產品**
2. 填寫產品資訊：
   - 產品名稱：防水袋
   - 價格：50
   - 產品類型：簡單產品
3. 發布產品
4. 記錄新產品的 ID
5. 更新前端代碼使用新 ID

---

## 🧪 完整測試流程

### 測試 1：基本訂單創建

```bash
# 測試成功場景（未來日期 + 真實產品 ID）
curl -X POST "http://104.199.144.122:80/wp-json/kayarine/v1/orders/create" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "test@kayarine.club",
    "customer_phone": "91234567",
    "items": [
      {"id": 6954, "name": "單人獨木舟", "quantity": 2, "bookingDate": "2026-03-20"}
    ],
    "payment_method": "fps"
  }'
```

**預期結果**：✅ `{"success": true, "order_id": ...}`

### 測試 2：黑名單日期驗證

```bash
# 測試黑名單日期
curl -X POST "http://104.199.144.122:80/wp-json/kayarine/v1/orders/create" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "test@kayarine.club",
    "customer_phone": "91234567",
    "items": [
      {"id": 6954, "name": "單人獨木舟", "quantity": 1, "bookingDate": "2026-02-15"}
    ],
    "payment_method": "fps"
  }'
```

**預期結果**：❌ `{"code": "blackout_date", ...}`（如果 2026-02-15 在黑名單中）

### 測試 3：庫存不足驗證

```bash
# 測試超出庫存限制
curl -X POST "http://104.199.144.122:80/wp-json/kayarine/v1/orders/create" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "test@kayarine.club",
    "customer_phone": "91234567",
    "items": [
      {"id": 6954, "name": "單人獨木舟", "quantity": 1000, "bookingDate": "2026-03-20"}
    ],
    "payment_method": "fps"
  }'
```

**預期結果**：❌ `{"code": "insufficient_inventory", ...}`

### 測試 4：前端完整流程

1. 訪問：`https://kayarine.club/rental-services`
2. 選擇日期（避開黑名單日期）
3. 選擇設備數量
4. 點擊「確認租借」
5. 填寫聯絡資訊
6. 選擇付款方式
7. 點擊「確認付款」
8. 驗證跳轉到 `/checkout/success`
9. 檢查訂單編號是否顯示

**瀏覽器開發者工具檢查**：
- Network Tab → 查看 POST 請求到 `/wp-json/kayarine/v1/orders/create`
- Console Tab → 查看日誌輸出（`📤 發送訂單請求`, `✅ 訂單創建成功`）

---

## 📊 常見錯誤代碼對照表

| 錯誤代碼 | 錯誤訊息 | 原因 | 解決方案 |
|---------|---------|------|---------|
| `blackout_date` | 所選日期不可預訂 | 日期在黑名單中 | 移除黑名單規則或選擇其他日期 |
| `insufficient_inventory` | [產品名] 庫存不足 | 庫存剩餘為 0 或小於請求數量 | 增加庫存限制或減少預訂數量 |
| `order_creation_failed` | 訂單創建失敗 | WooCommerce 錯誤 | 檢查 WooCommerce 配置和產品設置 |
| `rest_forbidden` | 無權訪問 | API 權限問題 | 檢查 REST API 權限設置 |
| `Failed to fetch` | 網絡連接失敗 | WordPress 服務未運行或網絡問題 | 檢查 WordPress 服務狀態 |

---

## 🚀 快速修復命令集

### 清除所有快取

```bash
# SSH 登入
ssh kayarine.server@104.199.144.122

# 清除 WordPress 快取
cd /opt/bitnami/wordpress
wp cache flush --allow-root

# 清除瞬態快取（庫存）
wp transient delete --all --allow-root

# 重啟 WordPress
sudo /opt/bitnami/ctlscript.sh restart
```

### 檢查 WordPress 服務狀態

```bash
# 檢查 Apache 狀態
sudo /opt/bitnami/ctlscript.sh status

# 檢查 WordPress 日誌
sudo tail -f /opt/bitnami/wordpress/wp-content/debug.log
```

### 啟用調試模式

編輯 `wp-config.php`：
```bash
sudo nano /opt/bitnami/wordpress/wp-config.php
```

添加：
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('KAYARINE_DISABLE_CACHE', true); // 禁用庫存快取用於調試
```

---

## 📝 維護建議

### 定期檢查清單

- [ ] 每週檢查黑名單日期配置是否需要更新
- [ ] 每月檢查庫存使用率，調整限制設置
- [ ] 每季度檢查訂單創建日誌，識別常見失敗原因
- [ ] 定期清除過期的待處理訂單（pending）

### 監控指標

- **訂單成功率**：成功訂單 / 總嘗試數
- **常見失敗原因**：黑名單日期 vs 庫存不足 vs 其他
- **平均響應時間**：API 響應時間
- **庫存使用率**：used / limit（高於 80% 需要考慮增加限制）

---

## 🔗 相關文件

- [`class-kayarine-rest-api.php`](kayarine-booking/includes/class-kayarine-rest-api.php) - REST API 端點實現
- [`class-kayarine-inventory.php`](kayarine-booking/includes/class-kayarine-inventory.php) - 庫存管理邏輯
- [`lib/api/inventory.ts`](../kayarine-nextjs-frontend/lib/api/inventory.ts) - 前端 API 調用
- [`CheckoutForm.tsx`](../kayarine-nextjs-frontend/components/rental-services/CheckoutForm.tsx) - 結帳表單
- [`INVENTORY_SYSTEM_INTEGRATION.md`](INVENTORY_SYSTEM_INTEGRATION.md) - 庫存系統文檔
- [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 部署指南

---

## 📞 技術支援

如問題持續存在，請提供以下信息：

1. **錯誤訊息**：完整的 API 響應或前端錯誤
2. **測試數據**：使用的日期、產品 ID、數量
3. **瀏覽器日誌**：開發者工具 Console 輸出
4. **伺服器日誌**：`/opt/bitnami/wordpress/wp-content/debug.log`

**最後更新**：2026-02-06
