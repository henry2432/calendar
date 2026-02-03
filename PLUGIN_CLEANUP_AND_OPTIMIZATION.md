# 插件清潔與優化指南

## 🎉 進度更新
- ✅ **Ninja Google Review 已刪除**
- ⚠️ **需要清理殘留資料**
- ⚠️ **發現多個 Kayarine 舊版本需清理**

---

## 第一部分：Ninja Google Review 殘留清理

### 1. 清理 WordPress 資料庫中的殘留資料

#### Step 1.1：檢查並刪除選項（Options）
```sql
-- 檢查 Ninja Google Review 相關選項
SELECT * FROM wp_options 
WHERE option_name LIKE '%ninja%google%' 
OR option_name LIKE '%ninjagooglereview%'
OR option_name LIKE '%njgr%';

-- 刪除這些選項（執行前備份！）
DELETE FROM wp_options 
WHERE option_name LIKE '%ninja%google%' 
OR option_name LIKE '%ninjagooglereview%'
OR option_name LIKE '%njgr%';
```

#### Step 1.2：檢查並刪除 Transients（臨時快取）
```sql
-- 檢查過期的 Transients
SELECT * FROM wp_options 
WHERE option_name LIKE '%transient%ninja%'
OR option_name LIKE '%transient%ngr%';

-- 刪除（通常已自動清除，但檢查一下）
DELETE FROM wp_options 
WHERE option_name LIKE '%transient%ninja%'
OR option_name LIKE '%transient%ngr%';
```

### 2. 清理文件系統殘留

#### Step 2.1：通過 SSH 檢查和移除
```bash
# SSH 到伺服器
ssh kayarine.server@104.199.144.122

# 進入插件目錄
cd /var/www/html/wp-content/plugins

# 檢查是否有殘留目錄
ls -la | grep -i "ninja\|ngr"

# 如果有，完全移除（已停用後可安全移除）
sudo rm -rf ninja-google-review
sudo rm -rf ninja-google-review*

# 驗證已清除
ls -la | grep -i ninja
```

#### Step 2.2：清理上傳資料夾（如有）
```bash
# 檢查是否有插件上傳的資料
cd /var/www/html/wp-content/uploads

# 尋找相關資料
find . -iname "*ninja*" -o -iname "*ngr*"

# 如有則移除
find . -iname "*ninja*" -delete
find . -iname "*ngr*" -delete
```

### 3. 透過 WordPress CLI 驗證

```bash
# 檢查是否有註冊的 transients
wp transient get ninja-google-review-cache

# 刪除所有 transients（可選）
wp transient delete --all

# 檢查 Option 中的殘留
wp option list | grep -i ninja
```

### 4. 驗證清潔完成

#### 在 WordPress 後台
1. 進入「設定」→「一般」
2. 向下捲動至「雜項」
3. 檢查無 `njgr_` 或 `ninja_google_review_` 開頭的選項

#### 在 debug.log
```bash
# SSH 到伺服器
ssh kayarine.server@104.199.144.122

# 檢查是否有新的錯誤
tail -100 /var/www/html/wp-content/debug.log | grep -i "ninja\|ngr"

# 應該無結果或只有歷史記錄
```

---

## 第二部分：Kayarine 舊版本插件清理

### 1. 識別舊版本 Kayarine 插件

#### Step 1.1：列出所有已安裝的 Kayarine 插件
```bash
# SSH 到伺服器
ssh kayarine.server@104.199.144.122

# 進入插件目錄
cd /var/www/html/wp-content/plugins

# 列出所有 Kayarine 相關
ls -la | grep -i kayarine

# 預期結果類似：
# drwxr-xr-x  kayarine-booking
# drwxr-xr-x  kayarine-booking.backup.1770094261
# drwxr-xr-x  kayarine-booking-old
# drwxr-xr-x  kayarine-booking-v1.4.0
# ... 等多個舊版本
```

#### Step 1.2：檢查版本號
```bash
# 檢查每個 Kayarine 插件的 header
for dir in kayarine-*; do
    echo "=== $dir ==="
    head -20 "$dir/"*.php | grep "Version:"
done
```

### 2. 確定哪個是當前活動版本

```bash
# 進入 WordPress 安裝目錄
cd /var/www/html

# 使用 WP-CLI 檢查
wp plugin list | grep kayarine

# 預期結果：
# kayarine-booking    active      1.4.14
# kayarine-booking-old    inactive    1.4.0
# kayarine-booking.backup... inactive    1.4.13
# ...
```

### 3. 清理舊版本

#### ⚠️ 警告：只刪除 INACTIVE 的版本

```bash
# SSH 到伺服器
ssh kayarine.server@104.199.144.122

cd /var/www/html/wp-content/plugins

# 列出所有 Kayarine 相關且不是當前活動版本
# 確認版本號後再刪除

# 範例：刪除舊版本（確認版本號後）
sudo rm -rf kayarine-booking.backup.1770094261
sudo rm -rf kayarine-booking-old
sudo rm -rf kayarine-booking-v1.4.0
sudo rm -rf kayarine-booking-1.4.0
# ... 等等其他舊版本

# ✅ 保留：kayarine-booking（當前活動版本 v1.4.14）
```

### 4. 驗證清潔完成

```bash
# 確認只有一個活動的 Kayarine
wp plugin list | grep kayarine

# 應該只看到一個條目：
# kayarine-booking    active      1.4.14

# 驗證文件系統
ls -la /var/www/html/wp-content/plugins | grep kayarine
# 應該只看到：kayarine-booking/
```

### 5. 資料庫中的清理（如需要）

有些舊版本可能在資料庫中留下選項：

```sql
-- 檢查 Kayarine 相關選項
SELECT * FROM wp_options 
WHERE option_name LIKE '%kayarine%'
ORDER BY option_id DESC
LIMIT 20;

-- 檢查版本號（應該只有一個當前版本）
SELECT * FROM wp_options 
WHERE option_name = 'kayarine_booking_version' 
OR option_name = 'kayarine_version';
```

---

## 第三部分：性能改進驗證

### 1. 重新測量頁面載入時間

刪除 Ninja Google Review 後應該看到改進：

```
使用開發工具：
1. 開啟無痕視窗
2. 造訪首頁
3. F12 → Network 標籤
4. 記錄「Load」時間

預期改進：-100-200ms
```

### 2. 清理 NitroPack 快取

移除舊插件後，清理 NitroPack 快取確保最新版本：

```
WordPress 後台 → NitroPack → 快取
點擊「清除快取」
```

### 3. 檢查 WordPress 調試日誌

```bash
# SSH 到伺服器
tail -f /var/www/html/wp-content/debug.log

# 應該看不到與已刪除插件相關的錯誤
# 只看到正常的 Kayarine 日誌
```

---

## 檢查清單

### Ninja Google Review 殘留清理
- [ ] 刪除資料庫中的 Options
- [ ] 刪除資料庫中的 Transients
- [ ] 移除文件系統目錄
- [ ] 檢查上傳資料夾
- [ ] 驗證 debug.log 無相關錯誤

### Kayarine 舊版本清理
- [ ] 列出所有 Kayarine 插件版本
- [ ] 確認當前活動版本是 v1.4.14
- [ ] 移除所有舊版本和備份目錄
- [ ] 驗證 WordPress 後台只列出一個 Kayarine
- [ ] 檢查資料庫 Options 無舊版本配置

### 性能驗證
- [ ] 清除 NitroPack 快取
- [ ] 重新測量頁面載入時間
- [ ] 記錄改進幅度
- [ ] 驗證所有頁面正常工作

---

## 執行順序建議

### 立即執行（安全）
```
1. 資料庫清理（Ninja Google Review Options/Transients）
2. 文件系統清理（移除 ninja-google-review 目錄）
3. 驗證無錯誤
4. 測量性能改進
```

### 短期執行（也安全）
```
1. 列出所有 Kayarine 版本
2. 確認當前活動版本和備份
3. 確認無人使用舊版本後刪除
4. 清除 NitroPack 快取
5. 驗證效能
```

---

## 風險評估

| 步驟 | 風險 | 恢復時間 | 建議 |
|------|------|--------|------|
| 刪除 Ninja Options | 極低 | 無（已刪除） | ✅ 安全執行 |
| 移除 Ninja 文件 | 極低 | 5 分鐘重新安裝 | ✅ 安全執行 |
| 刪除舊 Kayarine | 低 | 5 分鐘 FTP 上傳 | ✅ 可執行（確認備份） |

---

## 預期性能改進

```
當前：2.5 秒（已刪除 Ninja Google Review）

清理後：
- 移除 Ninja 殘留資料：-10-20ms（資料庫查詢減少）
- 清理舊 Kayarine：-5-10ms（插件掃描加快）
- ─────────────────────────────
- 總計：2.45-2.48 秒（小幅改進）

累積效果：
- 代碼優化 + 移除 Ninja + 清理 = -550ms
- 預期：2.0-2.1 秒（vs 原始 2.5 秒）
```

---

## 下一步

1. **立即**：執行 Ninja Google Review 殘留清理（15 分鐘）
2. **短期**：清理 Kayarine 舊版本（10 分鐘）
3. **測量**：驗證性能改進
4. **聯繫主機商**：準備 PHP 8.1 升級

---

## 預期結果

**清理前**：2.5 秒（刪除 Ninja 後）
**清理後**：2.45-2.48 秒（輕微改進）
**最終目標**：1.7-2.0 秒（升級 PHP 後）→ **1.3 秒（完整優化）**

