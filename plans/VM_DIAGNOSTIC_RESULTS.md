# GCP Bitnami WordPress VM - 診斷結果

**診斷日期**：2025-02-03  
**目標域名**：kayarine.club  
**VM 名稱**：wordpress-2025-vm  
**內部 IP**：10.140.0.9  

---

## ✅ 診斷摘要

| 項目 | 狀態 | 詳情 |
|------|------|------|
| WordPress 安裝 | ✅ | /opt/bitnami/wordpress 正常運行 |
| 域名配置 | ✅ | WP_HOME/WP_SITEURL = https://kayarine.club |
| 數據庫 | ✅ | MariaDB 本地運行，連接正常 |
| Web 服務 | ✅ | Apache 監聽 80/443 |
| SSL 證書 | ✅ | 已配置 (kayarine.club.crt) |
| 文件權限 | ✅ | wp-config.php: www-data:www-data |
| Cloudflare DNS | ✅ | 已配置，使用 Cloudflare 代理 |

---

## 詳細配置

### 1. WordPress 核心
```
路徑: /opt/bitnami/wordpress
版本: 已安裝並運行
wp-config.php 所有者: www-data:www-data (正確)

WP_HOME:       https://kayarine.club
WP_SITEURL:    https://kayarine.club
DB_HOST:       127.0.0.1:3306
DB_NAME:       bitnami_wordpress
DB_USER:       bn_wordpress
```

### 2. Web 服務 (Apache)
```
進程: /opt/bitnami/apache/bin/httpd
監聽端口: 80, 443
配置路徑: /opt/bitnami/apache/conf/
VHost 配置: /opt/bitnami/apache/conf/bitnami/bitnami.conf
            /opt/bitnami/apache/conf/bitnami/bitnami-ssl.conf
ServerName: kayarine.club
```

### 3. SSL 證書
```
文件: /opt/bitnami/apache/conf/kayarine.club.crt
狀態: 已配置並運行
證書驗證: 需要從瀏覽器檢查 (HTTPS 連接正常)
```

### 4. DNS 配置 (Cloudflare)
```
域名: kayarine.club
DNS A 記錄:
  - 104.21.47.5 (Cloudflare)
  - 172.67.169.169 (Cloudflare)

實際流量路由:
  用戶 → Cloudflare (104.21.47.5) → VM (10.140.0.9) → Apache → WordPress
```

### 5. 數據庫 (MariaDB)
```
進程: /opt/bitnami/mariadb/sbin/mysqld
監聽: 127.0.0.1:3306 (本地)
內存使用: 27.3% (穩定)
狀態: 正常運行
```

### 6. WordPress 插件目錄
```
路徑: /opt/bitnami/wordpress/wp-content/plugins/
狀態: 已清理
包含: kayarine-booking 和其他激活插件
```

---

## 🏗️ Next.js 應用部署架構決策

### 現狀分析
- ✅ kayarine.club 已運行 WordPress + Apache
- ✅ Cloudflare 配置完善，支持 DNS 和 SSL
- ✅ VM 資源充足（內存 27.3% 使用）
- ⚠️ Apache 監聽 80/443，Next.js 無法在同一端口運行

### 推薦方案：GCP Cloud Run 獨立部署

```
架構圖：
┌─────────────────────────────────────┐
│         用戶訪問 (瀏覽器)              │
└──────────────────┬──────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
   kayarine.club         kayarine.club/api
        │                     │
        ▼                     ▼
   ┌─────────┐          ┌──────────┐
   │ Figma → │          │WordPress │
   │Next.js  │          │REST API  │
   │(Cloud   │          │(Bitnami  │
   │Run)     │          │VM)       │
   └─────────┘          └──────────┘
   104.199.xxx          10.140.0.9
   (GCP Cloud Run)      (GCP VM)
```

### DNS 配置更新計劃
```
現在 (WordPress only):
kayarine.club A → Cloudflare → 10.140.0.9 → Apache → WordPress

遷移後 (WordPress + Next.js):
1. kayarine.club A → Cloudflare → GCP Cloud Run IP (Next.js)
2. api.kayarine.club A → Cloudflare → 10.140.0.9 (WordPress REST API)
3. 或保持現狀，使用子路徑：/api/ 由 Cloud Run 反向代理到 WordPress
```

### 具體步驟

**步驟 1：Next.js 在 GCP Cloud Run 部署**
- 域名：kayarine.club (替換當前 WordPress 前端)
- 功能：11 個靜態頁面 + Header/Footer
- API 調用：調用 WordPress REST API (http://104.199.144.122/wp-json/)

**步驟 2：WordPress 保留為 API 後端**
- 保留在 VM 上運行
- 配置 REST API 端點供 Next.js 前端調用
- 管理後台仍在：https://kayarine.club/wp-admin (需新配置)

**步驟 3：DNS Cloudflare 配置**
- 創建新 DNS 記錄：
  ```
  kayarine.club → GCP Cloud Run IP
  admin.kayarine.club → 10.140.0.9 (WordPress 管理後台)
  api.kayarine.club → 10.140.0.9 (REST API)
  ```

---

## 📋 Phase 1 前提條件

在開始編碼前，需確認：

- [ ] 是否使用上述架構（Next.js on Cloud Run）？
- [ ] WordPress 管理後台最終如何訪問？
- [ ] 是否需要在 VM 上配置 WordPress REST API？
- [ ] GitHub 倉庫已創建？
- [ ] GCP 項目中已啟用 Cloud Run API？

---

## 準備開始 Phase 1

一旦確認上述架構，我將立即開始：

**Phase 1.1** → GitHub 倉庫初始化  
**Phase 1.2** → Next.js 14 項目結構  
**Phase 1.3** → 環境配置  
**Phase 1.4** → Header/Footer 生成  
**Phase 1.5** → 本地測試  

預計完成時間：2-4 天
