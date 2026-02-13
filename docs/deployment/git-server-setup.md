# 服務器 Git 配置指南

## 🔍 問題診斷

**當前狀況**：
- ❌ 服務器上的代碼不是 Git 倉庫
- ❌ 使用 tar.gz 和 SCP 上傳，無法追蹤版本
- ❌ 本地和服務器代碼容易不同步

**目標狀態**：
- ✅ 服務器使用 Git 管理代碼
- ✅ 本地 push → 服務器 pull 同步
- ✅ 完整的版本控制和追蹤

---

## 🛠️ 解決方案：在服務器上設置 Git 倉庫

### 方案 A：完全重新克隆（推薦，最乾淨）

```bash
# 1. SSH 連接到服務器
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 2. 備份當前代碼
cd /home/kayarine.server/kayarine-nextjs
mv kayarine-nextjs-frontend kayarine-nextjs-frontend.backup.$(date +%Y%m%d)

# 3. 克隆 Git 倉庫
git clone https://github.com/YOUR_USERNAME/kayarine-nextjs-frontend.git

# 或使用 SSH（如果配置了 SSH key）
git clone git@github.com:YOUR_USERNAME/kayarine-nextjs-frontend.git

# 4. 進入目錄
cd kayarine-nextjs-frontend

# 5. 切換到 develop 分支
git checkout develop

# 6. 複製環境配置文件
cp ../kayarine-nextjs-frontend.backup.*/env.local ./.env.local
cp ../kayarine-nextjs-frontend.backup.*/ecosystem.config.js ./

# 7. 安裝依賴
npm install

# 8. 構建
sudo rm -rf .next
npm run build

# 9. 重啟服務
pm2 restart kayarine-nextjs-frontend --update-env
pm2 logs kayarine-nextjs-frontend --lines 30
```

### 方案 B：在現有目錄初始化 Git（保留現有文件）

```bash
# 1. SSH 連接到服務器
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 2. 進入現有目錄
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend

# 3. 初始化 Git
git init

# 4. 添加遠端倉庫
git remote add origin https://github.com/YOUR_USERNAME/kayarine-nextjs-frontend.git

# 5. 獲取遠端分支
git fetch origin

# 6. 切換到 develop 分支（會覆蓋本地文件）
git checkout -b develop origin/develop

# 或強制重置到遠端版本
git reset --hard origin/develop

# 7. 確認同步
git status
git log -1

# 8. 重新構建
sudo rm -rf .next
npm install
npm run build
pm2 restart kayarine-nextjs-frontend --update-env
```

---

## 📋 未來標準工作流程

### 本地開發 → 部署

```bash
# === 在 Mac 本地 ===
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend

# 1. 拉取最新代碼
git pull origin develop

# 2. 開發和測試
# ... 修改代碼 ...
npm run dev  # 本地測試

# 3. 提交更改
git add <files>
git commit -m "fix: 描述"
git push origin develop

# === 在 GCP 服務器 ===
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend

# 4. 拉取最新代碼
git pull origin develop

# 5. 安裝新依賴（如有）
npm install

# 6. 構建和部署
sudo rm -rf .next
npm run build
pm2 restart kayarine-nextjs-frontend --update-env
pm2 logs kayarine-nextjs-frontend --lines 30
```

---

## 🔧 配置 GitHub 訪問（服務器上）

### 選項 1：使用 HTTPS + Personal Access Token

```bash
# 在服務器上
git config --global credential.helper store

# 第一次 pull/push 時輸入：
# Username: YOUR_GITHUB_USERNAME
# Password: YOUR_PERSONAL_ACCESS_TOKEN (不是密碼！)

# 生成 Personal Access Token：
# https://github.com/settings/tokens
# 選擇 repo 權限
```

### 選項 2：使用 SSH Key（推薦）

```bash
# 在服務器上生成 SSH key
ssh-keygen -t ed25519 -C "kayarine.server@kayarine.club"

# 查看公鑰
cat ~/.ssh/id_ed25519.pub

# 複製公鑰，添加到 GitHub：
# https://github.com/settings/keys

# 測試連接
ssh -T git@github.com

# 更改遠端 URL 為 SSH
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
git remote set-url origin git@github.com:YOUR_USERNAME/kayarine-nextjs-frontend.git
```

---

## 🚀 一鍵部署腳本

創建本地腳本 `deploy-to-gcp.sh`：

```bash
#!/bin/bash
# deploy-to-gcp.sh - 自動化 Git 工作流程

set -e  # 任何錯誤立即退出

echo "🔍 檢查本地 Git 狀態..."
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend

if [[ -n $(git status -s) ]]; then
    echo "⚠️  有未提交的更改："
    git status -s
    read -p "是否繼續？(y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "📤 推送到 GitHub..."
git push origin develop

echo "🚀 連接服務器並部署..."
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'ENDSSH'
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend

echo "📥 拉取最新代碼..."
git pull origin develop

echo "📦 檢查依賴..."
npm install

echo "🏗️  構建生產版本..."
sudo rm -rf .next
npm run build

echo "🔄 重啟服務..."
pm2 restart kayarine-nextjs-frontend --update-env

echo "📋 查看日誌..."
sleep 3
pm2 logs kayarine-nextjs-frontend --lines 30 --nostream

ENDSSH

echo "✅ 部署完成！"
echo "🌐 訪問: http://104.199.144.122:3000"
```

使用：
```bash
chmod +x deploy-to-gcp.sh
./deploy-to-gcp.sh
```

---

## 🎯 檢查清單

在服務器上設置完成後，檢查以下項目：

```bash
# 在服務器上
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend

# ✅ 檢查 1：Git 倉庫狀態
git status
# 應該看到：On branch develop, nothing to commit, working tree clean

# ✅ 檢查 2：遠端配置
git remote -v
# 應該看到：origin https://github.com/... (fetch/push)

# ✅ 檢查 3：當前分支
git branch
# 應該看到：* develop

# ✅ 檢查 4：最新 commit
git log -1
# 應該與本地 Mac 的 commit 一致

# ✅ 檢查 5：能否拉取
git pull origin develop
# 應該看到：Already up to date.

# ✅ 檢查 6：PM2 運行狀態
pm2 status
# kayarine-nextjs-frontend 應該是 online
```

---

## 🚨 故障排除

### 問題：git pull 出現衝突

```bash
# 查看衝突文件
git status

# 選項 A：保留遠端版本（丟棄本地更改）
git reset --hard origin/develop

# 選項 B：手動解決衝突
vim <conflicted-file>
git add <conflicted-file>
git commit -m "解決衝突"
```

### 問題：忘記本地 push 就去服務器 pull

```bash
# 服務器會顯示：Already up to date.

# 解決：回到本地先 push
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
git push origin develop
```

### 問題：服務器文件被手動修改

```bash
# 檢查修改
git status
git diff

# 丟棄所有本地修改
git reset --hard HEAD
git clean -fd

# 拉取最新
git pull origin develop
```

---

## 📝 重要提醒

1. **永遠不要在服務器上直接修改代碼**
2. **所有修改都在本地完成，然後 push**
3. **服務器只做 pull、build、restart**
4. **定期檢查本地和服務器 commit 是否一致**
5. **部署前先本地測試**

---

## 📚 相關文檔

- [GIT_WORKFLOW.md](./GIT_WORKFLOW.md) - 詳細 Git 工作流程
- [DEPLOYMENT_GUIDE_GCP_STANDARD.md](./DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 部署指南
- [DEVELOPMENT_LOG.md](./DEVELOPMENT_LOG.md) - 開發日誌

---

最後更新：2026-02-05
