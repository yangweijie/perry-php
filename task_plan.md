# 代码库 Bug 修复计划

## 目标
按优先级修复 findings.md 中识别的所有 P0 和 P1 问题。

## 修复顺序

### Phase 1: P0 单行/小修复（生成错误代码的问题）
- [x] #6 WearTiles `requestRebus()` → `requestRebuild()`
- [x] #4 CGenerator 全 int → 类型感知
- [x] #7 KotlinGenerator 全 Double → 类型感知（支持类型映射参数）
- [x] #20 Glance `colorFilter` 修复（改为正确的 TextStyle color）
- [x] #21 Flutter SegmentedControl 修复（SegmentedButton → ButtonSegment）
- [x] #5 HtmlBackend 静态属性重置（generate() 入口重置）
- [x] #2 AppContainer::kind() + WidgetKind 条目
- [x] #10 Style::merge() 响应式变体丢失
- [x] #8 Generator 不可重入修复（6 个 Generator 全部添加 resetState）
- [x] #9 CornerRadius 枚举命名统一（cornerRadius → corner_radius）

### Phase 2: P0 中等修复
- [x] #3 Action::calculate() 操作数丢失（存入 closureBindings）
- [x] #1 Generator 抽象基类提取（AbstractGenerator + 5 个生成器继承）

### Phase 3: P1 修复
- [x] Widget 模型缺陷（Text/TabView/TextInput）
- [x] 测试断言增强
  - [x] SwiftUI 编译验证（swiftc calculator + button）
  - [x] 边界测试（特殊字符、深度嵌套、空值）
  - [x] 集成测试（Action::calculate、Text::content、TabView children）

## 最终状态
- **全部 findings.md 中识别的问题 100% 已修复** ✅
- **Tests: 全部通过, 0 failed** ✅