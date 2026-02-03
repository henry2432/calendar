# 安全診斷計劃（避免數據損壞）

## 風險評估

您的顧慮完全合理。直接修改數據庫可能風險：
- ❌ 添加錯誤的索引導致查詢計劃變差
- ❌ OPTIMIZE TABLE 如果中斷可能破壞表
- ❌ 錯誤的 SQL 可能刪除重要數據

## 安全策略

### 第一步：只讀診斷（零風險）✅
```bash
# 只讀操作，不修改任何數據
1. 檢查數據庫大小（讀操作）
2. 檢查表索引（讀操作）
3. 查看慢查詢日誌（讀操作）
```

### 第二步：軟設置優化（低風險）✅
```bash
# 不修改數據，只改變設置
1. 調整 NitroPack JavaScript 延遲設置
2. 調整 WordPress wp-config.php 設置
3. 這些都可以輕易回滾
```

### 第三步：備份後修改（中等風險）⚠️
```bash
# 添加索引前備份
1. 完整數據庫備份
2. 添加安全的索引（經過驗證）
3. 測試性能
4. 若有問題，回滾備份
```

---

## 當前建議流程

### 階段 1：安全診斷（現在執行）
```
✅ 檢查數據庫大小（讀操作）
✅ 檢查索引狀態（讀操作）
✅ 查看表結構（讀操作）
```

**預計結果：5 分鐘內完成，零風險**

### 階段 2：軟設置優化（接下來執行）
```
✅ 檢查 NitroPack JavaScript 延遲設置
✅ 調整為更激進的延遲（如有必要）
✅ 檢查 WordPress 自動保存設置
✅ 禁用修訂版本和垃圾桶
```

**預計改善：100-300ms，可隨時回滾**

### 階段 3：備份 + 數據庫優化（謹慎執行）
```
⚠️ 執行完整備份（強烈建議）
⚠️ 添加經過驗證的索引
⚠️ 執行 OPTIMIZE TABLE
⚠️ 監控性能變化
```

**預計改善：100-500ms，需備份保險**

---

## 立即執行的安全診斷

基於您的要求，我建議：

### 跳過數據庫修改，直接檢查：

#### 1️⃣ NitroPack JavaScript 設置（可隨時改回）
```
WP Admin → NitroPack → Settings → Advanced

檢查以下項目：
「Delay All JavaScript」- 如果啟用，改為「Partial」
「Optimize Critical CSS」- 檢查是否啟用
「Lazy Load JavaScript」- 檢查是否啟用
```

**目的：** 找出是否有過度優化的設置導致延遲

#### 2️⃣ WordPress 核心設置（低風險改動）
```
編輯 wp-config.php 添加：
define( 'AUTOSAVE_INTERVAL', 300 );  // 改為 5 分鐘
define( 'WP_POST_REVISIONS', 3 );    // 只保留 3 個版本
define( 'EMPTY_TRASH_DAYS', 0 );     // 永久刪除
```

**目的：** 減少自動保存和垃圾桶的查詢負擔

#### 3️⃣ 只讀數據庫診斷（絕對安全）
```bash
# 只讀操作，不修改
ssh ... /opt/bitnami/mariadb/bin/mysql -u wordpress ... << SQL
-- 這些都是 SELECT 操作，完全安全
SHOW TABLE STATUS FROM kayarine_db;
SHOW INDEX FROM wp_postmeta;
SHOW INDEX FROM wp_posts;
SQL
```

**目的：** 了解數據庫狀態，為未來優化做準備

---

## 數據備份計劃（如決定進行數據庫優化）

若決定添加索引或優化表，強烈建議：

### 自動備份步驟
```bash
# 完整備份數據庫
/opt/bitnami/mariadb/bin/mysqldump -u wordpress -p'Bitnami123!' kayarine_db > /home/kayarine.server/backup_$(date +%Y%m%d_%H%M%S).sql

# 備份到另一個位置（以防單點失敗）
scp kayarine.server@104.199.144.122:/home/kayarine.server/backup_*.sql ~/Desktop/
```

### 恢復計劃（若出錯）
```bash
# 恢復備份
/opt/bitnami/mariadb/bin/mysql -u wordpress -p'Bitnami123!' kayarine_db < backup_20260203_153000.sql
```

---

## 我的建議

**分三步走：**

1. **今天（現在）：** 執行階段 1（安全診斷）+ 階段 2（軟設置優化）
   - 預計改善：100-300ms，完全安全

2. **明天：** 檢查改善效果
   - 若達到 1.5-2.0 秒，停止
   - 若仍為 2.3+ 秒，進入階段 3

3. **若需要階段 3：** 執行備份後再修改數據庫
   - 有備份保險
   - 可隨時恢復

---

## 下一步

您同意按照這個安全計劃嗎？

選項：
- [ ] A：執行階段 1 + 2（現在）
- [ ] B：先備份，然後執行全部三個階段
- [ ] C：保守起見，只執行階段 2（軟設置）

請選擇，我會提供相應的步驟和命令。
