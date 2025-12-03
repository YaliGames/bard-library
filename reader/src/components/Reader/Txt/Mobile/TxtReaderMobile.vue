<template>
  <div>
    <MobileSearchDrawer
      :ref="readerContext.mobileSearchDrawerRef"
      :visible="readerContext.mobileSearchVisible"
      :current-chapter-content="readerContext.content.value"
      :current-chapter-index="readerContext.currentChapterIndex.value"
      :chapters="readerContext.chapters.value"
      :sentences="readerContext.sentences.value"
      @close="closeSearch"
      @jump-to-result="$emit('jump-to-result', $event)"
      @search-chapter="onSearchChapter"
      @search-global="onSearchGlobal"
    />

    <slot />

    <MobileBottomBar
      :visible="readerContext.mobileBottomBarVisible"
      :cached="!!readerContext.cachedBook.value"
      :has-prev="hasPrev"
      :has-next="hasNext"
      @prev="$emit('prev')"
      @menu="openDrawer('chapters')"
      @next="$emit('next')"
      @search="toggleSearch"
      @settings="openDrawer('settings')"
    />

    <MobileDrawer />
  </div>
</template>

<script setup lang="ts">
import { inject, computed } from 'vue'
import type { ReaderContext } from '@/types/readerContext'
import MobileSearchDrawer from '@/components/Reader/Txt/Mobile/MobileSearchDrawer.vue'
import MobileBottomBar from '@/components/Reader/Txt/Mobile/MobileBottomBar.vue'
import MobileDrawer from '@/components/Reader/Txt/Mobile/MobileDrawer.vue'
import type { SearchResult } from '@/types/reader'

const readerContext = inject<ReaderContext>('readerContext')!

const emit = defineEmits<{
  (e: 'jump-to-result', result: SearchResult): void
  (e: 'search-chapter', k: string, cs: boolean, ww: boolean): void
  (e: 'search-global', k: string, cs: boolean, ww: boolean): void
  (e: 'prev'): void
  (e: 'next'): void
}>()

const hasPrev = computed(
  () =>
    typeof readerContext.currentChapterIndex.value === 'number' &&
    readerContext.currentChapterIndex.value > 0,
)
const hasNext = computed(
  () =>
    typeof readerContext.currentChapterIndex.value === 'number' &&
    Array.isArray(readerContext.chapters.value) &&
    readerContext.currentChapterIndex.value < readerContext.chapters.value.length - 1,
)

function closeSearch() {
  readerContext.handleMobileSearchClose && readerContext.handleMobileSearchClose()
}

function toggleSearch() {
  readerContext.toggleSearch && readerContext.toggleSearch()
}

function openDrawer(tab?: 'chapters' | 'bookmarks' | 'cache' | 'settings') {
  readerContext.openMobileDrawer && readerContext.openMobileDrawer(tab)
}

function onSearchChapter(k: string, cs: boolean, ww: boolean) {
  emit('search-chapter', k, cs, ww)
}

function onSearchGlobal(k: string, cs: boolean, ww: boolean) {
  emit('search-global', k, cs, ww)
}
</script>
