<template>
  <div
    v-if="visible"
    :class="[
      'fixed z-50 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 shadow-lg',
      'md:top-20 md:right-6 md:w-80 md:rounded-lg',
      'max-md:inset-0 max-md:rounded-none',
    ]"
  >
    <SearchCore
      ref="searchCoreRef"
      :visible="visible"
      :current-chapter-content="currentChapterContent"
      :current-chapter-index="currentChapterIndex"
      :chapters="chapters"
      :sentences="sentences"
      @jump-to-result="result => $emit('jump-to-result', result)"
      @search-global="(keyword, cs, ww) => $emit('search-global', keyword, cs, ww)"
      @search-chapter="(keyword, cs, ww) => $emit('search-chapter', keyword, cs, ww)"
    >
      <template
        #default="{
          mode,
          keyword,
          caseSensitive,
          wholeWord,
          searching,
          searched,
          results,
          currentIndex,
          setMode,
          setKeyword,
          toggleCaseSensitive,
          toggleWholeWord,
          clearKeyword,
          handleSearch,
          jumpToResult,
          prevResult,
          nextResult,
        }"
      >
        <!-- 标题栏 -->
        <div
          class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700"
        >
          <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
            {{ mode === 'chapter' ? '章节搜索' : '全文搜索' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            <span class="material-symbols-outlined text-xl">close</span>
          </button>
        </div>

        <div class="p-3">
          <!-- 搜索模式切换 -->
          <el-radio-group
            :model-value="mode"
            @update:model-value="setMode"
            size="small"
            class="mb-3 w-full"
          >
            <el-radio-button label="chapter">当前章节</el-radio-button>
            <el-radio-button label="global">全文搜索</el-radio-button>
          </el-radio-group>

          <!-- 搜索输入框 -->
          <div class="relative mb-3">
            <el-input
              ref="searchInputRef"
              :model-value="keyword"
              @update:model-value="setKeyword"
              placeholder="输入关键词..."
              size="small"
              clearable
              @keyup.enter="handleSearch"
              @clear="clearKeyword"
            >
              <template #suffix>
                <span v-if="searching" class="text-xs text-gray-400">搜索中...</span>
                <span v-else-if="results.length > 0" class="text-xs text-gray-500">
                  {{ currentIndex + 1 }} / {{ results.length }}
                </span>
              </template>
            </el-input>
          </div>

          <!-- 搜索选项 -->
          <div class="flex items-center gap-3 mb-3 text-sm">
            <el-checkbox
              :model-value="caseSensitive"
              @update:model-value="toggleCaseSensitive"
              size="small"
            >
              区分大小写
            </el-checkbox>
            <el-checkbox
              :model-value="wholeWord"
              @update:model-value="toggleWholeWord"
              size="small"
            >
              全字匹配
            </el-checkbox>
          </div>

          <!-- 搜索按钮 -->
          <div class="flex mb-3">
            <el-button
              size="small"
              type="primary"
              :disabled="!keyword || searching"
              @click="handleSearch"
              class="flex-1"
            >
              <span class="material-symbols-outlined text-base mr-1">search</span>
              搜索
            </el-button>
            <el-button size="small" :disabled="results.length === 0" @click="prevResult">
              <span class="material-symbols-outlined text-base">arrow_upward</span>
            </el-button>
            <el-button size="small" :disabled="results.length === 0" @click="nextResult">
              <span class="material-symbols-outlined text-base">arrow_downward</span>
            </el-button>
          </div>

          <!-- 搜索结果列表 -->
          <div
            v-if="results.length > 0"
            class="max-h-96 md:max-h-96 max-md:max-h-[calc(100vh-20rem)] overflow-y-auto"
          >
            <div class="text-xs text-gray-500 mb-2">找到 {{ results.length }} 处结果</div>
            <div
              v-for="(result, index) in results"
              :key="index"
              @click="jumpToResult(index)"
              :class="[
                'px-2 py-2 mb-1 rounded cursor-pointer text-sm',
                'hover:bg-gray-100 dark:hover:bg-gray-700',
                currentIndex === index
                  ? 'bg-blue-100 dark:bg-blue-900'
                  : 'bg-gray-50 dark:bg-gray-750',
              ]"
            >
              <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                {{ result.chapterTitle || `第 ${result.chapterIndex + 1} 章` }}
              </div>
              <div
                class="text-gray-700 dark:text-gray-300 line-clamp-2"
                v-html="result.preview"
              ></div>
            </div>
          </div>

          <!-- 无结果提示 -->
          <div v-else-if="keyword && searched && !searching" class="text-center py-6 text-gray-400">
            <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
            <div class="text-sm">未找到匹配结果</div>
          </div>

          <!-- 初始提示 -->
          <div v-else-if="!searched" class="text-center py-6 text-gray-400">
            <span class="material-symbols-outlined text-4xl mb-2">search</span>
            <div class="text-sm">输入关键词开始搜索</div>
          </div>
        </div>
      </template>
    </SearchCore>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SearchCore from '../Core/SearchCore.vue'
import type { SearchResult } from '@/types/reader'

interface Props {
  visible: boolean
  currentChapterContent: string
  currentChapterIndex: number | null
  chapters: Array<{ index?: number; title?: string | null; offset?: number; length?: number }>
  sentences?: string[]
}

defineProps<Props>()

defineEmits<{
  close: []
  'jump-to-result': [result: SearchResult]
  'search-global': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
  'search-chapter': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
}>()

const searchInputRef = ref<any>(null)
const searchCoreRef = ref<InstanceType<typeof SearchCore> | null>(null)

// 暴露方法供父组件调用
defineExpose({
  setGlobalResults: (globalResults: SearchResult[]) => {
    searchCoreRef.value?.setGlobalResults(globalResults)
  },
  setSearching: (value: boolean) => {
    searchCoreRef.value?.setSearching(value)
  },
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
