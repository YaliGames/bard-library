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
            <el-button @click="showCacheManager = true">
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
              <el-button @click="readerContext.toggleSearch">
                <span class="material-symbols-outlined text-base">search</span>
              </el-button>
              <el-button type="primary" @click="settingsVisible = true">阅读设置</el-button>
            </div>
          </div>
        </div>
      </div>
      <slot />
      <!-- PC 端设置抽屉 -->
      <el-drawer
        v-model="settingsVisible"
        title="阅读设置"
        direction="rtl"
        size="320px"
        class="hidden md:block"
      >
        <div class="flex flex-col gap-4">
          <div>
            <div class="mb-2">主题</div>
            <el-radio-group v-model="settings.theme">
              <el-radio-button label="light">明亮</el-radio-button>
              <el-radio-button label="sepia">米黄</el-radio-button>
              <el-radio-button label="dark">深色</el-radio-button>
            </el-radio-group>
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>字体大小</span>
              <span>{{ settings.fontSize }}px</span>
            </div>
            <el-slider v-model="settings.fontSize" :min="14" :max="24" :step="1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>行高</span>
              <span>{{ settings.lineHeight.toFixed(1) }}</span>
            </div>
            <el-slider v-model="settings.lineHeight" :min="1.4" :max="2.2" :step="0.1" />
          </div>
          <div>
            <div class="flex justify-between mb-1">
              <span>内容宽度</span>
              <span>{{ settings.contentWidth }}px</span>
            </div>
            <el-slider v-model="settings.contentWidth" :min="560" :max="960" :step="10" />
          </div>
        </div>
      </el-drawer>
    </main>

    <!-- PC 端搜索面板 -->
    <SearchPanel
      :ref="readerContext.searchPanelRef"
      :visible="searchVisible"
      :current-chapter-content="content"
      :current-chapter-index="currentChapterIndex"
      :chapters="chapters"
      :sentences="sentences"
      class="hidden md:block"
      @close="readerContext.handleSearchClose"
      @jump-to-result="readerContext.handleJumpToSearchResult"
      @search-chapter="readerContext.handleChapterSearch"
      @search-global="readerContext.handleGlobalSearch"
    />
  </section>

  <!-- PC 端缓存管理对话框 -->
  <CacheManager
    v-model="showCacheManager"
    :file-id="fileId"
    :book-id="bookId"
    :book-title="bookTitle"
    :chapters="chapters"
    :cached-book="cachedBook"
    @cache-complete="readerContext.loadCacheStatus"
    class="hidden md:block"
  />
</template>

<script setup lang="ts">
import { inject } from 'vue'
import type { ReaderContext } from '@/types/readerContext'
import TxtNavTabs from '@/components/Reader/Txt/Desktop/TxtNavTabs.vue'
import TxtChapterNav from '@/components/Reader/Txt/Desktop/TxtChapterNav.vue'
import SearchPanel from '@/components/Reader/Txt/Shared/SearchPanel.vue'
import CacheManager from '@/components/Reader/Txt/Shared/CacheManager.vue'

const readerContext = inject<ReaderContext>('readerContext')!

const chapters = readerContext.chapters
const currentChapterIndex = readerContext.currentChapterIndex
const isLoggedIn = readerContext.isLoggedIn
const filteredBookmarks = readerContext.filteredBookmarks
const autoScrollCategory = readerContext.autoScrollCategory

const leftTab = readerContext.leftTab

const hasPrevChapter = readerContext.hasPrevChapter
const hasNextChapter = readerContext.hasNextChapter

const settings = readerContext.settings
const settingsVisible = readerContext.settingsVisible

const content = readerContext.content
const sentences = readerContext.sentences

const searchVisible = readerContext.searchVisible

const cachedBook = readerContext.cachedBook
const showCacheManager = readerContext.showCacheManager

const fileId = readerContext.fileId
const bookId = readerContext.bookId
const bookTitle = readerContext.bookTitle
</script>
