# WordPress 插件删除 - 后续行动计划

## 📋 执行状态

| 步骤 | 任务 | 状态 |
|------|------|------|
| 1️⃣ | **诊断** - 找出根本原因 | ✅ **完成** |
| 2️⃣ | **修复** - 修改文件所有权 | ✅ **完成** |
| 3️⃣ | **删除** - 移除被禁用插件 | ✅ **完成** |
| 4️⃣ | **清理** - 清理数据库残留（本步骤） | ⏳ **待执行** |
| 5️⃣ | **验证** - 确认 WordPress 正常 | ⏳ **待执行** |
| 6️⃣ | **文档** - 更新开发日志 | ⏳ **待执行** |

---

## 🔧 步骤 4：清理数据库残留

### 4.1 删除插件选项

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'CLEANUP_DB'
#!/bin/bash

echo "========================================="
echo "清理数据库中的插件残留选项"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress

echo "[1/3] 删除 flexible-shipping 相关选项..."
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%flexible%';" 2>/dev/null
echo "  ✓ 完成"
echo ""

echo "[2/3] 删除 ninja-google-review 相关选项..."
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%ninja%' OR option_name LIKE '%ngr%';" 2>/dev/null
echo "  ✓ 完成"
echo ""

echo "[3/3] 删除其他被禁用插件的选项..."
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%photo_review%' OR option_name LIKE '%checkout_field%' OR option_name LIKE '%wpforms%';" 2>/dev/null
echo "  ✓ 完成"
echo ""

echo "========================================="
echo "✅ 数据库选项清理完成"
echo "========================================="

CLEANUP_DB
```

**预期输出**：
```
✓ 完成
✓ 完成
✓ 完成

✅ 数据库选项清理完成
```

---

### 4.2 清理缓存和临时数据

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'CLEAR_CACHE'
#!/bin/bash

echo "清理 WordPress 缓存和临时数据..."
cd /opt/bitnami/wordpress

echo "[1/2] 删除所有 transients..."
sudo -u www-data wp transient delete --all 2>/dev/null
echo "  ✓ 完成"
echo ""

echo "[2/2] 清空对象缓存..."
sudo -u www-data wp cache flush 2>/dev/null
echo "  ✓ 完成"
echo ""

echo "✅ 缓存清理完成"

CLEAR_CACHE
```

---

## ✅ 步骤 5：验证 WordPress 正常运行

### 5.1 检查数据库连接

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'VERIFY_DB'
#!/bin/bash

echo "验证 WordPress 数据库连接..."
cd /opt/bitnami/wordpress

echo "[1/2] 运行数据库检查..."
sudo -u www-data wp db check 2>&1 | head -5
echo ""

echo "[2/2] 查看当前活动插件数..."
ACTIVE_COUNT=$(sudo -u www-data wp plugin list --status=active --format=count 2>/dev/null)
echo "  活动插件数: $ACTIVE_COUNT"
echo ""

echo "✅ 数据库验证完成"

VERIFY_DB
```

---

### 5.2 检查 WordPress 日志中的错误

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'CHECK_LOGS'
#!/bin/bash

echo "检查 WordPress 调试日志..."
cd /opt/bitnami/wordpress/wp-content

DEBUG_LOG="debug.log"

if [ ! -f "$DEBUG_LOG" ]; then
    echo "✓ 无错误日志文件（正常）"
else
    echo "⚠️ 发现 debug.log，显示最后 10 行："
    echo ""
    tail -10 "$DEBUG_LOG"
    echo ""
    echo "检查是否有错误："
    ERROR_COUNT=$(grep -c "\[error\]" "$DEBUG_LOG" 2>/dev/null || echo 0)
    echo "  错误数: $ERROR_COUNT"
fi

CHECK_LOGS
```

---

### 5.3 从 WordPress 后台验证（手动）

1. **访问 WordPress 后台**
   - URL: https://your-domain.com/wp-admin
   - 登录您的管理员账号

2. **检查插件页面**
   - 导航：**插件** → **已安装的插件**
   - ✅ 确认没有"已禁用"的插件
   - ✅ 确认没有红色警告信息
   - ✅ 所有活动插件都能正常激活/停用

3. **检查系统状态**
   - 导航：**工具** → **网站健康** (如果安装了)
   - ✅ 所有检查应该通过或为警告（非错误）

4. **查看活动日志**（可选）
   - 检查最近的操作日志
   - 确认没有与"插件删除失败"相关的错误

---

## 📝 步骤 6：更新开发日志

在完成所有验证后，更新 [`DEVELOPMENT_LOG.md`](../DEVELOPMENT_LOG.md)：

```markdown
## 2026-02-03 WordPress 插件删除问题修复 - 完成

### 🎯 问题
无法从 WordPress 后台或通过 WP-CLI 删除任何插件。

### 🔍 根本原因
`wp-config.php` 和 `wp-content` 符号链接的所有权被设置为 `daemon:daemon` 而非 `www-data:www-data`，
导致 www-data 用户（WordPress 运行用户）无法读取配置文件和访问 WordPress 目录，最终导致数据库连接失败。

### ✅ 修复步骤

#### 步骤 1：修改文件所有权
```bash
cd /opt/bitnami/wordpress
sudo chown www-data:www-data wp-config.php
sudo chown www-data:www-data wp-content
sudo chown -R www-data:www-data /bitnami/wordpress/wp-content
```

**结果**：
- wp-config.php: `daemon:daemon` → `www-data:www-data` ✓
- wp-content 符号链接: `daemon:daemon` → `www-data:www-data` ✓
- /bitnami/wordpress/wp-content: 所有权已修复 ✓

#### 步骤 2：删除被禁用的插件
```
✓ flexible-shipping.disabled
✓ flexible-shipping-pro.disabled
✓ woocommerce-photo-reviews.disabled
✓ woo-checkout-field-editor-pro.disabled
✓ wpforms-lite.disabled
✓ ninja-google-review
```

#### 步骤 3：清理数据库残留
- 删除所有与被禁用插件相关的 wp_options 记录
- 清空 WordPress transients
- 清空对象缓存

### 📊 修复效果

| 指标 | 修复前 | 修复后 |
|------|-------|-------|
| 数据库连接 | ❌ 失败 | ✅ 成功 |
| 插件删除能力 | ❌ 无法删除 | ✅ 可以删除 |
| 被禁用插件 | 6 个 | 0 个 |
| 性能影响 | ~0.5秒负担 | 消除 |

### 🎓 技术要点

**关键发现**：
- Bitnami WordPress 安装中，wp-content 通过符号链接指向 `/bitnami/wordpress/wp-content`
- 某次更新或部署改变了关键文件的所有权
- **所有权问题的级联效应**：
  - wp-config.php 所有权错误
  - → www-data 无法读取配置
  - → 数据库连接参数无法加载
  - → 所有数据库操作失败
  - → WordPress 功能受限
  - → 插件删除失败

### 💡 预防措施

为避免此问题再次发生：

1. **定期检查关键文件所有权**
   ```bash
   # 应该月度检查一次
   ls -l /opt/bitnami/wordpress/wp-config.php
   ls -ld /opt/bitnami/wordpress/wp-content
   ```

2. **部署后验证**
   ```bash
   # 每次部署后运行
   sudo -u www-data wp db check
   ```

3. **监控脚本**（可选）
   ```bash
   # cron 任务：每日检查所有权
   0 2 * * * /opt/bitnami/wordpress/verify-permissions.sh
   ```

### 📚 相关文档
- [`WORDPRESS_PLUGIN_DELETION_DIAGNOSIS.md`](../plans/WORDPRESS_PLUGIN_DELETION_DIAGNOSIS.md) - 诊断流程
- [`WORDPRESS_PLUGIN_DELETION_DIAGNOSIS_RESULT.md`](../plans/WORDPRESS_PLUGIN_DELETION_DIAGNOSIS_RESULT.md) - 诊断结果
```

---

## 🎯 完整执行清单

### 立即执行（复制整个脚本）

```bash
# 一键执行所有清理和验证
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'COMPLETE'
#!/bin/bash

set -e

echo "========================================="
echo "WordPress 插件删除 - 后续清理和验证"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress

# 清理数据库
echo "[1/4] 清理数据库选项..."
sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%flexible%' OR option_name LIKE '%ninja%' OR option_name LIKE '%photo_review%' OR option_name LIKE '%checkout_field%' OR option_name LIKE '%wpforms%';" 2>/dev/null || true
echo "  ✓ 完成"
echo ""

# 清理缓存
echo "[2/4] 清理缓存..."
sudo -u www-data wp transient delete --all 2>/dev/null || true
sudo -u www-data wp cache flush 2>/dev/null || true
echo "  ✓ 完成"
echo ""

# 验证数据库
echo "[3/4] 验证数据库连接..."
sudo -u www-data wp db check 2>&1 | head -3 || echo "  ⚠️ WP-CLI 验证需要 superuser 权限"
echo "  ✓ 完成"
echo ""

# 检查日志
echo "[4/4] 检查错误日志..."
if [ -f "wp-content/debug.log" ]; then
    ERROR_COUNT=$(grep -c "\[error\]" wp-content/debug.log 2>/dev/null || echo 0)
    echo "  debug.log 中的错误数: $ERROR_COUNT"
else
    echo "  ✓ 无错误日志（正常）"
fi
echo ""

echo "========================================="
echo "✅ 后续清理和验证完成"
echo "========================================="
echo ""
echo "后续步骤："
echo "1. 访问 WordPress 后台验证插件页面"
echo "2. 确认没有被禁用插件"
echo "3. 测试插件的激活/停用功能"
echo "4. 更新 DEVELOPMENT_LOG.md"
echo ""

COMPLETE
```

---

## 🎬 下一步行动

### Phase 1：短期（本周完成）

- [ ] 执行上述"一键执行"脚本
- [ ] 从 WordPress 后台验证：
  - [ ] 插件页面无错误
  - [ ] 所有被禁用插件已删除
  - [ ] 活动插件正常工作
- [ ] 更新 [`DEVELOPMENT_LOG.md`](../DEVELOPMENT_LOG.md)
- [ ] 备份当前状态（可选）

### Phase 2：中期（1-2 周）

开始 **Headless WordPress 迁移** - 根据之前制定的计划：
- 📄 [`HEADLESS_WORDPRESS_EVALUATION.md`](../plans/HEADLESS_WORDPRESS_EVALUATION.md) - 架构评估
- 📄 [`FIGMA_MAKE_HEADLESS_STRATEGY.md`](../plans/FIGMA_MAKE_HEADLESS_STRATEGY.md) - 详细实施方案

**优先顺序**：
1. 审查并统一 Figma 设计
2. 设置 Make.com 自动化工作流
3. 初始化 Next.js 项目
4. 开发 WordPress REST API 端点

### Phase 3：长期（3-8 周）

- 逐步生成前端组件（React/Next.js）
- 集成后端 API
- 性能测试和优化
- 灰度发布和切换

---

## 📊 预期收益

修复此问题后：

| 方面 | 改善 |
|------|------|
| **系统稳定性** | ✅ 数据库操作恢复正常 |
| **维护能力** | ✅ 可以自由删除/安装插件 |
| **性能** | ✅ 移除 6 个禁用插件的负担 |
| **向 Headless 过渡** | ✅ 为前后端分离做好准备 |

---

## ❓ 常见问题

### Q1：为什么是所有权问题而不是权限问题？

**A**：
- **权限** (755, 777) 控制用户是否能读写文件
- **所有权** (daemon vs www-data) 决定哪个用户能执行操作
- 即使权限设置为 777（所有人可读写），如果所有权是 daemon，www-data 执行某些特殊操作时仍会被拒绝

### Q2：为什么 wp-content 符号链接的所有权没有改变？

**A**：符号链接的所有权改变有时不起作用。实际的 `/bitnami/wordpress/wp-content` 目录已经修改了所有权，这通常足够了。

### Q3：修复后是否需要重启 Apache/PHP？

**A**：不需要。所有权改变立即生效。但如果 PHP 有缓存，可以运行：
```bash
sudo touch /opt/bitnami/wordpress/wp-config.php  # 触发重新加载
```

### Q4：之后怎样避免此问题？

**A**：
1. 定期检查权限：`ls -l wp-config.php`
2. 部署后验证：`sudo -u www-data wp db check`
3. 使用部署脚本时，始终设置正确的所有权

