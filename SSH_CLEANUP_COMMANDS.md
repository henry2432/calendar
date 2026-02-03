# SSH 清潔命令 - 複製即用

SSH 密鑰文件確認：
- **私鑰**：`gcp-ssh-key`（411 bytes）✅ 用這個
- **公鑰**：`gcp-ssh-key.pub`（97 bytes）❌ 不用

---

## 命令 1：測試 SSH 連接

複製並在你的終端執行：
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key -v kayarine.server@104.199.144.122 "echo 'Connection OK'"
```

---

## 命令 2：列出所有插件（檢查清單）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins
echo "=== Kayarine 相關插件 ==="
ls -1d kayarine* 2>/dev/null || echo "無"
echo -e "\n=== Ninja 插件 ==="
ls -1d ninja* 2>/dev/null || echo "✓ 無（已刪除）"
EOF
```

---

## 命令 3：刪除所有舊版本 Kayarine（保留 kayarine-booking v1.4.14）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins

echo "刪除舊版本 Kayarine..."
for dir in kayarine-*; do
    if [ "$dir" != "kayarine-booking" ]; then
        rm -rf "$dir"
        echo "  ✓ 刪除: $dir"
    fi
done

echo -e "\n驗證結果:"
ls -1d kayarine*
EOF
```

**預期輸出**：只顯示 `kayarine-booking`

---

## 命令 4：刪除 Ninja 殘留檔案

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress/wp-content/plugins

echo "清理 Ninja Google Review 檔案..."
rm -rf ninja-google-review* 2>/dev/null || true
echo "✓ 完成"

# 驗證
ls -1d ninja* 2>/dev/null && echo "⚠️ 仍有殘留" || echo "✓ 已完全移除"
EOF
```

**預期輸出**：`✓ 已完全移除`

---

## 命令 5：清理資料庫 - 刪除 Ninja 選項

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress

echo "清理資料庫..."

# 刪除 Ninja 相關選項
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%ninja%' OR option_name LIKE '%ngr%';" 2>/dev/null

echo "✓ 資料庫清潔完成"

# 驗證
echo -e "\n驗證："
NINJA_COUNT=$(sudo -u www-data wp db query "SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '%ninja%';" 2>/dev/null | tail -1)
echo "Ninja 相關項: $NINJA_COUNT (應為 0)"
EOF
```

**預期輸出**：
```
清理資料庫...
✓ 資料庫清潔完成

驗證：
Ninja 相關項: 0 (應為 0)
```

---

## 命令 6：清除 NitroPack 快取

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'EOF'
cd /opt/bitnami/wordpress

echo "清除快取..."
sudo -u www-data wp transient delete --all 2>/dev/null
echo "✓ 快取已清除"
EOF
```

---

## 完整清潔流程（一次執行所有）

複製整個代碼塊到終端：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'CLEANUP'
#!/bin/bash

echo "========================================="
echo "Kayarine 插件完整清潔"
echo "========================================="

# 步驟 1：刪除舊 Kayarine
echo -e "\n[1/5] 刪除舊版本 Kayarine..."
cd /opt/bitnami/wordpress/wp-content/plugins
for dir in kayarine-*; do
    if [ "$dir" != "kayarine-booking" ]; then
        rm -rf "$dir"
        echo "  ✓ $dir"
    fi
done

# 步驟 2：刪除 Ninja
echo -e "\n[2/5] 刪除 Ninja Google Review..."
rm -rf ninja-google-review* 2>/dev/null || true
echo "✓ Ninja 已移除"

# 步驟 3：清理資料庫
echo -e "\n[3/5] 清理資料庫..."
cd /opt/bitnami/wordpress
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%ninja%' OR option_name LIKE '%ngr%';" 2>/dev/null
echo "✓ 資料庫已清潔"

# 步驟 4：清除快取
echo -e "\n[4/5] 清除快取..."
sudo -u www-data wp transient delete --all 2>/dev/null
echo "✓ 快取已清除"

# 步驟 5：驗證
echo -e "\n[5/5] 驗證結果..."
cd /opt/bitnami/wordpress/wp-content/plugins
KAYARINE_COUNT=$(ls -1d kayarine* 2>/dev/null | wc -l)

cd /opt/bitnami/wordpress
NINJA_COUNT=$(sudo -u www-data wp db query "SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '%ninja%';" 2>/dev/null | tail -1)

echo -e "\n========================================="
echo "最終驗證"
echo "========================================="
echo "✓ Kayarine 版本: $KAYARINE_COUNT (預期: 1)"
echo "✓ Ninja 項目: $NINJA_COUNT (預期: 0)"

if [ "$KAYARINE_COUNT" == "1" ] && [ "$NINJA_COUNT" == "0" ]; then
    echo -e "\n✓ 清潔完成！"
else
    echo -e "\n⚠️ 請檢查上述結果"
fi
echo "========================================="

CLEANUP
```

---

## 執行步驟

1. **複製上面的完整清潔命令**
2. **在你的終端貼上**
3. **按 Enter**
4. **等待執行完成**
5. **查看最終驗證結果**

---

## 如果仍然超時

嘗試分開執行，每個命令單獨執行：

### 只列出插件
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "ls -1d /opt/bitnami/wordpress/wp-content/plugins/kayarine*"
```

### 只刪除舊版本
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "cd /opt/bitnami/wordpress/wp-content/plugins && rm -rf kayarine-booking.backup* kayarine-booking-old* kayarine-booking-v*"
```

### 只刪除 Ninja
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "rm -rf /opt/bitnami/wordpress/wp-content/plugins/ninja-google-review*"
```

---

## 密鑰驗證

確認使用正確的私鑰：
```bash
file /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key
# 應該顯示：OpenSSH private key format
```

檢查密鑰權限：
```bash
ls -l /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key
# 應該顯示：-rw------- (600)
```

---

## 完成標誌 ✅

執行完後檢查：
- [ ] Kayarine 版本數: 1 ✓
- [ ] Ninja 相關項: 0 ✓
- [ ] WordPress 後台顯示 kayarine-booking v1.4.14 active ✓

