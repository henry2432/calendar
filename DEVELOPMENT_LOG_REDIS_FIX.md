# Redis 快取問題修復記錄 (v2.3.7)

## 部署詳情
- **版本**：v2.3.7 (Redis 快取整合與問題修復)
- **時間戳**：2026-02-05T08:04 UTC+8
- **部署狀態**：✅ 網站功能已恢復，活動卡片正常顯示
- **解決問題**：Upstash Redis 整合導致 JSON 序列化錯誤

## 問題診斷與解決

### 問題描述
- **症狀**：首頁推薦旅程活動卡片消失
- **錯誤**：`SyntaxError: Unexpected token 'o', "[object Obj"... is not valid JSON`
- **影響範圍**：所有調用 `getWaterActivities()` 的頁面
- **PM2 日誌**：重複錯誤 `at JSON.parse(<anonymous>)` 在 `cZ()` 函數

### 根本原因
1. **Upstash Redis SDK 行為**：默認啟用 `automaticDeserialization: true`
2. **雙重序列化問題**：
   - Redis 返回已反序列化的 JavaScript 對象
   - 代碼使用 `JSON.parse(cached)` 嘗試解析對象
   - 結果：`JSON.parse("[object Object]")` → SyntaxError
3. **數據類型不匹配**：`safeRedisGet()` 返回值類型與預期不符

### 解決方案

#### 方案 A（嘗試但失敗）：修復 Redis 序列化邏輯
**文件變更**：
- `lib/redis.ts`：添加 `automaticDeserialization: false`
- `lib/redis.ts`：改進 `safeRedisGet()` 類型處理
- `lib/api/wordpress.ts`：添加安全 JSON 解析邏輯

**失敗原因**：
- 服務器 `node_modules` 缺少 `@upstash/redis` 依賴
- `npm install` 執行後，服務器端構建仍失敗
- 部署複雜度過高（需同步依賴、源代碼、編譯產物）

#### 方案 B（最終採用）：暫時禁用 Redis 快取
**文件變更**：
```typescript
// lib/api/wordpress.ts (Line 2)
// import { safeRedisGet, safeRedisSet } from '../redis'; // 暫時禁用

export async function getWaterActivities(): Promise<Product[]> {
  try {
    // 暫時禁用 Redis 緩存，直接從 API 獲取
    console.log('📡 直接從 WordPress API 獲取活動列表（Redis 暫時禁用）');
    
    const response = await fetch(`${WORDPRESS_API_URL}/wp-json/wc/store/products?per_page=100`, {
      cache: 'no-store'
    });
    
    // ... 數據處理邏輯 ...
    
    console.log('✅ 活動列表已從 WordPress API 獲取');
    return products;
  }
}
```

**優點**：
- ✅ 立即恢復網站功能
- ✅ 避免複雜的依賴管理
- ✅ 保留 ISR (revalidate: 3600) 頁面級快取
- ✅ Redis 代碼保留，未來可重新啟用

## 性能指標

### 當前狀態（Redis 禁用）
| 指標 | 數值 | 狀態 |
|------|------|------|
| 首頁響應時間 | 1.637s | ✅ 正常 |
| 活動數據載入 | 7 個活動 | ✅ 完整 |
| 構建時間 (本地) | 2.8s | ✅ 正常 |
| 構建時間 (服務器) | 11.8s | ✅ 正常 |
| PM2 狀態 | online | ✅ 穩定 |
| JSON 解析錯誤 | 0 | ✅ 已修復 |

### 活動列表載入驗證
```json
{
  "sup-yoga": "SUP瑜伽",
  "sup-intermediate": "白沙洲直立板中級銀章",
  "sup-beginner-pakshawan": "白沙洲直立板入門班",
  "sharp-island-kayak-snorkel": "橋咀島獨木舟浮潛體驗",
  "sup-beginner": "白沙洲直立板入門班",
  "sunset-sup": "白沙洲日落直立板團",
  "sunrise-sup": "白沙洲日出直立板團"
}
```

## 部署步驟

```bash
# 1. 移除 Redis 快取邏輯（本地）
cd kayarine-nextjs-frontend
# 編輯 lib/api/wordpress.ts - 注釋 Redis import 和調用

# 2. 本地構建
npm run build
# ✓ Compiled successfully in 2.8s

# 3. 上傳到服務器
scp -r .next lib kayarine.server@104.199.144.122:/home/kayarine.server/kayarine-nextjs-frontend/

# 4. 服務器端重新構建（清除緩存）
ssh kayarine.server@104.199.144.122 "cd /home/kayarine.server/kayarine-nextjs-frontend && \
  npm install && \
  pm2 stop kayarine-nextjs-frontend && \
  rm -rf .next && \
  npm run build && \
  pm2 restart kayarine-nextjs-frontend"

# 5. 驗證活動數據
curl -s http://104.199.144.122:3000/ | grep -o '"activities":\[.\{1,200\}\]'
```

## 技術學習

### Upstash Redis SDK 行為
- **automaticDeserialization**：默認 `true`，自動解析 JSON
- **返回值類型**：
  - String 存儲 → 自動解析為對象/數組
  - 需要明確 `automaticDeserialization: false` 保持原始字符串

### Next.js 部署陷阱
1. **構建緩存問題**：`.next` 文件夾上傳不更新，需服務器端重新構建
2. **依賴同步**：`package.json` 變更後必須在服務器執行 `npm install`
3. **PM2 環境變量**：使用 `--update-env` 標誌確保載入 `.env.local`

## 未來優化計劃

### 短期（1-2 天）
1. **重新整合 Redis 快取**
   - 在服務器上確認 `@upstash/redis` 已安裝
   - 啟用 `automaticDeserialization: false`
   - 使用新緩存 key：`activities:water:v2`
   - 測試數據序列化流程

2. **性能測試**
   - 對比 WordPress API 直接調用 vs Redis 快取
   - 測量首次訪問 vs 快取命中響應時間
   - 驗證 5 分鐘 TTL 是否合理

### 中期（1-2 週）
3. **擴展 Redis 快取範圍**
   - 快取部落格文章列表
   - 快取租借設備數據
   - 實施統一快取管理策略

4. **監控與日誌**
   - 添加 Redis 連接狀態監控
   - 記錄快取命中率（hit rate）
   - 設置快取失效告警

### 長期（庫存管理系統整合）
5. **Redis 數據結構設計**
   - 活動庫存：`inventory:{product_id}` (Hash)
   - 訂單隊列：`orders:pending` (List)
   - 用戶會話：`session:{user_id}` (String, TTL: 30min)

## 回滾指南

如果需要回退到 v2.3.6（無 Redis 版本）：
```bash
# 1. 還原 lib/api/wordpress.ts
git checkout v2.3.6 -- lib/api/wordpress.ts

# 2. 移除 Redis 文件
rm lib/redis.ts

# 3. 移除依賴
npm uninstall @upstash/redis

# 4. 重新部署
npm run build
scp -r .next kayarine.server@104.199.144.122:/home/kayarine.server/kayarine-nextjs-frontend/
ssh kayarine.server@104.199.144.122 "pm2 restart kayarine-nextjs-frontend"
```

## 檢查清單

- [x] 活動卡片正常顯示（7 個活動）
- [x] 無 JSON 解析錯誤
- [x] PM2 狀態：online
- [x] 構建時間正常（< 15s）
- [x] 響應時間可接受（< 2s）
- [x] 日誌無異常錯誤
- [ ] Redis 快取重新啟用（待完成）
- [ ] 性能達到 <500ms 目標（待完成）

---

**修復完成時間**：2026-02-05T08:06 UTC+8  
**下次優化重點**：整合 Redis 快取並實現 <500ms 響應時間
