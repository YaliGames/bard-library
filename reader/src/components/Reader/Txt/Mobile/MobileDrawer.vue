<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showDrawer" @click="closeDrawer" class="fixed inset-0 bg-black/50 z-50"></div>
    </Transition>

    <Transition name="slide-up">
      <div
        v-if="showDrawer"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl max-h-[80vh] flex flex-col"
      >
        <!-- 顶部拖拽条 -->
        <div
          class="flex items-center justify-center py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        <!-- 标签切换 -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="[
              'flex-1 py-3 px-2 text-sm font-medium transition-colors whitespace-nowrap',
              activeTab === tab.value
                ? 'text-blue-500 border-b-2 border-blue-500'
                : 'text-gray-600 dark:text-gray-400',
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- 内容区 -->
        <div ref="contentScrollRef" class="flex-1 overflow-y-auto min-h-0 mt-2">
          <!-- 章节 -->
          <div v-if="activeTab === 'chapters'" class="p-2">
            <ChapterCore
              :chapters="chapters"
              :current-chapter-index="currentChapterIndex"
              @open-chapter="handleChapterClick"
            >
              <template
                #default="{
                  hasChapters,
                  chapters: chapterList,
                  handleChapterClick: onChapterClick,
                  isCurrentChapter,
                }"
              >
                <div v-if="!hasChapters" class="text-center py-8 text-gray-400">
                  <span class="material-symbols-outlined text-4xl mb-2">menu_book</span>
                  <div class="text-sm">暂无章节</div>
                </div>
                <div v-else ref="chaptersScrollRef">
                  <div
                    v-for="(chapter, index) in chapterList"
                    :key="index"
                    :data-chapter-index="index"
                    @click="onChapterClick(index)"
                    :class="[
                      'px-4 py-3 rounded-lg mb-1 cursor-pointer transition-colors',
                      isCurrentChapter(index)
                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300',
                    ]"
                  >
                    <div class="text-sm font-medium">
                      {{ chapter.title || `第 ${index + 1} 章` }}
                    </div>
                  </div>
                </div>
              </template>
            </ChapterCore>
          </div>

          <!-- 书签 -->
          <div v-else-if="activeTab === 'bookmarks'">
            <BookmarkCore
              :bookmarks="bookmarks"
              :chapters="chapters"
              @jump-to-bookmark="handleBookmarkClick"
              @remove-bookmark="handleRemoveBookmark"
            >
              <template
                #default="{
                  hasBookmarks,
                  bookmarks: bookmarkList,
                  sortMode,
                  setSortMode,
                  getBookmarkChapter,
                  getBookmarkPreview,
                  handleBookmarkClick: onBookmarkClick,
                  handleRemoveBookmark: onRemoveBookmark,
                }"
              >
                <div v-if="!hasBookmarks" class="text-center py-8 text-gray-400">
                  <span class="material-symbols-outlined text-4xl mb-2">bookmark_border</span>
                  <div class="text-sm">暂无书签</div>
                </div>
                <div v-else ref="bookmarksScrollRef">
                  <!-- 排序选项 -->
                  <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-4 py-2 mb-2">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-500 dark:text-gray-400">排序</span>
                      <div class="flex gap-2">
                        <button
                          @click="setSortMode('article')"
                          :class="[
                            'px-3 py-1 rounded transition-colors',
                            sortMode === 'article'
                              ? 'bg-blue-500 text-white'
                              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                          ]"
                        >
                          章节顺序
                        </button>
                        <button
                          @click="setSortMode('created')"
                          :class="[
                            'px-3 py-1 rounded transition-colors',
                            sortMode === 'created'
                              ? 'bg-blue-500 text-white'
                              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                          ]"
                        >
                          创建时间
                        </button>
                      </div>
                    </div>
                  </div>
                  <!-- 书签列表 -->
                  <div
                    v-for="bookmark in bookmarkList"
                    :key="bookmark.id"
                    class="group relative px-4 py-3 rounded-lg mb-1 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    @click="onBookmarkClick(bookmark)"
                  >
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                      {{ getBookmarkChapter(bookmark).title }}
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
                      {{ getBookmarkPreview(bookmark) }}
                    </div>
                    <button
                      @click.stop="onRemoveBookmark(bookmark)"
                      class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20"
                    >
                      <span class="material-symbols-outlined text-base text-red-500">delete</span>
                    </button>
                  </div>
                </div>
              </template>
            </BookmarkCore>
          </div>

          <!-- 缓存 -->
          <div v-else-if="activeTab === 'cache'" class="p-4">
            <CacheCore
              :file-id="fileId"
              :book-id="bookId"
              :book-title="bookTitle"
              :chapters="chapters"
              :cached-book="cachedBook"
              @cache-complete="onCacheComplete"
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
                <!-- 缓存统计 -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                  <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg text-center">
                    <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                      {{ cacheStats.totalBooks }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">已缓存书籍</div>
                  </div>
                  <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg text-center">
                    <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                      {{ cacheStats.totalSize }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">占用空间</div>
                  </div>
                  <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg text-center">
                    <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                      {{ isCached ? '✓' : '✗' }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">当前书籍</div>
                  </div>
                </div>

                <!-- 当前书籍 -->
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">当前书籍</div>
                  <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0 mr-3">
                      <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                        {{ bookTitle }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ chapters.length }} 章
                      </div>
                    </div>
                    <button
                      v-if="!isCached"
                      @click="handleCacheCurrentBook"
                      :disabled="cacheLoading"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                        'bg-blue-500 text-white hover:bg-blue-600',
                        cacheLoading ? 'opacity-50 cursor-not-allowed' : '',
                      ]"
                    >
                      <span v-if="cacheLoading">缓存中...</span>
                      <span v-else>缓存本书</span>
                    </button>
                    <button
                      v-else
                      @click="handleDeleteCache(fileId)"
                      class="px-3 py-2 rounded-lg text-sm font-medium bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors whitespace-nowrap"
                    >
                      删除
                    </button>
                  </div>
                </div>

                <!-- 所有缓存 -->
                <div class="flex items-center justify-between mb-2">
                  <div class="text-sm font-medium text-gray-700 dark:text-gray-300">所有缓存</div>
                  <button
                    v-if="cachedBooks.length > 0"
                    @click="handleClearAllCache"
                    class="text-xs text-red-500 hover:text-red-600"
                  >
                    清空所有缓存
                  </button>
                </div>

                <div v-if="cachedBooks.length === 0" class="text-center py-8 text-gray-400">
                  <span class="material-symbols-outlined text-4xl mb-2">cloud_off</span>
                  <div class="text-sm">暂无缓存</div>
                </div>

                <div v-else class="space-y-2">
                  <div
                    v-for="book in cachedBooks"
                    :key="book.fileId"
                    class="group p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1 min-w-0 mr-2">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                          {{ book.bookTitle || book.fileName }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                          {{ book.chapterCount }} 章 · {{ book.size }}
                        </div>
                      </div>
                      <button
                        @click="handleDeleteCache(book.fileId)"
                        class="opacity-0 group-hover:opacity-100 transition-opacity px-2 py-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                      >
                        <span class="material-symbols-outlined text-base">delete</span>
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </CacheCore>
          </div>

          <!-- 设置 -->
          <div v-else-if="activeTab === 'settings'" class="p-4">
            <SettingsCore
              :settings="settings"
              @update-theme="onUpdateTheme"
              @update-font-size="onUpdateFontSize"
              @update-line-height="onUpdateLineHeight"
              @update-content-width="onUpdateContentWidth"
            >
              <template
                #default="{
                  settings: currentSettings,
                  themeOptions,
                  fontSizeRange,
                  lineHeightRange,
                  contentWidthRange,
                  updateTheme,
                  updateFontSize,
                  updateLineHeight,
                  updateContentWidth,
                }"
              >
                <div class="space-y-6">
                  <!-- 主题 -->
                  <div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                      主题
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                      <button
                        v-for="option in themeOptions"
                        :key="option.value"
                        @click="updateTheme(option.value)"
                        :class="[
                          'py-2 px-3 rounded-lg text-sm font-medium transition-colors',
                          currentSettings.theme === option.value
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                        ]"
                      >
                        {{ option.label }}
                      </button>
                    </div>
                  </div>

                  <!-- 字体大小 -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        字体大小
                      </span>
                      <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ currentSettings.fontSize }}px
                      </span>
                    </div>
                    <input
                      type="range"
                      :value="currentSettings.fontSize"
                      @input="updateFontSize(Number(($event.target as HTMLInputElement).value))"
                      :min="fontSizeRange.min"
                      :max="fontSizeRange.max"
                      :step="fontSizeRange.step"
                      class="w-full range-slider"
                    />
                  </div>

                  <!-- 行高 -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">行高</span>
                      <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ currentSettings.lineHeight.toFixed(1) }}
                      </span>
                    </div>
                    <input
                      type="range"
                      :value="currentSettings.lineHeight"
                      @input="updateLineHeight(Number(($event.target as HTMLInputElement).value))"
                      :min="lineHeightRange.min"
                      :max="lineHeightRange.max"
                      :step="lineHeightRange.step"
                      class="w-full range-slider"
                    />
                  </div>

                  <!-- 内容宽度 -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        内容宽度
                      </span>
                      <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ currentSettings.contentWidth }}px
                      </span>
                    </div>
                    <input
                      type="range"
                      :value="currentSettings.contentWidth"
                      @input="updateContentWidth(Number(($event.target as HTMLInputElement).value))"
                      :min="contentWidthRange.min"
                      :max="contentWidthRange.max"
                      :step="contentWidthRange.step"
                      class="w-full range-slider"
                    />
                  </div>
                </div>
              </template>
            </SettingsCore>
          </div>
        </div>

        <!-- 关闭按钮 -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
          <button
            @click="closeDrawer"
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
import { ref, watch, nextTick, inject, computed } from 'vue'
import ChapterCore from '@/components/Reader/Txt/Core/ChapterCore.vue'
import BookmarkCore from '@/components/Reader/Txt/Core/BookmarkCore.vue'
import CacheCore from '@/components/Reader/Txt/Core/CacheCore.vue'
import SettingsCore from '@/components/Reader/Txt/Core/SettingsCore.vue'
import type { Bookmark } from '@/api/types'
import type { ThemeKey } from '@/types/reader'
import type { ReaderContext } from '@/types/readerContext'

const readerContext = inject<ReaderContext>('readerContext')!

const chapters = computed(() => readerContext.chapters.value)
const bookmarks = computed(() => readerContext.bookmarks.value)
const currentChapterIndex = computed(() => readerContext.currentChapterIndex.value)
const fileId = computed(() => readerContext.fileId)
const bookId = computed(() =>
  typeof readerContext.bookId === 'number' ? readerContext.bookId : readerContext.bookId?.value,
)
const bookTitle = computed(() => readerContext.bookTitle.value)
const settings = computed(() => readerContext.settings.value)
const cachedBook = computed(() => readerContext.cachedBook.value)
const defaultTabProp = computed(() =>
  readerContext.mobileDrawerDefaultTab ? readerContext.mobileDrawerDefaultTab.value : undefined,
)
const autoScrollCategory = computed(() =>
  readerContext.autoScrollCategory ? readerContext.autoScrollCategory.value : undefined,
)
const showDrawer = computed(() => readerContext.showMobileDrawer.value)

const tabs = [
  { value: 'chapters', label: '目录' },
  { value: 'bookmarks', label: '书签' },
  { value: 'cache', label: '缓存' },
  { value: 'settings', label: '设置' },
] as const

function normalizeTab(t: any) {
  if (t === 'chapters' || t === 'bookmarks' || t === 'cache' || t === 'settings') return t
  return 'chapters'
}

const activeTab = ref<'chapters' | 'bookmarks' | 'cache' | 'settings'>(
  normalizeTab(defaultTabProp.value),
)

const contentScrollRef = ref<HTMLElement | null>(null)
const chaptersScrollRef = ref<HTMLElement | null>(null)
const bookmarksScrollRef = ref<HTMLElement | null>(null)

// 将当前章节滚动到可见位置
function scrollActiveChapterToTop(smooth = true) {
  try {
    if (autoScrollCategory.value === false) return
    if (activeTab.value !== 'chapters') return
    // 使用内容区的滚动容器
    const container = contentScrollRef.value
    if (!container) return
    const idx = currentChapterIndex.value
    if (idx == null) return
    // 从章节列表中查找目标元素
    const chapterList = chaptersScrollRef.value
    if (!chapterList) return
    const target = chapterList.querySelector(`[data-chapter-index="${idx}"]`) as HTMLElement | null
    if (!target) return
    // 计算目标元素相对于滚动容器的位置
    const containerRect = container.getBoundingClientRect()
    const targetRect = target.getBoundingClientRect()
    const delta = targetRect.top - containerRect.top
    const newScrollTop = container.scrollTop + delta
    container.scrollTo({ top: Math.max(0, newScrollTop), behavior: smooth ? 'smooth' : 'auto' })
  } catch {
    /* noop */
  }
}

// 监听defaultTab变化
watch(
  [() => readerContext.showMobileDrawer.value, () => defaultTabProp.value],
  ([visible, defaultTab]) => {
    if (visible && defaultTab) {
      activeTab.value = normalizeTab(defaultTab)
    }
  },
)

watch(
  () => defaultTabProp.value,
  newTab => {
    if (newTab) {
      activeTab.value = normalizeTab(newTab)
    }
  },
)

// 监听当前章节变化，自动滚动
watch(
  () => currentChapterIndex.value,
  async () => {
    await nextTick()
    requestAnimationFrame(() => requestAnimationFrame(() => scrollActiveChapterToTop(true)))
  },
)

// 监听标签切换，切换到章节时滚动
watch(
  () => activeTab.value,
  async tab => {
    if (tab === 'chapters') {
      await nextTick()
      requestAnimationFrame(() => scrollActiveChapterToTop(true))
    } else if (tab === 'bookmarks') {
      await nextTick()
      try {
        contentScrollRef.value?.scrollTo({ top: 0 })
      } catch {}
    }
  },
)

// 监听抽屉打开，初始滚动
watch(
  () => readerContext.showMobileDrawer.value,
  async visible => {
    if (visible && activeTab.value === 'chapters') {
      await nextTick()
      requestAnimationFrame(() => scrollActiveChapterToTop(false))
    }
    if (visible && activeTab.value === 'bookmarks') {
      await nextTick()
      try {
        contentScrollRef.value?.scrollTo({ top: 0 })
      } catch {}
    }
  },
)

function handleChapterClick(index: number) {
  readerContext.openChapter(index)
  readerContext.showMobileDrawer.value = false
}

function handleBookmarkClick(bookmark: Bookmark) {
  readerContext.jumpToBookmark(bookmark)
  readerContext.showMobileDrawer.value = false
}

function handleRemoveBookmark(bookmark: Bookmark) {
  readerContext.removeBookmarkConfirm(bookmark)
}

// Settings update handlers: update injected settings when available, otherwise emit events for parent
function onUpdateTheme(theme: ThemeKey) {
  readerContext.settings.value.theme = theme
}

function onUpdateFontSize(size: number) {
  readerContext.settings.value.fontSize = size
}

function onUpdateLineHeight(height: number) {
  readerContext.settings.value.lineHeight = height
}

function onUpdateContentWidth(width: number) {
  readerContext.settings.value.contentWidth = width
}

function closeDrawer() {
  readerContext.showMobileDrawer.value = false
}

function onCacheComplete() {
  // refresh cache status in page context
  if (readerContext.loadCacheStatus) readerContext.loadCacheStatus()
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* 滑动条样式 */
.range-slider {
  -webkit-appearance: none;
  appearance: none;
  height: 6px;
  border-radius: 3px;
  background: #e5e7eb;
  outline: none;
}

.dark .range-slider {
  background: #4b5563;
}

.range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  transition: all 0.2s;
}

.range-slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
  background: #2563eb;
}

.range-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border: none;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  transition: all 0.2s;
}

.range-slider::-moz-range-thumb:hover {
  transform: scale(1.2);
  background: #2563eb;
}

.range-slider::-moz-range-track {
  height: 6px;
  border-radius: 3px;
  background: #e5e7eb;
}

.dark .range-slider::-moz-range-track {
  background: #4b5563;
}
</style>
