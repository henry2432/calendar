# CloudFlare 性能最佳化設定指南

## 🎯 目標
將頁面轉頁時間從 2-3 秒降至 **1.3 秒以下**

---

## 🔧 Step 1：禁用 Challenge 和不必要的安全規則

### A. 禁用 Challenge Page
1. 登入 CloudFlare Dashboard：https://dash.cloudflare.com
2. 選擇 **kayarine.club**
3. 左側選單 → **Security** → **Settings**
4. 找到以下設定並調整：

| 設定 | 建議值 | 原因 |
|------|------|------|
| **Security Level** | Medium | Low = 無保護，High/Under Attack = 過多 Challenge |
| **Bot Management** | Disable（免費版無此功能） | Challenge 導致 1-2 秒延遲 |
| **Browser Integrity Check** | OFF | 移除額外檢查 |

### B. 禁用或調整 WAF Rules
1. **Security** → **WAF**
2. 檢查 **Managed Rules**
3. 禁用過度保護的規則：
   - ❌ 禁用：Rate Limiting（除非有特定需求）
   - ❌ 禁用：所有 Challenge 類規則
   - ✅ 保留：SQL Injection、XSS 防護

### C. Firewall Rules
1. **Security** → **Firewall Rules**
2. 刪除任何包含 `(cf.threat_score > X)` 或 `challenge` 的規則
3. 確保沒有針對您 IP 的阻止規則

---

## 🚀 Step 2：啟用快取和壓縮

### A. 啟用頁面快取
1. **Caching** → **Configuration**
2. 設定以下選項：

| 設定 | 推薦值 |
|------|------|
| Browser Cache TTL | 1 hour（或 4 hours） |
| Cache Level | **Cache Everything** |
| Edge Cache TTL | 1 day |

### B. 啟用 Gzip 和 Brotli 壓縮
1. **Speed** → **Optimization**
2. 確保以下已啟用：

```
✅ Brotli Compression: ON
✅ Gzip Compression: ON
✅ Minify: HTML, CSS, JavaScript 全部開啟
✅ Polish: OFF（除非您有 Pro 計畫）
✅ Rocket Loader: OFF（可能與 AJAX 衝突）
```

### C. 啟用 HTTP/2 和 HTTP/3
1. **Network**
2. 確保以下已啟用：

```
✅ HTTP/2: ON
✅ HTTP/3 (QUIC): ON
✅ HTTP/2 Server Push: OFF（可選）
✅ 0-RTT Connection Resumption: ON
```

---

## 📊 Step 3：最佳化性能設定

### A. 禁用不必要的功能
1. **Speed** → **Optimization**

| 功能 | 設定 | 原因 |
|------|------|------|
| **Email Obfuscation** | OFF | 不需要時禁用 |
| **Automatic HTTPS Rewrites** | ON | 確保 HTTPS |
| **Always Use HTTPS** | ON | 強制 HTTPS |
| **Opportunistic Encryption** | ON | 加速 |

### B. 啟用 Adaptive Acceleration（如果可用）
1. **Speed** → **Optimization**
2. 檢查 **Adaptive Acceleration** 並啟用

### C. Image Optimization（可選）
1. **Speed** → **Image Optimization**
2. 如果有 Pro 計畫，啟用：
   - ✅ Mirage：OFF（除非需要）
   - ✅ Polish：Lossy 或 OFF
   - ✅ WebP：ON

---

## 🔍 Step 4：驗證設定

### 測試 1：清除快取
1. **Caching** → **Purge Cache**
2. 選擇 **Purge Everything**
3. 等待 1 分鐘

### 測試 2：用無痕模式訪問
```bash
# Windows: Ctrl + Shift + N
# Mac: Cmd + Shift + N
```
1. 訪問 https://kayarine.club
2. 開啟 F12 → Network 標籤
3. 觀察轉頁時間
4. 預期：**0.8-1.3 秒**

### 測試 3：檢查 HTTP 響應頭
```bash
curl -I https://kayarine.club/account
```

應該看到：
```
✅ cf-mitigated: （不應出現 "challenge"）
✅ cache-control: public（而非 private）
✅ cf-cache-status: HIT（表示快取命中）
```

---

## ⚙️ Step 5：進階設定（可選）

### A. Page Rules（若需要特定優化）
1. **Rules** → **Page Rules**
2. 新增規則：

```
URL Pattern: kayarine.club/*
設定:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 day
  - Browser Cache TTL: 1 hour
```

### B. 設定 Cache-Control Headers（在 WordPress 中）
在 WordPress `wp-config.php` 或 `.htaccess` 中：

```php
// wp-config.php
define( 'COMPRESS_GZIP', true );
define( 'WP_MEMORY_LIMIT', '256M' );
```

```apache
# .htaccess（Elementor 快取）
<FilesMatch "\.(jpg|jpeg|png|gif|css|js|woff|woff2)$">
  Header set Cache-Control "public, max-age=31536000"
</FilesMatch>
```

---

## 📋 完整檢查清單

- [ ] 禁用 Challenge（Security Level: Medium）
- [ ] 禁用過度 WAF 規則
- [ ] 啟用 Cache Everything
- [ ] Browser Cache TTL: 1 hour
- [ ] Brotli 和 Gzip 壓縮：ON
- [ ] HTTP/2 和 HTTP/3：ON
- [ ] Minify HTML/CSS/JS：ON
- [ ] Rocket Loader：OFF
- [ ] 清除快取（Purge Everything）
- [ ] 用無痕模式測試
- [ ] 驗證 cf-cache-status: HIT

---

## 🎯 預期結果

完成上述設定後：

| 指標 | 目標 | 預期達成 |
|------|------|---------|
| 頁面轉頁時間 | 1.3 秒 | ✅ 0.8-1.3 秒 |
| 首字節時間（TTFB） | < 0.5 秒 | ✅ 0.2-0.4 秒 |
| 快取命中率 | > 80% | ✅ 90%+ |
| 壓縮率 | > 60% | ✅ 65-75% |

---

## 🆘 如果仍未改善

1. **檢查 WAF 日誌**
   - Security → Events
   - 查看是否還有 Challenge 被觸發

2. **查看 Analytics**
   - Analytics → Web Traffic
   - 檢查平均 TTFB

3. **聯絡 CloudFlare 支持**
   - 詢問是否有其他隱藏的安全規則

---

## 📝 Notes

- CloudFlare 免費版無法禁用 Challenge，但可以大幅降低觸發頻率
- 如果需要完全禁用 Challenge，需要升級到 Pro 計畫
- 最重要的是禁用 Bot Management 和 Rate Limiting（如果啟用了的話）
