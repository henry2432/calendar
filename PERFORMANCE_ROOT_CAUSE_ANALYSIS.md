# 性能下降根本原因分析 - Kayarine 部署影響

## 關鍵信息
- **之前**（無 Kayarine）：~1 秒
- **現在**（有 Kayarine）：~3 秒
- **差距**：+2 秒（100% 性能下降）
- **CloudFlare Challenge**：已排除（無頁面顯示，仍然 3 秒）
- **CloudFlare 緩存**：未啟用（需付費）

**結論**：問題在 **Kayarine 部署或 Bitnami 配置改變**，而非 CloudFlare Challenge。

---

## 第一步：診斷 Kayarine 影響

### 1.1 檢查 Kayarine 初始化時間

在伺服器上執行：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'DIAG'
cd /opt/bitnami/wordpress
tail -100 wp-content/debug.log | grep -i "kayarine"
DIAG
```

**查詢**：
- 是否有 `[Kayarine Perf]` 日誌？
- 加載時間是否仍為 3.22ms？
- 是否有新的錯誤或警告？

### 1.2 禁用 Kayarine 臨時測試

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'DIAG2'
cd /opt/bitnami/wordpress
sudo -u www-data wp plugin deactivate kayarine-booking
echo "✓ Kayarine 已停用"
sudo -u www-data wp cache flush
echo "✓ 快取已清除"
DIAG2
```

**測試**：
1. 訪問首頁
2. F12 → Network → 記錄 Load 時間
3. 如果恢復至 1 秒，則問題在 Kayarine
4. 如果仍為 3 秒，則問題在其他地方

**重新啟用**：
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'DIAG3'
cd /opt/bitnami/wordpress
sudo -u www-data wp plugin activate kayarine-booking
sudo -u www-data wp cache flush
DIAG3
```

---

## 第二步：分析 Kayarine 的資源加載

如果禁用 Kayarine 後性能恢復，問題原因可能是：

### 2.1 CSS/JS 檔案過大或未壓縮

檢查 Kayarine 的資源：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'SIZE'
cd /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking

echo "=== CSS 檔案大小 ==="
du -sh assets/css/

echo -e "\n=== JS 檔案大小 ==="
du -sh assets/js/

echo -e "\n=== PHP 檔案數量 ==="
find includes -name "*.php" | wc -l

echo -e "\n=== 總插件大小 ==="
du -sh .
SIZE
```

**預期**：
- CSS: < 100KB（壓縮後）
- JS: < 150KB（壓縮後）
- PHP: < 50 個文件

### 2.2 檢查 Kayarine 的 Hook 註冊

問題代碼位置（[`kayarine-booking.php`](../Documents/GitHub/calendar/kayarine-booking/kayarine-booking.php) 第 57 行）：

```php
add_action( 'plugins_loaded', 'kayarine_booking_init', 1 );
```

**潛在問題**：
1. 優先級 1（最高）可能與其他插件衝突
2. `kayarine_ensure_unified_account_page()` 函數是否每次都執行重操作？

---

## 第三步：CloudFlare 設置分析

即使不啟用緩存，CloudFlare 也會執行以下操作：

### 3.1 檢查 CloudFlare 設置

**進入 CloudFlare 儀表板**：

1. **Speed → Optimization**
   - [ ] Auto Minify：是否已啟用？（檢查 CSS、JS、HTML）
   - [ ] Rocket Loader：是否已啟用？（會延遲 JS，造成 1-2 秒）
   - [ ] Brotli Compression：是否已啟用？

2. **Caching → Rules**
   - [ ] 是否有頁面規則設定為「不快取」？
   - [ ] 是否有路由被排除快取？

3. **Performance**
   - [ ] 是否有「性能預設」為「保守」？

### 3.2 如果 Rocket Loader 已啟用 ⚠️

Rocket Loader 會：
- 延遲所有 JavaScript 執行
- 可能造成 1-2 秒延遲
- 對 Kayarine 的 AJAX 請求造成問題

**解決方案**：
```
CloudFlare → Speed → Optimization → Rocket Loader：關閉
```

---

## 第四步：Bitnami PHP-FPM 配置

Kayarine 部署後，是否改變了 PHP 配置？

### 4.1 檢查 PHP-FPM 狀態

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'PHP'
php -v
echo "=== PHP 配置 ==="
php -i | grep -E "max_execution_time|memory_limit|upload_max_filesize"

echo -e "\n=== PHP-FPM 進程 ==="
ps aux | grep php-fpm | head -3
PHP
```

---

## 第五步：資料庫查詢分析

Kayarine 是否在頁面加載時執行了過多 SQL 查詢？

### 5.1 啟用查詢日誌

編輯 wp-config.php：

```php
define( 'SAVEQUERIES', true );
```

在前端頁面加入：

```php
<?php
if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
    global $wpdb;
    echo '<pre>';
    foreach ( $wpdb->queries as $query ) {
        echo $query[0] . ' (Time: ' . $query[1] . 's)' . "\n";
    }
    echo '</pre>';
}
?>
```

**檢查**：
- Kayarine 執行多少 SQL 查詢？
- 最慢的查詢是什麼？
- 是否有重複查詢（N+1 問題）？

---

## 診斷執行步驟

### 第一優先級（立即執行）
```bash
# Step 1：檢查 Kayarine 日誌
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "cd /opt/bitnami/wordpress && tail -50 wp-content/debug.log | grep -i kayarine"

# Step 2：暫時禁用 Kayarine 測試性能
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "cd /opt/bitnami/wordpress && sudo -u www-data wp plugin deactivate kayarine-booking && wp cache flush"
# → 測試頁面載入時間

# Step 3：檢查 CloudFlare Rocket Loader
# → 登入 CloudFlare 儀表板，檢查 Speed > Optimization
```

### 第二優先級
```bash
# Step 4：檢查資源大小
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "cd /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking && du -sh assets/"

# Step 5：檢查 PHP 配置
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "php -v && php -i | grep 'memory_limit'"
```

---

## 可能的根本原因及解決方案

### 原因 1：CloudFlare Rocket Loader 啟用 ⚠️ 可能性：70%

**症狀**：
- JS 執行延遲 1-2 秒
- 頁面看起來在加載但無法交互

**解決方案**：
```
CloudFlare → Speed → Optimization → Rocket Loader：關閉
預期改進：-500ms 至 -1500ms
```

### 原因 2：Kayarine 插件執行重操作

**症狀**：
- 禁用後性能恢復至 1 秒
- 某個 Hook 在執行耗時操作

**檢查點**：
- [`kayarine_ensure_unified_account_page()`](../Documents/GitHub/calendar/kayarine-booking/kayarine-booking.php) 是否每次都查詢資料庫？
- Kayarine 是否加載了大量 CSS/JS？
- 是否有 AJAX 請求在初始化時發送？

**解決方案**：
- 實現 Transient 快取（已完成）
- 延遲加載非必要資源
- 使用 `wp_enqueue_script` 的 `'in_footer' => true`

### 原因 3：Bitnami 配置改變

**症狀**：
- PHP-FPM 進程不足
- OPCache 被禁用
- Memory limit 過低

**檢查點**：
- `php -v`（版本是否變化？）
- `php -i | grep memory_limit`（應為 256MB+）
- `ps aux | grep php-fpm`（進程數是否足夠？）

**解決方案**：
- 升級 PHP 至 8.1+（預計改進 200-300ms）
- 增加 PHP-FPM 進程數
- 啟用 OPCache

### 原因 4：資料庫性能下降

**症狀**：
- 資料庫查詢時間累加達 2+ 秒
- N+1 查詢問題

**檢查點**：
- 啟用 `SAVEQUERIES` 並檢查查詢列表
- 是否有 Kayarine 導致的大量查詢？

**解決方案**：
- 實現查詢緩存（Transient）
- 使用資料庫索引
- 延遲加載訂單或用戶數據

---

## 建議診斷順序

1. ✅ **CloudFlare Rocket Loader**（最可能，解決快）
2. ✅ **禁用 Kayarine 測試**（確認問題根源）
3. ✅ **檢查 Kayarine 資源大小**（是否有大檔案）
4. ✅ **檢查 PHP 配置**（OPCache、Memory）
5. ✅ **資料庫查詢分析**（如果前四項無問題）

---

## 快速修復建議

**如果是 CloudFlare Rocket Loader**：
```
CloudFlare 儀表板 → Speed → Optimization → Rocket Loader：關閉
預期立即改進 500ms-1500ms
```

**如果是 Kayarine 本身**：
需要進一步的代碼優化或資源重構。

**如果是 PHP 配置**：
```
聯繫主機商升級 PHP 至 8.1+
預期改進 200-300ms
```

---

## 下一步行動

1. **立即檢查 CloudFlare Rocket Loader 設置**
   - 登入 CloudFlare 儀表板
   - Speed → Optimization → Rocket Loader
   - 如果啟用，關閉並測試

2. **執行禁用 Kayarine 測試**
   - 確認性能是否恢復至 1 秒
   - 如果是，則 Kayarine 是瓶頸
   - 如果否，則其他配置有問題

3. **提供我以下信息**：
   - CloudFlare Rocket Loader 當前狀態
   - 禁用 Kayarine 後的載入時間
   - 所有診斷命令的輸出

