# Kayarine 專案 Git 工作流程指南

## 🎯 核心原則

**永遠遵循這個順序：Git Commit → Git Push → 服務器 Git Pull → 部署**

不要直接用 SCP 上傳文件到服務器！這會導致版本混亂和難以追蹤的問題。

---

## 📋 標準工作流程

### 步驟 1: 本地開發 (在 Mac 上)

```bash
# 1. 確認你在正確的分支
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
git branch  # 確認當前分支（通常是 develop）

# 2. 開始開發前先拉取最新代碼
git pull origin develop

# 3. 進行開發工作
# 修改文件、添加功能、修復 bug 等...

# 4. 查看修改內容
git status              # 查看哪些文件被修改
git diff                # 查看具體修改內容
git diff <filename>     # 查看特定文件的修改

# 5. 測試本地運行
npm run dev             # 本地測試，確保沒有錯誤
```

### 步驟 2: 提交到 Git (本地)

```bash
# 1. 添加修改的文件
git add <filename>                           # 添加特定文件
git add components/rental-services/*.tsx     # 添加特定目錄下的文件
git add .                                    # 添加所有修改（謹慎使用）

# 2. 查看將要提交的內容
git status

# 3. 提交更改（使用清晰的 commit message）
git commit -m "類型: 簡短描述

- 詳細說明第一點
- 詳細說明第二點
- 詳細說明第三點"

# Commit 類型參考：
# - feat: 新功能
# - fix: 修復 bug
# - refactor: 重構代碼
# - style: 樣式調整
# - docs: 文檔更新
# - perf: 性能優化
# - test: 測試相關

# 範例：
git commit -m "fix: 移除預訂表單冗餘欄位 + 修復付款錯誤

- 移除「參加方式」區塊，簡化結帳流程
- 增強 API 錯誤處理，添加超時機制
- 改進表單驗證和用戶反饋"
```

### 步驟 3: 推送到 GitHub

```bash
# 推送到遠端倉庫
git push origin develop

# 如果出現認證問題，使用 GitHub CLI 或配置 SSH key
# 或者先在 GitHub Desktop 中推送
```

### 步驟 4: 服務器同步 (在 GCP 服務器上)

```bash
# SSH 連接到服務器
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 進入專案目錄
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend

# 拉取最新代碼
git pull origin develop

# 如果有衝突，解決衝突後：
git add <conflicted-files>
git commit -m "解決衝突"

# 確認當前版本
git log -1              # 查看最新 commit
git status             # 確認沒有未提交的更改
```

### 步驟 5: 部署到生產環境

```bash
# 還在服務器 SSH 連接中

# 1. 清理舊的構建
sudo rm -rf .next

# 2. 安裝依賴（如有新增）
npm install

# 3. 構建生產版本
npm run build

# 4. 重啟 PM2 服務
pm2 restart kayarine-nextjs-frontend --update-env

# 5. 查看日誌確認運行正常
pm2 logs kayarine-nextjs-frontend --lines 30

# 6. 檢查錯誤日誌
pm2 logs kayarine-nextjs-frontend --lines 50 --err
```

### 步驟 6: 更新開發日誌

```bash
# 回到本地 Mac
# 編輯 DEVELOPMENT_LOG.md
vim /Users/henrylo/Documents/GitHub/calendar/DEVELOPMENT_LOG.md

# 添加新的部署記錄：
## YYYY-MM-DD (功能描述 vX.X.X) ✅

### 部署詳情
- **版本**：vX.X.X
- **時間戳**：YYYY-MM-DDTHH:mm UTC+8
- **部署狀態**：✅ 成功
- **核心改進**：簡短描述

### 修改內容
...

# 提交開發日誌
cd /Users/henrylo/Documents/GitHub/calendar
git add DEVELOPMENT_LOG.md
git commit -m "docs: 更新開發日誌 vX.X.X"
git push origin main  # 或 master，取決於你的分支名稱
```

---

## 🚫 常見錯誤與解決

### 錯誤 1: 使用 SCP 直接上傳文件

**❌ 錯誤做法：**
```bash
scp CheckoutForm.tsx kayarine.server@104.199.144.122:/path/to/file
```

**✅ 正確做法：**
```bash
# 本地
git add CheckoutForm.tsx
git commit -m "fix: 更新 CheckoutForm"
git push origin develop

# 服務器
ssh kayarine.server@104.199.144.122
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
git pull origin develop
npm run build
pm2 restart kayarine-nextjs-frontend
```

### 錯誤 2: 忘記 Git Pull 就開始開發

**問題**：本地代碼不是最新的，容易產生衝突

**解決**：
```bash
# 每次開發前
git pull origin develop

# 如果已經修改了文件，先暫存
git stash
git pull origin develop
git stash pop
```

### 錯誤 3: 服務器和本地代碼不一致

**檢查方法**：
```bash
# 本地
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
git log -1

# 服務器
ssh kayarine.server@104.199.144.122
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
git log -1

# 比較兩個 commit hash 是否一致
```

**解決**：
```bash
# 在服務器上
git fetch origin
git reset --hard origin/develop  # ⚠️ 會丟失本地未提交的修改
npm run build
pm2 restart kayarine-nextjs-frontend
```

---

## 📝 快速參考命令

### 本地開發循環
```bash
git pull origin develop          # 拉取最新
# 進行開發...
git status                       # 查看修改
git add <files>                  # 添加文件
git commit -m "message"          # 提交
git push origin develop          # 推送
```

### 服務器部署循環
```bash
ssh -i ~/.ssh/gcp-ssh-key kayarine.server@104.199.144.122
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
git pull origin develop
sudo rm -rf .next
npm run build
pm2 restart kayarine-nextjs-frontend --update-env
pm2 logs kayarine-nextjs-frontend --lines 30
```

### 緊急回滾
```bash
# 在服務器上
git log -5                                    # 查看最近 5 個 commit
git reset --hard <previous-commit-hash>       # 回滾到指定版本
npm run build
pm2 restart kayarine-nextjs-frontend
```

---

## 🎨 Commit Message 模板

```
類型: 簡短描述（不超過 50 字符）

詳細說明問題和解決方案：
- 修改了什麼
- 為什麼修改
- 如何測試

相關問題: #issue-number
```

**類型選擇：**
- `feat`: 新功能
- `fix`: Bug 修復
- `refactor`: 代碼重構
- `style`: 樣式更改
- `docs`: 文檔更新
- `perf`: 性能優化
- `test`: 測試相關
- `chore`: 構建/工具更改

---

## ⚡ 一鍵部署腳本（未來可選）

創建 `deploy.sh` 在本地：

```bash
#!/bin/bash
# deploy.sh - 自動化部署腳本

echo "📋 檢查 Git 狀態..."
git status

echo "📤 推送到 GitHub..."
git push origin develop

echo "🚀 連接服務器並部署..."
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend && \
git pull origin develop && \
sudo rm -rf .next && \
npm run build && \
pm2 restart kayarine-nextjs-frontend --update-env && \
sleep 3 && \
pm2 logs kayarine-nextjs-frontend --lines 30
"

echo "✅ 部署完成！"
```

使用：
```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 📌 重要提醒

1. **永遠先 commit，後部署**
2. **本地測試通過後再推送**
3. **保持 commit 信息清晰明確**
4. **每次部署後更新 DEVELOPMENT_LOG.md**
5. **不要在生產服務器上直接修改代碼**
6. **定期檢查本地和服務器代碼是否同步**

---

## 🔗 相關文檔

- [DEPLOYMENT_GUIDE_GCP_STANDARD.md](./DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 詳細部署指南
- [DEVELOPMENT_LOG.md](./DEVELOPMENT_LOG.md) - 開發日誌
- [SSH_REFERENCE.md](./SSH_REFERENCE.md) - SSH 連接參考

---

最後更新：2026-02-05
