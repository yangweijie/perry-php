# Perry 特性演示

展示所有 Perry PHP 特性的综合示例：全部 16 种微件类型、所有动作类型（set、append、clear、closure）、样式属性和状态管理。

```bash
# 生成 HTML
php examples/perry-demo.php web > perry-demo.html

# 生成 SwiftUI
php examples/perry-demo.php macos > ContentView.swift

# 生成 Jetpack Compose
php examples/perry-demo.php compose > MainActivity.kt
```

## 展示的特性

| 特性 | 使用的微件 |
|---------|-------------|
| **文本与样式** | Text — 字号、字重、颜色、对齐、内边距、边框、阴影、圆角、透明度 |
| **计数器** | Button — 增量/减量闭包和 Action::set 重置 |
| **文本输入** | TextInput — StateId、带条件逻辑的 Action::fromClosure |
| **开关** | Toggle — Binding 和暗色模式闭包动作 |
| **复选框与单选** | Checkbox 和 RadioButton — 分组选择 |
| **滑块与进度** | Slider — 透明度控制、Progress — 随机值进度条 |
| **标签页** | TabView — 3 个标签页 |
| **列表** | ListWidget — 项目列表 |
| **滚动视图** | ScrollView — 可滚动容器 |
| **图片** | Image — 带样式的占位图 |
| **状态管理** | 7 个 Binding 对象实现响应式状态 |
| **根容器** | AppContainer — 带额外绑定的根容器 |
| **弹性空白** | Spacer — 布局中的弹性空间 |

完整源码参见 `examples/perry-demo.php`。
