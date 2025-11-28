<template><template><template>

  <div id="app" class="min-h-screen bg-gray-50">

    <!-- 顶部导航 -->  <div id="app">  <div id="app" class="min-h-screen bg-gray-50">

    <header class="bg-white shadow-sm border-b">

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">    <router-view />    <!-- 顶部导航 -->

        <div class="flex justify-between items-center h-16">

          <div class="flex items-center">  </div>    <header class="bg-white shadow-sm border-b">

            <router-link to="/" class="text-xl font-bold text-gray-900">

              📚 Bard Library Reader</template>      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            </router-link>

          </div>        <div class="flex justify-between items-center h-16">

          <nav class="flex space-x-4">

            <router-link<script setup lang="ts">          <div class="flex items-center">

              to="/"

              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"// 应用入口组件            <router-link to="/" class="text-xl font-bold text-gray-900">

              :class="{ 'text-blue-600': $route.path === '/' }"

            ></script>              📚 Bard Library Reader

              图书列表

            </router-link>            </router-link>

            <router-link

              to="/cache"<style>          </div>

              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"

              :class="{ 'text-blue-600': $route.path === '/cache' }"#app {          <nav class="flex space-x-4">

            >

              缓存管理  font-family: Avenir, Helvetica, Arial, sans-serif;            <router-link

            </router-link>

          </nav>  -webkit-font-smoothing: antialiased;              to="/"

        </div>

      </div>  -moz-osx-font-smoothing: grayscale;              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"

    </header>

}              :class="{ 'text-blue-600': $route.path === '/' }"

    <!-- 主要内容 -->

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"></style>            >

      <router-view />              图书列表

    </main>            </router-link>

  </div>            <router-link

</template>              to="/cache"

              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"

<script setup lang="ts">              :class="{ 'text-blue-600': $route.path === '/cache' }"

// 应用入口组件            >

</script>              缓存管理

            </router-link>

<style>          </nav>

/* 全局样式 */        </div>

html, body {      </div>

  margin: 0;    </header>

  padding: 0;

  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',    <!-- 主要内容 -->

    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    sans-serif;      <router-view />

  -webkit-font-smoothing: antialiased;    </main>

  -moz-osx-font-smoothing: grayscale;

}    <!-- 网络状态提示 -->

</style>    <div
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
