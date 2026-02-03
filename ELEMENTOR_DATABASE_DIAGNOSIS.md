# Elementor 與資料庫性能診斷

## 排除過程
✅ PHP-FPM 優化：無顯著改善
✅ Rocket Loader：已禁用
✅ Kayarine 插件：3.22ms（優秀）
❓ **Elementor + 資料庫查詢**：仍待檢查

---

## 第一步：檢查 Elementor 資產優化

### 1️⃣ 登入 WordPress 後台
```
WP Admin → Elementor → Settings → Performance
```

### 2️⃣ 檢查以下設置

#### CSS 優化
```
☑️ Inline CSS File
   → 應啟用（將 CSS 內聯到 HTML，減少 HTTP 請求）

☑️ CSS Print Method
   → 推薦：Internal（內部加載，避免額外請求）
   ✅ 不選：External（會增加額外 HTTP 請求）

☑️ Minify CSS
   → 應啟用（已由 NitroPack 處理）
```

#### JavaScript 優化
```
☑️ Defer jQuery and jQuery Migrate
   → 應啟用（延遲加載 jQuery，加快初始渲染）

☑️ Minify JavaScript
   → 應啟用（由 NitroPack 處理）
```

#### 圖像優化
```
☑️ Lazy Load Images
   → 應啟用（延遲載入圖像）

☑️ AVIF Format
   → 啟用（現代圖像格式，更小）
```

---

## 第二步：診斷資料庫查詢性能

### 方法 A：使用 WordPress SAVEQUERIES（推薦）

#### 1️⃣ 啟用 SAVEQUERIES
編輯 `/opt/bitnami/wordpress/wp-config.php`：

```php
// 在 /* That's all, stop editing! */ 之前添加：
define( 'SAVEQUERIES', true );
define( 'WP_DEBUG', false );  // 關閉 DEBUG，避免 WP DEBUG BAR 干擾
```

#### 2️⃣ 創建診斷頁面
在主題 `functions.php` 添加：

```php
add_action( 'wp_footer', 'display_query_diagnostics' );
function display_query_diagnostics() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    global $wpdb;
    
    echo '<div style="background:#f5f5f5;padding:20px;margin-top:20px;font-family:monospace;font-size:11px;">';
    echo '<h3>🔍 資料庫查詢診斷</h3>';
    echo '<p><strong>總查詢數：</strong> ' . count( $wpdb->queries ) . '</p>';
    echo '<p><strong>總耗時：</strong> ' . number_format( array_sum( array_column( $wpdb->queries, 1 ) ), 4 ) . ' 秒</p>';
    
    // 排序慢查詢
    usort( $wpdb->queries, function( $a, $b ) {
        return $b[1] - $a[1];
    });
    
    echo '<h4>⏱️ 最慢的 10 個查詢：</h4>';
    echo '<table style="width:100%;border-collapse:collapse;">';
    echo '<tr style="background:#ddd;">';
    echo '<th style="border:1px solid #ccc;padding:5px;text-align:left;">時間 (ms)</th>';
    echo '<th style="border:1px solid #ccc;padding:5px;text-align:left;">查詢</th>';
    echo '</tr>';
    
    for ( $i = 0; $i < min( 10, count( $wpdb->queries ) ); $i++ ) {
        $time_ms = round( $wpdb->queries[$i][1] * 1000, 2 );
        $query = substr( $wpdb->queries[$i][0], 0, 100 ) . '...';
        
        $bg = $time_ms > 0.5 ? '#ffcccc' : '#fff';
        echo '<tr style="background:' . $bg . ';">';
        echo '<td style="border:1px solid #ccc;padding:5px;"><strong>' . $time_ms . '</strong></td>';
        echo '<td style="border:1px solid #ccc;padding:5px;">' . htmlspecialchars( $query ) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}
```

#### 3️⃣ 查看診斷結果
```
訪問前端頁面 → 向下滾動至底部
查看「資料庫查詢診斷」表格
```

### 方法 B：使用插件（更簡便）

安裝並啟用 **Query Monitor**：
```
WP Admin → Plugins → Add New
搜索：Query Monitor
安裝並啟用
```

使用方法：
```
訪問前端頁面 → 頁面左上方「QM」按鈕
點擊 → Queries 標籤
查看慢查詢（> 0.05s）
```

---

## 第三步：檢查常見性能殺手

### 常見導致性能問題的查詢

#### 1. WooCommerce 訂單查詢
```sql
-- ❌ 慢查詢（N+1 問題）
SELECT * FROM wp_posts WHERE post_type='shop_order' 
LIMIT 100;  // 然後對每個訂單再查詢元數據

-- ✅ 優化查詢
SELECT * FROM wp_posts 
INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id 
WHERE wp_posts.post_type='shop_order' 
AND wp_postmeta.meta_key IN ('_customer_user', '_order_total');
```

**已優化？** ✅ 在 `class-kayarine-member-dashboard.php` 中已實施 15 分鐘 Transient 緩存

#### 2. Elementor 頁面渲染
```
Elementor 會為每個頁面生成大量元數據
可能導致：
- 200-400+ 個額外的 postmeta 查詢
- 100-300ms 額外延遲
```

**檢查方法：** 禁用 Elementor，測試加載時間

#### 3. 分類/標籤查詢
```sql
-- Elementor 複製品會查詢所有分類
SELECT * FROM wp_terms;
SELECT * FROM wp_term_taxonomy WHERE taxonomy = 'category';
```

#### 4. 插件初始化查詢
```
每個插件的 init 鉤子可能執行額外查詢
組合起來可能 200+ 個查詢
```

---

## 第四步：快速性能測試對比

### 測試方案 A：禁用 Elementor 頁面生成器

#### 步驟：
```
1. WP Admin → Plugins → 尋找「Elementor」
2. 停用 Elementor（暫時，用於測試）
3. 清除所有快取
4. 在前端頁面載入時測試
5. 記錄加載時間
```

#### 預期結果：
```
如果禁用後改善至 1.3-1.8 秒
→ Elementor 是主要瓶頸

如果仍為 2-3 秒
→ 瓶頸在資料庫查詢或其他插件
```

### 測試方案 B：檢查資料庫查詢數量

使用上面的診斷代碼，查看：
```
- 總查詢數：應 < 100（目前可能 200+）
- 最慢查詢：應 < 0.1s（目前可能 0.2-0.5s）
- 總耗時：應 < 0.5s（目前可能 1-2s）
```

---

## 立即檢查清單

- [ ] 檢查 Elementor 性能設置（Inline CSS、Defer jQuery）
- [ ] 啟用 SAVEQUERIES 和診斷代碼
- [ ] 查看最慢的 10 個查詢
- [ ] 檢查總查詢數量（應 < 100）
- [ ] 測試禁用 Elementor 前後的加載時間

---

## 診斷命令（SSH）

### 快速檢查資料庫連接
```bash
ssh -i /path/to/key kayarine.server@104.199.144.122
mysql -u wordpress -p kayarine_db -e "SHOW STATUS LIKE 'Questions';"
```

### 檢查最大查詢時間
```bash
mysql -u wordpress -p kayarine_db -e "
SHOW VARIABLES LIKE 'long_query_time';
SHOW VARIABLES LIKE 'max_connections';
"
```

---

## 預期原因排序

1. **Elementor CSS/JS + 元數據查詢** - 50% 機率
   - 改善：-500-1000ms

2. **WooCommerce/Kayarine 查詢性能** - 30% 機率
   - 改善：-300-600ms（已部分優化）

3. **其他插件冲突** - 15% 機率
   - 改善：-200-400ms

4. **資料庫配置不优化** - 5% 機率
   - 改善：-100-200ms

---

## 下一步行動

### 立即執行（15 分鐘）
1. ✅ 檢查 Elementor 設置（Inline CSS、Defer jQuery）
2. ✅ 啟用 SAVEQUERIES 診斷
3. ✅ 測試禁用 Elementor（1-2 分鐘）
4. ✅ 報告結果

### 根據結果調整
- 若禁用 Elementor 改善 > 500ms → 需要優化 Elementor
- 若 Elementor 無幫助 → 檢查資料庫查詢優化

---

**預計完成時間：20 分鐘內確定主要瓶頸**
