# 服务器内存优化方案（月流量15000）

## 当前问题分析

### 内存占用分布
- **MariaDB**: 2.2GB (27.2%) - 过高
- **Apache**: ~520MB (13进程 × 40MB)
- **PHP-FPM**: ~112MB (7进程 × 16MB)
- **其他**: Gunicorn, fail2ban等

### 根本原因
1. **innodb_buffer_pool_size** 配置过高（默认 50-75% 内存）
2. **Apache MaxClients** 被 DDOS 防护提高（原本应为 10-15）
3. **PHP-FPM pm.max_children** 设置过高（应为 5-8）

---

## 优化建议方案

### 1. Apache 配置优化

**推荐设置（月流量15000）**：
```apache
# 原设置（过高）：MaxClients 256+
# 优化后设置：
MaxClients 15
MaxSpareServers 3
MinSpareServers 2
MaxConnectionsPerChild 500
```

**计算逻辑**：
- 月流量15000 ≈ 500/天 ≈ 20/小时
- 每个请求处理时间 0.5-2 秒
- 并发数 = (请求/秒) × 处理时间 = (20/3600) × 1 = 0.005 ≈ 2
- 安全缓冲：MaxClients = 10-15

---

### 2. PHP-FPM 配置优化

**推荐设置（月流量15000）**：
```ini
; 原设置：pm.max_children 50+
; 优化后设置：
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
pm.max_requests = 1000
```

**计算逻辑**：
- 并发 PHP 请求通常是 Apache MaxClients 的 20-30%
- 推荐 max_children = Apache MaxClients × 0.5 = 15 × 0.5 = 8

---

### 3. MariaDB 配置优化

**推荐设置（月流量15000）**：
```ini
; 原设置：innodb_buffer_pool_size = 1GB+ (可能配置了50-75%)
; 优化后设置（7.8GB RAM）：
innodb_buffer_pool_size = 2G          # 从 3-5GB 降至 2GB（25%）
max_connections = 30                   # 从 100+ 降至 30
innodb_log_buffer_size = 16M          # 保持
innodb_flush_log_at_trx_commit = 2    # 默认，提高性能
key_buffer_size = 256M                # MyISAM 缓冲（如有）
query_cache_size = 0                   # 禁用（性能反而更差）
query_cache_type = 0
```

**优化影响分析**：
- ✅ **好处**：
  - 每次加载表更快（缓存够用）
  - 降低内存碎片
  - 减少 OOM 风险
  - 允许更多并发连接（当前内存够用）

- ⚠️ **代价**（可接受）：
  - 高 I/O 负载时读写速度可能 5-10% 下降
  - 但月流量15000 完全可以承受

---

## 预期内存改善

**优化前**：
- MariaDB: 2.2GB (27%)
- Apache: 520MB (13进程)
- PHP-FPM: 112MB (7进程)
- **总计**: ~7.4GB (95%)

**优化后**：
- MariaDB: 2.0GB (25%)
- Apache: 200MB (5进程)
- PHP-FPM: 80MB (5进程)
- **总计**: ~2.5GB (32%)

**释放内存**: 4.9GB → 可作为 OS 缓存、文件系统缓存

---

## 实施步骤

### 步骤 1：修改 Apache 配置
```bash
sudo nano /opt/bitnami/apache/conf/httpd.conf
# 查找并修改 MaxClients 和 MaxSpareServers
```

### 步骤 2：修改 PHP-FPM 配置
```bash
sudo nano /opt/bitnami/php/etc/php-fpm.conf
# 修改 pm.max_children 等参数
```

### 步骤 3：修改 MariaDB 配置
```bash
sudo nano /opt/bitnami/mariadb/conf/my.cnf
# 修改 innodb_buffer_pool_size 等
```

### 步骤 4：重启服务
```bash
sudo /opt/bitnami/ctlscript.sh restart apache
sudo /opt/bitnami/ctlscript.sh restart php
sudo /opt/bitnami/ctlscript.sh restart mysql
```

---

## 监控和验证

重启后监控内存使用：
```bash
# 实时监控
watch -n 2 'free -h && echo "---" && ps aux --sort=-%mem | head -15'

# 检查服务是否正常
curl -I https://kayarine.com.hk/account/
```

---

## 风险评估

| 项目 | 风险等级 | 说明 |
|------|---------|------|
| Apache MaxClients 15 | 低 | 月流量15000足够，可扩展到 25 如果需要 |
| PHP-FPM max_children 8 | 低 | 并发通常不超过 5 |
| MariaDB buffer_pool 2GB | 低 | 可缓存大部分工作集，高 I/O 时可调回 2.5GB |

---

## 如果流量增长

**月流量 50,000**：
- Apache MaxClients: 25
- PHP-FPM pm.max_children: 15
- MariaDB innodb_buffer_pool_size: 2.5GB

**月流量 100,000+**：
- 考虑扩容到更大实例或使用 Redis 缓存
- 启用 WP Super Cache/Redis 缓存层

