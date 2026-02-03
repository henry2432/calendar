# Kayarine Member Dashboard - HTML Reference Template

基於 fig-tem1 設計風格的完整 HTML 模板

---

## 完整 HTML 模板

```html
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayarine Member Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="border-b border-border bg-card">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-water text-primary text-2xl"></i>
                        <span class="text-xl font-semibold text-primary">Kayarine Bookings</span>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="relative">
                            <i class="fas fa-bell text-muted-foreground text-lg"></i>
                            <span class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center bg-primary text-white text-xs rounded-full">3</span>
                        </button>

                        <!-- User Dropdown -->
                        <div class="flex items-center space-x-3">
                            <img src="https://images.unsplash.com/photo-1557110437-0bcd0a636d62?w=100&h=100&fit=crop" alt="Profile" class="w-10 h-10 rounded-full">
                            <div class="hidden sm:block">
                                <p class="text-sm font-medium">John Doe</p>
                                <p class="text-xs text-muted-foreground">Gold Member</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- 1. Welcome Card Section -->
            <section class="mb-8">
                <div class="bg-card border border-border rounded-lg overflow-hidden">
                    <!-- Card with gradient background -->
                    <div class="bg-gradient-to-r from-primary/5 to-primary/10 p-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Avatar Section -->
                            <div class="relative flex-shrink-0">
                                <img 
                                    src="https://images.unsplash.com/photo-1557110437-0bcd0a636d62?w=150&h=150&fit=crop" 
                                    alt="Profile" 
                                    class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg"
                                >
                                <button class="absolute -bottom-2 -right-2 w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary/90 transition">
                                    <i class="fas fa-camera text-sm"></i>
                                </button>
                            </div>

                            <!-- Welcome Content -->
                            <div class="flex-1">
                                <h1 class="text-3xl md:text-4xl font-light mb-2">
                                    Welcome back, <strong>John!</strong>
                                </h1>
                                <p class="text-muted-foreground flex items-center gap-2 mb-4">
                                    你今年已出海了 <strong>5 次</strong> 
                                    <i class="fas fa-trophy text-amber-500"></i>
                                </p>

                                <!-- Points Progress -->
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-muted-foreground">積分進度</span>
                                        <span class="text-sm font-semibold text-primary">850 points</span>
                                    </div>
                                    <div class="w-full bg-border rounded-full h-2 overflow-hidden">
                                        <div class="bg-primary h-full" style="width: 68%;"></div>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        150 more points to unlock Gold Membership rewards
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <button class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition text-sm font-medium">
                                        <i class="fas fa-edit mr-2"></i>Edit Profile
                                    </button>
                                    <button class="px-4 py-2 bg-background border border-border text-foreground rounded-md hover:bg-muted transition text-sm font-medium">
                                        <i class="fas fa-star mr-2"></i>View Achievements
                                    </button>
                                    <button class="px-4 py-2 bg-background border border-border text-foreground rounded-md hover:bg-muted transition text-sm font-medium">
                                        不同會員等級專享
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Upcoming Bookings Section -->
            <section class="mb-8">
                <div class="mb-4">
                    <h2 class="text-2xl font-semibold">My Bookings</h2>
                </div>

                <!-- Booking Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Booking Card 1 - Confirmed -->
                    <div class="bg-card border border-border rounded-lg overflow-hidden hover:shadow-lg transition">
                        <div class="p-4 border-l-4 border-l-green-500 bg-green-50/50">
                            <!-- Status Badge -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-foreground">雙人獨木舟探險 - 日落巡遊</h3>
                                    <p class="text-sm text-muted-foreground flex items-center gap-1 mt-1">
                                        <i class="fas fa-map-pin"></i>馬尼拉灣
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded">
                                    CONFIRMED
                                </span>
                            </div>

                            <!-- Details -->
                            <div class="space-y-2 text-sm">
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>2026-02-15
                                </p>
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-clock"></i>14:00 - 18:00 (4小時)
                                </p>
                            </div>

                            <!-- Price and Actions -->
                            <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                                <span class="font-semibold">$2,980</span>
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-xs border border-border rounded hover:bg-muted transition">
                                        改期
                                    </button>
                                    <button class="px-3 py-1 text-xs border border-destructive text-destructive rounded hover:bg-destructive/10 transition">
                                        取消
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Card 2 - Pending -->
                    <div class="bg-card border border-border rounded-lg overflow-hidden hover:shadow-lg transition">
                        <div class="p-4 border-l-4 border-l-amber-500 bg-amber-50/50">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-foreground">釣魚獨木舟體驗</h3>
                                    <p class="text-sm text-muted-foreground flex items-center gap-1 mt-1">
                                        <i class="fas fa-map-pin"></i>紅樹林生態區
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-amber-500 text-white text-xs font-semibold rounded">
                                    PENDING
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>2026-02-22
                                </p>
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-clock"></i>08:00 - 12:00 (4小時)
                                </p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                                <span class="font-semibold">$1,800</span>
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-xs border border-border rounded hover:bg-muted transition">
                                        改期
                                    </button>
                                    <button class="px-3 py-1 text-xs border border-destructive text-destructive rounded hover:bg-destructive/10 transition">
                                        取消
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Card 3 - Processing -->
                    <div class="bg-card border border-border rounded-lg overflow-hidden hover:shadow-lg transition">
                        <div class="p-4 border-l-4 border-l-blue-500 bg-blue-50/50">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-foreground">海洋生物觀察之旅</h3>
                                    <p class="text-sm text-muted-foreground flex items-center gap-1 mt-1">
                                        <i class="fas fa-map-pin"></i>珊瑚礁保護區
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-blue-500 text-white text-xs font-semibold rounded">
                                    PROCESSING
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>2026-03-01
                                </p>
                                <p class="text-muted-foreground flex items-center gap-1">
                                    <i class="fas fa-clock"></i>09:00 - 13:00 (4小時)
                                </p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                                <span class="font-semibold">$2,500</span>
                                <div class="flex gap-2">
                                    <button class="px-3 py-1 text-xs border border-border rounded hover:bg-muted transition">
                                        改期
                                    </button>
                                    <button class="px-3 py-1 text-xs border border-destructive text-destructive rounded hover:bg-destructive/10 transition">
                                        取消
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Loyalty & Points Section -->
            <section class="mb-8">
                <div class="mb-4">
                    <h2 class="text-2xl font-semibold">Loyalty Dashboard</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Points Card -->
                    <div class="bg-card border border-border rounded-lg p-6 text-center">
                        <p class="text-sm text-muted-foreground uppercase tracking-wide mb-2">Points Balance</p>
                        <p class="text-4xl font-bold text-primary mb-1">850</p>
                        <p class="text-xs text-muted-foreground">points</p>
                    </div>

                    <!-- Membership Tier Card -->
                    <div class="bg-card border border-border rounded-lg p-6 text-center">
                        <p class="text-sm text-muted-foreground uppercase tracking-wide mb-2">Membership Tier</p>
                        <p class="text-3xl font-bold text-foreground mb-1">⭐ Silver</p>
                        <p class="text-xs text-muted-foreground">銀牌會員</p>
                    </div>
                </div>
            </section>

            <!-- 4. Recommended Products Section -->
            <section class="bg-black text-white py-16 -mx-4 sm:-mx-6 lg:-mx-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Section Header -->
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-light mb-4">推薦購買</h2>
                        <p class="text-gray-400 text-lg">專為海洋而生的時尚泳裝</p>
                    </div>

                    <!-- Product Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <!-- Product 1 -->
                        <div class="group">
                            <div class="relative aspect-[3/4] overflow-hidden bg-gray-900 mb-4">
                                <img 
                                    src="https://images.unsplash.com/photo-1630406440709-80170ed7dfbb?w=400&h=500&fit=crop" 
                                    alt="優雅連身泳衣"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                >
                                <button class="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-heart text-black"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <p class="font-light text-lg mb-2">優雅連身泳衣</p>
                                <p class="text-gray-400">
                                    <span class="text-white font-semibold">$128</span>
                                    <span class="line-through text-sm">$160</span>
                                </p>
                            </div>
                        </div>

                        <!-- Product 2 -->
                        <div class="group">
                            <div class="relative aspect-[3/4] overflow-hidden bg-gray-900 mb-4">
                                <img 
                                    src="https://images.unsplash.com/photo-1575232707828-11f149efc281?w=400&h=500&fit=crop" 
                                    alt="專業防曬泳衣"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                >
                                <button class="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-heart text-black"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <p class="font-light text-lg mb-2">專業防曬泳衣</p>
                                <p class="text-gray-400">
                                    <span class="text-white font-semibold">$98</span>
                                    <span class="line-through text-sm">$125</span>
                                </p>
                            </div>
                        </div>

                        <!-- Product 3 -->
                        <div class="group">
                            <div class="relative aspect-[3/4] overflow-hidden bg-gray-900 mb-4">
                                <img 
                                    src="https://images.unsplash.com/photo-1560660019-7625c7e27d91?w=400&h=500&fit=crop" 
                                    alt="經典比基尼套裝"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                >
                                <button class="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-heart text-black"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <p class="font-light text-lg mb-2">經典比基尼套裝</p>
                                <p class="text-gray-400">
                                    <span class="text-white font-semibold">$88</span>
                                    <span class="line-through text-sm">$110</span>
                                </p>
                            </div>
                        </div>

                        <!-- Product 4 -->
                        <div class="group">
                            <div class="relative aspect-[3/4] overflow-hidden bg-gray-900 mb-4">
                                <img 
                                    src="https://images.unsplash.com/photo-1624296841740-d7255c8508cc?w=400&h=500&fit=crop" 
                                    alt="運動型泳衣"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                >
                                <button class="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-heart text-black"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <p class="font-light text-lg mb-2">運動型泳衣</p>
                                <p class="text-gray-400">
                                    <span class="text-white font-semibold">$115</span>
                                    <span class="line-through text-sm">$145</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Minimal CSS for reference -->
    <style>
        :root {
            --primary: #3b82f6;
            --foreground: #1f2937;
            --background: #ffffff;
            --card: #ffffff;
            --border: #e5e7eb;
            --muted-foreground: #6b7280;
            --destructive: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--foreground);
            background: #f9fafb;
        }

        .min-h-screen { min-height: 100vh; }
        .bg-background { background: var(--background); }
        .bg-card { background: var(--card); }
        .text-foreground { color: var(--foreground); }
        .text-muted-foreground { color: var(--muted-foreground); }
        .text-primary { color: var(--primary); }
        .text-white { color: white; }
        .text-destructive { color: var(--destructive); }

        .border { border: 1px solid var(--border); }
        .border-border { border-color: var(--border); }
        .border-l-4 { border-left-width: 4px; }
        .border-l-green-500 { border-left-color: #10b981; }
        .border-l-amber-500 { border-left-color: #f59e0b; }
        .border-l-blue-500 { border-left-color: #3b82f6; }
        .border-t { border-top: 1px solid var(--border); }
        .border-b { border-bottom: 1px solid var(--border); }

        .rounded { border-radius: 0.375rem; }
        .rounded-lg { border-radius: 0.5rem; }
        .rounded-full { border-radius: 9999px; }

        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }

        .m-0 { margin: 0; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mb-12 { margin-bottom: 3rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }

        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .flex-row { flex-direction: row; }
        .flex-1 { flex: 1; }
        .flex-shrink-0 { flex-shrink: 0; }
        .gap-1 { gap: 0.25rem; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .gap-6 { gap: 1.5rem; }
        .gap-8 { gap: 2rem; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }

        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .gap-4 { gap: 1rem; }

        @media (min-width: 768px) {
            .md\:flex-row { flex-direction: row; }
            .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .md\:text-4xl { font-size: 2.25rem; }
        }

        @media (min-width: 1024px) {
            .lg\:px-8 { padding-left: 2rem; padding-right: 2rem; }
            .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        h1 { font-size: 2rem; line-height: 1.2; }
        h2 { font-size: 1.5rem; font-weight: 600; }
        h3 { font-weight: 600; }
        p { line-height: 1.5; }

        .font-light { font-weight: 300; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }

        .text-xs { font-size: 0.75rem; }
        .text-sm { font-size: 0.875rem; }
        .text-lg { font-size: 1.125rem; }
        .text-2xl { font-size: 1.5rem; }
        .text-3xl { font-size: 1.875rem; }
        .text-4xl { font-size: 2.25rem; }

        .hover\:bg-muted:hover { background: #f3f4f6; }
        .hover\:bg-primary\/90:hover { background: rgb(37 99 235 / 0.9); }
        .hover\:scale-105:hover { transform: scale(1.05); }
        .hover\:shadow-lg:hover { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        .hover\:text-destructive:hover { color: var(--destructive); }

        .transition { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .transition-transform { transition-property: transform; }
        .transition-opacity { transition-property: opacity; }
        .duration-300 { transition-duration: 300ms; }
        .duration-700 { transition-duration: 700ms; }

        .opacity-0 { opacity: 0; }

        .overflow-hidden { overflow: hidden; }
        .object-cover { object-fit: cover; }

        .uppercase { text-transform: uppercase; }
        .tracking-wide { letter-spacing: 0.05em; }

        .bg-green-500 { background: #10b981; }
        .bg-green-50\/50 { background: rgb(240 253 250 / 0.5); }
        .bg-amber-500 { background: #f59e0b; }
        .bg-amber-50\/50 { background: rgb(254 252 232 / 0.5); }
        .bg-blue-500 { background: #3b82f6; }
        .bg-blue-50\/50 { background: rgb(239 246 255 / 0.5); }
        .bg-gray-900 { background: #111827; }
        .bg-gray-400 { background: #9ca3af; }
        .bg-black { background: #000; }
        .bg-white { background: #fff; }
        .bg-white\/90 { background: rgb(255 255 255 / 0.9); }

        .w-full { width: 100%; }
        .w-10 { width: 2.5rem; }
        .w-32 { width: 8rem; }
        .h-2 { height: 0.5rem; }
        .h-10 { height: 2.5rem; }
        .h-32 { height: 8rem; }
        .h-full { height: 100%; }
        .h-16 { height: 4rem; }

        .max-w-7xl { max-width: 80rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        .space-y-2 > * + * { margin-top: 0.5rem; }

        .bg-primary\/5 { background: rgb(59 130 246 / 0.05); }
        .bg-primary\/10 { background: rgb(59 130 246 / 0.1); }

        .border-l { border-left: 1px solid var(--border); }

        .line-through { text-decoration: line-through; }
        .relative { position: relative; }
        .absolute { position: absolute; }

        .-mx-4 { margin-left: -1rem; margin-right: -1rem; }
        .-mx-6 { margin-left: -1.5rem; margin-right: -1.5rem; }
        .-mx-8 { margin-left: -2rem; margin-right: -2rem; }

        .aspect-\[3\/4\] { aspect-ratio: 3 / 4; }

        .shadow-lg { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }

        button {
            font-family: inherit;
        }

        button:hover {
            opacity: 1;
        }

        img {
            display: block;
        }
    </style>
</body>
</html>
```

---

## 設計特點（基於 fig-tem1 參考）

### 1. **歡迎卡片** (Welcome Card)
- 頭像編輯按鈕（懸浮）
- 漸層背景
- 簡潔的歡迎文案
- 積分進度條
- 操作按鈕組

### 2. **預約卡片列表** (Booking Cards)
- 左邊框顏色標示狀態（綠/橙/藍）
- 狀態徽章
- 信息圖標（日期、時間、地點）
- 改期/取消按鈕
- Hover 效果

### 3. **忠誠度面板** (Loyalty Dashboard)
- 2 列網格（響應式 1 列）
- 簡潔的中心對齊設計
- 大號數字展示

### 4. **推薦商品** (Recommended Products)
- 全黑背景
- 圖片為主
- Hover 縮放效果
- 收藏按鈕
- 簡潔的價格展示

---

## Tailwind CSS 概念對應

| Tailwind Class | 說明 |
|---|---|
| `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4` | 響應式網格 |
| `group-hover:scale-105` | 組內懸停效果 |
| `transition-transform duration-700` | 平滑過渡 |
| `aspect-[3/4]` | 3:4 比例 |
| `bg-gradient-to-r from-primary/5 to-primary/10` | 漸層背景 |

---

請審核這個 HTML 模板是否符合您的期望。如有調整，請告知具體位置和需改進的部分。
