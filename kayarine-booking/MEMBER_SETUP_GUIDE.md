# Kayarine 會員中心頁面設置指南

## 🚨 您遇到的三個問題及解決方案

### 問題 1：登入後跳轉到 https://kayarine.club/member/ → 404

**原因**：`/member/` 頁面不存在或未配置任何內容

**解決方案（二選一）：**

#### 方案 A：創建「會員中心」頁面（推薦）
1. 進入 WordPress 後台 → 頁面 → 新增頁面
2. 頁面標題：「會員中心」或「My Account」
3. 頁面 URL（固定連結）：`member`（系統會自動變成 `/member/`）
4. 頁面內容中添加短代碼：
   ```
   [kayarine_member_dashboard_v2]
   ```
5. 發布頁面
6. 記住此頁面的新 URL，複製它

#### 方案 B：使用 WooCommerce My Account 頁面
1. 進入 WordPress 後台 → 設定 → 閱讀
2. 檢查「靜態首頁」和「文章頁面」設定
3. 進入 WooCommerce → 設定 → 帳戶 → 我的帳戶頁面
4. 確保「我的帳戶」頁面已配置
5. 頁面內容中添加短代碼：`[kayarine_member_dashboard_v2]`

---

### 問題 2：「前往註冊頁面 →」導向 ?page_id=30 → 404

**原因**：頁面 ID 30 不存在

**解決方案（三選一）：**

#### 方案 A：建立新「會員註冊」頁面（推薦）
1. 進入 WordPress 後台 → 頁面 → 新增頁面
2. 頁面標題：「會員註冊」
3. 頁面 URL（固定連結）：`register`
4. 頁面內容：**留空**（WooCommerce 會自動顯示註冊表單）
5. 發布頁面
6. **複製頁面 URL**（例如：`https://kayarine.club/register/`）
7. 編輯 `class-kayarine-auth-integration.php` 第 190 行
   ```php
   $my_account_url = 'https://kayarine.club/register/'; // 替換為您複製的 URL
   ```

#### 方案 B：使用 WooCommerce My Account（帶 Tab）
1. 進入 WordPress 後台 → WooCommerce → 設定 → 帳戶
2. 確認「允許客戶在 My Account 頁面建立帳戶」已啟用
3. 使用 My Account URL（通常是 `/my-account/`）
4. 編輯 `class-kayarine-auth-integration.php` 第 190 行
   ```php
   $my_account_url = get_permalink( get_option('woocommerce_myaccount_page_id') ) . '?tab=register';
   ```

#### 方案 C：修復現有頁面 ID 30
1. 進入 WordPress 後台 → 頁面
2. 搜索頁面 ID 30（可以在 URL 中查看）
3. 如果頁面存在但標題為空，編輯並填入標題和內容
4. 如果頁面不存在，跳過此方案

---

### 問題 3：需要創建完整的頁面結構

**推薦的完整設置：**

#### 步驟 1：建立必要的頁面

| 頁面名稱 | 固定連結 | 內容 | 說明 |
|---------|--------|------|------|
| 會員中心 | `/member/` | `[kayarine_member_dashboard_v2]` | 已登入用戶看到儀表板 |
| 會員登入 | `/login/` | `[kayarine_login_register]` | 登錄/註冊界面（推薦） |
| 會員註冊 | `/register/` | 留空 | WooCommerce 自動顯示註冊表單 |

#### 步驟 2：更新短代碼配置

編輯 `class-kayarine-auth-integration.php` 第 136 和 190 行：

```php
// 第 136 行 - 登入後的重定向 URL
$redirect_url = isset( $_REQUEST['redirect_to'] ) ? esc_url( $_REQUEST['redirect_to'] ) : home_url( '/member/' );

// 第 190 行 - 註冊頁面 URL
$my_account_url = home_url( '/register/' ); // 改為您建立的註冊頁面
```

#### 步驟 3：配置 WordPress 設定

進入 WordPress 後台 → 設定 → 一般：
- ✅ 允許任何人註冊：勾選
- ✅ 新使用者預設角色：Customer

進入 WordPress 後台 → WooCommerce → 設定 → 帳戶：
- ✅ 允許客戶在結帳時建立帳戶：勾選

#### 步驟 4：永久連結設定

進入 WordPress 後台 → 設定 → 永久連結：
- 選擇「文章名稱」或「自訂結構」（不要用數字 ID）
- 點擊「儲存變更」

---

## 📝 具體步驟（完整流程）

### 1️⃣ 建立「會員中心」頁面
```
標題：會員中心
固定連結：member
內容：[kayarine_member_dashboard_v2]
狀態：發布
```

### 2️⃣ 建立「會員登入」頁面（可選）
```
標題：會員登入
固定連結：login
內容：[kayarine_login_register]
狀態：發布
```

### 3️⃣ 建立「會員註冊」頁面
```
標題：會員註冊
固定連結：register
內容：（留空，WooCommerce 自動顯示表單）
狀態：發布
```

### 4️⃣ 更新程式碼

編輯 `kayarine-booking/includes/class-kayarine-auth-integration.php`：

**第 136 行**：
```php
$redirect_url = isset( $_REQUEST['redirect_to'] ) ? esc_url( $_REQUEST['redirect_to'] ) : home_url( '/member/' );
```

**第 190 行**：
```php
$my_account_url = home_url( '/register/' );
```

### 5️⃣ 刷新永久連結

進入 WordPress 後台 → 設定 → 永久連結 → 點擊「儲存變更」

### 6️⃣ 清除快取並測試

- 清除瀏覽器快取
- 清除 WordPress 快取外掛
- 訪問新建立的頁面確認無 404

---

## 🔗 測試流程

1. **訪問登入頁面**
   ```
   https://kayarine.club/login/
   ```
   應該看到：標籤頁登入/註冊介面

2. **點擊「免費註冊」標籤頁**
   應該看到：會員權益卡片 + 「前往註冊頁面」按鈕

3. **點擊「前往註冊頁面」按鈕**
   應該跳轉到：`https://kayarine.club/register/`（WooCommerce 註冊表單）

4. **完成註冊後**
   應該跳轉到：`https://kayarine.club/member/`（會員儀表板）

5. **在會員儀表板**
   應該看到：用戶資訊、預約列表、改期/取消按鈕

---

## ⚠️ 常見問題

**Q1：頁面建立後仍然 404**
A：
1. 清除 WordPress 快取
2. 進入設定 → 永久連結 → 再次儲存
3. 檢查固定連結是否正確（不要包含 `/` 前綴）

**Q2：「前往註冊頁面」按鈕仍然導向舊頁面**
A：
1. 編輯 `class-kayarine-auth-integration.php` 確認第 190 行已更新
2. 重新上傳檔案到伺服器
3. 清除所有快取

**Q3：登入後沒有重定向到 /member/**
A：
1. 確認 `class-kayarine-auth-integration.php` 第 136 行的重定向 URL 正確
2. 檢查 `/member/` 頁面是否存在且已發布
3. 檢查頁面是否公開（不是受密碼保護）

---

## 📦 所有需要修改的檔案

| 檔案 | 修改內容 | 優先級 |
|-----|--------|--------|
| `class-kayarine-auth-integration.php` | 第 136、190 行 URL 配置 | ⭐⭐⭐ 高 |
| `class-kayarine-member-dashboard-v2.php` | 色彩方案（可選）| ⭐ 低 |
| WordPress 頁面 | 建立 3 個新頁面 | ⭐⭐⭐ 高 |
| WordPress 設定 | 啟用註冊和永久連結 | ⭐⭐⭐ 高 |

---

## 🚀 最快的解決方案（10 分鐘）

1. **建立三個頁面**（3 分鐘）
   - 會員中心：`[kayarine_member_dashboard_v2]`
   - 會員登入：`[kayarine_login_register]`
   - 會員註冊：（空白）

2. **複製新頁面 URL**（1 分鐘）
   - /member/
   - /register/

3. **更新代碼**（2 分鐘）
   - 編輯 class-kayarine-auth-integration.php
   - 第 136 行：`home_url('/member/')`
   - 第 190 行：`home_url('/register/')`

4. **刷新永久連結**（1 分鐘）
   - 設定 → 永久連結 → 儲存

5. **測試**（3 分鐘）
   - 訪問頁面確認無 404
   - 測試登入/註冊流程

完成！✅

