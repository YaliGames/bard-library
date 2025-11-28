<template>
  <div
    ref="contentEl"
    class="txt-content-renderer select-text"
    @mouseup="handleMouseUp"
    @touchend="handleTouchEnd"
  >
    <div
      v-for="(sentence, index) in sentences"
      :key="index"
      class="sentence"
      :data-sentence-index="index"
      @click="handleSentenceClick($event, index)"
    >
      <span
        v-for="(segment, segIndex) in getSentenceSegments(index)"
        :key="segIndex"
        :class="segment.class"
        :style="segment.style"
        v-html="segment.html"
      ></span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import type { HighlightRange } from '@/stores/reader'

// Props
interface Props {
  content: string
  sentences: string[]
  markRanges: Map<number, HighlightRange[]>
  searchHighlight?: {
    keyword: string
    caseSensitive: boolean
    wholeWord: boolean
  } | null
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  selection: [event: SelectionEvent]
  markClick: [event: MarkClickEvent]
}>()

// Types
interface SelectionEvent {
  show: boolean
  x?: number
  y?: number
  range?: { start: number; end: number } | null
  text?: string | null
}

interface MarkClickEvent {
  show: boolean
  x?: number
  y?: number
  bookmarkId?: number | null
}

interface Segment {
  html: string
  class: string
  style?: Record<string, string>
}

// Refs
const contentEl = ref<HTMLElement>()

// 计算句子片段（包含高亮和搜索结果）
const getSentenceSegments = (sentenceIndex: number): Segment[] => {
  const sentence = props.sentences[sentenceIndex]
  if (!sentence) return [{ html: '', class: 'text-segment' }]

  const ranges = props.markRanges.get(sentenceIndex) || []
  const searchConfig = props.searchHighlight

  // 如果没有高亮或搜索，直接返回原文
  if (ranges.length === 0 && !searchConfig) {
    return [{
      html: escapeHtml(sentence),
      class: 'text-segment'
    }]
  }

  // 合并所有范围（书签高亮和搜索高亮）
  const allRanges = [...ranges]
  if (searchConfig) {
    // 添加搜索结果范围
    const searchRanges = findSearchRanges(sentence, searchConfig)
    allRanges.push(...searchRanges.map(r => ({ ...r, isSearch: true })))
  }

  // 排序范围
  allRanges.sort((a, b) => a.start - b.start)

  // 合并重叠范围
  const mergedRanges = mergeRanges(allRanges)

  // 生成片段
  const segments: Segment[] = []
  let lastEnd = 0

  for (const range of mergedRanges) {
    // 添加范围前的普通文本
    if (range.start > lastEnd) {
      segments.push({
        html: escapeHtml(sentence.slice(lastEnd, range.start)),
        class: 'text-segment'
      })
    }

    // 添加高亮文本
    const highlightedText = sentence.slice(range.start, range.end)
    segments.push({
      html: escapeHtml(highlightedText),
      class: range.isSearch ? 'search-highlight' : 'bookmark-highlight',
      style: range.color ? { backgroundColor: range.color } : undefined
    })

    lastEnd = range.end
  }

  // 添加剩余的普通文本
  if (lastEnd < sentence.length) {
    segments.push({
      html: escapeHtml(sentence.slice(lastEnd)),
      class: 'text-segment'
    })
  }

  return segments
}

// 查找搜索范围
const findSearchRanges = (text: string, config: NonNullable<Props['searchHighlight']>): HighlightRange[] => {
  const ranges: HighlightRange[] = []
  const { keyword, caseSensitive, wholeWord } = config

  if (!keyword) return ranges

  // 构建正则表达式
  let pattern = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  if (wholeWord) {
    pattern = `\\b${pattern}\\b`
  }

  const flags = caseSensitive ? 'g' : 'gi'
  const regex = new RegExp(pattern, flags)

  let match
  while ((match = regex.exec(text)) !== null) {
    ranges.push({
      start: match.index,
      end: match.index + match[0].length,
      isSearch: true
    })
  }

  return ranges
}

// 合并重叠范围
const mergeRanges = (ranges: HighlightRange[]): HighlightRange[] => {
  if (ranges.length === 0) return []

  const sorted = [...ranges].sort((a, b) => a.start - b.start)
  const merged: HighlightRange[] = []

  let current = sorted[0]
  for (let i = 1; i < sorted.length; i++) {
    const next = sorted[i]

    if (next.start <= current.end) {
      // 范围重叠，合并
      current.end = Math.max(current.end, next.end)
      // 搜索高亮优先级更高
      if (next.isSearch) {
        current.isSearch = true
        current.color = undefined // 搜索高亮没有颜色
      }
    } else {
      merged.push(current)
      current = next
    }
  }

  merged.push(current)
  return merged
}

// HTML转义
const escapeHtml = (text: string): string => {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

// 事件处理
const handleMouseUp = (event: MouseEvent) => {
  handleSelection(event.clientX, event.clientY)
}

const handleTouchEnd = (event: TouchEvent) => {
  const touch = event.changedTouches[0]
  if (touch) {
    handleSelection(touch.clientX, touch.clientY)
  }
}

const handleSelection = (x: number, y: number) => {
  const selection = window.getSelection()
  if (!selection || selection.rangeCount === 0) {
    emit('selection', { show: false })
    return
  }

  const range = selection.getRangeAt(0)
  if (range.collapsed) {
    emit('selection', { show: false })
    return
  }

  // 计算选中的句子范围
  const sentenceRange = getSelectedSentenceRange(range)
  if (!sentenceRange) {
    emit('selection', { show: false })
    return
  }

  const selectedText = selection.toString()
  emit('selection', {
    show: true,
    x,
    y,
    range: sentenceRange,
    text: selectedText
  })
}

const handleSentenceClick = (event: MouseEvent, sentenceIndex: number) => {
  // 检查是否点击了高亮区域
  const target = event.target as HTMLElement
  if (target.classList.contains('bookmark-highlight')) {
    // 找到对应的书签
    const ranges = props.markRanges.get(sentenceIndex) || []
    const rect = target.getBoundingClientRect()

    // 简单地找到第一个匹配的书签（可以根据点击位置优化）
    const bookmarkRange = ranges.find(r => r.bookmarkId)
    if (bookmarkRange) {
      emit('markClick', {
        show: true,
        x: rect.left + rect.width / 2,
        y: rect.top,
        bookmarkId: bookmarkRange.bookmarkId
      })
      return
    }
  }

  // 隐藏菜单
  emit('markClick', { show: false })
}

// 获取选中的句子范围
const getSelectedSentenceRange = (range: Range): { start: number; end: number } | null => {
  const startSentenceEl = range.startContainer.parentElement?.closest('.sentence')
  const endSentenceEl = range.endContainer.parentElement?.closest('.sentence')

  if (!startSentenceEl || !endSentenceEl) return null

  const startIndex = parseInt(startSentenceEl.getAttribute('data-sentence-index') || '0')
  const endIndex = parseInt(endSentenceEl.getAttribute('data-sentence-index') || '0')

  return {
    start: startIndex,
    end: endIndex + 1 // 包含结束句子
  }
}
</script>

<style scoped>
.txt-content-renderer {
  @apply leading-relaxed;
}

.sentence {
  @apply mb-2;
}

.text-segment {
  @apply text-current;
}

.bookmark-highlight {
  @apply bg-yellow-200 dark:bg-yellow-800 rounded px-0.5 cursor-pointer;
}

.search-highlight {
  @apply bg-blue-200 dark:bg-blue-800 rounded px-0.5;
}

.select-text {
  -webkit-user-select: text;
  -moz-user-select: text;
  -ms-user-select: text;
  user-select: text;
}
</style>