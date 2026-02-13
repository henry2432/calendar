# Kayarine 開發進度摘要 2026-02-08

**文檔用途**: 快速參考當前開發狀態、優先級和下一步行動  
**最後更新**: 2026-02-08 06:05 UTC+8  
**當前版本**: v2.4.6

---

## 📊 項目現況

### ✅ 已完成功能（v2.4.6）

| 功能 | 狀態 | 備註 |
|------|------|------|
| 庫存系統 | ✅ 完成 | 三層快取策略、黑名單/白名單 |
| 積分系統 | ✅ 完成 | 六階段處理流程 |
| 會員中心 | ✅ 完成 | UI + 訂單查詢 + 改期/取消 |
| 訂單用戶關聯 | ✅ 完成 | 已登入用戶正確關聯 |
| 黑名單日期禁用 | ✅ 完成 | 旅程和租借頁面都適用 |
| 前端會員中心 UI | ✅ 完成 | Next.js + 橙色主題 |

### ⏳ 待開發功能

**共 15 項功能**
- 🔴 P0（4項）：Email、Guest 結帳、忘記密碼、驗證碼 - **5 天**
- 🟠 P1（4項）：Google 登入、Guest 改期、Stripe、Pay/FPS教學 - **8 天**
- 🟡 P2（4項）：多語言、HTML 解譯、Calendar 同步、廣告追蹤 - **10 天**
- 🟢 P3（3項）：Cookie、UI、積分通知 - **3 天**

**總計開發時間**: ~26 天（5.5 週）

---

## 🎯 核心開發路徑

### 臨界路徑分析

```
Email 系統
    ↓
Guest 結帳系統 ← Stripe 支付（並行）
    ↓
Guest 改期頁面
```

**關鍵里程碑**：
1. **Email 系統** ← 優先！（所有通知的基礎）
2. **Guest 結帳** ← 核心轉換漏斗
3. **Stripe 支付** ← 收款機制
4. **多語言** ← SEO 和市場擴展

---

## 📋 按優先級排列

### 🔴 P0 - 本週開始（第一階段）

```
1. Email 系統         [█████░░░░░░░░░░░░░] 1-2 天
   ├─ SMTP 配置 (Mailgun/SendGrid)
   ├─ class-kayarine-emails.php
   └─ 郵件模板

2. Guest 結帳系統      [█████░░░░░░░░░░░░░] 2-3 天 ⚠️ 複雜
   ├─ 複製 Grok 邏輯
   ├─ Guest 會話管理
   └─ 前端表單 + 驗證

3. 會員忘記密碼        [█░░░░░░░░░░░░░░░░░] 1 天
   └─ 密碼重設工作流

4. 註冊驗證碼         [█░░░░░░░░░░░░░░░░░] 1 天
   └─ OTP 生成 + 驗證
```

**下週完成目標**: 以上 4 項（缺少則無法進行第二階段）

---

### 🟠 P1 - 第二階段（2-3 週）

```
5. Google 登入        [░░░░░░░░░░░░░░░░░░] 1-2 天
   └─ OAuth2 + Next.js API Routes

6. Stripe 支付        [░░░░░░░░░░░░░░░░░░] 2 天 (需 P0 完成)
   ├─ WooCommerce Stripe 外掛
   └─ Webhook 處理

7. Guest 改期頁面      [░░░░░░░░░░░░░░░░░░] 1-2 天 (需 P0 完成)
   ├─ Token 驗證
   ├─ 日期庫存檢查
   └─ 改期郵件

8. Pay/FPS 教學       [░░░░░░░░░░░░░░░░░░] 0.5 天
   └─ 靜態頁面
```

---

### 🟡 P2 - 第三階段（2-3 週）

```
9. 多語言 (中英日)     [░░░░░░░░░░░░░░░░░░] 2-3 天
   ├─ next-i18next 設置
   ├─ hreflang 標籤
   └─ 翻譯文件

10. HTML 解譯         [░░░░░░░░░░░░░░░░░░] 1 天
    └─ html-to-text + React 組件

11. Google Calendar   [░░░░░░░░░░░░░░░░░░] 2 天 🔴 複雜
    ├─ OAuth2 授權
    └─ 事件同步

12. 廣告追蹤          [░░░░░░░░░░░░░░░░░░] 1 天
    ├─ Google Ads Pixel
    └─ Meta Pixel
```

---

### 🟢 P3 - 第四階段（1 週）

```
13. Cookie 政策       [░░░░░░░░░░░░░░░░░░] 0.5 天

14. UI 小改動         [░░░░░░░░░░░░░░░░░░] 0.5 天
    └─ Faicon 更新

15. 積分通知          [░░░░░░░░░░░░░░░░░░] 0.5 天
    └─ 結帳前提示
```

---

## 🔗 依賴關係圖

```
┌─────────────────────┐
│  Email 系統 (P0-1)  │  ← 最高優先
└──────────┬──────────┘
           ↓
┌──────────────────────────┐
│  Guest 結帳 (P0-2)       │
│  ├─ Guest 改期 (P1-3)    │
│  └─ Stripe (P1-2)        │
└──────────┬───────────────┘
           ↓
┌──────────────────────────┐
│  忘記密碼 (P0-3)         │
│  驗證碼 (P0-4)           │
│  Google 登入 (P1-1)      │
└──────────────────────────┘


獨立開發（無依賴）：
- Pay/FPS 教學 (P1-4)
- 多語言 (P2-1) 
- Cookie (P3-1)
- UI 小改動 (P3-2)
```

---

## 📂 檔案建立清單

### 新增檔案（需建立）

| 檔案 | 用途 | 優先級 |
|------|------|--------|
| `kayarine-booking/includes/class-kayarine-emails.php` | Email 系統 | P0-1 |
| `kayarine-booking/includes/class-kayarine-otp.php` | OTP 驗證 | P0-4 |
| `kayarine-booking/includes/class-kayarine-google-calendar.php` | Calendar 同步 | P2-3 |
| `kayarine-nextjs-frontend/components/rental-services/GuestCheckoutForm.tsx` | Guest 結帳 UI | P0-2 |
| `kayarine-nextjs-frontend/app/(pages)/reschedule/[token]/page.tsx` | Guest 改期頁面 | P1-3 |
| `kayarine-nextjs-frontend/components/common/HtmlContent.tsx` | HTML 解譯組件 | P2-2 |
| `kayarine-nextjs-frontend/public/locales/` | 翻譯文件 | P2-1 |

### 修改檔案（需更新）

| 檔案 | 變更 | 優先級 |
|------|------|--------|
| `class-kayarine-rest-api.php` | 添加 Guest 結帳、改期端點 | P0-2, P1-3 |
| `CheckoutForm.tsx` | 支持 Guest 模式 | P0-2 |
| `kayarine-booking.php` | 加載新類 | P0-1+ |
| Next.js 全域 layout | GTM + Cookie 追蹤 | P2-4, P3-1 |

---

## 🚀 立即行動（下週一開始）

### ✅ 確認清單

在開始開發前確認以下事項：

- [ ] **Email 服務商選擇**
  - [ ] Mailgun / SendGrid / AWS SES / 自託管？
  - [ ] API 金鑰已獲得
  - [ ] 發件人 email 已驗證

- [ ] **Guest 結帳邏輯**
  - [ ] 從 Grok 獲取結帳代碼
  - [ ] 理解 Guest 會話管理邏輯
  - [ ] 確認資料庫表結構

- [ ] **Stripe 帳戶**
  - [ ] 測試 API 金鑰已設置
  - [ ] WooCommerce Stripe 外掛已測試

- [ ] **Google 服務**
  - [ ] Google Cloud 專案建立
  - [ ] OAuth2 認證已配置

- [ ] **開發環境**
  - [ ] 本地 WordPress 環境就緒
  - [ ] Next.js dev server 運行中
  - [ ] Git 分支策略確認

---

## 📞 溝通清單

**需要澄清的項目**：

1. **Guest 結帳邏輯** - 從 Grok copy 代碼
   - Grok 項目位置？
   - 代碼是 PHP / JS？

2. **Email 服務商** - 選擇哪個？
   - 成本考慮？
   - 既有契約？

3. **Google Calendar** - 是否必需？
   - 用戶是否要求此功能？
   - 優先級是否可降低至 P3？

4. **多語言動態內容** - 如何翻譯？
   - 自動翻譯（Google Translate API）？
   - 手動翻譯？
   - 先翻譯靜態頁面？

---

## 📈 進度追蹤

### 預計里程碑

| 日期 | 里程碑 | 預期成果 |
|------|--------|---------|
| 2026-02-16 | P0 完成 | Email + Guest 結帳 + 驗證系統就緒 |
| 2026-02-26 | P1 完成 | Stripe 支付 + Google 登入 + Guest 改期 |
| 2026-03-12 | P2 完成 | 多語言 + Calendar 同步 + 廣告追蹤 |
| 2026-03-15 | P3 完成 | 所有 15 項功能完成 |

### 追蹤方式

- 每個功能完成後更新 [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md)
- 記錄版本號、時間戳、遇到的問題
- 部署到 GCP 後記錄部署詳情

---

## 🔧 技術棧參考

### 後端（PHP）

```
WordPress 核心
├── REST API (v1)
├── WooCommerce
│   ├── Orders
│   ├── Products
│   └── Customers
├── 郵件系統 (SMTP)
└── 自訂 Hooks
```

### 前端（Next.js + TypeScript）

```
Next.js 14+ (App Router)
├── React 18+
├── TailwindCSS
├── API Routes (認證中介)
└── 外掛套件
    ├── next-i18next (多語言)
    ├── iron-session (Session)
    ├── stripe/react-stripe-js (支付)
    └── react-google-login (Google)
```

### 外部服務

```
✅ 已配置
├── Stripe (支付)
├── Google OAuth2
├── WordPress HTTPS
└── GCP Bitnami

❓ 待配置
├── Email 服務商 (SMTP)
├── Google Calendar API
├── Google Analytics / Ads
└── Meta Pixel
```

---

## ⚠️ 風險和注意事項

### 高風險項目

| 項目 | 風險 | 緩解策略 |
|------|------|---------|
| Guest 結帳 | 代碼來源不明 | 事先檢查 Grok 代碼品質 |
| Google Calendar | 時區問題 | 充分測試多時區情景 |
| 多語言 | 翻譯品質 | 使用專業翻譯或人工審核 |

### 已知問題

- 🔴 JWT Plugin 與現有 WordPress 不相容（已確認，不使用）
- ⚠️ WordPress 登入/註冊 404（需檢查 Permalink 設置）
- ⏳ 等待 Guest 結帳邏輯（來自 Grok）

---

## 📚 參考文檔

| 文檔 | 用途 |
|------|------|
| [`FEATURE_DEVELOPMENT_ROADMAP.md`](FEATURE_DEVELOPMENT_ROADMAP.md) | 詳細開發檢查清單 |
| [`MEMBER_CENTER_AUTHENTICATION_ROADMAP.md`](MEMBER_CENTER_AUTHENTICATION_ROADMAP.md) | 認證架構選項 |
| [`SYSTEM_INTEGRATION_SUMMARY.md`](SYSTEM_INTEGRATION_SUMMARY.md) | 現有系統架構 |
| [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) | 部署流程 |
| [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md) | 歷史記錄 |

---

## 💡 建議

### 優化開發流程

1. **並行開發**: Google 登入 和 Guest 結帳 可同時進行（無依賴）
2. **UI 準備**: 先設計 Guest 結帳、改期、Email 確認信的 UI
3. **測試環境**: 在 GCP staging 環境進行完整測試後部署生產
4. **文檔優先**: 每個功能前先寫 API 文檔，方便前後端溝通
5. **版本控制**: 
   - Feature 分支: `feature/guest-checkout`
   - Release 分支: `release/v2.5.0`
   - Hotfix 分支: `hotfix/email-bug`

### 團隊協作建議

- 確定前後端開發人員
- 定義 API contract（入參、出參、錯誤碼）
- 建立 Staging 環境用於聯調
- 週會同步進度

---

**最後更新**: 2026-02-08 06:05 UTC+8  
**下一次審視**: 2026-02-15（P0 進度檢查）

