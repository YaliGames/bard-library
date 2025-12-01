<template>
  <div class="h-full flex flex-col bg-gray-50 dark:bg-gray-900">
    <!-- Mobile Header -->
    <header v-if="isMobile" class="bg-white dark:bg-gray-800 shadow-sm px-4 py-3 flex items-center justify-between z-10">
      <div class="font-bold text-lg text-gray-900 dark:text-white">{{ pageTitle }}</div>
      <div class="flex items-center gap-2">
        <slot name="header-actions"></slot>
      </div>
    </header>

    <!-- Desktop Navbar -->
    <nav v-else class="bg-white dark:bg-gray-800 shadow-sm z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="font-bold text-xl text-primary">Bard Reader</span>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  $route.path === item.path
                    ? 'border-primary text-gray-900 dark:text-white'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                {{ item.name }}
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <slot name="desktop-actions"></slot>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="overflow-auto h-full">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Mobile Bottom TabBar -->
    <div v-if="isMobile && showBottomBar" class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 pb-safe">
      <div class="flex justify-around items-center h-14">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex flex-col items-center justify-center w-full h-full space-y-1"
          :class="[
            $route.path === item.path
              ? 'text-primary'
              : 'text-gray-500 dark:text-gray-400'
          ]"
        >
          <span class="material-symbols-outlined text-2xl">{{ item.icon }}</span>
          <span class="text-[10px]">{{ item.name }}</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useWindowSize } from '@vueuse/core'

const route = useRoute()
const { width } = useWindowSize()
const isMobile = computed(() => width.value < 640) // sm breakpoint

const navItems = [
  { name: '首页', path: '/', icon: 'home' },
  { name: '书库', path: '/books', icon: 'library_books' },
  { name: '离线书架', path: '/cache', icon: 'cloud_off' },
]

const pageTitle = computed(() => {
  const item = navItems.find(i => i.path === route.path)
  return item ? item.name : 'Bard Reader'
})

const showBottomBar = computed(() => {
  // 可以在这里添加逻辑来隐藏特定路由的底部栏
  return !route.meta.hideBottomBar
})
</script>

<style scoped>
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom);
}
</style>
