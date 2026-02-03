# Kayarine 系統計劃索引 (Plan Index)

最後更新：2026-01-31  
已整理：刪除過時/重複檔案，保留 16 個活躍計劃

---

## 📋 計劃分類

### 🏗️ 架構與設計（5份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`plan.md`](plan.md) | 技術架構概覽：Webhooks、Flask、Google Sheets CRM | 📌 主計劃 | ⭐⭐⭐ |
| [`ARCHITECTURE_DECISION.md`](ARCHITECTURE_DECISION.md) | 登錄/註冊系統：3 方案對比（A/B/C混合方案） | 決策文檔 | ⭐⭐ |
| [`IMPLEMENTATION_PLAN_C.md`](IMPLEMENTATION_PLAN_C.md) | 方案 C 實施計劃：登錄+會員儀表板+改期 | 實施指南 | ⭐⭐⭐ |
| [`redesign_plan_product_based.md`](redesign_plan_product_based.md) | 預訂系統重新設計：產品導向架構 + Elementor 整合 | 重構計劃 | ⭐⭐ |
| [`user_flows_wp_centralized.md`](user_flows_wp_centralized.md) | 用戶流程設計：WordPress 中心化管理（取代 Excel-First） | 新架構 | ⭐⭐⭐ |

---

### 💎 會員與行銷系統（3份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`membership_system.md`](membership_system.md) | 會員積分與行銷系統 v3（四級會員、點數、儲值金） | 詳細規格 | ⭐⭐⭐ |
| [`coupon_analytics.md`](coupon_analytics.md) | 優惠券管理升級：集中日誌、使用追蹤、分析 | 分析升級 | ⭐⭐ |
| [`BRANDING_SYSTEM_REFINEMENT.md`](BRANDING_SYSTEM_REFINEMENT.md) | 品牌配色系統實施：紫+橙色方案、UI 組件規範 | 設計規範 | ⭐⭐ |

---

### 📦 功能與庫存（3份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`inventory_plan.md`](inventory_plan.md) | 庫存管理實施：按日期限制、Google Sheets + Flask API | 實施計劃 | ⭐⭐⭐ |
| [`revision_plan_locker_cx.md`](revision_plan_locker_cx.md) | 預訂系統優化：移除置物櫃、即時價格、粘性頁腳、智能驗證 | 優化計劃 | ⭐⭐⭐ |
| [`ui_implementation_plan.md`](ui_implementation_plan.md) | UI 實施計劃：日曆 + 統一預訂介面標準化 | CSS 標準化 | ⭐⭐ |

---

### 🔐 安全與防護（3份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`ddos_security_plan.md`](ddos_security_plan.md) | DDoS 防護三層方案：Cloudflare + Flask-Limiter + 監控 | 完整方案 | ⭐⭐⭐ |
| [`CLOUDFLARE_FREE_SETUP_GUIDE.md`](CLOUDFLARE_FREE_SETUP_GUIDE.md) | Cloudflare 免費版設置：DNS、SSL、頁面規則、DDoS 保護 | 免費版指南 | ⭐⭐⭐ |
| [`PHASE1_DEPLOYMENT_GUIDE.md`](PHASE1_DEPLOYMENT_GUIDE.md) | Phase 1 部署指南：本地驗證 → 生產部署 → 監控 | 部署步驟 | ⭐⭐⭐ |

---

### 💬 WhatsApp 與 CRM（2份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`whatsapp_migration.md`](whatsapp_migration.md) | WhatsApp Cloud API 遷移：替代 Wati 的成本降低方案 | 遷移計劃 | ⭐⭐ |
| [`whatsapp_crm_plan.md`](whatsapp_crm_plan.md) | 私有 WhatsApp CRM 設計：自建 Inbox（替代 Wati） | CRM 設計 | ⭐⭐ |

---

### 🗂️ 其他（1份）

| 檔案 | 描述 | 狀態 | 優先級 |
|------|------|------|--------|
| [`WOOCOMMERCE_UNIFIED_ACCOUNT_ARCHITECTURE.md`](WOOCOMMERCE_UNIFIED_ACCOUNT_ARCHITECTURE.md) | WooCommerce 統一帳戶架構 | 架構文檔 | ⭐ |

---

## 🎯 推薦實施順序

### Phase 1：基礎安全與部署（立即 1-2 週）
1. ✅ [`ddos_security_plan.md`](ddos_security_plan.md) - 三層防護設計
2. ✅ [`CLOUDFLARE_FREE_SETUP_GUIDE.md`](CLOUDFLARE_FREE_SETUP_GUIDE.md) - Cloudflare 配置
3. ✅ [`PHASE1_DEPLOYMENT_GUIDE.md`](PHASE1_DEPLOYMENT_GUIDE.md) - 部署執行

### Phase 2：預訂系統優化（2-3 週）
1. ✅ [`revision_plan_locker_cx.md`](revision_plan_locker_cx.md) - 移除置物櫃 + UX 改進
2. ✅ [`inventory_plan.md`](inventory_plan.md) - 庫存管理
3. ✅ [`ui_implementation_plan.md`](ui_implementation_plan.md) - UI 標準化

### Phase 3：會員與行銷系統（3-4 週）
1. ✅ [`membership_system.md`](membership_system.md) - 會員積分系統
2. ✅ [`coupon_analytics.md`](coupon_analytics.md) - 優惠券分析
3. ✅ [`BRANDING_SYSTEM_REFINEMENT.md`](BRANDING_SYSTEM_REFINEMENT.md) - 品牌設計

### Phase 4：登錄與會員管理（2-3 週）
1. ✅ [`ARCHITECTURE_DECISION.md`](ARCHITECTURE_DECISION.md) - 決策確認
2. ✅ [`IMPLEMENTATION_PLAN_C.md`](IMPLEMENTATION_PLAN_C.md) - 實施

### Phase 5：長期架構升級（4-6 週）
1. ✅ [`user_flows_wp_centralized.md`](user_flows_wp_centralized.md) - WP 中心化
2. ✅ [`redesign_plan_product_based.md`](redesign_plan_product_based.md) - 產品導向設計
3. ✅ [`whatsapp_migration.md`](whatsapp_migration.md) + [`whatsapp_crm_plan.md`](whatsapp_crm_plan.md) - CRM 遷移

---

## 📊 檔案統計

- **總計**：16 個活躍計劃
- **已刪除**：2 個（過時 + 重複）
  - ❌ `ui_ux_improvements.md`（已完成 ✅）
  - ❌ `CLOUDFLARE_SETUP_GUIDE.md`（重複，保留免費版）
- **優先級分布**：
  - ⭐⭐⭐ 高：10 個
  - ⭐⭐ 中：5 個
  - ⭐ 低：1 個

---

## 🔄 相關文件連結

- **主文檔**：[README.md](../README.md)
- **開發日誌**：[DEVELOPMENT_LOG.md](../DEVELOPMENT_LOG.md)
- **部署指南**：[DEPLOYMENT_GUIDE_GCP_STANDARD.md](../DEPLOYMENT_GUIDE_GCP_STANDARD.md)
- **系統整合**：[SYSTEM_INTEGRATION_SUMMARY.md](../SYSTEM_INTEGRATION_SUMMARY.md)

---

## ✅ 整理檢查清單

- [x] 審查所有 18 份 plan 文件
- [x] 識別過時/重複文件
- [x] 刪除已完成文件（ui_ux_improvements.md）
- [x] 刪除重複文件（CLOUDFLARE_SETUP_GUIDE.md）
- [x] 檢查未讀文件內容
- [x] 建立統一索引
- [x] 按邏輯分類
- [x] 推薦實施順序

**狀態**：✅ 整理完成
