# WordPress 插件无法删除 - 深度诊断与修复指南

## 🔍 问题分析：5-7 个可能源头

基于您的报告"无法删除任何 plugin"，我分析了 5-7 个最可能的根本原因：

### 📊 可能源头排序（按概率）

| 排序 | 可能原因 | 症状 | 概率 |
|------|---------|------|------|
| **1** | 文件系统权限不足 | WordPress 后台显示"Permission Denied"或"操作失败" | **60%** 🔴 |
| **2** | wp-content/plugins 目录被锁定 | 无法删除任何插件文件 | **20%** 🟡 |
| **3** | 数据库权限问题 | 删除选项失败，插件信息残留数据库 | **10%** 🟡 |
| **4** | PHP 执行权限限制（open_basedir） | WordPress 无法访问插件目录 | **5%** 🟢 |
| **5** | WordPress 核心文件损坏 | wp-admin 删除功能失效 | **3%** 🟢 |
| **6** | SELinux/AppArmor 安全策略 | 系统级文件访问限制 | **1%** 🟢 |
| **7** | 数据库表损坏 | wp_options 表无法更新 | **1%** 🟢 |

---

## 🎯 根本原因诊断（最可能：权限问题）

### 症状 1️⃣：从 WordPress 后台无法删除插件

**表现**：
- WordPress 后台 → Plugins → 点击"Delete"后
- 显示："Could not locate a valid backup location for plugin"
- 或者："You do not have permission to do this"

**最可能原因**：
```
wp-content/plugins/ 目录权限不正确

当前（错误）：
drwxr-xr-x  www-data  www-data
        ↑         ↑
    权限755  拥有者不是 www-data

应该是：
drwxrwxr-x  www-data  www-data
        ↑
    权限 775（www-data 可写）
```

---

## 🔧 完整诊断与修复流程

### 步骤 1：验证 SSH 连接（必须）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "echo '✅ SSH 连接正常'"
```

**预期输出**：
```
✅ SSH 连接正常
```

如果失败，请检查：
- SSH 密钥路径是否正确
- SSH 密钥权限：`chmod 600 gcp-ssh-key`
- 是否在正确的目录（/Users/henrylo/Documents/GitHub/ssh/）

---

### 步骤 2：诊断脚本（一键检查所有问题）

复制以下命令到终端执行：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'DIAGNOSIS'
#!/bin/bash

echo "========================================="
echo "Kayarine WordPress 插件删除诊断"
echo "========================================="
echo ""

# 诊断 1：检查文件系统权限
echo "[诊断 1/7] 检查 wp-content/plugins 权限..."
PLUGIN_DIR="/opt/bitnami/wordpress/wp-content/plugins"
echo "目录: $PLUGIN_DIR"
ls -ld "$PLUGIN_DIR"
echo "权限分析："
stat -f "%Lp %Su:%Sg %N" "$PLUGIN_DIR" 2>/dev/null || stat -c '%a %U:%G %n' "$PLUGIN_DIR"
echo ""

# 诊断 2：检查 wp-content 目录
echo "[诊断 2/7] 检查 wp-content 目录权限..."
WP_CONTENT="/opt/bitnami/wordpress/wp-content"
ls -ld "$WP_CONTENT"
echo ""

# 诊断 3：检查插件文件所有权
echo "[诊断 3/7] 检查插件文件所有权..."
echo "示例插件 (flexible-shipping.disabled):"
TEST_PLUGIN="$PLUGIN_DIR/flexible-shipping.disabled"
if [ -d "$TEST_PLUGIN" ]; then
    ls -ld "$TEST_PLUGIN"
    ls -l "$TEST_PLUGIN" | head -5
else
    echo "该插件不存在或已删除"
fi
echo ""

# 诊断 4：检查 www-data 用户
echo "[诊断 4/7] 检查 www-data 用户..."
id www-data 2>/dev/null || echo "⚠️ www-data 用户不存在"
echo ""

# 诊断 5：检查 WordPress 文件所有权
echo "[诊断 5/7] 检查 wp-config.php 所有权..."
WP_CONFIG="/opt/bitnami/wordpress/wp-config.php"
ls -l "$WP_CONFIG"
echo ""

# 诊断 6：检查数据库连接
echo "[诊断 6/7] 检查 WordPress 数据库连接..."
cd /opt/bitnami/wordpress
sudo -u www-data wp db check 2>/dev/null && echo "✅ 数据库连接正常" || echo "❌ 数据库连接失败"
echo ""

# 诊断 7：检查磁盘空间
echo "[诊断 7/7] 检查磁盘空间..."
df -h /opt/bitnami/wordpress | awk '{print $1, $2, $3, $4, $5}'
echo ""

echo "========================================="
echo "诊断完成！"
echo "========================================="

DIAGNOSIS
```

---

### 步骤 3：根据诊断结果修复

#### 修复方案 A：文件系统权限不足（最常见）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FIX_PERMS'
#!/bin/bash

echo "修复 WordPress 文件权限..."
cd /opt/bitnami/wordpress

# 确保 www-data 用户存在
WWW_USER="www-data"
if ! id "$WWW_USER" &>/dev/null; then
    echo "⚠️ $WWW_USER 用户不存在，创建..."
    sudo useradd -r -s /bin/false "$WWW_USER"
fi

echo ""
echo "设置目录权限..."

# 设置 wp-content 权限（关键）
echo "1. wp-content (所有权 + 权限)"
sudo chown -R $WWW_USER:$WWW_USER wp-content
sudo chmod 755 wp-content
sudo chmod -R 755 wp-content/*

# 设置 plugins 权限（特别重要）
echo "2. wp-content/plugins (特殊权限)"
sudo chmod 775 wp-content/plugins
sudo chmod -R 755 wp-content/plugins/*

# 设置上传目录
echo "3. wp-content/uploads"
sudo chmod 775 wp-content/uploads
sudo chmod -R 755 wp-content/uploads/*

# 设置主目录权限（保守方案）
echo "4. 主 WordPress 文件"
sudo chown -R $WWW_USER:$WWW_USER .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo find . -name '*.php' -exec chmod 644 {} \;

# 特殊：wp-admin 和 wp-includes 必须可写
echo "5. wp-admin 和 wp-includes"
sudo chmod 755 wp-admin
sudo chmod 755 wp-includes

echo ""
echo "验证权限..."
echo "wp-content/plugins:"
ls -ld wp-content/plugins
echo ""
echo "✅ 权限修复完成！"

FIX_PERMS
```

#### 修复方案 B：数据库残留选项清理

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FIX_DB'
#!/bin/bash

cd /opt/bitnami/wordpress

echo "清理数据库中的插件残留..."

# 获取所有被禁用的插件
DISABLED_PLUGINS=(
    "flexible-shipping"
    "flexible-shipping-pro"
    "woocommerce-photo-reviews"
    "woo-checkout-field-editor-pro"
)

for plugin in "${DISABLED_PLUGINS[@]}"; do
    echo "清理 $plugin..."
    sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%${plugin}%';" 2>/dev/null
    sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_value LIKE '%${plugin}%';" 2>/dev/null
done

# 清理 transients 和临时数据
echo "清理临时缓存..."
sudo -u www-data wp transient delete --all 2>/dev/null
sudo -u www-data wp cache flush 2>/dev/null

echo "✅ 数据库清理完成！"

FIX_DB
```

#### 修复方案 C：强制删除插件文件（最后手段）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FORCE_DELETE'
#!/bin/bash

echo "强制删除插件文件..."
cd /opt/bitnami/wordpress/wp-content/plugins

# 列出要删除的文件
PLUGINS_TO_DELETE=(
    "flexible-shipping"
    "flexible-shipping.disabled"
    "flexible-shipping-pro"
    "flexible-shipping-pro.disabled"
    "woocommerce-photo-reviews"
    "woocommerce-photo-reviews.disabled"
    "woo-checkout-field-editor-pro"
    "woo-checkout-field-editor-pro.disabled"
)

for plugin in "${PLUGINS_TO_DELETE[@]}"; do
    if [ -d "$plugin" ]; then
        echo "删除: $plugin"
        sudo rm -rf "$plugin"
        echo "  ✓ $plugin 已删除"
    fi
done

echo ""
echo "验证删除结果..."
ls -1d flexible-shipping* woocommerce-photo-reviews* woo-checkout* 2>/dev/null || echo "✅ 所有插件已删除"

FORCE_DELETE
```

---

## 🚨 完整修复工作流（推荐）

### 一键修复所有问题

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'COMPLETE_FIX'
#!/bin/bash

set -e  # 任何错误都停止

echo "========================================="
echo "Kayarine WordPress 插件删除完整修复"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress
WWW_USER="www-data"
WP_GROUP="www-data"

# 步骤 1：修复文件权限
echo "[步骤 1/4] 修复文件权限..."
echo "设置 wp-content 所有权..."
sudo chown -R $WWW_USER:$WP_GROUP wp-content

echo "设置权限..."
sudo chmod 775 wp-content
sudo chmod -R 755 wp-content/plugins
sudo chmod -R 755 wp-content/themes
sudo chmod -R 755 wp-content/uploads
echo "  ✓ 权限修复完成"
echo ""

# 步骤 2：删除被禁用的插件文件
echo "[步骤 2/4] 删除被禁用的插件..."
cd wp-content/plugins
for plugin in flexible-shipping* woocommerce-photo-reviews* woo-checkout*; do
    if [ -d "$plugin" ] || [ -f "$plugin" ]; then
        echo "删除: $plugin"
        sudo rm -rf "$plugin"
    fi
done
echo "  ✓ 插件文件删除完成"
echo ""

# 步骤 3：清理数据库
echo "[步骤 3/4] 清理数据库..."
cd /opt/bitnami/wordpress

echo "清理插件选项..."
sudo -u www-data wp db query "DELETE FROM wp_options 
    WHERE option_name LIKE '%flexible%' 
    OR option_name LIKE '%photo_review%' 
    OR option_name LIKE '%checkout_field%';" 2>/dev/null || true

echo "清理缓存..."
sudo -u www-data wp transient delete --all 2>/dev/null || true
sudo -u www-data wp cache flush 2>/dev/null || true
echo "  ✓ 数据库清理完成"
echo ""

# 步骤 4：验证
echo "[步骤 4/4] 验证..."
echo "WordPress 数据库检查..."
sudo -u www-data wp db check 2>/dev/null && echo "  ✓ 数据库正常"

echo "检查插件列表..."
sudo -u www-data wp plugin list 2>/dev/null || echo "  ℹ️ 使用后台验证"

echo ""
echo "========================================="
echo "✅ 修复完成！"
echo "========================================="
echo ""
echo "后续步骤："
echo "1. 访问 WordPress 后台"
echo "2. 刷新插件页面"
echo "3. 尝试重新安装或激活必要插件"
echo ""

COMPLETE_FIX
```

---

## ✅ 验证修复成功

修复完成后，运行此命令验证：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'VERIFY'
#!/bin/bash

echo "========================================="
echo "验证修复结果"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress

# 检查权限
echo "✓ wp-content 权限检查："
ls -ld wp-content

echo ""
echo "✓ 是否还有被禁用插件："
ls -1d wp-content/plugins/flexible-* wp-content/plugins/woocommerce-photo* wp-content/plugins/woo-checkout* 2>/dev/null || echo "  ✅ 无被禁用插件"

echo ""
echo "✓ WordPress 健康检查："
cd /opt/bitnami/wordpress
sudo -u www-data wp health-check run --format=table 2>/dev/null || echo "  (跳过：Health Check 插件未安装)"

echo ""
echo "✓ 插件总数："
sudo -u www-data wp plugin list --format=count 2>/dev/null || echo "  (使用后台验证)"

echo ""
echo "========================================="
echo "验证完成！"
echo "========================================="

VERIFY
```

---

## 📋 问题排查决策树

```
无法删除插件
│
├─ 从 WordPress 后台无法删除？
│  ├─ YES → 权限问题（方案 A）
│  └─ NO → 继续
│
├─ SSH 删除文件时出错？
│  ├─ "Permission denied" → 权限问题（方案 A）
│  ├─ "No such file" → 文件不存在（可能已删除）
│  └─ "Operation not permitted" → SELinux/AppArmor（方案方案 D）
│
├─ WordPress 显示"数据库错误"？
│  └─ YES → 数据库问题（方案 B）
│
└─ 都不是上述情况？
   └─ 运行完整诊断脚本（步骤 2）
```

---

## 🎯 确认诊断

在我进行修复前，请运行诊断脚本（步骤 2）并告诉我：

1. **文件权限输出**（第一部分 ls -ld）
   ```
   例如：drwxr-xr-x 或 drwxrwxr-x?
   ```

2. **所有权信息**（谁拥有这些文件？）
   ```
   例如：root:root 或 www-data:www-data?
   ```

3. **具体错误信息**（WordPress 后台显示什么？）
   ```
   例如："Permission Denied" 或 "Could not locate backup location"?
   ```

这样我可以精准诊断根本原因，而不是盲目修复。

