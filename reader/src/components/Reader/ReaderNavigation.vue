<template>
  <div class="reader-navigation h-full flex flex-col">
    <!-- 头部 -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="font-medium text-gray-900 dark:text-gray-100">章节目录</h3>
    </div>

    <!-- 章节列表 -->
    <div class="flex-1 overflow-y-auto">
      <div
        v-for="(chapter, index) in store.chapters"
        :key="index"
        class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        :class="{
          'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700': index === store.currentChapterIndex
        }"
        @click="handleChapterClick(index)"
      >
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
          {{ chapter.title }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          第 {{ index + 1 }} 章
        </div>
      </div>
    </div>

    <!-- 底部操作区 -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">
          共 {{ store.chapters.length }} 章
        </span>
        <el-button
          v-if="!store.isCached && store.chapters.length > 0"
          type="primary"
          size="small"
          @click="handleCacheBook"
        >
          缓存
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type { UseReaderStore } from '@/stores/reader'

// 注入store和actions
const store = inject<UseReaderStore>('readerStore')!
const actions = inject('readerActions') as any

// 事件处理
const handleChapterClick = (index: number) => {
  actions.openChapter(index)
}

const handleCacheBook = () => {
  // TODO: 实现缓存功能
  console.log('Cache book functionality to be implemented')
}
</script>