<template>
  <div class="reader-content flex flex-col h-full">
    <!-- 顶部工具栏（桌面端） -->
    <div class="hidden md:flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="flex items-center gap-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ store.book?.title || 'TXT阅读器' }}
        </h1>
        <span class="text-sm text-gray-500 dark:text-gray-400">
          第 {{ (store.currentChapterIndex ?? 0) + 1 }} 章
          <span v-if="store.currentChapter?.title">
            - {{ store.currentChapter.title }}
          </span>
        </span>
      </div>

      <div class="flex items-center gap-2">
        <el-button
          :disabled="!store.hasPrevChapter"
          @click="actions.goPrevChapter"
        >
          <span class="material-symbols-outlined text-base">chevron_left</span>
          上一章
        </el-button>

        <el-button
          :disabled="!store.hasNextChapter"
          @click="actions.goNextChapter"
        >
          下一章
          <span class="material-symbols-outlined text-base">chevron_right</span>
        </el-button>

        <el-button @click="actions.toggleSearch">
          <span class="material-symbols-outlined text-base">search</span>
        </el-button>

        <el-button type="primary" @click="actions.toggleSettings">
          <span class="material-symbols-outlined text-base">settings</span>
          设置
        </el-button>
      </div>
    </div>

    <!-- 内容区域 -->
    <div class="flex-1 overflow-auto">
      <div
        class="reader-text-content"
        :style="contentStyle"
      >
        <!-- 加载状态 -->
        <template v-if="store.isLoading">
          <div class="flex items-center justify-center h-64">
            <el-skeleton
              :rows="8"
              animated
              class="w-full max-w-2xl"
            />
          </div>
        </template>

        <!-- 空状态 -->
        <template v-else-if="!store.content">
          <div class="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400">
            <span class="material-symbols-outlined text-6xl mb-4">article</span>
            <p class="text-lg">请选择章节开始阅读</p>
            <el-button
              type="primary"
              class="mt-4 md:hidden"
              @click="actions.openMobileDrawer('chapters')"
            >
              打开目录
            </el-button>
          </div>
        </template>

        <!-- 内容显示 -->
        <template v-else>
          <div class="max-w-none mx-auto px-4 py-8">
            <TxtContentRenderer
              ref="contentRef"
              :content="store.content"
              :sentences="store.sentences"
              :mark-ranges="store.markRanges"
              :search-highlight="searchHighlight"
              @selection="handleSelection"
              @mark-click="handleMarkClick"
            />
          </div>
        </template>
      </div>
    </div>

    <!-- 底部导航（桌面端） -->
    <div class="hidden md:flex items-center justify-center p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="flex items-center gap-4">
        <el-button
          :disabled="!store.hasPrevChapter"
          @click="actions.goPrevChapter"
        >
          <span class="material-symbols-outlined text-base">chevron_left</span>
          上一章
        </el-button>

        <span class="text-sm text-gray-600 dark:text-gray-400">
          {{ (store.currentChapterIndex ?? 0) + 1 }} / {{ store.chapters.length }}
        </span>

        <el-button
          :disabled="!store.hasNextChapter"
          @click="actions.goNextChapter"
        >
          下一章
          <span class="material-symbols-outlined text-base">chevron_right</span>
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, inject, ref } from 'vue'
import type { UseReaderStore } from '@/stores/reader'
import TxtContentRenderer from './TxtContentRenderer.vue'

// 注入store和actions
const store = inject<UseReaderStore>('readerStore')!
const actions = inject('readerActions') as any

// 内容渲染器引用
const contentRef = ref()

// 计算内容样式
const contentStyle = computed(() => ({
  '--reader-font-size': `${store.settings.fontSize}px`,
  '--reader-line-height': store.settings.lineHeight,
  '--reader-content-width': `${store.settings.contentWidth}px`,
  '--reader-theme-bg': themeColors[store.settings.theme].bg,
  '--reader-theme-fg': themeColors[store.settings.theme].fg,
}))

// 搜索高亮配置
const searchHighlight = computed(() => {
  if (!store.searchState.keyword) return null
  return {
    keyword: store.searchState.keyword,
    caseSensitive: store.searchState.caseSensitive,
    wholeWord: store.searchState.wholeWord
  }
})

// 主题颜色配置
const themeColors = {
  light: { bg: '#ffffff', fg: '#333333' },
  sepia: { bg: '#f5ecd9', fg: '#3b2f1e' },
  dark: { bg: '#111111', fg: '#dddddd' }
}

// 事件处理
const handleSelection = (event: any) => {
  // TODO: 处理选区事件
  console.log('Selection event:', event)
}

const handleMarkClick = (event: any) => {
  // TODO: 处理标记点击事件
  console.log('Mark click event:', event)
}
</script>

<style scoped>
.reader-text-content {
  font-size: var(--reader-font-size, 16px);
  line-height: var(--reader-line-height, 1.7);
  max-width: var(--reader-content-width, 720px);
  margin: 0 auto;
  background: var(--reader-theme-bg, #ffffff);
  color: var(--reader-theme-fg, #333333);
  min-height: 100%;
}
</style>