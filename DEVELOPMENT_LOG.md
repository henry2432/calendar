# Kayarine 系統開發日誌

整合開發過程中的診斷、測試和調試步驟記錄

---

## 最近開發（2026年1月 - 積分和會員系統改進）

### 問題識別

1. **會員中心無法顯示所有訂單狀態**
   - 症狀：on-hold、processing 訂單不顯示
   - 根因：參數錯誤（customer_id → customer）+ 缺少狀態過濾
   - 修復：`class-kayarine-member-dashboard.php`

2. **結帳時積分無法自動應用**
   - 症狀：需要「取消後重新勾選」才能應用
   - 根因：AJAX Nonce 驗證失敗 + Session 不可靠
   - 修復：改用表單隱藏欄位 + 訂單元數據

3. **積分未被正確扣除和回饋**
   - 症狀：使用積分後無法扣除，完成訂單無回饋
   - 根因：單一 Hook 觸發不足 + 缺乏狀態追蹤
   - 修復：多重 Hook 觸發 + Order Metadata 追蹤

### 解決方案

**改進的積分系統**（`class-kayarine-improved-checkout.php`）
- 6 階段處理：請求 → 記錄 → 添加費用 → 扣除 → 回饋 → 退款
- 架構轉變：Session + AJAX → Order Metadata + Form Fields
- 可靠性提升：多重 Hook + 狀態機制

### 部署

- 標準部署指南：`DEPLOYMENT_GUIDE_GCP_STANDARD.md`
- 使用 gcloud CLI 進行部署
- 預計部署時間：10-15 分鐘

---

## 歷史診斷記錄

### GCP 基礎設施診斷

- WordPress 路徑：`/opt/bitnami/wordpress`
- 插件路徑：`/opt/bitnami/wordpress/wp-content/plugins/kayarine-booking`
- Web 伺服器用戶：`www-data`

### SSH 連接問題排查

- 嘗試方式：gcloud compute ssh, 傳統 SSH, WP-CLI
- 解決：使用 gcloud CLI 進行遠端操作
- 認證：gcloud 預配置認證

### 緩存策略

- WordPress 快取：`wp cache flush`
- 瀏覽器快取：Ctrl+Shift+Delete
- CDN 快取（Cloudflare）：Purge Cache

---

## 測試步驟參考

### 會員中心驗證

```sql
-- 檢查訂單是否存在
SELECT ID, post_status FROM wp_posts 
WHERE ID = <ORDER_ID>;

-- 檢查用戶訂單
SELECT ID, post_status FROM wp_posts 
WHERE post_type = 'shop_order' 
AND post_author = <USER_ID>;
```

### 積分系統驗證

```sql
-- 查詢積分日誌
SELECT * FROM wp_kayarine_points_log 
WHERE user_id = <USER_ID> 
ORDER BY date_created DESC;

-- 查詢訂單元數據
SELECT * FROM wp_postmeta 
WHERE post_id = <ORDER_ID> 
AND meta_key LIKE '_kayarine%';
```

### 日誌監控

```bash
tail -100 /opt/bitnami/wordpress/wp-content/debug.log | grep "Kayarine"
```

---

## 當前系統狀態

| 組件 | 版本 | 狀態 |
|------|------|------|
| Kayarine Booking | v1.4.15 | ✅ 改進完成 |
| 會員中心 | 修復版 | ✅ 完成 |
| 積分系統 | 改進版 | ✅ 完成 |
| 庫存系統 | - | 📋 見 GCP 配置 |

---

## 文檔參考

| 文檔 | 用途 |
|------|------|
| `DEPLOYMENT_GUIDE_GCP_STANDARD.md` | 標準部署流程 |
| `CLOUDFLARE_DNS_REFERENCE.md` | DNS 配置參考 |
| `SYSTEM_WORKFLOW_ANALYSIS.md` | 系統 workflow 分析 |
| `DIAGNOSTIC_WORKFLOW.md` | 詳細診斷步驟 |
| `IMPROVED_POINTS_SYSTEM_DESIGN.md` | 設計細節 |

---

## 常見問題快速查詢

### Q: 會員中心看不到訂單
A: 檢查 debug.log 中 `[Kayarine Dashboard]` 日誌，確認訂單狀態是否在查詢範圍內

### Q: 積分未被應用
A: 檢查隱藏欄位是否正確設置，驗證表單提交

### Q: 積分未被扣除
A: 查詢 `wp_kayarine_points_log` 表，檢查訂單是否進入 processing/completed 狀態

### Q: 需要回滾
A: 恢復備份文件並重新啟用插件

---

最後更新：2026-01-31
