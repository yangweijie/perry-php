# Perry PHP — 完善计划 V2

## 目标
系统性完善 perry-php 所有 11 个后端，修复真实 bug，补齐剩余样式属性。

## 阶段

### Phase 1: Glance TextStyle Bugfix + 属性补齐
- [x] 发现 bug: 多个 TextStyle 属性 (FontSize + FontWeight + TextAlignment) 各自生成独立 `style = TextStyle(...)` 调用，只有最后一个生效
- [ ] 修复: 合并所有 TextStyle 属性到单个 TextStyle 构造调用
- [ ] 新增: FontFamily, TextDecoration, LineSpacing, LetterSpacing 到 TextStyle
- [ ] 更新 supportedStyleProperties() (16→20 props)

### Phase 2: WearTiles Opacity
- [ ] Column/Row: 添加 `.setOpacity(N)` 到容器 widgets
- [ ] 更新 supportedStyleProperties()

### Phase 3: Flutter ShadowOffsetX/Y
- [ ] 在 BoxShadow / Material elevation 方案中评估可行性
- [ ] 如果简单可实现: 添加 ShadowOffsetX/Y 支持

### Phase 4: 测试覆盖
- [ ] Glance TextStyle 合并后测试
- [ ] Glance 新增属性 (FontFamily, TextDecoration) 针对性测试
- [ ] WearTiles Opacity 针对性测试
- [ ] 验证 285+ 测试全部通过

### Phase 5: 最终验证
- [ ] 运行完整测试套件
- [ ] 更新 PROGRESS.md 中的数字

## 决策日志
| 日期 | 决定 | 理由 |
|------|------|------|
| - | - | - |

## 错误日志
| 错误 | 尝试 | 解决方法 |
|------|------|---------|
| - | - | - |
