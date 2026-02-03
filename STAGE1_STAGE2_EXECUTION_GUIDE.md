# 階段 1 + 2 執行指南（安全優化計劃）

## 概述
- **階段 1：** 安全診斷（只讀，0 風險）- 5 分鐘
- **階段 2：** 軟設置優化（可隨時回滾）- 10 分鐘
- **預計改善：** 100-300ms
- **風險等級：** 0 - 無任何數據庫修改

---

## 階段 1：安全診斷（只讀操作）

### 1️⃣ 檢查 NitroPack 當前 JavaScript 延遲設置

**操作：** 登入 WordPress 後台

```
WP Admin → NitroPack → Settings → Advanced
```

**檢查以下項目，並記錄當前狀態：**

```
☑️ Delay Non-Critical JavaScript
   當前設置：[  ]

☑️ Delay All JavaScript
   當前設置：[  ]  ← 關鍵項目！

☑️ Optimize Critical CSS
   當前設置：[  ]

☑️ Lazy Load JavaScript
   當前設置：[  ]

☑️ Remove Render-Blocking Resources
   當前設置：[  ]
```

**預期發現：** 如果「Delay All JavaScript」啟用，可能導致 200-500ms 延遲

---

### 2️⃣ 檢查 WordPress 自動保存設置

**操作：** SSH 訪問伺服器

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 檢查 wp-config.php 當前設置
grep -E 'AUTOSAVE_INTERVAL|WP_POST_REVISIONS|EMPTY_TRASH_DAYS|SAVEQUERIES' /opt/bitnami/wordpress/wp-config.php

# 檢查 WordPress 選項
cd /opt/bitnami/wordpress && /opt/bitnami/php/bin/php -r "
include 'wp-load.php';
echo 'AUTOSAVE_INTERVAL: ' . AUTOSAVE_INTERVAL . PHP_EOL;
echo 'WP_POST_REVISIONS: ' . WP_POST_REVISIONS . PHP_EOL;
echo 'EMPTY_TRASH_DAYS: ' . EMPTY_TRASH_DAYS . PHP_EOL;
"
```

**預期結果：**
```
AUTOSAVE_INTERVAL: 60         ← 應改為 300（每 5 分鐘）
WP_POST_REVISIONS: -1         ← 應改為 3（只保留 3 版本）
EMPTY_TRASH_DAYS: 30          ← 應改為 0（立即刪除）
```

---

### 3️⃣ 檢查 WordPress 快取設置

```bash
# 檢查 wp-config.php 中的快取設置
grep -i 'cache\|transient' /opt/bitnami/wordpress/wp-config.php
```

**預期結果：** 檢查是否有多餘的快取設置干擾

---

## 階段 2：軟設置優化（可隨時回滾）

### 1️⃣ 優化 NitroPack JavaScript 延遲

**如果在階段 1 發現「Delay All JavaScript」啟用：**

```
WP Admin → NitroPack → Settings → Advanced

找到「Delay All JavaScript」
改為：「Partial」或「Disable」

說明：
- ❌ Delay All JavaScript：延遲所有 JS，包括關鍵 JS（導致延遲）
- ✅ Partial：只延遲非關鍵 JS（更平衡）
- ✅ Disable：不延遲（最快，但占用更多帶寬）
```

**保存設置後：**
```
WP Admin → NitroPack → 點擊「Purge Cache」
清除瀏覽器快取（Cmd+Shift+Delete）
```

---

### 2️⃣ 優化 WordPress 自動保存

**編輯 `/opt/bitnami/wordpress/wp-config.php`**

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 備份原文件
cp /opt/bitnami/wordpress/wp-config.php /opt/bitnami/wordpress/wp-config.php.backup.$(date +%s)

# 編輯文件（使用 nano）
sudo nano /opt/bitnami/wordpress/wp-config.php
```

**在文件中找到這一行：**
```
/* That's all, stop editing! */
```

**在這行之前添加：**
```php
// 性能優化
define( 'AUTOSAVE_INTERVAL', 300 );      // 改為每 5 分鐘自動保存一次
define( 'WP_POST_REVISIONS', 3 );        // 只保留最近 3 個修訂版本
define( 'EMPTY_TRASH_DAYS', 0 );         // 垃圾桶中的項目立即刪除，不保留 30 天
```

**保存文件：** Ctrl+O，Enter，Ctrl+X

**驗證添加成功：**
```bash
grep -A3 '// 性能優化' /opt/bitnami/wordpress/wp-config.php
```

---

### 3️⃣ 重啟 PHP-FPM 以應用設置

```bash
sudo systemctl restart bitnami.php-fpm.service
sleep 2
echo "PHP-FPM 已重啟"
```

---

### 4️⃣ 清除所有快取

```bash
# 清除 WordPress 快取
rm -rf /opt/bitnami/wordpress/wp-content/cache/*

# 清除 Elementor 快取
rm -rf /opt/bitnami/wordpress/wp-content/uploads/elementor/cache/*

# 清除 NitroPack 快取（通過 WP Admin 進行會更安全）
# WP Admin → NitroPack → 點擊「Purge Cache」
```

---

## 測試新設置

### 1️⃣ 清除瀏覽器快取
```
Chrome: Cmd+Shift+Delete
選擇「All time」
點擊「Clear data」
```

### 2️⃣ 訪問頁面並測試加載時間
```
打開 Chrome DevTools (F12)
進入 Network 標籤
勾選「Disable cache」
重新載入頁面
查看底部「Load」時間（秒）
```

### 3️⃣ 記錄結果
```
修改前：______ 秒
修改後：______ 秒
改善：______ 秒
```

---

## 可隨時回滾的步驟

如果修改後性能變差，可以輕易恢復：

### 恢復 NitroPack 設置
```
WP Admin → NitroPack → Settings → Advanced
改回原來的設置
```

### 恢復 WordPress 設置
```bash
# 恢復備份的 wp-config.php
ssh -i ... kayarine.server@104.199.144.122
cp /opt/bitnami/wordpress/wp-config.php.backup.* /opt/bitnami/wordpress/wp-config.php
sudo systemctl restart bitnami.php-fpm.service
```

---

## 預期改善結果

### 如果「Delay All JavaScript」被禁用
```
改善：200-500ms
新加載時間：2.0-2.3 秒（從 2.3-2.8 秒）
```

### 如果 AUTOSAVE_INTERVAL 被改為 300
```
改善：30-100ms
原因：減少自動保存導致的數據庫寫入
```

### 如果 WP_POST_REVISIONS 被改為 3
```
改善：20-50ms
原因：減少修訂版本存儲和查詢
```

### 總計預期改善
```
100-300ms 累積改善
新加載時間目標：2.0-2.5 秒
```

---

## 立即執行清單

### 現在就做：
- [ ] 檢查 NitroPack JavaScript 延遲設置
- [ ] 檢查 WordPress 自動保存設置
- [ ] 編輯 wp-config.php 添加性能優化配置
- [ ] 清除所有快取
- [ ] 重啟 PHP-FPM
- [ ] 測試新加載時間
- [ ] 報告改善結果

---

## 下一步

完成階段 1+2 後：

1. **如果改善至 1.5-2.0 秒：**
   - ✅ 暫停優化，保持當前設置

2. **如果仍為 2.3+ 秒：**
   - 準備進行階段 3（數據庫優化）
   - 需要完整備份

---

## 聯繫和幫助

如果在執行過程中出錯：
1. 恢復備份的 wp-config.php
2. 重新啟動 PHP-FPM
3. 報告具體錯誤信息

---

**預計完成時間：15 分鐘**
