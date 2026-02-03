# Kayarine Booking - GCP gcloud 部署指南

> **推薦方式**: 使用 GCP `gcloud` CLI 進行部署，避免 SSH 金鑰配置問題

---

## 快速開始

### 必要條件
- 安裝 [Google Cloud CLI (gcloud)](https://cloud.google.com/sdk/docs/install)
- 已配置 GCP 認證: `gcloud auth login`
- GCP 實例已運行

### 一鍵部署

```bash
cd kayarine-booking
chmod +x deploy.sh
./deploy.sh production [--clear-cache]
```

---

## 詳細說明

### 1. 配置設置 (`../deploy.conf`)

編輯根目錄的 `deploy.conf`:

```bash
# GCP 實例名稱
GCP_INSTANCE="wordpress-2025-vm"

# GCP 區域
GCP_ZONE="asia-east1-b"

# WordPress 路徑（遠程服務器上）
WP_PATH="/opt/bitnami/wordpress"
PLUGIN_PATH="/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking"

# 環境
ENVIRONMENT="production"
```

### 2. 部署命令

**基本部署：**
```bash
./deploy.sh production
```

**部署並清除緩存：**
```bash
./deploy.sh production --clear-cache
```

### 3. 部署流程

1. ✅ 驗證 gcloud 連接
2. ✅ 打包插件（排除 macOS 元數據、`.git`、`.tar.gz`）
3. ✅ 上傳至服務器 `/tmp`
4. ✅ 備份當前版本到 `${PLUGIN_PATH}-backup`
5. ✅ 提取新版本
6. ✅ 修復權限（`daemon:daemon`）
7. ✅ 驗證版本信息
8. ✅ 可選：清除 WordPress 緩存

### 4. 故障排查

**問題：gcloud 命令未找到**
```bash
# 安裝 gcloud
curl https://sdk.cloud.google.com | bash
exec -l $SHELL
gcloud auth login
```

**問題：Permission denied**
- 確保 gcloud 使用的用戶有 SSH 權限
- 檢查實例的 IAM 設置

**問題：實例不存在**
```bash
gcloud compute instances list --zone asia-east1-b
```

**查看部署日誌：**
```bash
gcloud compute ssh wordpress-2025-vm --zone asia-east1-b --command="tail -50 /var/log/syslog"
```

---

## 優勢

| 方式 | SSH | gcloud |
|------|-----|--------|
| 金鑰配置 | ❌ 複雜 | ✅ 自動 |
| 連接穩定性 | ⚠️ 需排查 | ✅ 通過 GCP 認證 |
| 部署速度 | 普通 | ✅ 快速 |
| 錯誤率 | ⚠️ 高 | ✅ 低 |
| 推薦度 | ⚠️ 不推薦 | ✅✅ 推薦 |

---

## 完整工作流程

```bash
# 1. 修改代碼
vim kayarine-booking/includes/class-kayarine-booking.php

# 2. 部署
cd kayarine-booking
./deploy.sh production --clear-cache

# 3. 驗證
gcloud compute ssh wordpress-2025-vm --zone asia-east1-b \
  --command="grep 'Version:' /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/kayarine-booking.php"

# 4. 檢查日誌
gcloud compute ssh wordpress-2025-vm --zone asia-east1-b \
  --command="tail -30 /opt/bitnami/wordpress/wp-content/debug.log"
```

---

## 環境變數（可選）

如果部署指令需要，可設置環境變數：

```bash
export GCP_PROJECT="your-project-id"
export GCP_INSTANCE="wordpress-2025-vm"
export GCP_ZONE="asia-east1-b"
```

---

## 回滾

如果部署出現問題，恢復之前的版本：

```bash
gcloud compute ssh wordpress-2025-vm --zone asia-east1-b --command="
  sudo rm -rf /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking
  sudo mv /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking-backup /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking
  echo 'Rollback complete'
"
```
