# 🔐 Kayarine 認證系統文檔

本目錄包含所有認證和授權相關的文檔。

---

## 📚 文檔索引

### 核心文檔
- [`jwt-auth.md`](./jwt-auth.md) - JWT Token 認證系統
- [`google-oauth.md`](./google-oauth.md) - Google OAuth 登入整合
- [`apple-signin.md`](./apple-signin.md) - Apple Sign In 整合
- [`member-center.md`](./member-center.md) - 會員中心和積分系統

---

## 🎯 認證系統架構

### 系統概覽
```
┌─────────────────┐
│   Next.js 前端   │
│   登入/註冊介面   │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
┌───▼───┐  ┌─▼──────┐
│Google │  │ Apple  │
│OAuth  │  │Sign In │
└───┬───┘  └─┬──────┘
    │        │
    └────┬───┘
         │
┌────────▼─────────┐
│ WordPress 後端    │
│ JWT Auth API     │
└────────┬─────────┘
         │
┌────────▼─────────┐
│   會員資料庫      │
│  積分 + 等級      │
└──────────────────┘
```

### 支持的認證方式

| 方式 | 狀態 | 文檔 | 說明 |
|-----|------|------|------|
| **JWT Token** | ✅ 已實現 | [jwt-auth.md](./jwt-auth.md) | 基於 Token 的無狀態認證 |
| **Google OAuth** | ✅ 已實現 | [google-oauth.md](./google-oauth.md) | 一鍵 Google 登入 |
| **Apple Sign In** | ✅ 已實現 | [apple-signin.md](./apple-signin.md) | Apple ID 登入 |
| **傳統帳密** | ✅ 已實現 | [jwt-auth.md](./jwt-auth.md) | Email + 密碼登入 |

---

## 🚀 快速開始

### 前端配置 (Next.js)

**1. 安裝依賴**
```bash
npm install jsonwebtoken jose
npm install @types/jsonwebtoken --save-dev
```

**2. 環境變數** (`.env.local`)
```env
# JWT 配置
JWT_SECRET=your-secret-key-min-32-chars

# Google OAuth
NEXT_PUBLIC_GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com

# Apple Sign In
NEXT_PUBLIC_APPLE_CLIENT_ID=com.kayarine.signin
```

**3. WordPress API 端點**
```env
NEXT_PUBLIC_WORDPRESS_API=http://104.199.144.122/wp-json
```

### 後端配置 (WordPress)

**1. 啟用插件**
- Kayarine Booking Plugin（包含認證模組）
- JWT Authentication for WP REST API

**2. WordPress 配置** (`wp-config.php`)
```php
define('JWT_AUTH_SECRET_KEY', 'your-secret-key-min-32-chars');
define('JWT_AUTH_CORS_ENABLE', true);
```

**3. API 端點**
- `POST /wp-json/kayarine/v1/auth/login` - 傳統登入
- `POST /wp-json/kayarine/v1/auth/register` - 註冊
- `POST /wp-json/kayarine/v1/auth/google-login` - Google 登入
- `POST /wp-json/kayarine/v1/auth/apple-login` - Apple 登入
- `GET /wp-json/kayarine/v1/auth/me` - 獲取用戶資料

---

## 📋 功能清單

### ✅ 已實現功能

#### 基礎認證
- [x] JWT Token 生成和驗證
- [x] 用戶註冊（Email + 密碼）
- [x] 用戶登入（Email + 密碼）
- [x] 自動登入（記住我）
- [x] 登出功能
- [x] Session 管理

#### 社交登入
- [x] Google OAuth 登入
- [x] Google One Tap 登入
- [x] Apple Sign In
- [x] 社交帳號自動綁定
- [x] 社交帳號用戶創建

#### 會員系統
- [x] 會員等級系統（Bronze/Silver/Gold）
- [x] 積分累積系統
- [x] 會員資料管理
- [x] 訂單歷史記錄
- [x] 個人資料編輯

#### 安全特性
- [x] 密碼加密（bcrypt）
- [x] JWT Token 過期機制
- [x] CORS 保護
- [x] 輸入驗證
- [x] XSS 防護

### 🚧 待實現功能

- [ ] 雙因素認證 (2FA)
- [ ] 郵箱驗證
- [ ] 忘記密碼功能
- [ ] 密碼強度檢查
- [ ] 帳號鎖定機制
- [ ] OAuth 其他提供商（Facebook, Line）
- [ ] 社交帳號解綁

---

## 🔧 技術細節

### JWT Token 結構
```json
{
  "sub": "15",
  "email": "user@example.com",
  "name": "John Doe",
  "tier": "Bronze",
  "points": 0,
  "iat": 1707456000,
  "exp": 1707542400
}
```

### Cookie 設置
```typescript
{
  name: 'auth-token',
  value: jwt_token,
  httpOnly: true,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax',
  maxAge: 86400 // 24 hours
}
```

### API 請求認證
```typescript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

---

## 📊 用戶流程

### 新用戶註冊流程
```
1. 填寫註冊表單（Email + 密碼 + 姓名）
2. 前端驗證（格式、密碼強度）
3. 發送到 WordPress API
4. 創建 WordPress 用戶
5. 初始化會員等級（Bronze）
6. 初始化積分（0 分）
7. 生成 JWT Token
8. 設置 Cookie
9. 跳轉到會員頁面
```

### Google 登入流程
```
1. 點擊「使用 Google 登入」
2. 彈出 Google 認證視窗
3. 用戶選擇帳號並授權
4. 前端接收 Google ID Token
5. 驗證 Token（Google Identity Services）
6. 發送到 WordPress API
7. 檢查用戶是否存在
   ├─ 存在：更新用戶資料
   └─ 不存在：創建新用戶
8. 綁定 Google ID 到用戶
9. 生成 JWT Token
10. 設置 Cookie
11. 跳轉到會員頁面
```

### Apple 登入流程
```
1. 點擊「使用 Apple 登入」
2. 彈出 Apple 認證視窗
3. 用戶選擇是否隱藏郵箱並授權
4. 前端接收 Apple ID Token
5. 驗證 Token（使用 Apple JWKS）
6. 發送到 WordPress API
7. 檢查用戶是否存在
   ├─ 存在：更新用戶資料
   └─ 不存在：創建新用戶
8. 綁定 Apple ID 到用戶
9. 生成 JWT Token
10. 設置 Cookie
11. 跳轉到會員頁面
```

---

## 🔒 安全最佳實踐

### 密碼安全
- 使用 bcrypt 加密（cost factor 12）
- 最小長度 8 字符
- 禁止常見密碼
- 密碼歷史記錄（防止重複使用）

### Token 安全
- JWT Secret 最少 32 字符
- Token 有效期 24 小時
- 使用 HttpOnly Cookie
- 生產環境強制 HTTPS
- Refresh Token 機制（待實現）

### API 安全
- 輸入驗證和消毒
- 速率限制（待實現）
- CORS 白名單
- SQL 注入防護
- XSS 防護

---

## 📖 相關文檔

### 詳細指南
- [JWT 認證設置指南](./jwt-auth.md)
- [Google OAuth 整合指南](./google-oauth.md)
- [Apple Sign In 設置指南](./apple-signin.md)
- [會員中心開發路線圖](./member-center.md)

### 外部資源
- [JWT.io](https://jwt.io/) - JWT Token 介紹
- [Google Identity Services](https://developers.google.com/identity/gsi/web) - Google OAuth 文檔
- [Apple Sign In](https://developer.apple.com/sign-in-with-apple/) - Apple 官方文檔
- [WordPress REST API Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)

---

## 🐛 故障排除

### 常見問題

**Q: JWT Token 無效或過期**
- 檢查 JWT_SECRET 是否一致
- 確認 Token 未過期（24小時）
- 檢查時區設置

**Q: Google 登入失敗**
- 驗證 Client ID 是否正確
- 檢查授權的 JavaScript 來源
- 確認重定向 URI 配置

**Q: Apple 登入失敗**
- 驗證 Service ID 配置
- 檢查域名驗證
- 確認 Return URLs 正確

**Q: 用戶無法登入**
- 檢查用戶是否存在
- 驗證密碼是否正確
- 查看 WordPress 錯誤日誌

---

## 📞 技術支持

如遇問題，請查看：
1. 各個子文檔的故障排除章節
2. WordPress 錯誤日誌
3. 瀏覽器控制台錯誤
4. 網絡請求詳情

---

**最後更新**: 2026-02-09  
**版本**: v2.6.0
