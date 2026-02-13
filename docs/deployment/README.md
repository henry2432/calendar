# 🚀 Kayarine 部署文檔

本目錄包含所有部署相關的文檔和指南。

---

## 📚 文檔索引

### 核心文檔
- [`vm-deployment.md`](./vm-deployment.md) - VM SSH 部署指南（推薦）
- [`cloud-run-deployment.md`](./cloud-run-deployment.md) - Google Cloud Run 部署
- [`troubleshooting.md`](./troubleshooting.md) - 部署故障排除
- [`quick-start.md`](./quick-start.md) - 快速部署指南

### 專題指南
- [`gmail-deployment.md`](./gmail-deployment.md) - Gmail SMTP 部署
- [`git-server-setup.md`](./git-server-setup.md) - 服務器 Git 配置

---

## 🎯 部署決策樹

```
需要部署什麼？
│
├─ WordPress 後端 (calendar)
│  └─ 使用 VM SSH 部署
│     └─ 參考：DEPLOYMENT_GUIDE_GCP_STANDARD.md
│
├─ Next.js 前端 (kayarine-nextjs-frontend)
│  ├─ 選項 A: VM SSH 部署（推薦）
│  │  ├─ ✅ 與後端同伺服器
│  │  ├─ ✅ 簡化配置
│  │  └─ 📖 參考：vm-deployment.md
│  │
│  └─ 選項 B: Cloud Run 部署（可選）
│     ├─ ✅ 自動擴展
│     ├─ ⚠️ 冷啟動延遲
│     └─ 📖 參考：cloud-run-deployment.md
│
└─ 兩者都需要
   └─ 先部署後端，再部署前端
```

---

## 🌐 服務器信息

### GCP VM 實例
- **IP 地址**: `104.199.144.122`
- **SSH 用戶**: `kayarine.server`
- **SSH 連接**: `ssh kayarine.server@104.199.144.122`

### 目錄結構
```
/home/kayarine.server/
├── calendar/                      # WordPress 後端
│   ├── wp-content/
│   │   └── plugins/
│   │       └── kayarine-booking/
│   └── backend/                   # Python Flask
│
└── kayarine-nextjs-frontend/      # Next.js 前端
    ├── .next/
    ├── components/
    └── app/
```

### 服務
- **WordPress**: Apache (port 80)
- **Next.js**: PM2 (port 3000)
- **Nginx**: 反向代理
- **Python Flask**: Systemd service

---

## 📋 部署檢查清單

### 前置準備
- [ ] SSH 密鑰已配置
- [ ] Git 倉庫已拉取最新代碼
- [ ] 環境變數已設置
- [ ] 依賴已安裝

### WordPress 後端部署
- [ ] 上傳 PHP 文件到 `wp-content/plugins/kayarine-booking/`
- [ ] 設置文件權限 (644 for files, 755 for directories)
- [ ] 設置所有者 `www-data:www-data`
- [ ] 重啟 Apache: `sudo systemctl restart apache2`
- [ ] 測試 API 端點
- [ ] 記錄到 DEVELOPMENT_LOG.md

### Next.js 前端部署
- [ ] SSH 連接到 VM
- [ ] `cd ~/kayarine-nextjs-frontend`
- [ ] `git pull origin develop`
- [ ] `npm install --legacy-peer-deps`
- [ ] 配置 `.env.local`
- [ ] `npm run build`
- [ ] `pm2 restart kayarine-frontend` 或 `pm2 start ecosystem.config.js`
- [ ] 檢查 PM2 狀態: `pm2 status`
- [ ] 測試應用: 訪問 `http://104.199.144.122:3000`
- [ ] 記錄到 DEVELOPMENT_LOG.md

### Python 後端部署
- [ ] 上傳 Python 文件到 `backend/`
- [ ] 更新 `requirements.txt`
- [ ] `pip install -r requirements.txt`
- [ ] 重啟 Flask 服務: `sudo systemctl restart kayarine-flask`
- [ ] 檢查服務狀態: `sudo systemctl status kayarine-flask`

---

## 🔧 環境變數

### Next.js Frontend (.env.local)
```env
# WordPress API
NEXT_PUBLIC_WORDPRESS_API=http://104.199.144.122/wp-json

# JWT Authentication
JWT_SECRET=your-jwt-secret-min-32-chars

# Google OAuth
NEXT_PUBLIC_GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com

# Apple Sign In
NEXT_PUBLIC_APPLE_CLIENT_ID=com.kayarine.signin

# Gmail SMTP
GMAIL_USER=kayarine.server@gmail.com
GMAIL_APP_PASSWORD=iubh tcwy misx kdis
ADMIN_EMAIL=contact@kayarine.club

# Google Maps & Reviews
GOOGLE_MAPS_API_KEY=your-api-key
GOOGLE_PLACE_ID=ChIJeVgTGbcABDQRcwn0yLXGmhE
```

### WordPress (wp-config.php)
```php
// JWT Authentication
define('JWT_AUTH_SECRET_KEY', 'your-jwt-secret-min-32-chars');
define('JWT_AUTH_CORS_ENABLE', true);

// Database
define('DB_NAME', 'wordpress');
define('DB_USER', 'wp_user');
define('DB_PASSWORD', 'your-db-password');
define('DB_HOST', 'localhost');
```

### Python Flask (.env)
```env
FLASK_APP=app.py
FLASK_ENV=production
DATABASE_URL=mysql://user:password@localhost/kayarine
GOOGLE_SHEETS_CREDENTIALS=/path/to/credentials.json
```

---

## 📊 部署方式對比

### VM SSH 部署 (推薦)
**優勢**:
- ✅ 前後端統一運行
- ✅ 配置簡單
- ✅ 資源共享
- ✅ 無冷啟動延遲
- ✅ 熟悉的部署流程

**劣勢**:
- ⚠️ 需要手動擴展
- ⚠️ 需要維護伺服器

**適用場景**:
- 初期開發和測試
- 中小型流量應用
- 需要快速迭代

### Cloud Run 部署 (可選)
**優勢**:
- ✅ 自動擴展
- ✅ 按使用付費
- ✅ 容器化部署
- ✅ 內建負載均衡

**劣勢**:
- ⚠️ 冷啟動延遲 (0-10秒)
- ⚠️ 配置較複雜
- ⚠️ 調試較困難

**適用場景**:
- 大流量應用
- 需要高可用性
- 無需即時響應

---

## 🐛 常見問題

### Q: 部署後應用無法訪問
**解決方案**:
1. 檢查服務狀態: `pm2 status` 或 `systemctl status`
2. 檢查端口是否開放: `sudo netstat -tulpn | grep 3000`
3. 檢查防火牆規則
4. 查看應用日誌: `pm2 logs` 或 `journalctl -u service-name`

### Q: 環境變數未生效
**解決方案**:
1. 確認 `.env.local` 文件存在
2. 檢查變數名稱是否正確
3. 重新構建: `npm run build`
4. 重啟服務: `pm2 restart kayarine-frontend`

### Q: 構建失敗
**解決方案**:
1. 刪除 `.next` 目錄: `rm -rf .next`
2. 清除 npm 緩存: `npm cache clean --force`
3. 重新安裝依賴: `rm -rf node_modules && npm install --legacy-peer-deps`
4. 檢查 Node.js 版本: `node -v` (需要 18.x+)

### Q: PM2 無法啟動
**解決方案**:
1. 檢查 `ecosystem.config.js` 配置
2. 查看 PM2 日誌: `pm2 logs`
3. 重啟 PM2 守護進程: `pm2 kill && pm2 start ecosystem.config.js`

---

## 📖 詳細指南

### 部署步驟文檔
- [`vm-deployment.md`](./vm-deployment.md) - 完整 VM 部署指南
- [`cloud-run-deployment.md`](./cloud-run-deployment.md) - Cloud Run 部署指南
- [`quick-start.md`](./quick-start.md) - 5分鐘快速部署

### 專題指南
- [`gmail-deployment.md`](./gmail-deployment.md) - Gmail SMTP 配置和部署
- [`troubleshooting.md`](./troubleshooting.md) - 故障排除完整指南
- [`git-server-setup.md`](./git-server-setup.md) - 服務器 Git 配置

### 核心部署指南
- [DEPLOYMENT_GUIDE_GCP_STANDARD.md](../../DEPLOYMENT_GUIDE_GCP_STANDARD.md) 🔒 - GCP 標準部署指南（唯讀）

---

## 🔐 安全注意事項

### SSH 安全
- 使用 SSH 密鑰而非密碼
- 定期更新 SSH 密鑰
- 限制 SSH 訪問 IP

### 環境變數安全
- 絕不提交 `.env` 文件到 Git
- 使用強密碼和密鑰
- 定期輪換密鑰
- 考慮使用 GCP Secret Manager

### 應用安全
- 啟用 HTTPS (生產環境)
- 配置 CORS 白名單
- 限制 API 速率
- 定期更新依賴

---

## 📞 支持

如遇部署問題：
1. 查看 [`troubleshooting.md`](./troubleshooting.md)
2. 檢查應用日誌
3. 檢查服務器日誌
4. 查看 WordPress 錯誤日誌

---

## 📝 部署記錄

所有部署必須記錄到項目根目錄的 [`DEVELOPMENT_LOG.md`](../../DEVELOPMENT_LOG.md)，包括：
- 部署時間和版本號
- 部署的文件和功能
- 測試結果
- 遇到的問題和解決方案

---

**最後更新**: 2026-02-09  
**維護者**: Development Team
