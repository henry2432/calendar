# Theme 與資料庫層級性能優化

## 目前排除的項目
✅ PHP-FPM 進程優化
✅ CloudFlare Rocket Loader
✅ Elementor 圖像優化
✅ Kayarine 插件代碼（3.22ms）
✅ 插件衝突（已排除）

**當前狀況：2.3-2.8 秒，改善有限**

---

## 方案 1：Elementor CSS 快取診斷

### 1️⃣ 檢查 Elementor CSS 快取大小

```bash
ssh -i /path/to/key kayarine.server@104.199.144.122

# 檢查 Elementor CSS 文件數量
find /opt/bitnami/wordpress/wp-content/uploads/elementor/css -type f 2>/dev/null | wc -l

# 檢查文件夾總大小
du -sh /opt/bitnami/wordpress/wp-content/uploads/elementor/ 2>/dev/null

# 檢查最大的 CSS 文件
ls -lhS /opt/bitnami/wordpress/wp-content/uploads/elementor/css/*.css 2>/dev/null | head -10
```

### 2️⃣ 預期結果

```
✅ 正常狀態：
- 文件數：50-200 個
- 總大小：10-50MB
- 最大單個文件：< 5MB

⚠️ 異常狀態（快取失控）：
- 文件數：> 500 個
- 總大小：> 100MB
- 最大單個文件：> 10MB

如果異常，執行：
```

### 3️⃣ 清除 Elementor 快取

#### 方法 A：WP Admin（推薦）
```
WP Admin → Elementor → Tools → Regenerate Files & Data
點擊「Start」按鈕
```

#### 方法 B：SSH 清除
```bash
ssh -i /path/to/key kayarine.server@104.199.144.122

# 完全刪除 Elementor 快取
rm -rf /opt/bitnami/wordpress/wp-content/uploads/elementor/css/*
rm -rf /opt/bitnami/wordpress/wp-content/cache/elementor/*

# 清除 WordPress 快取
rm -rf /opt/bitnami/wordpress/wp-content/cache/wp-cache/*

# 重啟 PHP-FPM 以確保清除
sudo systemctl restart bitnami.php-fpm.service
```

#### 方法 C：資料庫清除
```bash
mysql -u wordpress -p'Bitnami123!' kayarine_db << 'SQL'
-- 清除 Elementor postmeta
DELETE FROM wp_postmeta WHERE meta_key LIKE '%elementor%';

-- 清除 NitroPack 快取記錄
DELETE FROM wp_options WHERE option_name LIKE '%nitrpack%cache%';

-- 最後一次優化時間重置
UPDATE wp_options SET option_value = '' WHERE option_name = '_elementor_css_print_method';

SQL
```

### 4️⃣ 重建 Elementor 快取
```
1. 清除後訪問任意 Elementor 頁面
2. 編輯頁面（Update）
3. 發佈（Publish）
4. 系統自動重新生成 CSS
```

---

## 方案 2：資料庫查詢性能優化

### 1️⃣ 檢查數據庫大小和索引

```bash
ssh -i /path/to/key kayarine.server@104.199.144.122

# 檢查資料庫大小
mysql -u wordpress -p'Bitnami123!' -e "
SELECT 
  table_schema,
  ROUND(SUM(data_length+index_length)/1024/1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'kayarine_db'
GROUP BY table_schema;
"

# 檢查最大的表
mysql -u wordpress -p'Bitnami123!' kayarine_db -e "
SELECT 
  table_name,
  ROUND(((data_length+index_length)/1024/1024), 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'kayarine_db'
ORDER BY (data_length+index_length) DESC
LIMIT 10;
"
```

### 2️⃣ 檢查表格索引

```bash
# 檢查缺失的索引
mysql -u wordpress -p'Bitnami123!' kayarine_db -e "
-- 檢查 wp_postmeta 的索引（最常查詢的表）
SHOW INDEX FROM wp_postmeta;
"
```

### 3️⃣ 添加缺失的索引

```bash
mysql -u wordpress -p'Bitnami123!' kayarine_db << 'SQL'

-- 確保 wp_postmeta 有關鍵索引
ALTER TABLE wp_postmeta ADD INDEX idx_post_id_meta_key (post_id, meta_key);
ALTER TABLE wp_postmeta ADD INDEX idx_meta_key_meta_value (meta_key, meta_value(100));

-- 確保 wp_posts 有關鍵索引
ALTER TABLE wp_posts ADD INDEX idx_post_type_status (post_type, post_status);
ALTER TABLE wp_posts ADD INDEX idx_post_parent (post_parent);

-- 確保 wp_options 有關鍵索引
ALTER TABLE wp_options ADD INDEX idx_option_name (option_name(191));

SQL
```

### 4️⃣ 優化資料庫

```bash
# 執行 MySQL 優化命令
mysql -u wordpress -p'Bitnami123!' kayarine_db -e "
-- 優化所有表
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
OPTIMIZE TABLE wp_options;
OPTIMIZE TABLE wp_terms;
OPTIMIZE TABLE wp_term_taxonomy;
OPTIMIZE TABLE wp_term_relationships;
"
```

---

## 方案 3：Theme 和 WordPress 核心性能

### 1️⃣ 檢查 Theme 性能設置

```
WP Admin → Appearance → Customize → Performance / Settings

檢查是否有以下選項：
☑️ Lazy Load Images - 應啟用
☑️ Defer Non-Critical CSS - 應啟用
☑️ Load Fonts Asynchronously - 應啟用
```

### 2️⃣ 禁用不必要的 WordPress 功能

編輯 `/opt/bitnami/wordpress/wp-config.php`：

```php
// 禁用 REST API（若前端不使用）
define( 'REST_REQUEST', false );

// 禁用自動保存（降低資料庫寫入）
define( 'AUTOSAVE_INTERVAL', 300 ); // 改為 5 分鐘一次

// 禁用修訂版本（保持清潔）
define( 'WP_POST_REVISIONS', 3 ); // 只保留最近 3 個修訂

// 禁用垃圾桶（永久刪除，減少資料庫查詢）
define( 'EMPTY_TRASH_DAYS', 0 );
```

---

## 方案 4：NitroPack 進階設置檢查

### 1️⃣ 檢查 NitroPack CSS/JS 優化

```
WP Admin → NitroPack → Settings → Advanced

檢查以下項目：
☑️ Optimize Critical CSS
   → 應啟用，但若導致樣式問題，改為「Conservative」

☑️ Delay Non-Critical CSS
   → 應啟用（延遲載入非關鍵 CSS）

☑️ Lazy Load JavaScript
   → 應啟用（延遲載入 JS）

⚠️ Delay All JavaScript
   → 若啟用，改為「Partial」（只延遲非關鍵 JS）

☑️ Remove Render-Blocking Resources
   → 應啟用
```

### 2️⃣ 檢查 NitroPack 快取設置

```
WP Admin → NitroPack → Settings → Caching

☑️ Browser Caching
   → 應設為「Aggressive」

☑️ Caching Duration
   → 應設為「1 Month」或「3 Months」

☑️ Stale Cache Serving
   → 應啟用（在更新期間提供舊快取）
```

### 3️⃣ 重新生成 NitroPack 快取

```
WP Admin → NitroPack → 點擊「Purge Cache」
訪問頁面（NitroPack 會重新分析和優化）
```

---

## 方案 5：HTTP/2 和伺服器優化

### 1️⃣ 檢查 HTTP 協議版本

```bash
ssh -i /path/to/key kayarine.server@104.199.144.122

# 檢查 Apache/Nginx 是否支持 HTTP/2
curl -I -v https://kayarine.hk 2>&1 | grep -i 'HTTP\|Protocol'
```

**預期輸出：**
```
HTTP/2 200  ← 支持 HTTP/2 ✅
或
HTTP/1.1 200  ← 只支持 HTTP/1.1（落後）
```

### 2️⃣ 檢查 GZIP 壓縮

```bash
curl -I -H 'Accept-Encoding: gzip' https://kayarine.hk | grep -i 'content-encoding'
```

**預期輸出：**
```
content-encoding: gzip  ← 已啟用 ✅
```

---

## 立即執行清單

### 優先級 1：Elementor 快取診斷（5 分鐘）
```
[ ] 檢查 Elementor CSS 快取大小
[ ] 若 > 500 文件或 > 100MB，執行清除
[ ] 清除後重建快取
[ ] 測試加載時間
```

### 優先級 2：資料庫優化（10 分鐘）
```
[ ] 檢查資料庫大小
[ ] 檢查關鍵索引
[ ] 添加缺失的索引
[ ] 執行 OPTIMIZE TABLE
[ ] 重啟 MySQL
```

### 優先級 3：NitroPack 進階調整（5 分鐘）
```
[ ] 檢查 Delay All JavaScript 設置
[ ] 若啟用，改為「Partial」
[ ] 重新生成快取
[ ] 測試加載時間
```

### 優先級 4：WordPress 核心優化（5 分鐘）
```
[ ] 編輯 wp-config.php
[ ] 禁用自動保存、修訂版本、垃圾桶
[ ] 重啟 PHP-FPM
```

---

## 預期改善結果

### 如果是 Elementor 快取失控（機率 40%）
```
清除快取後：改善 -200-500ms
最終加載時間：2.0-2.3 秒
```

### 如果是資料庫索引缺失（機率 30%）
```
添加索引後：改善 -300-600ms
最終加載時間：1.7-2.3 秒
```

### 如果是 NitroPack 過度優化（機率 20%）
```
調整設置後：改善 -100-300ms
最終加載時間：2.0-2.5 秒
```

### 如果是 WordPress 自動保存（機率 10%）
```
禁用後：改善 -50-100ms
最終加載時間：2.2-2.7 秒
```

---

## 下一步行動

**立即執行優先級 1 和 2（15 分鐘內完成）**

提供以下診斷結果：
1. Elementor CSS 快取大小
2. 資料庫總大小和最大表
3. 清除 / 優化後的加載時間

根據結果，進一步調整優先級 3 和 4。

---

**預計完成時間：30 分鐘內看到 200-600ms 的改善**
