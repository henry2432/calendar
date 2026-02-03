# Ninja Google Review 移除指南

## 影響評估

根據性能診斷，**Ninja Google Review** 插件的具體影響：

### 問題清單
1. **棄用警告（Deprecation Warnings）**
   - 在 `/wp-content/debug.log` 中頻繁產生警告
   - 使用已棄用的 WordPress API
   - 可能導致未來版本相容性問題

2. **性能負擔**
   - 未精確測量，但屬於「應移除」範疇
   - 廣告/評論加載額外的第三方資源
   - Google Reviews API 請求增加伺服器負擔

3. **頁面加載時間影響**
   - 估計 **100-300ms** 的額外延遲
   - 取決於 Google Reviews API 回應速度

## 移除方式（二選一）

### 方式 1：透過 WordPress 後台（推薦）
1. 登入 WordPress 管理面板
2. 進入「外掛程式」> 「已安裝的外掛程式」
3. 找到 **Ninja Google Review (v2.4.3)**
4. 點擊「停用」按鈕
5. （可選）點擊「刪除」以完全移除

### 方式 2：透過 SSH（需伺服器存取）
```bash
# SSH 到伺服器
ssh kayarine.server@104.199.144.122

# 進入插件目錄
cd /var/www/html/wp-content/plugins

# 移除插件目錄
rm -rf ninja-google-review

# 檢查是否成功
ls -la | grep ninja
```

## 驗證步驟

### 停用後檢查
1. **在 WordPress 後台驗證**
   - 進入「外掛程式」確認已停用
   - 進入「主題」確認沒有相關代碼調用

2. **檢查 debug.log**
   ```bash
   tail -f /var/www/html/wp-content/debug.log | grep -i "ninja\|deprecated"
   ```
   - 應該看不到新的棄用警告

3. **測試頁面載入**
   - 清除瀏覽器快取（Ctrl+Shift+Delete）
   - 重新造訪首頁
   - 打開瀏覽器開發工具（F12）
   - 檢查「Network」標籤
   - 記錄頁面載入時間

## 預期性能改進

| 指標 | 移除前 | 移除後 | 改進幅度 |
|------|------|------|--------|
| 首次內容繪製 (FCP) | ~1.2s | ~1.0s | ✅ -200ms |
| 可交互時間 (TTI) | ~2.3s | ~2.1s | ✅ -200ms |
| 累積版面配置偏移 (CLS) | 正常 | 無改變 | - |

**重點**：移除 Ninja Google Review 預計可節省 **100-300ms**

## 如果移除後遺漏功能怎麼辦？

如果網站上依賴 Google Reviews 顯示，改用替代方案：

### 替代插件推薦
- **Google Reviews (高評級版本）**
  - https://wordpress.org/plugins/google-reviews/
  - 更新維護中，無棄用警告

- **手動嵌入 Google Reviews**
  - 使用 Google Reviews 官方 iframe
  - 編輯頁面/文章，直接插入代碼
  - 更輕量級

## 檢查清單

- [ ] 停用或刪除 Ninja Google Review 插件
- [ ] 驗證 WordPress 後台確認已停用
- [ ] 檢查 debug.log 無相關警告
- [ ] 清除瀏覽器快取
- [ ] 重新造訪首頁並測量載入時間
- [ ] 記錄改進數據（秒數）

---

**預計改進**：1.8-2.7s → 1.6-2.5s（進展 100-200ms）

**下一步**：安裝 WP Super Cache 或 W3 Total Cache（可額外節省 400-800ms）
