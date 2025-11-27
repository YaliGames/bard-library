<template>
  <el-dialog v-model="visible" title="本地缓存管理" width="600px" :close-on-click-modal="false">
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
        <div class="space-y-4">
          <!-- 统计信息 -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ cacheStats.totalBooks }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">已缓存书籍</div>
              </div>
              <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ cacheStats.totalSize }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">占用空间</div>
              </div>
              <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ isCached ? '✓' : '✗' }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">当前书籍</div>
              </div>
            </div>
          </div>

          <!-- 当前书籍操作 -->
          <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h3 class="text-sm font-semibold mb-3 text-gray-800 dark:text-gray-200">当前书籍</h3>
            <div
              class="group flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <div>
                <div class="font-medium text-sm text-gray-800 dark:text-gray-200 truncate">
                  {{ bookTitle }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ chapters.length }} 章</div>
              </div>
              <div class="flex gap-2">
                <el-button
                  v-if="!isCached"
                  type="primary"
                  size="small"
                  :loading="cacheLoading"
                  @click="handleCacheCurrentBook"
                >
                  <span class="material-symbols-outlined text-base mr-1">download</span>
                  缓存到本地
                </el-button>
                <el-button
                  v-else
                  type="danger"
                  size="small"
                  link
                  class="opacity-0 group-hover:opacity-100 transition-opacity"
                  @click="handleDeleteCache(fileId)"
                >
                  删除
                </el-button>
              </div>
            </div>
          </div>

          <!-- 缓存列表 -->
          <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">所有缓存</h3>
              <el-button
                type="danger"
                size="small"
                :disabled="cachedBooks.length === 0"
                @click="handleClearAllCache"
              >
                <span class="material-symbols-outlined text-base mr-1">delete</span>
                删除所有缓存
              </el-button>
            </div>
            <div v-if="cachedBooks.length === 0" class="text-center py-4 text-gray-400">
              暂无缓存
            </div>
            <div v-else class="space-y-2 max-h-64 overflow-y-auto">
              <div
                v-for="book in cachedBooks"
                :key="book.fileId"
                class="group flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm text-gray-800 dark:text-gray-200 truncate">
                    {{ book.bookTitle || book.fileName }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ book.chapterCount }} 章 · {{ book.size }}
                  </div>
                </div>
                <el-button
                  type="danger"
                  size="small"
                  link
                  class="opacity-0 group-hover:opacity-100 transition-opacity"
                  @click="handleDeleteCache(book.fileId)"
                >
                  删除
                </el-button>
              </div>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="flex justify-end">
            <el-button @click="visible = false">关闭</el-button>
          </div>
        </div>
      </template>
    </CacheCore>
  </el-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import CacheCore from '../Core/CacheCore.vue'
import type { Chapter } from '@/api/txt'
import type { CachedBook } from '@/utils/txtCache'

interface Props {
  modelValue: boolean
  fileId: number
  bookId?: number
  bookTitle: string
  chapters: Chapter[]
  cachedBook: CachedBook | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'cache-complete': []
}>()

const visible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})
</script>
