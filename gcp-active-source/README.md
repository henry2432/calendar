# GCP 活躍源代碼快照

此目錄包含從 GCP WordPress 實例中直接下載的源代碼快照，用於文檔整合和參考。

## 📁 內容

| 文件 | 用途 |
|------|------|
| `gcp_inventory.php` | 庫存系統類（592 行）- 從 `/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-inventory.php` 下載 |

## 🔍 下載時間

- **日期**：2026-01-31
- **來源**：GCP 實例 IP `104.199.144.122`
- **用戶**：`kayarine.server`

## 📖 如何使用

這些文件已被整合到以下文檔中：

1. **[`../INVENTORY_SYSTEM_INTEGRATION.md`](../INVENTORY_SYSTEM_INTEGRATION.md)**
   - 庫存系統完整邏輯分析
   - 儲存機制、快取策略、SQL 查詢詳解
   - 包含源代碼行號引用

2. **[`../SYSTEM_INTEGRATION_SUMMARY.md`](../SYSTEM_INTEGRATION_SUMMARY.md)**
   - 系統整合總結
   - 三個核心系統關係圖
   - 部署檢查清單

## ⚠️ 重要說明

- **不應修改**：這些是生產環境的源代碼快照
- **參考用途**：用於文檔整合和理解系統邏輯
- **版本追蹤**：實際部署應使用 `../kayarine-booking/includes/` 中的文件或 GCP VM 中的最新版本

## 🔄 更新流程

若需更新這些快照：

```bash
# SSH 連接到 GCP
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122

# 複製最新文件
scp -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key \
    kayarine.server@104.199.144.122:/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/includes/class-kayarine-inventory.php \
    ./gcp_inventory.php
```

## 🚀 相關部署指南

- **部署步驟**：見 [`../DEPLOYMENT_GUIDE_GCP_STANDARD.md`](../DEPLOYMENT_GUIDE_GCP_STANDARD.md)
- **系統分析**：見 [`../SYSTEM_INTEGRATION_SUMMARY.md`](../SYSTEM_INTEGRATION_SUMMARY.md)

---

**管理日期**：2026-01-31  
**維護者**：開發團隊
