# Phase 1.2：Next.js 14 項目初始化與項目結構創建

## 🎯 目標
在已有的 GitHub 倉庫中初始化 Next.js 14 項目，包括 TypeScript、Tailwind CSS、App Router

---

## 📋 第 1 步：初始化 Next.js 14 項目

### 1.1 進入倉庫目錄並創建 Next.js 應用

在終端執行：

```bash
# 進入 kayarine-nextjs-frontend 目錄
cd ~/path/to/kayarine-nextjs-frontend

# 使用 create-next-app 初始化 Next.js 14
npx create-next-app@latest . \
  --typescript \
  --tailwind \
  --app \
  --no-eslint \
  --import-alias '@/*' \
  --skip-git

# 選項說明：
# . = 在當前目錄安裝（已有 .git）
# --typescript = 使用 TypeScript
# --tailwind = 安裝 Tailwind CSS
# --app = 使用 App Router（不是 Pages Router）
# --no-eslint = 跳過 ESLint（可稍後添加）
# --import-alias '@/*' = 配置路徑別名 @/
# --skip-git = 跳過 git 初始化（已有 .git）
```

### 1.2 互動選項（npx 會提示）

```
✔ Would you like to use TypeScript? › Yes
✔ Would you like to use ESLint? › No (or Yes)
✔ Would you like to use Tailwind CSS? › Yes
✔ Would you like to use `src/` directory? › No
✔ Would you like to use App Router? › Yes
✔ Would you like to customize the import alias? › Yes
✔ What import alias would you like configured? › @/*
✔ Would you like to skip the git initialization? › Yes
```

### 1.3 驗證初始化完成

```bash
# 查看生成的項目結構
ls -la

# 應該看到：
# app/
# components/
# public/
# node_modules/
# package.json
# tsconfig.json
# tailwind.config.js
# next.config.js
# ...
```

---

## 📋 第 2 步：調整項目結構

### 2.1 重新組織 components 目錄

```bash
# 創建子目錄
mkdir -p app/components/common
mkdir -p app/components/pages
mkdir -p app/components/shared
mkdir -p app/lib
mkdir -p app/styles

# 注：App Router 中，components 可以在 app/ 目錄內
```

### 2.2 移動和創建文件

```bash
# 如果 components/ 在根目錄，需要移動到 app/ 內
# 或者保持在根目錄也可以（推薦保持在根目錄便於管理）

# 創建空文件夾（用於組件存放）
mkdir -p components/common
mkdir -p components/pages
mkdir -p components/shared

# 創建 lib 目錄用於工具函數
mkdir -p lib

# 創建 styles 目錄
mkdir -p styles
```

---

## 📋 第 3 步：創建 11 個頁面路由

### 3.1 創建頁面文件結構

```bash
# 創建頁面組目錄
mkdir -p app/(pages)

# 創建各頁面目錄和 page.tsx 文件
# 首頁已有 app/page.tsx

# 租借服務
mkdir -p app/\(pages\)/rental-services
touch app/\(pages\)/rental-services/page.tsx

# 水上活動
mkdir -p app/\(pages\)/water-activities
touch app/\(pages\)/water-activities/page.tsx

# 品牌商店
mkdir -p app/\(pages\)/brand-shop
touch app/\(pages\)/brand-shop/page.tsx

# 關於我們
mkdir -p app/\(pages\)/about
touch app/\(pages\)/about/page.tsx

# Blog
mkdir -p app/\(pages\)/blog
touch app/\(pages\)/blog/page.tsx

# 活動策劃
mkdir -p app/\(pages\)/event-planning
touch app/\(pages\)/event-planning/page.tsx

# 私隱政策
mkdir -p app/\(pages\)/privacy
touch app/\(pages\)/privacy/page.tsx

# 旅程政策
mkdir -p app/\(pages\)/journey-policy
touch app/\(pages\)/journey-policy/page.tsx

# 預訂及取消政策
mkdir -p app/\(pages\)/booking-cancellation
touch app/\(pages\)/booking-cancellation/page.tsx

# 條款及細則
mkdir -p app/\(pages\)/terms
touch app/\(pages\)/terms/page.tsx
```

### 3.2 初始化頁面模板

每個 `page.tsx` 文件應包含基本模板：

**`app/(pages)/rental-services/page.tsx`**：
```typescript
import React from 'react'

export default function RentalServicesPage() {
  return (
    <div>
      <h1>租借服務</h1>
      <p>此頁面內容將由 Figma 設計生成</p>
    </div>
  )
}
```

類似地為其他 10 個頁面創建模板（後續由 Roo Code 生成）

---

## 📋 第 4 步：配置關鍵文件

### 4.1 更新 `tsconfig.json`

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "esModuleInterop": true,

    /* Bundler mode */
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",

    /* Linting */
    "strict": true,
    "noUnusedLocals": false,
    "noUnusedParameters": false,
    "noFallthroughCasesInSwitch": true,
    
    /* Path alias */
    "baseUrl": ".",
    "paths": {
      "@/*": ["./*"]
    }
  },
  "include": ["next-env.d.ts", "**/*.ts", "**/*.tsx"],
  "exclude": ["node_modules"]
}
```

### 4.2 驗證 `next.config.js`

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone', // 用於 Node.js 服務器部署
  reactStrictMode: true,
}

module.exports = nextConfig
```

### 4.3 驗證 `tailwind.config.js`

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/**/*.{js,ts,jsx,tsx}',
    './components/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

---

## 📋 第 5 步：安裝額外依賴

### 5.1 安裝常用庫

```bash
# React Query（TanStack Query）- 用於數據獲取和狀態管理
npm install @tanstack/react-query

# Axios - HTTP 客戶端
npm install axios

# Clsx - 條件類名組合
npm install clsx

# 類型定義
npm install -D @types/node @types/react
```

### 5.2 檢查 package.json

```json
{
  "name": "kayarine-nextjs-frontend",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint"
  },
  "dependencies": {
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "next": "^14.0.0",
    "@tanstack/react-query": "^5.0.0",
    "axios": "^1.6.0",
    "clsx": "^2.0.0"
  },
  "devDependencies": {
    "typescript": "^5.0.0",
    "tailwindcss": "^3.0.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0",
    "@types/node": "^20.0.0",
    "@types/react": "^18.0.0",
    "@types/react-dom": "^18.0.0"
  }
}
```

---

## 📋 第 6 步：驗證項目結構

```bash
# 最終項目結構應為：
tree -L 2 -I 'node_modules'

# kayarine-nextjs-frontend/
# ├── .git/
# ├── .gitignore
# ├── .next/
# ├── .env.local (本地，不提交)
# ├── .env.example
# ├── app/
# │   ├── layout.tsx (Root Layout - 待生成 Header/Footer)
# │   ├── page.tsx (首頁)
# │   ├── (pages)/
# │   │   ├── rental-services/page.tsx
# │   │   ├── water-activities/page.tsx
# │   │   ├── brand-shop/page.tsx
# │   │   ├── about/page.tsx
# │   │   ├── blog/page.tsx
# │   │   ├── event-planning/page.tsx
# │   │   ├── privacy/page.tsx
# │   │   ├── journey-policy/page.tsx
# │   │   ├── booking-cancellation/page.tsx
# │   │   └── terms/page.tsx
# │   ├── globals.css (Tailwind 全局樣式)
# │   └── favicon.ico
# ├── components/
# │   ├── common/
# │   ├── pages/
# │   └── shared/
# ├── lib/
# │   ├── api.ts (待創建 - WordPress API)
# │   ├── constants.ts
# │   └── types.ts
# ├── public/
# │   └── images/ (稍後添加)
# ├── styles/
# ├── package.json
# ├── tsconfig.json
# ├── tailwind.config.js
# ├── next.config.js
# ├── README.md
# └── node_modules/
```

---

## 📋 第 7 步：提交到 Git

```bash
# 檢查狀態
git status

# 添加所有文件到 develop 分支
git add .
git commit -m "feat: Initialize Next.js 14 with TypeScript and Tailwind CSS"

# 推送到遠端 develop
git push origin develop
```

---

## 🧪 第 8 步：本地測試運行

```bash
# 啟動開發服務器
npm run dev

# 應該看到輸出：
# ▲ Next.js 14.0.0
# - Local:        http://localhost:3000
# - Environments: .env.local

# 在瀏覽器中訪問 http://localhost:3000
# 應該看到 Next.js 默認歡迎頁面

# 測試路由
# http://localhost:3000/rental-services
# http://localhost:3000/water-activities
# ... etc

# 按 Ctrl+C 停止開發服務器
```

---

## 📝 Phase 1.2 完成檢查清單

- [ ] 在 kayarine-nextjs-frontend 目錄中運行 `create-next-app`
- [ ] 使用 TypeScript、Tailwind CSS、App Router
- [ ] 創建 11 個頁面的目錄和 page.tsx 文件
- [ ] 配置 tsconfig.json（路徑別名 @/*）
- [ ] 配置 tailwind.config.js
- [ ] 安裝額外依賴（@tanstack/react-query、axios 等）
- [ ] 驗證項目結構無誤
- [ ] 提交到 git develop 分支
- [ ] 本地 npm run dev 測試成功

---

## 🚀 進入 Phase 1.3

完成上述步驟後：

1. **提交 commit**：`"feat: Initialize Next.js 14 with TypeScript and Tailwind CSS"`
2. **推送到 develop**：`git push origin develop`
3. **準備進入 Phase 1.3**：環境配置（API 常量、類型定義）

---

## ⚠️ 常見問題

### Q: 報錯 `EACCES: permission denied`？
```bash
# 解決方案：使用 sudo
sudo npm install

# 或清除緩存重試
npm cache clean --force
npm install
```

### Q: TypeScript 報錯？
```bash
# 確保 TypeScript 已安裝
npm install --save-dev typescript

# 生成 tsconfig.json
npx tsc --init
```

### Q: Tailwind CSS 未載入？
```bash
# 確保 app/globals.css 包含 Tailwind 指令
# 並在 app/layout.tsx 中導入

import './globals.css'
```

### Q: 需要清除 .next 緩存？
```bash
rm -rf .next
npm run dev
```
