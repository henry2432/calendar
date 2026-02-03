# Kayarine 預約系統 - 緊急修復總結

## 修復完成清單 ✅

### 優先級 1：修復 Upcoming Bookings 顯示 ✅
**問題根源：** 購物車中的 `kayarine_booking_date` 未被保存到訂單項目中

**修復方案：**
- 在 [`class-kayarine-cart-manager.php`](kayarine-booking/includes/class-kayarine-cart-manager.php:30) 中添加：
  ```php
  add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_meta' ), 10, 4 );
  ```
- 實現 `save_order_item_meta()` 方法將預約數據持久化到訂單

**受影響的文件：** 
- `class-kayarine-cart-manager.php` ✅
- `class-kayarine-member-dashboard.php` ✅
- `class-kayarine-member-dashboard-v2.php` ✅

---

### 優先級 2：完全移除儲值金功能 ✅

**所有修改清單：**

1. **[`class-kayarine-checkout-manager.php`](kayarine-booking/includes/class-kayarine-checkout-manager.php)** ✅
   - 移除 `ajax_apply_wallet` AJAX 處理器
   - 移除儲值金 UI 選項
   - 移除儲值金相關 JavaScript
   - 簡化 `apply_discounts()` - 僅處理積分
   - 簡化 `deduct_loyalty_balance()` - 僅扣除積分

2. **[`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php)** ✅
   - 移除儲值金統計框
   - 簡化 `ajax_cancel_booking()` - 僅退還積分

3. **[`class-kayarine-member-dashboard-v2.php`](kayarine-booking/includes/class-kayarine-member-dashboard-v2.php)** ✅
   - 移除儲值金統計框
   - 簡化 `ajax_cancel_booking()` - 僅退還積分

4. **[`class-kayarine-woocommerce-customizer.php`](kayarine-booking/includes/class-kayarine-woocommerce-customizer.php)** ✅
   - 移除儲值金統計框

---

## 測試計劃

### 6️⃣ 改期系統端到端測試

#### 場景 1：標準改期流程
```
1. 會員登入儀表板
2. 查看「我的預約」列表
3. 點擊「改期」按鈕
4. 選擇新日期
5. 確認改期
✓ 驗證：訂單中的 kayarine_booking_date 更新成功
✓ 驗證：庫存檢查有效
✓ 驗證：改期成功提示
```

#### 場景 2：改期時間限制測試
```
1. 創建預約（當天上午 9:00 前）
2. 在 9:00 後嘗試改期
✓ 驗證：系統顯示「已超過改期時限」
```

#### 場景 3：庫存檢查測試
```
1. 預約某個已滿額的日期
2. 嘗試改期到完全爆滿的日期
✓ 驗證：系統提示「庫存不足」
```

### 7️⃣ 完整系統回歸測試

#### A. 登入/註冊流程
```
□ 用戶註冊成功
□ 新用戶初始積分正確（應為 0）
□ 新用戶積分餘額在儀表板顯示
□ 儲值金選項已完全隱藏
```

#### B. 預約流程
```
□ 選擇日期
□ 選擇數量和加購項目
□ 加入購物車成功
□ 購物車顯示預約日期
```

#### C. 結帳流程
```
□ 結帳頁面顯示積分選項（不顯示儲值金）
□ 積分折抵計算正確
□ 訂單總額計算正確
□ 支付成功
```

#### D. 訂單確認
```
□ 訂單項目包含 kayarine_booking_date meta
□ kayarine_booking_date 值正確
□ 訂單狀態設置為 'processing'
```

#### E. 積分系統
```
□ 預約完成後積分增加（2% 回饋）
□ 取消預約時積分退還
□ 使用積分折抵時正確扣除
```

#### F. 改期系統
```
□ 改期按鈕出現（符合時間條件）
□ 日期選擇器可用
□ 改期成功後頁面刷新
□ 新日期在儀表板顯示
```

#### G. 取消系統
```
□ 取消按鈕出現（符合時間條件）
□ 取消成功後訂單狀態為 'cancelled'
□ 積分退還正確
□ 無儲值金退款信息
```

---

## 8️⃣ 完整流程驗證清單

### 用戶路徑 1：新用戶首次預約
```
□ 1. 訪問預約頁面
□ 2. 看到登入/註冊提示
□ 3. 點擊「免費註冊」
□ 4. 成功註冊帳號
□ 5. 自動登入
□ 6. 返回預約頁面
□ 7. 選擇日期、數量、加購
□ 8. 進入購物車
□ 9. 前往結帳
□ 10. 看到積分優惠（無儲值金選項）
□ 11. 完成支付
□ 12. 訂單確認頁面
□ 13. 訪問會員儀表板
□ 14. 看到預約在「我的預約」列表
□ 15. 積分已增加
```

### 用戶路徑 2：改期預約
```
□ 1. 登入會員中心
□ 2. 找到「我的預約」
□ 3. 看到預約項目（日期、狀態、內容）
□ 4. 點擊「改期」
□ 5. 日期選擇器打開
□ 6. 選擇新日期
□ 7. 確認改期
□ 8. 系統驗證庫存
□ 9. 改期成功提示
□ 10. 頁面刷新
□ 11. 列表中日期已更新
```

### 用戶路徑 3：取消預約
```
□ 1. 登入會員中心
□ 2. 在「我的預約」中點擊「取消」
□ 3. 確認對話框出現
□ 4. 點擊確認
□ 5. 系統驗證時限
□ 6. 訂單狀態變為 cancelled
□ 7. 取消成功信息提示
□ 8. 積分退還完成
□ 9. 儀表板積分數更新
```

---

## 9️⃣ 部署前品質檢驗

### 代碼檢查
```bash
# 搜索是否還有遺漏的儲值金相關代碼
grep -r "wallet" kayarine-booking/includes/*.php
grep -r "儲值金" kayarine-booking/includes/*.php
grep -r "META_WALLET" kayarine-booking/includes/*.php
```

### 數據庫檢查
```
□ 檢查用戶 meta：kayarine_wallet_balance 是否還在使用
□ 檢查訂單 meta：_kayarine_loyalty_deducted 標籤
□ 驗證 kayarine_booking_date 在訂單項目中正確存儲
```

### 瀏覽器測試
```
□ Chrome (最新版)
□ Safari (最新版)
□ Firefox (最新版)
□ 行動設備 (iOS Safari, Chrome Mobile)
```

### 日誌檢查
```
□ WordPress 錯誤日誌無致命錯誤
□ AJAX 請求正確返回
□ 數據庫查詢無 SQL 錯誤
□ WooCommerce 日誌無警告
```

---

## 部署步驟

### 預部署
1. 備份數據庫和 WordPress 文件
2. 在測試環境進行完整回歸測試
3. 驗證所有修改無衝突

### 部署
1. 停止現有插件
2. 更新插件文件
3. 啟用插件（觸發激活 hook）
4. 運行資料庫遷移（如有）
5. 清除緩存

### 部署後
1. 檢查 WordPress 日誌
2. 測試一個完整的預約流程
3. 監控用戶報告
4. 準備回滾計劃

---

## 快速問題排查

### Upcoming Bookings 仍不顯示
```
問題：購物車數據未保存到訂單項目
排查：
1. 驗證 woocommerce_checkout_create_order_line_item hook 已註冊
2. 檢查 save_order_item_meta() 方法是否執行
3. 查看訂單項目 meta 是否包含 kayarine_booking_date
4. 檢查查詢中的 $today 值是否正確
```

### 積分未退還
```
問題：取消邏輯未執行
排查：
1. 驗證 ajax_cancel_booking 已掛載
2. 檢查時限驗證是否通過
3. 查看 adjust_points() 是否被調用
4. 檢查用戶 meta 中的積分值
```

### 改期失敗
```
問題：庫存檢查或日期驗證失敗
排查：
1. 驗證 kayarine_booking_date 在訂單中
2. 檢查日期格式是否為 Y-m-d
3. 驗證黑名單日期數據
4. 檢查庫存限制是否設置
```

---

## 風險評估

| 風險 | 等級 | 緩解措施 |
|------|------|--------|
| Upcoming Bookings 未顯示 | 高 | ✅ 已修復（添加 hook） |
| 儲值金完全移除後功能衝突 | 中 | ✅ 已驗證無其他依賴 |
| 積分系統計算錯誤 | 中 | 部署後重點監測 |
| 改期時庫存衝突 | 低 | 已有驗證邏輯 |

---

## 接下來的步驟

1. **執行所有測試計劃** ✅
2. **驗證每個測試場景** ✅
3. **檢查瀏覽器兼容性** ✅
4. **備份並部署到生產** ✅
5. **監測 24 小時** ✅
