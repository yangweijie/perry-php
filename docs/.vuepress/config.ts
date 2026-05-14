import { defineUserConfig } from 'vuepress'
import { viteBundler } from '@vuepress/bundler-vite'
import { llmsPlugin } from '@vuepress/plugin-llms'
import { hopeTheme } from 'vuepress-theme-hope'

// GitHub repo
const REPO = 'yangweijie/perry-php'

export default defineUserConfig({
  base: process.env.DOCS_BASE || '/perry-php/',

  bundler: viteBundler(),

  plugins: [
    llmsPlugin({
      domain: 'https://yangweijie.github.io/perry-php',
      locale: 'all',
      filter: (page) => !page.path.includes('PROGRESS'),
    }),
  ],

  // ========== Locales ==========
  locales: {
    '/': {
      lang: 'en-US',
      title: 'Perry PHP',
      description:
        'Cross-platform UI code generation framework — define UI once in PHP, generate native code for 11 platforms.',
    },
    '/zh/': {
      lang: 'zh-CN',
      title: 'Perry PHP',
      description: '跨平台 UI 代码生成框架 — 用 PHP 定义界面，为 11 个平台生成原生代码。',
    },
  },

  // ========== Theme ==========
  theme: hopeTheme({
    logo: null,
    repo: `https://github.com/${REPO}`,
    repoLabel: 'GitHub',
    docsRepo: `https://github.com/${REPO}`,
    docsBranch: 'main',
    docsDir: 'docs',

    // ----- Plugins -----
    plugins: {
      slimsearch: {
        locales: {
          '/': { placeholder: 'Search docs...' },
          '/zh/': { placeholder: '搜索文档...' },
        },
      },
    },

    // ===== Locale-specific theme config =====
    locales: {
      // English
      '/': {
        navbar: [
          { text: 'Home', link: '/' },
          { text: 'Guide', link: '/guide/' },
          { text: 'Examples', link: '/examples/' },
          { text: 'Progress', link: '/PROGRESS.html' },
          { text: 'GitHub', link: `https://github.com/${REPO}` },
        ],
        sidebar: {
          '/': [
            {
              text: 'Overview',
              icon: 'home',
              children: ['/README.md', '/PROGRESS.md'],
            },
          ],
          '/guide/': [
            {
              text: 'Guide',
              icon: 'book',
              children: [
                '/guide/README.md',
                '/guide/getting-started.md',
                '/guide/ui-components.md',
                '/guide/state-management.md',
                '/guide/actions.md',
                '/guide/styling.md',
                '/guide/code-generation.md',
                '/guide/platforms.md',
                '/guide/build-system.md',
                '/guide/api-reference.md',
                '/guide/best-practices.md',
                '/guide/extending.md',
                '/guide/contributing.md',
              ],
            },
          ],
          '/examples/': [
            {
              text: 'Examples',
              icon: 'code',
              children: [
                '/examples/README.md',
                '/examples/calculator.md',
                '/examples/counter.md',
                '/examples/todo.md',
                '/examples/pry.md',
                '/examples/perry-demo.md',
              ],
            },
          ],
        },
      },

      // Chinese
      '/zh/': {
        navbar: [
          { text: '首页', link: '/zh/' },
          { text: '指南', link: '/zh/guide/' },
          { text: '示例', link: '/zh/examples/' },
          { text: '进度', link: '/zh/PROGRESS.html' },
          { text: 'GitHub', link: `https://github.com/${REPO}` },
        ],
        sidebar: {
          '/zh/': [
            {
              text: '概览',
              icon: 'home',
              children: ['/zh/README.md', '/zh/PROGRESS.md'],
            },
          ],
          '/zh/guide/': [
            {
              text: '指南',
              icon: 'book',
              children: [
                '/zh/guide/README.md',
                '/zh/guide/getting-started.md',
                '/zh/guide/ui-components.md',
                '/zh/guide/state-management.md',
                '/zh/guide/actions.md',
                '/zh/guide/styling.md',
                '/zh/guide/code-generation.md',
                '/zh/guide/platforms.md',
                '/zh/guide/build-system.md',
                '/zh/guide/api-reference.md',
                '/zh/guide/best-practices.md',
                '/zh/guide/extending.md',
                '/zh/guide/contributing.md',
              ],
            },
          ],
          '/zh/examples/': [
            {
              text: '示例',
              icon: 'code',
              children: [
                '/zh/examples/README.md',
                '/zh/examples/calculator.md',
                '/zh/examples/counter.md',
                '/zh/examples/todo.md',
                '/zh/examples/pry.md',
                '/zh/examples/perry-demo.md',
              ],
            },
          ],
        },
      },
    },
  }),
})
