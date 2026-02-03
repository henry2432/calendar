# Kayarine Booking v1.4.13 部署說明

## 版本信息

- **版本號**：1.4.13
- **部署日期**：2026-01-27
- **主要功能**：實現預約改期和取消功能

---

## 新增功能

### 改期預約 (Reschedule Booking)
- **功能**：用戶可以點擊「改期」按鈕選擇新日期
- **驗證**：
  - 新日期必須是未來日期
  - 當日預約上午 9:00 後無法免費改期
  - 已取消的預約無法改期
- **實現方式**：
  - AJAX 處理器：`ajax_reschedule_booking()`
  - 日期選擇模態框
  - 更新訂單項元數據：`kayarine_booking_date`、`kayarine_rescheduled_at`

### 取消預約 (Cancel Booking)
- **功能**：用戶可以點擊「取消」按鈕取消預約
- **驗證**：
  - 確認對話框防止誤操作
  - 已取消的預約不可重複取消
- **實現方式**：
  - AJAX 處理器：`ajax_cancel_booking()`
  - 更新訂單項元數據：`kayarine_booking_cancelled`、`kayarine_cancelled_at`

---

## 修改的文件

### 1. kayarine-booking.php
- **版本更新**：1.4.12 → 1.4.13
- **行 7 和 16**：版本號更改

### 2. includes/class-kayarine-woocommerce-customizer.php
- **新增 AJAX 鉤子**（第 29-30 行）：
  ```php
  add_action( 'wp_ajax_kayarine_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
  add_action( 'wp_ajax_kayarine_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
  ```

- **修改 enqueue_custom_styles()**（第 1015-1028 行）：
  - 添加 Nonce 本地化：
    ```php
    wp_localize_script( 'jquery', 'kayarineBooking', array(
        'nonce' => wp_create_nonce( 'kayarine_booking_nonce' ),
    ) );
    ```

- **修改 get_ajax_handler_js()**（第 1030-1283 行）：
  - 添加「改期」按鈕事件監聽（第 1045-1057 行）
  - 添加「取消」按鈕事件監聽（第 1059-1092 行）
  - 添加改期模態框函數（第 1095-1167 行）

- **新增方法：ajax_reschedule_booking()**（第 1288-1355 行）：
  - Nonce 驗證
  - 權限檢查
  - 日期驗證
  - 預約所有權驗證
  - 取消狀態檢查
  - 時間窗口檢查（9:00 AM 截止）
  - 元數據更新

- **新增方法：ajax_cancel_booking()**（第 1360-1408 行）：
  - Nonce 驗證
  - 權限檢查
  - 預約 ID 驗證
  - 預約所有權驗證
  - 重複取消檢查
  - 元數據更新

---

## 部署步驟

### 方式 1：使用 WordPress 插件管理器（推薦）

1. **備份現有插件**
   ```bash
   # 在服務器上執行
   cd /var/www/html/wp-content/plugins
   cp -r kayarine-booking kayarine-booking.backup.v1.4.12
   ```

2. **上傳新版本**
   - 本地：`kayarine-booking-1.4.13.tar.gz`
   - 上傳到服務器並解壓：
     ```bash
     cd /var/www/html/wp-content/plugins
     tar -xzf kayarine-booking-1.4.13.tar.gz
     ```

3. **設置權限**
   ```bash
   chown -R www-data:www-data /var/www/html/wp-content/plugins/kayarine-booking
   chmod -R 755 /var/www/html/wp-content/plugins/kayarine-booking
   ```

4. **在 WordPress 管理面板中重新激活插件**
   - 進入：Plugins → Installed Plugins
   - 禁用：Kayarine Booking
   - 啟用：Kayarine Booking

### 方式 2：使用 FTP/SFTP

1. **連接到服務器**
   - 地址：`your-server-ip`
   - 用戶名：FTP 用戶名
   - 密碼：FTP 密碼

2. **上傳文件**
   ```
   本地: kayarine-booking/kayarine-booking.php
   遠程: /wp-content/plugins/kayarine-booking/kayarine-booking.php
   
   本地: kayarine-booking/includes/class-kayarine-woocommerce-customizer.php
   遠程: /wp-content/plugins/kayarine-booking/includes/class-kayarine-woocommerce-customizer.php
   ```

3. **在 WordPress 中重新激活插件**

---

## 測試清單

部署後，請驗證以下功能：

### 改期功能
- [ ] 登入會員中心
- [ ] 點擊預約項的「改期」按鈕
- [ ] 日期選擇器正常顯示
- [ ] 選擇新日期
- [ ] 點擊「確認改期」
- [ ] 預約日期成功更新
- [ ] 瀏覽器控制台無錯誤

### 取消功能
- [ ] 登入會員中心
- [ ] 點擊預約項的「取消」按鈕
- [ ] 確認對話框出現
- [ ] 點擊「確定」確認取消
- [ ] 預約從列表中移除（如果是未來預約）
- [ ] 瀏覽器控制台無錯誤

### 邊界情況
- [ ] 嘗試改期到過去的日期（應該錯誤）
- [ ] 嘗試改期已取消的預約（應該錯誤）
- [ ] 當日上午 9:00 後改期（應該錯誤）
- [ ] 嘗試重複取消預約（應該錯誤）

### 一般功能
- [ ] 會員中心隱藏菜單導航
- [ ] 預約列表正常顯示
- [ ] 結帳頁面顯示「應付」金額
- [ ] 響應式設計在移動設備上正常

---

## 回滾步驟

如果需要回滾到 v1.4.12：

```bash
# 在服務器上執行
cd /var/www/html/wp-content/plugins
rm -rf kayarine-booking
cp -r kayarine-booking.backup.v1.4.12 kayarine-booking
```

然後在 WordPress 管理面板中重新啟用插件。

---

## 故障排除

### 問題：改期/取消按鈕不響應

**解決方案**：
1. 檢查瀏覽器控制台（F12 → Console）
2. 查找 AJAX 錯誤信息
3. 確認已登入
4. 檢查服務器日誌：`/var/www/html/wp-content/debug.log`

### 問題：出現 Nonce 錯誤

**解決方案**：
1. 清除瀏覽器快取
2. 清除 WordPress 快取（如有快取插件）
3. 重新加載頁面

### 問題：日期選擇器未顯示

**解決方案**：
1. 檢查瀏覽器是否支持 HTML5 date input
2. 檢查 CSS 是否加載正確
3. 在瀏覽器開發者工具中檢查元素

---

## 技術細節

### AJAX 安全性
- 所有 AJAX 請求都通過 Nonce 保護
- Nonce 名稱：`kayarine_booking_nonce`
- 使用 `check_ajax_referer()` 驗證

### 數據存儲
所有預約數據存儲在 WooCommerce 訂單項元數據中：
- `kayarine_booking_date`：預約日期
- `kayarine_booking_cancelled`：取消狀態（'yes' 或空）
- `kayarine_cancelled_at`：取消時間戳
- `kayarine_rescheduled_at`：改期時間戳

### 時間驗證
- **改期截止時間**：當日上午 9:00 AM 之前
- **新日期要求**：必須是未來日期
- **時區**：使用 WordPress 站點時區

---

## 備份文件

- **備份位置**：`kayarine-booking-1.4.13.tar.gz`
- **大小**：115KB
- **包含內容**：完整 v1.4.13 插件

---

## 聯繫和支持

如有問題，請檢查：
1. WordPress 錯誤日誌
2. 瀏覽器開發者控制台
3. 服務器日誌
4. 插件與主題兼容性

---

**部署完成後，請運行測試清單確認所有功能正常運作。**
