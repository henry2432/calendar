# Kayarine 統一帳戶系統 - 快速部署指南（方案 A）

## ✅ 最優方案：單一 /account/ 頁面

使用 `[kayarine_account]` shortcode，根據用戶登入狀態自動切換：
- **未登入** → 顯示登入/免費註冊標籤頁（橙色主題）
- **已登入** → 顯示 WooCommerce 帳戶導航 + 會員進度儀表板

---

## 📋 部署步驟（3 步完成）

### 步驟 1️⃣：上傳文件到伺服器

上傳以下 2 個文件：
```
kayarine-booking/
├── includes/
│   └── class-kayarine-woocommerce-customizer.php  ← 已更新（1000+ 行）
└── kayarine-booking.php  ← 已更新（加載新 class）
```

**上傳方式：**
- FTP 上傳，或
- WordPress 後台 → 插件編輯器 → 編輯文件

### 步驟 2️⃣：修改 /account/ 頁面（WordPress 後台）

1. **進入頁面編輯**
   - **頁面** → **所有頁面** → 找到 `account` 頁面
   
2. **修改頁面內容**
   - **刪除：** `[woocommerce_my_account]`
   - **添加：** `[kayarine_account]`
   
3. **更新頁面**

### 步驟 3️⃣：重新整理 WordPress 重寫規則

1. 進入 **設定** → **固定連結**
2. 點擊 **儲存變更**（無需改變任何設定，只是重新整理）
3. 完成！

---

## ✔️ 驗證部署成功

### 未登入狀態（訪問 /account/）
應該看到：
- ✅ 橙色漸變頭部「Kayarine 會員中心」
- ✅ 「🔐 會員登入」和「✨ 免費註冊」標籤頁
- ✅ 登入表單（電郵、密碼、記住我）
- ✅ 所有按鈕和輸入框都是橙色主題（#FF8C42）

### 已登入狀態（登入後訪問 /account/）
應該看到：
- ✅ WooCommerce 帳戶導航菜單
- ✅ 菜單包含「🏅 會員進度」（新增項目）
- ✅ 點擊「會員進度」→ 顯示會員儀表板
  - 用戶頭像 + 名字 + 等級
  - 積分和儲值金統計
  - 升級進度條（橙色漸變）
  - 會員權益列表

### 響應式測試
在以下設備上測試：
- [ ] 手機 (480px 寬) - 單列布局
- [ ] 平板 (768px 寬) - 2 列布局
- [ ] 桌面 (1200px+) - 3 列益處卡片

---

## 🔑 核心 Shortcode：[kayarine_account]

使用新的 shortcode 而不是 WooCommerce 的，優勢：

| 功能 | 舊方案 | 新方案 |
|------|--------|--------|
| UI 定制 | ❌ 受限 | ✅ 完全自定義 |
| 登入/註冊 | ❌ 2 個頁面 | ✅ 同一頁面 |
| 色彩主題 | ❌ 默認灰色 | ✅ 橙色主題 |
| 標籤頁 | ❌ 無 | ✅ 有（會員進度） |
| 代碼維護 | ❌ 分散 | ✅ 集中在 customizer class |

---

## 📞 遇到問題？

### 問題 1：頁面仍顯示默認 WooCommerce UI

**原因：** shortcode 未改為 `[kayarine_account]`

**解決：**
1. 確認 /account/ 頁面內容為 `[kayarine_account]`
2. 清除快取（瀏覽器 + WordPress 插件快取）
3. 硬刷新（Ctrl+Shift+Delete）

### 問題 2：按鈕無法點擊或登入失敗

**原因：** jQuery 未加載或 nonce 驗證失敗

**解決：**
1. 在瀏覽器 F12 → Console 查看錯誤
2. 確保 WordPress 的 jQuery 已加載
3. 檢查 `wp_nonce_field()` 是否正確生成

### 問題 3：登入後不重定向

**原因：** 重寫規則未刷新

**解決：**
1. 進入 **設定** → **固定連結** → **儲存變更**
2. 再次嘗試登入

### 問題 4：會員進度菜單項不顯示

**原因：** WooCommerce 帳戶頁面未正確加載

**解決：**
1. 確認 WooCommerce 已安裝並啟用
2. 確認 `is_account_page()` 返回 true
3. 檢查 WordPress debug.log 中是否有錯誤

---

## 📝 文件說明

### class-kayarine-woocommerce-customizer.php

**核心方法：**

| 方法 | 功能 |
|------|------|
| `render_kayarine_account_shortcode()` | 根據登入狀態返回不同內容 |
| `render_login_register_ui()` | 未登入時的登入/註冊 UI |
| `render_logged_in_account()` | 已登入時的帳戶頁面 |
| `handle_custom_login()` | AJAX 登入處理 |
| `handle_custom_register()` | AJAX 註冊處理 |
| `customize_account_menu()` | 添加會員進度菜單項 |
| `render_membership_dashboard()` | 會員進度儀表板 |

---

## 🎯 後續改進（可選）

在部署成功後，可以考慮：

1. **添加社交登入**（Google、Facebook）
2. **集成更多會員功能**（地址簿、支付方式管理）
3. **優化登入後的重定向流程**
4. **添加雙因素驗證（2FA）**
5. **實現忘記密碼流程定制**

---

## ✨ 就這樣！

只需 3 個簡單步驟，您就有了一個現代化、統一的帳戶系統。祝部署順利！🚀
