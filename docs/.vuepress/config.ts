import { defineUserConfig } from 'vuepress'
import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { searchPlugin } from '@vuepress/plugin-search'

// GitHub repo
const REPO = 'yangweijie/perry-php'

export default defineUserConfig({
  // For GitHub Pages: <user>.github.io/perry-php
  // Override via env: DOCS_BASE=/perry-php/ npm run docs:build
  base: process.env.DOCS_BASE || '/perry-php/',

  bundler: viteBundler(),

  // ========== Locales (English default) ==========
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

  // ========== Plugins ==========
  plugins: [
    searchPlugin({
      locales: {
        '/': {
          placeholder: 'Search docs...',
        },
        '/zh/': {
          placeholder: '搜索文档...',
        },
      },
    }),
  ],

  // ========== Theme ==========
  theme: defaultTheme({
    logo: null,
    repo: `https://github.com/${REPO}`,
    repoLabel: 'GitHub',

    // ----- Navbar -----
    navbar: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/' },
      { text: 'Examples', link: '/examples/' },
      { text: 'Progress', link: '/PROGRESS.html' },
      { text: 'GitHub', link: `https://github.com/${REPO}` },
    ],

    // ----- Sidebar -----
    sidebar: {
      '/': [
        {
          text: 'Overview',
          children: ['/README.md', '/PROGRESS.md'],
        },
      ],
      '/guide/': [
        {
          text: 'Guide',
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
          children: [
            '/examples/README.md',
            '/examples/calculator.md',
            '/examples/counter.md',
            '/examples/todo.md',
            '/examples/pry.md',
          ],
        },
      ],
      '/zh/guide/': [
        {
          text: '指南',
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
          children: [
            '/zh/examples/README.md',
            '/zh/examples/calculator.md',
            '/zh/examples/counter.md',
            '/zh/examples/todo.md',
            '/zh/examples/pry.md',
          ],
        },
      ],
    },
  }),
})
