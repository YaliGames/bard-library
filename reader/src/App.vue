<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <router-link to="/" class="text-xl font-bold text-gray-900">
              📚 Bard Library Reader
            </router-link>
          </div>
          <nav class="flex space-x-4">
            <router-link
              to="/"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
              :class="{ 'text-blue-600': $route.path === '/' }"
            >
              图书列表
            </router-link>
            <router-link
              to="/cache"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
              :class="{ 'text-blue-600': $route.path === '/cache' }"
            >
              缓存管理
            </router-link>
          </nav>
        </div>
      </div>
    </header>

    <!-- 主要内容 -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <router-view />
    </main>

    <!-- 网络状态提示 -->
    <div
      v-if="!networkStore.isOnline"
      class="fixed bottom-4 right-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-2 rounded-lg shadow-lg"
    >
      <div class="flex items-center">
        <span class="material-symbols-outlined text-sm mr-2">wifi_off</span>
        网络连接已断开，仅可阅读已缓存的图书
      </div>
    </div>
  <Transition name="slide-down">
    <div v-if="showLoading" class="loading-container">
      <LoadingAnimation :book-size="0.5" />
    </div>
  </Transition>
</div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useNetworkStore } from '@/stores'
import LoadingAnimation from './components/common/LoadingAnimation.vue'

const networkStore = useNetworkStore()
const showLoading = ref(true)

// 模拟初始化过程
onMounted(() => {
  // 这里可以添加实际的初始化逻辑
  setTimeout(() => {
    showLoading.value = false
  }, 2000) // 2秒后隐藏加载动画
})
</script>

<style>
/* 全局样式 */
html, body {
  margin: 0;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* 加载容器 */
.loading-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  z-index: 9999;
}

/* 退场动画：向下滑出 */
.slide-down-enter-active {
  transition: transform 0.5s ease-in-out;
}

.slide-down-leave-active {
  transition: transform 0.8s ease-in-out;
}

.slide-down-enter-from {
  transform: translateY(0);
}

.slide-down-leave-to {
  transform: translateY(100%);
}
</style>
