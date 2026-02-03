# Cloudflare DNS 配置參考

## 狀態
✅ 配置已完成，短期內無需變動

---

## 當前配置

### 域名
- **主域名**：kayarine.club
- **次級域名**：kayarine.com.hk (重定向)

### DNS 記錄

| 記錄 | 類型 | 值 | 備註 |
|------|------|-----|------|
| @ | A | 104.199.144.122 | GCP 主機 IP |
| www | CNAME | kayarine.club | Web 別名 |

### Nameservers
- ns1.kayarine.com.hk
- ns2.kayarine.com.hk

---

## 地理限制

✅ 已配置：僅限香港地區訪問

---

## DDoS 防護

- 已啟用 DDoS 防護
- 安全等級：中等

---

## 快取設置

- 頁面規則：靜態資源快取 24 小時
- 旁路快取：WordPress 後台及購物車頁面

---

## 如需修改

聯絡 Cloudflare 支援或直接在面板修改。詳細步驟參考相關配置文檔。
