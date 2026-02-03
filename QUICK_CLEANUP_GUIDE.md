# 快速清潔指南 - 無法遠程連接時

由於伺服器連接超時，提供以下解決方案供你自己執行。

---

## 方案 A：透過 WordPress 後台（最簡單）

### Step 1：驗證 Ninja Google Review 已刪除
1. WordPress 後台 → 外掛程式
2. 搜尋「Ninja」或「Google Review」
3. 確認無相關插件（應該已完全移除）

### Step 2：驗證活動的 Kayarine 版本
1. WordPress 後台 → 外掛程式
2. 搜尋「Kayarine」
3. 確認**只有一個** `kayarine-booking` 活躍（版本應為 v1.4.14）
4. 記下其他 Kayarine 相關插件（如有）

### Step 3：刪除舊版本 Kayarine（如有多個）
1. 找出舊版本：
   - `kayarine-booking-old`
   - `kayarine-booking.backup.xxxx`
   - `kayarine-booking-v1.4.x` (x < 14)
   - 等等其他舊版本

2. 對每個舊版本：
   - 點擊「停用」（如果未停用）
   - 點擊「刪除」
   - 確認刪除

### Step 4：驗證 Ninja Google Review 資料庫殘留
WordPress 後台 → 工具 → 網站健康狀況
- 檢查是否有 Ninja 相關錯誤
- 應該無任何與已刪除插件相關的警告

---

## 方案 B：透過 phpMyAdmin（更徹底）

如果你能存取 phpMyAdmin 或 MySQL 工具：

### Step 1：清理 Ninja Google Review 資料庫

進入 phpMyAdmin → 你的資料庫 → 執行 SQL：

```sql
-- 刪除所有 Ninja Google Review 相關的選項
DELETE FROM wp_options 
WHERE option_name LIKE '%ninja%' 
   OR option_name LIKE '%ngr%'
   OR option_name LIKE '%ninjagooglereview%';

-- 刪除臨時快取
DELETE FROM wp_options 
WHERE option_name LIKE '%transient%ninja%';

-- 驗證已刪除
SELECT COUNT(*) FROM wp_options 
WHERE option_name LIKE '%ninja%';
-- 應該返回 0
```

---

## 方案 C：透過 SSH（當連接恢復時）

一旦 SSH 連接恢復，執行以下清潔命令：

### Step 1：備份
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins
# 備份舊版本
mkdir -p /tmp/kayarine_backup
cp -r kayarine-booking* /tmp/kayarine_backup/ 2>/dev/null || true
echo "Backup created"
EOF
```

### Step 2：列出所有 Kayarine 版本
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins
echo "=== Kayarine 相關插件 ==="
ls -la | grep kayarine

echo -e "\n=== WordPress 活動插件 ==="
cd /opt/bitnami/wordpress
wp plugin list | grep -iE 'kayarine|active'
EOF
```

### Step 3：刪除舊版本和 Ninja 殘留
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins

# 刪除 Ninja Google Review（如有殘留）
rm -rf ninja-google-review* 2>/dev/null || true
echo "✓ Ninja Google Review 已刪除"

# 列出要刪除的舊 Kayarine 版本（保留 kayarine-booking）
echo -e "\n=== 待刪除的舊版本 ==="
for dir in kayarine-*; do
    if [ "$dir" != "kayarine-booking" ]; then
        echo "  $dir"
        rm -rf "$dir"
    fi
done

echo -e "\n✓ 清潔完成"

# 驗證結果
echo -e "\n=== 驗證結果 ==="
ls -la | grep kayarine
EOF
```

### Step 4：清理資料庫（SSH）
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress

# 清理 Ninja 殘留資料
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%ninja%' OR option_name LIKE '%ngr%';"

# 清理 transients
sudo -u www-data wp transient delete --all

# 清除 NitroPack 快取
sudo -u www-data wp option delete nitropack_cache_data

echo "✓ 資料庫清潔完成"
EOF
```

---

## 現在立即執行（不需遠程連接）

### 立即步驟（透過 WordPress 後台）

1. **登入 WordPress 後台**
   - 地址：https://你的網站/wp-admin

2. **驗證插件狀態**
   - 外掛程式 → 已安裝的外掛程式
   - 記錄所有 Kayarine 和 Ninja 插件

3. **刪除舊版本 Kayarine**
   - 對於每個非主版本的 Kayarine，點擊「刪除」
   - 保留：`kayarine-booking` (v1.4.14)

4. **驗證 Ninja 已刪除**
   - 搜尋「Ninja」應該無結果
   - 搜尋「Google」應該無 Review 相關插件

5. **清除 NitroPack 快取**
   - 外掛程式 → NitroPack
   - 找到「快取」選項卡
   - 點擊「清除快取」

6. **測量改進**
   - 開啟無痕視窗
   - 造訪首頁
   - F12 → Network → 記錄 Load 時間
   - 預期改進 100-200ms

---

## 檢查清單

### 立即執行（今天）
- [ ] 登入 WordPress 後台
- [ ] 驗證 Ninja Google Review 已完全移除
- [ ] 列出所有 Kayarine 插件版本
- [ ] 刪除所有舊版本 Kayarine（保留 v1.4.14）
- [ ] 清除 NitroPack 快取
- [ ] 測量頁面載入時間改進

### 當 SSH 可用時
- [ ] 執行資料庫清潔（删除 Ninja 選項）
- [ ] 驗證檔案系統（確認舊文件已移除）
- [ ] 清除所有 transients

---

## 預期結果

**刪除前**：2.5-3 秒
**刪除後**：2.3-2.7 秒（改進 100-200ms）

---

## 如果 SSH 連接仍有問題

### 檢查清單
1. **網絡連接**
   - 檢查本地網絡是否穩定
   - 嘗試 ping 伺服器：`ping 104.199.144.122`

2. **防火牆設置**
   - 檢查是否有本地防火牆阻擋 SSH (port 22)
   - 嘗試從不同網絡連接

3. **GCP 防火牆規則**
   - 聯繫主機商確認 SSH 入站規則已啟用
   - 確認你的 IP 未被黑名單

4. **SSH 密鑰檢查**
   - 確認密鑰文件存在：`/Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key`
   - 檢查密鑰權限：`chmod 600 /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key`

### 替代方案
如果 SSH 完全不可用：
1. **透過 WordPress 後台執行所有清潔**（見上面的「方案 A」）
2. **聯繫主機商**要求他們：
   - 刪除舊版本 Kayarine 插件檔案
   - 清理資料庫中的 Ninja Google Review 資料
   - 確認 SSH 連接性

---

**建議**：先執行「方案 A」（WordPress 後台），這樣最多 10-15 分鐘就能完成，無需遠程連接。

