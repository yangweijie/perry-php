# Perry Optimization Plan — From libuiBuilder Learnings

## Goal

Implement 5 design improvements inspired by libuiBuilder to make Perry's PHP DSL more declarative, maintainable, and developer-friendly.

---

## Feature 1: Declarative HTML Frontend (Perry HTML DSL)

**Priority: P0** — Most impactful for developer experience

### Current State
Widget tree built imperatively via PHP: `new VStack(new Text(...), new Button(...)).style($s)`.

### Target State
HTML-based DSL that parses to Perry Widget tree, usable from any backend (not just HTML).

### Design

```
<!-- calculator.ui -->
<ui target="macos|web|android">
  <bind name="display" default="0" />
  <bind name="result" default="" />
  <bind name="operand1" default="0" />
  <bind name="operation" default="" />

  <vstack style="bgBlack">
    <vstack style="displayArea">
      <text bind="result" style="resultStyle" />
      <text bind="display" style="displayStyle" />
    </vstack>
    <!-- button rows -->
    <hstack>
      <button onclick="clear">C</button>
      <button onclick="percent">%</button>
      <button onclick="divide">÷</button>
    </hstack>
  </vstack>
</ui>
```

### Implementation Plan

1. **Create `src/UI/Frontend/` directory** with:
   - `HtmlFrontend.php` — HTML parser (PHP DOMDocument), tag → Widget factory
   - `FrontendWidgetFactory.php` — creates Widget instances from parsed config
   - `TagMapper.php` — HTML tag → WidgetKind mapping (vstack→VStack, etc.)
   - `AttributeResolver.php` — resolves `bind`, `style`, `onclick` to Perry objects

2. **Integration**: `HtmlFrontend::parse(string $html): Widget` returns root Widget

3. **Resuability**: Same parsed Widget tree targets ALL 11 backends

### Key Design Decisions
- Use PHP's built-in `DOMDocument` (same as libuiBuilder)
- Pure parse → no runtime dependency
- `style` attribute references named Style objects defined in PHP code
- `bind` attribute auto-creates `Binding` + `StateId` from `<bind>` definitions

---

## Feature 2: Declarative Binding + Batch Update

**Priority: P0** — Foundation for all reactive features

### Current State
- `StateId` is opaque random hex string — no named key access
- `State::set()` fires one notification per call
- `Binding` is just `name` + `initialValue` — no runtime state wiring
- Widgets store `StateId`, not `Binding` — inconsistent (some use Binding, some StateId)

### Target State
Named state keys with diff-based batch updates.

### Implementation

1. **Add `NamedState` class** (or extend State):
   ```php
   final class NamedState {
       private array $store = [];     // string key → mixed
       private array $watchers = [];  // string key → callable[]
       private array $batch = [];     // pending updates

       public function set(string $key, mixed $value): void
       public function get(string $key, mixed $default = null): mixed
       public function watch(string $key, callable $callback): void

       // Batch: collect changes, then notify once
       public function beginBatch(): void
       public function commitBatch(): void  // diff old/new, notify only changed keys
       public function update(array $updates): void  // set() + commitBatch()
   }
   ```

2. **Migrate widgets to use `Binding` consistently**:
   - `TextInput` uses `StateId $value` → change to `Binding $value`
   - `Slider`, `Toggle`, etc. — same migration
   - `AppContainer` stores `Binding[]` — keep this, make it the source of truth

3. **Deprecate `StateId`** for public API (keep internally for backward compat)

4. **Codegen integration**: Backends emit batch-compatible state code:
   - JS: `state.beginBatch(); state.display = '42'; state.result = '...='; state.commitBatch();`
   - Swift: single `state.setValue(...)` calls become batch calls

### Benefits
- Named state → HTML DSL `bind="display"` works naturally
- Batch updates → fewer UI refreshes

---

## Feature 3: Event Decoupling — Named Action Dispatch

**Priority: P1** — Clean separation of UI definition from logic

### Current State
Closures directly embedded in widget tree:
```php
$clear = Action::fromClosure(function() use ($display, $result, ...) { ... });
```
Actions reference specific `Binding` objects — tight coupling.

### Target State
Named actions, dispatched by name from UI:
```php
$app->on('clear', function(NamedState $state) {
    $state->update(['display' => '0', 'result' => '', 'operand1' => 0, ...]);
});
```

### Implementation

1. **Add `ActionRegistry`** — named action map:
   ```php
   final class ActionRegistry {
       private array $actions = [];  // string name → callable(NamedState): void
       public function register(string $name, callable $handler): void
       public function dispatch(string $name, NamedState $state): void
   }
   ```

2. **Action closure refactoring**: Receives `NamedState` directly instead of capturing individual bindings.

3. **Codegen integration**:
   - HTML/JS: `onclick="dispatch('clear')"` → `function dispatch(name) { ... }`
   - Swift: `Button("C", action: { dispatch("clear") })`
   - Closures still transpiled via existing IR pipeline, but with new signature

4. **Backward compat**: Old-style `Action::fromClosure()` still works with explicit binding captures. New-style actions are additive.

### Benefits
- HTML DSL `onclick="clear"` maps directly to `dispatch('clear')`
- UI definition separates from action logic — swap UIs without rewriting actions
- Reusable action handlers across multiple target platforms

---

## Feature 4: Custom User Components / Templates

**Priority: P1** — Reusability and composition

### Current State
No component abstraction. Each page builds a flat widget tree. No template reuse.

### Target State
PHP class components + HTML `<template>` / `<use>` support.

### Design

Option A — PHP Component Classes:
```php
class CalculatorButton extends PerryComponent {
    public function render(): Widget {
        return new Button($this->props['label'])
            ->style($this->props['style'])
            ->onClick($this->props['onClick']);
    }
}
```

Option B — HTML Templates:
```html
<template id="calcBtn">
    <button onclick="handleNumber" bind="display">{{label}}</button>
</template>

<use template="calcBtn" label="7" />
<use template="calcBtn" label="8" />
```

### Implementation

1. **Option B first** (lower effort, directly from HTML DSL):
   - `<template id="xxx">` support in HtmlFrontend
   - `<use template="xxx" prop1="val1">` support
   - Template variable substitution (`{{prop}}`)

2. **Option A second** (higher effort but more powerful):
   - `PerryComponent` abstract class
   - `ComponentRegistry` — register by name
   - Render pipeline: PHP component → widget tree → codegen

---

## Feature 5: Live Preview via HTML Backend

**Priority: P2** — Developer tooling

### Current State
Must rebuild to see changes. No iterative design workflow.

### Target State
File watcher + auto-regenerate HTML + browser hot reload.

### Implementation

1. **Live Preview Server** (`src/Dev/LivePreview.php`):
   ```bash
   php bin/perry preview calculator.php
   # Starts PHP built-in server on :8080
   # Watches calculator.php for changes
   # Generates HTML on request
   ```

2. **Auto-reload**: Generated HTML includes `<script>` that polls or uses SSE for hot reload.

3. **No new codegen backend needed**: Reuses existing `HtmlBackend`.

4. **Component tree view**: Bonus — generated HTML can include debug overlay showing widget tree structure.

---

## Implementation Order

```
Phase 1 (Foundation):    Feature 2 (NamedState) + Feature 3 (ActionRegistry)
                         → No codegen changes
                         → Tests for new state API

Phase 2 (Frontend):      Feature 1 (HtmlFrontend)
                         → Requires Feature 2 + 3
                         → Parser tests

Phase 3 (Reuse):         Feature 4 (Templates)
                         → Requires Feature 1

Phase 4 (Tooling):       Feature 5 (Live Preview)
                         → Requires Feature 1 + 2
```

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| Breaking `StateId` API | Keep `StateId` as internal — add `NamedState` alongside |
| Closure transpilation breakage | Old `Action::fromClosure()` untouhed; new dispatch pattern is additive |
| Widget constructor params | Migrate incrementally, one widget at a time |
| Test failures | Existing 914 tests must all pass after each phase |

## Success Criteria

- [ ] All 914 existing tests pass (0 failures)
- [ ] HtmlFrontend parses calculator HTML → correct Widget tree
- [ ] NamedState supports batch update with correct diff
- [ ] ActionRegistry dispatches named actions to correct handler
- [ ] `<template>`/`<use>` produces correct widget sub-tree
- [ ] `php bin/perry preview calculator.php` opens live-reload HTML
