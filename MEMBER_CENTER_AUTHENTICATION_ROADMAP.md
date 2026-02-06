# 會員中心認證系統開發路線圖

> 會員中心 UI 已完成，本文檔說明認證整合的當前狀況、問題分析和解決方案

**創建日期**: 2026-02-06  
**狀態**: 會員中心 UI 完成 ✅ | 認證系統待開發 ⏸️

---

## 📊 當前狀況總結

### ✅ 已完成部分

#### **Next.js 前端（完整部署）**
- **頁面**: `/login`, `/member`
- **組件**: 7個會員中心組件
- **UI**: 橙色主題（#FF6B35），完全符合 Figma 設計
- **參考設計**: 
  - 登入/註冊：參考 `/Users/henrylo/Documents/GitHub/calendar` 文件設計
  - 改期/取消：參考 [`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php)
- **部署狀態**: PM2 運行中，HTTPS 訪問正常

#### **相關文件**
- **前端組件**: `/Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend/components/member-dashboard/`
- **API 服務**: `/Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend/lib/api/member.ts`
- **配置文檔**: [`JWT_AUTH_SETUP_GUIDE.md`](JWT_AUTH_SETUP_GUIDE.md)

---

## ⚠️ 當前問題

### **問題 1：JWT Authentication Plugin 導致 WordPress 崩潰**

**嘗試的 Plugin**: `JWT Authentication for WP REST API`

**結果**:
- ✅ 安裝成功
- ✅ Secret Key 配置成功  
- ❌ **WordPress 後台崩潰**（Critical Error 500）
- ❌ wp-login.php 變黑畫面
- ❌ 移除 plugin 和配置後恢復正常

**結論**: 此 JWT plugin 與當前 WordPress 環境不相容

---

### **問題 2：登入/註冊功能無法使用**

**當前實現**: 重定向到 WordPress 原生頁面

**問題**:
- 登入：跳轉到 `/wp-login.php` → 404
- 註冊：跳轉到 `/my-account/` → 404

**原因**: WordPress permalink 或頁面配置問題

---

### **問題 3：WPGraphQL 生態 Plugins 找不到**

**搜尋不到的 Plugins**:
- WooGraphQL
- WPGraphQL JWT Authentication  
- WPGraphQL for WooCommerce

**可能原因**:
- Plugin 名稱不正確
- 需要從 GitHub 手動安裝
- 或使用其他替代方案

---

## 🏗️ 技術架構現況

### **當前架構**
```
Next.js 前端 (TypeScript/React)
     ↓ HTTP fetch
WordPress REST API (PHP)
     ↓
WordPress 數據庫 (MySQL)
```

**使用的 APIs**:
- `/wp-json/wc/store/products` - 產品列表
- `/wp-json/wp/v2/posts` - 文章列表
- `/wp-json/wp/v2/users/me` - 用戶資料（需認證）

---

## 💡 解決方案選項

### **方案 A：Next.js API Routes 認證**（推薦 🥇）

**架構**:
```
Next.js 前端 → Next.js API Routes → WordPress REST API
              ↑ 處理認證和 session
```

**實現**:
```typescript
// app/api/auth/login/route.ts
export async function POST(request: Request) {
  const { email, password } = await request.json();
  
  // 使用 WordPress Application Password 認證
  const auth = Buffer.from(`${email}:${appPassword}`).toString('base64');
  
  const response = await fetch('https://kayarine.club/wp-json/wp/v2/users/me', {
    headers: { 'Authorization': `Basic ${auth}` }
  });
  
  if (response.ok) {
    // 創建 Next.js session (iron-session 或 next-auth)
    return NextResponse.json({ success: true });
  }
}
```

**優點**:
- ✅ 無需任何 WordPress plugin
- ✅ 完全控制認證流程
- ✅ 使用 WordPress Application Password（內建功能）
- ✅ 1-2 天可完成

**缺點**:
- ⚠️ 需要在 WordPress 後台為每個用戶生成 Application Password

**所需 npm packages**:
- `iron-session` 或 `next-auth`

---

### **方案 B：WPGraphQL + 自定義認證**（進階 🥈）

**架構**:
```
Next.js → WPGraphQL → WordPress
```

**必要 Plugins**:
1. **WPGraphQL** ✅ 已安裝
2. **WPGraphQL for WooCommerce** ⚠️ 需手動安裝
   - GitHub: https://github.com/wp-graphql/wp-graphql-woocommerce
   - 或搜尋 "WooCommerce GraphQL"

**認證方式**: 使用 WPGraphQL 的內建 `login` mutation（無需 JWT plugin）

**GraphQL Query 範例**:
```graphql
mutation Login {
  login(input: {
    username: "user@example.com"
    password: "password"
  }) {
    authToken
    user {
      id
      name
      email
    }
  }
}
```

**優點**:
- ✅ GraphQL 更靈活
- ✅ 只請求需要的數據
- ✅ WordPress 官方推薦
- ✅ 內建 login mutation（無需 JWT plugin）

**缺點**:
- ⚠️ 需要重寫所有 API 調用（REST → GraphQL）
- ⚠️ 學習曲線（GraphQL 語法）
- ⚠️ 3-5 天工作量

**所需 npm packages**:
- `@apollo/client`
- `graphql`

---

### **方案 C：Python FastAPI 中間層**（最靈活 🥉）

**架構**:
```
Next.js → FastAPI (Python) → WordPress DB (直接查詢)
```

**實現**:
```python
# backend/auth.py
from fastapi import FastAPI, HTTPException
import bcrypt

@app.post("/api/auth/login")
async def login(email: str, password: str):
    # 直接查詢 WordPress wp_users 表
    user = db.query("SELECT * FROM wp_users WHERE user_email = %s", (email,))
    
    # 驗證密碼（WordPress 使用 PHP password hash）
    if check_wordpress_password(password, user['user_pass']):
        # 生成 JWT token (Python)
        token = create_jwt_token(user['ID'])
        return {"success": True, "token": token}
    
    raise HTTPException(401, "登入失敗")
```

**優點**:
- ✅ 完全控制
- ✅ 可以重用 calendar/backend/ 代碼
- ✅ Python 處理複雜邏輯更容易
- ✅ 避開所有 WordPress plugin 問題

**缺點**:
- ⚠️ 需要部署 Python 服務
- ⚠️ 需要處理 WordPress 密碼 hash 格式
- ⚠️ 5-7 天工作量

**所需**:
- FastAPI
- MySQL connector
- passlib (WordPress password hash)

---

## 🎯 建議下一步

### **短期方案**（立即可用）:

**1. 修復 WordPress 登入頁面**
```bash
# WordPress 後台
設定 → 永久連結 → 儲存變更
WooCommerce → 狀態 → 工具 → 建立預設頁面
```

**2. 使用簡單重定向**（會員中心 UI 已實現此方式）
- 登入：跳轉到 `/wp-login.php`  
- 註冊：跳轉到 `/wp-login.php?action=register`

---

### **中期方案**（1-2 天）:

**實現 Next.js API Routes + Application Password 認證**
- 無需 WordPress plugin
- 穩定可靠
- 完全控制

---

### **長期方案**（可選）:

**遷移到 WPGraphQL**（如需要）
- 更現代化的 API
- 更好的類型安全
- WordPress 官方推薦

---

## 📋 Prompt 引導（開始新任務）

```
請閱讀 MEMBER_CENTER_AUTHENTICATION_ROADMAP.md 了解會員中心認證系統的當前狀況和解決方案。

會員中心 UI 已完成（7個組件，2個頁面），現在需要實現認證功能。

根據文檔中的方案分析，實現【選擇方案 A/B/C】的認證系統。

參考文件：
- calendar/kayarine-booking/includes/class-kayarine-member-dashboard.php（改期/取消邏輯）
- kayarine-nextjs-frontend/lib/api/member.ts（當前 API 實現）
- JWT_AUTH_SETUP_GUIDE.md（JWT 配置，僅供參考）

目標：實現完整的登入/註冊功能，讓會員中心可以顯示真實用戶數據。
```

---

## 📁 相關文件清單

**已完成**:
- `kayarine-nextjs-frontend/components/member-dashboard/*.tsx` (7個組件)
- `kayarine-nextjs-frontend/app/(pages)/login/page.tsx`
- `kayarine-nextjs-frontend/app/(pages)/member/page.tsx`

**需要修改**（認證整合時）:
- `kayarine-nextjs-frontend/lib/api/member.ts`
- `kayarine-nextjs-frontend/components/common/Header.tsx`

**參考**:
- `calendar/kayarine-booking/includes/class-kayarine-member-dashboard.php`
- `JWT_AUTH_SETUP_GUIDE.md`
- `FIGMA_TO_DEPLOYMENT_GUIDE.md`

---

## 🔗 部署記錄

| 日期 | 內容 | 狀態 |
|------|------|------|
| 2026-02-06 | 會員中心 UI 轉換完成 | ✅ 已部署 |
| 2026-02-06 | JWT Plugin 測試 | ❌ 導致崩潰，已移除 |
| 2026-02-06 | WordPress 原生登入方式 | ⏸️ 頁面 404 |
| 待定 | 認證系統實現 | ⏳ 待開始 |

---

**會員中心 UI 轉換任務已完成。認證系統整合為新的獨立任務。**
