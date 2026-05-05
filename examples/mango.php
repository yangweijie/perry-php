<?php
declare(strict_types=1);
error_reporting(E_ERROR | E_PARSE);

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

// ============================================================
//  STATE
// ============================================================
$selectedDb   = new Binding('selectedDb', '');
$selectedColl = new Binding('selectedColl', '');
$filterJson   = new Binding('filterJson', '{}');
$documents    = new Binding('documents', '');
$statusMsg    = new Binding('statusMsg', 'Connect to a MongoDB instance to browse databases.');
$editDocJson  = new Binding('editDocJson', '');

$target = $argv[1] ?? 'macos';
$isWeb = in_array($target, ['web', 'wasm']);

// ============================================================
//  CUSTOM JAVASCRIPT — Full web-mode MongoDB GUI
// ============================================================
$mangoCustomScript = <<<'JS'
// --- CSS ---
var s = document.createElement("style");
s.textContent = `
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#2B2D42;color:#E8E9ED}
.app{display:flex;height:100vh}
.sidebar{width:240px;background:#232538;border-right:1px solid #4A4D6A;overflow-y:auto;flex-shrink:0}
.sidebar-header{padding:12px 16px;font-size:13px;font-weight:600;color:#FF9F1C;border-bottom:1px solid #4A4D6A}
.db-item{cursor:pointer;padding:8px 16px;display:flex;align-items:center;gap:6px;font-size:13px;color:#E8E9ED;border-bottom:1px solid rgba(74,77,106,0.3)}
.db-item:hover{background:rgba(255,159,28,0.08)}
.db-item .chevron{color:#8D99AE;font-size:10px;width:12px}
.db-item .db-icon{color:#FF9F1C;font-size:12px}
.coll-item{cursor:pointer;padding:6px 16px 6px 36px;font-size:12px;color:#8D99AE;border-bottom:1px solid rgba(74,77,106,0.15)}
.coll-item:hover{background:rgba(255,159,28,0.08);color:#E8E9ED}
.coll-item.active{color:#FF9F1C;background:rgba(255,159,28,0.1)}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.toolbar{background:#3A3D56;border-bottom:1px solid #4A4D6A;padding:10px 20px;display:flex;align-items:center;gap:12px}
.toolbar-title{font-size:18px;font-weight:700;color:#FF9F1C}
.toolbar-conn{font-size:11px;color:#2EC4B6;font-weight:500}
.query-bar{background:#3A3D56;border-bottom:1px solid #4A4D6A;padding:10px 20px;display:flex;align-items:center;gap:8px}
.query-bar input{background:#232538;border:1px solid #4A4D6A;border-radius:6px;padding:6px 10px;color:#E8E9ED;font-size:12px;font-family:'SF Mono',Menlo,monospace}
.query-bar input:focus{outline:none;border-color:#FF9F1C}
.query-bar input.db-input{width:120px}
.query-bar input.coll-input{width:120px}
.query-bar input.filter-input{flex:1}
.query-bar button{background:#FF9F1C;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer}
.query-bar button:hover{background:#FFBF69}
.content{flex:1;overflow-y:auto;padding:16px 20px}
.doc-card{background:#3A3D56;border:1px solid #4A4D6A;border-radius:8px;padding:12px 16px;margin-bottom:10px}
.doc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.doc-id{font-family:'SF Mono',Menlo,monospace;font-size:10px;color:#8D99AE}
.doc-actions{display:flex;gap:6px}
.doc-actions button{background:none;border:none;font-size:11px;cursor:pointer;padding:2px 6px;border-radius:4px}
.doc-actions .edit-btn{color:#FF9F1C}
.doc-actions .edit-btn:hover{background:rgba(255,159,28,0.15)}
.doc-actions .del-btn{color:#E8572A}
.doc-actions .del-btn:hover{background:rgba(232,87,42,0.15)}
.doc-field{display:flex;justify-content:space-between;padding:3px 0;font-size:12px;border-bottom:1px solid rgba(74,77,106,0.2)}
.doc-field:last-child{border-bottom:none}
.doc-key{color:#9cdcfe;font-family:'SF Mono',Menlo,monospace}
.doc-val{color:#E8E9ED;font-family:'SF Mono',Menlo,monospace;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.doc-val.str{color:#CE9178}
.doc-val.num{color:#B5CEA8}
.doc-val.bool{color:#569CD6}
.doc-val.null{color:#808080}
.status-bar{background:#232538;border-top:1px solid #4A4D6A;padding:8px 20px;font-size:11px;color:#8D99AE;display:flex;justify-content:space-between}
.status-bar .error{color:#E8572A}
.status-bar .ok{color:#2EC4B6}
.edit-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:50}
.edit-panel{background:#3A3D56;border:1px solid #4A4D6A;border-radius:12px;padding:20px;width:600px;max-height:80vh;display:flex;flex-direction:column}
.edit-panel h3{font-size:16px;color:#FF9F1C;margin-bottom:12px}
.edit-panel textarea{flex:1;background:#232538;border:1px solid #4A4D6A;border-radius:8px;padding:12px;color:#E8E9ED;font-family:'SF Mono',Menlo,monospace;font-size:13px;resize:vertical;min-height:200px}
.edit-panel textarea:focus{outline:none;border-color:#FF9F1C}
.edit-panel .btn-row{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
.edit-panel .btn-row button{border:none;border-radius:6px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer}
.btn-save{background:#FF9F1C;color:#fff}
.btn-cancel{background:transparent;color:#8D99AE;border:1px solid #4A4D6A}
.btn-delete{background:#E8572A;color:#fff}
`;
document.head.appendChild(s);

// --- MOCK DATA ---
var mockDbs = {
  "shop_db": ["customers", "orders", "products", "inventory"],
  "analytics": ["events", "sessions", "pageviews"],
  "admin": ["system.users", "system.sessions"]
};
var mockDocs = {
  "shop_db.customers": [
    {"_id":"69b26f3a8c1d4e5f7a2b63b1","name":"Alice Johnson","email":"alice@example.com","age":30,"role":"admin","department":"Engineering"},
    {"_id":"69b26f3a8c1d4e5f7a2b63b2","name":"Bob Smith","email":"bob@example.com","age":25,"role":"user","department":"Marketing"},
    {"_id":"69b26f3a8c1d4e5f7a2b63b3","name":"Charlie Brown","email":"charlie@example.com","age":35,"role":"editor","department":"Engineering"},
    {"_id":"69b26f3a8c1d4e5f7a2b63b4","name":"Diana Prince","email":"diana@example.com","age":28,"role":"admin","department":"Operations"}
  ],
  "shop_db.orders": [
    {"_id":"69b26f3a8c1d4e5f7a2b6401","orderId":"ORD-001","customer":"Alice Johnson","total":129.99,"status":"shipped","items":3},
    {"_id":"69b26f3a8c1d4e5f7a2b6402","orderId":"ORD-002","customer":"Bob Smith","total":49.50,"status":"pending","items":1}
  ],
  "shop_db.products": [
    {"_id":"69b26f3a8c1d4e5f7a2b6501","name":"Widget Pro","price":29.99,"stock":150,"category":"electronics"},
    {"_id":"69b26f3a8c1d4e5f7a2b6502","name":"Gadget Plus","price":49.99,"stock":75,"category":"electronics"},
    {"_id":"69b26f3a8c1d4e5f7a2b6503","name":"Thingamajig","price":9.99,"stock":300,"category":"accessories"}
  ],
  "analytics.events": [
    {"_id":"69b26f3a8c1d4e5f7a2b7001","event":"pageview","path":"/home","timestamp":"2024-01-15T10:30:00Z","userId":"u123"},
    {"_id":"69b26f3a8c1d4e5f7a2b7002","event":"click","target":"signup-btn","timestamp":"2024-01-15T10:31:00Z","userId":"u123"}
  ]
};

// --- STATE ---
var mg = {
  selectedDb: "",
  selectedColl: "",
  filterJson: "{}",
  documents: "",
  statusMsg: "Connect to a MongoDB instance to browse databases.",
  expandedDbs: {},
  sidebarHtml: ""
};

// --- HELPERS ---
function escapeHtml(s) {
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function valueType(v) {
  if (v === null) return "null";
  if (typeof v === "string") return "str";
  if (typeof v === "number") return "num";
  if (typeof v === "boolean") return "bool";
  return "str";
}

function shortId(id) {
  if (typeof id !== "string") return String(id);
  return id.length > 12 ? id.substring(0, 12) + "..." : id;
}

// --- SIDEBAR ---
function renderSidebar() {
  var html = '<div class="sidebar-header">MongoDB Explorer</div>';
  var dbs = Object.keys(mockDbs);
  for (var i = 0; i < dbs.length; i++) {
    var db = dbs[i];
    var expanded = mg.expandedDbs[db];
    var chevron = expanded ? "▼" : "▶";
    var colls = mockDbs[db];
    html += '<div class="db-item" onclick="toggleDb(\'' + escapeHtml(db) + '\')">';
    html += '<span class="chevron">' + chevron + '</span>';
    html += '<span class="db-icon">🗄</span> ' + escapeHtml(db);
    html += '</div>';
    if (expanded) {
      for (var j = 0; j < colls.length; j++) {
        var coll = colls[j];
        var active = (mg.selectedDb === db && mg.selectedColl === coll) ? " active" : "";
        html += '<div class="coll-item' + active + '" onclick="selectCollection(\'' + escapeHtml(db) + '\',\'' + escapeHtml(coll) + '\')">';
        html += '◦ ' + escapeHtml(coll);
        html += '</div>';
      }
    }
  }
  mg.sidebarHtml = html;
  var sidebar = document.getElementById("sidebar");
  if (sidebar) sidebar.innerHTML = html;
}

function toggleDb(db) {
  mg.expandedDbs[db] = !mg.expandedDbs[db];
  renderSidebar();
}

function selectCollection(db, coll) {
  mg.selectedDb = db;
  mg.selectedColl = coll;
  document.getElementById("db-input").value = db;
  document.getElementById("coll-input").value = coll;
  renderSidebar();
  runQuery();
}

// --- QUERY ---
function runQuery() {
  var db = document.getElementById("db-input").value || mg.selectedDb;
  var coll = document.getElementById("coll-input").value || mg.selectedColl;
  var filter = document.getElementById("filter-input").value || "{}";

  if (!db || !coll) {
    mg.statusMsg = "Select a database and collection first.";
    mg.documents = "";
    renderDocs();
    renderStatus();
    return;
  }

  mg.selectedDb = db;
  mg.selectedColl = coll;
  mg.filterJson = filter;

  var key = db + "." + coll;
  var docs = mockDocs[key];
  if (!docs) {
    mg.statusMsg = "Collection not found: " + key;
    mg.documents = "[]";
    renderDocs();
    renderStatus();
    renderSidebar();
    return;
  }

  // Apply filter (simple matching)
  try {
    var filterObj = JSON.parse(filter);
    if (Object.keys(filterObj).length > 0) {
      docs = docs.filter(function(doc) {
        for (var k in filterObj) {
          if (doc[k] !== filterObj[k]) return false;
        }
        return true;
      });
    }
  } catch(e) {}

  mg.documents = JSON.stringify(docs);
  mg.statusMsg = docs.length + " document" + (docs.length !== 1 ? "s" : "") + " in " + key;
  renderDocs();
  renderStatus();
  renderSidebar();
}

// --- DOCUMENTS ---
function renderDocs() {
  var container = document.getElementById("docs-container");
  if (!container) return;

  if (!mg.documents) {
    container.innerHTML = '<div style="color:#8D99AE;padding:20px;text-align:center">Select a database and collection, then run a query.</div>';
    return;
  }

  var docs;
  try { docs = JSON.parse(mg.documents); } catch(e) { docs = []; }
  if (!Array.isArray(docs)) docs = [];

  if (docs.length === 0) {
    container.innerHTML = '<div style="color:#8D99AE;padding:20px;text-align:center">No documents match the query.</div>';
    return;
  }

  var html = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">';
  html += '<span style="font-size:14px;font-weight:600;color:#FF9F1C">' + escapeHtml(mg.selectedDb) + '.' + escapeHtml(mg.selectedColl) + '</span>';
  html += '<span style="font-size:12px;color:#8D99AE">' + docs.length + ' document' + (docs.length !== 1 ? 's' : '') + '</span>';
  html += '</div>';

  for (var i = 0; i < docs.length; i++) {
    var doc = docs[i];
    var docJson = JSON.stringify(doc);
    html += '<div class="doc-card">';
    html += '<div class="doc-header">';
    html += '<span class="doc-id">' + shortId(doc._id || "unknown") + '</span>';
    html += '<div class="doc-actions">';
    html += '<button class="edit-btn" onclick="editDocument(\'' + escapeHtml(docJson).replace(/'/g, "\\'") + '\')">Edit</button>';
    html += '<button class="del-btn" onclick="deleteDocument(\'' + escapeHtml(doc._id || "") + '\')">Delete</button>';
    html += '</div></div>';

    var keys = Object.keys(doc);
    for (var j = 0; j < keys.length; j++) {
      var key = keys[j];
      var val = doc[key];
      var valStr = typeof val === "object" ? JSON.stringify(val) : String(val);
      var valClass = valueType(val);
      if (typeof val === "string") valClass = "str";
      html += '<div class="doc-field">';
      html += '<span class="doc-key">' + escapeHtml(key) + '</span>';
      html += '<span class="doc-val ' + valClass + '">' + escapeHtml(valStr) + '</span>';
      html += '</div>';
    }
    html += '</div>';
  }

  container.innerHTML = html;
}

function renderStatus() {
  var el = document.getElementById("status-text");
  if (el) {
    var msg = mg.statusMsg;
    el.innerHTML = msg;
    el.className = msg.indexOf("Error") >= 0 || msg.indexOf("failed") >= 0 ? "error" : "ok";
  }
}

// --- EDIT ---
function editDocument(docJson) {
  var overlay = document.createElement("div");
  overlay.className = "edit-overlay";
  overlay.id = "edit-overlay";

  var panel = document.createElement("div");
  panel.className = "edit-panel";

  var prettyJson;
  try { prettyJson = JSON.stringify(JSON.parse(docJson), null, 2); } catch(e) { prettyJson = docJson; }

  panel.innerHTML = '<h3>Edit Document</h3>' +
    '<textarea id="edit-textarea">' + escapeHtml(prettyJson) + '</textarea>' +
    '<div class="btn-row">' +
    '<button class="btn-cancel" onclick="closeEdit()">Cancel</button>' +
    '<button class="btn-delete" onclick="deleteFromEdit()">Delete</button>' +
    '<button class="btn-save" onclick="saveEdit()">Save Changes</button>' +
    '</div>';

  overlay.appendChild(panel);
  document.body.appendChild(overlay);
}

function closeEdit() {
  var el = document.getElementById("edit-overlay");
  if (el) el.remove();
}

function saveEdit() {
  var textarea = document.getElementById("edit-textarea");
  if (!textarea) return;
  try {
    var updated = JSON.parse(textarea.value);
    // Update in mock data
    var key = mg.selectedDb + "." + mg.selectedColl;
    var docs = mockDocs[key];
    if (docs) {
      for (var i = 0; i < docs.length; i++) {
        if (docs[i]._id === updated._id) {
          docs[i] = updated;
          break;
        }
      }
    }
    mg.statusMsg = "Document saved successfully.";
    closeEdit();
    runQuery();
  } catch(e) {
    mg.statusMsg = "Invalid JSON: " + e.message;
    renderStatus();
  }
}

function deleteDocument(docId) {
  var key = mg.selectedDb + "." + mg.selectedColl;
  var docs = mockDocs[key];
  if (docs) {
    mockDocs[key] = docs.filter(function(d) { return d._id !== docId; });
  }
  mg.statusMsg = "Document deleted.";
  runQuery();
}

function deleteFromEdit() {
  var textarea = document.getElementById("edit-textarea");
  if (!textarea) return;
  try {
    var doc = JSON.parse(textarea.value);
    deleteDocument(doc._id || "");
    closeEdit();
  } catch(e) {}
}

// --- INSERT ---
function insertDocument() {
  var overlay = document.createElement("div");
  overlay.className = "edit-overlay";
  overlay.id = "edit-overlay";

  var panel = document.createElement("div");
  panel.className = "edit-panel";

  var template = JSON.stringify({"_id": generateId(), "name": "", "value": 0}, null, 2);

  panel.innerHTML = '<h3>Insert Document</h3>' +
    '<textarea id="edit-textarea">' + escapeHtml(template) + '</textarea>' +
    '<div class="btn-row">' +
    '<button class="btn-cancel" onclick="closeEdit()">Cancel</button>' +
    '<button class="btn-save" onclick="insertFromEdit()">Insert</button>' +
    '</div>';

  overlay.appendChild(panel);
  document.body.appendChild(overlay);
}

function insertFromEdit() {
  var textarea = document.getElementById("edit-textarea");
  if (!textarea) return;
  try {
    var doc = JSON.parse(textarea.value);
    var key = mg.selectedDb + "." + mg.selectedColl;
    if (!mockDocs[key]) mockDocs[key] = [];
    mockDocs[key].push(doc);
    mg.statusMsg = "Document inserted.";
    closeEdit();
    runQuery();
  } catch(e) {
    mg.statusMsg = "Invalid JSON: " + e.message;
    renderStatus();
  }
}

function generateId() {
  var hex = "0123456789abcdef";
  var id = "";
  for (var i = 0; i < 24; i++) id += hex[Math.floor(Math.random() * 16)];
  return id;
}

function loadSample() {
  mg.selectedDb = "shop_db";
  mg.selectedColl = "customers";
  mg.filterJson = "{}";
  mg.documents = JSON.stringify(mockDocs["shop_db.customers"], null, 2);
  mg.statusMsg = "4 sample documents loaded";
  renderDocs();
  renderStatus();
  renderSidebar();
}

function formatJson() {
  try {
    if (!mg.documents) return;
    var data = JSON.parse(mg.documents);
    mg.documents = JSON.stringify(data, null, 2);
    mg.statusMsg = "Formatted";
    renderDocs();
    renderStatus();
  } catch(e) {
    mg.statusMsg = "Format error: " + e.message;
    renderStatus();
  }
}

function clearAll() {
  mg.selectedDb = "";
  mg.selectedColl = "";
  mg.filterJson = "{}";
  mg.documents = "";
  mg.statusMsg = "Cleared";
  mg.editDocJson = "";
  renderDocs();
  renderStatus();
  renderSidebar();
}

// --- INIT ---
function initApp() {
  var app = document.querySelector(".vstack");
  if (!app) return;

  app.innerHTML =
    '<div class="app">' +
    '<div class="sidebar" id="sidebar"></div>' +
    '<div class="main">' +
    '<div class="toolbar">' +
    '<span class="toolbar-title">🍃 Mango</span>' +
    '<span class="toolbar-conn">Demo Mode — Mock Data</span>' +
    '<span style="flex:1"></span>' +
    '<button style="background:#FF9F1C;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer" onclick="insertDocument()">+ Insert</button>' +
    '</div>' +
    '<div class="query-bar">' +
    '<input type="text" id="db-input" class="db-input" placeholder="database" value="">' +
    '<input type="text" id="coll-input" class="coll-input" placeholder="collection" value="">' +
    '<input type="text" id="filter-input" class="filter-input" placeholder=\'filter: {}\' value="{}">' +
    '<button onclick="runQuery()">Run Query</button>' +
    '</div>' +
    '<div class="content" id="docs-container">' +
    '<div style="color:#8D99AE;padding:20px;text-align:center">Select a database and collection, then run a query.</div>' +
    '</div>' +
    '<div class="status-bar"><span id="status-text" class="ok">' + escapeHtml(mg.statusMsg) + '</span></div>' +
    '</div></div>';

  renderSidebar();

  // Enter key runs query
  var filterInput = document.getElementById("filter-input");
  if (filterInput) {
    filterInput.addEventListener("keydown", function(e) {
      if (e.key === "Enter") runQuery();
    });
  }
}

// Override Perry's render to sync state
var _origRender = typeof render === "function" ? render : null;
render = function() {
  if (_origRender) _origRender();
  renderDocs();
  renderStatus();
  renderSidebar();
};

// Auto-init
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initApp);
} else {
  initApp();
}

JS;

// ============================================================
//  ACTIONS — Web mode uses Action::custom(), macOS uses Action::fromClosure()
// ============================================================

if ($isWeb) {
    // Web mode: Action::custom() with JavaScript functions from $mangoCustomScript
    $parseAction = Action::custom('runQuery()');
    $sampleAction = Action::custom('loadSample()');
    $formatAction = Action::custom('formatJson()');
    $clearAction = Action::custom('clearAll()');
} else {
    // macOS mode: Action::fromClosure() with PHP closures (parsed by nikic/php-parser → IR → Swift)
    $parseAction = Action::fromClosure(function () use ($jsonInput, $documents, $statusMsg) {
    $data = json_decode($jsonInput, true);
    if (json_last_error() !== 0) {
        $statusMsg = 'Parse error: ' . json_last_error_msg();
        $documents = '';
        return;
    }
    if (!is_array($data)) {
        $statusMsg = 'Input is not a JSON array';
        $documents = '';
        return;
    }
    $documents = json_encode($data, JSON_PRETTY_PRINT);
    $statusMsg = strval(count($data)) . ' documents loaded';
});

$sampleAction = Action::fromClosure(function () use ($jsonInput, $documents, $statusMsg) {
    $sample = '[{"_id":"69b26f3a8c1d4e5f7a2b63b1","name":"Alice Johnson","email":"alice@example.com","age":30,"role":"admin","department":"Engineering"},{"_id":"69b26f3a8c1d4e5f7a2b63b2","name":"Bob Smith","email":"bob@example.com","age":25,"role":"user","department":"Marketing"},{"_id":"69b26f3a8c1d4e5f7a2b63b3","name":"Charlie Brown","email":"charlie@example.com","age":35,"role":"editor","department":"Engineering"}]';
    $jsonInput = $sample;
    $data = json_decode($sample, true);
    $documents = json_encode($data, JSON_PRETTY_PRINT);
    $statusMsg = '3 sample documents loaded';
});

$formatAction = Action::fromClosure(function () use ($jsonInput, $statusMsg) {
    $data = json_decode($jsonInput, true);
    if (json_last_error() !== 0) {
        $statusMsg = 'Parse error: ' . json_last_error_msg();
        return;
    }
    $jsonInput = json_encode($data, JSON_PRETTY_PRINT);
    $statusMsg = 'JSON formatted';
});

$clearAction = Action::fromClosure(function () use ($jsonInput, $documents, $statusMsg) {
    $jsonInput = '';
    $documents = '';
    $statusMsg = 'Cleared';
});
} // end else (macOS mode)

// ============================================================
//  BUILD UI
// ============================================================

if ($isWeb) {
    HtmlBackend::$innerHTMLVars = ['documents', 'statusMsg'];
    HtmlBackend::$customScript = $mangoCustomScript;
}

// Toolbar
$logoText = (new Text('🍃 Mango'))->style(
    Style::make()->fontSize(18)->set(StyleProperty::FontFamily, 'system-ui')->set(StyleProperty::FontWeight, '700')->foregroundColor('#FF9F1C')
);
$connLabel = (new Text('Demo Mode'))->style(
    Style::make()->fontSize(11)->set(StyleProperty::FontFamily, 'system-ui')->foregroundColor('#2EC4B6')
);

$toolbar = new HStack(
    $logoText,
    $connLabel,
    new Spacer()
);

// Query bar (macOS only — web has its own)
$parseBtn  = new Button('Parse', $parseAction);
$sampleBtn = new Button('Sample', $sampleAction);
$formatBtn = new Button('Format', $formatAction);
$clearBtn  = new Button('Clear', $clearAction);

$queryBar = new HStack(
    $parseBtn,
    $sampleBtn,
    $formatBtn,
    $clearBtn,
    new Spacer()
);

// Document display
$docTitle = (new Text('Documents'))->style(
    Style::make()->fontSize(14)->set(StyleProperty::FontFamily, 'system-ui')->set(StyleProperty::FontWeight, '600')->foregroundColor('#FF9F1C')
);

// Content area
$content = new VStack(
    $docTitle,
    new Text($documents)
);

// Status bar
$statusBar = new Text($statusMsg);

// Assemble macOS body (used for web output)
$body = new VStack(
    $toolbar,
    $queryBar,
    $content,
    $statusBar
);

// ============================================================
//  GENERATE & BUILD
// ============================================================
if (isset($argv[2]) && $argv[2] === '--build') {
    $target = Target::fromString($argv[1] ?? 'macos');

    // Save HtmlBackend settings
    $savedInnerHtmlVars = HtmlBackend::$innerHTMLVars;
    $savedCustomScript = HtmlBackend::$customScript;

    // Generate web HTML with full Mango UI
    HtmlBackend::$innerHTMLVars = ['documents', 'statusMsg'];
    HtmlBackend::$customScript = $mangoCustomScript;

    // Use web-mode Action::custom() for all actions — WebView needs JavaScript functions
    $webApp = new App(Target::fromString('web'));
    $webApp->setRoot(
        new AppContainer(
            new VStack(
                new Text(''),  // placeholder — initApp() replaces everything
            ),
            900, 600,
            $documents, $statusMsg,
        )
    );
    $webHtml = $webApp->generateForTarget();

    // Restore HtmlBackend settings
    HtmlBackend::$innerHTMLVars = $savedInnerHtmlVars;
    HtmlBackend::$customScript = $savedCustomScript;

    // Build macOS app with WebView
    $root = new AppContainer(
        new WebView($webHtml),
        900, 600,
    );
    $compiler = new \Perry\Build\Compiler($target, 'build');
    $result = $compiler->compile($root, 'mango');
    if ($result->success) {
        echo "  Mango built!\n  Output: {$result->outputFile}\n";
        if ($target === 'macos') echo "Run: open {$result->outputFile}\n";
    } else {
        echo "  Build failed: {$result->error}\n";
    }
    exit(0);
}

$app = new App(Target::fromString($argv[1] ?? 'macos'));
$app->setRoot(new AppContainer($body, 900, 600));
echo $app->generateForTarget();
