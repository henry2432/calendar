# Kayarine 功能開發路線圖 2026-02

**創建日期**: 2026-02-08  
**最後更新**: 2026-02-08
**當前版本**: v2.4.7
**狀態**: 第一階段完成 (部分未實測) | 進行第二階段開發

---

## 📊 開發優先級矩陣

### 🔴 P0 優先級（關鍵路徑 - 1-2 週）

| # | 功能 | 依賴 | 複雜度 | 預計天數 | 狀態 | 說明 |
|---|------|------|--------|---------|------|------|
| 1 | **Email 系統（Gmail SMTP）** | 無 | 🟡 中 | 1 天 | ✅ 已完成 | **✅ 已確定：Gmail**。所有通知的基礎。⚠️ 未實測 |
| 2 | **Guest 結帳系統** | Email 系統 | 🔴 高 | 3 天 | ✅ 已完成 | **⚠️ 從零開始設計**（Grok 無代碼）。核心轉換漏斗。⚠️ 未實測 |
| 3 | **會員忘記密碼** | Email 系統 | 🟡 中 | 1 天 | ✅ 已完成 | 用戶流失補救。⚠️ 未實測 |
| 4 | **註冊驗證碼** | Email 系統 | 🟡 中 | 1 天 | ✅ 已完成 | 防止濫用註冊。⚠️ 未實測 |

### 🟠 P1 優先級（核心功能 - 2-3 週）

| # | 功能 | 依賴 | 複雜度 | 預計天數 | 狀態 | 說明 |
|---|------|------|--------|---------|------|------|
| 5 | **Google 登入** | 無 | 🟡 中 | 1-2 天 | ⏳ 待開發 | 不需 Cloud 專案。與 Guest 結帳並行開發 |
| 6 | **Stripe 支付集成** | Guest 結帳系統 | 🟡 中 | 2 天 | ⏳ 待開發 | 收款機制 |
| 7 | **Guest 改期頁面** | Guest 結帳系統 | 🟡 中 | 1-2 天 | ⏳ 待開發 | 必須在 Guest 結帳完成後 |
| 8 | **Pay/FPS 教學頁面** | 無 | 🟢 低 | 0.5 天 | ⏳ 待開發 | 靜態內容 |

### 🟡 P2 優先級（增強功能 - 2-3 週）

| # | 功能 | 依賴 | 複雜度 | 預計天數 | 狀態 | 說明 |
|---|------|------|--------|---------|------|------|
| 9 | **多語言系統（中英日）** | 無 | 🟡 中 | 2-3 天 | ⏳ 待開發 | hreflang + i18n 框架 |
| 10 | **旅程簡介 HTML 解譯** | 無 | 🟡 中 | 1 天 | ⏳ 待開發 | HTML → 正式文段 |
| 11 | **Google Calendar 同步** | 無 | 🔴 高 | 2 天 | ❓ 可選 | 需 Cloud 專案（可降至 P3） |
| 12 | **Google Ads / Meta Pixel** | 無 | 🟡 中 | 1 天 | ⏳ 待開發 | 轉換追蹤 |

### 🟢 P3 優先級（優化和完善）

| # | 功能 | 複雜度 | 預計天數 | 狀態 | 說明 |
|---|------|--------|---------|------|------|
| 13 | **Cookie 政策** | 🟢 低 | 0.5 天 | ⏳ 待開發 | Cookie 横幅 |
| 14 | **UI 小改動 (Faicon)** | 🟢 低 | 0.5 天 | ⏳ 待開發 | 圖標更新 |
| 15 | **結帳前積分通知** | 🟢 低 | 0.5 天 | ⏳ 待開發 | 提示用戶 |

---

## 🎯 建議開發次序（按依賴關係）

### **第一階段（1.5 週）- 基礎設施 [P0]** ✅ 已完成（⚠️ 部分未實測）

```
✅ 週一: Email 系統設置 (Gmail SMTP)
  ✅ 配置 Gmail App Password（2FA）
  ✅ 安裝 WP Mail SMTP 外掛
  ✅ 建立 class-kayarine-emails.php
  ⚠️ 測試郵件發送 (未實測)

✅ 週二-週四: Guest 結帳系統（從零設計 ⚠️）
  ✅ API 設計：端點、參數、驗證
  ✅ Guest 會話管理（Cookie + Database）
  ✅ 訂單創建邏輯
  ✅ 前端表單：Email、名字、電話、日期、設備
  ✅ Email 系統集成
  ⚠️ 端到端測試 (未實測)

✅ 週五-週一: 會員忘記密碼 + 驗證碼
  ✅ 密碼重設工作流 + OTP 生成
  ✅ 郵件發送
  ⚠️ 完整流程測試 (未實測)

**實測建議**：
- [ ] Email 發送測試（檢查收件匣、垃圾桶）
- [ ] Guest 結帳完整流程（表單提交 → 訂單創建 → Email 接收）
- [ ] 忘記密碼流程（重設連結 → 密碼更新 → 登入驗證）
- [ ] OTP 驗證（接收驗證碼 → 輸入驗證 → 註冊完成）
```

### **第二階段（1.5 週）- 支付和認證 [P1]** 🔄 進行中

```
⏳ 週二-週三: Stripe 支付集成
  ├─ WooCommerce Stripe 外掛配置
  ├─ Guest 結帳支付流程
  ├─ 前端 Stripe Elements 整合
  └─ Webhook 處理

⏳ 週三-週四: Google 登入 (並行開發可行)
  ├─ Google OAuth2 配置（無需 Cloud 專案）
  ├─ Next.js 認證集成
  └─ 自動用戶建立/綁定

⏳ 週五-週一: Guest 改期頁面
  ├─ Token 驗證機制
  ├─ 日期選擇 + 庫存檢查
  ├─ 改期郵件
  └─ 前端改期頁面

```

### **第三階段（2 週）- 增強功能 [P2]**

```
週二-週三: 多語言系統（中英日）
  ├─ Next.js i18n 設置
  ├─ hreflang 標籤
  └─ 靜態內容翻譯

週四: 旅程簡介 HTML 解譯
  └─ HTML 解析器 + 組件

週五-週一: Google Ads / Meta Pixel
  ├─ GTM 配置
  └─ 轉換追蹤

❓ Google Calendar（可選，見下面說明）
```

### **第四階段（1 週）- 完善 [P3]**

```
Cookie 政策、UI 小改動、積分通知
```

---

## 📋 詳細開發檢查清單

### 1️⃣ Email 系統 - Gmail SMTP（P0-1） ✅ 決定

**為什麼選 Gmail？**
- ✅ 免費，無費用
- ✅ 可靠的交付
- ✅ WordPress 簡易整合
- ✅ App Password 更安全

**步驟**:

1. **Gmail 帳戶配置**
   - [ ] 啟用 2FA（兩步驟驗證）
     - 訪問 https://myaccount.google.com
     - 左側選「安全性」
     - 啟用「兩步驟驗證」
   - [ ] 生成 **App Password**
     - 訪問 https://myaccount.google.com/apppasswords
     - 應用程式：「郵件」
     - 裝置：「Windows 電腦」（或其他）
     - 複製生成的 **16 位密碼**（不是 Gmail 密碼！）

2. **WordPress 外掛安裝**
   - [ ] 到 WordPress 後台 → 外掛 → 搜尋「WP Mail SMTP」
   - [ ] 安裝並啟用

3. **WP Mail SMTP 配置**
   - [ ] 後台 → WP Mail SMTP 設定
   - [ ] **SMTP Host**: `smtp.gmail.com`
   - [ ] **Port**: `587` (TLS)
   - [ ] **Encryption**: TLS
   - [ ] **Username**: Gmail 帳戶（完整郵箱）
   - [ ] **Password**: 生成的 App Password（不是 Gmail 密碼）
   - [ ] **From Email**: kayarine 郵箱
   - [ ] **From Name**: Kayarine
   - [ ] 點「發送測試郵件」驗證

4. **建立 PHP 郵件類**
   - [ ] 建立檔案：`kayarine-booking/includes/class-kayarine-emails.php`
   - [ ] 實現方法：
     - `send_order_confirmation($order_id)` - 訂單確認
     - `send_password_reset($user_email, $reset_link)` - 密碼重設
     - `send_otp($email, $otp_code)` - 驗證碼
     - `send_reschedule_link($order_id, $token)` - 改期連結

5. **測試**
   - [ ] 發送測試郵件（檢查垃圾桶）
   - [ ] 驗證模板渲染
   - [ ] 檢查變數替換

**Gmail App Password 注意事項**:
```
❌ 錯誤：使用 Gmail 登入密碼
✅ 正確：使用 App Password（16 位）

App Password 類似：
xmzk ljqo nkdp aqle
```

**檔案**:
- `kayarine-booking/includes/class-kayarine-emails.php` (新建)

---

### 2️⃣ Guest 結帳系統（P0-2）- 從零開始設計 ⚠️

**重點**：Grok 無代碼，需完整設計實現

**A. 後端 API 設計**

- [ ] **建立端點**
  - [ ] `POST /wp-json/kayarine/v1/guests/checkout`
  - [ ] 參數驗證（Email、名字、電話、日期、設備數量）
  - [ ] 庫存檢查
  - [ ] 黑名單日期檢查

- [ ] **Guest 會話管理（選擇一種）**
  - [ ] **方案 A（推薦）**：Session 表
    - [ ] 建立 `kayarine_guest_sessions` 表
    - [ ] 欄位：`session_id`, `email`, `phone`, `created_at`, `expires_at`
    - [ ] 訂單保存 `session_id` 到 meta
  - [ ] **方案 B**：加密 Cookie
    - [ ] 使用 WordPress nonce 機制
    - [ ] 無需資料庫表

- [ ] **訂單創建**
  - [ ] `wc_create_order()` 無 `customer_id`
  - [ ] 保存：guest_email, guest_phone, guest_session_id 到訂單 meta
  - [ ] 訂單狀態：`pending`（待支付）

- [ ] **郵件觸發**
  - [ ] 訂單確認郵件發送
  - [ ] 包含改期連結 token

**B. 前端表單**

- [ ] **建立 Guest 結帳頁面**：`components/rental-services/GuestCheckoutForm.tsx`
  - [ ] 郵件欄位（驗證）
  - [ ] 姓名欄位
  - [ ] 電話欄位（香港格式：+852）
  - [ ] 日期選擇（禁用黑名單日期）
  - [ ] 設備數量（檢查庫存）

- [ ] **表單驗證**
  ```typescript
  - Email: 格式驗證
  - Phone: +852 或 8-9 開頭
  - Date: 不能是過去日期、黑名單日期
  - Quantity: 不能超過庫存
  ```

- [ ] **提交邏輯**
  - [ ] 調用後端 `/guests/checkout`
  - [ ] 接收 `session_id`
  - [ ] 儲存到 localStorage
  - [ ] 進入支付頁面

**C. 整合**

- [ ] **Email 系統集成**
  - [ ] 訂單創建後自動發送確認郵件
  - [ ] 包含 Guest 改期連結

- [ ] **支付流程**
  - [ ] 支付頁面保持 session
  - [ ] 支付成功後更新訂單狀態
  - [ ] 重新發送確認郵件（已支付）

**D. 測試**

- [ ] 完整結帳流程（端到端）
- [ ] Session 過期處理
- [ ] 庫存不足提示
- [ ] 郵件接收
- [ ] 瀏覽器相容性

**檔案**:
- 前端：`kayarine-nextjs-frontend/components/rental-services/GuestCheckoutForm.tsx`
- 後端：`kayarine-booking/includes/class-kayarine-guest-checkout.php`
- API：更新 `class-kayarine-rest-api.php`

**API 合約示例**:
```json
POST /wp-json/kayarine/v1/guests/checkout
{
  "email": "guest@example.com",
  "name": "John Doe",
  "phone": "+85298765432",
  "product_id": 123,
  "quantity": 2,
  "date": "2026-02-20"
}

Response 200:
{
  "session_id": "guest_sess_xxxx",
  "order_id": 789,
  "total": 500,
  "currency": "HKD",
  "status": "pending"
}
```

---

### 3️⃣ Stripe 支付集成（P1）

- [ ] **外掛安裝**
  - [ ] WordPress 後台 → 外掛 → 搜尋「WooCommerce Stripe」
  - [ ] 安裝官方 Stripe 外掛
  - [ ] 配置 API 金鑰（測試 + 線上）

- [ ] **前端整合**
  - [ ] 更新 `CheckoutForm.tsx`
  - [ ] Stripe Elements 集成
  - [ ] 卡號欄位、有效期、CVC

- [ ] **支付流程**
  - [ ] 建立支付意圖（Payment Intent）
  - [ ] 處理支付成功/失敗
  - [ ] Webhook 處理

- [ ] **Guest 結帳支付**
  - [ ] 保持 Guest session
  - [ ] 支付後關聯訂單

- [ ] **測試**
  - [ ] 測試卡號支付（`4242 4242 4242 4242`）
  - [ ] 成功和失敗流程

**檔案**:
- 更新 `CheckoutForm.tsx` 或建立 `StripeCheckoutForm.tsx`

---

### 4️⃣ Google 登入（P1）

**✅ 不需要 Google Cloud 專案**（只需 OAuth2 ID）

- [ ] **Google OAuth2 設置**
  - [ ] 訪問 https://console.developers.google.com（不要 Console Cloud）
  - [ ] 建立 OAuth2 認證（Web 應用程式）
  - [ ] 用戶端 ID、用戶端密碼

- [ ] **前端**
  - [ ] 使用 `@react-oauth/google` 套件
  - [ ] Google 登入按鈕
  - [ ] Token 獲取

- [ ] **後端**
  - [ ] 驗證 Google token
  - [ ] 自動用戶建立/綁定
  - [ ] Session 管理

- [ ] **測試**
  - [ ] 新用戶註冊
  - [ ] 現有用戶綁定

**檔案**:
- 前端：`kayarine-nextjs-frontend/app/(pages)/login/page.tsx` (更新)
- API：新建 `/api/auth/google`

---

### 5️⃣ Guest 改期頁面（P1）

- [ ] **後端**
  - [ ] `/wp-json/kayarine/v1/guests/reschedule` 端點
  - [ ] Token 驗證
  - [ ] 新日期庫存檢查
  - [ ] 訂單 meta 更新

- [ ] **前端**
  - [ ] 郵件連結 → `/reschedule/[token]`
  - [ ] 日期選擇器
  - [ ] 新價格計算
  - [ ] 確認對話

- [ ] **郵件**
  - [ ] 改期確認郵件

- [ ] **測試**
  - [ ] Token 過期驗證
  - [ ] 日期庫存檢查
  - [ ] 端到端流程

**檔案**:
- 前端：`kayarine-nextjs-frontend/app/(pages)/reschedule/[token]/page.tsx`
- 後端：更新 `class-kayarine-rest-api.php`

---

### 6️⃣ 會員忘記密碼（P0-3）

- [ ] **後端**
  - [ ] 密碼重設 token 生成
  - [ ] 郵件發送
  - [ ] Token 驗證和密碼更新

- [ ] **前端**
  - [ ] 忘記密碼表單
  - [ ] 密碼重設頁面
  - [ ] 驗證訊息

- [ ] **測試**
  - [ ] 郵件接收
  - [ ] Token 過期
  - [ ] 密碼更新驗證

**使用 WordPress 原生機制 + 自訂郵件模板**

---

### 7️⃣ 註冊驗證碼（P0-4）

- [ ] **後端**
  - [ ] `class-kayarine-otp.php` - OTP 生成器
  - [ ] 6 位數字驗證碼
  - [ ] 郵件發送
  - [ ] 驗證邏輯（5-10 分鐘過期）

- [ ] **前端**
  - [ ] OTP 輸入框
  - [ ] 重新發送按鈕（60秒冷卻）
  - [ ] 驗證反饋

- [ ] **測試**
  - [ ] OTP 生成和驗證
  - [ ] 過期處理
  - [ ] 重複驗證限制

**檔案**:
- `kayarine-booking/includes/class-kayarine-otp.php`

---

### 8️⃣ 多語言系統（P2-1）

- [ ] **Next.js i18n 設置**
  - [ ] 安裝 `next-i18next`
  - [ ] 配置語言路由：`/en`, `/ja`, `/zh-TW`
  - [ ] 建立翻譯檔案結構

- [ ] **hreflang 標籤**
  - [ ] 每個語言版本添加
  - [ ] 自動生成或手動配置

- [ ] **靜態內容翻譯**
  - [ ] 導航、頁面標題、CTA 文本
  - [ ] Pay/FPS 教學頁面

- [ ] **後端支持**
  - [ ] API 語言參數：`?lang=en|ja|zh-TW`

- [ ] **測試**
  - [ ] 語言切換
  - [ ] SEO hreflang 驗證

**注意**：暫不翻譯旅程簡介、設備描述等動態資料

**檔案**:
- `kayarine-nextjs-frontend/public/locales/` (新建)
- `next-i18next.config.js` (新建)

---

### 9️⃣ 旅程簡介 HTML 解譯（P2-2）

- [ ] **安裝套件**
  - [ ] `npm install html-to-text`

- [ ] **後端處理**
  - [ ] HTML 清理（白名單標籤）
  - [ ] 移除危險指令碼

- [ ] **前端組件**
  - [ ] `HtmlContent.tsx` 組件
  - [ ] 安全渲染 HTML

- [ ] **測試**
  - [ ] XSS 防護
  - [ ] 樣式保留

**檔案**:
- `kayarine-nextjs-frontend/components/common/HtmlContent.tsx`

---

### 🔟 Google Calendar 同步（P2-3）- ❓ 可選

**為什麼需要 Google Cloud 專案?**

Google Calendar API 需要以下認證方式之一：
1. **API 金鑰**（需要 Cloud 專案）
2. **OAuth2 認證**（需要 Cloud 專案）
3. **Service Account**（需要 Cloud 專案）

所以無論哪種方式，都必須建立 Google Cloud 專案。

**建議**：
- 如果功能不是核心需求，可 **推至 P3 或後續版本**
- 實現難度較高，開發時間長
- 可能影響開發進度

**如果決定進行**：

- [ ] **Google Cloud 專案建立**
  - [ ] 訪問 https://console.cloud.google.com
  - [ ] 建立新專案
  - [ ] 啟用 Google Calendar API
  - [ ] 建立 OAuth2 認證（Web 應用）

- [ ] **後端實現**
  - [ ] 安裝 `google-api-php-client`
  - [ ] OAuth2 流程
  - [ ] 事件建立/更新/刪除
  - [ ] 訂單狀態 hooks

- [ ] **前端**
  - [ ] 授權按鈕
  - [ ] 同步狀態顯示

- [ ] **測試**
  - [ ] 授權流程
  - [ ] 事件建立
  - [ ] 時區處理

**檔案**:
- `kayarine-booking/includes/class-kayarine-google-calendar.php`

**現在建議**：⏸️ 暫不實現（推至 P3）

---

### 1️⃣1️⃣ Google Ads / Meta Pixel（P2-4）

- [ ] **GTM 設置**
  - [ ] Google Tag Manager 容器建立
  - [ ] 容器代碼部署

- [ ] **轉換追蹤**
  - [ ] Guest 結帳開始
  - [ ] 訂單完成
  - [ ] 自訂參數（金額、類型）

- [ ] **Meta Pixel**
  - [ ] Pixel ID 部署
  - [ ] 事件追蹤
  - [ ] Conversion API（後端）

- [ ] **測試**
  - [ ] GTM 除錯工具
  - [ ] Meta 驗證工具

**檔案**:
- Next.js layout/全域配置

---

### 1️⃣2️⃣ Cookie 政策（P3-1）

- [ ] **前端**
  - [ ] Cookie 同意橫幅
  - [ ] 多語言支持
  - [ ] 選擇持久化

- [ ] **文檔更新**
  - [ ] 隱私政策
  - [ ] Cookie 聲明

---

### 1️⃣3️⃣ UI 小改動 - Faicon（P3-2）

- [ ] **圖標庫升級**
  - [ ] 安裝新套件（Font Awesome / Tabler）
  - [ ] 替換現有圖標

---

### 1️⃣4️⃣ 結帳前積分通知（P3-3）

- [ ] **前端通知**
  - [ ] 結帳審查頁面添加
  - [ ] 標識不可用項目
  - [ ] 詳細解釋

---

## 🚀 預計總耗時

| 階段 | 預計天數 | 備註 |
|------|---------|------|
| P0（Email + Guest 結帳 + 驗證） | 6-7 天 | Guest 結帳 3 天（從零設計） |
| P1（Stripe + Google + 改期） | 7-8 天 | 並行開發，精簡工時 |
| P2（多語言 + HTML + 廣告） | 6-8 天 | Calendar 可跳過 |
| P3（Cookie + UI + 通知） | 2 天 | |
| **總計** | **21-25 天（4.5 週）** | |

---

## 📂 新增和修改檔案清單

### 新增檔案

```
後端:
  ✅ kayarine-booking/includes/class-kayarine-emails.php (P0-1)
  ✅ kayarine-booking/includes/class-kayarine-guest-checkout.php (P0-2)
  ✅ kayarine-booking/includes/class-kayarine-otp.php (P0-4)
  ⏳ kayarine-booking/includes/class-kayarine-google-calendar.php (P2-3, 可選)

前端:
  ✅ kayarine-nextjs-frontend/components/rental-services/GuestCheckoutForm.tsx (P0-2)
  ✅ kayarine-nextjs-frontend/app/(pages)/reschedule/[token]/page.tsx (P1-3)
  ✅ kayarine-nextjs-frontend/components/common/HtmlContent.tsx (P2-2)
  ✅ kayarine-nextjs-frontend/public/locales/[語言].json (P2-1)
  ✅ next-i18next.config.js (P2-1)

API Routes:
  ✅ kayarine-nextjs-frontend/app/api/auth/google/route.ts (P1-1)
```

### 修改檔案

```
後端:
  ✅ kayarine-booking/includes/class-kayarine-rest-api.php (添加新端點)
  ✅ kayarine-booking/kayarine-booking.php (加載新類)

前端:
  ✅ kayarine-nextjs-frontend/components/rental-services/CheckoutForm.tsx (支持 Stripe)
  ✅ kayarine-nextjs-frontend/app/(pages)/login/page.tsx (添加 Google 登入)
  ✅ Next.js layout (GTM + Cookie)
```

---

## ✅ 立即行動清單（下週一前）

在開始開發前確認：

- [x] **Email 服務商** ✅ Gmail SMTP
- [x] **Guest 結帳** ✅ 從零設計（Grok 無代碼）
- [ ] **Google Cloud 專案** - 決定是否實現 Calendar 同步
  - [ ] 如果實現：建立專案並獲得 API 金鑰
  - [ ] 如果不實現：將 Calendar 推至 P3
- [ ] **Stripe 帳戶** - 測試 API 金鑰已設置
- [ ] **本地開發環境** - WordPress + Next.js 就緒
- [ ] **Git 分支策略** - 確認命名規則

---

## 🔗 相關文檔

- [`DEVELOPMENT_SUMMARY.md`](DEVELOPMENT_SUMMARY.md) - 快速參考
- [`MEMBER_CENTER_AUTHENTICATION_ROADMAP.md`](MEMBER_CENTER_AUTHENTICATION_ROADMAP.md) - 認證架構
- [`SYSTEM_INTEGRATION_SUMMARY.md`](SYSTEM_INTEGRATION_SUMMARY.md) - 現有系統
- [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 部署流程
- [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md) - 歷史記錄

---

## 📝 備註

1. **Gmail App Password** - 記得用 16 位密碼，不是 Gmail 密碼
2. **Guest 結帳複雜度提升** - 從零設計，加上 1 天開發時間
3. **Google Cloud 可選** - Calendar 同步不是核心功能
4. **並行開發** - Google 登入、Pay/FPS 教學可同時進行
5. **每個功能完成後更新 `DEVELOPMENT_LOG.md`** - 記錄版本、時間戳、問題

---

**最後更新**：2026-02-08 19:11 UTC+8
**下一次審視**：2026-02-15（P1 進度檢查）

---

## 📌 第一階段完成狀態總結

**完成日期**: 2026-02-08
**實際開發時間**: 約 6 天（符合預期）

### ✅ 已實現功能
1. **Email 系統 (Gmail SMTP)** - `class-kayarine-emails.php` 已建立
2. **Guest 結帳系統** - 完整 API + 前端表單實現
3. **會員忘記密碼** - 密碼重設流程
4. **註冊驗證碼 (OTP)** - 6 位數驗證碼系統

### ⚠️ 待實測項目
- Email 發送功能（Gmail SMTP 配置）
- Guest 結帳端到端流程
- 忘記密碼完整流程
- OTP 驗證流程

### 📝 建議
在第二階段開發前，建議先進行實測以確保基礎功能穩定。這將有助於：
- 及早發現問題
- 避免在第二階段開發時回頭修復
- 確保支付集成建立在穩定的基礎上

