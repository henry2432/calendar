# Kayarine Booking 部署指南 - GCP 標準流程

## 部署環境

- **服務器**：GCP 虛擬主機
- **實例名稱**：`wordpress-2025-vm`
- **區域**：`asia-east1-b`
- **用戶**：`kayarine.server`
- **WordPress 路徑**：`/opt/bitnami/wordpress`
- **插件路徑**：`/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking`
- **Web 伺服器用戶**：`www-data`
- **SSH 金鑰路徑**：`/Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key`
- **SSH 金鑰公鑰**：`/Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key.pub`
- **GCP 實例 IP**：`104.199.144.122`

---

## SSH 連接方式

### 使用 SSH 直接連接（推薦）

```bash
# 直接 SSH 連接
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 遠端執行命令
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "<command>"

# SCP 上傳文件
scp -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key <local_file> kayarine.server@104.199.144.122:<remote_path>

# SCP 下載文件
scp -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122:<remote_path> <local_file>
```

### 使用 gcloud CLI

使用 gcloud CLI 進行所有遠端操作：

```bash
# SSH 連接
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b

# SCP 上傳文件
gcloud compute scp <local_file> kayarine.server@wordpress-2025-vm:<remote_path> --zone=asia-east1-b

# 遠端執行命令
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="<command>"
```

---

## 部署步驟

### 步驟 1: 備份原始文件

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
cd /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking
cp kayarine-booking.php kayarine-booking.php.backup
cp includes/class-kayarine-member-dashboard.php includes/class-kayarine-member-dashboard.php.backup
"
```

### 步驟 2: 上傳新文件

```bash
# 上傳改進的積分系統文件
gcloud compute scp includes/class-kayarine-improved-checkout.php \
    kayarine.server@wordpress-2025-vm:/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/ \
    --zone=asia-east1-b

# 上傳修改後的會員中心文件
gcloud compute scp includes/class-kayarine-member-dashboard.php \
    kayarine.server@wordpress-2025-vm:/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/ \
    --zone=asia-east1-b
```

### 步驟 3: 修改主插件文件

通過 SSH 連接編輯：

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
sudo nano /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php
"
```

**需要添加的位置**：

在第 28-29 行（`require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-woocommerce-customizer.php';` 後面）添加：

```php
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-improved-checkout.php';
```

在 `kayarine_booking_init()` 函數中（約第 49 行，在 `kayarine_ensure_unified_account_page();` 後面）添加：

```php
new Kayarine_Improved_Checkout();
```

### 步驟 4: 設置文件權限

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
sudo chown www-data:www-data /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
sudo chown www-data:www-data /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-member-dashboard.php
sudo chown www-data:www-data /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php
sudo chmod 644 /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
sudo chmod 644 /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-member-dashboard.php
sudo chmod 644 /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php
"
```

### 步驟 5: 清除緩存並重新啟用插件

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
cd /opt/bitnami/wordpress
sudo -u www-data wp plugin deactivate kayarine-booking
sudo -u www-data wp plugin activate kayarine-booking
sudo -u www-data wp cache flush
"
```

---

## 驗證部署

### 檢查文件是否已上傳

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
ls -lh /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-improved-checkout.php
ls -lh /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-member-dashboard.php
"
```

### 查看部署日誌

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
tail -50 /opt/bitnami/wordpress/wp-content/debug.log
"
```

---

## 回滾步驟

若需要回滾到之前的版本：

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="
cd /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking
sudo cp kayarine-booking.php.backup kayarine-booking.php
sudo cp includes/class-kayarine-member-dashboard.php.backup includes/class-kayarine-member-dashboard.php
sudo rm includes/class-kayarine-improved-checkout.php
sudo -u www-data wp plugin deactivate kayarine-booking
sudo -u www-data wp plugin activate kayarine-booking
"
```

---

## 常用命令參考

### SSH 連接

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b
```

### SCP 上傳

```bash
gcloud compute scp <local_file> kayarine.server@wordpress-2025-vm:<remote_path> --zone=asia-east1-b
```

### 遠端執行命令

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="<command>"
```

### 查看日誌

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="tail -100 /opt/bitnami/wordpress/wp-content/debug.log"
```

### 重啟 Web 伺服器

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="sudo service apache2 restart"
```

### 數據庫連接

```bash
gcloud compute ssh kayarine.server@wordpress-2025-vm --zone=asia-east1-b --command="sudo mysql -u root -p"
```

---

## 故障排查

### 無法連接到 GCP 實例

檢查：
1. gcloud 配置是否正確
2. 區域設置是否正確：`asia-east1-b`
3. 實例名稱是否正確：`wordpress-2025-vm`
4. 防火牆規則是否允許 SSH

### 文件上傳失敗

檢查：
1. 本地文件是否存在
2. 遠端目錄是否存在
3. 權限是否足夠
4. gcloud 認證是否有效

### 插件無法激活

檢查：
1. PHP 語法是否正確
2. 文件權限是否設置正確
3. WordPress 調試日誌是否有錯誤

---

## 文件清單

需要部署的文件：

| 文件 | 路徑 | 操作 |
|------|------|------|
| `kayarine-booking.php` | `/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/` | 修改 |
| `class-kayarine-member-dashboard.php` | `/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/` | 修改 + 上傳 |
| `class-kayarine-improved-checkout.php` | `/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/` | 上傳 |

---

## 部署時間表

| 步驟 | 估計時間 |
|------|--------|
| 備份 | 1 分鐘 |
| 上傳文件 | 2-3 分鐘 |
| 修改配置 | 3-5 分鐘 |
| 設置權限 | 1 分鐘 |
| 驗證 | 2 分鐘 |
| **總計** | **10-15 分鐘** |

