/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        // 通过 CSS 变量与 Element Plus 保持配色一致
        primary: 'var(--el-color-primary)',
        success: 'var(--el-color-success)',
        warning: 'var(--el-color-warning)',
        danger: 'var(--el-color-danger)',
        info: 'var(--el-color-info)',
      },
      borderColor: {
        DEFAULT: 'rgba(60,60,67,0.15)'
      }
    },
  },
  corePlugins: {
    preflight: false,
  },
  plugins: [],
}
