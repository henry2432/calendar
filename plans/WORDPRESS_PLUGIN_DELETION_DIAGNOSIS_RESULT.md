# WordPress 插件删除问题 - 诊断结果报告

## 🎯 根本原因确认（已诊断）

### ✅ 诊断结果汇总

| 诊断项 | 结果 | 状态 |
|--------|------|------|
| **wp-content/plugins 权限** | `drwxrwxrwx` + 所有权正确 (www-data:www-data) | ✅ 正常 |
| **wp-content 符号链接所有权** | `daemon:daemon` **不是** www-data | 🔴 **问题** |
| **wp-config.php 所有权** | `daemon:daemon` **不是** www-data | 🔴 **问题** |
| **数据库连接** | `wp db check` 失败 | 🔴 **问题** |
| **磁盘空间** | 38G 可用（充足） | ✅ 正常 |

---

## 🔍 问题分析

### 问题 1️⃣：wp-config.php 所有权不正确 (最严重)

```
当前状态：
-rw-r--r-- 1 daemon daemon 5608 Feb  3 16:13 /opt/bitnami/wordpress/wp-config.php
                ↑
            所有权是 daemon

应该是：
-rw-r--r-- 1 www-data www-data 5608 Feb  3 16:13 /opt/bitnami/wordpress/wp-config.php
                ↑
           所有权是 www-data
```

**影响**：
- www-data 用户**无法读取** wp-config.php
- 数据库连接参数无法加载
- WordPress 无法连接数据库
- 结果：所有数据库操作失败，插件删除失败

---

### 问题 2️⃣：wp-content 符号链接所有权不正确

```
当前状态：
lrwxrwxrwx 1 daemon daemon 29 May 14  2025 /opt/bitnami/wordpress/wp-content -> /bitnami/wordpress/wp-content
            ↑
        所有权是 daemon

应该是：
lrwxrwxrwx 1 www-data www-data 29 May 14  2025 /opt/bitnami/wordpress/wp-content -> /bitnami/wordpress/wp-content
            ↑
       所有权是 www-data
```

**影响**：
- 符号链接权限（777）看起来正常
- 但所有权错误可能导致某些操作受限

---

## 🔧 修复方案（立即执行）

### 方案 A：修复所有权（推荐 ✅）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FIX'
#!/bin/bash

echo "修复 WordPress 所有权..."
cd /opt/bitnami/wordpress

# 步骤 1：修改 wp-config.php 所有权
echo "[1/3] 修改 wp-config.php 所有权..."
sudo chown www-data:www-data wp-config.php
echo "  ✓ wp-config.php 所有权已修改"
ls -l wp-config.php

echo ""

# 步骤 2：修改 wp-content 符号链接所有权
echo "[2/3] 修改 wp-content 符号链接所有权..."
sudo chown www-data:www-data wp-content
echo "  ✓ wp-content 所有权已修改"
ls -ld wp-content

echo ""

# 步骤 3：修改实际 wp-content 目录的所有权（在 /bitnami/wordpress/）
echo "[3/3] 修改 /bitnami/wordpress/wp-content 所有权..."
sudo chown -R www-data:www-data /bitnami/wordpress/wp-content
echo "  ✓ /bitnami/wordpress/wp-content 所有权已修改"
ls -ld /bitnami/wordpress/wp-content

echo ""
echo "验证修复..."
echo ""
echo "wp-config.php:"
ls -l wp-config.php
echo ""
echo "wp-content (符号链接):"
ls -ld wp-content
echo ""
echo "✅ 修复完成！"

FIX
```

---

### 方案 B：验证修复成功

修复后立即运行此脚本验证：

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'VERIFY'
#!/bin/bash

echo "========================================="
echo "验证修复结果"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress

echo "[1/3] 检查 wp-config.php 所有权..."
ls -l wp-config.php
echo ""

echo "[2/3] 检查数据库连接..."
sudo -u www-data wp db check 2>&1
echo ""

echo "[3/3] 检查插件列表..."
sudo -u www-data wp plugin list --format=table 2>&1 | head -20
echo ""

echo "========================================="
echo "验证完成！"
echo "========================================="

VERIFY
```

---

## 🎯 为什么会出现这个问题？

根据诊断结果推测：

1. **Bitnami WordPress 的默认配置**
   - Bitnami 安装时可能使用了 daemon 用户
   - 符号链接指向 /bitnami/wordpress/wp-content（外部路径）

2. **权限变更冲突**
   - 可能之前的某个更新或部署改变了所有权
   - 当前 plugins 目录权限是 www-data（正确）
   - 但 wp-config.php 仍是 daemon（错误）

3. **导致的后果链**
   ```
   wp-config.php 所有权错误
   ↓
   www-data 无法读取配置
   ↓
   数据库连接失败
   ↓
   所有数据库操作失败
   ↓
   插件删除失败（需要数据库操作）
   ```

---

## 📋 执行步骤

### 步骤 1：运行修复脚本（复制整个代码块）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FIX'
#!/bin/bash

echo "修复 WordPress 所有权..."
cd /opt/bitnami/wordpress

# 步骤 1
echo "[1/3] 修改 wp-config.php 所有权..."
sudo chown www-data:www-data wp-config.php
echo "  ✓ 完成"
ls -l wp-config.php
echo ""

# 步骤 2
echo "[2/3] 修改 wp-content 符号链接所有权..."
sudo chown www-data:www-data wp-content
echo "  ✓ 完成"
ls -ld wp-content
echo ""

# 步骤 3
echo "[3/3] 修改 /bitnami/wordpress/wp-content 所有权..."
sudo chown -R www-data:www-data /bitnami/wordpress/wp-content
echo "  ✓ 完成"
ls -ld /bitnami/wordpress/wp-content
echo ""

echo "✅ 修复完成！"

FIX
```

### 步骤 2：验证修复（复制整个代码块）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'VERIFY'
#!/bin/bash

echo "验证数据库连接..."
cd /opt/bitnami/wordpress
sudo -u www-data wp db check 2>&1

echo ""
echo "验证插件列表..."
sudo -u www-data wp plugin list --format=table 2>&1 | head -20

VERIFY
```

### 步骤 3：删除被禁用的插件（修复后执行）

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'DELETE'
#!/bin/bash

echo "删除被禁用的插件..."
cd /opt/bitnami/wordpress/wp-content/plugins

PLUGINS=(
    "flexible-shipping.disabled"
    "flexible-shipping-pro.disabled"
    "woocommerce-photo-reviews.disabled"
    "woo-checkout-field-editor-pro.disabled"
    "wpforms-lite.disabled"
    "ninja-google-review"
)

for plugin in "${PLUGINS[@]}"; do
    if [ -d "$plugin" ]; then
        echo "删除: $plugin"
        sudo rm -rf "$plugin"
        echo "  ✓ 已删除"
    fi
done

echo ""
echo "✅ 删除完成！"

DELETE
```

---

## 🚨 诊断数据备份

### 原始诊断输出

```
[诊断 1/7] 检查 wp-content/plugins 权限...
drwxrwxrwx 28 www-data www-data 4096 Feb  3 17:51 /opt/bitnami/wordpress/wp-content/plugins
✅ 权限正常，所有权正确

[诊断 2/7] 检查 wp-content 目录权限...
lrwxrwxrwx 1 daemon daemon 29 May 14  2025 /opt/bitnami/wordpress/wp-content -> /bitnami/wordpress/wp-content
🔴 所有权错误：daemon（应该是 www-data）

[诊断 3/7] 检查所有插件...
已列出所有插件，包括：
- flexible-shipping.disabled
- flexible-shipping-pro.disabled
- woo-checkout-field-editor-pro.disabled
- woocommerce-photo-reviews.disabled
- wpforms-lite.disabled
- ninja-google-review
以及其他正常插件

[诊断 4/7] 检查 www-data 用户...
✅ uid=33(www-data) gid=33(www-data) groups=33(www-data)
www-data 用户存在且正常

[诊断 5/7] 检查 wp-config.php 所有权...
-rw-r--r-- 1 daemon daemon 5608 Feb  3 16:13 /opt/bitnami/wordpress/wp-config.php
🔴 所有权错误：daemon（应该是 www-data）
🔴 权限是 644，www-data 不能修改

[诊断 6/7] 检查数据库连接...
❌ wp db check 失败
原因：wp-config.php 所有权错误，www-data 无法读取

[诊断 7/7] 检查磁盘空间...
✅ 38G 可用（充足）
```

---

## ✅ 预期修复结果

修复完成后：

1. ✅ wp-config.php 所有权变为 www-data:www-data
2. ✅ wp-content 符号链接所有权变为 www-data:www-data
3. ✅ 数据库连接恢复
4. ✅ WordPress 可以执行删除操作
5. ✅ 所有被禁用的插件可以被删除

---

## 📝 后续行动

修复后建议：

1. **清理数据库残留**
   ```bash
   # 删除被禁用插件的数据库选项
   sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%flexible%';"
   sudo -u www-data wp db query "DELETE FROM wp_options WHERE option_name LIKE '%ninja%';"
   ```

2. **清理缓存**
   ```bash
   sudo -u www-data wp transient delete --all
   sudo -u www-data wp cache flush
   ```

3. **测试插件删除**
   - 从 WordPress 后台尝试删除几个插件
   - 或使用 WP-CLI：`wp plugin delete plugin-name`

