<template>
  <el-tabs v-model="innerTab" stretch class="!mb-1">
    <el-tab-pane label="章节" name="chapters">
      <ChapterCore
        :chapters="chapters"
        :current-chapter-index="currentChapterIndex"
        @open-chapter="emit('open-chapter', $event)"
      >
        <template
          #default="{ chapters: chapterList, hasChapters, handleChapterClick, isCurrentChapter }"
        >
          <el-empty v-if="!hasChapters" description="当前文件暂无目录"></el-empty>
          <div v-else ref="chaptersScrollRef" class="max-h-[60vh] overflow-auto space-y-1">
            <div
              v-for="c in chapterList"
              :key="c.index"
              :data-chapter-index="c.index"
              @click="handleChapterClick(c.index)"
              :class="[
                'px-2 py-1 rounded-md cursor-pointer transition flex items-center gap-2 hover:bg-gray-200',
                isCurrentChapter(c.index) ? 'bg-primary/10 text-primary font-semibold' : '',
              ]"
            >
              <span
                :class="['text-xs text-gray-500', isCurrentChapter(c.index) ? 'text-primary' : '']"
              >
                #{{ c.index }}
              </span>
              <span class="truncate">{{ c.title || '(无标题)' }}</span>
            </div>
          </div>
        </template>
      </ChapterCore>
    </el-tab-pane>

    <el-tab-pane label="书签" name="bookmarks">
      <el-empty v-if="!isLoggedIn" description="登录后可查看书签"></el-empty>

      <BookmarkCore
        v-else
        :bookmarks="filteredBookmarks"
        :chapters="chapters"
        @jump-to-bookmark="emit('jump', $event)"
        @remove-bookmark="emit('remove', $event)"
      >
        <template
          #default="{
            hasBookmarks,
            bookmarks,
            sortMode,
            setSortMode,
            getBookmarkChapter,
            getBookmarkPreview,
            handleBookmarkClick,
            handleRemoveBookmark,
          }"
        >
          <el-empty v-if="!hasBookmarks" description="当前文件暂无书签"></el-empty>
          <div v-else class="max-h-[60vh] overflow-auto space-y-1">
            <div
              class="sticky top-0 z-10 bg-white/80 backdrop-blur px-2 pt-1 pb-2 flex items-center justify-between"
            >
              <span class="text-xs text-gray-500">排序</span>
              <el-radio-group
                :model-value="sortMode"
                @update:model-value="setSortMode"
                size="small"
              >
                <el-radio-button label="article">章节顺序</el-radio-button>
                <el-radio-button label="created">创建时间</el-radio-button>
              </el-radio-group>
            </div>
            <div
              v-for="b in bookmarks"
              :key="b.id"
              class="group relative px-2 py-2 rounded-md cursor-pointer transition hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="handleBookmarkClick(b)"
            >
              <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                {{ getBookmarkChapter(b).title }}
              </div>
              <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
                {{ getBookmarkPreview(b) }}
              </div>
              <el-button
                @click.stop="handleRemoveBookmark(b)"
                type="danger"
                circle
                text
                class="!absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <template #icon>
                  <span class="material-symbols-outlined text-sm">delete</span>
                </template>
              </el-button>
            </div>
          </div>
        </template>
      </BookmarkCore>
    </el-tab-pane>
  </el-tabs>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import ChapterCore from '@/components/Reader/Txt/Core/ChapterCore.vue'
import BookmarkCore from '@/components/Reader/Txt/Core/BookmarkCore.vue'
import type { Bookmark } from '@/api/types'

type TabKey = 'chapters' | 'bookmarks'

interface Chapter {
  index: number
  title?: string | null
  offset?: number
  length?: number
}

const props = defineProps<{
  tab: TabKey
  chapters: Chapter[]
  currentChapterIndex: number | null
  isLoggedIn: boolean
  filteredBookmarks: Bookmark[]
  autoScrollCategory?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:tab', v: TabKey): void
  (e: 'open-chapter', index: number): void
  (e: 'jump', b: Bookmark): void
  (e: 'remove', b: Bookmark): void
}>()

const innerTab = computed({
  get: () => props.tab,
  set: (v: TabKey) => emit('update:tab', v),
})

const chaptersScrollRef = ref<HTMLElement | null>(null)

// 将当前章节在目录容器中对齐到顶部
function scrollActiveChapterToTop(smooth = true) {
  try {
    if (props.autoScrollCategory === false) return
    if (innerTab.value !== 'chapters') return
    const container = chaptersScrollRef.value
    if (!container) return
    const idx = props.currentChapterIndex
    if (idx == null) return
    const target = container.querySelector(`[data-chapter-index="${idx}"]`) as HTMLElement | null
    if (!target) return
    const crect = container.getBoundingClientRect()
    const trect = target.getBoundingClientRect()
    const delta = trect.top - crect.top // distance from container top to target top
    const newTop = container.scrollTop + delta
    container.scrollTo({ top: Math.max(0, newTop), behavior: smooth ? 'smooth' : 'auto' })
  } catch {
    /* noop */
  }
}

watch(
  () => props.currentChapterIndex,
  async () => {
    await nextTick()
    requestAnimationFrame(() => requestAnimationFrame(() => scrollActiveChapterToTop(true)))
  },
)

watch(
  () => innerTab.value,
  async tab => {
    if (tab === 'chapters') {
      await nextTick()
      requestAnimationFrame(() => scrollActiveChapterToTop(true))
    }
  },
)

onMounted(() => {
  requestAnimationFrame(() => scrollActiveChapterToTop(false))
})
</script>
