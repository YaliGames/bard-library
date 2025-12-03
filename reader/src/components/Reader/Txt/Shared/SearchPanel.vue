<template>
  <div :class="['flex flex-col h-full', inline ? '' : 'p-4']">
    <SearchCore
      ref="searchCoreRef"
      :visible="true"
      :current-chapter-content="currentChapterContent"
      :current-chapter-index="currentChapterIndex"
      :chapters="chapters"
      :sentences="sentences"
      @jump-to-result="handleJumpToResult"
      @search-global="handleSearchGlobal"
      @search-chapter="handleSearchChapter"
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
        <!-- 搜索模式 -->
        <div class="flex gap-2 mb-2">
          <button
            @click="setMode('chapter')"
            :class="[
              'flex-1 py-1 px-2 text-xs rounded transition-colors border',
              mode === 'chapter'
                ? 'bg-primary text-white border-primary'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',
            ]"
          >
            章节内
          </button>
          <button
            @click="setMode('global')"
            :class="[
              'flex-1 py-1 px-2 text-xs rounded transition-colors border',
              mode === 'global'
                ? 'bg-primary text-white border-primary'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',
            ]"
          >
            全书
          </button>
        </div>

        <!-- 输入框 -->
        <div class="relative mb-2">
          <input
            :value="keyword"
            @input="setKeyword(($event.target as HTMLInputElement).value)"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="搜索关键词..."
            class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none focus:border-primary"
          />
          <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
            <span v-if="searching" class="text-xs text-gray-400">...</span>
            <span v-else-if="results.length > 0" class="text-xs text-gray-400">
              {{ currentIndex + 1 }}/{{ results.length }}
            </span>
            <button
              v-if="keyword"
              @click="clearKeyword"
              class="text-gray-400 hover:text-gray-600"
            >
              <span class="material-symbols-outlined text-sm">close</span>
            </button>
          </div>
        </div>

        <!-- 选项 -->
        <div class="flex gap-3 mb-2 text-xs text-gray-600 dark:text-gray-400">
          <label class="flex items-center gap-1 cursor-pointer">
            <input
              type="checkbox"
              :checked="caseSensitive"
              @change="toggleCaseSensitive"
              class="rounded border-gray-300"
            />
            区分大小写
          </label>
          <label class="flex items-center gap-1 cursor-pointer">
            <input
              type="checkbox"
              :checked="wholeWord"
              @change="toggleWholeWord"
              class="rounded border-gray-300"
            />
            全字匹配
          </label>
        </div>

        <!-- 按钮 -->
        <div class="flex gap-2 mb-2">
          <button
            @click="handleSearch"
            :disabled="!keyword.trim() || searching"
            class="flex-1 py-1.5 bg-primary text-white text-sm rounded hover:bg-primary-dark disabled:opacity-50"
          >
            {{ searching ? '搜索中' : '搜索' }}
          </button>
          <div class="flex gap-1">
            <button
              @click="prevResult"
              :disabled="results.length === 0"
              class="px-2 py-1.5 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            >
              <span class="material-symbols-outlined text-sm">arrow_upward</span>
            </button>
            <button
              @click="nextResult"
              :disabled="results.length === 0"
              class="px-2 py-1.5 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            >
              <span class="material-symbols-outlined text-sm">arrow_downward</span>
            </button>
          </div>
        </div>

        <!-- 结果列表 -->
        <div class="flex-1 overflow-y-auto min-h-0 border-t border-gray-200 dark:border-gray-700 pt-2">
          <div v-if="!searched" class="text-center py-4 text-xs text-gray-400">
            输入关键词搜索
          </div>
          <div v-else-if="results.length === 0" class="text-center py-4 text-xs text-gray-400">
            无结果
          </div>
          <div v-else class="space-y-1">
            <div
              v-for="(result, index) in results"
              :key="index"
              @click="jumpToResult(index)"
              :class="[
                'p-2 rounded cursor-pointer text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
                index === currentIndex ? 'bg-primary/10 border border-primary/20' : '',
              ]"
            >
              <div v-if="mode === 'global'" class="text-xs text-primary mb-0.5">
                {{ result.chapterTitle }}
              </div>
              <div class="line-clamp-2 text-gray-700 dark:text-gray-300" v-html="result.preview"></div>
            </div>
          </div>
        </div>
      </template>
    </SearchCore>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SearchCore from '@/components/Reader/Txt/Core/SearchCore.vue'
import type { SearchResult } from '@/types/reader'

interface Chapter {
  index?: number
  title?: string | null
}

interface Props {
  inline?: boolean
  currentChapterContent: string
  currentChapterIndex: number | null
  chapters: Chapter[]
  sentences?: string[]
}

withDefaults(defineProps<Props>(), {
  inline: false,
})

const emit = defineEmits<{
  'jump-to-result': [result: SearchResult]
  'search-global': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
  'search-chapter': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
}>()

const searchCoreRef = ref<InstanceType<typeof SearchCore>>()

function handleJumpToResult(result: SearchResult) {
  emit('jump-to-result', result)
}

function handleSearchGlobal(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
  emit('search-global', keyword, caseSensitive, wholeWord)
}

function handleSearchChapter(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
  emit('search-chapter', keyword, caseSensitive, wholeWord)
}

// 暴露方法
defineExpose({
  setGlobalResults: (results: SearchResult[]) => searchCoreRef.value?.setGlobalResults(results),
  setSearching: (searching: boolean) => searchCoreRef.value?.setSearching(searching),
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
