<template>
  <ReaderProvider>
    <ReaderLayout>
      <!-- 左侧导航栏 -->
      <template #navigation>
        <ReaderNavigation />
      </template>

      <!-- 主内容区 -->
      <template #content>
        <ReaderContent />
      </template>

      <!-- 右侧控制面板 -->
      <template #controls>
        <ReaderControls />
      </template>

      <!-- 移动端底部栏 -->
      <template #mobile-bottom>
        <MobileBottomBar />
      </template>

      <!-- 移动端抽屉 -->
      <template #mobile-drawer>
        <MobileDrawer />
      </template>
    </ReaderLayout>
  </ReaderProvider>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useReaderStore } from '@/stores/reader'
import ReaderProvider from '@/components/Reader/ReaderProvider.vue'
import ReaderLayout from '@/components/Reader/ReaderLayout.vue'
import ReaderNavigation from '@/components/Reader/ReaderNavigation.vue'
import ReaderContent from '@/components/Reader/ReaderContent.vue'
import ReaderControls from '@/components/Reader/ReaderControls.vue'
import MobileBottomBar from '@/components/Reader/MobileBottomBar.vue'
import MobileDrawer from '@/components/Reader/MobileDrawer.vue'

const route = useRoute()
const store = useReaderStore()

// 初始化阅读器
onMounted(async () => {
  const fileId = parseInt(route.params.id as string)
  if (fileId) {
    store.fileId = fileId
    await store.loadChapters()
  }
})
</script>