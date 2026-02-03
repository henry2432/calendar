# CloudFlare Rocket Loader 診斷指南

## 問題背景
- 當前頁面加載時間：2-3 秒
- 目標：1.3 秒
- PHP-FPM 優化已完成，性能無顯著改善
- **高度懷疑：CloudFlare Rocket Loader 是主要瓶頸**（已知會增加 1-2 秒延遲）

## Rocket Loader 是什麼？
CloudFlare 的 Rocket Loader 是一個 JavaScript 優化功能，聲稱能改善性能，但實際上：
- ❌ 延遲所有 JavaScript 執行
- ❌ 增加 1-2 秒頁面加載時間
- ❌ 與某些 WordPress 插件相容性問題
- ❌ 特別是與 Elementor、WooCommerce 衝突

---

## 立即檢查步驟

### 1️⃣ 登入 CloudFlare 儀表板
- 訪問：https://dash.cloudflare.com
- 選擇您的域名：kayarine.hk（或您的域名）
- 進入左側菜單 **Speed** → **Optimization**

### 2️⃣ 尋找 Rocket Loader 設置
在 **Optimization** 頁面中，查找以下選項：
```
⚙️ Rocket Loader
   [啟用] / [停用]
```

### 3️⃣ 檢查目前狀態
- 如果 Rocket Loader 顯示 **「On」**（藍色開啟），**立即關閉**
- 如果顯示 **「Off」**（灰色關閉），則 Rocket Loader 不是瓶頸

---

## 禁用 Rocket Loader 步驟

### 方法 A：CloudFlare 儀表板（推薦）
```
1. 登入 CloudFlare 儀表板
2. Speed → Optimization
3. Rocket Loader 開關 → 點擊關閉
4. 等待生效（通常 5-10 分鐘）
5. 清除所有快取並重新測試
```

### 方法 B：直接 HTML 禁用（如果儀表板沒有此選項）
在 WordPress 頁面 head 中添加：
```html
<meta http-equiv="cf-incompatibility-version" content="0" />
```

---

## 測試流程

### 禁用前測試
```bash
1. 打開 Chrome DevTools (F12)
2. Network 標籤
3. 禁用快取（勾選 Disable cache）
4. 重新載入頁面
5. 記錄總加載時間（秒）
```

### 禁用 Rocket Loader
```
登入 CloudFlare → Speed → Rocket Loader → 關閉
等待 5-10 分鐘生效
```

### 禁用後測試
```bash
1. 清除瀏覽器快取
2. 清除 NitroPack 快取（如適用）
3. 再次在 Chrome DevTools 中測試
4. 記錄新的加載時間
```

---

## 預期改善

如果 Rocket Loader 是瓶頸：
```
禁用前：2.5-3.0 秒
禁用後：1.3-1.8 秒 ✅
改善幅度：-700-1500ms
```

---

## 其他 CloudFlare 優化設置檢查

同時檢查以下設置（可能也影響性能）：

### 1. Auto Minify（自動最小化）
```
Speed → Optimization → Auto Minify
✅ 應啟用：JavaScript, CSS, HTML
```

### 2. Brotli 壓縮
```
Speed → Optimization → Brotli
✅ 應啟用
```

### 3. Prefetch Preload
```
Speed → Optimization → Prefetch Preload
⚠️ 根據需要啟用/禁用（可能與 Elementor 衝突）
```

### 4. Early Hints
```
Speed → Optimization → Early Hints
⚠️ 新功能，可嘗試啟用
```

### 5. Polish（圖像優化）
```
Speed → Optimization → Polish
✅ 啟用「Lossy」或「Lossless」
```

---

## 檢查 CloudFlare 緩存設置

### 缓存等級
```
Caching → Cache Level
✅ 推薦：Cache Everything（使用 Page Rule）
❌ 避免：Standard（默認，緩存較少）
```

### 瀏覽器快取 TTL
```
Caching → Browser Cache TTL
✅ 推薦：30 days（月級別）
```

---

## 診斷工具

### 檢查 CloudFlare 是否在處理流量
```bash
# SSH 查看 HTTP 標頭
curl -I https://kayarine.hk | grep -i 'cf-'
```

預期輸出：
```
cf-cache-status: HIT
cf-ray: XXXXX
x-powered-by: CloudFlare
```

---

## 下一步

### 立即執行：
1. ✅ 禁用 CloudFlare Rocket Loader
2. ✅ 清除所有快取
3. ✅ 測試頁面加載時間
4. ✅ 報告新的測試結果

### 若仍然 2-3 秒，則檢查：
1. Elementor CSS/JS 資產優化
2. 資料庫查詢性能（使用 SAVEQUERIES 診斷）
3. 個別插件性能影響

---

## 聯繫信息
如需進一步診斷，可提供：
- 禁用前後的 Chrome DevTools 加載時間截圖
- CloudFlare 儀表板的 Speed Insights 報告
- NitroPack 的性能診斷結果

---

**預計時間表：**
- 禁用 Rocket Loader：1-2 分鐘
- CloudFlare 生效：5-10 分鐘
- 測試：5 分鐘
- **總計：15 分鐘內看到效果**
