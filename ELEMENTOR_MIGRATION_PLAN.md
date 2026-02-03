# Elementor 遷離計劃 - 完整指南

## 📋 執行概要

**目標：** 完全移除 Elementor，達成 1.0-1.2 秒頁面加載時間（目標 1.3 秒 ✓）

**當前狀態：** 3.1-3.2 秒（禁用 5 個不兼容插件後）
**遷離後預期：** 1.0-1.2 秒（節省 1000-1400ms）
**轉移時間：** 2-4 小時（取決於頁面數量）

---

## 🎯 第 1 步：準備備份（重要！）

### 步驟 1.1：備份 Elementor 頁面內容

```bash
# SSH 連接伺服器
ssh -i ~/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 導出 Elementor 頁面數據（作為 JSON 備份）
wp db export /tmp/elementor-backup-$(date +%Y%m%d-%H%M%S).sql
```

### 步驟 1.2：截圖所有 Elementor 頁面

在執行遷離前，對所有 Elementor 頁面進行截圖以保留設計參考：

1. 登入 WordPress 後台
2. 進入 **Pages** → 逐個打開每個頁面
3. 按 F12 或使用瀏覽器截圖工具（如 Nimbus Screenshot）
4. 保存所有設計參考

### 步驟 1.3：記錄 Elementor 頁面列表

```
編輯您網站上的 Elementor 頁面清單：

當前 Elementor 頁面：
□ 首頁 (ID: ___)
□ 關於我們 (ID: ___)
□ 服務 (ID: ___)
□ 聯絡我們 (ID: ___)
□ 其他... (ID: ___)
```

---

## 🔄 第 2 步：選擇替代方案

根據需求和難度，提供三種方案：

### 方案 A：WordPress Gutenberg Block Editor（推薦 ✓）

**優點：**
- ✅ WordPress 原生，零額外開銷
- ✅ 速度最快（預期 0.8-1.0 秒）
- ✅ 無需額外插件費用
- ✅ 長期支持（WordPress 官方維護）
- ✅ 簡單易用

**缺點：**
- ❌ 設計自由度不如 Elementor
- ❌ 需要重新設計頁面

**實施時間：** 1-2 小時

**推薦場景：**
- 頁面結構簡單（3-5 個主要頁面）
- 不需要複雜的視覺效果
- 預算有限

---

### 方案 B：Oxygen Builder（高級選項）

**優點：**
- ✅ 速度快（預期 1.0-1.3 秒）
- ✅ 設計自由度高（接近 Elementor）
- ✅ 專業級構建器

**缺點：**
- ❌ 需要付費授權（$99/年起）
- ❌ 學習曲線陡峭

**實施時間：** 2-3 小時

**推薦場景：**
- 需要複雜設計
- 預算充足
- 中等規模網站

---

### 方案 C：Beaver Builder（平衡選項）

**優點：**
- ✅ 速度快（預期 1.1-1.3 秒）
- ✅ 設計相對自由
- ✅ 學習曲線適中

**缺點：**
- ❌ 需要付費授權（$199/年起）
- ❌ 性能不如原生 Gutenberg

**實施時間：** 2-3 小時

**推薦場景：**
- 中等複雜度設計
- 預算中等
- 需要良好的平衡

---

## 🔴 推薦方案：Gutenberg Block Editor（方案 A）

基於您的需求（快速達成 1.3 秒目標），**強烈推薦使用 Gutenberg**：

**理由：**
1. ✅ 速度最快（0.8-1.0 秒 vs Elementor 的 3.1-3.2 秒）
2. ✅ 零成本（WordPress 原生）
3. ✅ 無需額外學習（與 WordPress 整合）
4. ✅ Kayarine 插件相容（無相容性問題）

---

## 📝 第 3 步：使用 Gutenberg 重建頁面

### 步驟 3.1：啟用 Gutenberg 塊編輯器

```bash
# SSH 連接
ssh -i ~/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 確認 Gutenberg 已啟用（WordPress 5.0+ 默認啟用）
wp option get gutenberg-widget-legacy-widget-migrations
```

### 步驟 3.2：編輯每個頁面

**對於每個 Elementor 頁面：**

1. **進入後台編輯頁面**
   - WordPress 後台 → Pages → 編輯頁面
   - 點擊「Edit」進入編輯模式

2. **刪除 Elementor 內容**
   - 頁面會顯示：「This page was built using Elementor...」
   - 點擊「Edit with Elementor」或頁面內容區域
   - 選擇所有內容 (`Ctrl+A` 或 `Cmd+A`)
   - 按 Delete 刪除

3. **添加 Gutenberg 區塊**
   - 點擊「+」按鈕添加新區塊
   - 常用區塊：
     - 標題 (Heading)
     - 段落 (Paragraph)
     - 圖像 (Image)
     - 按鈕 (Button)
     - 欄 (Columns)
     - 群組 (Group)

4. **根據截圖重建頁面**
   - 參考之前保存的 Elementor 截圖
   - 重建每個部分

5. **保存並發佈**
   - 點擊「Save」
   - 點擊「Publish」

### 步驟 3.3：重建範例（首頁）

```
Elementor 首頁通常包含：

1. Hero 區塊（大標題 + 背景圖）
   Gutenberg 方案：
   ├─ 使用「Cover」區塊（背景圖 + 文字）
   └─ 添加標題和副標題

2. 特色部分（3 欄）
   Gutenberg 方案：
   ├─ 使用「Columns」區塊
   ├─ 設定為 3 欄
   └─ 每欄添加圖像 + 文字

3. 服務列表
   Gutenberg 方案：
   ├─ 使用「Paragraph」區塊列表
   └─ 使用「Button」添加行動按鈕

4. 頁腳
   Gutenberg 方案：
   ├─ 使用「Group」區塊
   └─ 添加聯繫信息
```

### 步驟 3.4：CSS 自訂（可選）

如果需要自訂樣式，將 CSS 添加到 WordPress 自訂 CSS：

```css
/* WordPress 後台 → Customize → Additional CSS */

/* 例子：自訂首頁樣式 */
.wp-block-cover {
  min-height: 400px;
}

.wp-block-columns {
  gap: 20px;
}

.wp-block-button__link {
  background-color: #007cba;
  color: white;
  padding: 12px 24px;
  border-radius: 5px;
}
```

---

## 🗑️ 第 4 步：刪除 Elementor

### 步驟 4.1：禁用並刪除 Elementor 插件

```bash
# SSH 連接
ssh -i ~/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 禁用 Elementor
wp plugin deactivate elementor || sudo -u www-data wp plugin deactivate elementor --allow-root

# 刪除 Elementor
wp plugin delete elementor || sudo -u www-data wp plugin delete elementor --allow-root

# 如果已安裝 Elementor Pro，也刪除
wp plugin delete elementor-pro || sudo -u www-data wp plugin delete elementor-pro --allow-root
```

### 步驟 4.2：清理數據庫（可選但推薦）

```bash
# 刪除 Elementor 相關選項
mysql -h localhost -u wordpress_user -p wordpress_db << EOF
DELETE FROM wp_options WHERE option_name LIKE 'elementor%';
DELETE FROM wp_postmeta WHERE meta_key LIKE '%elementor%';
OPTIMIZE TABLE wp_options;
OPTIMIZE TABLE wp_postmeta;
EOF
```

### 步驟 4.3：清空 WordPress 緩存

```bash
# 如果使用了任何緩存插件
wp cache flush || sudo -u www-data wp cache flush --allow-root
```

---

## ✅ 第 5 步：驗證性能改進

### 步驟 5.1：清除所有快取

```bash
# CloudFlare 快取清除（如果使用）
# WordPress 後台 → CloudFlare → Purge Cache → Purge Everything

# 瀏覽器快取
# Ctrl+Shift+Delete 或 Cmd+Shift+Delete 清除瀏覽器快取
# 或在無痕模式下測試
```

### 步驟 5.2：測試頁面加載時間

**使用 Query Monitor（推薦）：**
```
1. 登入 WordPress 後台
2. 訪問前端頁面
3. 點擊左上角「QM」按鈕
4. 查看「Page Generation Time」
```

**預期結果：**
```
遷離前：3.1-3.2 秒
遷離後：0.8-1.2 秒（改進 60-70%）
```

**使用 Google PageSpeed Insights：**
```
1. 訪問 https://pagespeed.web.dev/
2. 輸入您的網站 URL
3. 檢查首屏內容繪製時間 (FCP) 和最大內容繪製時間 (LCP)
```

### 步驟 5.3：驗證各頁面功能

```
測試清單：
□ 首頁載入正常
□ 關於頁面顯示正確
□ 服務頁面格式正確
□ 聯繫表單運作
□ 行動裝置界面正常
□ 所有連結有效
□ 圖像加載正確
```

---

## 🚨 故障排除

### 問題 1：頁面在 Elementor 中看不到

**原因：** Elementor 已刪除

**解決方案：**
```bash
# 檢查備份
cat /tmp/elementor-backup-*.sql | head -50

# 如果需要恢復，可以重新安裝臨時 Elementor
wp plugin install elementor --activate
```

### 問題 2：頁面仍然加載 Elementor CSS

**原因：** 緩存或資源未更新

**解決方案：**
1. 進入 WordPress 後台 → Settings → Permalinks
2. 點擊「Save Changes」（不需要改變任何設定）
3. 清除所有緩存
4. 硬刷瀏覽器 (Ctrl+Shift+R 或 Cmd+Shift+R)

### 問題 3：Gutenberg 編輯器看起來很簡陋

**原因：** 需要添加 CSS 樣式

**解決方案：**
在 WordPress 後台 → Customize → Additional CSS 添加：

```css
/* 增強 Gutenberg 外觀 */
.wp-block-heading {
  font-size: 2.5em;
  margin-bottom: 20px;
}

.wp-block-image {
  margin: 20px 0;
}

.wp-block-column {
  padding: 20px;
  border-radius: 5px;
}
```

---

## 📊 預期性能對比

```
┌──────────────────────────┬──────────┬──────────┬──────────┐
│ 指標                      │ Elementor│ Gutenberg│ 改進     │
├──────────────────────────┼──────────┼──────────┼──────────┤
│ 頁面生成時間              │ 3.1-3.2s │ 0.8-1.0s │ -67%⚡   │
│ 數據庫查詢                │ 272 個   │ 100-120個│ -55%    │
│ 記憶體用量                │ 195 MB   │ 120 MB   │ -38%    │
│ CSS 文件大小              │ 1.4 MB   │ 0.2 MB   │ -85%    │
│ HTTP 請求數              │ 80+      │ 30-40    │ -60%    │
└──────────────────────────┴──────────┴──────────┴──────────┘
```

---

## 🎯 後續優化（可選）

遷離 Elementor 後，還可以進一步優化：

### 1️⃣ 啟用頁面快取

```bash
# 安裝 WP Super Cache（免費）
wp plugin install wp-super-cache --activate

# 或安裝 W3 Total Cache（免費）
wp plugin install w3-total-cache --activate
```

**預期改進：** 後續訪問 -80% 時間（0.2-0.3 秒）

### 2️⃣ 啟用 GZIP 壓縮

```bash
# 檢查 Apache 配置
ssh -i ~/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122
cat /opt/bitnami/apache/conf/httpd.conf | grep -i gzip
```

### 3️⃣ 優化圖像

```bash
# 安裝圖像優化插件
wp plugin install imagemagick-engine --activate

# 批量優化現有圖像
wp plugin install bulk-image-optimization --activate
```

---

## 📋 執行檢查清單

```
準備階段：
□ 備份數據庫
□ 備份 Elementor 頁面截圖
□ 記錄 Elementor 頁面列表

遷移階段：
□ 編輯首頁（使用 Gutenberg）
□ 編輯關於頁面
□ 編輯服務頁面
□ 編輯聯繫頁面
□ 編輯其他頁面

清理階段：
□ 禁用 Elementor 插件
□ 刪除 Elementor 插件
□ 刪除 Elementor Pro（如有）
□ 清理數據庫
□ 清空快取

驗證階段：
□ 測試頁面加載時間
□ 驗證所有頁面正常
□ 驗證移動裝置界面
□ 驗證所有功能運作
□ 檢查 Google PageSpeed Insights

後續優化（可選）：
□ 啟用頁面快取
□ 啟用 GZIP 壓縮
□ 優化圖像
```

---

## 📞 需要幫助？

如果遇到問題：

1. **保存截圖：** 所有錯誤消息
2. **檢查日誌：** `/opt/bitnami/wordpress/wp-content/debug.log`
3. **測試性能：** 運行 Query Monitor 檢查

---

## ⏱️ 預期時間表

```
第一步：準備備份（15 分鐘）
├─ 備份數據庫
├─ 備份頁面截圖
└─ 記錄頁面列表

第二步：選擇方案（5 分鐘）
└─ 決定使用 Gutenberg

第三步：重建頁面（1-2 小時）
├─ 編輯 3-5 個頁面
├─ 每頁約 15-20 分鐘
└─ 測試每個頁面

第四步：刪除 Elementor（5 分鐘）
├─ SSH 禁用和刪除插件
└─ 清理數據庫

第五步：驗證性能（10 分鐘）
├─ 清除快取
├─ 測試性能
└─ 驗證功能

═══════════════════════════════
總計：2-2.5 小時（取決於頁面數量）
═══════════════════════════════
```

---

## 🎉 預期成果

遷離完成後：

✅ **頁面加載時間：** 0.8-1.2 秒（達成 1.3 秒目標）
✅ **性能改進：** -60% 至 -70% 時間
✅ **數據庫查詢：** 減少 50-55%
✅ **服務器負載：** 降低 40-50%
✅ **用戶體驗：** 明顯更快的頁面轉換

