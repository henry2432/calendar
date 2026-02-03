# 性能測試說明 - Kayarine 停用狀態

## ✅ 測試環境已準備

Kayarine 已停用，快取已清除。現在進行性能基準測試。

---

## 📊 測試步驟

### Step 1：清除瀏覽器快取

**Windows 用戶**：
```
按 Ctrl + Shift + Delete
選擇「所有時間」
點擊「清除數據」
```

**Mac 用戶**：
```
按 Cmd + Shift + Delete
選擇「所有時間」
點擊「清除數據」
```

### Step 2：開啟無痕視窗

**Windows / Mac / Linux**：
```
Chrome: Ctrl + Shift + N（或 Cmd + Shift + N）
訪問：https://kayarine.com.hk
```

### Step 3：開啟開發工具並測量

1. **按 F12** 開發工具
2. **進入 Network 標籤**
3. **重新整理頁面**（F5）
4. **等待頁面完全加載**
5. **查看最下方的統計**
   - 找「Load」時間（秒）
   - 或查看進度條完成時間

### Step 4：記錄結果

測試結果應包含：
```
⏱️ 頁面載入時間（秒）：_____

測試時間：2026-02-03 10:XX:XX
測試環境：Kayarine 已停用
```

---

## 📋 預期結果解釋

### 情景 A：恢復至 ~1 秒

**結果**：
```
之前（有 Kayarine）：3 秒
現在（無 Kayarine）：1 秒
改進：-2 秒
```

**結論**：✅ **Kayarine 是性能瓶頸**

**原因**：
- Kayarine 初始化導致 WordPress 變慢
- 或 Kayarine 與 Elementor 衝突
- 或 Kayarine 執行了耗時操作

**下一步**：需要優化 Kayarine 的代碼

---

### 情景 B：仍為 ~3 秒

**結果**：
```
之前（有 Kayarine）：3 秒
現在（無 Kayarine）：3 秒
改進：0 秒
```

**結論**：❌ **Kayarine 不是瓶頸**

**原因**：
- 其他插件導致性能下降
- WordPress 核心初始化慢
- Elementor 或其他主題級別問題
- CloudFlare 或伺服器配置

**下一步**：需要逐一禁用其他插件進行測試

---

## 🔄 恢復 Kayarine 命令

測試完成後，恢復 Kayarine：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'RESTORE'
cd /opt/bitnami/wordpress

echo "恢復 Kayarine..."
sudo bash -c "su -s /bin/bash www-data -c 'wp plugin activate kayarine-booking'"
echo "✓ Kayarine 已恢復"

sudo bash -c "su -s /bin/bash www-data -c 'wp cache flush'"
echo "✓ 快取已清除"
RESTORE
```

---

## ⏰ 測試時間表

| 步驟 | 耗時 |
|------|------|
| 清除快取 | 1 分鐘 |
| 開啟無痕視窗 | 1 分鐘 |
| 測量載入時間 | 2 分鐘 |
| 記錄結果 | 1 分鐘 |
| **總計** | **~5 分鐘** |

---

## 📌 重要提示

1. **使用無痕視窗**：避免舊快取影響結果
2. **多次測試**：至少 3 次，取平均值
3. **檢查網絡**：確保網速穩定（不要在下載時測試）
4. **時間選擇**：選擇非尖峰時間（無其他用戶使用時）
5. **記錄詳細**：包含時間戳、測試次數、環境信息

---

## 💬 預期下一步

### 如果 Kayarine 是瓶頸（情景 A）：

1. 分析 Kayarine 的 Hook 優先級
2. 檢查是否有耗時的初始化操作
3. 優化 Elementor 集成
4. 延遲加載非必要資源

### 如果不是 Kayarine（情景 B）：

1. 禁用其他插件逐一測試
2. 識別具體導致變慢的插件
3. 更換或卸載該插件
4. 或聯繫插件開發者報告性能問題

---

## 🔧 進階測試（可選）

如果想了解更詳細的性能數據：

**在 Chrome 開發工具 Network 標籤中查看**：
- HTML 載入時間
- JavaScript 執行時間
- CSS 樣式計算時間
- 最慢的資源（可能是第三方 API）

**使用 PageSpeed Insights**：
1. 訪問 https://pagespeed.web.dev/
2. 輸入 https://kayarine.com.hk
3. 檢查「首次內容繪製」(FCP) 和「最大內容繪製」(LCP) 時間

---

## 📝 測試報告模板

測試完成後，請提供以下信息：

```
=== 性能測試結果 ===

無 Kayarine 狀態（停用後）：
- 頁面載入時間：___ 秒
- 測試次數：3 次
- 最快時間：___ 秒
- 最慢時間：___ 秒
- 平均時間：___ 秒

結論：
□ 恢復至 ~1 秒（Kayarine 是瓶頸）
□ 仍為 ~3 秒（其他瓶頸）

測試時間：2026-02-03 XX:XX:XX
測試環境：
- 瀏覽器：Chrome（無痕）
- 網絡：正常
- CloudFlare：有/無 Challenge
```

---

**準備好開始測試了嗎？請按上述步驟進行，然後報告結果！**

