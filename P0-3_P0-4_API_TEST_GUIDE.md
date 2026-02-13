# P0-3 & P0-4 API 測試指南

**版本**: v2.5.1  
**功能**: 忘記密碼 (P0-3) + 註冊驗證碼 (P0-4)  
**日期**: 2026-02-08

---

## 📋 API 端點總覽

| 端點 | 方法 | 功能 | 狀態 |
|------|------|------|------|
| `/auth/send-otp` | POST | 發送註冊驗證碼 | ✅ |
| `/auth/verify-otp` | POST | 驗證註冊 OTP | ✅ |
| `/auth/forgot-password` | POST | 發送密碼重設 OTP | ✅ |
| `/auth/verify-reset-otp` | POST | 驗證密碼重設 OTP | ✅ |
| `/auth/reset-password` | POST | 重設密碼 | ✅ |

**基礎 URL**: `https://kayarine.club/wp-json/kayarine/v1`

---

## 🧪 測試場景

### **場景 1：註冊驗證碼流程（P0-4）**

#### 步驟 1：發送註冊 OTP

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "驗證碼已發送到您的電子郵件",
  "dev_otp": "123456",
  "expires_in": 600
}
```

**預期失敗響應（Email 已存在）**:
```json
{
  "code": "email_exists",
  "message": "此電子郵件已被註冊，請直接登入",
  "data": { "status": 400 }
}
```

---

#### 步驟 2：驗證註冊 OTP

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com",
    "otp_code": "123456"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "驗證成功，請完成註冊",
  "verified": true
}
```

**預期失敗響應（OTP 無效）**:
```json
{
  "code": "otp_invalid",
  "message": "驗證碼無效或已使用",
  "data": { "status": 400 }
}
```

**預期失敗響應（OTP 過期）**:
```json
{
  "code": "otp_expired",
  "message": "驗證碼已過期，請重新獲取",
  "data": { "status": 400 }
}
```

---

#### 步驟 3：完成註冊（帶 OTP）

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "Password123",
    "otp_code": "123456"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "註冊成功！請登入您的帳戶",
  "data": {
    "user_id": 42,
    "username": "newuser_example.com",
    "email": "newuser@example.com"
  }
}
```

---

### **場景 2：忘記密碼流程（P0-3）**

#### 步驟 1：發送密碼重設 OTP

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "existing@example.com"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "密碼重設驗證碼已發送到您的電子郵件",
  "dev_otp": "654321",
  "expires_in": 600
}
```

**注意**: 即使 Email 不存在，也會返回成功（安全考慮）

---

#### 步驟 2：驗證密碼重設 OTP

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/verify-reset-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "existing@example.com",
    "otp_code": "654321"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "驗證成功，請設定新密碼",
  "verified": true
}
```

---

#### 步驟 3：重設密碼

```bash
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "existing@example.com",
    "otp_code": "654321",
    "new_password": "NewPassword123"
  }'
```

**預期成功響應**:
```json
{
  "success": true,
  "message": "密碼重設成功，請使用新密碼登入"
}
```

**安全特性**: 重設後所有現有 session 會被清除，用戶需重新登入

---

### **場景 3：防濫用測試**

#### 測試冷卻期（60 秒）

```bash
# 第一次請求
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'

# 立即第二次請求（應失敗）
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'
```

**預期失敗響應**:
```json
{
  "code": "otp_cooldown",
  "message": "請等待 60 秒後再重新獲取驗證碼",
  "data": { "status": 429 }
}
```

---

### **場景 4：OTP 過期測試**

```bash
# 發送 OTP
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'

# 等待 10 分鐘後驗證（應失敗）
sleep 600
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "otp_code": "123456"}'
```

**預期失敗響應**:
```json
{
  "code": "otp_expired",
  "message": "驗證碼已過期，請重新獲取",
  "data": { "status": 400 }
}
```

---

### **場景 5：密碼強度測試**

```bash
# 使用弱密碼重設
curl -X POST https://kayarine.club/wp-json/kayarine/v1/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp_code": "123456",
    "new_password": "weak"
  }'
```

**預期失敗響應**:
```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): new_password",
  "data": { "status": 400 }
}
```

**密碼要求**:
- 最少 8 個字符
- 至少 1 個大寫字母
- 至少 1 個數字

---

## 🗄️ 資料庫查詢

### 檢查 OTP 表

```sql
-- 連接資料庫
ssh kayarine.server@104.199.144.122
mysql -u kayarine -p wordpress_db

-- 查看所有 OTP 記錄
SELECT * FROM wp_kayarine_otp ORDER BY created_at DESC LIMIT 10;

-- 查看特定 Email 的 OTP
SELECT * FROM wp_kayarine_otp 
WHERE email = 'test@example.com' 
ORDER BY created_at DESC;

-- 統計 OTP 類型
SELECT otp_type, COUNT(*) as count 
FROM wp_kayarine_otp 
GROUP BY otp_type;

-- 查看過期的 OTP
SELECT * FROM wp_kayarine_otp 
WHERE expires_at < NOW();

-- 查看已驗證的 OTP
SELECT * FROM wp_kayarine_otp 
WHERE verified = 1 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 📊 測試檢查清單

### **註冊驗證碼（P0-4）**
- [ ] ✅ 發送 OTP 成功
- [ ] ✅ 收到 6 位數字 OTP
- [ ] ✅ OTP 10 分鐘內有效
- [ ] ✅ 驗證正確 OTP 成功
- [ ] ✅ 驗證錯誤 OTP 失敗
- [ ] ✅ OTP 過期後驗證失敗
- [ ] ✅ 60 秒冷卻期生效
- [ ] ✅ Email 已存在時返回錯誤
- [ ] ✅ 註冊時使用 OTP 成功
- [ ] ✅ OTP 不能重複使用

### **忘記密碼（P0-3）**
- [ ] ✅ 發送重設 OTP 成功
- [ ] ✅ Email 不存在時也返回成功（安全）
- [ ] ✅ 驗證重設 OTP 成功
- [ ] ✅ 重設密碼成功
- [ ] ✅ 重設後舊 session 失效
- [ ] ✅ 弱密碼被拒絕
- [ ] ✅ OTP 驗證後才能重設密碼
- [ ] ✅ 重設 OTP 不能用於註冊

### **安全性測試**
- [ ] ✅ 防止暴力破解（冷卻期）
- [ ] ✅ OTP 單次使用
- [ ] ✅ OTP 自動過期
- [ ] ✅ 不洩漏用戶存在信息
- [ ] ✅ 密碼強度檢查
- [ ] ✅ Session 管理正確

### **資料庫測試**
- [ ] ✅ 表自動創建
- [ ] ✅ 索引正常工作
- [ ] ✅ 過期記錄自動清理
- [ ] ✅ 舊 OTP 自動失效

---

## 🐛 錯誤代碼對照表

| 錯誤代碼 | HTTP 狀態 | 說明 | 解決方案 |
|---------|----------|------|---------|
| `email_exists` | 400 | Email 已註冊 | 使用其他 Email 或直接登入 |
| `otp_cooldown` | 429 | 冷卻期內 | 等待 60 秒後重試 |
| `otp_invalid` | 400 | OTP 無效或已使用 | 重新獲取 OTP |
| `otp_expired` | 400 | OTP 已過期 | 重新獲取 OTP |
| `invalid_email` | 400 | Email 格式錯誤 | 檢查 Email 格式 |
| `user_not_found` | 404 | 用戶不存在 | 檢查 Email 是否正確 |
| `rest_invalid_param` | 400 | 參數驗證失敗 | 檢查參數格式和要求 |

---

## 🔍 日誌查詢

### 查看 OTP 相關日誌

```bash
ssh kayarine.server@104.199.144.122
tail -f ~/wordpress/wp-content/debug.log | grep "Kayarine OTP"
```

**日誌示例**:
```
[Kayarine OTP] Table created or verified: wp_kayarine_otp
[Kayarine OTP] Generated OTP for test@example.com: 123456 (Type: registration, Expires: 2026-02-08 18:40:00)
[Kayarine OTP] OTP verified successfully for test@example.com
[Kayarine Auth] Sending registration OTP to: test@example.com
[Kayarine Auth] Registration OTP generated: 123456
```

---

## 📧 待完成：Email 整合（P0-1）

目前 OTP 生成成功但尚未發送郵件。完成 P0-1 後需要：

1. **選擇 SMTP 服務**：Mailgun / SendGrid / AWS SES
2. **設計郵件模板**：HTML + 純文字版本
3. **整合到 OTP 系統**：
   ```php
   // class-kayarine-otp.php
   $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
   
   // 發送郵件
   Kayarine_Emails::send_otp_email($email, $otp_code, $type);
   ```

4. **移除開發模式 OTP 顯示**：
   ```php
   // 生產環境不返回 dev_otp
   return rest_ensure_response(array(
       'success' => true,
       'message' => '驗證碼已發送到您的電子郵件',
       'expires_in' => Kayarine_OTP::OTP_EXPIRY
       // 'dev_otp' => $otp_code  // 移除此行
   ));
   ```

---

## 🎯 快速測試腳本

### Bash 腳本

```bash
#!/bin/bash
BASE_URL="https://kayarine.club/wp-json/kayarine/v1"
EMAIL="test$(date +%s)@example.com"

echo "=== 測試註冊驗證碼流程 ==="
echo "1. 發送註冊 OTP..."
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/send-otp" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\"}")
echo $RESPONSE | jq .

OTP=$(echo $RESPONSE | jq -r '.dev_otp')
echo "OTP: $OTP"

echo "2. 驗證 OTP..."
curl -s -X POST "$BASE_URL/auth/verify-otp" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"otp_code\":\"$OTP\"}" | jq .

echo "3. 註冊用戶..."
curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Test User\",\"email\":\"$EMAIL\",\"password\":\"Password123\",\"otp_code\":\"$OTP\"}" | jq .
```

---

## 📝 總結

### **已完成**
- ✅ OTP 驗證系統（6 位數字，10 分鐘有效期）
- ✅ 防濫用機制（60 秒冷卻期）
- ✅ 註冊驗證碼 API（P0-4）
- ✅ 忘記密碼 API（P0-3）
- ✅ 資料庫自動管理
- ✅ 完整的錯誤處理

### **待完成**
- ⏳ Email 系統整合（P0-1）
- ⏳ 前端 UI 組件（Next.js）
- ⏳ 生產環境部署

### **測試狀態**
- 🔵 本地測試：待測試
- 🔵 Staging 測試：待測試
- 🔵 生產環境：待部署

---

**最後更新**: 2026-02-08T18:30 UTC+8  
**版本**: v2.5.1  
**負責人**: Kayarine Team
