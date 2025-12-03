<template>
  <aside
    v-show="panelOpen"
    class="hidden md:block w-[320px] shrink-0 sticky top-4 self-start max-h-[calc(100vh-2rem)]"
  >
    <div
      class="bg-white dark:bg-[var(--el-bg-color-overlay)] border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden flex flex-col max-h-full"
    >
      <div
        class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
      >
        <h3 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">工具</h3>
        <div class="flex items-center gap-2">
          <el-button size="small" @click="closePanel">
            <span class="material-symbols-outlined text-base">close</span>
          </el-button>
        </div>
      </div>

      <div class="p-2 flex-1 overflow-y-auto">
        <div class="flex border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <button
            type="button"
            class="flex-1 p-2 text-sm font-medium transition-colors whitespace-nowrap border-b-2"
            :class="
              readerContext.rightPanelTab.value === 'search'
                ? 'text-primary border-primary'
                : 'text-gray-600 dark:text-gray-400 border-gray-200'
            "
            @click="readerContext.rightPanelTab.value = 'search'"
          >
            搜索
          </button>
          <button
            type="button"
            class="flex-1 p-2 text-sm font-medium transition-colors whitespace-nowrap border-b-2"
            :class="
              readerContext.rightPanelTab.value === 'cache'
                ? 'text-primary border-primary'
                : 'text-gray-600 dark:text-gray-400 border-gray-200'
            "
            @click="readerContext.rightPanelTab.value = 'cache'"
          >
            缓存
          </button>
          <button
            type="button"
            class="flex-1 p-2 text-sm font-medium transition-colors whitespace-nowrap border-b-2"
            :class="
              readerContext.rightPanelTab.value === 'settings'
                ? 'text-primary border-primary'
                : 'text-gray-600 dark:text-gray-400 border-gray-200'
            "
            @click="readerContext.rightPanelTab.value = 'settings'"
          >
            设置
          </button>
        </div>

        <!-- 内容区：根据 tab 显示对应面板 -->
        <div class="mt-2">
          <div v-if="readerContext.rightPanelTab.value === 'search'">
            <SearchPanel
              ref="searchPanelRef"
              :inline="true"
              :current-chapter-content="content"
              :current-chapter-index="currentChapterIndex"
              :chapters="chapters"
              :sentences="sentences"
              @jump-to-result="readerContext.handleJumpToSearchResult"
              @search-chapter="readerContext.handleChapterSearch"
              @search-global="readerContext.handleGlobalSearch"
            />
          </div>

          <div v-else-if="readerContext.rightPanelTab.value === 'cache'">
            <CacheManager
              v-model="cacheVisible"
              :inline="true"
              :file-id="fileId"
              :book-id="bookId"
              :book-title="bookTitle"
              :chapters="chapters"
              :cached-book="cachedBook"
              @cache-complete="readerContext.loadCacheStatus"
            />
          </div>

          <div v-else-if="readerContext.rightPanelTab.value === 'settings'">
            <div class="p-2">
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
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { inject, computed } from 'vue'
import type { ReaderContext } from '@/types/readerContext'
import SearchPanel from '@/components/Reader/Txt/Shared/SearchPanel.vue'
import CacheManager from '@/components/Reader/Txt/Shared/CacheManager.vue'

const readerContext = inject<ReaderContext>('readerContext')!

const panelOpen = computed(() => {
  try {
    return readerContext.rightPanelOpen.value === true
  } catch {
    return false
  }
})

const cacheVisible = computed({
  get: () => readerContext.rightPanelTab.value === 'cache',
  set: (v: boolean) => {
    try {
      if (v) readerContext.rightPanelTab.value = 'cache'
      else if (readerContext.rightPanelTab.value === 'cache')
        readerContext.rightPanelTab.value = 'none'
    } catch {}
  },
})

function closePanel() {
  try {
    readerContext.closeRightPanel()
  } catch {}
}

const chapters = readerContext.chapters
const currentChapterIndex = readerContext.currentChapterIndex
const content = readerContext.content
const sentences = readerContext.sentences
const cachedBook = readerContext.cachedBook
const fileId = readerContext.fileId
const bookId = readerContext.bookId
const bookTitle = readerContext.bookTitle
const settings = readerContext.settings

const searchPanelRef = readerContext.searchPanelRef
</script>
