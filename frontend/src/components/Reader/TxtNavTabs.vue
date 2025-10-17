<template>
  <el-tabs v-model="innerTab" stretch class="!mb-1">
    <el-tab-pane label="章节" name="chapters">
      <el-empty v-if="chapters.length===0" description="当前文件暂无目录"></el-empty>
      <div ref="chaptersScrollRef" class="max-h-[60vh] overflow-auto space-y-1">
        <div
          v-for="c in chapters"
          :key="c.index"
          :data-chapter-index="c.index"
          @click="emit('open-chapter', c.index)"
          :class="[
            'px-2 py-1 rounded-md cursor-pointer transition flex items-center gap-2 hover:bg-gray-200',
            currentChapterIndex===c.index ? 'bg-primary/10 text-primary font-semibold' : ''
          ]"
        >
          <span :class="['text-xs text-gray-500', currentChapterIndex===c.index ? 'text-primary' : '']" >#{{ c.index }}</span>
          <span class="truncate">{{ c.title || '(无标题)' }}</span>
        </div>
      </div>
    </el-tab-pane>
    <el-tab-pane label="书签" name="bookmarks">
      <el-empty v-if="!isLoggedIn" description="登录后可查看书签"></el-empty>
      <el-empty v-else-if="filteredBookmarks.length===0" description="当前文件暂无书签"></el-empty>
      <div class="max-h-[60vh] overflow-auto space-y-1">
        <div
          v-for="b in filteredBookmarks"
          :key="b.id"
          @click="emit('jump', b)"
          class="px-2 py-1 rounded-md cursor-pointer transition flex items-center gap-2 hover:bg-gray-200"
        >
          <span class="text-xs text-gray-500">{{ bookmarkLabel(b, true) }}</span>
          <span class="line-clamp-2">{{ bookmarkLabel(b) }}</span>
          <el-button @click.stop="emit('remove', b)" type="danger" circle text>
            <template #icon><span class="material-symbols-outlined text-sm">delete</span></template>
          </el-button>
        </div>
      </div>
    </el-tab-pane>
    <el-tab-pane label="书签" name="bookmarks_old" v-if="false">
      <div v-if="!isLoggedIn" class="text-gray-500 text-sm">登录后可查看书签</div>
      <div v-else-if="filteredBookmarks.length===0" class="text-gray-500 text-sm">当前文件暂无书签</div>
        <div class="max-h-[60vh] overflow-auto flex flex-col gap-1">
          <div v-for="b in filteredBookmarks" :key="b.id" class="group flex items-start gap-2 p-2 rounded-md border hover:bg-gray-50">
            <button @click="emit('jump', b)" class="text-xs px-2 py-1 rounded bg-primary/10 text-primary hover:bg-primary/15">跳转</button>
            <div class="flex-1 min-w-0 whitespace-nowrap overflow-hidden text-ellipsis">
              <div class="text-[13px] truncate">{{ bookmarkLabel(b) }}</div>
            </div>
            <button @click="emit('remove', b)" title="删除书签" class="text-xs text-red-500 hover:text-red-600">删除</button>
          </div>
        </div>
    </el-tab-pane>
  </el-tabs>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import type { Bookmark } from '@/api/types'

type TabKey = 'chapters'|'bookmarks'

interface Chapter { index:number; title?:string|null }

const props = defineProps<{ 
  tab: TabKey,
  chapters: Chapter[],
  currentChapterIndex: number | null,
  isLoggedIn: boolean,
  filteredBookmarks: Bookmark[],
  autoScrollCategory?: boolean,
}>()

const emit = defineEmits<{
  (e:'update:tab', v: TabKey): void
  (e:'open-chapter', index: number): void
  (e:'jump', b: Bookmark): void
  (e:'remove', b: Bookmark): void
}>()

const innerTab = computed({
  get: () => props.tab,
  set: (v: TabKey) => emit('update:tab', v)
})

function bookmarkLabel(b: Bookmark, showChap?: boolean): string {
  try {
    const loc = JSON.parse(b.location || '{}')
    const text = loc?.selectionText || ''
    let chap = ''
    if (typeof loc?.absStart === 'number' && props.chapters.length > 0) {
      const abs = Number(loc.absStart)
      let idx = 0
      for (let i = 0; i < props.chapters.length; i++) {
        const ch = props.chapters[i]
        if (abs >= (ch as any).offset && abs < (ch as any).offset + (ch as any).length) { idx = i; break }
      }
      chap = `#${idx}`
    } else if (typeof loc?.chapterIndex === 'number') {
      chap = `#${loc.chapterIndex}`
    }
    if (showChap) return `${chap}`.trim()
    return `${text}`.trim()
  } catch { return '' }
}

const chaptersScrollRef = ref<HTMLElement | null>(null)

// 将当前章节在目录容器中对齐到顶部
function scrollActiveChapterToTop(smooth = true) {
  try {
    if (props.autoScrollCategory === false) return
    if (innerTab.value !== 'chapters') return
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
  } catch { /* noop */ }
}

watch(() => props.currentChapterIndex, async () => {
  await nextTick()
  requestAnimationFrame(() => requestAnimationFrame(() => scrollActiveChapterToTop(true)))
})

watch(() => innerTab.value, async (tab) => {
  if (tab === 'chapters') {
    await nextTick()
    requestAnimationFrame(() => scrollActiveChapterToTop(true))
  }
})

onMounted(() => {
  requestAnimationFrame(() => scrollActiveChapterToTop(false))
})
</script>

<style scoped>
</style>
