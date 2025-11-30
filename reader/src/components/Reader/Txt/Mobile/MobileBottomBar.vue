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
        @click="doSearch"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl">search</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">搜索</span>
      </button>

      <!-- 上一章 -->
      <button
        @click="doPrev"
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
        @click="doMenu"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl text-blue-500">menu_book</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">目录</span>
      </button>

      <!-- 下一章 -->
      <button
        @click="doNext"
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
        @click="doSettings"
        class="flex flex-col items-center justify-center py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600"
      >
        <span class="material-symbols-outlined text-xl">settings</span>
        <span class="text-xs mt-1 text-gray-700 dark:text-gray-300">设置</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject, computed } from 'vue'
import type { ReaderContext } from '@/types/readerContext'

const readerContext = inject<ReaderContext>('readerContext')!

const visible = computed(() => Boolean(readerContext.mobileBottomBarVisible.value))
const hasPrev = computed(
  () =>
    typeof readerContext.currentChapterIndex.value === 'number' &&
    readerContext.currentChapterIndex.value > 0,
)
const hasNext = computed(
  () =>
    typeof readerContext.currentChapterIndex.value === 'number' &&
    Array.isArray(readerContext.chapters.value) &&
    readerContext.currentChapterIndex.value < readerContext.chapters.value.length - 1,
)

function doPrev() {
  readerContext.goPrevChapter()
}

function doNext() {
  readerContext.goNextChapter()
}

function doMenu() {
  readerContext.openMobileDrawer('chapters')
}

function doSearch() {
  readerContext.toggleSearch()
}

function doSettings() {
  readerContext.openMobileDrawer('settings')
}
</script>
