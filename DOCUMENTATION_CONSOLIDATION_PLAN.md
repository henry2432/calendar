# 📚 Kayarine 項目文檔整合計劃

**創建日期**: 2026-02-09  
**目的**: 整合並優化兩個項目的markdown文檔，消除冗餘，提升可維護性

---

## 📊 文檔分析總覽

### Calendar 項目 (WordPress 後端)
- **總文檔數**: 50個 markdown 文件
- **核心文檔**: 6個
- **分類文檔**: 44個

### Kayarine-nextjs-frontend 項目 (Next.js 前端)
- **總文檔數**: 26個 markdown 文件
- **核心文檔**: 4個
- **分類文檔**: 22個

---

## 🔍 重複文檔識別

### 1️⃣ 完全重複的文檔 (需合併)

| 文檔名稱 | Calendar | Frontend | 處理策略 |
|---------|----------|----------|---------|
| `DEPLOYMENT_GUIDE_GCP_STANDARD.md` | ✅ | ✅ | 合併為統一部署指南 |
| `DEVELOPMENT_LOG.md` | ✅ | ✅ | 合併為完整開發日誌 |
| `GIT_WORKFLOW.md` | ✅ | ✅ | 保留 Calendar 版本，Frontend 刪除 |
| `README.md` | ✅ | ✅ | 分別保留（內容不同）|

### 2️⃣ 相似主題的文檔 (需整合)

#### 🔐 認證系統相關
- **Calendar**: 
  - `AUTHENTICATION_SYSTEM_SETUP.md`
  - `JWT_AUTH_SETUP_GUIDE.md`
  - `MEMBER_CENTER_AUTHENTICATION_ROADMAP.md`
- **Frontend**: 
  - `APPLE_SIGN_IN_STATUS.md`
  - `SOCIAL_AUTH_SETUP_GUIDE.md`
  - `README_APPLE_SIGN_IN.md`
- **整合策略**: 創建 `docs/authentication/` 目錄，按技術分類

#### 🚀 部署相關
- **Calendar**: 
  - `DEPLOYMENT_GUIDE_GCP_STANDARD.md` 🔒
  - `DEPLOYMENT_STATUS_v2.5.1.md`
  - `SERVER_GIT_SETUP.md`
- **Frontend**: 
  - `DEPLOYMENT_GUIDE_GCP_STANDARD.md`
  - `DEPLOYMENT_QUICK_START.md`
  - `DEPLOYMENT_TROUBLESHOOTING.md`
  - `DEPLOYMENT_TROUBLESHOOTING_GMAIL.md`
  - `GCP_CONSOLE_DEPLOYMENT_GUIDE.md`
  - `VM_DEPLOYMENT_GUIDE.md`
  - `DEPLOY_NOW.md`
- **整合策略**: 創建 `docs/deployment/` 目錄

#### ⚡ 性能優化相關
- **Calendar**: 
  - `PERFORMANCE_DIAGNOSTIC_REPORT.md`
  - `PERFORMANCE_OPTIMIZATION_ACTION_PLAN.md`
  - `PERFORMANCE_ROOT_CAUSE_ANALYSIS.md`
  - `PERFORMANCE_TEST_INSTRUCTIONS.md`
  - `DEEP_PERFORMANCE_DIAGNOSIS.md`
  - `FINAL_PERFORMANCE_SUMMARY_AND_ACTION_PLAN.md`
  - `PHP_FPM_OPTIMIZATION_PLAN.md`
  - `CACHING_PLUGIN_SETUP_GUIDE.md`
- **Frontend**: 
  - `PERFORMANCE_OPTIMIZATION_ANALYSIS.md`
- **整合策略**: 創建 `docs/performance/` 目錄，合併為統一性能優化指南

#### ☁️ Cloudflare 相關
- **Calendar**: 
  - `CLOUDFLARE_DNS_REFERENCE.md` ⭐ 保留
  - `CLOUDFLARE_OPTIMIZATION_GUIDE.md`
  - `CLOUDFLARE_ROCKET_LOADER_DIAGNOSIS.md`
- **整合策略**: 創建 `docs/cloudflare/` 目錄

#### 🔧 WordPress 優化相關
- **Calendar**: 
  - `WORDPRESS_BACKEND_CLEANUP.md`
  - `WORDPRESS_AND_DATABASE_OPTIMIZATION_EXPLAINED.md`
  - `THEME_AND_DATABASE_OPTIMIZATION.md`
  - `PLUGIN_CLEANUP_AND_OPTIMIZATION.md`
  - `PLUGIN_DISABLED_ANALYSIS_ROOT_CAUSE.md`
  - `QUERY_MONITOR_ANALYSIS_RESULTS.md`
  - `QUERY_MONITOR_DEEP_DIAGNOSIS.md`
- **整合策略**: 創建 `docs/wordpress-optimization/` 目錄

#### 📝 Elementor 相關
- **Calendar**: 
  - `ELEMENTOR_CACHE_DIAGNOSIS_RESULT.md`
  - `ELEMENTOR_DATABASE_DIAGNOSIS.md`
  - `ELEMENTOR_MIGRATION_PLAN.md`
  - `ELEMENTOR_SETTINGS_OPTIMIZATION.md`
- **整合策略**: 創建 `docs/elementor/` 目錄或歸檔

#### 🌟 Google Reviews 相關
- **Calendar**: 
  - `GOOGLE_REVIEWS_DEPLOYMENT_LOG.md`
  - `GOOGLE_REVIEWS_IMPLEMENTATION.md`
  - `NINJA_GOOGLE_REVIEW_ANALYSIS.md`
  - `NINJA_GOOGLE_REVIEW_REMOVAL_GUIDE.md`
- **Frontend**: 
  - `GOOGLE_REVIEWS_SETUP.md`
- **整合策略**: 創建 `docs/integrations/google-reviews/` 目錄

#### 📮 Email 相關
- **Frontend**: 
  - `GMAIL_DEPLOYMENT_CHECKLIST.md`
  - `GMAIL_SMTP_SETUP.md`
  - `DEPLOYMENT_TROUBLESHOOTING_GMAIL.md`
- **整合策略**: 創建 `docs/integrations/email/` 目錄

#### 📦 其他整合和功能
- **Calendar**: 
  - `INVENTORY_SYSTEM_INTEGRATION.md` ⭐ 核心
  - `SYSTEM_INTEGRATION_SUMMARY.md` ⭐ 核心
  - `ORDER_CREATION_DIAGNOSTIC_GUIDE.md`
  - `P0-3_P0-4_API_TEST_GUIDE.md`
- **Frontend**: 
  - `FIGMA_TO_DEPLOYMENT_GUIDE.md`
  - `PHASE2_COMPLETION_SUMMARY.md`
  - `PHASE2_IMPLEMENTATION_GUIDE.md`
  - `GUEST_CHECKOUT_CHECKLIST.md`

---

## 🎯 整合策略

### Phase 1: 創建新目錄結構 ✅

在 **Calendar 項目**創建以下目錄結構（作為主項目）:

```
calendar/
├── README.md ⭐ 保留並更新
├── DEPLOYMENT_GUIDE_GCP_STANDARD.md 🔒 保留（唯讀）
├── DEVELOPMENT_LOG.md ⭐ 合併兩個項目
├── SYSTEM_INTEGRATION_SUMMARY.md ⭐ 保留
├── INVENTORY_SYSTEM_INTEGRATION.md ⭐ 保留
├── CLOUDFLARE_DNS_REFERENCE.md ⭐ 保留
├── GIT_WORKFLOW.md ⭐ 保留
│
├── docs/
│   ├── authentication/           # 認證系統文檔
│   │   ├── README.md            # 認證系統總覽
│   │   ├── jwt-auth.md
│   │   ├── google-oauth.md
│   │   ├── apple-signin.md
│   │   └── member-center.md
│   │
│   ├── deployment/               # 部署相關文檔
│   │   ├── README.md            # 部署總覽
│   │   ├── vm-deployment.md
│   │   ├── cloud-run.md
│   │   ├── troubleshooting.md
│   │   └── quick-start.md
│   │
│   ├── performance/              # 性能優化文檔
│   │   ├── README.md
│   │   ├── backend-optimization.md
│   │   ├── frontend-optimization.md
│   │   ├── caching-strategy.md
│   │   └── php-fpm-tuning.md
│   │
│   ├── integrations/             # 第三方整合
│   │   ├── google-reviews/
│   │   │   ├── README.md
│   │   │   ├── setup.md
│   │   │   └── troubleshooting.md
│   │   └── email/
│   │       ├── README.md
│   │       └── gmail-smtp.md
│   │
│   ├── cloudflare/               # Cloudflare 配置
│   │   ├── README.md
│   │   └── optimization.md
│   │
│   └── wordpress/                # WordPress 優化
│       ├── README.md
│       ├── plugin-optimization.md
│       ├── database-optimization.md
│       └── theme-optimization.md
│
└── archives/                     # 歷史文檔歸檔
    ├── 2024-performance/
    ├── 2025-elementor/
    └── deprecated/
```

### Phase 2: 合併核心文檔 ✅

#### 2.1 合併 DEVELOPMENT_LOG.md

**目標**: 創建統一的開發日誌，包含前後端所有開發記錄

**策略**:
- 以 **Calendar** 的 `DEVELOPMENT_LOG.md` 為基礎（內容更詳細）
- 從 **Frontend** 的 `DEVELOPMENT_LOG.md` 提取前端獨有的開發記錄
- 按時間倒序整合（最新在上）
- 保留所有版本號和時間戳
- 添加前後端標籤區分

#### 2.2 更新 DEPLOYMENT_GUIDE_GCP_STANDARD.md

**目標**: 創建統一的GCP部署指南

**策略**:
- 以 **Calendar** 的版本為主（因為標記為🔒唯讀）
- 添加 Frontend 的 Next.js 部署步驟
- 整合 VM 和 Cloud Run 兩種部署方式
- 保持文檔結構清晰

#### 2.3 更新 README.md

**Calendar README**:
- 保留完整的項目結構說明
- 添加新的 `docs/` 目錄說明
- 更新文檔索引

**Frontend README**:
- 保留 Next.js 基本說明
- 添加指向 Calendar 項目的文檔鏈接
- 簡化為快速開始指南

### Phase 3: 整合主題文檔 ✅

#### 3.1 認證系統文檔
創建 `docs/authentication/README.md`:
- 整合所有認證相關文檔
- 提供統一的認證架構圖
- 包含 JWT、Google OAuth、Apple Sign In 指南

#### 3.2 部署文檔
創建 `docs/deployment/README.md`:
- 合併所有部署相關文檔
- 提供決策樹（選擇部署方式）
- 包含故障排除指南

#### 3.3 性能優化文檔
創建 `docs/performance/README.md`:
- 整合前後端性能優化策略
- 提供性能測試工具和方法
- 包含優化檢查清單

#### 3.4 整合服務文檔
創建 `docs/integrations/`:
- Google Reviews 整合指南
- Gmail SMTP 配置
- 其他第三方服務整合

### Phase 4: 歸檔歷史文檔 ✅

移動到 `archives/`:
- 所有 Elementor 相關文檔
- 過時的性能診斷報告
- 已解決的問題診斷文檔
- 臨時狀態文檔

### Phase 5: 清理冗餘文檔 ✅

**刪除以下類型的文檔**:
- 重複的部署指南（保留統一版本）
- 臨時狀態文檔（如 DEPLOY_NOW.md, QUICK_FIX.md）
- 已完成的階段性文檔（如 PHASE2_*.md）
- 單一問題診斷文檔（已解決的）

---

## 📋 執行清單

### Calendar 項目

#### 創建新結構
- [ ] 創建 `docs/` 目錄及子目錄
- [ ] 創建 `archives/` 目錄

#### 核心文檔
- [ ] 合併 DEVELOPMENT_LOG.md
- [ ] 更新 README.md（添加新目錄說明）
- [ ] 保持 DEPLOYMENT_GUIDE_GCP_STANDARD.md 🔒

#### 整合文檔
- [ ] 創建 `docs/authentication/README.md`
- [ ] 創建 `docs/deployment/README.md`
- [ ] 創建 `docs/performance/README.md`
- [ ] 創建 `docs/integrations/google-reviews/README.md`
- [ ] 創建 `docs/cloudflare/README.md`
- [ ] 創建 `docs/wordpress/README.md`

#### 移動文檔
- [ ] 移動認證相關文檔到 `docs/authentication/`
- [ ] 移動部署相關文檔到 `docs/deployment/`
- [ ] 移動性能相關文檔到 `docs/performance/`
- [ ] 移動 Elementor 文檔到 `archives/`
- [ ] 移動過時診斷文檔到 `archives/`

#### 刪除冗餘
- [ ] 刪除重複的部署文檔
- [ ] 刪除臨時狀態文檔
- [ ] 刪除已合併的文檔

### Frontend 項目

#### 核心文檔
- [ ] 簡化 README.md
- [ ] 移除重複的 DEVELOPMENT_LOG.md（已合併）
- [ ] 移除重複的 GIT_WORKFLOW.md
- [ ] 移除重複的 DEPLOYMENT_GUIDE_GCP_STANDARD.md

#### 創建新結構
- [ ] 創建 `docs/` 目錄（精簡版）
- [ ] 創建指向 Calendar 項目文檔的鏈接

#### 整合文檔
- [ ] 保留 Frontend 特定的部署文檔
- [ ] 保留 API 密鑰配置文檔
- [ ] 其他文檔移動到 Calendar 或刪除

---

## 🎯 預期結果

### Calendar 項目（主文檔庫）
- **根目錄**: 6-8個核心文檔
- **docs/**: 分類詳細文檔
- **archives/**: 歷史文檔
- **減少**: ~25個根目錄文檔

### Frontend 項目（精簡版）
- **根目錄**: 3-5個核心文檔
- **docs/**: 指向 Calendar 文檔的鏈接
- **減少**: ~15個文檔

### 總體改進
- ✅ 消除文檔重複
- ✅ 清晰的文檔分類
- ✅ 易於維護和查找
- ✅ 降低文檔數量 40%+
- ✅ 提升文檔質量

---

## ⚠️ 注意事項

1. **DEPLOYMENT_GUIDE_GCP_STANDARD.md** 標記為 🔒 唯讀，謹慎修改
2. 所有移動或刪除操作前先備份
3. 更新所有文檔內部鏈接
4. 通知團隊文檔結構變更
5. 保持 Git 歷史記錄完整

---

## 📅 執行時間表

- **Phase 1**: 創建新目錄結構 - 30分鐘
- **Phase 2**: 合併核心文檔 - 1小時
- **Phase 3**: 整合主題文檔 - 2小時
- **Phase 4**: 歸檔歷史文檔 - 30分鐘
- **Phase 5**: 清理冗餘文檔 - 30分鐘

**總計**: 約 4.5 小時

---

**下一步**: 開始執行 Phase 1 - 創建新目錄結構
