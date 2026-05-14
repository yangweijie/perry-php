# 最佳实践

编写高质量、可维护的 Perry PHP 代码的指南。

---

## 1. 优先使用 `Binding` 而非 `State`/`StateId`

所有新代码都应使用 `Binding`。它是声明式的，由 `AppContainer` 自动收集，且支持所有微件。

```php
// ✅ 好——声明式，自动收集
$count = new Binding('count', 0);
$label = new Binding('label', 'Clicks: 0');

// ❌ 避免——手动 State 管理（旧版）
$state = new State();
$count = $state->create(0);
```

**原因：** 当你将 `Binding` 传给 `Text` 微件时，`AppContainer` 会自动发现它们。后端就知道需要声明哪些 `@State` / `const state` 变量。

---

## 2. 对参数化按钮使用嵌套闭包模式

当创建多个行为相似的按钮时（如计算器键盘），使用工厂函数配合 `compact()`：

```php
// ✅ 好——可复用，无代码重复
function numBtn(string $digit, Binding $display): Button {
    return new Button($digit, Action::fromClosure(
        function () use ($digit, $display) {
            $display .= $digit;
        },
        compact('digit')  // 在生成的代码中将 $digit 替换为字面量
    ));
}

$row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
```

**原因：** 如果不使用 `compact('digit')`，PHP 闭包会通过引用捕获变量。转译器需要显式的绑定替换来将字面量内联到生成的代码中。

---

## 3. 按关注点组织 Binding

相关的 Binding 放在一起，并给予描述性名称：

```php
// ✅ 好——清晰的命名，按功能分组
$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

// ❌ 避免——晦涩的名称
$a = new Binding('a', 0);
$b = new Binding('b', '');
```

---

## 4. 始终将额外 Binding 传递给 `AppContainer`

在闭包动作中使用但未附加到 `Text` 微件的任何 `Binding` 都必须显式传递给 `AppContainer`：

```php
$operand1 = new Binding('operand1', 0.0);

$app = new AppContainer(
    $contentWidget,
    320, 480,
    // ✅ 在此传递非 Text 的 Binding
    $operand1,
);
```

---

## 5. 使用 `Style::make()` 链式调用

以 fluent 方式构建样式——绝不手动构造原始 `Style` 对象：

```php
// ✅ 好——fluent、可读
$btnStyle = Style::make()
    ->backgroundColor('#007AFF')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->padding(12)
    ->cornerRadius(8);
```

---

## 6. 合并基础样式以保持一致性

定义基础样式，合并每个实例的覆盖：

```php
$baseBtn = Style::make()->fontSize(14)->padding(8)->cornerRadius(4);

$primaryBtn = $baseBtn->merge(
    Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')
);
```

---

## 7. 复杂逻辑使用 `Action::fromClosure()`

简单赋值用 `Action::set()`，带条件或多步骤的逻辑使用闭包：

```php
// ✅ 简单 → Action::set
$btn = new Button('Reset', Action::set($display, '0'));

// ✅ 复杂 → 闭包
$btn = new Button('±', Action::fromClosure(function () use ($display) {
    if (str_starts_with((string) $display, '-')) {
        $display = substr((string) $display, 1);
    } else {
        $display = '-' . $display;
    }
}));
```

---

## 8. 尽早测试多个后端

不同后端对样式属性的支持不同。开发时至少在两个后端上验证 UI：

```bash
# 先检查 HTML 输出（迭代最快）
php your-app.php > test.html

# 然后在原生平台上验证
php your-app.php > App.swift
```

使用 `supportedStyleProperties()` 检查可用属性：

```php
$backend = $app->codegen()->get('swiftui');
$supported = $backend->supportedStyleProperties();
```

---

## 9. 保持微件树结构清晰易读

使用中间变量，避免深度嵌套：

```php
// ✅ 好——可读性好
$header = new Text('Calculator');
$display_area = (new Text($display))->style(Style::make()->fontSize(32));
$button_row = new HStack(numBtn('1', $display), numBtn('2', $display));
$root = new VStack($header, $display_area, $button_row);
```

---

## 10. 根据复杂度选择合适的 UI 方式

| 场景 | 方案 |
|----------|----------|
| 简单表单、计数器、开关 | 原生微件（Text, Button, Toggle） |
| 复杂数据可视化 | `WebView` + HTML/JS |
| 最大性能 | 原生微件 |

---

## 11. Binding 名称必须唯一

Binding 名称在应用中必须唯一。它们会成为生成代码中的变量名：

```php
// ✅ 好——唯一、描述性
$userName = new Binding('userName', '');

// ❌ 避免——过于通用
$data = new Binding('data', '');
$value = new Binding('value', '');
```
