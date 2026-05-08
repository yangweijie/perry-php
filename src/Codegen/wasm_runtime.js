/**
 * Perry WASM Runtime — perry_ui_* bridge API for DOM manipulation.
 *
 * This runtime provides the `perry_ui_*` function family that Perry
 * codegen backends emit. It is designed to be API-compatible with
 * perry-ts's wasm_runtime.js so that generated code works identically
 * whether compiled via perry-ts (Rust → WASM binary) or perry-php
 * (PHP → generated JS → HTML).
 *
 * The runtime supports:
 *   - Widget creation & tree assembly (perry_ui_createWidget, addChild, etc.)
 *   - Styling (perry_ui_setStyle)
 *   - Text content (perry_ui_setTextContent, setInnerHTML)
 *   - Event handling (perry_ui_onClick, onInput, onChange, onSubmit)
 *   - State management (perry_ui_state_create, get, set)
 *   - Widget attribute operations (perry_ui_setAttribute, setProperty)
 *   - Value binding for form controls (perry_ui_setValue, getValue)
 *   - WASM loading via bootPerryWasm()
 *   - Handle-based DOM element tracking
 *   - Canvas 2D drawing (perry_ui_canvas_*)
 *   - Scroll position management (perry_ui_setScrollChild, scrollTo)
 *   - Tab/Screen visibility (perry_ui_showScreen, showTab)
 *   - File input trigger (perry_ui_triggerFileInput)
 *   - Pre/Post build lifecycle hooks
 */

(function () {
  'use strict';

  // ─── Handle Store ───
  // 1-indexed so handle 0 is always invalid/null.
  var handles = [null];
  var nextHandle = 1;

  // ─── State Store ───
  var state = {};

  // ─── Helper: resolve handle to DOM element ───
  function el(h) {
    if (h == null || h === 0) return null;
    return handles[h] || null;
  }

  // ─── Pre-build hook (called once before build() starts) ───
  // Override via window.__perry_preBuild to run setup before widget tree assembly.
  var preBuild = window.__perry_preBuild || function () {};

  // ─── Post-build hook (called once after build() finishes) ───
  // Override via window.__perry_postBuild to run setup after mount.
  var postBuild = window.__perry_postBuild || function () {};
  var postBuildCalled = false;

  // ─── Public API ───

  window.perry_ui_createWidget = function (tag) {
    var e;
    if (tag === 'svg') {
      e = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    } else {
      e = document.createElement(tag || 'div');
    }
    var h = nextHandle++;
    handles[h] = e;
    return h;
  };

  window.perry_ui_addChild = function (parent, child) {
    var p = el(parent);
    var c = el(child);
    if (p && c) p.appendChild(c);
  };

  window.perry_ui_insertChild = function (parent, child, index) {
    var p = el(parent);
    var c = el(child);
    if (p && c) {
      var ref = p.children[index] || null;
      p.insertBefore(c, ref);
    }
  };

  window.perry_ui_removeChild = function (parent, child) {
    var p = el(parent);
    var c = el(child);
    if (p && c && c.parentNode === p) p.removeChild(c);
  };

  window.perry_ui_clearChildren = function (handle) {
    var e = el(handle);
    if (e) e.innerHTML = '';
  };

  window.perry_ui_mount = function (selector, widgetHandle) {
    var root = document.querySelector(selector);
    if (!root) root = document.getElementById('perry-root');
    if (!root) return;
    var w = el(widgetHandle);
    if (w) root.appendChild(w);
  };

  window.perry_ui_insertBefore = function (container, child, refChild) {
    var c = el(container);
    var ch = el(child);
    var rf = el(refChild);
    if (c && ch) {
      if (rf && rf.parentNode === c) {
        c.insertBefore(ch, rf);
      } else {
        c.appendChild(ch);
      }
    }
  };

  // ─── Style ───

  window.perry_ui_setStyle = function (handle, prop, value) {
    var e = el(handle);
    if (!e) return;
    if (typeof value === 'number') value = String(value);
    e.style[prop] = value;
  };

  window.perry_ui_setStyles = function (handle, props) {
    var e = el(handle);
    if (!e) return;
    for (var k in props) {
      if (props.hasOwnProperty(k)) {
        e.style[k] = String(props[k]);
      }
    }
  };

  // ─── Content ───

  window.perry_ui_setTextContent = function (handle, text) {
    var e = el(handle);
    if (e) e.textContent = String(text);
  };

  window.perry_ui_setInnerHTML = function (handle, html) {
    var e = el(handle);
    if (e) e.innerHTML = String(html);
  };

  // ─── Attributes ───

  window.perry_ui_setAttribute = function (handle, attr, value) {
    var e = el(handle);
    if (e) e.setAttribute(attr, String(value));
  };

  window.perry_ui_setProperty = function (handle, prop, value) {
    var e = el(handle);
    if (e) e[prop] = value;
  };

  // ─── Events ───

  window.perry_ui_onClick = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('click', fn);
  };

  window.perry_ui_onInput = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('input', fn);
  };

  window.perry_ui_onChange = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('change', fn);
  };

  window.perry_ui_onSubmit = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('submit', fn);
  };

  window.perry_ui_onMouseEnter = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('mouseenter', fn);
  };

  window.perry_ui_onMouseLeave = function (handle, fn) {
    var e = el(handle);
    if (e) e.addEventListener('mouseleave', fn);
  };

  // ─── Value binding (form controls) ───

  window.perry_ui_setValue = function (handle, value) {
    var e = el(handle);
    if (!e) return;
    e.value = value;
  };

  window.perry_ui_getValue = function (handle) {
    var e = el(handle);
    return e ? e.value : '';
  };

  window.perry_ui_setChecked = function (handle, checked) {
    var e = el(handle);
    if (e) e.checked = !!checked;
  };

  // ─── State ───

  window.perry_ui_state_create = function (key, initialValue) {
    state[key] = initialValue;
  };

  window.perry_ui_state_get = function (key) {
    return state.hasOwnProperty(key) ? state[key] : undefined;
  };

  window.perry_ui_state_set = function (key, value) {
    state[key] = value;
  };

  window.perry_ui_state_keys = function () {
    return Object.keys(state);
  };

  // ─── Element query ───

  window.perry_ui_getElementById = function (id) {
    var e = document.getElementById(id);
    if (!e) return 0;
    var h = nextHandle++;
    handles[h] = e;
    return h;
  };

  window.perry_ui_querySelector = function (selector) {
    var e = document.querySelector(selector);
    if (!e) return 0;
    var h = nextHandle++;
    handles[h] = e;
    return h;
  };

  // ─── Specialized widget helpers ───

  window.perry_ui_setScrollChild = function (scrollHandle, childHandle) {
    // Clear existing, add child
    var s = el(scrollHandle);
    var c = el(childHandle);
    if (s) {
      s.innerHTML = '';
      if (c) s.appendChild(c);
    }
  };

  window.perry_ui_scrollTo = function (handle, x, y) {
    var e = el(handle);
    if (e) { e.scrollLeft = x; e.scrollTop = y; }
  };

  window.perry_ui_showScreen = function (navHandle, screenIndex) {
    // For NavigationView: show children[screenIndex], hide others
    var n = el(navHandle);
    if (!n) return;
    for (var i = 0; i < n.children.length; i++) {
      n.children[i].style.display = (i === screenIndex) ? '' : 'none';
    }
  };

  window.perry_ui_showTab = function (tabHandle, tabIndex) {
    // For TabView: show tab[tabIndex] contents, hide others
    var t = el(tabHandle);
    if (!t) return;
    for (var i = 0; i < t.children.length; i++) {
      t.children[i].style.display = (i === tabIndex) ? '' : 'none';
    }
  };

  window.perry_ui_triggerFileInput = function (handle) {
    var e = el(handle);
    if (e) e.click();
  };

  // ─── Canvas 2D ───

  window.perry_ui_canvas_getContext = function (handle, type) {
    var e = el(handle);
    if (!e || !e.getContext) return 0;
    var ctx = e.getContext(type || '2d');
    if (!ctx) return 0;
    var h = nextHandle++;
    handles[h] = ctx;
    return h;
  };

  window.perry_ui_canvas_fillStyle = function (ctxHandle, style) {
    var ctx = handles[ctxHandle];
    if (ctx && typeof ctx.fillStyle !== 'undefined') ctx.fillStyle = style;
  };

  window.perry_ui_canvas_fillRect = function (ctxHandle, x, y, w, h) {
    var ctx = handles[ctxHandle];
    if (ctx && typeof ctx.fillRect === 'function') ctx.fillRect(x, y, w, h);
  };

  window.perry_ui_canvas_clearRect = function (ctxHandle, x, y, w, h) {
    var ctx = handles[ctxHandle];
    if (ctx && typeof ctx.clearRect === 'function') ctx.clearRect(x, y, w, h);
  };

  window.perry_ui_canvas_fillText = function (ctxHandle, text, x, y) {
    var ctx = handles[ctxHandle];
    if (ctx && typeof ctx.fillText === 'function') ctx.fillText(String(text), x, y);
  };

  window.perry_ui_canvas_stroke = function (ctxHandle) {
    var ctx = handles[ctxHandle];
    if (ctx && typeof ctx.stroke === 'function') ctx.stroke();
  };

  // ─── WASM Loading ───

  /**
   * bootPerryWasm(wasmB64, ffiImports)
   *
   * Decodes a base64-encoded WebAssembly binary, instantiates it with
   * the Perry perry_ui_* bridge as imports, and mounts it.
   *
   * @param {string} wasmB64 - Base64-encoded WASM binary
   * @param {Object} [ffiImports] - Additional FFI imports (optional)
   * @returns {Promise<WebAssembly.Instance>}
   */
  window.bootPerryWasm = function (wasmB64, ffiImports) {
    var binary = atob(wasmB64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }

    // Build import object: perry_ui_* bridge + user FFI
    var bridge = {
      perry_ui_widget_create: window.perry_ui_createWidget,
      perry_ui_widget_add_child: window.perry_ui_addChild,
      perry_ui_widget_remove_child: window.perry_ui_removeChild,
      perry_ui_clear_children: window.perry_ui_clearChildren,
      perry_ui_set_style: window.perry_ui_setStyle,
      perry_ui_set_text_content: window.perry_ui_setTextContent,
      perry_ui_set_inner_html: window.perry_ui_setInnerHTML,
      perry_ui_mount: window.perry_ui_mount,
      perry_ui_state_create: window.perry_ui_state_create,
      perry_ui_state_get: window.perry_ui_state_get,
      perry_ui_state_set: window.perry_ui_state_set,
      perry_ui_on_click: window.perry_ui_onClick,
      perry_ui_on_input: window.perry_ui_onInput,
      perry_ui_on_change: window.perry_ui_onChange,
    };

    var importObj = {
      env: bridge,
      wasi_snapshot_preview1: {},
    };

    // Merge user FFI imports
    if (ffiImports) {
      for (var mod in ffiImports) {
        if (ffiImports.hasOwnProperty(mod)) {
          importObj[mod] = ffiImports[mod];
        }
      }
    }

    return WebAssembly.instantiate(bytes, importObj).then(function (result) {
      if (result.instance.exports._start) {
        result.instance.exports._start();
      } else if (result.instance.exports.main) {
        result.instance.exports.main();
      }
      return result.instance;
    });
  };

  // ─── Render lifecycle ───

  window.perry_ui_render = function (buildFn) {
    preBuild();
    postBuildCalled = false;
    buildFn();
    if (!postBuildCalled) {
      postBuild();
      postBuildCalled = true;
    }
  };

  // ─── Expose handle store for debugging ───
  window.__perry_handles = handles;
  window.__perry_state = state;

})();
