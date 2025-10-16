<template>
  <div :class="wrapperClass" :style="wrapperStyle">
    <img v-if="fileId" :src="coverUrl(fileId)" :alt="alt" class="w-full h-full object-cover" />
    <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
      <span class="material-symbols-outlined" :style="{ fontSize: iconSize }">book_2</span>
    </div>
    <div class="absolute top-2 right-2 flex gap-1 flex-wrap justify-end">
      <slot name="overlay"></slot>
    </div>
  </div>
  
</template>
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  fileId?: number | null
  alt?: string
  aspect?: string // e.g. '3/4'
  rounded?: boolean
  class?: string
  iconSize?: string
}>()

// 默认 3:4（宽:高）
const aspect = computed(() => props.aspect || '3/4')
const wrapperClass = computed(() => [
  `relative w-full overflow-hidden bg-gray-100 select-none`,
  props.rounded !== false ? 'rounded' : '',
  props.class || '',
].join(' ').trim())

const wrapperStyle = computed(() => {
  const ratio = String(aspect.value).replace('/', ' / ')
  return { aspectRatio: ratio }
})

function coverUrl(fileId: number) { return `/api/v1/files/${fileId}/preview` }

const iconSize = computed(() => props.iconSize || '48px')
</script>

<style scoped>
.material-symbols-outlined { line-height: 1; }
</style>
