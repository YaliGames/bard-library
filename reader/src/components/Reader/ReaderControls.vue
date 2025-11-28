<template>
  <div class="reader-controls h-full flex flex-col">
    <!-- 头部 -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="font-medium text-gray-900 dark:text-gray-100">阅读设置</h3>
    </div>

    <!-- 设置内容 -->
    <div class="flex-1 p-4 space-y-6">
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
          show-input
          class="w-full"
        />
      </div>

      <!-- 行高 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          行高
        </label>
        <el-slider
          v-model="store.settings.lineHeight"
          :min="1.2"
          :max="2.0"
          :step="0.1"
          show-input
          class="w-full"
        />
      </div>

      <!-- 内容宽度 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          内容宽度
        </label>
        <el-slider
          v-model="store.settings.contentWidth"
          :min="600"
          :max="1000"
          :step="50"
          show-input
          class="w-full"
        />
      </div>

      <!-- 主题选择 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          主题
        </label>
        <el-radio-group v-model="store.settings.theme" class="w-full">
          <el-radio-button label="light">浅色</el-radio-button>
          <el-radio-button label="sepia">护眼</el-radio-button>
          <el-radio-button label="dark">深色</el-radio-button>
        </el-radio-group>
      </div>

      <!-- 分割线 -->
      <div class="border-t border-gray-200 dark:border-gray-700"></div>

      <!-- 搜索功能 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          搜索
        </label>
        <el-input
          v-model="store.searchState.keyword"
          placeholder="搜索内容..."
          clearable
          @keyup.enter="handleSearch"
        >
          <template #suffix>
            <el-button @click="handleSearch" type="primary" size="small">
              <span class="material-symbols-outlined text-sm">search</span>
            </el-button>
          </template>
        </el-input>

        <div class="mt-2 space-y-2">
          <el-checkbox v-model="store.searchState.caseSensitive">
            区分大小写
          </el-checkbox>
          <el-checkbox v-model="store.searchState.wholeWord">
            全词匹配
          </el-checkbox>
        </div>
      </div>

      <!-- 搜索结果 -->
      <div v-if="store.searchState.results.length > 0">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            搜索结果 ({{ store.searchState.results.length }})
          </span>
          <el-button @click="clearSearch" type="text" size="small">
            清除
          </el-button>
        </div>
        <div class="max-h-40 overflow-y-auto space-y-1">
          <div
            v-for="(result, index) in store.searchState.results"
            :key="index"
            class="text-xs p-2 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
            :class="{
              'bg-blue-100 dark:bg-blue-900': index === store.searchState.currentResultIndex
            }"
            @click="jumpToSearchResult(index)"
          >
            <div class="font-medium truncate">{{ result.chapterTitle }}</div>
            <div class="text-gray-600 dark:text-gray-400 truncate">{{ result.preview }}</div>
          </div>
        </div>
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
const handleSearch = () => {
  // TODO: 实现搜索功能
  console.log('Search functionality to be implemented')
}

const clearSearch = () => {
  store.searchState.keyword = ''
  store.searchState.results = []
  store.searchState.currentResultIndex = -1
}

const jumpToSearchResult = (index: number) => {
  // TODO: 跳转到搜索结果
  console.log('Jump to search result:', index)
}
</script>