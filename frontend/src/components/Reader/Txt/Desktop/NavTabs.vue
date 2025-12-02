<template>
  <el-tabs v-model="innerTab" stretch>
    <el-tab-pane label="章节" name="chapters">
      <ChapterList
        ref="chapterListRef"
        :chapters="chapters"
        :current-chapter-index="currentChapterIndex"
        :auto-scroll="autoScrollCategory"
        @open-chapter="emit('open-chapter', $event)"
      />
    </el-tab-pane>

    <el-tab-pane label="书签" name="bookmarks">
      <BookmarkList
        :bookmarks="filteredBookmarks"
        :chapters="chapters"
        :is-logged-in="isLoggedIn"
        @jump="emit('jump', $event)"
        @remove="emit('remove', $event)"
      />
    </el-tab-pane>
  </el-tabs>
</template>

<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import ChapterList from './ChapterList.vue'
import BookmarkList from './BookmarkList.vue'
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

const chapterListRef = ref<InstanceType<typeof ChapterList> | null>(null)

watch(
  () => innerTab.value,
  async tab => {
    if (tab === 'chapters') {
      await nextTick()
      chapterListRef.value?.scrollActiveChapterToTop(true)
    }
  },
)
</script>

