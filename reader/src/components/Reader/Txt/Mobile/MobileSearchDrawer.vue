<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" @click="$emit('close')" class="fixed inset-0 bg-black/50 z-50"></div>
    </Transition>
    <Transition name="slide-up">
      <div
        v-if="visible"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl max-h-[85vh] flex flex-col"
      >
        <div
          class="flex items-center justify-center py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        <div class="flex-1 p-3 min-h-0 flex flex-col">
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
              <!-- 搜索模式切换 -->
              <div class="flex gap-2 mb-3">
                <button
                  @click="setMode('chapter')"
                  :class="[
                    'flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors',
                    mode === 'chapter'
                      ? 'bg-blue-500 text-white'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                  ]"
                >
                  章节内搜索
                </button>
                <button
                  @click="setMode('global')"
                  :class="[
                    'flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-colors',
                    mode === 'global'
                      ? 'bg-blue-500 text-white'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                  ]"
                >
                  全书搜索
                </button>
              </div>

              <!-- 搜索输入框 -->
              <div class="mb-3">
                <div class="relative">
                  <input
                    :value="keyword"
                    @input="setKeyword(($event.target as HTMLInputElement).value)"
                    @keyup.enter="handleSearch"
                    type="text"
                    placeholder="输入关键词..."
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none focus-within:ring-2 focus-within:ring-[var(--el-color-primary)]"
                  />
                  <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-2 items-center">
                    <span v-if="searching" class="text-xs text-gray-500">搜索中...</span>
                    <span v-else-if="results.length > 0" class="text-xs text-gray-500">
                      {{ currentIndex + 1 }}/{{ results.length }}
                    </span>
                    <button
                      v-if="keyword"
                      @click="clearKeyword"
                      class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z"
                          clip-rule="evenodd"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- 搜索选项 -->
              <div class="flex gap-4 mb-3">
                <el-checkbox
                  :model-value="caseSensitive"
                  @update:model-value="toggleCaseSensitive"
                  label="区分大小写"
                  size="large"
                />
                <el-checkbox
                  :model-value="wholeWord"
                  @update:model-value="toggleWholeWord"
                  label="全字匹配"
                  size="large"
                />
              </div>

              <!-- 搜索按钮 -->
              <div class="flex gap-2 mb-3">
                <button
                  @click="handleSearch"
                  :disabled="!keyword.trim() || searching"
                  :class="[
                    'flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-colors',
                    'bg-blue-500 text-white hover:bg-blue-600',
                    !keyword.trim() || searching ? 'opacity-50 cursor-not-allowed' : '',
                  ]"
                >
                  {{ searching ? '搜索中...' : '搜索' }}
                </button>
                <button
                  v-if="searched && results.length > 0"
                  @click="prevResult"
                  :disabled="results.length === 0"
                  class="px-3 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="material-symbols-outlined text-lg">arrow_upward</span>
                </button>
                <button
                  v-if="searched && results.length > 0"
                  @click="nextResult"
                  :disabled="results.length === 0"
                  class="px-3 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="material-symbols-outlined text-lg">arrow_downward</span>
                </button>
              </div>

              <!-- 搜索结果 -->
              <div class="flex-1 overflow-y-auto">
                <div v-if="!searched" class="text-center py-8 text-gray-500 dark:text-gray-400">
                  输入关键词开始搜索
                </div>
                <div
                  v-else-if="results.length === 0"
                  class="text-center py-8 text-gray-500 dark:text-gray-400"
                >
                  未找到相关结果
                </div>
                <div v-else-if="mode === 'chapter'" class="space-y-2">
                  <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    找到 {{ results.length }} 个结果
                  </div>
                  <div
                    v-for="(result, index) in results"
                    :key="index"
                    @click="jumpToResult(index)"
                    :class="[
                      'p-3 rounded-lg cursor-pointer transition-colors',
                      index === currentIndex
                        ? 'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800'
                        : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700',
                    ]"
                  >
                    <div
                      class="text-sm text-gray-900 dark:text-gray-100"
                      v-html="result.preview"
                    ></div>
                  </div>
                </div>
                <div v-else class="space-y-2">
                  <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    找到 {{ results.length }} 个结果
                  </div>
                  <div
                    v-for="(result, index) in results"
                    :key="index"
                    @click="jumpToResult(index)"
                    class="p-3 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">
                      {{ result.chapterTitle }}
                    </div>
                    <div
                      class="text-sm text-gray-900 dark:text-gray-100"
                      v-html="result.preview"
                    ></div>
                  </div>
                </div>
              </div>
            </template>
          </SearchCore>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 p-3 flex-shrink-0">
          <button
            @click="$emit('close')"
            class="w-full py-2.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            关闭
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, inject } from 'vue'
import type { ReaderContext } from '@/types/reader'
import SearchCore from '@/components/Reader/Txt/Core/SearchCore.vue'
import type { SearchResult } from '@/types/reader'

const readerContext = inject<ReaderContext>('readerContext')!

// Use direct aliases to the provided Refs for simplicity and clarity
const visible = readerContext.mobileSearchVisible
const currentChapterContent = readerContext.content
const currentChapterIndex = readerContext.currentChapterIndex
const chapters = readerContext.chapters
const sentences = readerContext.sentences

const emit = defineEmits<{
  close: []
  'jump-to-result': [result: SearchResult]
  'search-chapter': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
  'search-global': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
}>()

const searchCoreRef = ref<InstanceType<typeof SearchCore>>()
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
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
