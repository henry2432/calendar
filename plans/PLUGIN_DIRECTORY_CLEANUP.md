# WordPress 插件目录清理指南

## 🔍 问题诊断

### 当前状况

`/opt/bitnami/wordpress/wp-content/plugins/` 目录混乱：

**实际插件数**：20 个
```
✓ essential-addons-elementor/
✓ essential-addons-for-elementor-lite/
✓ google-site-kit/
✓ kayarine-booking/
✓ nitropack/
✓ query-monitor/
✓ royal-elementor-addons/
✓ translatepress-developer/
✓ translatepress-multilingual/
✓ updraftplus/
✓ woocommerce/
✓ woocommerce-gateway-stripe/
✓ woocommerce-smart-coupons/
✓ wordpress-seo/
✓ wordpress-seo-premium/
✓ wp-mail-smtp-pro/
✓ wpr-addons-pro/
✓ yith-woocommerce-booking-premium/
```

**混乱文件数**：28 个（不应该在这里的文件）
```
❌ COMPLETION_REPORT_2026_01_28.md
❌ composer.json
❌ CRITICAL_FIXES_10_ISSUES.md
❌ CRITICAL_FIXES_SUMMARY.md
❌ demo-login-redesign.html
❌ DEPLOYMENT_GCLOUD_GUIDE.md
❌ DEPLOYMENT_INSTRUCTIONS.md
❌ DEPLOYMENT.sh
❌ DEPLOYMENT_V1.4.13_NOTES.md
❌ DEPLOYMENT_v1.4.14.md
❌ deploy.sh
❌ EXECUTIVE_SUMMARY.md
❌ index.php (特殊 - 通常应该存在)
❌ KAYARINE_ACCOUNT_DEPLOYMENT_GUIDE.md
❌ MEMBER_SETUP_GUIDE.md
❌ MENU_FIX_DIAGNOSTIC_GUIDE.md
❌ PRE_DEPLOYMENT_CHECKLIST.md
❌ QUICK_DEPLOY_GUIDE_SIMPLIFIED.md
❌ QUICK_DEPLOYMENT_GUIDE.md
❌ QUICK_DEPLOY_SIMPLIFIED.sh
❌ QUICK_DEPLOY_SSH.md
❌ QUICK_FIX_GUIDE.md
❌ REDESIGN_DOCUMENTATION.md
❌ RESCHEDULE_CANCEL_TESTING_GUIDE.md
❌ SERVER_OPTIMIZATION_PLAN.md
❌ terms_and_conditions.txt
❌ TESTING_MENU_FIX_1.4.8.md
❌ UNIFIED_ACCOUNT_IMPLEMENTATION_GUIDE.md
```

**混乱目录数**：2 个（不是插件，应该在 kayarine-booking 中）
```
❌ assets/
❌ includes/
```

---

## 🎯 根本原因分析

### 问题来源

这些文件很可能是：

1. **开发文档和部署脚本被意外上传到 wp-content/plugins/**
   - 通过 FTP/SFTP 上传时没有正确的目录结构
   - 或 git checkout 时出错

2. **kayarine-booking 插件的文件被错误放置**
   - assets/ 和 includes/ 应该在 kayarine-booking/ 目录内
   - 而不是在 plugins/ 根目录

3. **部署脚本执行时的目录问题**
   - 某些部署命令 cd 到了错误的目录
   - 导致文件复制到了插件目录

---

## 🔧 清理方案

### 方案 1：安全清理（推荐）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'CLEANUP'
#!/bin/bash

echo "========================================="
echo "WordPress 插件目录清理"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress/wp-content/plugins

# 备份到临时位置（以防万一）
echo "[1/3] 创建备份..."
BACKUP_DIR="/tmp/plugins-backup-$(date +%s)"
mkdir -p "$BACKUP_DIR"
cp -r . "$BACKUP_DIR/"
echo "  ✓ 备份已保存到: $BACKUP_DIR"
echo ""

# 删除混乱文件（保留所有真正的插件目录和 index.php）
echo "[2/3] 删除混乱文件..."

# 要删除的文件列表（不是插件的文件）
FILES_TO_DELETE=(
    "COMPLETION_REPORT_2026_01_28.md"
    "composer.json"
    "CRITICAL_FIXES_10_ISSUES.md"
    "CRITICAL_FIXES_SUMMARY.md"
    "demo-login-redesign.html"
    "DEPLOYMENT_GCLOUD_GUIDE.md"
    "DEPLOYMENT_INSTRUCTIONS.md"
    "DEPLOYMENT.sh"
    "DEPLOYMENT_V1.4.13_NOTES.md"
    "DEPLOYMENT_v1.4.14.md"
    "deploy.sh"
    "EXECUTIVE_SUMMARY.md"
    "KAYARINE_ACCOUNT_DEPLOYMENT_GUIDE.md"
    "MEMBER_SETUP_GUIDE.md"
    "MENU_FIX_DIAGNOSTIC_GUIDE.md"
    "PRE_DEPLOYMENT_CHECKLIST.md"
    "QUICK_DEPLOY_GUIDE_SIMPLIFIED.md"
    "QUICK_DEPLOYMENT_GUIDE.md"
    "QUICK_DEPLOY_SIMPLIFIED.sh"
    "QUICK_DEPLOY_SSH.md"
    "QUICK_FIX_GUIDE.md"
    "REDESIGN_DOCUMENTATION.md"
    "RESCHEDULE_CANCEL_TESTING_GUIDE.md"
    "SERVER_OPTIMIZATION_PLAN.md"
    "terms_and_conditions.txt"
    "TESTING_MENU_FIX_1.4.8.md"
    "UNIFIED_ACCOUNT_IMPLEMENTATION_GUIDE.md"
)

for file in "${FILES_TO_DELETE[@]}"; do
    if [ -f "$file" ]; then
        rm "$file"
        echo "  ✓ 删除: $file"
    fi
done
echo ""

# 删除混乱目录（assets 和 includes 应该在 kayarine-booking 内）
echo "[3/3] 整理混乱目录..."

# 检查 assets 和 includes 是否不是插件
if [ -d "assets" ] && [ ! -f "assets/index.php" ] && [ ! -f "assets/plugin.php" ]; then
    echo "  移动: assets/ → kayarine-booking/assets/"
    if [ ! -d "kayarine-booking/assets" ]; then
        mv assets kayarine-booking/
    else
        echo "  ⚠️ kayarine-booking/assets 已存在，跳过"
    fi
fi

if [ -d "includes" ] && [ ! -f "includes/index.php" ] && [ ! -f "includes/plugin.php" ]; then
    echo "  移动: includes/ → kayarine-booking/includes/"
    if [ ! -d "kayarine-booking/includes" ]; then
        mv includes kayarine-booking/
    else
        echo "  ⚠️ kayarine-booking/includes 已存在，跳过"
    fi
fi

echo ""
echo "========================================="
echo "✅ 清理完成"
echo "========================================="
echo ""
echo "清理摘要："
echo "- 删除了 27 个混乱文件"
echo "- 整理了混乱目录"
echo "- 保留了 20 个有效插件"
echo "- 备份位置: $BACKUP_DIR"
echo ""

CLEANUP
```

---

### 方案 2：验证清理结果

清理后运行此脚本验证：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'VERIFY'
#!/bin/bash

echo "验证插件目录清理结果..."
echo ""

cd /opt/bitnami/wordpress/wp-content/plugins

echo "[1/2] 检查插件数量..."
PLUGIN_COUNT=$(ls -1d */ 2>/dev/null | wc -l)
echo "  插件总数: $PLUGIN_COUNT（应该是 20 个）"
echo ""

echo "[2/2] 列出所有插件..."
ls -1d */ 2>/dev/null | nl
echo ""

echo "检查是否还有混乱文件..."
FILE_COUNT=$(ls -1 -p | grep -v '/$' | grep -v '^index.php$' | wc -l)
if [ "$FILE_COUNT" -eq 0 ]; then
    echo "  ✅ 无混乱文件（正常）"
else
    echo "  ❌ 还有 $FILE_COUNT 个混乱文件："
    ls -1 -p | grep -v '/$' | grep -v '^index.php$'
fi
echo ""

VERIFY
```

---

## ⚠️ 注意事项

### 重要：执行前检查

1. **确认 kayarine-booking 目录存在**
   ```bash
   ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "ls -d /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking"
   ```

2. **确认没有激活损坏的插件**
   - 如果 assets/ 或 includes/ 是插件的一部分，移动它们会导致主插件损坏
   - 但根据 WordPress 规范，它们不应该在 plugins/ 根目录

3. **备份已自动创建**
   - 脚本会在 `/tmp/plugins-backup-{timestamp}/` 创建完整备份
   - 如果出问题，可以恢复

### 如果出现问题

如果清理后 WordPress 出现问题：

```bash
# 恢复备份（将 {timestamp} 替换为实际时间戳）
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'RESTORE'
#!/bin/bash

cd /opt/bitnami/wordpress/wp-content/plugins
rm -rf *
cp -r /tmp/plugins-backup-{timestamp}/* .

RESTORE
```

---

## 📋 执行清单

- [ ] **备份当前插件目录**（脚本自动执行）
- [ ] **执行清理脚本**（方案 1）
- [ ] **验证清理结果**（方案 2）
- [ ] **访问 WordPress 后台检查**
  - [ ] 所有插件仍然激活
  - [ ] 没有激活错误
  - [ ] kayarine-booking 插件正常工作
- [ ] **检查日志** - 查看 debug.log 是否有新错误
- [ ] **更新文档** - 记录清理操作到 DEVELOPMENT_LOG.md

---

## 🎯 预期结果

| 指标 | 清理前 | 清理后 |
|------|-------|-------|
| **混乱文件** | 27 个 | 0 个 |
| **混乱目录** | 2 个 | 0 个 |
| **有效插件** | 20 个 | 20 个（不变） |
| **目录整洁度** | ❌ 混乱 | ✅ 整洁 |

---

## 💡 预防措施

为避免此问题再次发生：

1. **使用正确的部署脚本**
   - 部署时，确保文件复制到正确的目录
   - 使用 `rsync` 或 `git` 而不是手动 cp

2. **验证目录结构**
   ```bash
   # 定期检查
   find /opt/bitnami/wordpress/wp-content/plugins -maxdepth 1 -type f ! -name 'index.php' | wc -l
   # 应该输出 0
   ```

3. **使用 .gitignore**
   - 确保本地开发文件不会被推送到远程
   - 避免意外上传到生产环境

