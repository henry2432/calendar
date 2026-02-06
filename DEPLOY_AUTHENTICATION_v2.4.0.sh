#!/bin/bash

# ============================================
# Kayarine 會員認證系統部署腳本 v2.4.0
# ============================================
# 創建日期：2026-02-06
# 功能：部署 Next.js JWT 認證系統
# ============================================

set -e  # 遇到錯誤立即退出

echo "============================================"
echo "開始部署會員認證系統 v2.4.0"
echo "============================================"

# 顏色輸出
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ============================================
# 步驟 1：推送代碼到 GitHub（本地執行）
# ============================================
echo -e "${YELLOW}步驟 1/10: 推送代碼到 GitHub...${NC}"
echo "請在本地終端執行："
echo ""
echo "  cd ~/Documents/GitHub/kayarine-nextjs-frontend"
echo "  git push origin develop"
echo ""
echo "  cd ~/Documents/GitHub/calendar"
echo "  git push origin main"
echo ""
read -p "代碼已推送？按 Enter 繼續..."

# ============================================
# 步驟 2：SSH 連接到服務器
# ============================================
echo -e "${YELLOW}步驟 2/10: 連接到 GCP 服務器...${NC}"
SERVER_USER="kayarine.server"
SERVER_IP="104.199.144.122"

echo "SSH 連接命令："
echo "  ssh ${SERVER_USER}@${SERVER_IP}"
echo ""
echo "以下命令需要在服務器上執行："
echo "============================================"

# ============================================
# 以下命令在服務器上執行
# ============================================

cat << 'EOF_SERVER_COMMANDS'

# ============================================
# 步驟 3：生成 JWT 密鑰
# ============================================
echo "步驟 3/10: 生成 JWT 密鑰..."
JWT_SECRET=$(node -e "console.log(require('crypto').randomBytes(32).toString('hex'))")
echo "JWT 密鑰已生成：$JWT_SECRET"
echo ""

# ============================================
# 步驟 4：配置環境變數
# ============================================
echo "步驟 4/10: 配置 .env.local..."
cd /var/www/kayarine-nextjs-frontend

# 備份現有 .env.local
if [ -f .env.local ]; then
    cp .env.local .env.local.backup.$(date +%Y%m%d_%H%M%S)
    echo "已備份現有 .env.local"
fi

# 創建新的 .env.local
cat > .env.local << EOF
# WordPress API
NEXT_PUBLIC_WORDPRESS_API_URL=https://kayarine.club

# MySQL 數據庫配置
DB_HOST=localhost
DB_USER=wordpress_readonly
DB_PASSWORD=REPLACE_WITH_ACTUAL_PASSWORD
DB_NAME=wordpress

# JWT 密鑰
JWT_SECRET=$JWT_SECRET
EOF

echo "✅ .env.local 已創建"
echo "⚠️  請編輯 .env.local 並替換 DB_PASSWORD"
echo ""
read -p "已更新密碼？按 Enter 繼續..."

# ============================================
# 步驟 5：創建 MySQL 只讀用戶
# ============================================
echo "步驟 5/10: 創建 MySQL 只讀用戶..."
echo "請執行以下 SQL 命令："
echo ""
echo "sudo mysql -u root -p"
echo ""
cat << 'SQL'
-- 創建只讀用戶（替換密碼）
CREATE USER 'wordpress_readonly'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';

-- 授予 SELECT 權限
GRANT SELECT ON wordpress.wp_users TO 'wordpress_readonly'@'localhost';
GRANT SELECT ON wordpress.wp_usermeta TO 'wordpress_readonly'@'localhost';

-- 授予 INSERT 權限（用於註冊）
GRANT INSERT ON wordpress.wp_users TO 'wordpress_readonly'@'localhost';
GRANT INSERT ON wordpress.wp_usermeta TO 'wordpress_readonly'@'localhost';

-- 刷新權限
FLUSH PRIVILEGES;

-- 退出
EXIT;
SQL
echo ""
read -p "MySQL 用戶已創建？按 Enter 繼續..."

# ============================================
# 步驟 6：測試數據庫連接
# ============================================
echo "步驟 6/10: 測試數據庫連接..."
echo "執行測試命令："
echo "  mysql -u wordpress_readonly -p wordpress -e 'SELECT COUNT(*) FROM wp_users;'"
echo ""
read -p "連接測試成功？按 Enter 繼續..."

# ============================================
# 步驟 7：拉取最新代碼
# ============================================
echo "步驟 7/10: 拉取最新代碼..."
cd /var/www/kayarine-nextjs-frontend

# 檢查當前 Git 狀態
git status

# 拉取最新代碼
git fetch origin
git checkout develop
git pull origin develop

echo "✅ 代碼已更新"
echo ""

# ============================================
# 步驟 8：安裝 npm 依賴
# ============================================
echo "步驟 8/10: 安裝 npm 依賴..."
npm install --legacy-peer-deps

echo "✅ 依賴安裝完成"
echo ""

# ============================================
# 步驟 9：構建生產版本
# ============================================
echo "步驟 9/10: 構建生產版本..."
npm run build

if [ $? -eq 0 ]; then
    echo "✅ 構建成功"
else
    echo "❌ 構建失敗，請檢查錯誤訊息"
    exit 1
fi
echo ""

# ============================================
# 步驟 10：重啟 PM2 服務
# ============================================
echo "步驟 10/10: 重啟 PM2 服務..."
pm2 restart kayarine-nextjs

# 查看日誌
echo ""
echo "查看日誌（按 Ctrl+C 退出）："
pm2 logs kayarine-nextjs --lines 50

EOF_SERVER_COMMANDS

# ============================================
# 部署後測試
# ============================================
echo ""
echo "============================================"
echo -e "${GREEN}部署完成！${NC}"
echo "============================================"
echo ""
echo "請進行以下測試："
echo ""
echo "1. 註冊測試："
echo "   訪問：https://kayarine.club/login"
echo "   - 切換到「註冊」標籤"
echo "   - 填寫姓名、郵箱、密碼"
echo "   - 點擊「註冊」"
echo "   - 預期：自動登入並跳轉到會員中心"
echo ""
echo "2. 登入測試："
echo "   - 登出後重新登入"
echo "   - 使用註冊的郵箱和密碼"
echo "   - 預期：成功登入"
echo ""
echo "3. 會員中心測試："
echo "   訪問：https://kayarine.club/member"
echo "   - 檢查用戶名顯示正確"
echo "   - 檢查會員等級顯示"
echo "   - 檢查積分和消費金額"
echo ""
echo "4. 認證保護測試："
echo "   - 登出"
echo "   - 直接訪問 /member"
echo "   - 預期：自動跳轉到登入頁面"
echo ""
echo "5. Token 持久化測試："
echo "   - 登入後關閉瀏覽器"
echo "   - 重新打開並訪問 /member"
echo "   - 預期：仍然保持登入狀態"
echo ""
echo "============================================"
echo "如遇到問題，請查看："
echo "  - PM2 日誌：pm2 logs kayarine-nextjs"
echo "  - 瀏覽器 Console"
echo "  - Network 面板"
echo "============================================"
echo ""
echo "部署文檔："
echo "  ~/calendar/AUTHENTICATION_SYSTEM_SETUP.md"
echo ""
