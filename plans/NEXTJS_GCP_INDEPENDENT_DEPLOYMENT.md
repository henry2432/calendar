# Next.js 獨立應用 + GCP Cloud Run 逐頁部署計劃

## 🏗️ 系統架構

```
┌─────────────────────────────────────────────────────────────────┐
│                      用户客户端 (瀏覽器)                            │
└────────────────────────────┬────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  首頁 (Home)      │  │  租借服務        │  │  ... 11 個頁面   │
│  - Next.js SSR   │  │  - Next.js SSR   │  │  - Next.js SSR   │
│  - React + TS    │  │  - React + TS    │  │  - React + TS    │
│  - Tailwind CSS  │  │  - Tailwind CSS  │  │  - Tailwind CSS  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                    ┌────────▼─────────┐
                    │  GCP Cloud Run   │
                    │  (Next.js App)   │
                    │  asia-east1-c    │
                    └────────┬─────────┘
                             │
                   ┌─────────▼──────────┐
                   │  WordPress REST API│
                   │  (Bitnami VM)      │
                   │  104.199.144.122   │
                   │  Port: 80/443      │
                   └────────────────────┘
```

---

## 📋 11 個靜態頁面清單

| # | 頁面名稱 | 路由 | 狀態 | 備註 |
|---|---------|------|------|------|
| 1 | 首頁 (Homepage) | `/` | ⏳ Pending | Hero + Featured Tours + Testimonials |
| 2 | 租借服務 (Rental Services) | `/rental-services` | ⏳ Pending | 服務列表 + 價格表 |
| 3 | 水上活動 (Water Activities) | `/water-activities` | ⏳ Pending | 活動卡片 + 預訂按鈕 |
| 4 | 品牌商店 (Brand Shop) | `/brand-shop` | ⏳ Pending | 商品網格 + 購物車集成 |
| 5 | 關於我們 (About Us) | `/about` | ⏳ Pending | 公司歷史 + 團隊介紹 |
| 6 | Blog | `/blog` | ⏳ Pending | 文章列表 + 詳細頁 |
| 7 | 活動策劃 (Event Planning) | `/event-planning` | ⏳ Pending | 活動表單 + 方案選項 |
| 8 | 私隱政策 (Privacy Policy) | `/privacy` | ⏳ Pending | 長文本頁面 |
| 9 | 旅程政策 (Journey Policy) | `/journey-policy` | ⏳ Pending | 長文本頁面 |
| 10 | 預訂及取消政策 (Booking & Cancellation) | `/booking-cancellation` | ⏳ Pending | 長文本頁面 |
| 11 | 條款及細則 (Terms & Conditions) | `/terms` | ⏳ Pending | 長文本頁面 |

---

## 🎯 第一階段：基礎設置（完成後才能開始逐頁部署）

### Phase 1.1: GitHub 倉庫初始化
- [ ] 在 GitHub 創建新倉庫 `kayarine-nextjs-frontend`
- [ ] Clone 到本地
- [ ] 初始化 Git 工作流（main/develop 分支）

### Phase 1.2: Next.js 項目初始化
- [ ] 初始化 Next.js 14+ 項目 (App Router)
- [ ] 安裝核心依賴：React 19, TypeScript, Tailwind CSS
- [ ] 創建項目結構
  ```
  kayarine-nextjs-frontend/
  ├── src/
  │   ├── app/
  │   │   ├── layout.tsx (Root Layout with Header/Footer)
  │   │   ├── page.tsx (Homepage)
  │   │   ├── (pages)/
  │   │   │   ├── rental-services/page.tsx
  │   │   │   ├── water-activities/page.tsx
  │   │   │   ├── brand-shop/page.tsx
  │   │   │   ├── about/page.tsx
  │   │   │   ├── blog/page.tsx
  │   │   │   ├── event-planning/page.tsx
  │   │   │   ├── privacy/page.tsx
  │   │   │   ├── journey-policy/page.tsx
  │   │   │   ├── booking-cancellation/page.tsx
  │   │   │   └── terms/page.tsx
  │   ├── components/
  │   │   ├── common/
  │   │   │   ├── Header.tsx
  │   │   │   └── Footer.tsx
  │   │   ├── pages/
  │   │   └── shared/
  │   ├── lib/
  │   │   ├── api.ts (WordPress REST API 調用)
  │   │   ├── constants.ts
  │   │   └── types.ts
  │   └── styles/
  │       └── globals.css
  ├── Dockerfile (GCP Cloud Run)
  ├── package.json
  ├── tsconfig.json
  └── next.config.js
  ```

### Phase 1.3: 環境配置
- [ ] 創建 `.env.local` 配置
  ```env
  NEXT_PUBLIC_API_URL=https://kayarine.com
  NEXT_PUBLIC_WORDPRESS_URL=http://104.199.144.122:80
  NEXT_PUBLIC_API_ENDPOINT=/wp-json/kayarine/v1
  ```
- [ ] 配置 TypeScript `tsconfig.json`
- [ ] 配置 Tailwind CSS

### Phase 1.4: 共享組件（Header/Footer）生成
- [ ] 在 Figma 完成 Header 設計
- [ ] 在 Figma 完成 Footer 設計
- [ ] 生成 Header.tsx 組件
- [ ] 生成 Footer.tsx 組件
- [ ] 創建共享 Layout.tsx
  ```typescript
  // src/app/layout.tsx
  import Header from '@/components/common/Header'
  import Footer from '@/components/common/Footer'
  
  export default function RootLayout({
    children,
  }: {
    children: React.ReactNode
  }) {
    return (
      <html lang="zh-TW">
        <body>
          <Header />
          <main>{children}</main>
          <Footer />
        </body>
      </html>
    )
  }
  ```

### Phase 1.5: 本地測試
- [ ] 運行 `npm run dev` 驗證項目啟動
- [ ] 測試 Header/Footer 響應式設計
- [ ] 測試導航路由

---

## 🚀 第二階段：GCP 部署基礎設置

### Phase 2.1: GCP 項目配置
- [ ] 在 GCP 創建新項目或使用現有項目
- [ ] 啟用 Cloud Run API
- [ ] 啟用 Artifact Registry API
- [ ] 創建 GCP 服務賬號 (Service Account)

### Phase 2.2: Docker 容器化
- [ ] 創建 Dockerfile
  ```dockerfile
  FROM node:20-alpine
  
  WORKDIR /app
  
  COPY package*.json ./
  RUN npm ci --only=production
  
  COPY .next ./.next
  COPY public ./public
  COPY next.config.js ./
  
  EXPOSE 3000
  
  ENV PORT 3000
  ENV NODE_ENV production
  
  CMD ["npm", "start"]
  ```
- [ ] 創建 `.dockerignore`
- [ ] 本地測試 Docker 構建

### Phase 2.3: GitHub Actions CI/CD 設置
- [ ] 創建 `.github/workflows/deploy-gcp.yml`
  ```yaml
  name: Deploy to GCP Cloud Run
  
  on:
    push:
      branches: [main]
  
  jobs:
    deploy:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v3
        
        - name: Set up Cloud SDK
          uses: google-github-actions/setup-gcloud@v1
          with:
            version: 'latest'
        
        - name: Configure Docker for GCP
          run: |
            gcloud auth configure-docker asia-east1-docker.pkg.dev
        
        - name: Build and Push
          run: |
            docker build -t asia-east1-docker.pkg.dev/${{ secrets.GCP_PROJECT }}/kayarine/nextjs:latest .
            docker push asia-east1-docker.pkg.dev/${{ secrets.GCP_PROJECT }}/kayarine/nextjs:latest
        
        - name: Deploy to Cloud Run
          run: |
            gcloud run deploy kayarine-nextjs \
              --image asia-east1-docker.pkg.dev/${{ secrets.GCP_PROJECT }}/kayarine/nextjs:latest \
              --platform managed \
              --region asia-east1 \
              --allow-unauthenticated
  ```
- [ ] 在 GitHub Secrets 配置：GCP_PROJECT, GCP_SA_KEY

### Phase 2.4: 首次部署驗證
- [ ] 手動觸發 GitHub Actions 部署
- [ ] 驗證 Cloud Run 服務上線
- [ ] 測試公開訪問 URL

---

## 🔄 第三階段：逐頁部署循環（11 次迭代）

### 循環流程結構

```
FOR page IN [1, 2, 3, ..., 11]:
  
  步驟 1: Figma 設計（2-3 小時）
    - 在 Figma 完成這一頁的設計
    - 確保響應式設計（Desktop/Tablet/Mobile）
    - 標記所有組件和顏色
    
  步驟 2: 代碼生成（1-2 小時）
    - Roo Code 生成 React 組件
    - 應用 Tailwind CSS 樣式
    - 添加 TypeScript 類型
    
  步驟 3: 集成測試（30 分鐘）
    - 集成到 Layout
    - 本地 `npm run dev` 測試
    - 驗證響應式設計
    
  步驟 4: 部署（10-15 分鐘）
    - Git commit 和 push 到 main
    - 監視 GitHub Actions 執行
    - 驗證 Cloud Run 更新
    
  步驟 5: 上線驗證（15 分鐘）
    - 打開公開 URL 測試
    - 檢查頁面排版、圖片加載
    - 記錄部署到 DEVELOPMENT_LOG.md

END FOR
```

---

## 📄 執行清單：逐頁循環

### 循環 1: 首頁 (Homepage)
- **Figma 設計清單**
  - [ ] Hero Section（大背景 + 文案 + CTA）
  - [ ] Featured Tours（3-4 個卡片）
  - [ ] Why Choose Us（特性列表）
  - [ ] Testimonials（評價部分）
  - [ ] CTA 部分
  
- **代碼生成**
  - [ ] 生成 Homepage 組件
  - [ ] 集成到 Layout
  - [ ] 測試並部署
  - [ ] 驗證上線

### 循環 2-11: 其他 10 頁面
- (重複相同流程，使用相同的清單模板)

---

## 🔗 WordPress API 集成

### API 基礎設置（Phase 1 完成後）
- [ ] 在 WordPress 中安裝 Kayarine REST API 插件或新增端點
- [ ] 創建必要的 API 路由：
  - `GET /wp-json/kayarine/v1/pages` - 獲取頁面列表
  - `GET /wp-json/kayarine/v1/pages/{slug}` - 獲取單一頁面內容
  - `GET /wp-json/kayarine/v1/posts` - 獲取文章列表
  - `GET /wp-json/kayarine/v1/products` - 獲取商品列表

### Next.js API 調用（src/lib/api.ts）
```typescript
const API_URL = process.env.NEXT_PUBLIC_WORDPRESS_URL
const API_ENDPOINT = process.env.NEXT_PUBLIC_API_ENDPOINT

export async function getPages() {
  const res = await fetch(`${API_URL}${API_ENDPOINT}/pages`)
  return res.json()
}

export async function getPageBySlug(slug: string) {
  const res = await fetch(`${API_URL}${API_ENDPOINT}/pages/${slug}`)
  return res.json()
}
```

---

## 📊 時間估計

| 階段 | 任務 | 時長 |
|------|------|------|
| Phase 1.1 | GitHub 初始化 | 30 分鐘 |
| Phase 1.2-1.3 | Next.js 項目 + 配置 | 1-2 小時 |
| Phase 1.4 | Header/Footer 生成 | 3-4 小時 |
| Phase 1.5 | 本地測試 | 1 小時 |
| Phase 2.1-2.4 | GCP 部署設置 | 2-3 小時 |
| **Phase 3** | **逐頁循環（11 頁）** | **60-80 小時** |
| | 每頁平均 | 6-7 小時 |

---

## 📝 關鍵文件清單

- `src/app/layout.tsx` - 根布局（包含 Header/Footer）
- `src/components/common/Header.tsx` - Header 組件
- `src/components/common/Footer.tsx` - Footer 組件
- `src/lib/api.ts` - WordPress API 調用函數
- `src/lib/types.ts` - TypeScript 類型定義
- `Dockerfile` - GCP Cloud Run 容器化
- `.github/workflows/deploy-gcp.yml` - 自動部署流程
- `next.config.js` - Next.js 配置
- `tailwind.config.js` - Tailwind 樣式配置

---

## ✅ 成功標記

- ✅ Next.js 項目在本地啟動 (`npm run dev`)
- ✅ GCP Cloud Run 服務已部署並可公開訪問
- ✅ Header/Footer 在所有頁面正確顯示
- ✅ 所有 11 頁面均已上線
- ✅ 所有頁面通過響應式設計測試（Desktop/Tablet/Mobile）
- ✅ WordPress API 正確集成（如需要）
- ✅ DEVELOPMENT_LOG.md 已更新所有部署記錄
