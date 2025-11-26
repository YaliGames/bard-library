<template>
  <div
    :class="[
      'fixed bottom-0 left-0 right-0 z-50 transition-transform duration-300',
      'bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg',
      visible ? 'translate-y-0' : 'translate-y-full',
    ]"
  >
    <div class="grid grid-cols-5 gap-1 px-2 py-2">
      <!-- 搜索 -->
      <button
        @click="$emit('search')"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl">search</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">搜索</span>
      </button>

      <!-- 上一章 -->
      <button
        @click="$emit('prev')"
        :disabled="!hasPrev"
        :class="[
          'flex flex-col items-center justify-center py-2 rounded',
          hasPrev
            ? 'hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600'
            : 'opacity-40 cursor-not-allowed',
        ]"
      >
        <span class="material-symbols-outlined text-xl">arrow_back</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">上一章</span>
      </button>

      <!-- 目录/菜单 -->
      <button
        @click="$emit('menu')"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl text-blue-500">menu_book</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">目录</span>
      </button>

      <!-- 下一章 -->
      <button
        @click="$emit('next')"
        :disabled="!hasNext"
        :class="[
          'flex flex-col items-center justify-center py-2 rounded',
          hasNext
            ? 'hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600'
            : 'opacity-40 cursor-not-allowed',
        ]"
      >
        <span class="material-symbols-outlined text-xl">arrow_forward</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">下一章</span>
      </button>

      <!-- 设置 -->
      <button
        @click="$emit('settings')"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl">settings</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">设置</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  visible: boolean
  cached: boolean
  hasPrev: boolean
  hasNext: boolean
}

defineProps<Props>()

defineEmits<{
  prev: []
  menu: []
  next: []
  search: []
  settings: []
}>()
</script>
