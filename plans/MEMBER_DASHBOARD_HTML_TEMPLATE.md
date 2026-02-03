# Kayarine Member Dashboard - HTML 模板示例

**版本**：1.0  
**用途**：展示 PHP 輸出的 HTML 結構和 CSS 類名  
**集成方式**：嵌入至 WordPress 短代碼 `[kayarine_member_dashboard]`

---

## 📝 HTML 結構概覽

```html
<div class="kayarine-member-dashboard">
    <!-- 1️⃣ 歡迎卡片 -->
    <section class="kmd-welcome-card">
        <div class="kmd-avatar-group">
            <div class="kmd-avatar">
                <img src="user-avatar.jpg" alt="Profile">
                <button class="kmd-avatar-edit">✎</button>
            </div>
        </div>
        
        <div class="kmd-welcome-content">
            <h1 class="kmd-welcome-title">
                歡迎回來，<strong>王小明</strong>！
            </h1>
            
            <p class="kmd-achievement">
                你今年已出海了 <strong>5 次</strong> 🏆
            </p>
            
            <div class="kmd-progress-section">
                <div class="kmd-progress-label">
                    <span>積分進度</span>
                    <span class="kmd-points">850 points</span>
                </div>
                <div class="kmd-progress-bar">
                    <div class="kmd-progress-fill" style="width: 70%;"></div>
                </div>
                <p class="kmd-progress-hint">150 more points to unlock Gold Membership rewards</p>
            </div>
            
            <div class="kmd-button-group">
                <button class="kmd-btn kmd-btn-primary">編輯個人資料</button>
                <button class="kmd-btn">查看成就徽章</button>
                <button class="kmd-btn">會員等級專享</button>
            </div>
        </div>
    </section>

    <!-- 2️⃣ 我的預約 -->
    <section class="kmd-bookings-section">
        <h2 class="kmd-section-title">我的預約</h2>
        
        <div class="kmd-bookings-list">
            <!-- 單個預約卡片 -->
            <div class="kmd-booking-card kmd-booking-status-completed">
                <div class="kmd-booking-info">
                    <h3 class="kmd-booking-title">雙人獨木舟探險 - 日落巡遊</h3>
                    <p class="kmd-booking-detail">📍 馬尼拉灣</p>
                </div>
                
                <div class="kmd-booking-info">
                    <p class="kmd-booking-detail">📅 2026-02-15</p>
                    <p class="kmd-booking-detail">🕐 14:00 - 18:00 (4小時)</p>
                </div>
                
                <div class="kmd-booking-amount">$2,980</div>
                
                <div class="kmd-booking-actions">
                    <button class="kmd-btn-small" data-action="reschedule" data-order-id="123">改期</button>
                    <button class="kmd-btn-small kmd-btn-danger" data-action="cancel" data-order-id="123">取消</button>
                </div>
                
                <span class="kmd-booking-status-badge kmd-status-completed">已確認</span>
            </div>
            
            <!-- 預約卡片 - Pending -->
            <div class="kmd-booking-card kmd-booking-status-pending">
                <div class="kmd-booking-info">
                    <h3 class="kmd-booking-title">釣魚獨木舟體驗</h3>
                    <p class="kmd-booking-detail">📍 紅樹林生態區</p>
                </div>
                
                <div class="kmd-booking-info">
                    <p class="kmd-booking-detail">📅 2026-02-22</p>
                    <p class="kmd-booking-detail">🕐 08:00 - 12:00 (4小時)</p>
                </div>
                
                <div class="kmd-booking-amount">$1,800</div>
                
                <div class="kmd-booking-actions">
                    <button class="kmd-btn-small" data-action="reschedule" data-order-id="124">改期</button>
                    <button class="kmd-btn-small kmd-btn-danger" data-action="cancel" data-order-id="124">取消</button>
                </div>
                
                <span class="kmd-booking-status-badge kmd-status-pending">待確認</span>
            </div>
            
            <!-- 預約卡片 - Processing -->
            <div class="kmd-booking-card kmd-booking-status-processing">
                <div class="kmd-booking-info">
                    <h3 class="kmd-booking-title">海洋生物觀察之旅</h3>
                    <p class="kmd-booking-detail">📍 珊瑚礁保護區</p>
                </div>
                
                <div class="kmd-booking-info">
                    <p class="kmd-booking-detail">📅 2026-03-01</p>
                    <p class="kmd-booking-detail">🕐 09:00 - 13:00 (4小時)</p>
                </div>
                
                <div class="kmd-booking-amount">$2,500</div>
                
                <div class="kmd-booking-actions">
                    <button class="kmd-btn-small" data-action="reschedule" data-order-id="125">改期</button>
                    <button class="kmd-btn-small kmd-btn-danger" data-action="cancel" data-order-id="125">取消</button>
                </div>
                
                <span class="kmd-booking-status-badge kmd-status-processing">處理中</span>
            </div>
        </div>
    </section>

    <!-- 3️⃣ 忠誠度面板 -->
    <section class="kmd-loyalty-section">
        <h2 class="kmd-section-title">忠誠度面板</h2>
        
        <div class="kmd-loyalty-grid">
            <!-- 積分卡片 -->
            <div class="kmd-loyalty-card">
                <p class="kmd-loyalty-label">積分餘額</p>
                <div class="kmd-loyalty-value">850</div>
                <p class="kmd-loyalty-unit">points</p>
            </div>
            
            <!-- 會員等級卡片 -->
            <div class="kmd-loyalty-card">
                <p class="kmd-loyalty-label">會員等級</p>
                <div class="kmd-loyalty-value">⭐ Silver</div>
                <p class="kmd-loyalty-unit">銀牌會員</p>
            </div>
        </div>
    </section>

    <!-- 4️⃣ 推薦商品 -->
    <section class="kmd-recommended-section">
        <div class="kmd-section-header-center">
            <h2 class="kmd-section-title">推薦購買</h2>
            <p class="kmd-section-subtitle">專為海洋而生的時尚泳裝</p>
        </div>
        
        <div class="kmd-product-grid">
            <!-- 商品卡片 1 -->
            <div class="kmd-product-card">
                <div class="kmd-product-image">
                    <img src="product-1.jpg" alt="優雅連身泳衣">
                </div>
                <div class="kmd-product-info">
                    <p class="kmd-product-name">優雅連身泳衣</p>
                    <p class="kmd-product-price">
                        $128 
                        <span class="kmd-original-price">$160</span>
                    </p>
                </div>
            </div>
            
            <!-- 商品卡片 2 -->
            <div class="kmd-product-card">
                <div class="kmd-product-image">
                    <img src="product-2.jpg" alt="專業防曬泳衣">
                </div>
                <div class="kmd-product-info">
                    <p class="kmd-product-name">專業防曬泳衣</p>
                    <p class="kmd-product-price">
                        $98 
                        <span class="kmd-original-price">$125</span>
                    </p>
                </div>
            </div>
            
            <!-- 商品卡片 3 -->
            <div class="kmd-product-card">
                <div class="kmd-product-image">
                    <img src="product-3.jpg" alt="經典比基尼套裝">
                </div>
                <div class="kmd-product-info">
                    <p class="kmd-product-name">經典比基尼套裝</p>
                    <p class="kmd-product-price">
                        $88 
                        <span class="kmd-original-price">$110</span>
                    </p>
                </div>
            </div>
            
            <!-- 商品卡片 4 -->
            <div class="kmd-product-card">
                <div class="kmd-product-image">
                    <img src="product-4.jpg" alt="運動型泳衣">
                </div>
                <div class="kmd-product-info">
                    <p class="kmd-product-name">運動型泳衣</p>
                    <p class="kmd-product-price">
                        $115 
                        <span class="kmd-original-price">$145</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 改期模態框 (Modal) -->
    <div id="kmd-reschedule-modal" class="kmd-modal" style="display: none;">
        <div class="kmd-modal-content">
            <div class="kmd-modal-header">
                <h3>選擇新日期</h3>
                <button class="kmd-modal-close">&times;</button>
            </div>
            
            <div class="kmd-modal-body">
                <input 
                    type="text" 
                    id="kmd-reschedule-date" 
                    class="kmd-input" 
                    placeholder="選擇日期"
                >
            </div>
            
            <div class="kmd-modal-footer">
                <button class="kmd-btn kmd-btn-primary" id="kmd-confirm-reschedule">確認</button>
                <button class="kmd-btn" id="kmd-cancel-reschedule">取消</button>
            </div>
            
            <div id="kmd-reschedule-error" class="kmd-error-message"></div>
        </div>
    </div>
</div>
```

---

## 🏗️ CSS 類名系統

### 命名約定
- **前綴**：`kmd-` (Kayarine Member Dashboard)
- **結構**：`kmd-{component}-{element}`
- **修飾符**：`kmd-{state}` 或 `kmd-{variant}`

### 完整類名列表

```
容器
├─ .kayarine-member-dashboard          主容器
├─ .kmd-welcome-card                   歡迎卡片
├─ .kmd-bookings-section               預約區塊
├─ .kmd-loyalty-section                忠誠度區塊
└─ .kmd-recommended-section            推薦商品區塊

歡迎卡片
├─ .kmd-avatar-group                   頭像區域
├─ .kmd-avatar                         頭像
├─ .kmd-avatar-edit                    頭像編輯按鈕
├─ .kmd-welcome-content                內容區
├─ .kmd-welcome-title                  標題
├─ .kmd-achievement                    成就文案
├─ .kmd-progress-section               進度條區塊
├─ .kmd-progress-label                 進度標籤
├─ .kmd-progress-bar                   進度條容器
├─ .kmd-progress-fill                  進度填充
├─ .kmd-progress-hint                  進度提示
├─ .kmd-button-group                   按鈕組
├─ .kmd-btn                            按鈕基礎
├─ .kmd-btn-primary                    主按鈕
└─ .kmd-points                         積分數值

預約列表
├─ .kmd-section-title                  區塊標題
├─ .kmd-bookings-list                  預約列表容器
├─ .kmd-booking-card                   預約卡片
├─ .kmd-booking-status-completed       狀態修飾符-已完成
├─ .kmd-booking-status-pending         狀態修飾符-待確認
├─ .kmd-booking-status-processing      狀態修飾符-處理中
├─ .kmd-booking-status-cancelled       狀態修飾符-已取消
├─ .kmd-booking-info                   預約資訊區
├─ .kmd-booking-title                  預約標題
├─ .kmd-booking-detail                 預約詳情
├─ .kmd-booking-amount                 預約金額
├─ .kmd-booking-actions                操作按鈕區
├─ .kmd-btn-small                      小按鈕
├─ .kmd-btn-danger                     危險按鈕 (取消)
├─ .kmd-booking-status-badge           狀態徽章
├─ .kmd-status-completed               徽章狀態-已完成
├─ .kmd-status-pending                 徽章狀態-待確認
├─ .kmd-status-processing              徽章狀態-處理中
└─ .kmd-status-cancelled               徽章狀態-已取消

忠誠度面板
├─ .kmd-loyalty-grid                   忠誠度網格
├─ .kmd-loyalty-card                   忠誠度卡片
├─ .kmd-loyalty-label                  標籤
├─ .kmd-loyalty-value                  數值
└─ .kmd-loyalty-unit                   單位

推薦商品
├─ .kmd-section-header-center          中心標題區
├─ .kmd-section-subtitle               副標題
├─ .kmd-product-grid                   商品網格
├─ .kmd-product-card                   商品卡片
├─ .kmd-product-image                  商品圖片
├─ .kmd-product-info                   商品資訊
├─ .kmd-product-name                   商品名稱
├─ .kmd-product-price                  商品價格
└─ .kmd-original-price                 原價 (刪除線)

模態框
├─ .kmd-modal                          模態框背景
├─ .kmd-modal-content                  模態框內容
├─ .kmd-modal-header                   模態框標題
├─ .kmd-modal-close                    關閉按鈕
├─ .kmd-modal-body                     模態框主體
├─ .kmd-modal-footer                   模態框頁腳
├─ .kmd-input                          輸入框
└─ .kmd-error-message                  錯誤訊息
```

---

## 📊 數據屬性 (Data Attributes)

用於 JavaScript 互動：

```html
<!-- 改期按鈕 -->
<button 
    class="kmd-btn-small" 
    data-action="reschedule" 
    data-order-id="123"
>
    改期
</button>

<!-- 取消按鈕 -->
<button 
    class="kmd-btn-small kmd-btn-danger" 
    data-action="cancel" 
    data-order-id="123"
>
    取消
</button>
```

---

## 🔗 PHP 動態整合點

```php
// 在 PHP 中動態生成的區域

// 1. 用戶信息
<img src="<?php echo $user_avatar_url; ?>" alt="Profile">

// 2. 積分進度
<div class="kmd-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>

// 3. 預約列表
<?php foreach ($orders as $order): ?>
    <div class="kmd-booking-card kmd-booking-status-<?php echo $order->get_status(); ?>">
        <!-- ... -->
    </div>
<?php endforeach; ?>

// 4. 忠誠度數據
<div class="kmd-loyalty-value"><?php echo $user_points; ?></div>
```

---

## 🎯 實現要點

### 1. HTML 結構
- ✅ 語義化標籤 (`<section>`, `<h2>`, `<p>`)
- ✅ 統一類名前綴 `kmd-`
- ✅ 清晰的嵌套結構
- ✅ 狀態類修飾符

### 2. 預留的 data-* 屬性
- ✅ `data-action` - 按鈕操作類型
- ✅ `data-order-id` - 訂單 ID
- ✅ 便於 JavaScript 選擇和事件綁定

### 3. 模態框設計
- ✅ 隱藏狀態：`style="display: none;"`
- ✅ 日期輸入：使用 flatpickr 或 date-fns
- ✅ 錯誤提示區域

### 4. 響應式考量
- ✅ Flexbox/Grid 容器
- ✅ 斷點相關的類名可選：`.kmd-mobile-only`, `.kmd-desktop-only`
- ✅ 圖片優化：使用 `<picture>` 或 srcset

---

## 🔄 PHP 渲染流程

```
render_dashboard()
    ├─ 檢查登入狀態
    ├─ 獲取用戶數據
    │   ├─ 用戶頭像、名稱、成就
    │   ├─ 積分 + 進度
    │   └─ 會員等級
    ├─ 獲取預約訂單
    │   └─ 循環輸出每個卡片
    ├─ 獲取忠誠度數據
    ├─ 獲取推薦商品列表
    ├─ 注入 CSS + JS
    └─ 返回 ob_get_clean()
```

---

## ✨ 下一步

這個 HTML 模板將被整合到 PHP 類中：

**檔案**：`kayarine-booking/includes/class-kayarine-member-dashboard.php`

**位置**：`render_dashboard()` 方法內的 `ob_start()` 和 `ob_get_clean()` 之間

