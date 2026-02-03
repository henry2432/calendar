# 改期及取消功能測試指南

## 功能概述

改期和取消功能已修復，現在應該能正常運作。以下是詳細的測試步驟和使用說明。

## 修復內容

### 1. Nonce 驗證修復
- **問題**：原使用 `check_ajax_referer()` 會直接 die()，導致 AJAX 失敗
- **解決**：改為手動使用 `wp_verify_nonce()` 進行驗證，返回正確的錯誤信息

### 2. 視覺反饋增強
- **改期按鈕**：點擊時顯示「加載中...」，modal 打開後恢復
- **取消按鈕**：點擊時顯示「取消中...」，操作完成後恢復或刷新
- **改期確認按鈕**：提交時顯示「改期中...」，禁用日期輸入框

### 3. Modal 樣式
- 添加了完整的 modal 視覺樣式（半透明背景、動畫、陰影）
- 支持響應式設計
- 點擊背景或關閉按鈕可關閉

### 4. 調試日誌
- 添加了詳細的 console 日誌便於調試
- 服務器端添加了 error_log 輸出

## 如何使用

### 改期預約步驟

1. **登入會員中心**
   - 進入 Kayarine 會員頁面
   - 使用有效帳號登入

2. **查看預約**
   - 在「我的預約」部分看到未來的預約

3. **點擊「改期」按鈕**
   - 按鈕會顯示「加載中...」（視覺反饋）
   - 改期 Modal 會彈出

4. **選擇新日期**
   - 點擊日期輸入框
   - 選擇未來的日期（最少明天）
   - 必須選擇一個日期

5. **確認改期**
   - 點擊「確認改期」按鈕
   - 按鈕會顯示「改期中...」（視覺反饋）
   - 等待服務器響應

6. **確認成功**
   - 顯示成功消息
   - 頁面自動刷新
   - 預約日期已更新

### 取消預約步驟

1. **登入會員中心**
   - 進入 Kayarine 會員頁面
   - 使用有效帳號登入

2. **查看預約**
   - 在「我的預約」部分看到未來的預約

3. **點擊「取消」按鈕**
   - 會彈出確認對話框
   - 點擊「確定」確認取消

4. **確認取消**
   - 按鈕會顯示「取消中...」（視覺反饋）
   - 等待服務器響應

5. **確認成功**
   - 顯示成功消息
   - 頁面自動刷新
   - 已取消的預約不再顯示

## 限制規則

### 改期限制
- **時間限制**：同日期的預約在上午 9:00 後無法免費改期
- **已取消**：已取消的預約無法改期
- **日期限制**：新日期必須是未來日期（不能是今天或過去）

### 取消限制
- **已取消**：已取消的預約無法再次取消

## 調試方法

### 1. 檢查瀏覽器控制台
```javascript
// 按 F12 打開開發者工具 → 控制台標籤
// 會看到類似以下日誌：
// [Kayarine] Reschedule button clicked for booking 123
// [Kayarine] Current date: 2026年 2月 1日 (Sunday)
// [Kayarine] Nonce available: yes
```

### 2. 檢查服務器日誌
```bash
# 查看 WordPress debug.log
tail -f /path/to/wp-content/debug.log

# 會看到類似以下日誌：
# [Kayarine Debug] ajax_reschedule_booking called
# [Kayarine Debug] POST data: {...}
# [Kayarine Debug] Nonce verification result: 1
# [Kayarine] Added upcoming booking: 456 on 2026-02-01
```

### 3. 常見問題排查

**問題：按鈕沒有反應**
- 確保已登入
- 檢查瀏覽器控制台是否有錯誤
- 檢查 JavaScript 是否正確加載
- 檢查 jQuery 和 ajaxurl 全局變數

**問題：Modal 沒有出現**
- 檢查 CSS 是否正確加載
- 檢查 z-index 衝突
- 在控制台檢查 HTML 是否生成 modal 元素

**問題：改期/取消失敗**
- 檢查錯誤消息（會在 alert 中顯示）
- 檢查服務器 debug.log 中的 nonce 驗證結果
- 確認用戶擁有該預約
- 檢查日期是否有效

**問題：頁面不刷新**
- 檢查 location.reload() 是否被阻止
- 檢查瀏覽器安全設置
- 嘗試手動刷新頁面

## 功能驗證清單

- [ ] 能看到「我的預約」列表
- [ ] 「改期」按鈕點擊時顯示視覺反饋
- [ ] Modal 能正常彈出
- [ ] 能選擇新日期
- [ ] 「確認改期」按鈕能執行改期
- [ ] 成功改期後頁面刷新且預約日期更新
- [ ] 「取消」按鈕點擊時能彈出確認對話框
- [ ] 確認取消後按鈕顯示視覺反饋
- [ ] 成功取消後頁面刷新且預約消失
- [ ] 已取消的預約不再顯示在列表中
- [ ] 改期按鈕在同日期 9:00 後無法使用（可選檢驗）

## 支持的瀏覽器

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- 移動瀏覽器（iOS Safari, Chrome Mobile）

## 返回值示例

### 改期成功
```json
{
  "success": true,
  "data": {
    "message": "預約已成功改期",
    "new_date": "2026年 2月 15日 (Sunday)"
  }
}
```

### 改期失敗
```json
{
  "success": false,
  "data": {
    "message": "今日預約在上午 9:00 後無法免費改期"
  }
}
```

### 取消成功
```json
{
  "success": true,
  "data": {
    "message": "預約已成功取消"
  }
}
```

### 取消失敗
```json
{
  "success": false,
  "data": {
    "message": "該預約已取消"
  }
}
```

## 技術實現細節

### AJAX 端點
- `wp_ajax_kayarine_reschedule_booking` - 改期端點
- `wp_ajax_kayarine_cancel_booking` - 取消端點

### 儲存位置
- WooCommerce 訂單項目元數據
- `kayarine_booking_date` - 預約日期
- `kayarine_booking_cancelled` - 取消狀態
- `kayarine_rescheduled_at` - 改期時間戳
- `kayarine_cancelled_at` - 取消時間戳

### 安全機制
- Nonce 驗證（`kayarine_booking_nonce`）
- 用戶登入檢查
- 訂單項目所有權驗證
- 日期有效性檢查

---

**最後更新**：2026-01-28
**版本**：1.0
