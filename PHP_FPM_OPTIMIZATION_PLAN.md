# PHP-FPM 進程優化計劃 - 基於流量分析

## 當前情況分析

**流量特性**：
- 目前：低流量（測試階段）
- 旺季：預計 10,000-15,000 訪問/月
- 平均：每月 ~333-500 訪問/天（~14-21 訪問/小時）
- 峰值：可能 2-3 倍平均（~30-60 訪問/小時）

**當前問題**：
- 頁面載入 3 秒（即使沒有流量）
- 單個 PHP-FPM 進程
- Kayarine 部署後從 1 秒變 3 秒

**重點**：低流量下仍然 3 秒，表示 **不是並發問題，而是單個請求本身慢**

---

## 第一步：確認瓶頸真正位置

### 測試 1：禁用 Kayarine 看性能是否恢復

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'TEST1'
cd /opt/bitnami/wordpress

echo "停用 Kayarine..."
sudo -u www-data wp plugin deactivate kayarine-booking
sudo -u www-data wp cache flush

echo "✓ Kayarine 已停用，現在測試頁面載入時間"
echo "立即測試首頁載入，如果恢復至 ~1 秒，則 Kayarine 是瓶頸"
echo "如果仍為 3 秒，則其他插件是瓶頸"

TEST1
```

**測試步驟**：
1. 執行上面的命令
2. 清除瀏覽器快取（Ctrl+Shift+Delete）
3. 訪問首頁
4. F12 → Network → 記錄 Load 時間
5. 報告結果

---

## 第二步：根據結果確定優化方向

### 情景 A：禁用 Kayarine 後恢復至 ~1 秒

**結論**：Kayarine 是瓶頸

**原因分析**（雖然代碼很快但為什麼整體頁面慢）：
1. WordPress 初始化 Kayarine 時載入了大量資源
2. Kayarine 執行的 Hook 與其他插件衝突
3. Elementor 在渲染 Kayarine 元素時變慢

**解決方案**：
- 優化 Kayarine 的 Hook 優先級
- 延遲加載非必要資源
- 分離關鍵/非關鍵 CSS/JS

### 情景 B：禁用 Kayarine 後仍為 ~3 秒

**結論**：Kayarine 不是瓶頸，其他插件或 WordPress 核心有問題

**可能原因**：
1. **Elementor** 初始化很慢
2. **其他插件**（WooCommerce、Flexible Shipping 等）
3. **資料庫查詢** 很慢
4. **CloudFlare API 呼叫** 延遲

**解決方案**：
- 逐一禁用其他插件測試
- 檢查資料庫查詢
- 分析 CloudFlare 日誌

---

## 第三步：PHP-FPM 進程數設置（旺季準備）

### 基於旺季流量計算

**旺季流量**：10,000-15,000 訪問/月
```
每天：333-500 訪問
每小時：14-21 訪問
每分鐘：0.23-0.35 訪問
峰值：可能 3 倍（70-105 訪問/小時 = 1.2-1.75/分鐘）
```

### 進程數計算

**假設**：
- 每個請求耗時：2 秒（WordPress + Elementor）
- 同時進行的請求：最多 5 個（峰值）

**計算**：
```
最大並發請求 = （訪問/秒） × （響應時間秒數）
            = 1.75/秒 × 2秒
            = 3.5 個請求

加上安全裕度（50%）= 5-6 個進程
```

### 推薦配置

| 階段 | max_children | start_servers | min_spare | max_spare | 用途 |
|-----|-------------|---------------|-----------|-----------|------|
| 現在（測試） | 2 | 2 | 1 | 2 | 低流量，節省資源 |
| 小流量 | 3 | 2 | 1 | 3 | 季節性低谷 |
| 旺季 | 6 | 3 | 2 | 4 | 10k-15k/月流量 |

---

## 第四步：安全的進程數增加計劃

### 方案 1：保守方案（低風險）

先從 1 → 2 個進程開始，逐步增加

```bash
# 配置 1：2 個進程
pm.max_children = 2
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 2
```

### 方案 2：適中方案（推薦）

根據旺季需求設置 3-4 個進程

```bash
# 配置 2：3 個進程
pm.max_children = 3
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

### 方案 3：充分準備（可選）

為旺季設置 5-6 個進程

```bash
# 配置 3：6 個進程
pm.max_children = 6
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
```

---

## 記憶體計算

**當前記憶體消耗**：
- 診斷顯示：1 個 PHP-FPM 進程 ~216MB
- 伺服器記憶體：512MB（推定）

**進程數與記憶體的關係**：

| 進程數 | 記憶體消耗 | 剩餘記憶體 | 風險 |
|-------|----------|---------|------|
| 1 | ~216MB | ~296MB | ✅ 安全 |
| 2 | ~432MB | ~80MB | ✅ 安全 |
| 3 | ~648MB | -136MB | ⚠️ 可能崩潰 |

**結論**：**不能超過 2 個進程**（會超出 512MB 記憶體）

---

## 推薦方案：2 個進程

基於以上分析：

```bash
# 安全且有改進的配置
pm.max_children = 2         # 最多 2 個進程（512MB 限制）
pm.start_servers = 1        # 啟動 1 個
pm.min_spare_servers = 1    # 最少 1 個
pm.max_spare_servers = 2    # 最多 2 個
```

**預期改進**：
- 當前（1 進程）：3 秒
- 修改後（2 進程）：2-2.5 秒（改進 ~500ms）
- **但仍需解決根本原因**

---

## ⚠️ 重要：如果增加進程仍無改進

如果從 1→2 個進程後，性能仍無改進，說明問題 **不是進程不足**，而是：

1. **單個請求就是慢**
2. **需要優化代碼或禁用插件**

---

## 完整執行步驟

### Step 1：確認瓶頸（必需）

```bash
# 禁用 Kayarine 測試
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'TEST'
cd /opt/bitnami/wordpress
sudo -u www-data wp plugin deactivate kayarine-booking
sudo -u www-data wp cache flush
echo "✓ Kayarine 已停用，測試頁面載入時間"
TEST

# → 測試首頁，記錄載入時間
# → 如果恢復至 1 秒，是 Kayarine 問題
# → 如果仍為 3 秒，是其他問題
```

### Step 2：重新啟用 Kayarine

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'REVERT'
cd /opt/bitnami/wordpress
sudo -u www-data wp plugin activate kayarine-booking
sudo -u www-data wp cache flush
REVERT
```

### Step 3：（可選）增加進程至 2

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'PROCESS'
CONFIG="/opt/bitnami/php/etc/php-fpm.d/www.conf"

# 備份
sudo cp "$CONFIG" "$CONFIG.backup.$(date +%s)"

# 修改為 2 個進程
sudo sed -i 's/^pm\.max_children = .*/pm.max_children = 2/' "$CONFIG"
sudo sed -i 's/^pm\.start_servers = .*/pm.start_servers = 1/' "$CONFIG"
sudo sed -i 's/^pm\.min_spare_servers = .*/pm.min_spare_servers = 1/' "$CONFIG"
sudo sed -i 's/^pm\.max_spare_servers = .*/pm.max_spare_servers = 2/' "$CONFIG"

echo "✓ 配置已修改為 2 個進程"

# 重啟
sudo systemctl restart php-fpm
sleep 2

# 驗證
ps aux | grep "php-fpm: pool www" | grep -v grep | wc -l
echo "進程數已調整"
PROCESS
```

---

## 流量計算工作表

|  | 值 |
|------|-----|
| 旺季月訪問 | 10,000-15,000 |
| 日均訪問 | 333-500 |
| 時均訪問 | 14-21 |
| 峰值倍數 | 3x |
| 峰值時均 | 42-63 |
| 峰值秒均 | 0.7-1.75 |
| 響應時間 | 2 秒 |
| 最大並發 | 1.4-3.5 個 |
| 推薦進程數（含裕度） | 3-5 個 |
| **實際可用進程**（512MB） | **2 個** ⚠️ |

---

## 結論

1. **先確認瓶頸**：禁用 Kayarine 看是否恢復
2. **記憶體限制**：512MB 只能安全支持 2 個進程
3. **增加至 2 個**：預計改進 ~500ms，但可能不足
4. **如果仍慢**：問題在於單個請求本身，需要代碼優化
5. **升級伺服器**：如果要支持更多進程，需要升級記憶體至 1GB+

