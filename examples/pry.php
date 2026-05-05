<?php
declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        $prefix = 'Perry\\';
        $baseDir = __DIR__ . '/../src/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) require $file;
    });
}

use Perry\App;
use Perry\Build\Target;
use Perry\Codegen\HtmlBackend;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextEditor;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\WebView;

$jsonInput = new Binding('jsonInput', '');
$treeHtml  = new Binding('treeHtml', '');
$stats     = new Binding('stats', 'Paste JSON to begin');
$searchInfo = new Binding('searchInfo', '');

$target = $argv[1] ?? 'macos';
$isWeb = in_array($target, ['web', 'wasm']);

// Helper to count JSON nodes recursively
$countJsonNodes = function ($v) use (&$countJsonNodes): int {
    if ($v === null || !is_array($v) && !is_object($v)) return 1;
    $c = 1;
    if (is_array($v)) {
        foreach ($v as $item) $c += $countJsonNodes($item);
    } elseif (is_object($v)) {
        foreach (get_object_vars($v) as $val) $c += $countJsonNodes($val);
    }
    return $c;
};

// Web mode: Action::custom() with JavaScript tree view
// macOS mode: Action::fromClosure() with PHP JSON parsing

// Always define the customScript (needed for both web output and macOS WebView embedding)
$pryCustomScript = <<<'JS'
(function() {
var s = document.createElement("style");
s.textContent = `
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#1e1e1e;color:#d4d4d4}
.tree-view{overflow:auto;padding:16px;font-family:'SF Mono',Menlo,monospace;font-size:13px;background:#1e1e1e}
.tree-node>.row{display:flex;align-items:center;padding:1px 0;cursor:default}
.tree-node>.row:hover{background:rgba(255,255,255,0.04)}
.toggle{cursor:pointer;user-select:none;width:16px;text-align:center;color:#808080;font-size:11px}
.key{color:#9cdcfe;font-weight:bold}
.value-string{color:#ce9178}
.value-number{color:#b5cea8}
.value-bool{color:#569cd6}
.value-null{color:#808080}
.bracket{color:#808080}
.badge{color:#808080;font-style:italic}
.jsonpath{display:none;font-size:10px;color:#606060;margin-left:8px}
.copy-menu{position:fixed;background:#252526;border:1px solid #444;border-radius:4px;padding:4px 0;z-index:100;min-width:140px}
.copy-menu div{padding:6px 12px;cursor:pointer;font-size:12px;color:#d4d4d4}
.copy-menu div:hover{background:#094771}
`;
document.head.appendChild(s);

window.parseJson = function() {
    var raw = state.jsonInput;
    if (!raw || raw.trim() === "") {
        state.treeHtml = "";
        state.stats = "Paste JSON to begin";
        return;
    }
    var t0 = performance.now();
    try {
        var data = JSON.parse(raw);
        var ms = performance.now() - t0;
        var bytes = new Blob([raw]).size;
        var nodes = countNodes(data);
        state.treeHtml = '<div class="tree-view">' + renderTree(data, "$", 0, true) + '</div>';
        state.stats = formatNum(nodes) + " nodes  \u00b7  " + formatBytes(bytes) + "  \u00b7  parsed in " + formatTime(ms);
    } catch(e) {
        state.treeHtml = '<div style="color:#F44747;padding:16px"><b>Parse Error</b><br>' + escHtml(e.message) + '</div>';
        state.stats = "Invalid JSON";
    }
};

window.countNodes = function(v) {
    if (v === null || typeof v !== "object") return 1;
    var c = 1;
    if (Array.isArray(v)) { for (var i = 0; i < v.length; i++) c += countNodes(v[i]); }
    else { for (var k in v) c += countNodes(v[k]); }
    return c;
};

window.renderTree = function(v, path, depth, expanded) {
    var indent = "  ".repeat(depth);
    if (v === null) return leafNode(indent, path, "", "null", "value-null");
    if (typeof v === "boolean") return leafNode(indent, path, "", v?"true":"false", "value-bool");
    if (typeof v === "number") return leafNode(indent, path, "", ""+v, "value-number");
    if (typeof v === "string") {
        var display = v.length > 80 ? v.substring(0, 79) + "\u2026" : v;
        return leafNode(indent, path, "", '"'+escHtml(display)+'"', "value-string");
    }
    if (Array.isArray(v)) {
        if (!expanded) {
            return containerNode(indent, path, "[ " + v.length + " items ]", "badge", false, []);
        }
        var rows = containerNode(indent, path, "[", "bracket", true, []);
        for (var i = 0; i < v.length; i++) {
            var cp = path + "[" + i + "]";
            rows += renderTree(v[i], cp, depth+1, isExpanded(cp));
        }
        rows += indent + "  <span class='bracket'>]</span>\n";
        return rows;
    }
    var keys = []; for (var k in v) keys.push(k);
    if (!expanded) {
        return containerNode(indent, path, "{ " + keys.length + " keys }", "badge", false, []);
    }
    var rows2 = containerNode(indent, path, "{", "bracket", true, []);
    for (var j = 0; j < keys.length; j++) {
        var key = keys[j];
        var cp2 = path + "." + key;
        var child = v[key];
        var keyHtml = '<span class="key">' + escHtml(key) + ': </span>';
        if (child !== null && typeof child === "object") {
            rows2 += renderTree(child, cp2, depth+1, isExpanded(cp2));
        } else {
            rows2 += leafNode("  ".repeat(depth+1), cp2, keyHtml, renderValue(child), valueClass(child));
        }
    }
    rows2 += indent + "  <span class='bracket'>}</span>\n";
    return rows2;
};

window.renderValue = function(v) {
    if (v === null) return "null";
    if (typeof v === "boolean") return v ? "true" : "false";
    if (typeof v === "number") return "" + v;
    var d = v.length > 80 ? v.substring(0, 79) + "\u2026" : v;
    return '"'+escHtml(d)+'"';
};

window.valueClass = function(v) {
    if (v === null) return "value-null";
    if (typeof v === "boolean") return "value-bool";
    if (typeof v === "number") return "value-number";
    return "value-string";
};

window.containerNode = function(indent, path, label, labelClass, expanded, children) {
    var toggle = expanded ? "\u25bc" : "\u25b6";
    var expClass = expanded ? "expanded" : "collapsed";
    return '<div class="tree-node ' + expClass + '" data-path="' + escHtml(path) + '">'
        + '<div class="row">'
        + '<span class="toggle">' + toggle + '</span>'
        + '<span class="' + labelClass + '">' + label + '</span>'
        + '<span class="jsonpath">' + escHtml(path) + '</span>'
        + '</div>'
        + (expanded ? '<div class="children" style="display:block">' + children.join("") + '</div>' : '')
        + '</div>\n';
};

window.leafNode = function(indent, path, keyHtml, valHtml, valClass) {
    return '<div class="tree-node collapsed" data-path="' + escHtml(path) + '">'
        + '<div class="row">'
        + '<span class="toggle">  </span>'
        + keyHtml
        + '<span class="' + valClass + '">' + valHtml + '</span>'
        + '<span class="jsonpath">' + escHtml(path) + '</span>'
        + '</div></div>\n';
};

window.escHtml = function(s) {
    return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
};

window.formatNum = function(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};
window.formatBytes = function(n) {
    if (n < 1024) return n + " B";
    if (n < 1048576) return (Math.round(n/102.4)/10) + " KB";
    return (Math.round(n/(104857.6))/10) + " MB";
};
window.formatTime = function(ms) {
    if (ms < 1) return "<1ms";
    if (ms < 1000) return Math.round(ms) + "ms";
    return (Math.round(ms/100)/10) + "s";
};

var expandedPaths = ["$"];

window.isExpanded = function(path) {
    return expandedPaths.indexOf(path) >= 0 || expandedPaths.indexOf("*") >= 0;
};
window.togglePath = function(path) {
    var idx = expandedPaths.indexOf(path);
    if (idx >= 0) expandedPaths.splice(idx, 1);
    else expandedPaths.push(path);
    parseJson();
    if (typeof render === "function") render();
};
window.expandAll = function() { expandedPaths = ["*"]; parseJson(); };
window.collapseAll = function() { expandedPaths = ["$"]; parseJson(); };

// Clipboard helper — works in WKWebView and browsers
window.clipCopy = function(text) {
    var ta = document.createElement("textarea");
    ta.value = text;
    ta.style.cssText = "position:fixed;left:-9999px";
    document.body.appendChild(ta);
    ta.select();
    document.execCommand("copy");
    document.body.removeChild(ta);
};

window.pasteJson = function() {
    var t = prompt("Paste your JSON here:");
    if (t !== null && t.trim() !== "") {
        state.jsonInput = t;
        parseJson();
    }
};
window.formatJson = function() {
    try { state.jsonInput = JSON.stringify(JSON.parse(state.jsonInput), null, 2); parseJson(); }
    catch(e) { state.stats = "Error: " + e.message; }
};
window.minifyJson = function() {
    try { state.jsonInput = JSON.stringify(JSON.parse(state.jsonInput)); parseJson(); }
    catch(e) { state.stats = "Error: " + e.message; }
};
window.copyJson = function() {
    try { clipCopy(JSON.stringify(JSON.parse(state.jsonInput), null, 2)); state.stats = "Copied!"; }
    catch(e) { state.stats = "Copy failed"; }
};
window.loadSample = function() {
    state.jsonInput = JSON.stringify({name:"Pry",version:"1.0.0",description:"A fast, native JSON viewer",author:{name:"Skelpo GmbH",url:"https://perryts.com"},features:["tree_view","search","syntax_highlighting","clipboard"],platforms:{macOS:true,iOS:true,android:true,linux:true,windows:true},stats:{stars:42,downloads:10000,active:true,license:null},nested:{level1:{level2:{level3:{value:"deep!"}}}},mixed:[1,"two",true,null,{nested:true}],long_string:"This is a relatively long string that should be truncated when displayed in the tree view to keep things readable"}, null, 2);
    parseJson();
};

window.doSearch = function() {
    var q = document.getElementById("searchInput") ? document.getElementById("searchInput").value : "";
    var tree = document.getElementById("treeHtml");
    if (!tree) return;
    tree.querySelectorAll(".search-match").forEach(function(el) { el.classList.remove("search-match"); });
    if (!q) { state.searchInfo = ""; return; }
    var lower = q.toLowerCase();
    var count = 0;
    tree.querySelectorAll(".key, .value-string, .value-number, .value-bool, .value-null, .badge").forEach(function(el) {
        if (el.textContent.toLowerCase().indexOf(lower) >= 0) {
            el.classList.add("search-match");
            count++;
            var node = el.closest(".tree-node");
            if (node) {
                var p = node.parentElement;
                while (p && p !== tree) {
                    if (p.classList && p.classList.contains("tree-node")) {
                        p.classList.remove("collapsed");
                        p.classList.add("expanded");
                        var ch = p.querySelector(":scope > .children");
                        if (ch) ch.style.display = "block";
                        var tg = p.querySelector(":scope > .row > .toggle");
                        if (tg) tg.textContent = "\u25bc";
                    }
                    p = p.parentElement;
                }
            }
        }
    });
    state.searchInfo = count > 0 ? count + " matches" : "No matches";
};

document.addEventListener("click", function(e) {
    var toggle = e.target.closest(".toggle");
    if (toggle) {
        var node = toggle.closest(".tree-node");
        if (node) { var path = node.dataset.path; if (path) togglePath(path); }
        return;
    }
    var cm = document.getElementById("ctxMenu");
    if (cm) cm.remove();
    var row = e.target.closest(".row");
    if (row) {
        var pathEl = row.querySelector(".jsonpath");
        if (pathEl) {
            e.preventDefault();
            var menu = document.createElement("div");
            menu.id = "ctxMenu";
            menu.className = "copy-menu";
            menu.style.left = e.pageX + "px";
            menu.style.top = e.pageY + "px";
            var p = pathEl.textContent;
            var safePath = p.replace(/\\/g,"\\\\").replace(/'/g,"\\'");
            menu.innerHTML = '<div onclick="clipCopy(\'' + safePath + '\');this.parentElement.remove()">Copy Path</div>'
                + '<div onclick="copyNodeValue(\'' + safePath + '\');this.parentElement.remove()">Copy Value</div>';
            document.body.appendChild(menu);
            setTimeout(function() { document.addEventListener("click", function h() { var m = document.getElementById("ctxMenu"); if (m) m.remove(); document.removeEventListener("click", h); }); }, 10);
        }
    }
});

window.copyNodeValue = function(path) {
    try {
        var data = JSON.parse(state.jsonInput);
        var clean = path.replace(/^\$\.?/, "");
        var parts = clean.split(/\.|\[|\]/).filter(Boolean);
        var val = data;
        for (var i = 0; i < parts.length; i++) {
            var pk = parts[i];
            if (/^\d+$/.test(pk)) val = val[parseInt(pk)];
            else val = val[pk];
        }
        clipCopy(typeof val === "string" ? val : JSON.stringify(val, null, 2));
        state.stats = "Copied value at " + path;
    } catch(e) { state.stats = "Copy failed"; }
};

var searchTimer = null;
document.addEventListener("input", function(e) {
    if (e.target.id === "searchInput") {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(doSearch, 200);
    }
    if (e.target.id === "jsonInput") {
        state.jsonInput = e.target.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(parseJson, 300);
    }
});

var origRender = render;
render = function() {
    origRender();
    var ta = document.getElementById("jsonInput");
    if (ta && ta.value !== state.jsonInput) ta.value = state.jsonInput;
    var si = document.getElementById("searchInfoSpan");
    if (si) si.textContent = state.searchInfo;
};

var initApp = function() {
    var app = document.querySelector(".vstack");
    if (!app) return;
    var ta = document.createElement("textarea");
    ta.id = "jsonInput";
    ta.placeholder = "Paste JSON here...";
    ta.style.cssText = "width:100%;height:120px;padding:12px;border:none;border-bottom:1px solid #333;background:#252526;color:#d4d4d4;font-family:'SF Mono',Menlo,monospace;font-size:13px;resize:vertical;outline:none";
    ta.value = state.jsonInput;
    var toolbar = app.querySelector(".hstack");
    if (toolbar && toolbar.nextElementSibling) {
        app.insertBefore(ta, toolbar.nextElementSibling);
    } else {
        app.appendChild(ta);
    }
    var searchDiv = document.createElement("div");
    searchDiv.style.cssText = "display:flex;align-items:center;gap:8px;padding:8px 16px;background:#252526;border-bottom:1px solid #333";
    var searchInput = document.createElement("input");
    searchInput.id = "searchInput";
    searchInput.placeholder = "Search keys and values...";
    searchInput.style.cssText = "flex:1;padding:6px 10px;border:1px solid #444;border-radius:4px;background:#1e1e1e;color:#d4d4d4;font-size:12px";
    var searchInfo = document.createElement("span");
    searchInfo.id = "searchInfoSpan";
    searchInfo.style.cssText = "font-size:11px;color:#606060";
    searchDiv.appendChild(searchInput);
    searchDiv.appendChild(searchInfo);
    if (ta.nextElementSibling) app.insertBefore(searchDiv, ta.nextElementSibling);
    else app.appendChild(searchDiv);
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initApp);
} else {
    initApp();
}
})();
JS;

// Web mode: set up HtmlBackend and use Action::custom()
if ($isWeb) {
    HtmlBackend::$innerHTMLVars = ['treeHtml'];
    HtmlBackend::$customScript = $pryCustomScript;

    $parseAction = Action::custom('parseJson()');
    $pasteAction = Action::custom('pasteJson()');
    $formatAction = Action::custom('formatJson()');
    $minifyAction = Action::custom('minifyJson()');
    $copyAction = Action::custom('copyJson()');
    $sampleAction = Action::custom('loadSample()');
    $expandAction = Action::custom('expandAll()');
    $collapseAction = Action::custom('collapseAll()');
} else {
    // macOS mode: Action::fromClosure() with PHP JSON functions
    $parseAction = Action::fromClosure(function () use ($jsonInput, $treeHtml, $stats) {
        $raw = $jsonInput;
        if (empty($raw)) {
            $treeHtml = '';
            $stats = 'Paste JSON to begin';
            return;
        }
        $decoded = json_decode($raw);
        if (json_last_error() !== 0) {
            $treeHtml = 'Parse Error: ' . json_last_error_msg();
            $stats = 'Invalid JSON';
            return;
        }
        $formatted = json_encode($decoded, JSON_PRETTY_PRINT);
        $bytes = strlen($raw);
        $treeHtml = $formatted;
        $stats = $bytes . ' bytes';
    });

    $pasteAction = Action::fromClosure(function () use ($stats) {
        $stats = 'Paste not available on macOS';
    });

    $formatAction = Action::fromClosure(function () use ($jsonInput, $treeHtml, $stats) {
        $decoded = json_decode($jsonInput);
        if (json_last_error() !== 0) {
            $stats = 'Error: ' . json_last_error_msg();
            return;
        }
        $formatted = json_encode($decoded, JSON_PRETTY_PRINT);
        $jsonInput = $formatted;
        $treeHtml = $formatted;
        $stats = 'Formatted';
    });

    $minifyAction = Action::fromClosure(function () use ($jsonInput, $treeHtml, $stats) {
        $decoded = json_decode($jsonInput);
        if (json_last_error() !== 0) {
            $stats = 'Error: ' . json_last_error_msg();
            return;
        }
        $minified = json_encode($decoded);
        $jsonInput = $minified;
        $treeHtml = $minified;
        $stats = 'Minified';
    });

    $copyAction = Action::fromClosure(function () use ($stats) {
        $stats = 'Copy not available on macOS';
    });

    $sampleAction = Action::fromClosure(function () use ($jsonInput, $treeHtml, $stats) {
        $json = '{"name":"Pry","version":"1.0.0","description":"A fast, native JSON viewer","author":{"name":"Skelpo GmbH","url":"https://perryts.com"},"features":["tree_view","search","syntax_highlighting","clipboard"],"platforms":{"macOS":true,"iOS":true,"android":true,"linux":true,"windows":true},"stats":{"stars":42,"downloads":10000,"active":true,"license":null},"nested":{"level1":{"level2":{"level3":{"value":"deep!"}}}},"mixed":[1,"two",true,null,{"nested":true}]}';
        $jsonInput = $json;
        $treeHtml = $json;
        $stats = strlen($json) . ' bytes';
    });

    $expandAction = Action::fromClosure(function () use ($stats) {
        $stats = 'Tree view only available on web';
    });

    $collapseAction = Action::fromClosure(function () use ($stats) {
        $stats = 'Tree view only available on web';
    });
}

$btnPrimary = Style::make()->fontSize(12)->foregroundColor('#000')->backgroundColor('#4EC9B0')->cornerRadius(4)->paddingAll(6, 12, 6, 12);
$btnNormal = Style::make()->fontSize(12)->foregroundColor('#d4d4d4')->backgroundColor('#333333')->cornerRadius(4)->paddingAll(6, 12, 6, 12);
$headerStyle = Style::make()->fontSize(18)->foregroundColor('#FFFFFF')->paddingAll(12, 16, 8, 16);
$statusStyle = Style::make()->fontSize(11)->foregroundColor('#ffffff')->paddingAll(8, 16, 8, 16)->backgroundColor('#007acc');
$toolbarStyle = Style::make()->paddingAll(8, 16, 8, 16)->backgroundColor('#252526');
$treeStyle = Style::make()->fontSize(13)->foregroundColor('#d4d4d4')->paddingAll(16, 16, 16, 16)->backgroundColor('#1e1e1e')->set(StyleProperty::MinHeight, 400);

$app = new App(Target::fromString($argv[1] ?? 'macos'));
$app->setRoot(
    new AppContainer(
        new VStack(
            (new HStack(
                (new Text('Pry'))->style($headerStyle),
                (new Text('JSON Viewer'))->style(Style::make()->fontSize(12)->foregroundColor('#606060')),
                new Spacer(),
            ))->style(Style::make()->backgroundColor('#252526')->paddingAll(12, 16, 4, 16)),

            (new HStack(
                (new Button('Paste', $pasteAction))->style($btnPrimary),
                (new Button('Sample', $sampleAction))->style($btnNormal),
                (new Button('Format', $formatAction))->style($btnNormal),
                (new Button('Minify', $minifyAction))->style($btnNormal),
                (new Button('Copy', $copyAction))->style($btnNormal),
                new Spacer(),
                (new Button('Expand All', $expandAction))->style($btnNormal),
                (new Button('Collapse All', $collapseAction))->style($btnNormal),
            ))->style($toolbarStyle),

            (new Text($treeHtml))->style($treeStyle),

            (new Text($stats))->style($statusStyle),

        )->style(Style::make()->backgroundColor('#1e1e1e')),

        800, 700,
        $jsonInput,
    )
);

$target = $argv[1] ?? 'macos';
$build = in_array('--build', $argv);

if ($build) {
    // For macOS: generate complete web HTML, embed in WKWebView
    // This gives 100% feature parity with web version

    // Save and set web-mode HtmlBackend settings
    $savedInnerHtmlVars = HtmlBackend::$innerHTMLVars;
    $savedCustomScript = HtmlBackend::$customScript;
    HtmlBackend::$innerHTMLVars = ['treeHtml'];
    HtmlBackend::$customScript = $pryCustomScript;

    // Generate web HTML using the same widget tree
    // MUST use web-mode Action::custom() for all actions — WebView needs JavaScript functions
    $webApp = new App(Target::fromString('web'));
    $webApp->setRoot(
        new AppContainer(
            new VStack(
                (new HStack(
                    (new Text('Pry'))->style($headerStyle),
                    (new Text('JSON Viewer'))->style(Style::make()->fontSize(12)->foregroundColor('#606060')),
                    new Spacer(),
                ))->style(Style::make()->backgroundColor('#252526')->paddingAll(12, 16, 4, 16)),
                (new HStack(
                    (new Button('Paste', Action::custom('pasteJson()')))->style($btnPrimary),
                    (new Button('Sample', Action::custom('loadSample()')))->style($btnNormal),
                    (new Button('Format', Action::custom('formatJson()')))->style($btnNormal),
                    (new Button('Minify', Action::custom('minifyJson()')))->style($btnNormal),
                    (new Button('Copy', Action::custom('copyJson()')))->style($btnNormal),
                    new Spacer(),
                    (new Button('Expand All', Action::custom('expandAll()')))->style($btnNormal),
                    (new Button('Collapse All', Action::custom('collapseAll()')))->style($btnNormal),
                ))->style($toolbarStyle),
                (new Text($treeHtml))->style($treeStyle),
                (new Text($stats))->style($statusStyle),
            )->style(Style::make()->backgroundColor('#1e1e1e')),
            800, 700,
            $jsonInput, $treeHtml, $stats, $searchInfo,
        )
    );
    $webHtml = $webApp->generateForTarget();

    // Restore HtmlBackend settings
    HtmlBackend::$innerHTMLVars = $savedInnerHtmlVars;
    HtmlBackend::$customScript = $savedCustomScript;

    // Build macOS app with WebView
    $root = new AppContainer(
        new WebView($webHtml),
        800, 700,
    );
    $compiler = new \Perry\Build\Compiler(Target::fromString($target), 'build');
    $result = $compiler->compile($root, 'pry');
    if ($result->success) {
        echo "  Pry built!\n  Output: {$result->outputFile}\n";
        if ($target === 'macos') echo "Run: open {$result->outputFile}\n";
        else echo "Open: {$result->outputFile}\n";
    } else {
        echo "  Build failed: {$result->error}\n";
    }
} else {
    echo $app->generateForTarget() . "\n";
}
