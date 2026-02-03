# 深層性能診斷指南

## 當前狀況總結

**已完成優化：**
- ✅ 代碼級優化（Transient 快取、requestIdleCallback）
- ✅ PHP-FPM 進程優化（ondemand 模式，max_children=6）
- ✅ CloudFlare Rocket Loader（已禁用）
- ✅ Elementor 圖像優化（Optimized Image Loading、Lazy Load）
- ✅ 刪除 Ninja Google Review 插件
- ✅ 清理舊版本 Kayarine 插件

**當前性能：** 2.3-2.8 秒（改善不大）

**問題分析：** 瓶頸不在前述優化項目中，應該是：
1. **Elementor + Theme 初始化** - 1000+ 毫秒
2. **資料庫查詢累積** - 500+ 毫秒
3. **插件衝突** - 300+ 毫秒
4. **JavaScript 阻塞** - 200+ 毫秒

---

## 方案 A：禁用插件逐一測試（最有效）

### 目的
通過二分搜索法找出性能殺手插件

### 步驟

#### 1️⃣ 建立測試清單
登入 WP Admin → Plugins，記錄所有啟用的插件。

**已知插件名單：**
- Elementor (Essential)
- Elementor Pro (Essential)
- WooCommerce (Essential)
- Kayarine Booking (Essential)
- NitroPack (Essential for caching)
- Query Monitor (Diagnostic)
- ?其他插件

#### 2️⃣ 禁用非關鍵插件並測試

**策略：禁用一半插件測試**

```
循環 1：禁用一半的非關鍵插件
└─ 記錄加載時間
└─ 若改善 > 200ms → 該組有問題
└─ 若無改善 → 該組無問題，重新啟用

循環 2：繼續在問題組內二分搜索
└─ 逐個禁用，找出問題插件
```

#### 3️⃣ 測試順序（推薦）

```
第 1 輪：
禁用：所有非必需插件（除了 Elementor/WC/Kayarine/NitroPack/QM）
預期：若改善 > 300ms，說明有插件衝突

第 2 輪：
逐個重新啟用第 1 輪禁用的插件
每次只啟用 1 個，測試加載時間
找出具體是哪個插件導致性能下降

第 3 輪（如果還未解決）：
禁用 Elementor Pro 測試
禁用 Elementor 本體測試
禁用 WooCommerce 測試
```

#### 4️⃣ 如何禁用和測試

```
1. WP Admin → Plugins
2. 勾選要禁用的插件
3. 下拉選單選「停用」
4. 清除 NitroPack 快取
5. 清除瀏覽器快取（Cmd+Shift+Delete）
6. 訪問頁面，用 DevTools (F12) → Network 檢查加載時間
```

---

## 方案 B：詳細資料庫查詢診斷

### 使用 Query Monitor 進階分析

#### 1️⃣ 打開 Query Monitor
```
前端頁面 → 左上角「QM」按鈕 → 點擊展開
```

#### 2️⃣ 檢查關鍵指標
```
📊 Queries 標籤：
- 總查詢數：應 < 100（目前可能 150+）
- 平均查詢時間：應 < 1ms（目前可能 2-3ms）
- 最慢查詢：應 < 20ms（目前可能 50-200ms）

⏱️ Database Time（資料庫耗時）：
- 應 < 0.5 秒（目前可能 0.8-1.2 秒）

📁 Files & Functions：
- 檢查哪個插件觸發最多查詢

🔌 Hooks：
- 檢查 wp_footer 是否有過多鉤子執行
```

#### 3️⃣ 查看慢查詢（> 10ms）
```
QM → Queries → 點擊「Slow Queries」標籤
記錄所有 > 10ms 的查詢
分析調用者（通常會顯示插件名稱）
```

#### 4️⃣ 導出查詢報告
```
QM → 頁面下方「Export」
下載 JSON 格式查詢日誌
分析最耗時的查詢
```

---

## 方案 C：檢查 Elementor 快取

### Elementor 可能在重新生成 CSS

#### 1️⃣ 檢查 Elementor CSS 快取大小
```
SSH 命令：
ssh -i /path/to/key kayarine.server@104.199.144.122
find /opt/bitnami/wordpress/wp-content/uploads/elementor/css -type f | wc -l
du -sh /opt/bitnami/wordpress/wp-content/uploads/elementor/css
```

若有 1000+ 個文件或 > 100MB，說明快取失控

#### 2️⃣ 清除 Elementor 快取
```
WP Admin → Elementor → Tools → Clear Cache
```

#### 3️⃣ 重建 Elementor 快取
```
編輯任意 Elementor 頁面 → 發佈
讓系統重新生成 CSS
```

---

## 方案 D：檢查 NitroPack 配置

### NitroPack 可能設置過度優化

#### 1️⃣ 檢查 NitroPack 設置
```
WP Admin → NitroPack → Settings → Advanced

檢查以下項目：
☑️ Lazy Load JavaScript - 應啟用
☑️ Optimize Critical CSS - 應啟用
☑️ Delay Non-Critical CSS - 應啟用
☑️ Delay All JavaScript - 可能有問題（導致頁面互動延遲）
```

#### 2️⃣ 若啟用「Delay All JavaScript」
```
建議禁用該選項
改為「Delay Non-Critical JavaScript」

重新建立 NitroPack 快取：
1. WP Admin → NitroPack
2. Settings → Clear Cache
3. 訪問頁面重新生成快取
```

---

## 方案 E：禁用 Elementor Pro 功能

某些 Elementor Pro 功能可能導致性能問題

### 禁用的功能
```
WP Admin → Elementor Pro → Settings
- Global Widgets：可能造成額外查詢
- Custom Post Types：若未使用，禁用
- Form Submissions Tracking：禁用（造成 postmeta 查詢）
```

---

## 方案 F：WordPress 核心優化

### 檢查 WordPress 設置

#### 1️⃣ 禁用不必要的功能
```
SSH 進入 WordPress：
ssh -i /path/to/key kayarine.server@104.199.144.122
cd /opt/bitnami/wordpress

# 檢查自動更新是否關閉
wp config get AUTOMATIC_UPDATER_DISABLED --allow-root

# 檢查 WP-Cron 是否啟用
wp option get cron --allow-root
```

#### 2️⃣ 禁用 WordPress REST API（若未使用）
在 wp-config.php 中添加：
```php
define( 'REST_REQUEST', false );
```

---

## 立即執行方案（排序）

### 優先級 1：禁用插件測試（15 分鐘）
```
[ ] 1. 列出所有啟用的插件
[ ] 2. 禁用非關鍵插件（除了 Elementor/WC/Kayarine/NitroPack）
[ ] 3. 測試加載時間
[ ] 4. 若改善，逐個啟用找出問題插件
[ ] 5. 報告結果
```

### 優先級 2：Query Monitor 深度分析（10 分鐘）
```
[ ] 1. 打開 QM，檢查總查詢數
[ ] 2. 查看最慢的 10 個查詢
[ ] 3. 分析哪些插件觸發最多查詢
[ ] 4. 檢查 Database Time（應 < 0.5s）
```

### 優先級 3：Elementor 快取檢查（5 分鐘）
```
[ ] 1. 檢查 Elementor CSS 快取大小
[ ] 2. 清除 Elementor 快取
[ ] 3. 重建快取（編輯頁面發佈）
[ ] 4. 測試加載時間
```

### 優先級 4：NitroPack 設置審核（5 分鐘）
```
[ ] 1. 檢查「Delay All JavaScript」是否啟用
[ ] 2. 若啟用，改為「Delay Non-Critical JavaScript」
[ ] 3. 清除 NitroPack 快取重新生成
[ ] 4. 測試加載時間
```

---

## 預期結果

### 最可能的問題（70% 機率）
**插件衝突**
- 禁用某個插件後 → 改善至 1.5-2.0 秒
- 解決方案：替換該插件或關閉其某些功能

### 次可能（20% 機率）
**Elementor CSS 快取失控**
- 清除快取後 → 改善至 1.8-2.2 秒
- 解決方案：定期清除或優化 Elementor 設置

### 較少可能（10% 機率）
**資料庫查詢性能**
- 優化查詢後 → 改善至 1.3-1.8 秒
- 解決方案：添加資料庫索引或進一步優化代碼

---

## 下一步行動

**立即執行方案優先級 1（禁用插件測試）**，這是最快速有效的方法來識別問題。

完成後報告：
1. 禁用各組插件後的加載時間
2. 具體是哪個插件導致性能下降
3. Query Monitor 的查詢數據（若已檢查）

根據結果，我將提供更具體的優化方案。

---

**預計完成時間：30-45 分鐘內確定根本原因**
