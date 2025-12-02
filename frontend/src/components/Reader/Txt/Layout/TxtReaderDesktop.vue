<template>
  <section class="flex gap-4 items-start md:p-4 lg:p-6">
    <!-- 左侧导览 -->
    <aside
      class="hidden md:block w-[320px] shrink-0 sticky top-4 self-start max-h-[calc(100vh-2rem)]"
    >
      <div
        class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden flex flex-col max-h-full"
      >
        <div
          class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">导览</h3>
          <div class="flex">
            <el-button @click="readerContext.openRightPanel('cache')">
              <span class="material-symbols-outlined text-base">
                {{ cachedBook ? 'check' : 'download' }}
              </span>
              {{ cachedBook ? '已缓存' : '缓存' }}
            </el-button>
            <el-button
              type="primary"
              v-permission="'books.edit'"
              @click="readerContext.goEditChapters"
            >
              编辑章节
            </el-button>
          </div>
        </div>
        <div class="p-2 flex-1 overflow-y-auto">
          <TxtNavTabs
            v-model:tab="leftTab"
            :chapters="chapters"
            :current-chapter-index="currentChapterIndex"
            :is-logged-in="isLoggedIn"
            :filtered-bookmarks="filteredBookmarks"
            :auto-scroll-category="autoScrollCategory"
            @open-chapter="readerContext.openChapter"
            @jump="readerContext.jumpToBookmark"
            @remove="readerContext.removeBookmarkConfirm"
          />
        </div>
      </div>
    </aside>
    <!-- 主阅读区 -->
    <main class="flex-1 min-w-0 pb-20 md:pb-0">
      <!-- 顶部工具栏（PC 显示） -->
      <div class="hidden md:flex items-center justify-between mb-3 sticky top-4 z-10">
        <div
          class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm w-full"
        >
          <div class="flex items-center justify-between px-3 py-2">
            <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">
              {{ bookTitle || '正文' }}
            </h3>
            <div class="flex items-center">
              <TxtChapterNav
                :has-prev="hasPrevChapter"
                :has-next="hasNextChapter"
                @prev="readerContext.goPrevChapter"
                @back="readerContext.backToBook"
                @next="readerContext.goNextChapter"
              />
            </div>
            <div class="flex items-center gap-2">
              <el-button @click="readerContext.openRightPanel('search')">
                <span class="material-symbols-outlined text-base">search</span>
              </el-button>
              <el-button type="primary" @click="readerContext.openRightPanel('settings')">
                阅读设置
              </el-button>
            </div>
          </div>
        </div>
      </div>
      <slot />
      <!-- 结束主阅读区 -->
    </main>

    <RightTools />
  </section>
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type { ReaderContext } from '@/types/readerContext'
import TxtNavTabs from '@/components/Reader/Txt/Desktop/TxtNavTabs.vue'
import TxtChapterNav from '@/components/Reader/Txt/Desktop/TxtChapterNav.vue'
import RightTools from '@/components/Reader/Txt/Desktop/RightTools.vue'

const readerContext = inject<ReaderContext>('readerContext')!

const chapters = readerContext.chapters
const currentChapterIndex = readerContext.currentChapterIndex
const isLoggedIn = readerContext.isLoggedIn
const filteredBookmarks = readerContext.filteredBookmarks
const autoScrollCategory = readerContext.autoScrollCategory

const leftTab = readerContext.leftTab

const hasPrevChapter = readerContext.hasPrevChapter
const hasNextChapter = readerContext.hasNextChapter

const cachedBook = readerContext.cachedBook
const bookTitle = readerContext.bookTitle
</script>
