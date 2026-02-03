# Kayarine 簡化部署指南
## 快速部署 (無備份、無虛擬機上傳)

### 📋 概述
本指南提供最快的部署方式，跳過以下步驟：
- ✗ 建立備份檔案
- ✗ 上傳到虛擬機
- ✓ 直接同步檔案到伺服器
- ✓ 設定權限
- ✓ 驗證部署

---

## 🚀 快速開始

### 前置條件
- SSH 存取伺服器權限
- WordPress 已安裝
- `kayarine-booking` 外掛目錄已存在

### 部署指令 (單行)
```bash
chmod +x kayarine-booking/QUICK_DEPLOY_SIMPLIFIED.sh
./kayarine-booking/QUICK_DEPLOY_SIMPLIFIED.sh <user@host> /var/www/html
```

### 範例
```bash
./kayarine-booking/QUICK_DEPLOY_SIMPLIFIED.sh admin@example.com /var/www/html
```

---

## 📦 部署流程

### 第 1 步：上傳檔案 (2-3 秒)
- 上傳所有外掛檔案到伺服器
- 包括所有 PHP、CSS、JavaScript 檔案
- 使用 SCP 進行安全傳輸

### 第 2 步：更新設定 (1-2 秒)
- 清除 WordPress 快取
- 驗證外掛已啟用 (如果安裝了 wp-cli)

### 第 3 步：設定權限 (1-2 秒)
- PHP 檔案：644
- CSS/JS 檔案：644
- 目錄：755

### 第 4 步：驗證 (<1 秒)
- 檢查必要檔案是否存在
- 驗證檔案完整性

**總計：約 5-10 秒部署時間**

---

## 📝 部署的檔案

### 新增檔案
```
includes/class-kayarine-auth-integration.php
```

### 修改檔案
```
kayarine-booking.php (主外掛)
includes/class-kayarine-member-dashboard-v2.php (固定 upcoming bookings)
```

### 現有檔案 (會覆蓋)
```
assets/css/style.css
includes/kayarine-config.php
```

---

## ✅ 部署後驗證

### 1. 檢查外掛啟用
```
WordPress 後台 → 外掛 → 搜尋 "kayarine-booking"
```
應看到 "Kayarine Booking" 顯示為 "已啟用"

### 2. 測試登入/註冊
```
任何頁面/文章中添加短代碼：[kayarine_login_register]
```
應看到紫色/橙色的登入/註冊標籤頁界面

### 3. 測試會員中心
```
已登入用戶訪問：[kayarine_member_dashboard_v2]
```
應看到：
- 會員等級和積分
- 升級進度條
- 即將到來的預約清單

### 4. 檢查最近訂單
```
如果有最近的訂單，應在儀表板中看到
```
- 訂單日期
- 預約日期
- 改期/取消按鈕 (如果還未超過截止時間)

---

## 🔧 故障排除

### 問題：檔案未上傳
**解決方案：**
```bash
# 手動驗證連接
ssh user@host ls -la /var/www/html/wp-content/plugins/kayarine-booking/

# 手動上傳
scp -r kayarine-booking/* user@host:/var/www/html/wp-content/plugins/kayarine-booking/
```

### 問題：短代碼無法顯示
**解決方案：**
1. 確認外掛已啟用：`wp plugin activate kayarine-booking`
2. 確認 `class-kayarine-auth-integration.php` 已上傳
3. 清除快取：`wp cache flush`
4. 重新加載頁面

### 問題：預約未顯示
**解決方案：**
1. 確認訂單狀態為 "pending"、"processing" 或 "completed"
2. 確認訂單項目有 `kayarine_booking_date` meta 資料
3. 檢查日期格式 (YYYY-MM-DD)

### 問題：登入按鈕無反應
**解決方案：**
1. 開啟瀏覽器開發者工具 (F12 → Console)
2. 檢查是否有 JavaScript 錯誤
3. 確認 jQuery 已加載
4. 檢查 `style.css` 權限是否為 644

---

## 🛠️ 手動部署 (如果腳本失敗)

如果自動部署失敗，可手動部署：

### Step 1: SSH 連接
```bash
ssh user@host
cd /var/www/html/wp-content/plugins/kayarine-booking
```

### Step 2: 上傳檔案 (本地機器)
```bash
scp -r kayarine-booking/* user@host:/var/www/html/wp-content/plugins/kayarine-booking/
```

### Step 3: 設定權限 (遠端伺服器)
```bash
find /var/www/html/wp-content/plugins/kayarine-booking -type f -name "*.php" -exec chmod 644 {} \;
find /var/www/html/wp-content/plugins/kayarine-booking -type f -name "*.css" -exec chmod 644 {} \;
find /var/www/html/wp-content/plugins/kayarine-booking -type d -exec chmod 755 {} \;
```

### Step 4: 驗證 (如果安裝了 wp-cli)
```bash
wp plugin activate kayarine-booking
wp cache flush
```

---

## 📊 部署性能

| 步驟 | 時間 |
|------|------|
| 上傳檔案 | 2-3 秒 |
| 更新設定 | 1-2 秒 |
| 設定權限 | 1-2 秒 |
| 驗證 | <1 秒 |
| **總計** | **~5-10 秒** |

相比完整部署 (包含備份和虛擬機上傳)：
- 完整部署：5-10 分鐘
- 簡化部署：5-10 秒
- **快 60-120 倍** ⚡

---

## 🔄 反復部署

如果需要重複部署 (例如修複 bug)：

```bash
# 運行相同指令
./kayarine-booking/QUICK_DEPLOY_SIMPLIFIED.sh admin@example.com /var/www/html
```

無需手動清除舊檔案，腳本會自動覆蓋。

---

## 📞 支持與反饋

如遇問題，請檢查：
1. **SSH 連接**：`ssh user@host "echo OK"`
2. **WordPress 路徑**：`ssh user@host "wp --info"`
3. **外掛目錄**：`ssh user@host "ls -la /path/to/wp-content/plugins/kayarine-booking"`
4. **錯誤日誌**：`ssh user@host "tail -50 /var/www/html/wp-content/debug.log"`

---

## 版本資訊

- **建立日期**：2026-01-27
- **Kayarine 版本**：1.4.1
- **WordPress 要求**：5.0+
- **PHP 要求**：7.4+
