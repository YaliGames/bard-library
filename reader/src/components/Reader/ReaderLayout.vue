<template>
  <div class="reader-layout">
    <!-- 桌面端：三栏布局 -->
    <div class="hidden md:flex h-screen">
      <!-- 左侧导航栏 -->
      <aside class="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <slot name="navigation" />
      </aside>

      <!-- 主内容区 -->
      <main class="flex-1 flex flex-col min-w-0">
        <slot name="content" />
      </main>

      <!-- 右侧控制面板 -->
      <aside class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
        <slot name="controls" />
      </aside>
    </div>

    <!-- 移动端：全屏布局 -->
    <div class="md:hidden h-screen flex flex-col">
      <!-- 主内容区 -->
      <main class="flex-1 flex flex-col min-h-0">
        <slot name="content" />
      </main>

      <!-- 移动端底部栏 -->
      <slot name="mobile-bottom" />

      <!-- 移动端浮层 -->
      <Teleport to="body">
        <Transition name="fade">
          <div
            v-if="store.mobileDrawerVisible"
            class="fixed inset-0 z-50 bg-black bg-opacity-50"
            @click="store.closeMobileDrawer"
          >
            <Transition name="slide-right">
              <div
                v-if="store.mobileDrawerVisible"
                class="absolute right-0 top-0 h-full w-80 max-w-[90vw] bg-white dark:bg-gray-800 shadow-xl"
                @click.stop
              >
                <slot name="mobile-drawer" />
              </div>
            </Transition>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type { UseReaderStore } from '@/stores/reader'

// 注入store
const store = inject<UseReaderStore>('readerStore')!
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.3s ease;
}

.slide-right-enter-from {
  transform: translateX(100%);
}

.slide-right-leave-to {
  transform: translateX(100%);
}
</style>