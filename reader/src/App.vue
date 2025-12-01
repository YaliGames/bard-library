<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useOfflineStore } from '@/stores/offline'
import { onMounted, onUnmounted } from 'vue'

const offlineStore = useOfflineStore()

onMounted(() => {
  offlineStore.init()
})

onUnmounted(() => {
  offlineStore.cleanup()
})
</script>

<template>
  <div
    v-if="offlineStore.isOffline"
    class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 px-4 py-2 text-center text-sm font-medium border-b border-yellow-100 dark:border-yellow-900/50"
  >
    <span class="flex items-center justify-center gap-2">
      <span class="material-symbols-outlined text-base">wifi_off</span>
      当前处于离线模式，仅可访问已缓存的内容
    </span>
  </div>
  <RouterView />
</template>

<style scoped>
</style>
