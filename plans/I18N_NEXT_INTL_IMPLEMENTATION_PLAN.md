# next-intl 多語言系統實施計劃（P2-1）

## 📋 執行摘要

**目標**：使用 `next-intl` 實現香港繁體中文、英文、日文三語系統  
**方案**：next-intl（Next.js 官方推薦的 i18n 方案）  
**語言代碼**：`zh-HK`（香港繁體）、`en`（英文）、`ja`（日文）  
**實施風險**：🟡 中等（需要重構目錄結構）  
**SEO 收益**：🟢 高（URL 路徑分離，hreflang 優化）

---

## 🎯 為什麼選擇 next-intl？

### ✅ 優勢

**SEO 優化**：
- URL 路徑分離：`/zh-HK/about`, `/en/about`, `/ja/about`
- Google 可獨立索引每種語言版本
- 自動生成 hreflang 標籤
- 多語言搜索排名提升

**技術優勢**：
- Next.js 16 App Router 原生支持
- Server Components 完整支持
- TypeScript 類型安全
- 零運行時開銷（翻譯在構建時處理）

**長期維護**：
- 社群活躍，文檔完善
- 與 Next.js 版本同步更新
- 易於擴展到更多語言

### ❌ 我們遇到的挑戰

1. **Next.js 16 新特性**：`params` 變成 Promise
2. **Turbopack 緩存損壞**：目錄大量變更導致
3. **複雜的目錄遷移**：23+ 頁面需要移動
4. **動態路由更新**：6+ 動態路由需要調整

---

## 🏗️ 目錄結構變更

### 現有結構（Before）

```
app/
├── layout.tsx
├── page.tsx
├── globals.css
├── (pages)/              # 23+ 個頁面
│   ├── about/
│   ├── journeys/
│   ├── journey/[slug]/
│   ├── member/
│   ├── login/
│   ├── order/[orderKey]/
│   └── ...
└── api/                  # API 路由
```

### 目標結構（After）

```
app/
├── layout.tsx            # Root layout（最小化）
├── [locale]/             # 🆕 語言動態路由
│   ├── layout.tsx        # Locale layout（主要邏輯）
│   ├── page.tsx          # 首頁
│   ├── globals.css       # 樣式
│   └── (pages)/          # 所有頁面遷移到這裡
│       ├── about/
│       ├── journeys/
│       ├── journey/[slug]/
│       ├── member/
│       └── ...
└── api/                  # 保持不變
```

### URL 路由變化

```
Before               →    After
/                    →    /zh-HK
/about               →    /zh-HK/about
/journeys            →    /zh-HK/journeys
/journey/kayaking    →    /zh-HK/journey/kayaking

新增：
/en                  →    英文首頁
/en/about            →    英文關於頁面
/ja/journeys         →    日文旅程頁面
```

---

## 📝 分階段實施計劃

### 🔵 階段 1：準備與備份（15 分鐘）

**目標**：確保可以安全回滾

#### 步驟 1.1：創建功能分支

```bash
cd /Users/henrylo/Documents/GitHub/kayarine-nextjs-frontend
git checkout -b feature/i18n-next-intl
git push -u origin feature/i18n-next-intl
```

#### 步驟 1.2：完整備份

```bash
# 備份整個項目
cd /Users/henrylo/Documents/GitHub
tar -czf kayarine-nextjs-backup-$(date +%Y%m%d-%H%M%S).tar.gz kayarine-nextjs-frontend/

# 驗證備份
ls -lh kayarine-nextjs-backup-*.tar.gz
```

#### 步驟 1.3：列出所有需要遷移的文件

```bash
cd kayarine-nextjs-frontend
find app/(pages) -type f -name "*.tsx" > migration-checklist.txt
wc -l migration-checklist.txt
```

**檢查點**：
- [ ] 功能分支已創建
- [ ] 備份文件已生成
- [ ] 遷移清單已建立

---

### 🟢 階段 2：安裝與配置（20 分鐘）

**目標**：設置 next-intl 核心配置

#### 步驟 2.1：安裝 next-intl

```bash
npm install next-intl --legacy-peer-deps
```

#### 步驟 2.2：創建配置文件

**文件 1：`i18n.ts`**
```typescript
import { getRequestConfig } from 'next-intl/server';

export const locales = ['zh-HK', 'en', 'ja'] as const;
export const defaultLocale = 'zh-HK';

export default getRequestConfig(async ({ locale }) => {
  return {
    locale,  // ⚠️ 必須返回
    messages: (await import(`./messages/${locale}.json`)).default
  };
});
```

**文件 2：`middleware.ts`**
```typescript
import createMiddleware from 'next-intl/middleware';
import { locales, defaultLocale } from './i18n';

export default createMiddleware({
  locales,
  defaultLocale,
  localePrefix: 'always'
});

export const config = {
  matcher: ['/((?!api|_next|.*\\..*).*)']
};
```

**文件 3：`next.config.ts`** （更新）
```typescript
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./i18n.ts');

const nextConfig: NextConfig = {
  // ... 保留現有配置
};

export default withNextIntl(nextConfig);
```

#### 步驟 2.3：創建翻譯文件

```bash
mkdir messages
# 創建 messages/zh-HK.json
# 創建 messages/en.json
# 創建 messages/ja.json
```

**檢查點**：
- [ ] next-intl 安裝成功
- [ ] 配置文件已創建
- [ ] 翻譯文件已就緒

---

### 🟡 階段 3：目錄遷移（30 分鐘）⚠️ 關鍵步驟

**目標**：安全遷移所有頁面到 `[locale]` 目錄

#### 步驟 3.1：創建自動化遷移腳本

**文件：`scripts/migrate-i18n-structure.sh`**

```bash
#!/bin/bash
set -e

echo "🚀 開始 i18n 目錄遷移..."

cd app

# 停止開發服務器
echo "⏸️ 停止服務器..."
pkill -9 node || true
sleep 3

# 清除緩存
echo "🧹 清除緩存..."
rm -rf ../.next

# 創建目錄
echo "📁 創建 [locale] 結構..."
mkdir -p '[locale]'
mkdir -p '[locale]/(pages)'

# 創建新的 root layout
echo "📝 創建 Root Layout..."
cat > layout.tsx << 'EOF'
import { ReactNode } from "react";

export default function RootLayout({ children }: { children: ReactNode }) {
  return children;
}
EOF

# 移動並更新 locale layout
echo "📝 移動 Layout..."
cp layout.tsx.backup '[locale]/layout.tsx'  # 需要手動準備

# 移動頁面
echo "📄 移動 page.tsx..."
mv page.tsx '[locale]/'

# 複製樣式
echo "🎨 複製 globals.css..."
cp globals.css '[locale]/'

# 批量遷移 (pages)
echo "📂 遷移所有頁面..."
for item in (pages)/*; do
  if [ -e "$item" ]; then
    echo "  移動: $item"
    mv "$item" '[locale]/(pages)/'
  fi
done

# 刪除空目錄
rmdir '(pages)' 2>/dev/null || true

echo "✅ 遷移完成！"
ls -la '[locale]'
ls -la '[locale]/(pages)' | head -20
```

#### 步驟 3.2：執行遷移

```bash
chmod +x scripts/migrate-i18n-structure.sh
bash scripts/migrate-i18n-structure.sh
```

#### 步驟 3.3：驗證遷移

```bash
# 檢查文件數量
find app/[locale]/(pages) -type f | wc -l
# 應該看到所有頁面都已遷移

# 檢查目錄結構
tree app -L 3
```

**檢查點**：
- [ ] 所有頁面已移到 `[locale]/(pages)`
- [ ] `(pages)` 目錄已刪除
- [ ] 文件數量正確

---

### 🟣 階段 4：更新動態路由（40 分鐘）

**目標**：修復所有動態路由的 params 處理

#### 需要更新的文件清單

| 文件 | 動態參數 | 優先級 |
|------|----------|--------|
| `journey/[slug]/page.tsx` | `{ locale, slug }` | P0 |
| `product/[id]/page.tsx` | `{ locale, id }` | P1 |
| `post/[slug]/page.tsx` | `{ locale, slug }` | P1 |
| `order/[orderKey]/page.tsx` | `{ locale, orderKey }` | P0 |
| `reschedule/[token]/page.tsx` | `{ locale, token }` | P0 |
| `journeys/[category]/page.tsx` | `{ locale, category }` | P1 |

#### 更新模式

**Before（錯誤）**：
```typescript
export default function Page({ 
  params 
}: { 
  params: { slug: string } 
}) {
  const { slug } = params;  // ❌ Next.js 16 會報錯
}
```

**After（正確）**：
```typescript
export default async function Page({ 
  params 
}: { 
  params: Promise<{ locale: string; slug: string }> 
}) {
  const { locale, slug } = await params;  // ✅ 正確
}
```

#### 自動化腳本（建議）

**文件：`scripts/update-dynamic-routes.sh`**

```bash
#!/bin/bash

FILES=(
  "app/[locale]/(pages)/journey/[slug]/page.tsx"
  "app/[locale]/(pages)/product/[id]/page.tsx"
  "app/[locale]/(pages)/post/[slug]/page.tsx"
  "app/[locale]/(pages)/order/[orderKey]/page.tsx"
  "app/[locale]/(pages)/reschedule/[token]/page.tsx"
  "app/[locale]/(pages)/journeys/[category]/page.tsx"
)

for file in "${FILES[@]}"; do
  if [ -f "$file" ]; then
    echo "⚠️ 需要手動更新: $file"
    echo "   添加 locale 參數並使用 await params"
  fi
done
```

**檢查點**：
- [ ] 所有動態路由已更新
- [ ] params 正確使用 await
- [ ] locale 參數已添加

---

### 🔴 階段 5：解決 Turbopack 問題（30 分鐘）

**目標**：避免緩存損壞

#### 策略 1：完全清除緩存

```bash
# 停止所有 Node 進程
pkill -9 node
sleep 5

# 刪除所有緩存
rm -rf .next
rm -rf node_modules/.cache
rm -rf /var/folders/*/T/next-*  # macOS 臨時緩存

# 重新啟動
npm run dev
```

#### 策略 2：禁用 Turbopack（備用）

```bash
# 使用 webpack 代替
next dev --no-turbopack
```

**或更新 `package.json`**：
```json
{
  "scripts": {
    "dev": "next dev --no-turbopack",
    "dev:turbo": "next dev"
  }
}
```

#### 策略 3：分批重啟

```
遷移 5 個頁面 → 測試 → 成功 ✓
遷移 5 個頁面 → 測試 → 成功 ✓
遷移剩餘頁面 → 測試 → 成功 ✓
```

**檢查點**：
- [ ] 緩存已清除
- [ ] 服務器可啟動
- [ ] 無 Turbopack panic 錯誤

---

### 🟢 階段 6：測試與驗證（30 分鐘）

**目標**：確保所有功能正常

#### 測試清單

**路由測試**：
```bash
curl -I http://localhost:3000/
# 應重定向到 /zh-HK

curl -I http://localhost:3000/zh-HK
# 應返回 200

curl -I http://localhost:3000/en
# 應返回 200

curl -I http://localhost:3000/ja
# 應返回 200
```

**頁面測試**：
- [ ] `/zh-HK` - 首頁（繁體）
- [ ] `/en` - 首頁（英文）
- [ ] `/ja` - 首頁（日文）
- [ ] `/zh-HK/about` - 關於（繁體）
- [ ] `/en/journeys` - 旅程（英文）
- [ ] `/ja/member` - 會員（日文）

**動態路由測試**：
- [ ] `/en/journey/kayaking` - 旅程詳情
- [ ] `/ja/product/123` - 產品頁面
- [ ] `/zh-HK/order/abc123` - 訂單管理

**語言切換測試**：
- [ ] 切換器顯示正常
- [ ] 點擊切換 → URL 更新
- [ ] 頁面路徑保持（`/zh-HK/about` → `/en/about`）

**SEO 測試**：
- [ ] View Page Source
- [ ] 檢查 hreflang 標籤
- [ ] 檢查 meta language 標籤

**檢查點**：
- [ ] 所有路由正常工作
- [ ] 語言切換無誤
- [ ] SEO 標籤正確

---

## 🛠️ 核心代碼模板

### 1. i18n.ts（完整版）

```typescript
import { getRequestConfig } from 'next-intl/server';

export const locales = ['zh-HK', 'en', 'ja'] as const;
export type Locale = (typeof locales)[number];
export const defaultLocale: Locale = 'zh-HK';

export default getRequestConfig(async ({ locale }) => {
  // 靜態導入，避免動態路徑問題
  let messages;
  if (locale === 'zh-HK') {
    messages = (await import('./messages/zh-HK.json')).default;
  } else if (locale === 'en') {
    messages = (await import('./messages/en.json')).default;
  } else if (locale === 'ja') {
    messages = (await import('./messages/ja.json')).default;
  }

  return {
    locale,      // ⚠️ 必須返回 locale
    messages
  };
});
```

### 2. app/layout.tsx（Root Layout）

```typescript
import { ReactNode } from "react";

export default function RootLayout({ children }: { children: ReactNode }) {
  // 最小化 - 主要邏輯在 [locale]/layout.tsx
  return children;
}
```

### 3. app/[locale]/layout.tsx（Locale Layout）

```typescript
import { Geist, Geist_Mono } from "next/font/google";
import { NextIntlClientProvider } from 'next-intl';
import { getMessages } from 'next-intl/server';
import { notFound } from 'next/navigation';
import "./globals.css";
import { Layout } from "@/components/common/Layout";
import { AuthProvider } from "@/contexts/AuthContext";
import { locales } from '@/i18n';

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

type Props = {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;  // ⚠️ Next.js 16: Promise
};

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;  // ⚠️ 必須 await
  
  const titles: Record<string, string> = {
    'zh-HK': 'Kayarine - 水上活動預訂平台',
    'en': 'Kayarine - Water Sports Booking Platform',
    'ja': 'Kayarine - ウォータースポーツ予約プラットフォーム',
  };

  return {
    title: titles[locale] || titles['zh-HK'],
    description: '...',
  };
}

export default async function LocaleLayout({ children, params }: Props) {
  const { locale } = await params;  // ⚠️ 必須 await
  
  if (!locales.includes(locale as any)) {
    notFound();
  }

  const messages = await getMessages();

  return (
    <html lang={locale}>
      <head>
        <link rel="alternate" hrefLang="zh-HK" href="https://kayarine.club/zh-HK" />
        <link rel="alternate" hrefLang="en" href="https://kayarine.club/en" />
        <link rel="alternate" hrefLang="ja" href="https://kayarine.club/ja" />
        <link rel="alternate" hrefLang="x-default" href="https://kayarine.club/zh-HK" />
      </head>
      <body className={`${geistSans.variable} ${geistMono.variable}`}>
        <NextIntlClientProvider messages={messages}>
          <AuthProvider>
            <Layout>{children}</Layout>
          </AuthProvider>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
```

### 4. 動態路由頁面模板

```typescript
// app/[locale]/(pages)/journey/[slug]/page.tsx

type Props = {
  params: Promise<{ 
    locale: string;  // 新增
    slug: string; 
  }>;
};

export default async function JourneyDetailPage({ params }: Props) {
  const { locale, slug } = await params;  // ⚠️ await 並解構 locale
  
  // ... 頁面邏輯
}
```

### 5. 語言切換器組件

```typescript
'use client';

import { useLocale } from 'next-intl';
import { usePathname, useRouter } from 'next/navigation';
import { Globe } from 'lucide-react';
import { locales } from '@/i18n';

export function LanguageSwitcher() {
  const locale = useLocale();
  const router = useRouter();
  const pathname = usePathname();

  const handleLanguageChange = (newLocale: string) => {
    const pathWithoutLocale = pathname.replace(`/${locale}`, '');
    const newPath = `/${newLocale}${pathWithoutLocale}`;
    router.push(newPath);
  };

  return (
    <div className="relative group">
      <button className="flex items-center gap-2">
        <Globe className="w-4 h-4" />
        <span>{locale.toUpperCase()}</span>
      </button>
      <div className="absolute dropdown">
        {locales.map((loc) => (
          <button key={loc} onClick={() => handleLanguageChange(loc)}>
            {loc}
          </button>
        ))}
      </div>
    </div>
  );
}
```

---

## ⚠️ 風險與緩解措施

### 風險 1：Turbopack 緩存損壞 🔴 高風險

**症狀**：
- `Failed to restore task data`
- `DecompressionFailed`
- 重啟循環

**預防措施**：
1. 完全停止服務器再進行遷移
2. 遷移後清除 `.next` 目錄
3. 必要時使用 `--no-turbopack`
4. 分批遷移並測試

**緊急回滾**：
```bash
git reset --hard HEAD
rm -rf .next
npm run dev
```

---

### 風險 2：目錄遷移錯誤 🟡 中風險

**症狀**：
- 頁面找不到
- 404 錯誤
- 路由不匹配

**預防措施**：
1. 使用腳本自動化
2. 檢查每個步驟的輸出
3. 驗證文件數量
4. 保留備份

**驗證命令**：
```bash
# 遷移前
find app/(pages) -type f | wc -l

# 遷移後
find app/[locale]/(pages) -type f | wc -l
# 數字應該相同
```

---

### 風險 3：params Promise 未處理 🟡 中風險

**症狀**：
- `params.locale is undefined`
- TypeScript 錯誤

**預防措施**：
1. 使用查找工具定位所有 `params` 使用
2. 批量更新類型定義
3. 添加 `await`

**查找命令**：
```bash
grep -r "params:" app/[locale]/(pages) --include="*.tsx"
```

---

## 📅 建議執行時程

### 選項 A：一次性完成（高風險）
**時間**：2-3 小時連續  
**適合**：有完整時間、願意承擔風險

### 選項 B：分三次完成（低風險）⭐ 推薦

**Session 1（1 小時）**：
- 準備、備份、安裝
- 配置文件設置
- 遷移 10 個簡單頁面
- 測試基本路由

**Session 2（1 小時）**：
- 遷移剩餘頁面
- 更新所有動態路由
- 完整功能測試

**Session 3（30 分鐘）**：
- SEO 優化
- 文檔更新
- Code review
- 合併分支

---

## 📚 參考資源

**官方文檔**：
- [next-intl App Router Guide](https://next-intl-docs.vercel.app/docs/getting-started/app-router)
- [Next.js 16 Dynamic Routes](https://nextjs.org/docs/app/building-your-application/routing/dynamic-routes)

**已創建的文件**（從之前嘗試）：
- ✅ `messages/zh-HK.json` - 翻譯文件已完成
- ✅ `messages/en.json` - 翻譯文件已完成
- ✅ `messages/ja.json` - 翻譯文件已完成
- ✅ `components/common/LanguageSwitcher.tsx` - 組件已完成
- ✅ `lib/i18n/translations.ts` - 可作為參考

---

## ✅ 成功標準

**功能性**：
- [ ] 三種語言都可訪問
- [ ] URL 路徑正確（`/zh-HK`, `/en`, `/ja`）
- [ ] 語言切換器正常工作
- [ ] 所有頁面路由正常

**SEO**：
- [ ] hreflang 標籤存在且正確
- [ ] 每種語言有獨立的 metadata
- [ ] HTML lang 屬性正確

**穩定性**：
- [ ] 無編譯錯誤
- [ ] 無運行時錯誤
- [ ] 服務器可穩定運行

---

## 🎯 下一步建議

基於剛才的經驗教訓，我建議：

**📌 立即行動**：
1. 確認網站已恢復正常（`git stash` 後）
2. 創建詳細的實施計劃文檔（本文檔）
3. 決定執行時程（選項 A 或 B）

**📌 準備工作**（可先完成）：
- [ ] Review 翻譯文件內容
- [ ] 準備測試案例
- [ ] 閱讀 next-intl 文檔

**📌 執行建議**：
- 使用 Session 2（分三次）方案
- 每次 commit 進度
- 遇到問題立即回滾

---

**最後更新**：2026-02-09  
**版本**：v1.0  
**狀態**：規劃階段
