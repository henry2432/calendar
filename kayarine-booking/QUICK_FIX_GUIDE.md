# Kayarine 登錄界面 - 快速修復指南

## 已識別的問題

1. **標籤頁無法切換**（登入 ↔ 註冊）
2. **註冊面板沒有正確顯示**

## 立即修復步驟

### 步驟 1：上傳更新的檔案

您需要上傳以下更新的檔案到伺服器：

**檔案**：`kayarine-booking/includes/class-kayarine-auth-integration.php`

**變更內容**：
- ✅ 改進的 JavaScript 標籤頁切換（支援原生 DOM API 和 jQuery）
- ✅ 修復註冊邏輯以檢查 WordPress `users_can_register` 設定
- ✅ 改進的錯誤訊息顯示

### 步驟 2：驗證 WordPress 設定

進入 **WordPress 後台 → 設定 → 一般**

確認以下設定：
- ☑ **成員資格**：「任何人都可以註冊」✅ 已勾選
- ☑ **新使用者預設角色**：選擇「Customer」或「Subscriber」

### 步驟 3：清除緩存

1. 在 WordPress 後台清除快取（如有啟用快取外掛）
2. 在瀏覽器中清除快取：`Ctrl+Shift+Delete`（Windows）或 `Cmd+Shift+Delete`（Mac）

### 步驟 4：測試

重新訪問頁面並測試：

1. **標籤頁切換**
   - 點擊「會員登入」標籤頁
   - 點擊「免費註冊」標籤頁
   - 應該能看到兩個不同的內容面板

2. **註冊面板**
   - 應該看到三個會員權益卡片（✨ 累積積分、📅 輕鬆管理、💰 專屬折扣）
   - 應該看到「前往註冊頁面」按鈕（橙色）

3. **登入面板**
   - 應該看到登入表單（電子郵件/用戶名、密碼）
   - 應該看到「記住我」複選框
   - 應該看到「忘記密碼？」連結

---

## 詳細診斷

### 問題 1：標籤頁仍無法切換

**原因可能**：
- JavaScript 未加載
- 瀏覽器緩存問題
- jQuery 衝突

**解決方案**：
1. 在瀏覽器開發人員工具（F12）中打開控制台
2. 檢查是否有錯誤訊息（紅色文字）
3. 嘗試在控制台中執行：
   ```javascript
   var btn = document.querySelector('.kayarine-auth-tab-btn');
   console.log('Tab button found:', btn);
   ```
4. 如果返回 `null`，說明頁面沒有正確加載 HTML

### 問題 2：註冊面板為空或顯示警告

**原因可能**：
- `users_can_register` 未設為 `1`
- `woocommerce_myaccount_page_id` 未設定
- WooCommerce 註冊被禁用

**解決方案**：
1. 進入 WordPress 後台 → 設定 → 一般
2. 確認「任何人都可以註冊」已勾選
3. 進入 WordPress 後台 → WooCommerce → 設定 → 帳戶
4. 確認「允許客戶在結帳時建立帳戶」已勾選

### 問題 3：登入後無反應

**原因可能**：
- WordPress 登入 URL 重定向失敗
- 會話處理問題

**解決方案**：
1. 檢查 WordPress 設定 → 一般中的「WordPress 位址」和「網站位址」是否正確
2. 嘗試在不同瀏覽器中登入
3. 檢查 WordPress 錯誤日誌：`wp-content/debug.log`

---

## 檔案列表（需要上傳）

| 檔案路徑 | 類型 | 優先級 |
|---------|------|--------|
| `kayarine-booking/includes/class-kayarine-auth-integration.php` | 更新 | ⭐⭐⭐ 高 |
| `kayarine-booking/kayarine-booking.php` | 更新 | ⭐⭐ 中 |
| `kayarine-booking/assets/css/style.css` | 更新 | ⭐ 低 |

---

## 測試檢清單

部署後，在瀏覽器中訪問包含 `[kayarine_login_register]` 短代碼的頁面，驗證以下項目：

### 視覺化
- [ ] 紫色漸變標題「Kayarine 會員中心」顯示
- [ ] 兩個橙色標籤頁按鈕可見
- [ ] 紫色邊框在活躍標籤頁下方
- [ ] 整體佈局居中且響應式

### 功能性
- [ ] 點擊「會員登入」標籤頁切換成功
- [ ] 點擊「免費註冊」標籤頁切換成功
- [ ] 登入表單中的輸入欄可輸入
- [ ] 登入按鈕（橙色）可點擊
- [ ] 「忘記密碼？」連結可點擊
- [ ] 註冊面板顯示三個會員權益卡片
- [ ] 「前往註冊頁面」按鈕可點擊並重定向

### 響應式設計
- [ ] 在手機上（< 480px）：標籤頁堆疊，按鈕全寬
- [ ] 在平板上（768px - 899px）：適當的間距
- [ ] 在桌面上（≥ 900px）：完整佈局

### 已登入狀態
- [ ] 以現有帳戶登入
- [ ] 重新訪問頁面應顯示「歡迎回來」訊息
- [ ] 應顯示「我的預約」、「編輯檔案」、「登出」按鈕

---

## 常見問題

**Q：為什麼還是看到默認的 WordPress 登入頁面？**
A：確認您在頁面上添加了短代碼 `[kayarine_login_register]`。如果只訪問 `/account/` 或其他登入頁面，WordPress 可能會使用默認登入頁。

**Q：標籤頁標題是中文的，但其他文本是英文的？**
A：所有文本都是中文。如果看到英文，可能是快取問題。清除快取重試。

**Q：如何隱藏默認的 WooCommerce My Account 頁面？**
A：您不需要隱藏它。訪問不同的 URL：
- 自定義登錄頁面：添加 `[kayarine_login_register]` 短代碼到自定義頁面
- My Account 頁面：保持默認，用於帳戶管理和訂單歷史

**Q：如何自定義顏色？**
A：編輯 `style.css`，搜索：
- `#7B68EE`（紫色主色）替換為您的顏色
- `#FF8C42`（橙色動作色）替換為您的顏色

---

## 支持

如需進一步幫助，請提供以下資訊：
1. 瀏覽器開發人員工具的控制台錯誤（F12）
2. `wp-content/debug.log` 中的 PHP 錯誤
3. 您正在測試的確切 URL
4. 使用的 WordPress 版本和 PHP 版本

