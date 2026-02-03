# Kayarine 項目工作流指南

本文檔介紹項目結構、文件分類和所有模式的標準工作流。

---

## 📁 項目結構與分類

### 🎯 核心文檔（根目錄 - 優先級最高）

| 文檔 | 用途 | 權限 |
|------|------|------|
| **`DEPLOYMENT_GUIDE_GCP_STANDARD.md`** | GCP 標準部署指南 **[部署必讀]** | 🔒 唯讀 |
| **`SYSTEM_INTEGRATION_SUMMARY.md`** | 系統架構和整合總結 | ✏️ 更新 |
| **`INVENTORY_SYSTEM_INTEGRATION.md`** | 庫存系統完整邏輯 | ✏️ 更新 |
| **`CLOUDFLARE_DNS_REFERENCE.md`** | DNS 配置參考 | 📋 參考 |
| **`DEVELOPMENT_LOG.md`** | 開發日誌 | ✏️ 記錄 |

---

## 📂 分類目錄說明

### 1️⃣ `backend/` - Python 後端代碼

**用途**：Flask 應用、數據庫管理、外部 API 整合

**主要文件**：
- `app.py` - Flask 主應用
- `db_manager.py` - 數據庫操作
- `sheet_manager.py` - Google Sheets 整合
- `wc_handler.py` - WooCommerce API 整合
- `whatsapp_handler.py` - WhatsApp API 整合

**新增檔案**：後端代碼、Python 模塊都應存放在此

---

### 2️⃣ `scripts/` - 執行腳本

**用途**：一次性腳本、測試、部署輔助、數據導入/導出

**主要文件**：
- `DEPLOYMENT_SCRIPT_BITNAMI.sh` - Bitnami 部署腳本
- `run_march_inventory_test.sh` - 庫存測試腳本
- `mock_test.py` - 模擬測試
- `generate_import_csv.py` - CSV 導入生成

**⚠️ 重要規則**：
- 腳本執行完後 **必須刪除**（`rm script_name`）
- 不要讓臨時腳本留在代碼庫中
- 可重複使用的腳本應移至 `backend/`

---

### 3️⃣ `config/` - 配置文件

**用途**：應用配置、部署配置、憑證

**主要文件**：
- `deploy.conf` - 部署配置
- `deploy.conf.example` - 配置示例
- `credentials.json` - API 憑證
- `wordpress-https-vhost.conf` - Apache 虛擬主機配置

**⚠️ 安全規則**：
- **不要提交憑證**到版本控制
- 使用 `.example` 模板管理敏感配置
- 本地副本保持私密

---

### 4️⃣ `kayarine-booking/` - WordPress 插件源代碼

**用途**：Kayarine Booking 插件的主要開發代碼

**結構**：
```
kayarine-booking/
├── includes/
│   ├── class-kayarine-inventory.php         # 庫存系統
│   ├── class-kayarine-improved-checkout.php # 積分系統
│   ├── class-kayarine-member-dashboard.php  # 會員中心
│   └── ...
├── assets/
│   ├── css/
│   └── js/
└── kayarine-booking.php                     # 插件主文件
```

**開發規則**：
- 修改此目錄中的文件後，參考 [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) 執行部署
- 新增功能類應遵循命名規範：`class-kayarine-{feature}.php`

---

### 5️⃣ `gcp-active-source/` - GCP 源代碼快照

**用途**：從 GCP WordPress 實例直接下載的源代碼快照

**內容**：
- `gcp_inventory.php` - GCP 上的庫存系統類

**使用規則**：
- 僅用於文檔參考和對比
- 不應直接修改
- 需要更新時讀取 GCP 最新源代碼

**更新流程**：
```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122
scp -i /path/to/key kayarine.server@104.199.144.122:/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-inventory.php ./gcp-active-source/
```

---

### 6️⃣ `archives/` - 版本檔案

**用途**：歷史版本、備份、已完成的壓縮檔

**內容**：
- 最新 3 個版本（`.tar.gz` 壓縮檔）
- 兼容性測試版本

**規則**：
- 保留最近 3 個版本用於回滾
- 舊版本應移至 `package/` 目錄

---

### 7️⃣ `data/` - 日誌和數據

**用途**：測試日誌、調試日誌、臨時數據

**主要文件**：
- `debug_sql_test.log` - SQL 調試日誌
- `flask_log.txt` - Flask 應用日誌
- `chat_history.db` - 聊天歷史數據
- `coupon樣本.csv` - 優惠券數據示例

**規則**：
- 日誌文件不應提交版本控制
- 使用 `.gitignore` 排除此目錄

---

### 8️⃣ `reference/` - 參考文檔和查詢

**用途**：SQL 查詢、設計文檔、筆記

**主要文件**：
- `INVENTORY_DEBUG_QUERY.sql` - 庫存調試 SQL
- `rough draft/` - 設計草稿

**規則**：
- 可包含未定案的設計和查詢
- 供開發參考，不用於部署

---

### 9️⃣ 其他目錄

| 目錄 | 內容 |
|------|------|
| `package/` | 18 個歷史壓縮檔版本 |
| `plans/` | 項目計劃文檔 |
| `screenshots/` | UI 截圖和演示 |
| `UI templates/` | UI 設計模板 |
| `roo/` | 開發規則文檔 |

---

## 🚀 工作流指南

### 情景 1: 部署到 GCP VM

1. **必讀文檔**（按順序）：
   - 📖 [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) **[唯讀]**
   - 📖 [`SYSTEM_INTEGRATION_SUMMARY.md`](SYSTEM_INTEGRATION_SUMMARY.md)

2. **部署步驟**：
   - 按照 `DEPLOYMENT_GUIDE_GCP_STANDARD.md` 的 5 個步驟執行
   - SSH 連接命令已包含正確的用戶和密鑰路徑

3. **完成後**：
   - 執行驗證步驟
   - 更新 `DEVELOPMENT_LOG.md` 記錄部署時間和版本

---

### 情景 2: 修改或新增功能

1. **修改現有功能**：
   - 修改 `kayarine-booking/includes/` 中的相應類文件
   - 更新相關 markdown 文檔（優先更新現有，不新增）
   - 參考 `DEPLOYMENT_GUIDE_GCP_STANDARD.md` 部署更改

2. **新增全新功能**：
   - 在 `kayarine-booking/includes/` 創建新類：`class-kayarine-{feature}.php`
   - 在 `SYSTEM_INTEGRATION_SUMMARY.md` 添加新功能說明
   - 創建 **全新** markdown 文檔（僅當功能足夠獨立）

3. **文檔更新規則**：
   ```
   優先級：
   1. 更新相關現有 md（80% 情況）
   2. 在現有 md 添加新章節（15% 情況）
   3. 創建全新 md（5% 情況 - 只有全新、獨立的內容）
   ```

---

### 情景 3: 運行測試或調試腳本

1. **創建腳本**：
   ```bash
   # 將臨時腳本放在 scripts/ 目錄
   scripts/my_test_script.sh
   ```

2. **執行腳本**：
   ```bash
   bash scripts/my_test_script.sh
   ```

3. **⚠️ 完成後必須刪除**：
   ```bash
   rm scripts/my_test_script.sh
   ```

   **原因**：保持代碼庫整潔，避免累積廢棄腳本

---

### 情景 4: 新增配置或憑證

1. **配置文件**：
   - 存放在 `config/` 目錄
   - 使用 `.example` 模板
   - 實際憑證加入 `.gitignore`

   ```bash
   # 例如
   config/deploy.conf.example          # 提交版本控制
   config/deploy.conf                  # 本地副本，不提交
   ```

2. **更新文檔**：
   - 在 `SYSTEM_INTEGRATION_SUMMARY.md` 的「部署檢查清單」添加新配置項

---

### 情景 5: 添加後端功能

1. **Python 模塊**：
   - 存放在 `backend/` 目錄
   - 遵循 Python 命名規範：`snake_case.py`
   - 類名使用 `PascalCase`

2. **更新依賴**：
   ```bash
   # 編輯 backend/requirements.txt
   pip install new_package
   pip freeze >> backend/requirements.txt
   ```

3. **文檔**：
   - 更新 `DEVELOPMENT_LOG.md` 記錄新依賴
   - 在相關功能 markdown 添加說明

---

## 📝 Markdown 文檔管理

### 核心規則

| 操作 | 規則 |
|------|------|
| **修改現有功能** | ✏️ 更新相關 md，優先不新增 |
| **新增功能** | 📋 先檢查是否應更新現有 md |
| **全新獨立功能** | 📄 才創建新 md（稀有情況） |
| **舊文檔棄用** | 🗑️ 移至 `reference/` 或 `package/` |

### 文檔優先級

```
🔴 最高：DEPLOYMENT_GUIDE_GCP_STANDARD.md [唯讀]
🟠 高：  SYSTEM_INTEGRATION_SUMMARY.md
🟡 中：  INVENTORY_SYSTEM_INTEGRATION.md
🟢 低：  其他功能 md
🔵 參考：DEVELOPMENT_LOG.md
```

---

## ✅ 最佳實踐

### ✨ 整潔原則

- [ ] 根目錄只保留核心 md 文檔
- [ ] 所有代碼文件按分類放入相應目錄
- [ ] 臨時腳本執行後立即刪除
- [ ] 日誌文件存放在 `data/` 目錄

### 📚 文檔原則

- [ ] 讀取相關 md 後再修改
- [ ] 優先更新現有 md，而非創建新 md
- [ ] 部署前更新 `DEVELOPMENT_LOG.md`
- [ ] 大改動前檢查 `SYSTEM_INTEGRATION_SUMMARY.md`

### 🔒 安全原則

- [ ] 憑證不提交版本控制
- [ ] 使用 `.example` 模板
- [ ] 敏感文件存放 `config/` 目錄
- [ ] 部署時遵循 `DEPLOYMENT_GUIDE_GCP_STANDARD.md`

### 🧹 清理原則

- [ ] 每月檢查是否有遺留的臨時文件
- [ ] 舊版本壓縮檔移至 `archives/` 或 `package/`
- [ ] 調試完成的日誌保留，但不提交版本控制

---

## 🎓 快速參考

### 我想要... | 應該...

| 我想要 | 應該 |
|-------|------|
| 部署到 GCP | 📖 讀取 `DEPLOYMENT_GUIDE_GCP_STANDARD.md` |
| 了解系統架構 | 📖 讀取 `SYSTEM_INTEGRATION_SUMMARY.md` |
| 理解庫存邏輯 | 📖 讀取 `INVENTORY_SYSTEM_INTEGRATION.md` |
| 修改積分系統 | ✏️ 編輯 `kayarine-booking/includes/class-kayarine-improved-checkout.php` 後更新相關 md |
| 新增 Python 功能 | 📁 在 `backend/` 創建新文件 |
| 運行測試 | 📁 在 `scripts/` 創建臨時文件，完成後刪除 |
| 查詢 SQL | 📁 查看 `reference/INVENTORY_DEBUG_QUERY.sql` |
| 記錄更改 | 📝 更新 `DEVELOPMENT_LOG.md` |

---

## 📞 支援資源

**部署相關**：
- 🔒 [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 標準部署指南

**系統相關**：
- 📖 [`SYSTEM_INTEGRATION_SUMMARY.md`](SYSTEM_INTEGRATION_SUMMARY.md) - 系統總結
- 📖 [`INVENTORY_SYSTEM_INTEGRATION.md`](INVENTORY_SYSTEM_INTEGRATION.md) - 庫存詳解

**開發參考**：
- 📁 `gcp-active-source/` - GCP 源代碼快照
- 📁 `reference/` - SQL 查詢、設計文檔
- 📁 `kayarine-booking/` - 源代碼目錄

---

**最後更新**：2026-01-31  
**版本**：v1.0  
**使用者**：開發團隊
