<template>
  <div class="mobile-drawer h-full flex flex-col">
    <!-- 头部 -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <h3 class="font-medium text-gray-900 dark:text-gray-100">
        {{ tabTitles[store.mobileDrawerTab] }}
      </h3>
      <el-button @click="actions.closeMobileDrawer" type="text" class="p-1">
        <span class="material-symbols-outlined">close</span>
      </el-button>
    </div>

    <!-- 内容区域 -->
    <div class="flex-1 overflow-y-auto">
      <!-- 章节标签页 -->
      <div v-if="store.mobileDrawerTab === 'chapters'" class="p-4">
        <div class="space-y-2">
          <div
            v-for="(chapter, index) in store.chapters"
            :key="index"
            class="p-3 rounded-lg cursor-pointer transition-colors"
            :class="{
              'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100': index === store.currentChapterIndex,
              'hover:bg-gray-100 dark:hover:bg-gray-700': index !== store.currentChapterIndex
            }"
            @click="handleChapterClick(index)"
          >
            <div class="text-sm font-medium truncate">
              {{ chapter.title }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              第 {{ index + 1 }} 章
            </div>
          </div>
        </div>
      </div>

      <!-- 书签标签页 -->
      <div v-else-if="store.mobileDrawerTab === 'bookmarks'" class="p-4">
        <div v-if="store.bookmarks.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
          <span class="material-symbols-outlined text-4xl mb-2">bookmark_border</span>
          <p>暂无书签</p>
        </div>
        <div v-else class="space-y-2">
          <!-- TODO: 实现书签列表 -->
          <p class="text-gray-500 dark:text-gray-400">书签功能开发中...</p>
        </div>
      </div>

      <!-- 设置标签页 -->
      <div v-else-if="store.mobileDrawerTab === 'settings'" class="p-4 space-y-4">
        <!-- 字体大小 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            字体大小
          </label>
          <el-slider
            v-model="store.settings.fontSize"
            :min="12"
            :max="24"
            :step="2"
            class="w-full"
          />
        </div>

        <!-- 主题选择 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            主题
          </label>
          <el-radio-group v-model="store.settings.theme" class="flex flex-col space-y-2">
            <el-radio label="light">浅色</el-radio>
            <el-radio label="sepia">护眼</el-radio>
            <el-radio label="dark">深色</el-radio>
          </el-radio-group>
        </div>
      </div>

      <!-- 缓存标签页 -->
      <div v-else-if="store.mobileDrawerTab === 'cache'" class="p-4">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">缓存状态</span>
            <el-tag :type="store.isCached ? 'success' : 'info'">
              {{ store.isCached ? '已缓存' : '未缓存' }}
            </el-tag>
          </div>

          <div v-if="!store.isCached" class="text-center">
            <el-button type="primary" @click="handleCacheBook">
              缓存整本书
            </el-button>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
              缓存后可在离线状态下阅读
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部标签栏 -->
    <div class="border-t border-gray-200 dark:border-gray-700">
      <div class="flex">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="flex-1 p-3 text-center transition-colors"
          :class="{
            'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900': store.mobileDrawerTab === tab.key,
            'text-gray-600 dark:text-gray-400': store.mobileDrawerTab !== tab.key
          }"
          @click="store.mobileDrawerTab = tab.key"
        >
          <span class="material-symbols-outlined text-lg">{{ tab.icon }}</span>
          <div class="text-xs mt-1">{{ tab.label }}</div>
        </button>
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

// 标签页配置
const tabs = [
  { key: 'chapters', label: '目录', icon: 'list' },
  { key: 'bookmarks', label: '书签', icon: 'bookmark' },
  { key: 'settings', label: '设置', icon: 'settings' },
  { key: 'cache', label: '缓存', icon: 'download' }
] as const

const tabTitles = {
  chapters: '章节目录',
  bookmarks: '书签管理',
  settings: '阅读设置',
  cache: '缓存管理'
}

// 事件处理
const handleChapterClick = (index: number) => {
  actions.openChapter(index)
  actions.closeMobileDrawer()
}

const handleCacheBook = () => {
  // TODO: 实现缓存功能
  console.log('Cache book functionality to be implemented')
}
</script>