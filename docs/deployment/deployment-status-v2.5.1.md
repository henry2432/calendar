# P0-3 & P0-4 部署狀態報告

**版本**: v2.5.1  
**日期**: 2026-02-08  
**狀態**: ⚠️ 檔案已上傳，等待插件重新載入

---

## ✅ 已完成項目

### 1. 程式碼開發
- ✅ [`class-kayarine-otp.php`](kayarine-booking/includes/class-kayarine-otp.php) - 11KB
- ✅ [`class-kayarine-auth-endpoints.php`](kayarine-booking/includes/class-kayarine-auth-endpoints.php) - 15KB（新增 6 個 API 端點）
- ✅ [`class-kayarine-pricing-api.php`](kayarine-booking/includes/class-kayarine-pricing-api.php) - 3.7KB（補充遺失檔案）
- ✅ [`kayarine-booking.php`](kayarine-booking/kayarine-booking.php) - 1.6KB（v1.5.0）

### 2. 檔案上傳到 GCP
```bash
✅ class-kayarine-otp.php → /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/
✅ class-kayarine-auth-endpoints.php → /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/
✅ class-kayarine-pricing-api.php → /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/
✅ kayarine-booking.php → /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/
✅ 檔案權限設置：www-data:www-data (644)
✅ Apache 已重啟
```

### 3. 文檔更新
- ✅ [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md) - v2.5.1 開發記錄
- ✅ [`P0-3_P0-4_API_TEST_GUIDE.md`](P0-3_P0-4_API_TEST_GUIDE.md) - 完整測試指南

---

## ⚠️ 當前問題

### 插件未重新載入
**現象**: 
- WordPress 日誌仍顯示版本 1.4.14
- REST API 端點返回 404（路由未註冊）
- 新代碼未生效

**原因分析**:
1. WordPress 物件快取（Object Cache）未清除
2. 插件檔案已更新但 WordPress 未檢測到變更
3. 需要手動停用/啟用插件來觸發重新載入

---

## 🔧 解決方案

### ⭐ 方案 1：WordPress 管理介面操作（強烈推薦）

這是最安全和最可靠的方法：

1. **登入 WordPress 管理後台**
   ```
   https://kayarine.club/wp-admin
   ```

2. **停用插件**
   - 進入「外掛」→「已安裝的外掛」
   - 找到「Kayarine Booking System」
   - 點擊「停用」

3. **啟用插件**
   - 點擊「啟用」
   - 檢查版本號是否變成 **1.5.0**

4. **清除快取**
   - 如有快取外掛（如 WP Super Cache），點擊「清除快取」
   - 或進入「設定」→「永久連結」，點擊「儲存變更」（強制刷新重寫規則）

5. **驗證部署**
   ```bash
   curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
     -H "Content-Type: application/json" \
     -d '{"email":"test@example.com"}'
   ```
   
   應返回：
   ```json
   {
     "success": true,
     "message": "驗證碼已發送到您的電子郵件",
     "dev_otp": "123456",
     "expires_in": 600
   }
   ```

---

### 方案 2：SSH 命令行操作（需要技術知識）

如果無法訪問管理介面，可嘗試以下命令：

```bash
# 連接到伺服器
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 清除 Redis/Memcached 快取（如果有）
sudo /opt/bitnami/ctlscript.sh restart redis  # 或 memcached

# 清除 WordPress 快取
cd /opt/bitnami/wordpress
rm -rf wp-content/cache/*

# 重啟所有服務
sudo /opt/bitnami/ctlscript.sh restart
```

---

### 方案 3：建立測試頁面（診斷用）

創建一個簡單的 PHP 頁面來檢查類別是否已載入：

```php
<?php
// test-otp-class.php
require_once '/opt/bitnami/wordpress/wp-load.php';

echo "Plugin Version: " . get_plugin_data('/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php')['Version'] . "\n";
echo "OTP Class Exists: " . (class_exists('Kayarine_OTP') ? 'Yes' : 'No') . "\n";
echo "Auth Endpoints Class Exists: " . (class_exists('Kayarine_Auth_Endpoints') ? 'Yes' : 'No') . "\n";

if (class_exists('Kayarine_OTP')) {
    echo "OTP Table Name: " . $wpdb->prefix . "kayarine_otp\n";
}
```

---

## 📋 部署後檢查清單

完成插件重新載入後，請執行以下檢查：

### 1. 檢查插件版本
```bash
# 日誌應顯示版本 1.5.0
ssh kayarine.server@104.199.144.122 \
  "sudo tail -20 /opt/bitnami/wordpress/wp-content/debug.log | grep Kayarine"
```

預期看到：
```
[08-Feb-2026 XX:XX:XX UTC] [Kayarine 1.5.0] Plugin initialization successful
[08-Feb-2026 XX:XX:XX UTC] [Kayarine OTP] Table created or verified: wp_kayarine_otp
```

---

### 2. 測試註冊 OTP API (P0-4)

#### 2.1 發送註冊驗證碼
```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}' | jq .
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "驗證碼已發送到您的電子郵件",
  "dev_otp": "123456",
  "expires_in": 600
}
```

#### 2.2 驗證 OTP
```bash
# 使用上一步返回的 dev_otp
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","otp_code":"123456"}' | jq .
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "驗證成功，請完成註冊",
  "verified": true
}
```

---

### 3. 測試忘記密碼 API (P0-3)

#### 3.1 發送密碼重設 OTP
```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com"}' | jq .
```

#### 3.2 驗證重設 OTP
```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/verify-reset-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","otp_code":"654321"}' | jq .
```

#### 3.3 重設密碼
```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email":"admin@example.com",
    "otp_code":"654321",
    "new_password":"NewPassword123"
  }' | jq .
```

---

### 4. 檢查資料庫表

```bash
ssh kayarine.server@104.199.144.122
mysql -u kayarine -p wordpress_db

-- 查看表結構
DESCRIBE wp_kayarine_otp;

-- 查看記錄
SELECT * FROM wp_kayarine_otp ORDER BY created_at DESC LIMIT 5;
```

**預期表結構**:
```
+------------+--------------+------+-----+-------------------+
| Field      | Type         | Null | Key | Default           |
+------------+--------------+------+-----+-------------------+
| id         | bigint(20)   | NO   | PRI | NULL              |
| email      | varchar(100) | NO   | MUL | NULL              |
| otp_code   | varchar(10)  | NO   |     | NULL              |
| otp_type   | varchar(20)  | NO   | MUL | NULL              |
| expires_at | datetime     | NO   | MUL | NULL              |
| verified   | tinyint(1)   | YES  |     | 0                 |
| created_at | datetime     | YES  |     | CURRENT_TIMESTAMP |
+------------+--------------+------+-----+-------------------+
```

---

## 📊 已部署的 API 端點

| 端點 | 方法 | 功能 | 優先級 |
|------|------|------|--------|
| `/auth/send-otp` | POST | 發送註冊驗證碼 | P0-4 |
| `/auth/verify-otp` | POST | 驗證註冊 OTP | P0-4 |
| `/auth/register` | POST | 註冊（支援 OTP） | P0-4 |
| `/auth/forgot-password` | POST | 發送密碼重設 OTP | P0-3 |
| `/auth/verify-reset-otp` | POST | 驗證密碼重設 OTP | P0-3 |
| `/auth/reset-password` | POST | 重設密碼 | P0-3 |

**基礎 URL**: `https://kayarine.club/wp-json/kayarine/v1`

---

## 🎯 下一步行動

### 立即執行（5 分鐘內）

1. ⭐ **登入 WordPress 管理介面** → 停用並重新啟用 Kayarine Booking System
2. ✅ **檢查版本號** → 確認顯示 v1.5.0
3. 🧪 **測試 API** → 使用上方的 curl 命令
4. 🗄️ **檢查資料庫** → 確認 wp_kayarine_otp 表已創建

### 後續步驟

1. **整合 Email 系統（P0-1）**
   - 選擇 SMTP 服務（Mailgun/SendGrid）
   - 設計郵件模板
   - 將 OTP 通過郵件發送

2. **開發前端 UI**
   - 註冊驗證碼頁面
   - 忘記密碼頁面
   - API 封裝函數

3. **生產環境調整**
   - 移除 `dev_otp` 欄位
   - 配置正式的 SMTP

---

## 📝 檔案清單總結

### 已部署檔案（GCP 伺服器）
```
/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/
├── kayarine-booking.php (v1.5.0) ✅
└── includes/
    ├── class-kayarine-otp.php (11KB) ✅
    ├── class-kayarine-auth-endpoints.php (15KB) ✅
    └── class-kayarine-pricing-api.php (3.7KB) ✅
```

### 本地檔案（已更新）
```
/Users/henrylo/Documents/GitHub/calendar/
├── kayarine-booking/
│   ├── kayarine-booking.php (v1.5.0)
│   └── includes/
│       ├── class-kayarine-otp.php
│       ├── class-kayarine-auth-endpoints.php
│       └── class-kayarine-pricing-api.php
├── DEVELOPMENT_LOG.md (已添加 v2.5.1 記錄)
├── P0-3_P0-4_API_TEST_GUIDE.md (新建)
└── DEPLOYMENT_STATUS_v2.5.1.md (本文件)
```

---

## ⚡ 快速命令參考

### 檢查插件狀態
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 \
  "sudo tail -20 /opt/bitnami/wordpress/wp-content/debug.log"
```

### 測試 API（插件啟用後）
```bash
# 測試註冊 OTP
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'

# 測試忘記密碼
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

### 檢查資料庫
```bash
ssh kayarine.server@104.199.144.122
mysql -u kayarine -p wordpress_db -e "SHOW TABLES LIKE 'wp_kayarine_otp';"
```

---

## 📞 需要手動操作

**重要**：由於 WordPress 快取機制，新代碼需要通過以下方式之一來觸發載入：

### 選項 A：WordPress 管理介面（推薦）
1. 訪問 https://kayarine.club/wp-admin
2. 外掛 → 已安裝的外掛
3. 停用「Kayarine Booking System」
4. 重新啟用

### 選項 B：清除所有快取
1. 登入 WordPress 管理介面
2. 找到任何快取外掛（WP Super Cache, W3 Total Cache 等）
3. 點擊「清除所有快取」
4. 進入「設定」→「永久連結」，點擊「儲存變更」

### 選項 C：完整重啟（最徹底）
```bash
ssh kayarine.server@104.199.144.122
sudo /opt/bitnami/ctlscript.sh restart
```

---

## 🎯 成功指標

當插件成功載入後，您應該看到：

### 1. 日誌顯示新版本
```
[Kayarine 1.5.0] Plugin initialization successful
[Kayarine OTP] Table created or verified: wp_kayarine_otp
```

### 2. API 端點可訪問
```bash
curl https://kayarine.club/wp-json/kayarine/v1/auth/send-otp
# 應返回 JSON 而不是 404
```

### 3. 資料庫表已創建
```sql
SHOW TABLES LIKE 'wp_kayarine_otp';
-- 應顯示 1 row
```

---

## 📈 開發進度更新

### DEVELOPMENT_SUMMARY.md 狀態
- ✅ **P0-3：會員忘記密碼** - 已完成
- ✅ **P0-4：註冊驗證碼** - 已完成
- ⏳ **P0-1：Email 系統** - 下一優先級
- ⏳ **P0-2：Guest 結帳** - v2.5.0 已完成

### 剩餘 P0 項目
只剩 **P0-1 Email 系統**（1-2 天），完成後 P0 階段全部完成！

---

## 💡 開發建議

### 前端整合（Next.js）
建議創建以下檔案：

1. **API 封裝**
```typescript
// lib/api/auth.ts
export async function sendRegistrationOTP(email: string)
export async function verifyRegistrationOTP(email: string, code: string)
export async function sendPasswordResetOTP(email: string)
export async function verifyPasswordResetOTP(email: string, code: string)
export async function resetPassword(email: string, code: string, password: string)
```

2. **UI 組件**
```typescript
// components/auth/RegisterWithOTP.tsx - 註冊流程
// components/auth/ForgotPasswordFlow.tsx - 忘記密碼流程
// components/auth/OTPInput.tsx - OTP 輸入組件
```

---

**最後更新**: 2026-02-08T18:42 UTC+8  
**部署者**: Kayarine Team  
**下一步**: 手動重新啟用插件
