import { defineConfig } from 'vite'import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'

import { resolve } from 'path'import { defineConfig } from 'vite'

import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({

  plugins: [vue()],// https://vite.dev/config/

  resolve: {export default defineConfig({

    alias: {  plugins: [

      '@': resolve(__dirname, 'src'),    vue(),

    },    vueDevTools(),

  },  ],

  server: {  resolve: {

    port: 3001,    alias: {

    host: true,      '@': fileURLToPath(new URL('./src', import.meta.url))

  },    },

})  },
  server: {
    port: 5174,
    open: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
