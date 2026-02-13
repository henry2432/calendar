# Git 工作流程提醒 Prompt

## 📋 每次開發結束時使用此 Prompt

複製以下內容，在完成開發後提醒 AI 助手執行標準 Git 工作流程：

---

```
請幫我執行標準 Git 工作流程：

1. 檢查當前 Git 狀態（git status、未提交的文件、領先的 commits）
2. 查看並總結所有未提交的修改內容
3. 如有未提交的文件，生成合適的 commit message 並提交
4. 推送所有 commits 到 GitHub（如遇認證問題請提示我）
5. 更新 DEVELOPMENT_LOG.md（添加版本號、時間戳、修改內容）
6. 提醒我是否需要部署到 GCP 服務器

參考文檔：
- /Users/henrylo/Documents/GitHub/calendar/GIT_WORKFLOW.md
- /Users/henrylo/Documents/GitHub/calendar/SERVER_GIT_SETUP.md
```

---

## 🎯 更詳細版本（用於複雜修改）

```
請執行完整的 Git 和部署檢查：

【Git 狀態檢查】
1. 顯示當前分支、遠端倉庫、領先/落後狀態
2. 列出所有未提交的文件及修改類型
3. 顯示最近 5 個 commits

【代碼審查】
4. 查看每個未提交文件的 diff
5. 識別主要修改內容和影響範圍
6. 建議是否需要分多個 commits

【提交和推送】
7. 生成符合規範的 commit message（類型: 簡短描述 + 詳細列表）
8. 執行 git add 和 git commit
9. 推送到 GitHub origin/develop

【文檔更新】
10. 更新 /Users/henrylo/Documents/GitHub/calendar/DEVELOPMENT_LOG.md
11. 記錄版本號（v2.3.X）、時間戳、修改內容

【部署檢查】
12. 確認服務器代碼同步方式（Git pull 或 SCP）
13. 如需部署，提供完整的部署命令
14. 提醒部署後檢查日誌

參考：
- 本地倉庫: /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
- 遠端: https://github.com/henry2432/kayarine-nextjs-frontend.git
- 服務器: kayarine.server@104.199.144.122
```

---

## 🚀 快速部署版本（已有 commits，只需部署）

```
我的本地 commits 已就緒，請幫我部署到 GCP 服務器：

1. 確認本地 Git 狀態（是否已 push）
2. 連接服務器並同步代碼（git pull 或提供 SCP 命令）
3. 執行標準部署流程：
   - npm install（如有新依賴）
   - sudo rm -rf .next
   - npm run build
   - pm2 restart kayarine-nextjs-frontend
4. 查看部署日誌並確認運行狀態

服務器信息：
- SSH Key: /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key
- 用戶: kayarine.server@104.199.144.122
- 目錄: /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
```

---

## 💡 使用建議

### 何時使用哪個版本：

**簡潔版**：
- 日常小改動
- 修復 1-2 個文件
- 快速迭代開發

**詳細版**：
- 重大功能開發
- 多文件多模組修改
- 需要詳細審查的改動

**部署版**：
- 代碼已提交完成
- 只需要部署到服務器
- 緊急熱修復

### 最佳實踐：

1. **開發完成後立即使用** - 不要累積多天的修改
2. **先用簡潔版檢查** - 如發現問題再用詳細版
3. **記得更新文檔** - DEVELOPMENT_LOG.md 是重要的版本記錄
4. **部署後驗證** - 查看日誌確認無錯誤

---

## 📌 常用命令快速參考

```bash
# 本地 Git 操作
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
git status
git add <files>
git commit -m "type: 描述"
git push origin develop

# 服務器部署（通過 SSH）
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122
cd /home/kayarine.server/kayarine-nextjs/kayarine-nextjs-frontend
git pull origin develop  # 如果服務器是 Git 倉庫
sudo rm -rf .next && npm run build
pm2 restart kayarine-nextjs-frontend --update-env
pm2 logs kayarine-nextjs-frontend --lines 30
```

---

最後更新：2026-02-05
