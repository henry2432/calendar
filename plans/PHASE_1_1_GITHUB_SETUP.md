# Phase 1.1：GitHub 倉庫初始化與 Git 工作流設置

## 🎯 目標
設置 GitHub 倉庫和本地開發環境，為 Next.js 項目提供版本控制

---

## 📋 第 1 步：在 GitHub 上創建倉庫

### 1.1 在 GitHub 創建新倉庫

1. 訪問 https://github.com/new
2. 填寫以下信息：
   - **Repository name**: `kayarine-nextjs-frontend`
   - **Description**: Next.js Frontend - Headless WordPress Migration
   - **Visibility**: Private（或 Public，根據需要）
   - **Initialize this repository with**:
     - ✅ Add a README file
     - ✅ Add .gitignore → 選擇 Node
     - ✅ Choose a license → 選擇 MIT License

3. 點擊「Create repository」

---

## 📋 第 2 步：本地 Clone 並設置

### 2.1 Clone 倉庫到本地

在終端執行（在 Desktop 或您的開發目錄）：

```bash
# 進入您的開發目錄
cd ~/Desktop  # 或您的偏好位置

# Clone 倉庫
git clone https://github.com/[YOUR_USERNAME]/kayarine-nextjs-frontend.git

# 進入項目目錄
cd kayarine-nextjs-frontend
```

### 2.2 配置 Git 用戶信息（首次使用）

```bash
# 配置全局用戶名（一次即可）
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# 驗證配置
git config --list
```

---

## 📋 第 3 步：初始化分支工作流

### 3.1 創建 develop 分支

```bash
# 當前應在 main 分支
git branch

# 創建 develop 分支（從 main）
git checkout -b develop

# 推送 develop 到遠端
git push -u origin develop
```

### 3.2 設置分支保護規則（在 GitHub）

1. 進入 GitHub 倉庫 → Settings → Branches
2. 點擊「Add rule」
3. **Branch name pattern**: `main`
4. 啟用以下規則：
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging

5. 對 `develop` 重複上述過程

---

## 📋 第 4 步：初始文件結構

### 4.1 創建項目目錄結構

```bash
# 在項目根目錄執行
mkdir -p src/{app,components/{common,pages,shared},lib,styles}
mkdir -p public

# 創建 .env.example（不提交實際密碼）
cat > .env.example << 'EOF'
NEXT_PUBLIC_WORDPRESS_URL=http://localhost:80
NEXT_PUBLIC_API_ENDPOINT=/wp-json/kayarine/v1
EOF

# 創建 .env.local（本地開發，不提交到 git）
cp .env.example .env.local

# 確保 .env.local 在 .gitignore 中
echo ".env.local" >> .gitignore
```

### 4.2 初始 README

編輯 `README.md`：

```markdown
# Kayarine Next.js Frontend

Next.js 應用，作為 WordPress 無頭 CMS 的前端。

## 項目結構

- `src/app/` - Next.js 應用路由
- `src/components/` - React 組件
- `src/lib/` - 工具函數和 API 調用
- `public/` - 靜態資源

## 分支策略

- `main` - 生產環境分支
- `develop` - 開發分支
- `feature/*` - 功能分支

## 開發流程

1. 創建功能分支: `git checkout -b feature/your-feature develop`
2. 開發和提交: `git commit -m "描述"`
3. 推送: `git push origin feature/your-feature`
4. 在 GitHub 創建 Pull Request 到 `develop`
5. 審核和合併後，測試環境驗證
6. 最後從 `develop` 合併到 `main` 進行生產發佈

## 本地開發

\`\`\`bash
npm install
npm run dev
# 訪問 http://localhost:3000
\`\`\`
```

---

## 📋 第 5 步：提交初始設置

```bash
# 檢查狀態
git status

# 添加所有文件
git add .

# 提交
git commit -m "feat: Initialize Next.js project structure and git workflow"

# 推送到 develop
git push origin develop
```

---

## 🔄 Git 分支工作流（後續開發）

### 開發新功能的標準流程

```bash
# 1. 確保本地 develop 是最新的
git checkout develop
git pull origin develop

# 2. 創建功能分支
git checkout -b feature/header-footer-design develop

# 3. 進行開發、提交
git add .
git commit -m "feat: Add Header and Footer components"

# 4. 推送功能分支
git push origin feature/header-footer-design

# 5. 在 GitHub 創建 Pull Request
# - 從: feature/header-footer-design
# - 到: develop
# - 添加描述

# 6. 審核和合併後，刪除功能分支
git checkout develop
git pull origin develop
git branch -d feature/header-footer-design
git push origin --delete feature/header-footer-design
```

### 完整工作流示例

```bash
# ========== PHASE 1.4：生成 Header/Footer ==========

# 步驟 1：創建功能分支
git checkout -b feature/phase-1-4-header-footer develop

# 步驟 2：生成組件文件（由 Roo Code 完成）
# - 生成 src/components/common/Header.tsx
# - 生成 src/components/common/Footer.tsx
# - 生成 src/app/layout.tsx

# 步驟 3：提交
git add src/components/common/Header.tsx
git add src/components/common/Footer.tsx
git add src/app/layout.tsx
git commit -m "feat: Add Header and Footer shared components (Phase 1.4)"

# 步驟 4：推送
git push origin feature/phase-1-4-header-footer

# 步驟 5：在 GitHub 創建 PR，審核後合併到 develop

# 步驟 6：清理
git checkout develop
git pull origin develop
git branch -d feature/phase-1-4-header-footer
```

---

## 📝 .gitignore 完整配置

確保 `.gitignore` 包含：

```
# Dependencies
/node_modules
/.pnp
.pnp.js

# Testing
/coverage

# Next.js
/.next
/out

# Production
/build

# Misc
.DS_Store
*.pem
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# Logs
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# IDE
.vscode
.idea
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db
```

---

## ✅ 驗證設置完成

```bash
# 檢查分支
git branch -a

# 應該看到：
# * develop
#   main
#   remotes/origin/HEAD -> origin/main
#   remotes/origin/develop
#   remotes/origin/main

# 檢查遠端
git remote -v

# 應該看到：
# origin  https://github.com/[YOUR_USERNAME]/kayarine-nextjs-frontend.git (fetch)
# origin  https://github.com/[YOUR_USERNAME]/kayarine-nextjs-frontend.git (push)

# 檢查最近提交
git log --oneline -5
```

---

## 🎯 Phase 1.1 完成檢查清單

- [ ] GitHub 上創建了 kayarine-nextjs-frontend 倉庫
- [ ] 倉庫設置為 Private（可選）
- [ ] 本地 clone 了倉庫
- [ ] 創建了 develop 分支
- [ ] 配置了分支保護規則
- [ ] 創建了初始目錄結構
- [ ] 創建了 .env.example
- [ ] 更新了 README.md
- [ ] 初始提交已推送到 develop
- [ ] 所有分支在遠端可見

---

## 🚀 準備進入 Phase 1.2

完成上述所有步驟後，通知我開始 **Phase 1.2：Next.js 14 項目初始化**

此時倉庫結構應為：
```
kayarine-nextjs-frontend/
├── .git/
├── .github/
├── .gitignore
├── README.md
├── .env.example
├── .env.local (本地)
├── src/
│   ├── app/
│   ├── components/
│   ├── lib/
│   └── styles/
└── public/
```

Next.js 和依賴將在 Phase 1.2 添加。
