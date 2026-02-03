# Kayarine 登錄界面重新設計 - 部署指南

## 部署概況

已完成對 kayarine-booking 外掛的核心修改，實現了自定義標籤頁式登錄/註冊界面，集成了 WordPress 用戶帳戶系統，並使用 Kayarine 品牌色彩（紫色 #7B68EE 和橙色 #FF8C42）。

---

## 需要部署的檔案

### 1. 新建檔案
```
kayarine-booking/includes/class-kayarine-auth-integration.php
```
- **作用**：提供自定義 `[kayarine_login_register]` 短代碼
- **功能**：
  - 標籤頁式登錄/註冊界面
  - WordPress 用戶認證整合
  - 會員儀表板顯示（已登入用戶）
  - 會員權益網格顯示

### 2. 更新檔案
```
kayarine-booking/kayarine-booking.php
```
- **變更**：第 22 行添加了 `require_once` 語句以包含新的認證整合類
```php
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-auth-integration.php';
```

### 3. 更新檔案
```
kayarine-booking/assets/css/style.css
```
- **新增**：約 900 行的認證界面 CSS 樣式
  - 紫色 (#7B68EE) 標籤頁和標題
  - 橙色 (#FF8C42) 按鈕（漸變效果）
  - 響應式設計（768px、480px 斷點）
  - 無深色模式

---

## 部署步驟

### 步驟 1：備份現有檔案
在上傳前，建議備份以下檔案：
- `wp-content/plugins/kayarine-booking/kayarine-booking.php`
- `wp-content/plugins/kayarine-booking/assets/css/style.css`

### 步驟 2：上傳新檔案
使用 FTP/SFTP 或 WordPress 文件管理器：

1. 創建新檔案：`wp-content/plugins/kayarine-booking/includes/class-kayarine-auth-integration.php`
   - 複製本地的 `kayarine-booking/includes/class-kayarine-auth-integration.php` 到伺服器

### 步驟 3：更新現有檔案
1. 上傳更新後的 `kayarine-booking.php` 到 `wp-content/plugins/kayarine-booking/`
   - 覆蓋現有檔案

2. 上傳更新後的 `style.css` 到 `wp-content/plugins/kayarine-booking/assets/css/`
   - 覆蓋現有檔案

### 步驟 4：驗證權限
確保所有檔案的權限設置正確（通常為 644）。

### 步驟 5：清除緩存
如果使用了緩存外掛（如 WP Super Cache、W3 Total Cache），請清除快取。

---

## 使用方法

部署後，在任何 WordPress 頁面或文章中使用以下短代碼：

### 登錄/註冊界面
```
[kayarine_login_register]
```

**功能**：
- 顯示標籤頁式登錄/註冊界面
- 自動檢測用戶登入狀態
- 已登入用戶顯示會員儀表板

### 會員儀表板（替代方案）
```
[kayarine_member_dashboard]
```

**功能**：
- 僅對已登入用戶顯示
- 顯示歡迎訊息
- 會員操作按鈕（預約、編輯檔案、登出）

---

## 設計特性

### 品牌色彩
- **主色**：紫色 #7B68EE（標籤頁、標題、焦點狀態）
- **輔色**：深紫色 #6A5ACD（漸變效果）
- **動作色**：橙色 #FF8C42（按鈕、連結）
- **懸停色**：深橙色 #FF7A3D

### 響應式設計
- **桌面** (900px+)：完整佈局
- **平板** (768px-899px)：優化的標籤頁寬度
- **手機** (480px-767px)：堆疊佈局
- **小屏幕** (<480px)：極小化設計

### 無深色模式
根據您的需求，此設計不包含深色模式媒體查詢。

---

## 測試清單

部署後，請驗證以下功能：

- [ ] 短代碼 `[kayarine_login_register]` 正常顯示
- [ ] 標籤頁可以切換（登入 ↔ 註冊）
- [ ] 登入表單提交有效
- [ ] 密碼重置連結正常運作
- [ ] 已登入用戶顯示會員儀表板
- [ ] 紫色和橙色品牌色彩正確顯示
- [ ] 在手機上測試響應式設計
- [ ] 在平板上測試響應式設計
- [ ] 在桌面上測試響應式設計
- [ ] 表單驗證正常運作
- [ ] 沒有 JavaScript 控制台錯誤

---

## 故障排除

### 問題：短代碼未顯示
**解決方案**：
1. 確認 `class-kayarine-auth-integration.php` 已上傳到 `includes/` 目錄
2. 確認 `kayarine-booking.php` 中的 `require_once` 語句正確
3. 重新啟用外掛（停用 → 啟用）
4. 清除 WordPress 快取

### 問題：登入按鈕無反應
**解決方案**：
1. 檢查瀏覽器控制台是否有 JavaScript 錯誤
2. 確認 jQuery 已加載
3. 確認 `style.css` 已正確上傳

### 問題：顏色不正確
**解決方案**：
1. 清除瀏覽器快取（Ctrl+Shift+Delete）
2. 清除 WordPress 快取外掛
3. 在瀏覽器開發人員工具中檢查實際顏色值

### 問題：響應式設計不工作
**解決方案**：
1. 檢查主題 CSS 是否覆蓋了響應式樣式
2. 在 `style.css` 中使用 `!important` 已添加
3. 確認視口中繼標籤在主題中

---

## 技術細節

### WordPress 集成
- 使用 `wp_login_url()` 進行安全登入重定向
- 使用 `wp_lostpassword_url()` 進行密碼重置
- 使用 `is_user_logged_in()` 檢查登入狀態
- 使用 `wp_logout_url()` 進行登出

### WooCommerce 集成
- 檢查 `woocommerce_enable_myaccount_registration` 設定
- 連結到 WooCommerce My Account 頁面進行註冊
- 使用 `wc_get_account_endpoint_url()` 獲取帳戶端點

### 安全性
- 所有用戶輸入都已進行 `sanitize_text_field()`
- 所有 URL 都已進行 `esc_url()`
- 所有 HTML 輸出都已進行 `esc_html()`
- 短代碼使用標準 WordPress 輸出緩衝（`ob_start()` / `ob_get_clean()`）

---

## 支持與反饋

如果遇到問題或需要進一步的自定義，請檢查：
1. WordPress 錯誤日誌（`wp-content/debug.log`）
2. 瀏覽器開發人員工具控制台
3. 外掛相容性（禁用其他外掛測試）

---

## 版本資訊

- **修改日期**：2026-01-27
- **Kayarine 版本**：1.4.1
- **WordPress 要求**：5.0+
- **PHP 要求**：7.4+

