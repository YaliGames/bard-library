<template>
  <div
    ref="containerRef"
    class="txt-content relative select-text"
    @mouseup="handleMouseUp"
    @touchend="handleMouseUp"
  >
    <!-- 渲染文本段落 -->
    <span
      v-for="(sentence, index) in sentences"
      :key="index"
      :data-index="index"
      v-html="renderParagraph(sentence, index)"
      @click="handleParagraphClick($event, index)"
    ></span>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

interface MarkRange {
  start: number
  end: number
  id: string | number
  color?: string
  note?: string
  type?: 'bookmark' | 'search'
}

interface Props {
  content: string
  sentences: string[]
  markRanges?: MarkRange[] // 书签/高亮范围
  markTick?: number // 用于强制刷新高亮
  searchHighlight?: {
    keyword: string
    caseSensitive: boolean
    wholeWord: boolean
    currentResultIndex?: number
    results?: any[]
  } | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'selection', range: { start: number; end: number; text: string; rect: DOMRect } | null): void
  (e: 'mark-click', markId: string | number, rect: DOMRect): void
}>()

const containerRef = ref<HTMLElement | null>(null)

// 渲染段落，处理高亮
function renderParagraph(text: string, index: number): string {
  // 计算该段落的起始位置
  let offset = 0
  for (let i = 0; i < index; i++) {
    offset += props.sentences[i].length
  }
  const end = offset + text.length

  // 收集该段落内的所有高亮
  const highlights: { start: number; end: number; color: string; type: string; id?: any }[] = []

  // 1. 书签高亮
  if (props.markRanges) {
    for (const mark of props.markRanges) {
      // 判断是否有重叠
      const startIn = mark.start >= offset && mark.start < end
      const endIn = mark.end > offset && mark.end <= end
      const cover = mark.start <= offset && mark.end >= end

      if (startIn || endIn || cover) {
        const s = Math.max(mark.start, offset) - offset
        const e = Math.min(mark.end, end) - offset
        highlights.push({
          start: s,
          end: e,
          color: mark.color || '#ffeb3b',
          type: 'bookmark',
          id: mark.id,
        })
      }
    }
  }

  // 2. 搜索高亮
  if (props.searchHighlight && props.searchHighlight.keyword) {
    const { keyword, caseSensitive } = props.searchHighlight
    const flags = caseSensitive ? 'g' : 'gi'
    const regex = new RegExp(escapeRegExp(keyword), flags)
    let match
    while ((match = regex.exec(text)) !== null) {
      highlights.push({
        start: match.index,
        end: match.index + match[0].length,
        color: '#ff9800', // 搜索高亮颜色
        type: 'search',
      })
    }
  }

  // 如果没有高亮，直接返回转义后的文本
  if (highlights.length === 0) {
    return escapeHtml(text)
  }

  // 排序并处理重叠（简单处理：后来的覆盖前面的，或者合并）
  // 这里为了简单，先按起始位置排序
  highlights.sort((a, b) => a.start - b.start)

  // 构建HTML
  let html = ''
  let cursor = 0

  for (const h of highlights) {
    if (h.start > cursor) {
      html += escapeHtml(text.substring(cursor, h.start))
    }
    if (h.end > cursor) {
      // 截取高亮文本
      const s = Math.max(h.start, cursor)
      const e = h.end
      const sub = text.substring(s, e)
      
      if (h.type === 'bookmark') {
        html += `<span class="highlight bookmark-highlight" data-mark-id="${h.id}" style="background-color: ${h.color}40; border-bottom: 2px solid ${h.color}">${escapeHtml(sub)}</span>`
      } else {
        html += `<span class="highlight search-highlight" style="background-color: ${h.color}60">${escapeHtml(sub)}</span>`
      }
      cursor = e
    }
  }

  if (cursor < text.length) {
    html += escapeHtml(text.substring(cursor))
  }

  return html
}

function escapeRegExp(string: string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

function escapeHtml(unsafe: string) {
  return unsafe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

// 处理文本选择
function handleMouseUp() {
  const selection = window.getSelection()
  if (!selection || selection.isCollapsed) {
    emit('selection', null)
    return
  }

  const range = selection.getRangeAt(0)
  const text = selection.toString()

  // 计算绝对位置
  // 这是一个简化的计算，实际可能需要更复杂的逻辑来处理跨段落选择
  // 这里假设选择是在 containerRef 内
  if (!containerRef.value?.contains(range.commonAncestorContainer)) {
    return
  }

  // 获取选区相对于全文的偏移量
  // 这是一个难点，因为DOM结构和原始文本可能有差异
  // 简单起见，我们尝试获取选区所在的段落
  let startNode = range.startContainer
  if (startNode.nodeType === Node.TEXT_NODE) startNode = startNode.parentNode!
  
  // 向上查找直到找到 p.paragraph
  let pNode = startNode as HTMLElement
  while (pNode && !pNode.classList?.contains('paragraph') && pNode !== containerRef.value) {
    pNode = pNode.parentElement as HTMLElement
  }

  if (pNode && pNode.classList?.contains('paragraph')) {
    const pIndex = parseInt(pNode.dataset.index || '0')
    // 计算段落之前的偏移
    let baseOffset = 0
    for (let i = 0; i < pIndex; i++) {
      baseOffset += props.sentences[i].length
    }
    
    // 计算段落内的偏移
    // 这里需要精确计算，因为可能有高亮标签
    // 简单实现：获取选区前的文本长度
    const preRange = document.createRange()
    preRange.selectNodeContents(pNode)
    preRange.setEnd(range.startContainer, range.startOffset)
    const startInP = preRange.toString().length
    
    const absStart = baseOffset + startInP
    const absEnd = absStart + text.length

    const rect = range.getBoundingClientRect()
    emit('selection', { start: absStart, end: absEnd, text, rect })
  } else {
    // 跨段落选择，暂时不支持或简单处理
    emit('selection', null)
  }
}

// 处理段落点击（用于点击高亮）
function handleParagraphClick(event: MouseEvent, index: number) {
  const target = event.target as HTMLElement
  if (target.classList.contains('bookmark-highlight')) {
    const markId = target.dataset.markId
    if (markId) {
      const rect = target.getBoundingClientRect()
      emit('mark-click', markId, rect)
      event.stopPropagation()
    }
  }
}

// 滚动到指定位置
function scrollToTarget(target: number | string) {
  nextTick(() => {
    if (!containerRef.value) return

    if (typeof target === 'number') {
      // 滚动到字符位置
      // 找到对应的段落
      let offset = 0
      for (let i = 0; i < props.sentences.length; i++) {
        const len = props.sentences[i].length
        if (target >= offset && target < offset + len) {
          const p = containerRef.value.children[i] as HTMLElement
          if (p) {
            p.scrollIntoView({ behavior: 'smooth', block: 'center' })
          }
          break
        }
        offset += len
      }
    } else {
      // 滚动到文本（搜索结果）
      // 暂时未实现
    }
  })
}

// 监听 markTick 强制重绘
watch(() => props.markTick, () => {
  // Vue 的响应式系统会自动处理，只要 props.markRanges 变化
  // 如果 markTick 是为了强制刷新，可能需要 key hack 或者其他方式
  // 这里假设 markRanges 变化会触发重绘
})

defineExpose({
  scrollToTarget
})
</script>

<style scoped>
.txt-content {
  font-size: var(--reader-font-size, 16px);
  line-height: var(--reader-line-height, 1.6);
  color: var(--reader-fg, #333);
  background-color: var(--reader-bg, #fff);
  word-wrap: break-word;
  white-space: pre-wrap;
  padding: 12px 16px;
}

:deep(.highlight) {
  border-radius: 2px;
  cursor: pointer;
}
</style>
