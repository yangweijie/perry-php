# 状态管理

Perry 提供两种状态管理方式：`Binding`（声明式，推荐）和 `State`/`StateId`（底层）。

---

## Binding

声明式的双向数据绑定，是管理状态的首选方式。

```php
use Perry\UI\Binding;

$count = new Binding('count', 0);         // int
$display = new Binding('display', '0');   // string
$visible = new Binding('visible', true);  // bool
$opacity = new Binding('opacity', 1.0);   // float
```

### 工作原理

1. 将 `Binding` 传给 `Text` 微件：`new Text($display)`
2. `AppContainer` 自动从微件树中收集所有 Binding
3. 后端生成对应的状态声明：

| 后端 | 生成的代码 |
|---------|---------------|
| SwiftUI | `@State private var display = "0"` |
| JavaScript | `const state = { display: "0" }` |
| Kotlin | `var display = mutableStateOf("0")` |
| Dart | `var display = ValueNotifier("0")` |
| C# | `var display = "0";` |

4. 当按钮动作修改 `$display` 时，生成的代码会赋值给状态变量
5. 重新渲染更新所有绑定的 `Text` 微件

### 在闭包动作中使用 Binding

```php
$count = new Binding('count', 0);

$action = Action::fromClosure(function () use ($count) {
    $count += 1;
});

// 生成的 Swift：count = count + 1
// 生成的 JS：   state.count = state.count + 1
// 生成的 Kotlin：count.value = count.value + 1
```

### 构造函数

| 参数 | 类型 | 说明 |
|-----------|------|-------------|
| `$name` | `string` | 生成代码中的变量名 |
| `$initialValue` | `mixed` | 默认值（`string`、`int`、`float`、`bool`） |

---

## State / StateId

用于 `TextInput` 和 `Toggle` 微件的底层状态管理（新代码请优先使用 `Binding`）。

```php
use Perry\UI\State;

$state = new State();

// 创建状态条目
$name = $state->create('');           // StateId
$darkMode = $state->create(false);    // StateId
$speed = $state->create(1.0);         // StateId

// 读取值
$currentName = $state->get($name);    // ''

// 更新值
$state->set($name, 'Alice');

// 订阅变化
$state->subscribe($name, function (mixed $newValue) {
    echo "名称变更为：$newValue\n";
});
```

### 使用建议

| 方式 | 何时使用 |
|----------|-------------|
| `Binding` | **大多数情况。** 声明式、由 AppContainer 自动收集、支持所有微件。 |
| `State`/`StateId` | 需要 `StateId` 的旧版 `TextInput` 和 `Toggle` 微件。 |

> **提示：** 新代码始终优先使用 `Binding`。它更简单、更强大，而且自动收集。
