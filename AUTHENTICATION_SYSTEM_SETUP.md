# 會員中心認證系統設置指南

> **創建日期**: 2026-02-06  
> **方案**: Next.js 自主 JWT 認證（方案 D）  
> **狀態**: ✅ 開發完成，待部署測試

---

## 📋 系統概述

### 架構
```
Next.js 前端
    ↓ HTTP/HTTPS
Next.js API Routes (JWT 認證)
    ↓ MySQL
WordPress 數據庫（直接查詢）
```

### 核心功能
- ✅ 用戶註冊（自動生成用戶名）
- ✅ 用戶登入（支持郵箱/用戶名）
- ✅ JWT Token 管理（7天過期）
- ✅ 密碼驗證（WordPress PHPass 格式）
- ✅ 會員資料顯示（等級、積分、消費）
- ✅ 認證保護（未登入自動跳轉）

---

## 🔧 部署步驟

### 步驟 1：配置環境變數

複製環境變數範例：
```bash
cd /var/www/kayarine-nextjs-frontend
cp .env.example .env.local
```

編輯 `.env.local`：
```bash
nano .env.local
```

必填配置：
```env
# WordPress API
NEXT_PUBLIC_WORDPRESS_API_URL=https://kayarine.club

# MySQL 數據庫（WordPress）
DB_HOST=localhost
DB_USER=wordpress_readonly
DB_PASSWORD=YOUR_ACTUAL_PASSWORD
DB_NAME=wordpress

# JWT 密鑰（生成隨機密鑰）
JWT_SECRET=使用下方命令生成
```

生成 JWT 密鑰：
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

### 步驟 2：創建只讀數據庫用戶（安全性）

連接到 MySQL：
```bash
sudo mysql -u root -p
```

執行以下 SQL（替換密碼）：
```sql
-- 創建只讀用戶
CREATE USER 'wordpress_readonly'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD_HERE';

-- 授予只讀權限
GRANT SELECT ON wordpress.wp_users TO 'wordpress_readonly'@'localhost';
GRANT SELECT ON wordpress.wp_usermeta TO 'wordpress_readonly'@'localhost';

-- 授予寫入權限（用於註冊新用戶）
GRANT INSERT ON wordpress.wp_users TO 'wordpress_readonly'@'localhost';
GRANT INSERT ON wordpress.wp_usermeta TO 'wordpress_readonly'@'localhost';

-- 刷新權限
FLUSH PRIVILEGES;

-- 退出
EXIT;
```

測試數據庫連接：
```bash
mysql -u wordpress_readonly -p wordpress -e "SELECT COUNT(*) FROM wp_users;"
```

---

### 步驟 3：安裝依賴並構建

```bash
cd /var/www/kayarine-nextjs-frontend

# 安裝新的依賴（已包含在 package.json）
npm install --legacy-peer-deps

# 構建生產版本
npm run build
```

---

### 步驟 4：重啟 Next.js 服務

```bash
# 重啟 PM2
pm2 restart kayarine-nextjs

# 查看日誌（檢查是否有錯誤）
pm2 logs kayarine-nextjs --lines 50
```

---

## 🧪 測試認證系統

### 1. 測試註冊
1. 訪問 `https://kayarine.club/login`
2. 切換到「註冊」標籤
3. 填寫：
   - 姓名：測試用戶
   - 郵箱：test@example.com
   - 密碼：至少8個字符
4. 點擊「註冊」
5. 預期結果：自動登入並跳轉到會員中心

### 2. 測試登入
1. 登出後重新訪問登入頁面
2. 使用註冊的郵箱和密碼登入
3. 預期結果：成功登入並跳轉到會員中心

### 3. 測試會員中心
1. 登入後訪問 `/member`
2. 檢查：
   - 用戶名顯示正確
   - 會員等級顯示（Bronze/Silver/Gold/Platinum）
   - 積分和消費金額顯示
3. 預期結果：顯示真實用戶數據

### 4. 測試認證保護
1. 登出
2. 直接訪問 `/member`
3. 預期結果：自動跳轉到登入頁面

### 5. 測試 Token 持久化
1. 登入後關閉瀏覽器
2. 重新打開並訪問 `/member`
3. 預期結果：仍然保持登入狀態（Token 存在 localStorage）

---

## 📂 文件結構

### 新增文件
```
kayarine-nextjs-frontend/
├── lib/
│   ├── db.ts                    # 數據庫連接層
│   └── auth.ts                  # JWT 和密碼驗證工具
├── app/api/auth/
│   ├── login/route.ts           # 登入 API
│   ├── register/route.ts        # 註冊 API
│   ├── verify/route.ts          # Token 驗證 API
│   └── me/route.ts              # 獲取當前用戶 API
├── contexts/
│   └── AuthContext.tsx          # 認證 Context Provider
└── .env.local                   # 環境變數（需手動創建）
```

### 修改文件
```
├── lib/api/member.ts            # 更新為使用新的認證 API
├── components/auth/LoginRegisterTabs.tsx  # 整合真實登入邏輯
├── app/(pages)/member/page.tsx  # 添加認證保護
└── app/layout.tsx               # 添加 AuthProvider
```

---

## 🔒 安全性說明

### 已實現的安全措施
1. **JWT Token**:
   - 256-bit 密鑰
   - 7天過期時間
   - 存儲在 localStorage（HTTPS 加密傳輸）
   
2. **密碼處理**:
   - WordPress PHPass 格式驗證
   - 新用戶使用 bcrypt hash
   - 密碼永不明文存儲

3. **數據庫安全**:
   - 只讀用戶（限制權限）
   - 參數化查詢（防 SQL 注入）
   - 連接字符串存環境變數

4. **API 安全**:
   - HTTPS 加密傳輸（已配置）
   - Authorization Bearer Token
   - 錯誤訊息統一（避免洩露信息）

### 建議的額外措施（可選）
- Rate limiting（防暴力破解）
- 登入嘗試日誌
- IP 白名單（數據庫訪問）
- Refresh token 機制

---

## 🐛 故障排除

### 問題 1：無法連接數據庫
```
Error: connect ECONNREFUSED
```

**解決方案**:
1. 檢查 `.env.local` 配置是否正確
2. 測試數據庫連接：
   ```bash
   mysql -u wordpress_readonly -p wordpress -e "SELECT 1"
   ```
3. 檢查 MySQL 是否運行：
   ```bash
   sudo systemctl status mysql
   ```

---

### 問題 2：JWT Token 無效
```
Token 無效或已過期
```

**解決方案**:
1. 確認 `.env.local` 中的 `JWT_SECRET` 與生成 Token 時一致
2. 清除瀏覽器 localStorage：
   ```javascript
   // 在瀏覽器 Console 執行
   localStorage.clear();
   ```
3. 重新登入

---

### 問題 3：密碼驗證失敗
```
郵箱或密碼錯誤
```

**可能原因**:
1. WordPress 使用舊的 MD5 hash（而非 PHPass）
2. 密碼在數據庫中損壞

**解決方案**:
1. 檢查數據庫中的密碼格式：
   ```sql
   SELECT user_login, user_pass FROM wp_users WHERE user_email = 'test@example.com';
   ```
2. 密碼應以 `$P$` 或 `$2y$` 開頭
3. 如果格式不對，在 WordPress 後台重置密碼

---

### 問題 4：會員資料不顯示
```
用戶資料為空或 null
```

**解決方案**:
1. 檢查 `wp_usermeta` 表是否有資料：
   ```sql
   SELECT * FROM wp_usermeta WHERE user_id = 1;
   ```
2. 如果沒有，系統會使用默認值（Bronze, 0 積分）

---

## 📊 數據庫結構

### 使用的表
1. **wp_users** - 用戶基本資料
   - ID, user_login, user_pass, user_email, display_name

2. **wp_usermeta** - 用戶元數據
   - total_spending（總消費）→ 計算會員等級
   - reward_points（積分）
   - trips_this_year（今年出海次數）

### 會員等級計算邏輯
```typescript
Bronze:   $0 - $999
Silver:   $1000 - $2999
Gold:     $3000 - $4999
Platinum: $5000+
```

---

## 🔗 相關文件
- [`MEMBER_CENTER_AUTHENTICATION_ROADMAP.md`](MEMBER_CENTER_AUTHENTICATION_ROADMAP.md) - 方案選擇過程
- [`JWT_AUTH_SETUP_GUIDE.md`](JWT_AUTH_SETUP_GUIDE.md) - 舊的 JWT Plugin 方案（已棄用）
- [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 標準部署流程

---

## ✅ 完成檢查清單

部署前檢查：
- [ ] `.env.local` 配置完成
- [ ] JWT_SECRET 已生成並設置
- [ ] 數據庫只讀用戶已創建
- [ ] 數據庫連接測試成功
- [ ] npm 依賴已安裝
- [ ] 生產構建成功
- [ ] PM2 服務重啟

測試檢查：
- [ ] 註冊功能正常
- [ ] 登入功能正常
- [ ] 會員中心顯示真實數據
- [ ] 未登入自動跳轉
- [ ] Token 持久化正常

---

**認證系統開發完成，等待部署測試。**
