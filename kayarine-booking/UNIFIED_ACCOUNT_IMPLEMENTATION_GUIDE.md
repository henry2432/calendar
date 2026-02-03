# WooCommerce 統一帳戶系統實施指南

## 完成的開發工作

✅ **已創建新文件：**
- [`kayarine-booking/includes/class-kayarine-woocommerce-customizer.php`](includes/class-kayarine-woocommerce-customizer.php)
  - 整合所有帳戶功能（登入、註冊、儀表板）
  - 自動應用 Kayarine 橙色主題（#FF8C42）
  - 添加「會員進度」自定義帳戶端點
  - 包含完整的 CSS 和 JavaScript

✅ **已修改的文件：**
- [`kayarine-booking/kayarine-booking.php`](kayarine-booking.php) - 加載新的 customizer 類

---

## 部署步驟（按順序執行）

### 步驟 1: 上傳新文件到伺服器

上傳以下文件：
```
kayarine-booking/
├── includes/
│   └── class-kayarine-woocommerce-customizer.php (新建)
└── kayarine-booking.php (已更新)
```

### 步驟 2: 在 WordPress 後台創建 `/account/` 頁面

**操作路徑：**
1. 進入 **WordPress 後台** → **頁面** → **新增頁面**
2. **頁面標題：** `我的帳戶` 或 `Account`
3. **頁面 Slug：** `account`（很重要！）
4. **頁面內容：** 添加以下 shortcode：
   ```
   [woocommerce_my_account]
   ```
5. **發佈**

### 步驟 3: 重新整理 WordPress 重寫規則（非常重要！）

重新整理 WordPress 的 permalink 結構，使自定義端點生效：

**選項 A（推薦）：進入 WordPress 後台**
1. 進入 **設定** → **固定連結**
2. 點擊 **儲存變更**（不需要改變任何設定，只是重新整理）
3. 返回您的網站，測試功能

**選項 B：通過代碼**
```php
// 在 WordPress 後台「外觀」→「自訂代碼」或在主題的 functions.php 中添加
flush_rewrite_rules();
```

### 步驟 4: 測試功能

#### 4.1 測試未登入狀態（登入/註冊表單）

1. **以訪客身份訪問 `/account/`**
   - 應該看到：**登入** 和 **免費註冊** 標籤頁
   - 標籤頁應該有橙色底線（#FF8C42）
   - 登入表單應該可以輸入

2. **點擊「免費註冊」標籤頁**
   - 應該顯示：益處卡片（累積積分、輕鬆管理、專屬折扣）
   - 顯示 WooCommerce 註冊表單

3. **測試表單樣式**
   - 輸入框應該有橙色焦點效果
   - 登入/註冊按鈕應該是橙色漸變背景 + 白色字體

#### 4.2 測試已登入狀態（帳戶導航 + 會員進度）

1. **以已登入用戶身份訪問 `/account/`**
   - 應該看到帳戶導航菜單（左側或上方，取決於 WooCommerce 主題）
   - 菜單項應包括：
     - Dashboard（儀表板）
     - 🏅 會員進度（新項目）
     - Orders（訂單）
     - 其他標準項目
     - Logout（登出）

2. **點擊「會員進度」菜單項**
   - 顯示用戶頭像（橙色漸變圓形）
   - 顯示會員等級和用戶名
   - 顯示積分和儲值金統計
   - 顯示升級進度條（橙色漸變）
   - 顯示會員權益列表

3. **測試響應式設計**
   - 在**手機** (480px) 上測試
   - 在**平板** (768px) 上測試
   - 所有元素應該正確對齐和縮放

---

## 重要：舊頁面處理

### 刪除舊頁面（在測試完成後）

**待刪除的頁面：**
- ❌ `/login/` 
- ❌ `/register/`
- ❌ `/member/`

**推薦做法：**
1. **不要立即刪除** - 先設置 301 重定向
2. 在舊頁面上添加重定向代碼：
   ```php
   // 在舊頁面的 PHP 模板或通過插件添加
   wp_redirect( home_url( '/account/' ), 301 );
   exit;
   ```
3. 等待 1-2 週，確保搜索引擎已更新索引
4. 然後刪除舊頁面

### 廢棄的 Shortcode

以下 shortcode 現在已廢棄，但仍可保留以防需要（如果沒有使用，可刪除）：
- ❌ `[kayarine_login_register]` - 由 WooCommerce 登入表單替代
- ⚠️ `[kayarine_member_dashboard_v2]` - 仍在使用但可整合

---

## 故障排除

### 問題 1: 頁面顯示空白或「未找到」

**原因：** 重寫規則未刷新

**解決：**
1. 進入 **設定** → **固定連結** → **儲存變更**
2. 清除瀏覽器快取（Ctrl+Shift+Delete）
3. 訪問 `/account/` 或 `/account/?p=PAGE_ID`（其中 PAGE_ID 是頁面 ID）

### 問題 2: 登入/註冊標籤頁不工作

**原因：** jQuery 未加載或 JavaScript 錯誤

**解決：**
1. 檢查瀏覽器控制台（F12）是否有 JavaScript 錯誤
2. 確保 WooCommerce 已啟用
3. 嘗試停用其他插件，檢查是否有衝突

### 問題 3: 樣式不正確（顏色、間距等）

**原因：** 主題 CSS 可能覆蓋了自定義樣式

**解決：**
1. 在瀏覽器 Inspector（F12）中檢查 CSS 覆蓋情況
2. 增加 CSS 特異性或使用 `!important`
3. 清除所有快取（插件快取、CDN 快取、瀏覽器快取）

### 問題 4: 會員進度菜單項不顯示

**原因：** WooCommerce 版本可能不支援自定義端點

**解決：**
1. 檢查 WooCommerce 版本（需要 3.5+）
2. 檢查 WordPress debug.log 中是否有錯誤
3. 嘗試重新整理重寫規則（參考步驟 3）

---

## 文件說明

### class-kayarine-woocommerce-customizer.php

**核心功能：**

| 方法 | 功能 |
|------|------|
| `add_custom_endpoint()` | 註冊 `kayarine-membership` 自定義端點 |
| `login_form_wrapper_start()` | 在登入表單前添加標籤頁容器 HTML |
| `register_form_wrapper_start()` | 切換到註冊表單標籤頁 |
| `render_registration_benefits()` | 顯示益處卡片（累積積分、輕鬆管理、專屬折扣） |
| `customize_account_menu()` | 在帳戶導航中添加「會員進度」菜單項 |
| `render_membership_dashboard()` | 顯示完整的會員進度儀表板 |
| `enqueue_custom_styles()` | 加載 CSS 和 JavaScript |
| `get_custom_css()` | 定義所有自定義樣式（橙色主題） |
| `get_tab_switching_js()` | jQuery 標籤頁切換邏輯 |

**Hook 整合：**
- `woocommerce_login_form_start` - 添加標籤頁 HTML
- `woocommerce_register_form_before` - 顯示益處 + 打開註冊標籤頁
- `woocommerce_register_form_after` - 關閉標籤頁容器
- `woocommerce_account_menu_items` - 添加自定義菜單項
- `woocommerce_account_kayarine-membership_endpoint` - 渲染會員進度內容
- `wp_enqueue_scripts` - 加載樣式和腳本

---

## 性能最佳實踐

### 1. 使用頁面快取

建議使用以下快取插件之一：
- **WP Super Cache** - 簡單易用
- **W3 Total Cache** - 功能豐富
- **LiteSpeed Cache** - 高性能

**快取設定：**
- 排除 `/account/*` 頁面（已登入用戶）
- 允許 `/account/` 匿名訪問的快取

### 2. 異步加載 JavaScript

已優化：JavaScript 在 footer 中加載，不會阻止頁面渲染

### 3. 內聯樣式

已優化：CSS 通過 `wp_add_inline_style()` 內聯到頁面中，減少 HTTP 請求

---

## 後續改進建議

### 短期（1-2 週）
- [ ] 添加社交登入（Google、Facebook）
- [ ] 實現密碼重置流程定制
- [ ] 添加帳戶驗證流程

### 中期（1 個月）
- [ ] 集成支付方式管理
- [ ] 添加地址簿管理
- [ ] 實現用戶通知偏好設定

### 長期（2-3 個月）
- [ ] 開發移動應用登入
- [ ] 實現 OAuth2 單點登入（SSO）
- [ ] 添加進階會員分析儀表板

---

## 需要幫助？

如有任何問題，請提供：
1. **瀏覽器控制台錯誤信息**（F12 → Console）
2. **WordPress debug.log 內容**
3. **使用的主題和 WooCommerce 版本**
4. **截圖或視頻**

祝部署順利！🚀
