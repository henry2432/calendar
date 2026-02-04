# Next.js 應用 - GCP Bitnami VM 部署計劃

## 🏗️ 系統架構（VM 部署）

```
┌─────────────────────────────────────────────────────────┐
│                GCP Bitnami WordPress VM                  │
│              10.140.0.9 (内部 IP)                        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │        Apache (Port 80/443)                         │ │
│  │  ┌─────────────────────────────────────────────┐  │ │
│  │  │ VirtualHost kayarine.club                   │  │ │
│  │  │                                             │  │ │
│  │  │ ┌──────────────────┐   ┌─────────────────┐ │  │ │
│  │  │ │ Request to /     │──→│ Node.js (3000)  │ │  │ │
│  │  │ │ (Next.js Routes) │   │ Next.js App     │ │  │ │
│  │  │ │                  │   │ (Proxy)         │ │  │ │
│  │  │ └──────────────────┘   └─────────────────┘ │  │ │
│  │  │                                             │  │ │
│  │  │ ┌──────────────────┐   ┌─────────────────┐ │  │ │
│  │  │ │ Request to       │──→│ /opt/bitnami/   │ │  │ │
│  │  │ │ /wp-admin        │   │ wordpress       │ │  │ │
│  │  │ │ /wp-json/        │   │ (Direct)        │ │  │ │
│  │  │ └──────────────────┘   └─────────────────┘ │  │ │
│  │  │                                             │  │ │
│  │  └─────────────────────────────────────────────┘  │ │
│  │                                                    │ │
│  └────────────────────────────────────────────────────┘ │
│                                                          │
│  MariaDB (3306) ←─── WordPress + Next.js              │
│                                                          │
└─────────────────────────────────────────────────────────┘
                            ▲
                            │
                  Cloudflare DNS
                            │
                        互聯網用戶
```

---

## 📋 部署策略

### 選項 A：Apache 反向代理（推薦）
**優點**：
- Next.js 在內部端口 3000，由 Apache 代理
- 統一使用 80/443，無需開放新端口
- SSL 由 Apache 管理
- 支持路由分流（/ → Next.js，/wp-admin → WordPress）

**缺點**：
- Apache 配置稍複雜
- 需要啟用 mod_proxy

### 選項 B：不同子域名
**優點**：
- 配置簡單
- 完全分離 Next.js 和 WordPress

**缺點**：
- 需要新 DNS 記錄和 SSL 證書

---

## 🚀 Phase 1：基礎設置（2-4 天）

### Phase 1.1：GitHub 倉庫初始化
- [ ] 在 GitHub 創建 `kayarine-nextjs-frontend`
- [ ] 初始化 git 工作流（main/develop 分支）
- [ ] 創建 .gitignore

### Phase 1.2：Next.js 項目初始化
```bash
npx create-next-app@latest kayarine-nextjs-frontend \
  --typescript \
  --tailwind \
  --app \
  --no-src-dir
```

**項目結構**：
```
kayarine-nextjs-frontend/
├── app/
│   ├── layout.tsx (根布局 + Header/Footer)
│   ├── page.tsx (首頁 /)
│   └── (pages)/
│       ├── rental-services/page.tsx
│       ├── water-activities/page.tsx
│       ├── brand-shop/page.tsx
│       ├── about/page.tsx
│       ├── blog/page.tsx
│       ├── event-planning/page.tsx
│       ├── privacy/page.tsx
│       ├── journey-policy/page.tsx
│       ├── booking-cancellation/page.tsx
│       └── terms/page.tsx
├── components/
│   ├── common/
│   │   ├── Header.tsx
│   │   └── Footer.tsx
│   ├── pages/
│   └── shared/
├── lib/
│   ├── api.ts (WordPress REST API)
│   ├── constants.ts
│   └── types.ts
├── public/
├── package.json
├── next.config.js
├── tsconfig.json
└── tailwind.config.js
```

### Phase 1.3：環境配置

**`.env.local`**：
```env
NEXT_PUBLIC_WORDPRESS_URL=http://localhost:80
NEXT_PUBLIC_API_ENDPOINT=/wp-json/kayarine/v1
```

**`next.config.js`** (輸出靜態/SSG)：
```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone', // 用於 Node.js 服務器部署
  // 或
  // output: 'export', // 用於靜態 HTML 部署
}

module.exports = nextConfig
```

### Phase 1.4：Header/Footer 生成

您在 Figma 完成設計後：

**`components/common/Header.tsx`** - 由 Roo Code 生成  
**`components/common/Footer.tsx`** - 由 Roo Code 生成  
**`app/layout.tsx`** - 共享布局

### Phase 1.5：本地測試

```bash
npm install
npm run dev

# 訪問 http://localhost:3000 測試
```

---

## 🔧 Phase 2：VM 部署設置（2-3 天）

### Phase 2.1：生產構建

```bash
npm run build

# 生成 .next/ 輸出目錄
```

### Phase 2.2：在 VM 上安裝 Node.js

SSH 到 VM：
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 檢查 Node.js
node --version

# 如果沒有，安裝
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Phase 2.3：部署 Next.js 應用到 VM

```bash
# 本地構建
npm run build

# 上傳到 VM
scp -i ssh/gcp-ssh-key -r .next package.json kayarine.server@104.199.144.122:/home/kayarine.server/kayarine-nextjs/

# SSH 到 VM 並安裝
ssh -i ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
  cd /home/kayarine.server/kayarine-nextjs
  npm install --production
"
```

### Phase 2.4：配置 Apache 反向代理

**編輯 Apache 配置**（在 VM 上）：

```bash
# 啟用 mod_proxy
sudo a2enmod proxy
sudo a2enmod proxy_http

# 編輯 Bitnami VHost 配置
sudo nano /opt/bitnami/apache/conf/bitnami/bitnami.conf
```

**添加反向代理配置**：
```apache
<VirtualHost *:80>
  ServerName kayarine.club
  DocumentRoot "/opt/bitnami/wordpress"
  
  # Next.js 反向代理
  <Location "/">
    ProxyPreserveHost On
    ProxyPass http://localhost:3000/
    ProxyPassReverse http://localhost:3000/
  </Location>
  
  # WordPress 管理後台和 REST API
  <Location "/wp-admin">
    ProxyPass !
  </Location>
  <Location "/wp-json">
    ProxyPass !
  </Location>
  <Location "/wp-content">
    ProxyPass !
  </Location>
  <Location "/wp-includes">
    ProxyPass !
  </Location>
  
  <Directory "/opt/bitnami/wordpress">
    Require all granted
  </Directory>
</VirtualHost>
```

### Phase 2.5：使用 PM2 管理 Next.js 進程

```bash
# 在 VM 上安裝 PM2
npm install -g pm2

# 創建 ecosystem.config.js
cat > /home/kayarine.server/kayarine-nextjs/ecosystem.config.js << 'EOF'
module.exports = {
  apps: [
    {
      name: 'kayarine-nextjs',
      script: 'npm',
      args: 'start',
      cwd: '/home/kayarine.server/kayarine-nextjs',
      instances: 'max',
      exec_mode: 'cluster',
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      }
    }
  ]
}
EOF

# 啟動
pm2 start ecosystem.config.js

# 設置開機自動啟動
pm2 startup
pm2 save
```

### Phase 2.6：驗證部署

```bash
# 測試 Next.js 進程
ps aux | grep "node\|npm"

# 測試端口 3000
netstat -tlnp | grep 3000

# 測試 Apache 反向代理
curl http://localhost/ | head -50

# 驗證公開訪問
# 打開瀏覽器訪問 https://kayarine.club
```

---

## 🔄 Phase 3：逐頁部署循環（11 頁）

### 循環流程

```
FOR page IN [1, 2, 3, ..., 11]:
  
  步驟 1：Figma 設計（2-3 小時）
  步驟 2：Roo Code 生成（1-2 小時）
  步驟 3：本地測試（30 分鐘）
    npm run dev
    
  步驟 4：部署到 VM（15 分鐘）
    npm run build
    scp -r .next package.json ...
    # 重啟 Next.js
    ssh ... "pm2 restart kayarine-nextjs"
    
  步驟 5：驗證上線（15 分鐘）
    打開瀏覽器驗證 https://kayarine.club/[page]

END FOR
```

---

## 📊 時間估計

| 階段 | 任務 | 時長 |
|------|------|------|
| Phase 1.1 | GitHub 初始化 | 30 分鐘 |
| Phase 1.2-1.3 | Next.js + 配置 | 2 小時 |
| Phase 1.4 | Header/Footer 生成 | 3-4 小時 |
| Phase 1.5 | 本地測試 | 1 小時 |
| Phase 2.1-2.6 | VM 部署 + Apache | 3-4 小時 |
| **Phase 3** | **逐頁循環** | **60-80 小時** |
| **總計** | **完整遷移** | **3-4 週** |

---

## ⚠️ 關鍵注意事項

1. **Node.js 版本**：推薦 Node.js 20+
2. **PM2 自動重啟**：配置開機自啟動
3. **Apache 代理**：確保 mod_proxy 已啟用
4. **數據庫連接**：WordPress 和 Next.js 都共享 MariaDB
5. **SSL 證書**：由 Apache 統一管理
6. **環境變量**：.env.local 在構建時需要包含

---

## 📝 待辦清單（已更新為 VM 部署）

- [ ] Phase 1.1：GitHub 倉庫初始化
- [ ] Phase 1.2：Next.js 14 項目初始化
- [ ] Phase 1.3：環境配置
- [ ] Phase 1.4：Header/Footer 生成
- [ ] Phase 1.5：本地測試
- [ ] Phase 2.1：生產構建
- [ ] Phase 2.2：VM Node.js 安裝
- [ ] Phase 2.3：上傳 Next.js 應用
- [ ] Phase 2.4：Apache 反向代理配置
- [ ] Phase 2.5：PM2 進程管理
- [ ] Phase 2.6：驗證部署
- [ ] Phase 3：逐頁部署循環 × 11
