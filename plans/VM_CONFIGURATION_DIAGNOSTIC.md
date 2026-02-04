# GCP VM 配置診斷計劃

## 目標
檢查 kayarine.club 的 GCP Bitnami WordPress 環境配置，包括：
- WordPress 配置和數據庫連接
- DNS 設置和 SSL 證書
- Web 服務（Apache/Nginx）配置
- 插件和文件系統權限
- Bitnami 版本和服務狀態

## 診斷命令清單

### 1. 系統基本信息
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
hostname
uname -a
"
```

### 2. WordPress 配置檢查
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== WordPress Path ==='
ls -la /opt/bitnami/wordpress/wp-config.php
echo ''
echo '=== WordPress Configuration ==='
grep -E 'WP_HOME|WP_SITEURL|DB_HOST|DB_NAME|DB_USER' /opt/bitnami/wordpress/wp-config.php | head -10
"
```

### 3. Web 服務狀態
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== Apache Status ==='
ps aux | grep -E 'apache|httpd' | grep -v grep
echo ''
echo '=== Nginx Status ==='
ps aux | grep nginx | grep -v grep
echo ''
echo '=== Listening Ports ==='
ss -tlnp | grep -E ':80|:443|:3000|:8080'
"
```

### 4. DNS 和 SSL 配置
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== Server IP Address ==='
hostname -I
echo ''
echo '=== SSL Certificates ==='
ls -la /opt/bitnami/common/certs/ 2>/dev/null || echo 'Bitnami certs not found'
"
```

### 5. VirtualHost 配置
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== Apache VirtualHost ==='
cat /etc/apache2/sites-enabled/kayarine*.conf 2>/dev/null || echo 'No kayarine vhost found'
echo ''
echo '=== Apache Default VirtualHost ==='
cat /etc/apache2/sites-enabled/000-default.conf 2>/dev/null | head -30
"
```

### 6. 插件檢查
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== WordPress Plugins ==='
ls -la /opt/bitnami/wordpress/wp-content/plugins/ | head -20
"
```

### 7. 數據庫檢查
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== Database Configuration ==='
grep -E 'DB_HOST|DB_NAME|DB_USER' /opt/bitnami/wordpress/wp-config.php | grep -v '^//'
echo ''
echo '=== MySQL/MariaDB Status ==='
ps aux | grep -E 'mysql|mariadb' | grep -v grep
"
```

### 8. 文件權限檢查
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== wp-config.php Permissions ==='
ls -l /opt/bitnami/wordpress/wp-config.php
echo ''
echo '=== wp-content Directory Permissions ==='
ls -ld /opt/bitnami/wordpress/wp-content
"
```

### 9. Bitnami 版本和服務
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
echo '=== Bitnami Version ==='
cat /opt/bitnami/common/VERSION 2>/dev/null || echo 'Version file not found'
echo ''
echo '=== Bitnami Services ==='
/opt/bitnami/ctlscript.sh status 2>/dev/null || echo 'ctlscript not available'
"
```

## 配置發現應該檢查

### WordPress 配置
- [ ] WP_HOME 和 WP_SITEURL 是否指向 kayarine.club？
- [ ] 數據庫主機、名稱、用戶是否正確？
- [ ] WordPress 是否已安裝並運行？

### DNS 和 SSL
- [ ] kayarine.club 的 DNS A 記錄是否指向正確的 VM IP？
- [ ] SSL 證書是否有效？
- [ ] 證書過期日期？

### Web 服務
- [ ] 運行的是 Apache 還是 Nginx？
- [ ] 是否監聽 80 和 443 端口？
- [ ] VirtualHost 配置是否正確？

### 部署規劃影響
根據診斷結果，決定：
1. Next.js 應用是否能部署到同一 VM 上的不同端口？
2. 還是需要部署到 GCP Cloud Run 的獨立實例？
3. DNS 配置如何支持子域名（如 app.kayarine.club）？

## 診斷後的決定樹

```
診斷完成
├─ 如果 kayarine.club 已配置 SSL 並運行 WordPress
│  ├─ 選項 A：Next.js 在 Cloud Run (推薦)
│  │   └─ 配置 DNS: app.kayarine.club → Cloud Run
│  │   └─ WordPress 保留在 VM 作為 API 後端
│  │
│  └─ 選項 B：Next.js 在同一 VM 不同端口
│      └─ 配置 Apache/Nginx 反向代理
│      └─ WordPress 在 /，Next.js 在 /app 或 :3000
│
└─ 如果配置存在問題
   └─ 修複並重新配置 DNS/SSL
```
