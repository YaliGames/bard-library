<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Bookmark } from '@/api/types'

interface Chapter {
  index: number
  title?: string | null
  offset?: number
  length?: number
}

interface Props {
  bookmarks: Bookmark[]
  chapters: Chapter[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'jump-to-bookmark': [bookmark: Bookmark]
  'remove-bookmark': [bookmark: Bookmark]
}>()

// 书签排序模式
const sortMode = ref<'article' | 'created'>('article')

// 计算属性
const hasBookmarks = computed(() => props.bookmarks.length > 0)

const sortedBookmarks = computed(() => {
  const list = props.bookmarks.slice()
  if (sortMode.value === 'article') {
    list.sort((a, b) => bookmarkPos(a) - bookmarkPos(b))
  } else {
    list.sort((a, b) => bookmarkCreatedAt(b) - bookmarkCreatedAt(a))
  }
  return list
})

// 方法：计算书签位置（用于排序）
function bookmarkPos(b: Bookmark): number {
  try {
    const loc = JSON.parse((b as any).location || '{}')
    if (typeof loc?.absStart === 'number') return Number(loc.absStart) || 0
    if (typeof loc?.start === 'number') return Number(loc.start) || 0
    if (typeof loc?.chapterIndex === 'number') return Number(loc.chapterIndex) * 1_000_000
    return 0
  } catch {
    return 0
  }
}

// 方法：获取书签创建时间（用于排序）
function bookmarkCreatedAt(b: Bookmark): number {
  const v = (b as any).createdAt ?? (b as any).created_at ?? null
  if (!v) return 0
  if (typeof v === 'number') return v
  const t = Date.parse(String(v))
  return isNaN(t) ? 0 : t
}

// 方法：获取书签的章节信息
function getBookmarkChapter(b: Bookmark): { index: number; title: string } {
  try {
    const loc = JSON.parse(b.location || '{}')
    let chapterIndex = 0

    // 优先使用 absStart 计算章节
    if (typeof loc?.absStart === 'number' && props.chapters.length > 0) {
      const abs = Number(loc.absStart)
      for (let i = 0; i < props.chapters.length; i++) {
        const ch = props.chapters[i]
        if (abs >= (ch.offset || 0) && abs < (ch.offset || 0) + (ch.length || 0)) {
          chapterIndex = i
          break
        }
      }
    } else if (typeof loc?.chapterIndex === 'number') {
      chapterIndex = loc.chapterIndex
    }

    const chapter = props.chapters[chapterIndex]
    const title = chapter?.title || `第 ${chapterIndex + 1} 章`

    return { index: chapterIndex, title }
  } catch {
    return { index: 0, title: '第 1 章' }
  }
}

// 方法：获取书签的预览文本
function getBookmarkPreview(b: Bookmark): string {
  try {
    const loc = JSON.parse(b.location || '{}')
    return loc?.selectionText || loc?.text || b.note || ''
  } catch {
    return b.note || ''
  }
}

// 方法：切换排序模式
function setSortMode(mode: 'article' | 'created') {
  sortMode.value = mode
}

// 方法：处理书签点击
function handleBookmarkClick(bookmark: Bookmark) {
  emit('jump-to-bookmark', bookmark)
}

// 方法：处理删除书签
function handleRemoveBookmark(bookmark: Bookmark) {
  emit('remove-bookmark', bookmark)
}

// 暴露给父组件
defineExpose({
  hasBookmarks,
  sortedBookmarks,
  sortMode,
  setSortMode,
  getBookmarkChapter,
  getBookmarkPreview,
  handleBookmarkClick,
  handleRemoveBookmark,
})
</script>

<template>
  <slot
    :has-bookmarks="hasBookmarks"
    :bookmarks="sortedBookmarks"
    :sort-mode="sortMode"
    :set-sort-mode="setSortMode"
    :get-bookmark-chapter="getBookmarkChapter"
    :get-bookmark-preview="getBookmarkPreview"
    :handle-bookmark-click="handleBookmarkClick"
    :handle-remove-bookmark="handleRemoveBookmark"
  >
    <!-- 默认UI -->
    <div v-if="!hasBookmarks" class="text-center py-8 text-gray-400">
      <div class="text-sm">暂无书签</div>
    </div>
    <div v-else>
      <div class="mb-2">
        <label class="text-xs text-gray-500 mr-2">排序:</label>
        <button @click="setSortMode('article')" :class="sortMode === 'article' ? 'font-bold' : ''">
          章节顺序
        </button>
        <button @click="setSortMode('created')" :class="sortMode === 'created' ? 'font-bold' : ''">
          创建时间
        </button>
      </div>
      <div
        v-for="bookmark in sortedBookmarks"
        :key="bookmark.id"
        @click="handleBookmarkClick(bookmark)"
        class="p-2 mb-1 border rounded cursor-pointer hover:bg-gray-50"
      >
        <div class="text-xs text-gray-500">
          {{ getBookmarkChapter(bookmark).title }}
        </div>
        <div class="text-sm">{{ getBookmarkPreview(bookmark) }}</div>
        <button @click.stop="handleRemoveBookmark(bookmark)" class="text-xs text-red-500">
          删除
        </button>
      </div>
    </div>
  </slot>
</template>
