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

# 或使用 Pest 直接运行（输出更详细）
./vendor/bin/pest

# 运行特定测试文件
./vendor/bin/pest tests/Codegen/SwiftUIBackendTest.php
```

---

## 项目结构

```
src/
├── App.php              # 入口
├── Build/               # 构建管线
├── Codegen/             # 11 个平台代码生成器
├── Generator/           # 5 个语言生成器
├── IR/                  # 54 个 IR 节点类型
└── UI/                  # DSL：16 个微件、样式、动作
    ├── Widget/          # 微件类
    ├── Styling/         # 样式系统
    └── Platform/        # 平台驱动
tests/                   # Pest 测试文件
docs/                    # VuePress 文档
examples/                # 示例应用
```

---

## 如何贡献

### 1. 添加新微件

请参阅[扩展指南——自定义微件](/zh/guide/extending.html#1-添加自定义微件)。

检查清单：
- [ ] 在 `src/UI/Widget/` 中创建微件类
- [ ] 在 `src/UI/WidgetKind.php` 中添加枚举值
- [ ] 在 `tests/Codegen/` 中添加测试（至少一个冒烟测试）
- [ ] 更新所有 11 个后端以处理新微件类型
- [ ] 更新 `docs/zh/guide/ui-components.md`
- [ ] 更新 `docs/zh/guide/api-reference.md`

### 2. 添加新后端

请参阅[扩展指南——自定义后端](/zh/guide/extending.html#2-添加自定义后端)。

检查清单：
- [ ] 在 `src/Codegen/` 中创建后端类
- [ ] 在 `src/Codegen/CodegenFactory.php` 中注册
- [ ] 实现 `supportedStyleProperties()`
- [ ] 在 `tests/Codegen/` 中创建测试文件
- [ ] 在 `docs/zh/guide/code-generation.md` 中添加文档

### 3. 添加 PHP 函数映射

请参阅[扩展指南——函数映射](/zh/guide/extending.html#4-添加-php-函数映射)。

检查清单：
- [ ] 在所有 5 个生成器中添加（`Swift`、`JavaScript`、`Kotlin`、`Dart`、`CSharp`）
- [ ] 在 `tests/Generator/` 中添加测试
- [ ] 更新 `docs/zh/guide/actions.md` 中的映射表

### 4. 改进文档

文档位于 `docs/` 目录，使用 VuePress。

```bash
# 启动开发服务器
npm run docs:dev

# 构建
npm run docs:build
```

中文翻译在 `docs/zh/` 目录。欢迎所有改进：
- 修正拼写错误或不清晰的解释
- 添加更多代码示例
- 改进 API 参考
- 添加更多中文翻译

### 5. 报告 Bug

在 GitHub 上创建 [Issue](https://github.com/yangweijie/perry-php/issues)，包含：
- **描述**：实际结果与期望结果的对比
- **复现**：最小化的 PHP 代码
- **环境**：PHP 版本、操作系统、目标平台
- **测试输出**：相关的测试结果或错误信息

---

## 编码规范

### PHP

- **严格类型**：所有文件必须以 `declare(strict_types=1);` 开头
- **PSR-4**：自动加载遵循 PSR-4
- **PSR-12**：代码风格遵循 PSR-12
- **类型提示**：使用类型化属性和返回类型
- **Docblocks**：对公开 API 使用 PHPDoc

```php
<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Slider extends Widget
{
    public function __construct(
        private float $min = 0.0,
        private float $max = 1.0,
        private float $step = 0.1,
        private ?\Perry\UI\Binding $value = null,
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Slider;
    }
}
```

### 测试

- 使用 **Pest PHP** 框架
- 测试文件放在 `tests/` 目录，与 `src/` 结构对应
- 测试文件以 `*Test.php` 后缀命名
- 最低测试：微件生成非空输出，无崩溃

```php
<?php

use Perry\UI\Widget\Text;

it('generates text widget', function () {
    $widget = new Text('Hello');
    // ... 断言生成的输出
});
```

### 文档

- 使用 **英式英语** 拼写
- 代码示例必须 **可测试**（复制粘贴即可运行）
- 使用带语言标记的围栏代码块：` ```php `
- 保持表格对齐

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

### PR 检查清单

- [ ] 测试通过（`composer test`）
- [ ] 新代码添加了对应的测试
- [ ] 文档已更新
- [ ] 代码遵循 PSR-12 标准
- [ ] 无死代码或注释掉的代码
- [ ] 每个 PR 单一用途

---

## 文档开发

```bash
# 安装
npm install

# 启动开发服务器（热重载，访问 http://localhost:8080/perry-php/）
npm run docs:dev

# 构建
npm run docs:build

# 预览生产构建
npx serve docs/.vuepress/dist
```

推送到 `main` 分支后，文档会自动部署到 GitHub Pages。

---

## 发布流程

1. 更新 `composer.json` 中的版本号（语义化版本）
2. 更新 `CHANGELOG.md`
3. 打标签：`git tag v1.x.x && git push --tags`
4. GitHub Actions 自动构建并发布到 Packagist

---

## 有问题？

在 GitHub 上创建 [Discussion](https://github.com/yangweijie/perry-php/discussions) 或 [Issue](https://github.com/yangweijie/perry-php/issues)。
