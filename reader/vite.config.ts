import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  base: './', // Important for Capacitor/offline use
  server: {
    port: 5174, // Different port from frontend
    proxy: {
      '/api': {
        target: 'http://localhost:8000', // Assuming backend is on 8000
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})
