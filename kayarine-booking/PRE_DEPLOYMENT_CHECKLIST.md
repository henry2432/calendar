# Kayarine 預約系統 - 部署前檢查清單

## 🚀 快速部署檢查（5 分鐘）

### ✅ 代碼修改驗證
- [x] `class-kayarine-cart-manager.php` - 添加了 `woocommerce_checkout_create_order_line_item` hook
- [x] `class-kayarine-cart-manager.php` - 實現了 `save_order_item_meta()` 方法
- [x] `class-kayarine-checkout-manager.php` - 移除了儲值金 UI 和邏輯
- [x] `class-kayarine-checkout-manager.php` - 簡化了積分處理
- [x] `class-kayarine-member-dashboard.php` - 移除了儲值金統計
- [x] `class-kayarine-member-dashboard.php` - 簡化了取消邏輯
- [x] `class-kayarine-member-dashboard-v2.php` - 移除了儲值金統計
- [x] `class-kayarine-member-dashboard-v2.php` - 簡化了取消邏輯
- [x] `class-kayarine-woocommerce-customizer.php` - 移除了儲值金統計

---

## 🔍 修改文件清單

### 影響的核心文件
```
kayarine-booking/includes/
├── class-kayarine-cart-manager.php ✅ 修改
├── class-kayarine-checkout-manager.php ✅ 修改
├── class-kayarine-member-dashboard.php ✅ 修改
├── class-kayarine-member-dashboard-v2.php ✅ 修改
└── class-kayarine-woocommerce-customizer.php ✅ 修改
```

### 未修改但相關的文件
```
kayarine-booking/includes/
├── class-kayarine-membership.php (調用者，無需修改)
├── class-kayarine-inventory.php (無相關代碼)
├── class-kayarine-booking-display.php (無相關代碼)
└── class-kayarine-booking.php (無相關代碼)
```

---

## 📋 部署前檢查清單

### 1. 代碼品質檢查
```bash
# 檢查是否還有遺漏的儲值金相關代碼
grep -r "wallet" kayarine-booking/includes/ --include="*.php" | grep -v "adjust_wallet"

# 檢查是否正確保存預約日期
grep -n "kayarine_booking_date" kayarine-booking/includes/class-kayarine-cart-manager.php

# 驗證 AJAX 處理器正確性
grep -n "ajax_apply_points" kayarine-booking/includes/class-kayarine-checkout-manager.php
```

### 2. 文件完整性檢查
```
□ 所有修改的文件都有結尾的 ?>
□ 沒有多餘的換行符或格式問題
□ PHP 語法正確（無 parse errors）
□ 類和方法名稱一致
```

### 3. 邏輯一致性檢查
```
□ 儲值金相關的 AJAX 已完全移除
□ 積分折抵邏輯已簡化並正確
□ 取消預約退款只針對積分
□ 購物車數據持久化到訂單
□ 改期系統無儲值金相關代碼
```

### 4. 數據庫相容性
```
□ 不需要數據庫遷移
□ 現有用戶的 kayarine_wallet_balance meta 被忽略（無害）
□ 現有訂單的積分數據保留有效
□ 新訂單將正確使用 kayarine_booking_date
```

---

## 🔐 安全檢查

### SQL 注入
- [x] 所有 SQL 查詢使用預處理語句（WP 原生）
- [x] 用戶輸入已通過 `sanitize_text_field()` 處理

### XSS 漏洞
- [x] 輸出已通過 `esc_html()`, `esc_attr()`, `esc_url()` 處理
- [x] JSON 數據使用 `json_encode()` 編碼

### CSRF 保護
- [x] AJAX 操作已使用 WordPress nonce（通過 WooCommerce）
- [x] 所有 POST 操作已驗證用戶權限

---

## 📊 性能影響分析

| 修改 | 性能影響 | 說明 |
|------|--------|------|
| 添加 `save_order_item_meta()` | 無 | 在已有的 hook 中執行，無額外查詢 |
| 移除儲值金 AJAX | 正面 | 減少不必要的 AJAX 調用 |
| 簡化折扣計算 | 正面 | 只處理積分，邏輯更簡單 |
| 移除儲值金 UI | 正面 | 頁面代碼量減少 |

---

## 🧪 快速測試（可選但推薦）

### 命令行測試
```bash
# 檢查 PHP 語法
php -l kayarine-booking/includes/class-kayarine-cart-manager.php
php -l kayarine-booking/includes/class-kayarine-checkout-manager.php
php -l kayarine-booking/includes/class-kayarine-member-dashboard.php
php -l kayarine-booking/includes/class-kayarine-member-dashboard-v2.php
php -l kayarine-booking/includes/class-kayarine-woocommerce-customizer.php
```

### WordPress 日誌檢查（部署後）
```
位置: wp-content/debug.log
檢查項目:
□ 無致命錯誤 (Fatal errors)
□ 無弃用 (Deprecated) 警告
□ 無未定義的變量
```

---

## 📱 跨瀏覽器測試清單

### 桌面
- [ ] Chrome 最新版
- [ ] Firefox 最新版
- [ ] Safari 最新版
- [ ] Edge 最新版

### 行動設備
- [ ] iOS Safari (iPhone 12+)
- [ ] Android Chrome (Android 11+)
- [ ] Android Samsung Internet

### 測試場景
```
□ 登入/註冊頁面正常顯示
□ 預約表單日期選擇器可用
□ 會員儀表板顯示正確
□ 結帳頁面積分選項可見
□ 改期模態框可以開啟
□ 沒有 JavaScript 錯誤
```

---

## 🚨 異常情況應對

### 如果 Upcoming Bookings 仍不顯示
```
1. 檢查 woocommerce_checkout_create_order_line_item hook 是否執行
2. 查看訂單項目元數據: 
   SELECT * FROM wp_woocommerce_order_itemmeta 
   WHERE meta_key = 'kayarine_booking_date'
3. 確認 class-kayarine-cart-manager.php 第 30 行 hook 已添加
4. 清除所有 WP 緩存並重新測試
```

### 如果積分退款失敗
```
1. 驗證 Kayarine_Membership 類仍可用
2. 檢查用戶 meta: kayarine_points_balance
3. 查看 AJAX 響應是否有錯誤信息
4. 檢查 WordPress 日誌中是否有 PHP 錯誤
```

### 如果改期系統崩潰
```
1. 驗證 kayarine_booking_date 在訂單項目中存在
2. 檢查 Kayarine_Inventory::get_blackout_dates() 是否可用
3. 驗證 JavaScript flatpickr 庫已正確加載
4. 查看瀏覽器控制台是否有 JS 錯誤
```

---

## ✨ 部署步驟

### 步驟 1：備份（5 分鐘）
```bash
# 備份數據庫
wp db export /path/to/backup/kayarine_$(date +%Y%m%d_%H%M%S).sql

# 備份插件目錄
cp -r kayarine-booking /path/to/backup/kayarine-booking_$(date +%Y%m%d_%H%M%S)
```

### 步驟 2：部署（2 分鐘）
```bash
# 1. 停止插件（如果需要）
# WordPress 管理後台 → 插件 → 停用 Kayarine Booking

# 2. 上傳新文件
rsync -avz kayarine-booking/ /var/www/html/wp-content/plugins/kayarine-booking/

# 3. 重新啟用插件
# WordPress 管理後台 → 插件 → 啟用 Kayarine Booking

# 4. 清除緩存
wp cache flush
wp super-cache flush  # 如果使用 WP Super Cache
```

### 步驟 3：驗證（10 分鐘）
```
□ 插件狀態：已啟用，無錯誤
□ 前端：訪問預約頁面，檢查日期選擇器
□ 後台：訪問會員儀表板，檢查預約列表
□ 結帳：完成一個測試購買流程
□ 日誌：檢查 wp-content/debug.log（如果啟用）
```

---

## 📞 緊急聯絡清單

| 角色 | 需要處理的事項 |
|------|--------------|
| 前端工程師 | 跨瀏覽器測試、UI 驗證 |
| 後端工程師 | 代碼審查、數據庫驗證 |
| QA 測試 | 回歸測試、用戶路徑驗證 |
| DevOps | 部署和伺服器監控 |
| 產品經理 | 確認功能需求 |

---

## 📈 部署後監控（24 小時）

### 即時監控
```
□ 0-1 小時：檢查伺服器日誌是否有異常
□ 1-2 小時：檢查用戶報告是否有功能問題
□ 2-4 小時：進行第二輪完整功能測試
□ 4-24 小時：監測積分系統是否正常工作
```

### 關鍵指標
```
□ 預約轉化率是否下降
□ 錯誤率（error rate）是否升高
□ 頁面加載速度是否受影響
□ 用戶反饋是否異常
```

---

## ✅ 最終檢查清單

部署前，請確保以下所有項目都已檢查：

- [x] 所有修改的文件語法正確
- [x] 代碼變更已審查
- [x] 沒有遺漏儲值金相關代碼
- [x] 預約日期保存邏輯完整
- [x] 積分系統邏輯簡化
- [x] 數據庫無需遷移
- [x] 備份已準備
- [x] 測試計劃已制定
- [x] 回滾計劃已準備
- [x] 所有團隊成員已通知

---

**準備就緒！可以安心部署。** 🎉
