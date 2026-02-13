# JWT Authentication Plugin 安裝配置指南

> 為 Kayarine Next.js 前端實現完整的前後端分離認證

---

## 📦 Plugin 資訊

**Plugin 名稱**: JWT Authentication for WP-API  
**作者**: Enrique Chavez  
**版本**: 最新穩定版  
**費用**: 完全免費  
**功能**: 為 WordPress REST API 提供 JWT token 認證

---

## 🔧 安裝步驟

### 步驟 1：安裝 Plugin

#### 方法 A：通過 WordPress 後台（推薦）

```bash
1. 登入 WordPress 後台（https://kayarine.club/wp-admin）
2. 進入「外掛」→「安裝外掛」
3. 搜尋「JWT Authentication for WP REST API」
4. 點擊「立即安裝」→「啟用」
```

#### 方法 B：通過 SSH

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
cd /opt/bitnami/wordpress &&
sudo -u www-data wp plugin install jwt-authentication-for-wp-rest-api --activate
"
```

### 步驟 2：配置 JWT Secret Key

編輯 `wp-config.php` 添加 JWT 密鑰：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122
```

```bash
cd /opt/bitnami/wordpress
sudo nano wp-config.php
```

在 `/* That's all, stop editing! */` 前添加：

```php
// JWT Authentication Configuration
define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key-here-change-this');
define('JWT_AUTH_CORS_ENABLE', true);
```

**生成安全密鑰**：
```bash
# 使用 OpenSSL 生成隨機密鑰
openssl rand -base64 64
```

### 步驟 3：配置 .htaccess（Apache）

如果使用 Apache，需要啟用 Authorization header：

```bash
sudo nano /opt/bitnami/wordpress/.htaccess
```

在文件頂部添加：

```apache
# JWT Authentication
RewriteEngine on
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

### 步驟 4：允許用戶註冊

```bash
# 方法 A：WordPress 後台
1. 設定 → 一般
2. 勾選「任何人都可以註冊」
3. 新使用者預設角色：Customer
4. 儲存變更

# 方法 B：WP-CLI
sudo -u www-data wp option update users_can_register 1
sudo -u www-data wp option update default_role customer
```

---

## 🧪 測試 JWT 端點

### 測試 1：獲取 Token（登入）

```bash
curl -X POST https://kayarine.club/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{
    "username": "test@example.com",
    "password": "your-password"
  }'
```

**預期返回**：
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user_email": "test@example.com",
  "user_nicename": "test",
  "user_display_name": "Test User"
}
```

### 測試 2：驗證 Token

```bash
curl -X POST https://kayarine.club/wp-json/jwt-auth/v1/token/validate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**預期返回**：
```json
{
  "code": "jwt_auth_valid_token",
  "data": {
    "status": 200
  }
}
```

### 測試 3：使用 Token 訪問受保護端點

```bash
curl https://kayarine.club/wp-json/wp/v2/users/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🔐 安全配置（重要）

### CORS 設置

在 `wp-config.php` 中添加（如果需要跨域）：

```php
// CORS Headers for JWT
define('JWT_AUTH_CORS_ENABLE', true);

// 允許的來源（限制為您的前端域名）
header('Access-Control-Allow-Origin: https://kayarine.club');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Credentials: true');
```

### Token 過期時間

默認 7 天，可自定義：

```php
// Token 有效期（秒）
define('JWT_AUTH_EXPIRATION', 604800); // 7 天
```

---

## 📱 Next.js 前端使用

### API 端點

**1. 登入（獲取 Token）**
```
POST /wp-json/jwt-auth/v1/token
Body: { "username": "email", "password": "password" }
```

**2. 驗證 Token**
```
POST /wp-json/jwt-auth/v1/token/validate
Header: Authorization: Bearer {token}
```

**3. 刷新 Token（延長有效期）**
```
POST /wp-json/jwt-auth/v1/token/refresh
Header: Authorization: Bearer {token}
```

### 前端實現（已完成）

**文件**: [`lib/api/member.ts`](../kayarine-nextjs-frontend/lib/api/member.ts)

- `login()` - 使用 JWT 登入
- `register()` - WordPress 用戶註冊
- `getCurrentUser()` - 使用 Token 獲取用戶資料
- `logout()` - 清除 Token

---

## ⚠️ 故障排除

### 問題 1：Token 驗證失敗

**錯誤**：
```json
{
  "code": "jwt_auth_invalid_token",
  "message": "Token is invalid"
}
```

**解決方案**：
1. 確認 `JWT_AUTH_SECRET_KEY` 已設置
2. 檢查 `.htaccess` 是否正確配置
3. 重啟 Apache：`sudo /opt/bitnami/ctlscript.sh restart apache`

### 問題 2：CORS 錯誤

**錯誤**: `Access-Control-Allow-Origin` 缺失

**解決方案**：
```php
// wp-config.php
define('JWT_AUTH_CORS_ENABLE', true);
```

### 問題 3：註冊失敗

**錯誤**: `rest_cannot_create_user`

**解決方案**：
```bash
# 確認允許註冊
sudo -u www-data wp option get users_can_register
# 應該返回 1

# 如果是 0，執行：
sudo -u www-data wp option update users_can_register 1
```

---

## 📋 完整配置檢查清單

### WordPress 後台
- [ ] JWT Authentication plugin 已安裝並啟用
- [ ] 設定 → 一般 → 「任何人都可以註冊」✅
- [ ] 設定 → 一般 → 「新使用者預設角色」= Customer

### wp-config.php
- [ ] `JWT_AUTH_SECRET_KEY` 已設置（使用強密鑰）
- [ ] `JWT_AUTH_CORS_ENABLE` 已設置為 true
- [ ] CORS headers 已添加（如需要）

### .htaccess
- [ ] Authorization header rewrite 規則已添加

### 測試
- [ ] 登入端點返回 token
- [ ] Token 驗證成功
- [ ] 使用 token 可訪問 `/wp/v2/users/me`
- [ ] 註冊端點可創建新用戶

---

## 🔗 參考資源

- **Plugin 官方頁面**: https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/
- **GitHub Repository**: https://github.com/usefulteam/jwt-auth
- **WordPress REST API 文檔**: https://developer.wordpress.org/rest-api/

---

## 🚀 快速配置命令（複製即用）

```bash
# 1. 安裝 plugin
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
cd /opt/bitnami/wordpress &&
sudo -u www-data wp plugin install jwt-authentication-for-wp-rest-api --activate &&
echo '✅ JWT Plugin 已安裝'
"

# 2. 生成密鑰
echo "🔑 生成 JWT Secret Key:"
openssl rand -base64 64

# 3. 添加配置到 wp-config.php（手動執行）
# 複製上面生成的密鑰，然後：
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122
sudo nano /opt/bitnami/wordpress/wp-config.php
# 添加：
# define('JWT_AUTH_SECRET_KEY', '貼上密鑰');
# define('JWT_AUTH_CORS_ENABLE', true);

# 4. 啟用用戶註冊
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
cd /opt/bitnami/wordpress &&
sudo -u www-data wp option update users_can_register 1 &&
sudo -u www-data wp option update default_role customer &&
echo '✅ 用戶註冊已啟用'
"

# 5. 重啟 Apache
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
sudo /opt/bitnami/ctlscript.sh restart apache &&
echo '✅ Apache 已重啟'
"

# 6. 測試登入
curl -X POST https://kayarine.club/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{"username":"您的email","password":"您的密碼"}'
```

---

## 📝 版本記錄

| 日期 | 版本 | 內容 |
|------|------|------|
| 2026-02-05 | 1.0 | 初始版本：JWT Authentication 配置指南 |

---

**配置完成後，前端的登入/註冊功能將完全可用，無需跳轉頁面**
