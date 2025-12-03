<template>
  <div :class="['flex flex-col h-full', inline ? '' : 'p-4']">
    <CacheCore
      :file-id="fileId"
      :book-id="bookId"
      :book-title="bookTitle"
      :chapters="chapters"
      :cached-book="cachedBook"
      @cache-complete="emit('cache-complete')"
    >
      <template
        #default="{
          isCached,
          cacheStats,
          cachedBooks,
          cacheLoading,
          handleCacheCurrentBook,
          handleDeleteCache,
          handleClearAllCache,
        }"
      >
        <!-- 统计信息 -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="grid grid-cols-2 gap-4 text-center">
            <div>
              <div class="text-lg font-semibold text-primary">{{ cacheStats.totalBooks }}</div>
              <div class="text-xs text-gray-500">已缓存书籍</div>
            </div>
            <div>
              <div class="text-lg font-semibold text-primary">{{ cacheStats.totalSize }}</div>
              <div class="text-xs text-gray-500">占用空间</div>
            </div>
          </div>
        </div>

        <!-- 当前书籍操作 -->
        <div class="mb-4">
          <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">当前书籍</div>
          <div class="flex items-center justify-between p-2 border border-gray-200 dark:border-gray-700 rounded">
            <div class="truncate flex-1 mr-2 text-sm">{{ bookTitle }}</div>
            <button
              v-if="!isCached"
              @click="handleCacheCurrentBook"
              :disabled="cacheLoading"
              class="px-3 py-1.5 bg-primary text-white text-xs rounded hover:bg-primary-dark disabled:opacity-50"
            >
              {{ cacheLoading ? '缓存中...' : '缓存' }}
            </button>
            <button
              v-else
              @click="handleDeleteCache(fileId)"
              class="px-3 py-1.5 bg-red-50 text-red-600 text-xs rounded hover:bg-red-100"
            >
              删除
            </button>
          </div>
        </div>

        <!-- 所有缓存列表 -->
        <div class="flex-1 flex flex-col min-h-0">
          <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">缓存列表</div>
            <button
              v-if="cachedBooks.length > 0"
              @click="handleClearAllCache"
              class="text-xs text-red-500 hover:text-red-600"
            >
              清空所有
            </button>
          </div>

          <div class="flex-1 overflow-y-auto space-y-2">
            <div v-if="cachedBooks.length === 0" class="text-center py-8 text-xs text-gray-400">
              暂无缓存书籍
            </div>
            <div
              v-for="book in cachedBooks"
              :key="book.fileId"
              class="p-2 border border-gray-100 dark:border-gray-800 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 group"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0 mr-2">
                  <div class="text-sm font-medium truncate text-gray-800 dark:text-gray-200">
                    {{ book.bookTitle || book.fileName }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ book.chapterCount }} 章 · {{ book.size }}
                  </div>
                </div>
                <button
                  @click="handleDeleteCache(book.fileId)"
                  class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-opacity"
                >
                  <span class="material-symbols-outlined text-sm">delete</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </CacheCore>
  </div>
</template>

<script setup lang="ts">
import CacheCore from '@/components/Reader/Txt/Core/CacheCore.vue'
import type { CachedBook } from '@/utils/txtCache'

interface Chapter {
  title?: string | null
  index: number
  offset: number
  length: number
}

interface Props {
  inline?: boolean
  fileId: number
  bookId?: number
  bookTitle: string
  chapters: Chapter[]
  cachedBook: CachedBook | null
}

const props = withDefaults(defineProps<Props>(), {
  inline: false,
})

const emit = defineEmits<{
  'cache-complete': []
  'update:modelValue': [value: boolean]
}>()
</script>
