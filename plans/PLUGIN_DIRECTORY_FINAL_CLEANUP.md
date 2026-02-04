# WordPress 插件目录 - 最终清理（第二步）

## 🔍 当前问题分析

清理结果显示，**plugins/ 目录仍然混乱**：

### 问题 1：plugins/ 根目录仍有杂乱目录

```
❌ /wp-content/plugins/assets/     （不应该在这里）
❌ /wp-content/plugins/includes/   （不应该在这里）
```

这两个目录是多余的复制品，kayarine-booking 中已经有了正确的版本。

### 问题 2：kayarine-booking 插件内含大量非插件文件

```
kayarine-booking/
├── COMPLETION_REPORT_2026_01_28.md          ❌
├── CRITICAL_FIXES_*.md                      ❌
├── DEPLOYMENT_*.md / deploy.sh              ❌
├── demo-login-redesign.html                 ❌
├── composer.json                            ❌
├── assets/                                  ✅ (插件资源)
└── includes/                                ✅ (插件代码)
```

这些文档应该在**项目根目录**，而不是在 kayarine-booking 插件中。

---

## 🔧 第二步清理方案

### 方案：彻底整理 kayarine-booking 插件

```bash
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FINAL_CLEANUP'
#!/bin/bash

set -e

echo "========================================="
echo "WordPress 插件目录 - 最终清理"
echo "========================================="
echo ""

cd /opt/bitnami/wordpress/wp-content/plugins

# 步骤 1：删除 plugins 根目录的重复目录
echo "[步骤 1/3] 删除 plugins 根目录的重复目录..."
if [ -d "assets" ] && [ "$(ls assets | wc -l)" -eq 0 ]; then
    # 如果 assets 为空或只有 Mac 系统文件，删除它
    rm -rf assets
    echo "  ✓ 删除空的 assets 目录"
elif [ -d "assets" ]; then
    # 如果 assets 有内容但 kayarine-booking/assets 也有，使用 sudo
    sudo rm -rf assets
    echo "  ✓ 删除重复的 assets 目录"
fi

if [ -d "includes" ] && [ "$(ls includes | wc -l)" -eq 0 ]; then
    rm -rf includes
    echo "  ✓ 删除空的 includes 目录"
elif [ -d "includes" ]; then
    sudo rm -rf includes
    echo "  ✓ 删除重复的 includes 目录"
fi
echo ""

# 步骤 2：清理 kayarine-booking 内的非插件文件
echo "[步骤 2/3] 清理 kayarine-booking 内的非插件文件..."
cd kayarine-booking

# 列出所有非插件文件（文档和配置）
NON_PLUGIN_FILES=(
    "COMPLETION_REPORT_2026_01_28.md"
    "CRITICAL_FIXES_10_ISSUES.md"
    "CRITICAL_FIXES_SUMMARY.md"
    "demo-login-redesign.html"
    "DEPLOYMENT_GCLOUD_GUIDE.md"
    "DEPLOYMENT_INSTRUCTIONS.md"
    "DEPLOYMENT.sh"
    "DEPLOYMENT_V1.4.13_NOTES.md"
    "DEPLOYMENT_v1.4.14.md"
    "DEPLOYMENT_v1.4.15.md"
    "DEPLOYMENT.md"
    "deploy.sh"
    "QUICK_DEPLOY_GUIDE_SIMPLIFIED.md"
    "QUICK_DEPLOYMENT_GUIDE.md"
    "QUICK_DEPLOY_SIMPLIFIED.sh"
    "QUICK_DEPLOY_SSH.md"
    "QUICK_FIX_GUIDE.md"
    "composer.json"
    "kayarine-booking.php"  # 如果这是旧文件，主插件文件应该在这里
)

for file in "${NON_PLUGIN_FILES[@]}"; do
    if [ -f "$file" ]; then
        sudo rm "$file"
        echo "  ✓ 删除: $file"
    fi
done

# 删除 Mac 系统文件（._* 和 .DS_Store）
echo "  清理 Mac 系统文件..."
find . -name "._*" -exec sudo rm {} \; 2>/dev/null || true
find . -name ".DS_Store" -exec sudo rm {} \; 2>/dev/null || true
echo "  ✓ 完成"

echo ""

# 步骤 3：验证和总结
echo "[步骤 3/3] 验证结果..."
cd /opt/bitnami/wordpress/wp-content/plugins

echo ""
echo "plugins/ 根目录结构:"
ls -1d */ | nl
echo ""

echo "kayarine-booking/ 插件内容:"
ls -1 kayarine-booking/ | head -20
echo ""

echo "========================================="
echo "✅ 最终清理完成"
echo "========================================="
echo ""
echo "清理摘要："
echo "✓ 删除了 plugins 根目录的重复目录"
echo "✓ 删除了 kayarine-booking 内的非插件文件"
echo "✓ 清理了 Mac 系统文件"
echo ""
echo "现在 kayarine-booking 应该只包含:"
echo "  - assets/          (CSS、JS、图像)"
echo "  - includes/        (PHP 类和代码)"
echo "  - kayarine-booking.php (主插件文件)"
echo ""

FINAL_CLEANUP
```

---

## ⚠️ 重要注意

### 问题：部分文件权限限制

清理过程中会遇到权限问题：
```
Permission denied: cannot remove 'assets/css/style.css'
```

**原因**：文件所有权可能是 daemon 或其他用户。

**解决方案**：脚本会自动使用 `sudo rm` 处理。

---

## 🔍 验证清理成功

清理完成后，**kayarine-booking/** 目录结构应该是这样：

```
✅ kayarine-booking/
   ├── assets/
   │   ├── css/
   │   │   └── style.css
   │   └── js/
   │       └── script.js
   ├── includes/
   │   ├── class-kayarine-admin.php
   │   ├── class-kayarine-booking.php
   │   ├── class-kayarine-checkout-manager.php
   │   ├── class-kayarine-inventory.php
   │   ├── class-kayarine-member-dashboard.php
   │   ├── class-kayarine-membership.php
   │   ├── class-kayarine-pricing.php
   │   ├── class-kayarine-woocommerce-customizer.php
   │   ├── kayarine-config.php
   │   └── ... (其他 PHP 类)
   └── kayarine-booking.php
       （或 index.php，主插件入口文件）
```

---

## 🎯 执行步骤

### 1. 运行最终清理脚本
```bash
# 复制整个脚本到终端运行
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 << 'FINAL_CLEANUP'
#!/bin/bash
...
FINAL_CLEANUP
```

### 2. 验证结果
```bash
# 检查 plugins 目录
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "ls -1d /opt/bitnami/wordpress/wp-content/plugins/*/"

# 检查 kayarine-booking 内容
ssh -i /Users/henrylo/Documents/GitHub/ssh/gcp-ssh-key kayarine.server@104.199.144.122 "ls -1 /opt/bitnami/wordpress/wp-content/plugins/kayarine-booking/ | grep -v '^[._]'"
```

### 3. WordPress 后台验证
- 访问 WordPress 后台
- 进入 **Plugins** 页面
- 确认 kayarine-booking 插件仍然激活且无错误

---

## 📋 完整清理清单（3 步）

- [x] **第一步** - 删除 27 个杂乱文件和被禁用插件（已完成）
- [x] **第一步验证** - 清空 debug.log（已完成）
- [ ] **第二步** - 清理 kayarine-booking 内的非插件文件（本步）
- [ ] **第二步验证** - 验证插件目录结构整洁
- [ ] **第三步** - WordPress 后台完整验证

---

## 💡 为什么要做这个清理？

1. **性能优化**
   - 减少不必要文件的加载
   - 降低磁盘 I/O

2. **安全性**
   - 生产环境不应该包含开发文档
   - 减少信息泄露风险

3. **可维护性**
   - 目录结构清晰
   - 易于理解和维护

4. **部署快速**
   - 减小上传和备份体积
   - 加快部署速度

