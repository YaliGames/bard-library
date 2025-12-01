<template>
  <div class="border border-gray-200 dark:border-gray-700 md:rounded-lg shadow-sm" :style="cssVars">
    <template v-if="loading">
      <div style="max-width: var(--reader-content-width); margin: 0 auto; padding: 12px 16px">
        <el-skeleton :rows="8" animated />
      </div>
    </template>
    <template v-else-if="!content">
      <div style="max-width: var(--reader-content-width); margin: 0 auto; padding: 24px 16px">
        <el-empty description="选择章节开始阅读"></el-empty>
      </div>
    </template>
    <template v-else>
      <div class="max-w-[var(--reader-content-width)] mx-auto px-4 pb-20">
        <TxtContent
          :ref="readerContext.contentRef"
          :content="content"
          :sentences="sentences"
          :mark-ranges="readerContext.markRanges"
          :mark-tick="markTick"
          :search-highlight="searchHighlight"
          @selection="readerContext.onSelectionEvent"
          @mark-click="readerContext.onMarkClickEvent"
        />

        <SelectionMenu
          :show="showSelectionMenu"
          :x="selectionMenuPos.x"
          :y="selectionMenuPos.y"
          :actions="selectionActions"
        />

        <HighlightMenu
          :show="showHighlightMenu"
          :x="highlightMenuPos.x"
          :y="highlightMenuPos.y"
          :note="currentHitNote"
          :current-color="currentHitColor"
          @add-note="readerContext.onAddNote"
          @pick-color="readerContext.onPickColor"
          @delete="readerContext.onDeleteFromMenu"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { inject, computed } from 'vue'
import type { ReaderContext } from '@/types/reader'
import TxtContent from '@/components/Reader/Txt/Shared/TxtContent.vue'
import SelectionMenu from '@/components/Reader/Txt/Shared/SelectionMenu.vue'
import HighlightMenu from '@/components/Reader/Txt/Shared/HighlightMenu.vue'

const readerContext = inject<ReaderContext>('readerContext')!

const loading = computed(() => Boolean((readerContext.loading as any)?.value))
const content = computed(() => (readerContext.content as any).value)
const sentences = computed(() => (readerContext.sentences as any).value)
const markTick = computed(() => (readerContext.markTick as any).value)
const searchHighlight = computed(() => (readerContext.searchHighlight as any).value)
const showSelectionMenu = computed(() => (readerContext.showSelectionMenu as any).value)
const selectionMenuPos = computed(() => (readerContext.selectionMenuPos as any).value)
const selectionActions = computed(() => (readerContext.selectionActions as any).value)
const showHighlightMenu = computed(() => (readerContext.showHighlightMenu as any).value)
const highlightMenuPos = computed(() => (readerContext.highlightMenuPos as any).value)
const currentHitNote = computed(() => (readerContext.currentHitNote as any).value)
const currentHitColor = computed(() => (readerContext.currentHitColor as any).value)

const settings = readerContext.settings
const themeColors = (readerContext as any).themeColors || { light: { bg: '#fff', fg: '#333' } }

const cssVars = computed(() => {
  const theme = (settings as any).value?.theme ?? 'light'
  const themeObj = themeColors[theme] ?? { bg: '#ffffff', fg: '#333333' }
  return {
    '--reader-font-size': ((settings as any).value?.fontSize ?? 16) + 'px',
    '--reader-line-height': String((settings as any).value?.lineHeight ?? 1.7),
    '--reader-content-width': ((settings as any).value?.contentWidth ?? 720) + 'px',
    '--reader-bg': themeObj.bg,
    '--reader-fg': themeObj.fg,
    background: 'var(--reader-bg)',
    color: 'var(--reader-fg)',
    padding: '8px 0',
  }
})
</script>

<style scoped></style>
