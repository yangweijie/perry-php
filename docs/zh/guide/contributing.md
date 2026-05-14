# 贡献指南

感谢你对 Perry PHP 的关注！我们欢迎各种形式的贡献——Bug 修复、新功能、文档改进等。

---

## 行为准则

请保持尊重、建设性和包容性。不容忍骚扰和歧视行为。

---

## 准备工作

### 环境要求

- PHP 8.2+
- Composer
- Node.js 18+（用于文档）

### 安装

```bash
# 克隆仓库
git clone https://github.com/yangweijie/perry-php.git
cd perry-php

# 安装 PHP 依赖
composer install

# 安装文档依赖（可选）
npm install
```

### 运行测试

```bash
# 运行所有测试
composer test

# 或使用 Pest 直接运行
./vendor/bin/pest

# 运行特定测试文件
./vendor/bin/pest tests/Codegen/SwiftUIBackendTest.php
```

---

## 如何贡献

### 1. 添加新微件

请参阅[扩展指南——自定义微件](/zh/guide/extending.html#1-添加自定义微件)。

检查清单：
- [ ] 在 `src/UI/Widget/` 中创建微件类
- [ ] 在 `src/UI/WidgetKind.php` 中添加枚举值
- [ ] 在 `tests/Codegen/` 中添加测试
- [ ] 更新所有 11 个后端以处理新的微件类型
- [ ] 更新 `docs/zh/guide/ui-components.md`

### 2. 添加新后端

请参阅[扩展指南——自定义后端](/zh/guide/extending.html#2-添加自定义后端)。

### 3. 改进文档

文档位于 `docs/` 目录，使用 VuePress。

```bash
# 启动开发服务器
npm run docs:dev

# 构建
npm run docs:build
```

中文翻译在 `docs/zh/` 目录。

### 4. 报告 Bug

在 GitHub 上创建 [Issue](https://github.com/yangweijie/perry-php/issues)，包含：
- **描述**：发生了什么与期望结果的对比
- **复现**：最小化的 PHP 代码
- **环境**：PHP 版本、操作系统、目标平台

---

## 编码规范

### PHP

- **严格类型**：所有文件必须以 `declare(strict_types=1);` 开头
- **PSR-4**：自动加载遵循 PSR-4
- **PSR-12**：代码风格遵循 PSR-12
- **类型提示**：使用类型化属性和返回类型

### 测试

- 使用 **Pest PHP** 框架
- 测试文件放在 `tests/` 目录，与 `src/` 结构对应
- 测试文件以 `*Test.php` 后缀命名

---

## Pull Request 流程

1. **Fork** 仓库
2. **创建分支**：`git checkout -b feature/your-feature`
3. **做出修改**，遵循编码规范
4. **添加测试**，覆盖新功能
5. **运行测试**：`composer test` —— 必须全部通过
6. **更新文档**，如果 API 或行为发生变化
7. **提交**，使用清晰描述性的提交信息
8. **推送到**你的 Fork
9. **创建 Pull Request**，目标为 `main` 分支
