# WordPress 後台清潔方案 - SSH 超時替代

由於 SSH 連接不穩定，改用 WordPress 後台進行清潔。無需遠程連接，你完全控制進度。

---

## 第一步：刪除舊版本 Kayarine 插件

### 進入 WordPress 後台
1. 訪問：`https://你的網站/wp-admin`
2. 登入你的 WordPress 帳戶
3. 左邊欄 → **外掛程式**

### 列出所有 Kayarine 相關插件
在「已安裝的外掛程式」中搜尋「kayarine」

**應該看到**：
```
□ kayarine-booking (v1.4.14) - 活躍
□ kayarine-booking.backup.1770094261 - 停用
□ kayarine-booking-old - 停用
□ kayarine-booking-v1.4.0 - 停用
... 其他舊版本
```

### 刪除舊版本（逐一操作）

對每個 **非 kayarine-booking** 的版本：

1. 點擊該插件名稱
2. 如果顯示「停用」，先點「停用」
3. 返回列表
4. 找該插件，點擊「刪除」
5. 確認刪除

**重複步驟直到只剩 `kayarine-booking (v1.4.14)` 為止**

### 驗證
完成後搜尋「kayarine」：
- ✅ 應該只看到一個：`kayarine-booking (v1.4.14) - 活躍`

---

## 第二步：驗證 Ninja Google Review 已刪除

### 檢查插件列表
搜尋「Ninja」或「Google」
- ✅ 應該無結果

搜尋「Review」
- ✅ 應該無 Ninja Google Review 插件

---

## 第三步：清除快取

### NitroPack 快取
1. 左邊欄 → **外掛程式**
2. 找到 **NitroPack**
3. 點擊進入設定
4. 找「快取」或「Cache」選項卡
5. 點「清除快取」或「Clear Cache」
6. 等待完成

### WordPress 快取（可選）
如果看到快取相關選項：
1. **設定** → **WP Super Cache 或 W3 Total Cache**
2. 點「清除所有快取」

---

## 第四步：資料庫清潔（可選但推薦）

如果你有 phpMyAdmin 存取權：

### 進入 phpMyAdmin
1. 主機商通常提供 cPanel 或控制面板
2. 找「phpMyAdmin」並登入
3. 選擇 WordPress 資料庫（通常名為 `wordpress` 或 `kayarine_db`）

### 執行清潔 SQL

進入「SQL」選項卡，執行以下查詢：

```sql
-- 刪除 Ninja Google Review 相關選項
DELETE FROM wp_options 
WHERE option_name LIKE '%ninja%' 
   OR option_name LIKE '%ngr%'
   OR option_name LIKE '%ninjagooglereview%';

-- 清理臨時快取
DELETE FROM wp_options 
WHERE option_name LIKE '%transient%ninja%';
```

點擊「執行」按鈕。

---

## 第五步：測量性能改進

### 清除瀏覽器快取
1. 按 **Ctrl + Shift + Delete**（Windows）或 **Cmd + Shift + Delete**（Mac）
2. 清除「所有時間」的快取

### 測量載入時間
1. 開啟無痕視窗（Ctrl+Shift+N）
2. 造訪首頁：`https://你的網站`
3. 按 **F12** 開發工具
4. 進入「Network」標籤
5. 記錄「Load」時間

**預期改進**：
- 刪除前：2.3-2.7 秒
- 刪除後：2.1-2.5 秒（改進 100-200ms）

---

## 完整步驟摘要

| 步驟 | 操作 | 耗時 |
|------|------|------|
| 1 | 刪除舊 Kayarine 版本 | 5 分鐘 |
| 2 | 驗證 Ninja 已刪除 | 1 分鐘 |
| 3 | 清除 NitroPack 快取 | 2 分鐘 |
| 4 | 資料庫清潔（phpMyAdmin） | 3 分鐘 |
| 5 | 測量性能改進 | 3 分鐘 |
| **總計** | | **14 分鐘** |

---

## 檢查清單

- [ ] 進入 WordPress 後台
- [ ] 找到所有 Kayarine 版本
- [ ] 刪除除 v1.4.14 外的所有版本
- [ ] 驗證無 Ninja Google Review
- [ ] 清除 NitroPack 快取
- [ ] （可選）進入 phpMyAdmin 執行 SQL
- [ ] 清除瀏覽器快取
- [ ] 測量頁面載入時間
- [ ] 記錄改進幅度（秒數）

---

## 常見問題

### Q1：不確定哪個是當前版本？
**A**：當前版本應該是 `kayarine-booking` 且顯示為「活躍」狀態，版本號應為 v1.4.14。其他帶「.backup」、「-old」、「-v1.4.x」的都是舊版本。

### Q2：刪除舊版本會影響功能嗎？
**A**：不會。只有活躍的 `kayarine-booking (v1.4.14)` 在運作。舊版本檔案已停用，刪除它們不會影響任何功能。

### Q3：無法進入 phpMyAdmin？
**A**：可以跳過第四步。WordPress 後台清潔已足以改進性能。資料庫清潔只是額外優化。

### Q4：清除快取後網站變慢？
**A**：正常。快取需要時間重新生成。給它 1-2 分鐘，然後再測試。

---

## 下一步（短期）

清潔完成後：

1. **記錄改進數據**
   - 新的頁面載入時間
   - 與 Ninja 刪除前比較

2. **聯繫主機商升級 PHP**
   - 預期額外改進 200-300ms
   - 說詞：要求升級至 PHP 8.1 或 8.2

3. **驗證最終結果**
   ```
   初始：2.0-3.0 秒
   →清潔後：2.1-2.5 秒（-100-200ms）
   →PHP升級：1.7-2.0 秒（-300ms）
   ✅ 接近 1.3 秒目標
   ```

---

## 預期結果

**本步驟改進**：-100-200ms
**總累積改進**：-350-750ms
**最終預期**：1.3-2.0 秒（取決於 PHP 升級）

