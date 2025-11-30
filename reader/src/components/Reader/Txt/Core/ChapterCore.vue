<script setup lang="ts">
import { computed } from 'vue'

interface Chapter {
  title?: string | null
  index: number
  offset?: number
  length?: number
}

interface Props {
  chapters: Chapter[]
  currentChapterIndex: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'open-chapter': [index: number]
}>()

// 计算属性
const hasChapters = computed(() => props.chapters.length > 0)

// 方法
function handleChapterClick(index: number) {
  emit('open-chapter', index)
}

function isCurrentChapter(index: number): boolean {
  return props.currentChapterIndex === index
}

// 暴露给父组件
defineExpose({
  hasChapters,
  handleChapterClick,
  isCurrentChapter,
})
</script>

<template>
  <slot
    :chapters="chapters"
    :current-chapter-index="currentChapterIndex"
    :has-chapters="hasChapters"
    :handle-chapter-click="handleChapterClick"
    :is-current-chapter="isCurrentChapter"
  >
    <!-- 默认UI -->
    <div v-if="!hasChapters" class="text-center py-8 text-gray-400">
      <div class="text-sm">暂无章节</div>
    </div>
    <div v-else>
      <div
        v-for="(chapter, index) in chapters"
        :key="index"
        @click="handleChapterClick(index)"
        :class="{ active: isCurrentChapter(index) }"
        class="cursor-pointer"
      >
        {{ chapter.title || `第 ${index + 1} 章` }}
      </div>
    </div>
  </slot>
</template>
