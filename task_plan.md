# Task Plan: Fix WearTiles State Binding & SwiftUI Style Coverage

## Goal
Fix two identified backend issues in perry-php:
1. **WearTilesBackend**: Add state binding integration (action generation, reactive updates)
2. **SwiftUIBackend**: Improve style coverage from 65% to near-parity (fix ghost properties, missing properties, and bugs)

## Phases

### Phase 1: Research & Analysis
- [x] Read WearTilesBackend.php
- [x] Read SwiftUIBackend.php
- [x] Read GlanceBackend.php (reference for state binding)
- [x] Read StyleProperty.php (all 51 properties)
- [x] Read existing tests
- [x] Document findings in findings.md

### Phase 2: Fix WearTiles State Binding
- [x] Add `generateAction()` method (SetValue/Append/Clear/Closure/Custom)
- [x] Integrate action into `generateButton()`
- [x] Add reactive update mechanism (TileUpdater / requestRebus)
- [x] Add `supportedStyleProperties()` method (currently missing)

### Phase 3: Fix SwiftUI Style Coverage
- [x] Implement missing 9 properties in `generateModifiers()`
- [x] Implement 9 ghost properties (declared but no code)
- [x] Fix bugs: overline mapping, justifyContent mapping, BorderColor dependency
- [x] Add FlexShrink, Gap, FlexDirection/FlexWrap where feasible

### Phase 4: Validation
- [x] Run existing tests
- [x] Verify generated code compiles
- [x] Run full test suite

## Decisions
| Decision | Rationale |
|----------|-----------|
| WearTiles: Use Futures-based async pattern | Wear Tiles API uses ListenableFuture, not Compose |
| WearTiles: Generate `requestRebus()` wrapper | Triggers tile re-render on state change |
| SwiftUI: Prefer `.animation(_:value:)` over deprecated `.animation()` | Modern SwiftUI API |
| SwiftUI: Map `Gap` to VStack/HStack `spacing` | Most natural SwiftUI mapping |
| SwiftUI: Map `Margin` to `.padding()` | SwiftUI has no margin modifier |

## Status
- Phase 1: **complete**
- Phase 2: **complete**
- Phase 3: **complete**
- Phase 4: **complete** ✅
