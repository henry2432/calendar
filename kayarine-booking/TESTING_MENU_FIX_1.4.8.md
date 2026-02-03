# Kayarine 會員中心菜單修復驗證指南 (v1.4.8)

## 背景
版本 1.4.8 實現了菜單渲染邏輯修復，改變了如何構建和顯示會員中心菜單項目。

### 修復內容
- **舊方法**：直接調用 WooCommerce 的 `woocommerce_account_navigation()` 函數，導致過濾器被繞過
- **新方法**：自定義菜單構建，手動應用 `woocommerce_account_menu_items` 過濾器，只顯示允許的菜單項目

### 預期結果
登入後的會員中心應該：
1. **只顯示以下菜單項目**：
   - 🏅 Kayarine 會員中心 (Dashboard)
   - 📊 我的進度 (Kayarine Membership)
   - Logout (登出)

2. **完全隱藏以下 WooCommerce 菜單項目**：
   - ❌ Orders (訂單)
   - ❌ Downloads (下載)
   - ❌ Addresses (地址)
   - ❌ Account Details (帳戶詳細信息)

3. **會員進度區域應該正常顯示**：
   - 用戶名與等級
   - 現有積分
   - 消費進度條
   - 升級提示

---

## 測試步驟

### 步驟 1：清除瀏覽器緩存
由於某些瀏覽器/CDN 可能緩存頁面，建議：
1. 開啟無痕模式/隱私瀏覽模式
2. 或清除所有緩存並硬刷新頁面 (Ctrl+Shift+R 或 Cmd+Shift+R)

### 步驟 2：訪問會員登入頁面
1. 打開 `https://kayarine.club/account/`
2. 應該看到登入/註冊表單（橙色主題）

### 步驟 3：使用測試帳戶登入
- **用戶名**：`test`
- **密碼**：`testtest`
- 點擊「登入」按鈕

### 步驟 4：等待重定向完成
- 應該自動重定向回 `https://kayarine.club/account/`
- 此時應該看到已登入的會員中心頁面

### 步驟 5：檢查菜單項目（關鍵驗證）

**在頁面左側或頂部檢查菜單欄：**

✅ **應該看到這些菜單項目**：
- 🏅 Kayarine 會員中心
- 📊 我的進度
- Logout（登出）

❌ **絕對不應該看到這些項目**：
- Orders
- Downloads
- Addresses
- Account Details

**如果看到任何 WooCommerce 菜單項目，則修復失敗**。

### 步驟 6：驗證會員進度內容

當沒有特定端點被訪問時，應該看到「會員進度」區域，包含：
- 👤 用戶頭像和用戶名
- 等級標籤（Bronze/Silver/Gold/VIP）
- ✨ 現有積分顯示
- 📊 消費進度條（從 $0 到升級目標）
- 升級提示（例如"再消費 HK$X 即可升級"）

### 步驟 7：點擊「我的進度」菜單項目

1. 點擊左側菜單中的「📊 我的進度」
2. 應該看到詳細的會員進度信息
3. 菜單中該項目應該標記為「active」

### 步驟 8：點擊「Kayarine 會員中心」返回

1. 點擊「🏅 Kayarine 會員中心」
2. 應該回到主會員進度頁面

---

## 如果修復不工作

如果您看到 WooCommerce 菜單項目（Orders, Downloads 等），請：

### 檢查清單

1. **清除 WordPress 緩存**
   - 登入 WordPress 後台 (wp-admin)
   - 如果有緩存插件（如 W3 Total Cache），清除所有緩存
   - 訪問 Settings → Performance → Purge All Caches

2. **檢查服務器版本**
   ```bash
   # SSH 進入服務器
   gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b \
     --command="grep 'Version:' /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php"
   
   # 應該返回: Version: 1.4.8
   ```

3. **檢查錯誤日誌**
   ```bash
   # 檢查最近的 50 行日誌
   gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b \
     --command="tail -50 /opt/bitnami/wordpress/wp-content/debug.log"
   
   # 尋找包含 "Kayarine 1.4.8" 或錯誤信息的行
   ```

4. **檢查菜單過濾器是否被調用**
   ```bash
   # 查看診斷日誌
   gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b \
     --command="grep '\[Kayarine 1.4.8\]' /opt/bitnami/wordpress/wp-content/debug.log | tail -20"
   ```

---

## 預期的調試日誌輸出

如果修復正確工作，在 WordPress debug.log 中應該看到類似的日誌：

```
[Kayarine 1.4.8] Menu Filter - START ================================
[Kayarine 1.4.8] Menu Filter - is_user_logged_in(): true
[Kayarine 1.4.8] Menu Filter - Incoming items: ["dashboard","orders","downloads","edit-address","edit-account","customer-logout"]
[Kayarine 1.4.8] Menu Filter - Total incoming items: 6
[Kayarine 1.4.8] Menu Filter - Added item: dashboard
[Kayarine 1.4.8] Menu Filter - Added item: customer-logout
[Kayarine 1.4.8] Menu Filter - Added kayarine-membership (not found in items)
[Kayarine 1.4.8] Menu Filter - Outgoing items: ["dashboard","kayarine-membership","customer-logout"]
[Kayarine 1.4.8] Menu Filter - Hidden items: ["orders","downloads","edit-address","edit-account"]
[Kayarine 1.4.8] Menu Filter - END ================================
```

**重要**：隱藏項目列表應該包含 "orders", "downloads", "edit-address", "edit-account"。

---

## 測試結果報告格式

請以以下格式報告測試結果：

```
## 測試結果報告 - v1.4.8 菜單修復

### 環境
- 日期：[今天日期]
- 帳戶：test / testtest
- 瀏覽器：[例如 Chrome/Firefox/Safari]

### 菜單驗證
- [ ] 看到「🏅 Kayarine 會員中心」
- [ ] 看到「📊 我的進度」
- [ ] 看到「Logout」
- [ ] 沒看到「Orders」
- [ ] 沒看到「Downloads」
- [ ] 沒看到「Addresses」
- [ ] 沒看到「Account Details」

### 會員進度區域
- [ ] 用戶名正確顯示
- [ ] 等級標籤正確顯示
- [ ] 積分數值正確顯示
- [ ] 消費進度條正確顯示

### 菜單功能
- [ ] 可點擊「我的進度」並看到詳細信息
- [ ] 可點擊「Kayarine 會員中心」返回主頁
- [ ] 登出按鈕工作正常

### 總體結果
- ✅ 成功 / ❌ 失敗

### 備註
[如有任何問題或異常，詳細說明]
```

---

## 相關文檔

- 部署指南：[`kayarine-booking/DEPLOYMENT_GCLOUD_GUIDE.md`](./DEPLOYMENT_GCLOUD_GUIDE.md)
- 修復細節：見 [`class-kayarine-woocommerce-customizer.php`](./includes/class-kayarine-woocommerce-customizer.php) 第 104-192 行（菜單渲染邏輯）和第 419-461 行（菜單過濾）

---

## 快速部署（如需重新部署）

如果需要重新部署最新版本：

```bash
cd kayarine-booking
./deploy.sh production --clear-cache
```

預計時間：~10 秒
