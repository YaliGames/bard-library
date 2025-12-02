<template>
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
            'px-2 py-1 rounded-md cursor-pointer transition flex items-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-700',
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
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue'
import ChapterCore from '@/components/Reader/Txt/Core/ChapterCore.vue'

interface Chapter {
  index: number
  title?: string | null
  offset?: number
  length?: number
}

const props = defineProps<{
  chapters: Chapter[]
  currentChapterIndex: number | null
  autoScroll?: boolean
}>()

const emit = defineEmits<{
  (e: 'open-chapter', index: number): void
}>()

const chaptersScrollRef = ref<HTMLElement | null>(null)

// 将当前章节在目录容器中对齐到顶部
function scrollActiveChapterToTop(smooth = true) {
  try {
    if (props.autoScroll === false) return
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

onMounted(() => {
  requestAnimationFrame(() => scrollActiveChapterToTop(false))
})

// Expose scroll method if parent needs to trigger it manually (e.g. when tab becomes active)
defineExpose({ scrollActiveChapterToTop })
</script>
