# Perry PHP — VuePress 文档站搭建

## 目标
用 VuePress 搭建完整的项目文档站，可发布到 GitHub Pages（`yangweijie/perry-php`）。

## 当前状态（2026-05-14）

### Phase 1: 配置与基础设施 ✅ COMPLETE
- [x] 更新 `config.ts` — base path `/perry-php/`、导航栏、侧边栏
- [x] 安装 `@vuepress/plugin-search` — 全文搜索
- [x] 配置多语言骨架（en-US + zh-CN）
- [x] 修复构建依赖（sass-embedded）
- [x] 首次构建验证 — 23 页，0 错误，0 警告

### Phase 2: 内容补充 ✅ COMPLETE
- [x] 创建 `docs/guide/api-reference.md` — 完整 API 参考
- [x] 创建 `docs/guide/best-practices.md` — 12 条最佳实践
- [x] 创建 `docs/guide/contributing.md` — 贡献指南含 PR 流程、编码规范

### Phase 3: 部署配置 ✅ COMPLETE
- [x] 创建 `.github/workflows/deploy-docs.yml` — GitHub Actions 自动构建部署
- [x] 更新 `package.json` — 依赖声明

### Phase 5: 中文内容翻译 ✅ COMPLETE
- [x] 翻译 `zh/guide/getting-started.md` — 快速开始
- [x] 翻译 `zh/guide/ui-components.md` — UI 组件
- [x] 翻译 `zh/guide/state-management.md` — 状态管理
- [x] 翻译 `zh/guide/actions.md` — 动作
- [x] 翻译 `zh/guide/styling.md` — 样式
- [x] 翻译 `zh/guide/code-generation.md` — 代码生成
- [x] 翻译 `zh/guide/platforms.md` — 平台支持
- [x] 翻译 `zh/guide/build-system.md` — 构建系统
- [x] 翻译 `zh/guide/api-reference.md` — API 参考
- [x] 翻译 `zh/guide/best-practices.md` — 最佳实践
- [x] 翻译 `zh/guide/extending.md` — 扩展
- [x] 翻译 `zh/guide/contributing.md` — 贡献指南
- [x] 更新 `config.ts` 中文侧边栏 — 13 个页面全部注册
- [x] 构建验证 — 35 页，0 错误，0 警告

## 决策日志
| 日期 | 决定 | 理由 |
|------|------|------|
| 2026-05-14 | 使用 `@vuepress/plugin-search@2.0.0-rc.128` | 兼容 VuePress 2 RC |
| 2026-05-14 | 安装 `sass-embedded` | VuePress 2 RC 需要编译 SCSS |
| 2026-05-14 | 配置 `/` 侧边栏包含 PROGRESS | 消除构建警告，方便导航到进度页 |
| 2026-05-14 | zh-CN 只创建首页和指南首页框架 | 后续逐步翻译 |

## 错误日志
| 错误 | 尝试 | 解决方法 |
|------|------|---------|
| `chalk` 模块路径错误 | npm install | 重新安装依赖 |
| `searchPlugin` 不是 named export | CommonJS 导入 | 安装 v2 RC 版本 |
| `sass-embedded` 未找到 | npm install -D | 安装 sass-embedded |
| PROGRESS.html 缺少 sidebar | 添加 `/` 侧边栏 | PROGRESS.md 在 Overview 中 |
