# WordPress 核心優化指南（簡化版）

## 概述
- **目標：** 優化 WordPress 自動保存和修訂設置
- **操作時間：** 10 分鐘
- **風險等級：** 0 - 只修改配置文件，隨時可回滾
- **預計改善：** 50-150ms

---

## 步驟 1：備份 wp-config.php

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 創建備份
cp /opt/bitnami/wordpress/wp-config.php /opt/bitnami/wordpress/wp-config.php.backup.$(date +%s)

# 驗證備份成功
ls -la /opt/bitnami/wordpress/wp-config.php.backup.*
```

---

## 步驟 2：檢查當前設置

```bash
# 查看當前設置
grep -E 'AUTOSAVE_INTERVAL|WP_POST_REVISIONS|EMPTY_TRASH_DAYS' /opt/bitnami/wordpress/wp-config.php || echo "這些設置未定義，將使用默認值"

# 查看默認值
cd /opt/bitnami/wordpress && /opt/bitnami/php/bin/php -r "
include 'wp-load.php';
echo 'Current AUTOSAVE_INTERVAL: ' . AUTOSAVE_INTERVAL . ' seconds' . PHP_EOL;
echo 'Current WP_POST_REVISIONS: ' . WP_POST_REVISIONS . ' (unlimited if -1)' . PHP_EOL;
echo 'Current EMPTY_TRASH_DAYS: ' . EMPTY_TRASH_DAYS . ' days' . PHP_EOL;
" 2>/dev/null || echo "無法執行 PHP，使用編輯器方式"
```

---

## 步驟 3：編輯 wp-config.php

### 方法 A：使用 sed 自動替換（推薦，更安全）

```bash
# 檢查文件中是否已有這些設置
grep -c 'AUTOSAVE_INTERVAL\|WP_POST_REVISIONS\|EMPTY_TRASH_DAYS' /opt/bitnami/wordpress/wp-config.php

# 如果上面的命令返回 0，說明這些設置還不存在，需要添加

# 找到「That's all, stop editing!」的行號
grep -n "That's all, stop editing" /opt/bitnami/wordpress/wp-config.php

# 在該行之前插入優化配置（示例：假設在第 83 行）
sudo sed -i "83i \\
// ===== 性能優化配置 =====\\
define( 'AUTOSAVE_INTERVAL', 300 );      // 改為每 5 分鐘自動保存一次\\
define( 'WP_POST_REVISIONS', 3 );        // 只保留最近 3 個修訂版本\\
define( 'EMPTY_TRASH_DAYS', 0 );         // 垃圾桶中的項目立即刪除\\
// ========================\\
" /opt/bitnami/wordpress/wp-config.php
```

### 方法 B：手動編輯（如自動替換失敗）

```bash
# 使用 nano 編輯
sudo nano /opt/bitnami/wordpress/wp-config.php
```

**編輯步驟：**
1. 按 Ctrl+W 搜索 `That's all, stop editing`
2. 找到該行
3. 在該行之前添加以下代碼：

```php
// ===== 性能優化配置 =====
define( 'AUTOSAVE_INTERVAL', 300 );      // 改為每 5 分鐘自動保存一次
define( 'WP_POST_REVISIONS', 3 );        // 只保留最近 3 個修訂版本
define( 'EMPTY_TRASH_DAYS', 0 );         // 垃圾桶中的項目立即刪除
// ========================
```

4. 保存：Ctrl+O，Enter，Ctrl+X

---

## 步驟 4：驗證編輯成功

```bash
# 驗證新配置是否添加成功
grep -A3 '性能優化配置' /opt/bitnami/wordpress/wp-config.php

# 驗證語法（PHP 檢查）
/opt/bitnami/php/bin/php -l /opt/bitnami/wordpress/wp-config.php
```

**預期輸出：**
```
No syntax errors detected in /opt/bitnami/wordpress/wp-config.php
```

---

## 步驟 5：重啟 PHP-FPM

```bash
sudo systemctl restart bitnami.php-fpm.service

# 等待 2 秒
sleep 2

# 驗證 PHP-FPM 已啟動
sudo systemctl status bitnami.php-fpm.service | grep 'active (running)'
```

**預期輸出：**
```
active (running) since ...
```

---

## 步驟 6：清除快取

```bash
# 清除 WordPress 快取
rm -rf /opt/bitnami/wordpress/wp-content/cache/*

# 驗證清除成功
echo "快取已清除"
```

---

## 步驟 7：測試和驗證

### 在瀏覽器中測試

```
1. 清除瀏覽器快取
   Chrome: Cmd+Shift+Delete → All time → Clear data

2. 訪問網站首頁

3. 打開 Chrome DevTools (F12)
   → Network 標籤
   → 勾選「Disable cache」
   → 重新載入頁面
   → 查看底部「Load」時間（秒）

4. 多次測試以確保穩定性
   - 第 1 次載入：______ 秒
   - 第 2 次載入：______ 秒
   - 第 3 次載入：______ 秒
   - 平均時間：______ 秒
```

---

## 預計改善

```
修改前：2.3-2.8 秒
修改後：2.2-2.7 秒（預期改善 50-150ms）

具體改善來自：
- AUTOSAVE_INTERVAL (60 → 300)：30-100ms
- WP_POST_REVISIONS (-1 → 3)：20-50ms
```

---

## 如果需要回滾

### 恢復備份

```bash
# 列出備份文件
ls -la /opt/bitnami/wordpress/wp-config.php.backup.*

# 恢復最新備份（假設是 wp-config.php.backup.1738590000）
cp /opt/bitnami/wordpress/wp-config.php.backup.1738590000 /opt/bitnami/wordpress/wp-config.php

# 重啟 PHP-FPM
sudo systemctl restart bitnami.php-fpm.service

echo "已恢復備份"
```

---

## 執行清單

- [ ] 備份 wp-config.php
- [ ] 檢查當前設置
- [ ] 編輯 wp-config.php（添加優化配置）
- [ ] 驗證編輯成功（檢查語法）
- [ ] 重啟 PHP-FPM
- [ ] 清除快取
- [ ] 測試加載時間（多次）
- [ ] 報告改善結果

---

## 完成標誌

✅ 當您能看到以下結果時，說明優化成功：

```bash
# 驗證配置已應用
grep 'AUTOSAVE_INTERVAL' /opt/bitnami/wordpress/wp-config.php
# 應輸出：define( 'AUTOSAVE_INTERVAL', 300 );

# 驗證 PHP-FPM 運行正常
sudo systemctl status bitnami.php-fpm.service | grep 'active'
# 應輸出：active (running)

# 網頁加載時間改善
# 瀏覽器測試顯示改善
```

---

**預計完成時間：10 分鐘**
