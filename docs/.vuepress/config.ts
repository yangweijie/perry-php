import { defineUserConfig } from 'vuepress'
import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'

export default defineUserConfig({
  lang: 'en-US',
  title: 'Perry PHP',
  description: 'Cross-platform UI code generation framework — define UI once in PHP, generate native code for 11 platforms.',

  // For GitHub Pages deployment:
  // If repo is <user>.github.io/perry-php, set base to '/perry-php/'
  // Override via env: DOCS_BASE=/perry-php/ npm run docs:build
  base: process.env.DOCS_BASE || '/',

  bundler: viteBundler(),

  theme: defaultTheme({
    logo: null,
    repo: 'https://github.com/mikonos/perry-php',
    repoLabel: 'GitHub',

    navbar: [
      { text: 'Guide', link: '/guide/' },
      { text: 'Examples', link: '/examples/' },
      {
        text: 'GitHub',
        link: 'https://github.com/mikonos/perry-php',
      },
    ],

    sidebar: {
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
            '/guide/extending.md',
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
    },
  }),
})
