# 🚀 SSH 快速部署指南 - 加速開發流程

**用途：** 每次代碼修改後直接通過 SSH 部署至遠程服務器，無需手動操作

---

## 📋 前置設置（一次性）

### 1️⃣ 配置服務器信息

編輯 `deploy.conf` 文件（在項目根目錄）：

```bash
cat > deploy.conf << 'EOF'
# ========================================
# Kayarine 部署配置 - SSH 自動部署
# ========================================

# 服務器信息
DEPLOY_HOST="your-server.com"           # SSH 主機名或 IP
DEPLOY_USER="wordpress"                 # SSH 用戶名
DEPLOY_PORT="22"                        # SSH 端口（默認 22）
DEPLOY_KEY="/Users/henrylo/.ssh/id_rsa" # SSH 私鑰路徑

# WordPress 路徑
WP_PATH="/var/www/html/wordpress"       # 遠程 WordPress 根目錄
PLUGIN_PATH="$WP_PATH/wp-content/plugins/kayarine-booking"

# 備份
BACKUP_DIR="./backups"                  # 本地備份目錄
KEEP_BACKUPS="7"                        # 保留備份數量

# Slack 通知（可選）
SLACK_WEBHOOK=""                        # Slack Webhook URL（可選）

# 環境
ENVIRONMENT="production"                 # staging 或 production

EOF
```

### 2️⃣ 創建部署腳本

```bash
# 使用下面提供的 deploy.sh
chmod +x kayarine-booking/deploy.sh
```

### 3️⃣ 測試 SSH 連接

```bash
ssh -i /Users/henrylo/.ssh/id_rsa wordpress@your-server.com \
  "echo 'SSH 連接成功'"
```

---

## 🚀 快速部署命令

### 方案 A：一鍵部署（推薦）

```bash
# 自動創建備份、驗證、上傳、驗證
./deploy.sh

# 或指定環境
./deploy.sh staging
```

### 方案 B：快速部署（跳過備份）

```bash
./deploy.sh --fast
```

### 方案 C：僅上傳特定文件

```bash
# 只上傳修改的文件（更快）
./deploy.sh --files class-kayarine-checkout-manager.php
```

### 方案 D：部署 + 自動清除緩存

```bash
./deploy.sh --clear-cache
```

---

## 📝 部署腳本內容

在 `kayarine-booking/deploy.sh` 中：

```bash
#!/bin/bash

################################################################################
# Kayarine 快速 SSH 部署腳本
# 使用：./deploy.sh [環境] [選項]
# 選項：--fast（跳過備份），--clear-cache（清除緩存）
################################################################################

set -e

# 加載配置
if [ ! -f "deploy.conf" ]; then
    echo "❌ deploy.conf 未找到，請先運行初始化"
    exit 1
fi
source deploy.conf

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 參數解析
ENVIRONMENT="${1:-$ENVIRONMENT}"
FAST_MODE=false
CLEAR_CACHE=false
SPECIFIC_FILES=()

for arg in "$@"; do
    case $arg in
        --fast) FAST_MODE=true ;;
        --clear-cache) CLEAR_CACHE=true ;;
        --files) shift; SPECIFIC_FILES+=("$@") ;;
    esac
done

# 日誌函數
log_info() { echo -e "${BLUE}ℹ${NC} $1"; }
log_success() { echo -e "${GREEN}✓${NC} $1"; }
log_warning() { echo -e "${YELLOW}⚠${NC} $1"; }
log_error() { echo -e "${RED}✗${NC} $1"; exit 1; }

# 標題
echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Kayarine SSH 快速部署 - $ENVIRONMENT${NC}"
echo -e "${BLUE}║   $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════╝${NC}\n"

# 檢查SSH連接
log_info "驗證 SSH 連接..."
if ssh -i "$DEPLOY_KEY" -p "$DEPLOY_PORT" \
    -q "$DEPLOY_USER@$DEPLOY_HOST" "echo 'SSH OK'" 2>/dev/null; then
    log_success "SSH 連接成功"
else
    log_error "SSH 連接失敗，請檢查配置"
fi

# 創建本地備份（除非 --fast 模式）
if [ "$FAST_MODE" = false ]; then
    log_info "創建本地備份..."
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/kayarine_$(date +%Y%m%d_%H%M%S).tar.gz"
    tar -czf "$BACKUP_FILE" kayarine-booking/
    log_success "備份已創建: $BACKUP_FILE"
else
    log_warning "跳過備份（快速模式）"
fi

# 創建遠程備份
log_info "創建遠程備份..."
REMOTE_BACKUP="kayarine_backup_$(date +%Y%m%d_%H%M%S)"
ssh -i "$DEPLOY_KEY" -p "$DEPLOY_PORT" \
    "$DEPLOY_USER@$DEPLOY_HOST" \
    "cd $PLUGIN_PATH && \
     cp -r . /tmp/$REMOTE_BACKUP && \
     echo '遠程備份已創建: /tmp/$REMOTE_BACKUP'"

log_success "遠程備份已創建"

# 上傳文件
log_info "上傳修改文件..."

if [ ${#SPECIFIC_FILES[@]} -gt 0 ]; then
    # 上傳特定文件
    for file in "${SPECIFIC_FILES[@]}"; do
        scp -i "$DEPLOY_KEY" -P "$DEPLOY_PORT" \
            "kayarine-booking/includes/$file" \
            "$DEPLOY_USER@$DEPLOY_HOST:$PLUGIN_PATH/includes/"
        log_success "已上傳: $file"
    done
else
    # 上傳整個目錄
    rsync -avz -e "ssh -i $DEPLOY_KEY -p $DEPLOY_PORT" \
        --delete \
        kayarine-booking/ \
        "$DEPLOY_USER@$DEPLOY_HOST:$PLUGIN_PATH/"
    log_success "所有文件已上傳"
fi

# 遠程驗證
log_info "驗證遠程文件..."
ssh -i "$DEPLOY_KEY" -p "$DEPLOY_PORT" \
    "$DEPLOY_USER@$DEPLOY_HOST" \
    "php -l $PLUGIN_PATH/includes/class-kayarine-cart-manager.php && \
     echo '✓ PHP 語法正確'"

log_success "遠程驗證通過"

# 清除緩存（如果指定）
if [ "$CLEAR_CACHE" = true ]; then
    log_info "清除 WordPress 緩存..."
    ssh -i "$DEPLOY_KEY" -p "$DEPLOY_PORT" \
        "$DEPLOY_USER@$DEPLOY_HOST" \
        "cd $WP_PATH && \
         wp cache flush && \
         wp plugin deactivate kayarine-booking && \
         wp plugin activate kayarine-booking && \
         echo '✓ 緩存已清除，插件已重新啟用'"
    log_success "緩存已清除"
fi

# Slack 通知
if [ -n "$SLACK_WEBHOOK" ]; then
    log_info "發送 Slack 通知..."
    curl -X POST "$SLACK_WEBHOOK" \
        -H 'Content-Type: application/json' \
        -d "{
            \"text\": \"✅ Kayarine 部署成功\",
            \"attachments\": [{
                \"color\": \"good\",
                \"fields\": [
                    {\"title\": \"環境\", \"value\": \"$ENVIRONMENT\"},
                    {\"title\": \"時間\", \"value\": \"$(date '+%Y-%m-%d %H:%M:%S')\"},
                    {\"title\": \"備份\", \"value\": \"/tmp/$REMOTE_BACKUP\"}
                ]
            }]
        }" > /dev/null 2>&1
    log_success "Slack 通知已發送"
fi

# 完成
echo -e "\n${GREEN}╔════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ 部署完成${NC}"
echo -e "${GREEN}║   遠程備份: /tmp/$REMOTE_BACKUP${NC}"
echo -e "${GREEN}║   部署時間: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"

# 回滾指令提示
echo -e "\n${YELLOW}回滾命令（如需要）：${NC}"
echo "ssh -i $DEPLOY_KEY -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST \\"
echo "  \"cp -r /tmp/$REMOTE_BACKUP/* $PLUGIN_PATH/ && echo '✓ 已回滾'\""

```

---

## 📊 快速部署工作流

```
代碼修改（本地）
    ↓
運行 ./deploy.sh
    ↓
✅ 自動執行以下步驟：
   1. 創建本地備份
   2. 驗證 SSH 連接
   3. 創建遠程備份
   4. 上傳修改文件
   5. 遠程驗證（PHP 語法）
   6. 清除緩存（可選）
   7. Slack 通知（可選）
    ↓
✅ 部署完成（2-5 分鐘）
```

---

## ⚡ 使用示例

### 示例 1：標準部署

```bash
./deploy.sh
# 創建備份 → 驗證 → 上傳 → 驗證 → 完成
```

### 示例 2：快速部署（跳過本地備份）

```bash
./deploy.sh --fast
# 適合快速迭代開發
```

### 示例 3：只上傳特定文件

```bash
./deploy.sh --files class-kayarine-checkout-manager.php
# 只上傳修改的結帳文件，更快
```

### 示例 4：完整部署（包括緩存清除）

```bash
./deploy.sh production --clear-cache
# 部署到生產，並清除所有緩存
```

---

## 🔄 CI/CD 集成（可選）

### GitHub Actions 自動部署

在 `.github/workflows/deploy.yml` 中：

```yaml
name: Auto Deploy

on:
  push:
    branches: [ main ]
    paths:
      - 'kayarine-booking/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to Server
        env:
          DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}
        run: |
          mkdir -p ~/.ssh
          echo "$DEPLOY_KEY" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          
          ssh-keyscan -H ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts
          
          rsync -avz -e "ssh -i ~/.ssh/deploy_key" \
            kayarine-booking/ \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:${{ secrets.PLUGIN_PATH }}/
          
          ssh -i ~/.ssh/deploy_key \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} \
            "cd ${{ secrets.PLUGIN_PATH }} && \
             wp plugin deactivate kayarine-booking && \
             wp plugin activate kayarine-booking"
```

---

## 🛡️ 安全建議

### 1️⃣ SSH 金鑰管理

```bash
# 使用 SSH 金鑰（不用密碼）
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa_kayarine
ssh-copy-id -i ~/.ssh/id_rsa_kayarine.pub user@server

# 更新 deploy.conf
DEPLOY_KEY="/Users/henrylo/.ssh/id_rsa_kayarine"
```

### 2️⃣ 權限限制

```bash
# 在服務器上創建專用用戶
useradd -m -s /bin/bash wordpress
# 授予 WordPress 目錄權限
chown -R wordpress:wordpress /var/www/html/wordpress
```

### 3️⃣ 備份保留策略

```bash
# 自動刪除舊備份
find $BACKUP_DIR -name "kayarine_*" -type f \
  -mtime +$KEEP_BACKUPS -delete
```

---

## 📋 故障排查

### SSH 連接失敗

```bash
# 測試 SSH
ssh -i /path/to/key -p 22 user@host "echo OK"

# 檢查權限
chmod 600 ~/.ssh/id_rsa
chmod 700 ~/.ssh
```

### 上傳速度慢

```bash
# 使用 rsync 並進行壓縮（推薦）
# 或使用 --fast 模式跳過備份
./deploy.sh --fast
```

### 部署後出現錯誤

```bash
# 立即回滾（使用輸出中的回滾命令）
ssh -i $KEY user@host "cp -r /tmp/backup_dir/* /path/"

# 或通過 WordPress 後台禁用插件
wp plugin deactivate kayarine-booking
```

---

## ✅ 每次開發後的流程

```
1. 在本地修改代碼
2. 測試無誤後，運行：
   ./deploy.sh --fast
3. 訪問遠程 WordPress 驗證
4. 如有問題，運行回滾命令
5. 修改代碼，重複步驟 2-4
```

這樣可以大大加快開發迭代速度！ 🚀
