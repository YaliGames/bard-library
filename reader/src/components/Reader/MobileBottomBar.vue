<template>
  <div class="mobile-bottom-bar bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-2">
    <div class="flex items-center justify-between">
      <!-- 上一章 -->
      <el-button
        :disabled="!store.hasPrevChapter"
        @click="actions.goPrevChapter"
        type="text"
        class="p-2"
      >
        <span class="material-symbols-outlined">chevron_left</span>
      </el-button>

      <!-- 章节信息 -->
      <div class="flex-1 text-center">
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ (store.currentChapterIndex ?? 0) + 1 }} / {{ store.chapters.length }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
          {{ store.currentChapter?.title }}
        </div>
      </div>

      <!-- 下一章 -->
      <el-button
        :disabled="!store.hasNextChapter"
        @click="actions.goNextChapter"
        type="text"
        class="p-2"
      >
        <span class="material-symbols-outlined">chevron_right</span>
      </el-button>

      <!-- 菜单按钮 -->
      <el-button
        @click="actions.openMobileDrawer('chapters')"
        type="text"
        class="p-2 ml-2"
      >
        <span class="material-symbols-outlined">menu</span>
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type { UseReaderStore } from '@/stores/reader'

// 注入store和actions
const store = inject<UseReaderStore>('readerStore')!
const actions = inject('readerActions') as any
</script>