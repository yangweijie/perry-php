# Findings: WearTiles & SwiftUI Backend Issues

## WearTilesBackend State Binding

### Current State
- Generates state `var` fields in TileService class (persist only within service instance)
- `generateButton()` has NO action code at all
- No `generateAction()` method
- Button just generates static LayoutElementBuilders.Button with text only
- State is NOT in companion object → lost on service recreate
- No clickable/actionable interactive elements

### Fix Plan
1. Add companion object to hold persistent state
2. Add `generateAction()` method (SetValue/Append/Clear/Closure/Custom)
3. Generate Clickable.Builder() for buttons
4. Use requestRebus() pattern with companion object state mutation
5. Handle pending actions on each tile request

### Wear Tiles API Constraints
- Buttons use `Clickable.Builder().setOnClick(Action)` where Action is `ActionBuilders.Action`
- Actions use `ActionBuilders.ActionLaunchRequest.Builder().setIntent(Intent)`
- State changes require companion object (class instance is recreated)
- `requestRebus()` triggers tile re-render

## SwiftUIBackend Style Coverage

### Current Issues

#### Missing (not in supportedStyleProperties): 9
| Property | Count | SwiftUI Mapping |
|----------|-------|-----------------|
| LetterSpacing | never declared | `.tracking(N)` |
| MinWidth | never declared | `.frame(minWidth: N)` |
| MaxWidth | never declared | `.frame(maxWidth: N)` |
| MaxHeight | never declared | `.frame(maxHeight: N)` |
| AnimationDelay | never declared | Skip (keyframe only) |
| AnimationIterationCount | never declared | Skip |
| AnimationDirection | never declared | Skip |
| AnimationFillMode | never declared | Skip |
| AnimationPlayState | never declared | Skip |

#### Ghost (declared but no generateModifiers code): 9
| Property | Fix |
|----------|-----|
| MinHeight | Add to frame modifier |
| Margin | Map to `.padding(N)` |
| FlexDirection | Skip (SwiftUI uses VStack/HStack, not relevant) |
| FlexWrap | Skip (not supported in SwiftUI) |
| Gap | Map to VStack/HStack `spacing:` parameter |
| FlexShrink | `.layoutPriority(0)` for shrink |
| AnimationDuration | Skip (keyframe animation, not transition) |
| AnimationEasing | Skip |
| TransitionProperty | Skip (all-property transitions) |

#### Bugs: 3
1. `overline` → generates invalid `.overline` → remove
2. JustifyContent → all values map to `.frame(maxWidth: .infinity)` → differentiate
3. BorderColor without BorderWidth → not rendered → generate overlay separately
