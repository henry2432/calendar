# Kayarine v1.4.15 - 手動部署指南

GCP SSH 連接遇到認證問題。以下是手動部署的完整步驟。

---

## 方法 1: 通過 WordPress 後台編輯器部署（最簡單）

### 步驟 1: 訪問 WordPress 後台插件編輯器

1. 登入 `https://kayarine.com.hk/wp-admin`
2. 導航到 **插件 → 插件編輯器**
3. 在右邊選擇 **Kayarine Booking**

### 步驟 2: 編輯主插件文件

**文件**：`kayarine-booking.php`

在第 28-29 行（在 `require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-woocommerce-customizer.php';` 後面）添加：

```php
// ✅ 新增 v1.4.15：改進的積分系統（不依賴 Session，改用訂單元數據）
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-improved-checkout.php';
```

在 `kayarine_booking_init()` 函數中（大約第 49 行，在 `kayarine_ensure_unified_account_page();` 後面）添加：

```php
// ✅ 初始化改進的積分系統 (v1.4.15)
new Kayarine_Improved_Checkout();
```

**點擊「更新文件」保存**

### 步驟 3: 編輯會員中心文件

**文件**：`includes/class-kayarine-member-dashboard.php`

找到第 34-42 行，替換為：

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

**點擊「更新文件」保存**

### 步驟 4: 添加新的積分系統文件

由於 WordPress 後台編輯器無法新增文件，需要手動創建。

**步驟 4a: 複製代碼**

打開 `includes/class-kayarine-improved-checkout.php`，複製整個內容

**步驟 4b: 使用 FTP 或 SFTP 上傳**

使用 FTP 客戶端（如 FileZilla）：
1. 連接到服務器
2. 導航到 `/wp-content/plugins/kayarine-booking/includes/`
3. 新建文件 `class-kayarine-improved-checkout.php`
4. 粘貼代碼內容

或使用命令行：

```bash
# 如果有 SSH 訪問權限
scp includes/class-kayarine-improved-checkout.php \
    kayarine@kayarine.com.hk:/home/kayarine/public_html/wp-content/plugins/kayarine-booking/includes/
```

### 步驟 5: 重新啟用插件

1. 進入 WordPress 後台 → **插件**
2. 找到 **Kayarine Booking**
3. 點擊 **停用**
4. 點擊 **啟用**

### 步驟 6: 清除緩存

1. **WordPress 緩存**：設定 → 一般 → 保存
2. **瀏覽器緩存**：按 Ctrl+Shift+Delete 清除
3. **CDN 緩存**（如果有）：Cloudflare → Caching → Purge Cache

---

## 方法 2: 通過 WP-CLI 部署（如果已安裝）

```bash
# SSH 到服務器
ssh kayarine@kayarine.com.hk

# 進入 WordPress 目錄
cd /home/kayarine/public_html

# 使用 WP-CLI 完成部署
wp plugin deactivate kayarine-booking
wp plugin activate kayarine-booking
wp cache flush
```

---

## 方法 3: 通過 SSH 和文本編輯器部署

```bash
# 1. SSH 進入服務器
ssh kayarine@kayarine.com.hk

# 2. 進入插件目錄
cd /home/kayarine/public_html/wp-content/plugins/kayarine-booking/includes

# 3. 編輯主文件
nano class-kayarine-member-dashboard.php
# 進行上述編輯，按 Ctrl+X → Y → Enter 保存

# 4. 上傳新文件
# 在本地機器上執行：
scp /path/to/class-kayarine-improved-checkout.php \
    kayarine@kayarine.com.hk:/home/kayarine/public_html/wp-content/plugins/kayarine-booking/includes/

# 5. 設置權限
chmod 644 /home/kayarine/public_html/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
```

---

## 驗證部署

### ✅ 會員中心驗證

1. 訪問 `https://kayarine.com.hk/account`
2. 應該看到所有訂單（所有狀態）
3. 查看日誌：
   ```bash
   tail -50 /opt/bitnami/wordpress/wp-content/debug.log | grep "Dashboard"
   ```

### ✅ 積分系統驗證

1. 進入結帳頁面
2. 應該看到「自動使用積分折抵」複選框且預設勾選
3. 應該顯示「將折抵: X 分 = HK$X」
4. 檢查隱藏欄位（F12 → Elements，搜索 `kayarine_points_request`）
5. 完成訂單並檢查積分是否扣除

### 📊 數據庫驗證

```sql
-- 查詢積分日誌
SELECT * FROM wp_kayarine_points_log 
WHERE user_id = <USER_ID> 
ORDER BY date_created DESC 
LIMIT 10;

-- 查詢訂單元數據
SELECT * FROM wp_postmeta 
WHERE post_id = <ORDER_ID> 
AND meta_key LIKE '_kayarine%';
```

---

## 故障排查

### 問題 1: 會員中心仍然看不到訂單

**檢查清單**：
- [ ] 確認修改已保存
- [ ] 確認插件已重新啟用
- [ ] 查看日誌中是否有錯誤
- [ ] 確認用戶確實有訂單

**日誌檢查**：
```bash
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep -E "Dashboard|ERROR"
```

### 問題 2: 積分系統 UI 未出現

**檢查清單**：
- [ ] 確認 `class-kayarine-improved-checkout.php` 已上傳
- [ ] 確認主文件中的 `new Kayarine_Improved_Checkout()` 已添加
- [ ] 確認插件已重新啟用
- [ ] 清除所有緩存（瀏覽器 + WordPress + CDN）

**日誌檢查**：
```bash
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Improved"
```

### 問題 3: 積分未被扣除

**檢查清單**：
- [ ] 確認訂單確實應用了積分
- [ ] 確認訂單已進入 processing/completed 狀態
- [ ] 查看 `wp_kayarine_points_log` 表

**日誌檢查**：
```bash
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine"
```

---

## 回滾步驟（如有問題）

1. **停用插件**
   - WordPress 後台 → 插件 → 停用 Kayarine Booking

2. **恢復文件**
   - 刪除 `class-kayarine-improved-checkout.php`
   - 恢復 `kayarine-booking.php` 和 `class-kayarine-member-dashboard.php` 的原始版本

3. **重新啟用**
   - 啟用 Kayarine Booking

---

## 支援文檔

已提供的完整文檔：

1. **SYSTEM_WORKFLOW_ANALYSIS.md** - 系統邏輯分析
2. **DIAGNOSTIC_WORKFLOW.md** - 診斷和測試指南
3. **IMPROVED_POINTS_SYSTEM_DESIGN.md** - 設計細節
4. **IMPLEMENTATION_GUIDE_v1415.md** - 部署步驟
5. **MANUAL_DEPLOYMENT_v1415.md** - 本文檔

---

## 文件位置

所有文件都已準備在本地：

```
/Users/henrylo/Documents/GitHub/calendar/kayarine-booking/
├── includes/
│   ├── class-kayarine-member-dashboard.php      ← 已修改
│   ├── class-kayarine-improved-checkout.php     ← 新文