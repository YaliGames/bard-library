<template>
  <div class="reader-provider">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { provide, readonly } from 'vue'
import { useReaderStore } from '@/stores/reader'

// 创建store实例
const store = useReaderStore()

// 提供store的只读版本和常用方法
provide('readerStore', readonly(store))
provide('readerActions', {
  // 章节导航
  openChapter: store.openChapter,
  goPrevChapter: store.goPrevChapter,
  goNextChapter: store.goNextChapter,

  // UI控制
  toggleSearch: store.toggleSearch,
  toggleSettings: store.toggleSettings,
  openMobileDrawer: store.openMobileDrawer,
  closeMobileDrawer: store.closeMobileDrawer,

  // 数据加载
  loadChapters: store.loadChapters,
  loadBookmarks: store.loadBookmarks,
})
</script>