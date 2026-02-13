# 會員中心認證系統開發路線圖

> 會員中心 UI 已完成，本文檔說明認證整合的當前狀況、問題分析和解決方案

**創建日期**: 2026-02-06  
**狀態**: 會員中心 UI 完成 ✅ | 認證系統待開發 ⏸️

---

## 📊 當前狀況總結

### ✅ 已完成部分

#### **Next.js 前端（完整部署）**
- **頁面**: `/login`, `/member`
- **組件**: 7個會員中心組件
- **UI**: 橙色主題（#FF6B35），完全符合 Figma 設計
- **參考設計**: 
  - 登入/註冊：參考 `/Users/henrylo/Documents/GitHub/calendar` 文件設計
  - 改期/取消：參考 [`class-kayarine-member-dashboard.php`](kayarine-booking/includes/class-kayarine-member-dashboard.php)
- **部署狀態**: PM2 運行中，HTTPS 訪問正常

#### **相關文件**
- **前端組件**: `/Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend/components/member-dashboard/`
- **API 服務**: `/Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend/lib/api/member.ts`
- **配置文檔**: [`JWT_AUTH_SETUP_GUIDE.md`](JWT_AUTH_SETUP_GUIDE.md)

---

## ⚠️ 當前問題

### **問題 1：JWT Authentication Plugin 導致 WordPress 崩潰**

**嘗試的 Plugin**: `JWT Authentication for WP REST API`

**結果**:
- ✅ 安裝成功
- ✅ Secret Key 配置成功  
- ❌ **WordPress 後台崩潰**（Critical Error 500）
- ❌ wp-login.php 變黑畫面
- ❌ 移除 plugin 和配置後恢復正常

**結論**: 此 JWT plugin 與當前 WordPress 環境不相容

---

### **問題 2：登入/註冊功能無法使用**

**當前實現**: 重定向到 WordPress 原生頁面

**問題**:
- 登入：跳轉到 `/wp-login.php` → 404
- 註冊：跳轉到 `/my-account/` → 404

**原因**: WordPress permalink 或頁面配置問題

---

### **問題 3：WPGraphQL 生態 Plugins 找不到**

**搜尋不到的 Plugins**:
- WooGraphQL
- WPGraphQL JWT Authentication  
- WPGraphQL for WooCommerce

**可能原因**:
- Plugin 名稱不正確
- 需要從 GitHub 手動安裝
- 或使用其他替代方案

---

## 🏗️ 技術架構現況

### **當前架構**
```
Next.js 前端 (TypeScript/React)
     ↓ HTTP fetch
WordPress REST API (PHP)
     ↓
WordPress 數據庫 (MySQL)
```

**使用的 APIs**:
- `/wp-json/wc/store/products` - 產品列表
- `/wp-json/wp/v2/posts` - 文章列表
- `/wp-json/wp/v2/users/me` - 用戶資料（需認證）

---

## 💡 解決方案選項

### **方案 A：Next.js API Routes 認證**（推薦 🥇）

**架構**:
```
Next.js 前端 → Next.js API Routes → WordPress REST API
              ↑ 處理認證和 session
```

**實現**:
```typescript
// app/api/auth/login/route.ts
export async function POST(request: Request) {
  const { email, password } = await request.json();
  
  // 使用 WordPress Application Password 認證
  const auth = Buffer.from(`${email}:${appPassword}`).toString('base64');
  
  const response = await fetch('https://kayarine.club/wp-json/wp/v2/users/me', {
    headers: { 'Authorization': `Basic ${auth}` }
  });
  
  if (response.ok) {
    // 創建 Next.js session (iron-session 或 next-auth)
    return NextResponse.json({ success: true });
  }
}
```

**優點**:
- ✅ 無需任何 WordPress plugin
- ✅ 完全控制認證流程
- ✅ 使用 WordPress Application Password（內建功能）
- ✅ 1-2 天可完成

**缺點**:
- ⚠️ 需要在 WordPress 後台為每個用戶生成 Application Password

**所需 npm packages**:
- `iron-session` 或 `next-auth`

---

### **方案 B：WPGraphQL + 自定義認證**（進階 🥈）

**架構**:
```
Next.js → WPGraphQL → WordPress
```

**必要 Plugins**:
1. **WPGraphQL** ✅ 已安裝
2. **WPGraphQL for WooCommerce** ⚠️ 需手動安裝
   - GitHub: https://github.com/wp-graphql/wp-graphql-woocommerce
   - 或搜尋 "WooCommerce GraphQL"

**認證方式**: 使用 WPGraphQL 的內建 `login` mutation（無需 JWT plugin）

**GraphQL Query 範例**:
```graphql
mutation Login {
  login(input: {
    username: "user@example.com"
    password: "password"
  }) {
    authToken
    user {
      id
      name
      email
    }
  }
}
```

**優點**:
- ✅ GraphQL 更靈活
- ✅ 只請求需要的數據
- ✅ WordPress 官方推薦
- ✅ 內建 login mutation（無需 JWT plugin）

**缺點**:
- ⚠️ 需要重寫所有 API 調用（REST → GraphQL）
- ⚠️ 學習曲線（GraphQL 語法）
- ⚠️ 3-5 天工作量

**所需 npm packages**:
- `@apollo/client`
- `graphql`

---

### **方案 C：Python FastAPI 中間層**（最靈活 🥉）

**架構**:
```
Next.js → FastAPI (Python) → WordPress DB (直接查詢)
```

**實現**:
```python
# backend/auth.py
from fastapi import FastAPI, HTTPException
import bcrypt

@app.post("/api/auth/login")
async def login(email: str, password: str):
    # 直接查詢 WordPress wp_users 表
    user = db.query("SELECT * FROM wp_users WHERE user_email = %s", (email,))
    
    # 驗證密碼（WordPress 使用 PHP password hash）
    if check_wordpress_password(password, user['user_pass']):
        # 生成 JWT token (Python)
        token = create_jwt_token(user['ID'])
        return {"success": True, "token": token}
    
    raise HTTPException(401, "登入失敗")
```

**優點**:
- ✅ 完全控制
- ✅ 可以重用 calendar/backend/ 代碼
- ✅ Python 處理複雜邏輯更容易
- ✅ 避開所有 WordPress plugin 問題

**缺點**:
- ⚠️ 需要部署 Python 服務
- ⚠️ 需要處理 WordPress 密碼 hash 格式
- ⚠️ 5-7 天工作量

**所需**:
- FastAPI
- MySQL connector
- passlib (WordPress password hash)

---

## 🎯 建議下一步

### **短期方案**（立即可用）:

**1. 修復 WordPress 登入頁面**
```bash
# WordPress 後台
設定 → 永久連結 → 儲存變更
WooCommerce → 狀態 → 工具 → 建立預設頁面
```

**2. 使用簡單重定向**（會員中心 UI 已實現此方式）
- 登入：跳轉到 `/wp-login.php`  
- 註冊：跳轉到 `/wp-login.php?action=register`

---

### **中期方案**（1-2 天）:

**實現 Next.js API Routes + Application Password 認證**
- 無需 WordPress plugin
- 穩定可靠
- 完全控制

---

### **長期方案**（可選）:

**遷移到 WPGraphQL**（如需要）
- 更現代化的 API
- 更好的類型安全
- WordPress 官方推薦

---

## 📋 Prompt 引導（開始新任務）

```
請閱讀 MEMBER_CENTER_AUTHENTICATION_ROADMAP.md 了解會員中心認證系統的當前狀況和解決方案。

會員中心 UI 已完成（7個組件，2個頁面），現在需要實現認證功能。

根據文檔中的方案分析，實現【選擇方案 A/B/C】的認證系統。

參考文件：
- calendar/kayarine-booking/includes/class-kayarine-member-dashboard.php（改期/取消邏輯）
- kayarine-nextjs-frontend/lib/api/member.ts（當前 API 實現）
- JWT_AUTH_SETUP_GUIDE.md（JWT 配置，僅供參考）

目標：實現完整的登入/註冊功能，讓會員中心可以顯示真實用戶數據。
```

---

## 📁 相關文件清單

**已完成**:
- `kayarine-nextjs-frontend/components/member-dashboard/*.tsx` (7個組件)
- `kayarine-nextjs-frontend/app/(pages)/login/page.tsx`
- `kayarine-nextjs-frontend/app/(pages)/member/page.tsx`

**需要修改**（認證整合時）:
- `kayarine-nextjs-frontend/lib/api/member.ts`
- `kayarine-nextjs-frontend/components/common/Header.tsx`

**參考**:
- `calendar/kayarine-booking/includes/class-kayarine-member-dashboard.php`
- `JWT_AUTH_SETUP_GUIDE.md`
- `FIGMA_TO_DEPLOYMENT_GUIDE.md`

---

## 🔗 部署記錄

| 日期 | 內容 | 狀態 |
|------|------|------|
| 2026-02-06 | 會員中心 UI 轉換完成 | ✅ 已部署 |
| 2026-02-06 | JWT Plugin 測試 | ❌ 導致崩潰，已移除 |
| 2026-02-06 | WordPress 原生登入方式 | ⏸️ 頁面 404 |
| 待定 | 認證系統實現 | ⏳ 待開始 |

**會員中心 UI 轉換任務已完成。認證系統整合為新的獨立任務。**

---

## 📝 任務 5-8：會員中心改進計劃

**創建日期**: 2026-02-07
**狀態**: 📋 待開發
**優先級**: 中

### 🎯 任務目標

改進會員中心的用戶體驗和視覺呈現，包含真實商品顯示、品牌吉祥物整合、產品展示優化和品牌商店連結。

---

### 📋 改進項目清單

#### **1. 會員中心顯示真實商品** 🛍️

**當前狀況**:
- [`RecommendedProducts.tsx`](../kayarine-nextjs-frontend/components/member-dashboard/RecommendedProducts.tsx) 使用佔位符商品
- 未連接 WordPress 真實產品數據

**改進目標**:
- ✅ 連接 WordPress WooCommerce API
- ✅ 顯示真實防曬衣商品
- ✅ 包含真實價格、圖片、描述
- ✅ 支持點擊跳轉到商品詳情頁

**相關文件**:
- [`components/member-dashboard/RecommendedProducts.tsx`](../kayarine-nextjs-frontend/components/member-dashboard/RecommendedProducts.tsx)
- [`lib/api/rental-equipment.ts`](../kayarine-nextjs-frontend/lib/api/rental-equipment.ts)

---

#### **2. Profile Picture 使用品牌吉祥物** 🎨

**當前狀況**:
- [`WelcomeCard.tsx`](../kayarine-nextjs-frontend/components/member-dashboard/WelcomeCard.tsx:72) 使用通用佔位符圖片
- 路徑: `/member-avatar-placeholder.jpg`

**改進目標**:
- ✅ 替換為 Kayarine 品牌吉祥物圖片
- ✅ 優化圖片尺寸（建議 256x256px）
- ✅ 添加 WebP 格式支持（更好的壓縮）
- ✅ 保持圓形邊框設計（border-4 border-primary/20）

**修改文件**:
```typescript
// components/member-dashboard/WelcomeCard.tsx
<img
  src="/mascot-avatar.webp"  // 更新為吉祥物圖片
  alt="Kayarine Member"
  className="w-full h-full object-cover"
/>
```

**所需資源**:
- 品牌吉祥物圖片（PNG/WebP，256x256px）
- 放置路徑: `kayarine-nextjs-frontend/public/mascot-avatar.webp`

---

#### **3. 防曬衣 Carousel + 圖片優化** 🎠

**當前狀況**:
- 靜態商品展示（grid 佈局）
- 無輪播功能
- 圖片未優化

**改進目標**:
- ✅ 實現 carousel 輪播效果（使用 Embla Carousel 或 Swiper）
- ✅ 支持左右滑動和自動播放
- ✅ 優化商品圖片（WebP 格式 + 響應式尺寸）
- ✅ 添加商品快速預覽功能
- ✅ 移動端支持觸摸滑動

**實現方案**:

**A. 使用 Embla Carousel**（推薦 - 更輕量）:
```bash
npm install embla-carousel-react
```

```typescript
// components/member-dashboard/RecommendedProducts.tsx
import useEmblaCarousel from 'embla-carousel-react';
import Autoplay from 'embla-carousel-autoplay';

export function RecommendedProducts() {
  const [emblaRef] = useEmblaCarousel(
    { loop: true, align: 'start' },
    [Autoplay({ delay: 3000 })]
  );

  return (
    <div className="embla" ref={emblaRef}>
      <div className="embla__container">
        {products.map(product => (
          <div className="embla__slide" key={product.id}>
            <ProductCard product={product} />
          </div>
        ))}
      </div>
    </div>
  );
}
```

**B. 圖片優化策略**:
```typescript
import Image from 'next/image';

<Image
  src={product.image}
  alt={product.name}
  width={400}
  height={400}
  quality={85}
  loading="lazy"
  placeholder="blur"
  blurDataURL={product.blurDataURL}
/>
```

**C. 響應式設計**:
```css
/* 移動端: 1個商品 */
/* 平板: 2個商品 */
/* 桌面: 3-4個商品 */
```

---

#### **4. 指向 Brand Shop 連結** 🔗

**改進目標**:
- ✅ 添加「探索更多」按鈕跳轉到品牌商店
- ✅ 商品卡片點擊跳轉到商品詳情頁
- ✅ 整合 brand-shop 路由

**實現方案**:

**A. 添加「探索品牌商店」CTA**:
```typescript
// components/member-dashboard/RecommendedProducts.tsx
<div className="text-center mt-6">
  <Button
    size="lg"
    onClick={() => router.push('/brand-shop')}
    className="gap-2"
  >
    <Store className="w-5 h-5" />
    探索品牌商店
  </Button>
</div>
```

**B. 商品卡片跳轉**:
```typescript
<Card
  className="cursor-pointer hover:shadow-lg transition-shadow"
  onClick={() => router.push(`/brand-shop/product/${product.id}`)}
>
  {/* 商品內容 */}
</Card>
```

**相關路由**:
- `/brand-shop` - 品牌商店主頁
- `/brand-shop/product/[id]` - 商品詳情頁

---

### 🏗️ 技術架構

**API 整合**:
```typescript
// lib/api/brand-shop.ts
export async function getBrandProducts() {
  const response = await fetch(
    `${process.env.NEXT_PUBLIC_WORDPRESS_API_URL}/wp-json/wc/store/products?category=防曬衣`
  );
  return response.json();
}

export async function getProductById(id: number) {
  const response = await fetch(
    `${process.env.NEXT_PUBLIC_WORDPRESS_API_URL}/wp-json/wc/store/products/${id}`
  );
  return response.json();
}
```

---

### 📦 修改文件清單

**需要修改**:
1. [`components/member-dashboard/WelcomeCard.tsx`](../kayarine-nextjs-frontend/components/member-dashboard/WelcomeCard.tsx) - 更新 profile 圖片
2. [`components/member-dashboard/RecommendedProducts.tsx`](../kayarine-nextjs-frontend/components/member-dashboard/RecommendedProducts.tsx) - 實現 carousel + 真實商品
3. [`lib/api/brand-shop.ts`](../kayarine-nextjs-frontend/lib/api/brand-shop.ts) - 新增（品牌商店 API）

**需要新增**:
4. [`app/(pages)/brand-shop/page.tsx`](../kayarine-nextjs-frontend/app/(pages)/brand-shop/page.tsx) - 品牌商店頁面
5. [`app/(pages)/brand-shop/product/[id]/page.tsx`](../kayarine-nextjs-frontend/app/(pages)/brand-shop/product/[id]/page.tsx) - 商品詳情頁
6. [`components/brand-shop/ProductGrid.tsx`](../kayarine-nextjs-frontend/components/brand-shop/ProductGrid.tsx) - 商品網格組件
7. `public/mascot-avatar.webp` - 品牌吉祥物圖片

---

### 📋 開發步驟

#### **階段 1：基礎設置** (1-2天)
- [ ] 準備品牌吉祥物圖片（PNG/WebP）
- [ ] 創建 brand-shop API 模組
- [ ] 設置 WordPress 防曬衣商品分類
- [ ] 安裝 Embla Carousel 依賴

#### **階段 2：Profile 圖片更新** (0.5天)
- [ ] 更新 WelcomeCard.tsx 圖片路徑
- [ ] 優化圖片尺寸和格式
- [ ] 測試不同屏幕尺寸顯示

#### **階段 3：真實商品整合** (1-2天)
- [ ] 實現 getBrandProducts() API
- [ ] 更新 RecommendedProducts.tsx 使用真實數據
- [ ] 添加載入狀態和錯誤處理
- [ ] 優化商品圖片顯示

#### **階段 4：Carousel 實現** (1-2天)
- [ ] 整合 Embla Carousel
- [ ] 實現左右滑動控制
- [ ] 添加自動播放功能
- [ ] 移動端觸摸支持
- [ ] 添加分頁指示器

#### **階段 5：品牌商店連結** (1天)
- [ ] 創建 /brand-shop 頁面
- [ ] 創建商品詳情頁模板
- [ ] 實現商品卡片點擊跳轉
- [ ] 添加「探索更多」CTA 按鈕

#### **階段 6：測試與優化** (1天)
- [ ] 功能測試（所有連結和跳轉）
- [ ] 響應式測試（移動端/平板/桌面）
- [ ] 性能優化（圖片載入、Carousel 流暢度）
- [ ] 無障礙測試（鍵盤導航、屏幕閱讀器）

---

### 🧪 測試檢查清單

**Profile 圖片**:
- [ ] 吉祥物圖片正確顯示
- [ ] 圓形邊框樣式正確
- [ ] 移動端/桌面端顯示正常
- [ ] 圖片載入速度快

**真實商品**:
- [ ] 商品數據從 WordPress 正確獲取
- [ ] 價格、名稱、圖片正確顯示
- [ ] 無商品時顯示友好提示

**Carousel 功能**:
- [ ] 左右滑動正常
- [ ] 自動播放正常
- [ ] 移動端觸摸滑動流暢
- [ ] 分頁指示器正確顯示
- [ ] 無限循環正常

**品牌商店連結**:
- [ ] 商品卡片點擊跳轉正確
- [ ] 「探索更多」按鈕跳轉正確
- [ ] 商品詳情頁正確顯示
- [ ] 返回功能正常

---

### 🎨 設計規範

**吉祥物圖片規格**:
- 尺寸: 256x256px（1x），512x512px（2x Retina）
- 格式: WebP（主）+ PNG（後備）
- 背景: 透明或品牌色
- 風格: 友好、可愛、品牌一致性

**Carousel 設計**:
- 滑動速度: 300ms 過渡
- 自動播放間隔: 3-5秒
- 商品間距: 16px (移動端), 24px (桌面端)
- 控制按鈕: 橙色主題 (#FF6B35)

**商品卡片**:
- 懸停效果: shadow-lg + scale(1.02)
- 圖片比例: 1:1 (正方形)
- 角落圓角: rounded-lg (8px)
- 邊框: border border-gray-200

---

### 💡 後續優化建議

**短期**:
- [ ] 商品收藏功能（添加到願望清單）
- [ ] 商品快速預覽（Modal）
- [ ] 商品篩選和排序

**中期**:
- [ ] 商品評論和評分系統
- [ ] 會員專屬折扣標籤
- [ ] 庫存狀態實時顯示

**長期**:
- [ ] 個性化商品推薦（基於用戶歷史）
- [ ] AR 試穿功能
- [ ] 社交分享功能

---

### 📊 優先級評估

| 項目 | 優先級 | 工作量 | 影響範圍 |
|------|--------|--------|----------|
| 真實商品顯示 | 🔴 高 | 2天 | 會員中心體驗 |
| Profile 吉祥物 | 🟡 中 | 0.5天 | 品牌形象 |
| Carousel 實現 | 🟡 中 | 2天 | 用戶體驗 |
| 品牌商店連結 | 🟢 低 | 1天 | 商業轉換 |

**總預估工作量**: 5-7 天

---

### 🔗 相關文件

- [`MEMBER_CENTER_AUTHENTICATION_ROADMAP.md`](MEMBER_CENTER_AUTHENTICATION_ROADMAP.md) - 會員中心路線圖
- [`DEVELOPMENT_LOG.md`](DEVELOPMENT_LOG.md) - 開發日誌
- [`DEPLOYMENT_GUIDE_GCP_STANDARD.md`](DEPLOYMENT_GUIDE_GCP_STANDARD.md) - 部署指南
- [`components/member-dashboard/`](../kayarine-nextjs-frontend/components/member-dashboard/) - 會員中心組件目錄

---

**任務記錄日期**: 2026-02-07
**下一步**: 準備吉祥物圖片資源，並開始 API 整合工作
