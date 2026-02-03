# Kayarine 菜單修復診斷指南 (v1.4.8)

## 問題描述
菜單修復已部署，但用戶報告仍然看到 WooCommerce 菜單項目。

## 最可能的原因

### 1️⃣ WordPress 緩存未清除（最常見原因）
- 頁面被 W3 Total Cache 或其他緩存插件緩存
- 即使代碼已更新，仍然提供舊版頁面

### 2️⃣ 部署文件未正確更新
- tar 解壓可能沒有覆蓋所有文件
- 權限問題導致文件無法覆蓋

### 3️⃣ 代碼邏輯問題
- 過濾器回調沒有被調用
- 多個 shortcode 衝突

---

## 診斷步驟

### 步驟 1：清除 WordPress 緩存（CRITICAL）

#### 方法 A：通過 WordPress 後台
1. 登入 `https://kayarine.club/wp-admin`
2. 尋找缓存插件菜單（如 "W3 Total Cache"、"WP Super Cache"）
3. 點擊「清除所有緩存」或「Purge All」
4. 刷新前端頁面 (Ctrl+Shift+R 或 Cmd+Shift+R)

#### 方法 B：通過伺服器 SSH
```bash
# 清除 WordPress 所有緩存
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  # 清除 WP-Super-Cache
  sudo rm -rf /opt/bitnami/wordpress/wp-content/cache/*
  
  # 清除 W3 Total Cache
  sudo rm -rf /opt/bitnami/wordpress/wp-content/w3tc-cache/*
  
  # 清除 Transients
  wp transient delete-all --allow-root --path=/opt/bitnami/wordpress
'
```

### 步驟 2：驗證服務器上的部署版本

檢查當前部署的版本號：

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  grep "Version:" /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php
'
```

**預期輸出**：`Version: 1.4.8`

如果看到其他版本號，說明部署沒有成功。

### 步驟 3：驗證菜單過濾器代碼

檢查菜單過濾邏輯是否在服務器上：

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  grep -n "customize_account_menu" /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-woocommerce-customizer.php | head -5
'
```

**預期輸出**：應該看到 `customize_account_menu` 方法（通常在 419 行附近）

### 步驟 4：檢查錯誤日誌

查看 WordPress debug 日誌中是否有菜單過濾相關的日誌：

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  grep "Kayarine 1.4.8" /opt/bitnami/wordpress/wp-content/debug.log | tail -50
'
```

**預期輸出**：應該看到類似的日誌：
```
[Kayarine 1.4.8] Menu Filter - START ================================
[Kayarine 1.4.8] Menu Filter - Outgoing items: ["dashboard","kayarine-membership","customer-logout"]
[Kayarine 1.4.8] Menu Filter - Hidden items: ["orders","downloads","edit-address","edit-account"]
```

如果沒有這些日誌，說明過濾器沒有被調用。

### 步驟 5：檢查頁面使用的 Shortcode

訪問會員中心頁面並檢查頁面設置：

1. 登入 WordPress 後台
2. 進入 Pages → 尋找會員中心頁面（通常是 `/account/`）
3. 檢查頁面內容中使用的 shortcode：
   - ✅ **應該使用**：`[kayarine_account]`
   - ❌ **不應該使用**：`[kayarine_login_register]` 或任何 WooCommerce shortcode

4. 如果使用了錯誤的 shortcode，編輯頁面並更改為正確的

---

## 完整修復流程

如果上述診斷顯示版本正確但菜單仍不工作，請執行完整修復：

### 步驟 1：強制清除所有緩存

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  # 清除所有可能的緩存位置
  sudo rm -rf /opt/bitnami/wordpress/wp-content/cache/*
  sudo rm -rf /opt/bitnami/wordpress/wp-content/w3tc-cache/*
  sudo rm -rf /opt/bitnami/wordpress/wp-content/plugins/*/cache/*
  sudo rm -rf /tmp/*cache*
  
  # 重啟 Apache
  sudo /opt/bitnami/ctlscript.sh restart apache
'
```

### 步驟 2：重新部署最新代碼

```bash
cd kayarine-booking
./deploy.sh production --clear-cache
```

### 步驟 3：驗證頁面設置

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  # 檢查 /account/ 頁面的內容
  wp post list --path=/opt/bitnami/wordpress --post_type=page --name=account --format=json --allow-root | jq ".[0] | {ID, post_title, post_name, post_content}"
'
```

### 步驟 4：重新測試

1. 清除瀏覽器緩存（Ctrl+Shift+Delete 或 Cmd+Shift+Delete）
2. 打開無痕模式並訪問 `https://kayarine.club/account/`
3. 使用測試帳戶 (`test`/`testtest`) 登入
4. 檢查菜單

---

## 如果問題仍然存在

### 檢查是否有 WooCommerce 菜單渲染代碼存在

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  # 搜索直接調用 woocommerce_account_navigation() 的代碼
  grep -r "woocommerce_account_navigation" /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/ --include="*.php" 2>/dev/null
'
```

**預期結果**：沒有搜索結果（或只在註釋中出現）

如果有搜索結果，說明仍然有代碼直接調用 WooCommerce 導航，需要移除。

### 檢查多個 Shortcode 衝突

```bash
gcloud compute ssh wordpress-2025-vm --zone=asia-east1-b --command='
  # 列出所有已註冊的 shortcodes
  wp hook list --path=/opt/bitnami/wordpress --hook=shortcode_atts_kayarine_account --allow-root
  wp hook list --path=/opt/bitnami/wordpress --hook=shortcode_atts_kayarine_login_register --allow-root
'
```

---

## 預期的伺服器日誌輸出

如果菜單修復正常工作，應該在 debug.log 中看到：

```
[Kayarine 1.4.8] Menu Filter - START ================================
[Kayarine 1.4.8] Menu Filter - is_user_logged_in(): true
[Kayarine 1.4.8] Menu Filter - Incoming items: ["dashboard","orders","downloads","edit-address","edit-account","customer-logout"]
[Kayarine 1.4.8] Menu Filter - Total incoming items: 6
[Kayarine 1.4.8] Menu Filter - Added item: dashboard
[Kayarine 1.4.8] Menu Filter - Added item: customer-logout
[Kayarine 1.4.8] Menu Filter - Added kayarine-membership (not found in items)
[Kayarine 1.4.8] Menu Filter - Outgoing items: ["dashboard","kayarine-membership","customer-logout"]
[Kayarine 1.4.8] Menu Filter - Hidden items: ["orders","downloads","edit-address","edit-account"]
[Kayarine 1.4.8] Menu Filter - END ================================
[Kayarine 1.4.8] Final menu items to render: ["dashboard","kayarine-membership","customer-logout"]
```

---

## 快速檢查清單

- [ ] WordPress 緩存已清除
- [ ] 服務器版本確認為 1.4.8
- [ ] 菜單過濾器代碼存在於服務器
- [ ] 頁面使用 `[kayarine_account]` shortcode
- [ ] 瀏覽器緩存已清除
- [ ] 無痕模式下重新測試
- [ ] Debug.log 中看到菜單過濾器日誌

---

## 聯絡支持

如果以上步驟都不能解決問題，請收集以下信息：

1. 服務器版本號（from `grep Version kayarine-booking.php`）
2. WordPress debug.log 最後 100 行
3. 頁面使用的 shortcode 
4. 登入後看到的實際菜單項目列表
5. 頁面源代碼（F12 → Elements）

---

## 相關文檔

- 部署指南：[`kayarine-booking/DEPLOYMENT_GCLOUD_GUIDE.md`](./DEPLOYMENT_GCLOUD_GUIDE.md)
- 菜單修復實現：見 [`class-kayarine-woocommerce-customizer.php`](./includes/class-kayarine-woocommerce-customizer.php)
