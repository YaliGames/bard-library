<template>
  <article
    ref="articleEl"
    :key="'article-' + markTick"
    @mouseup="onArticleMouseUp"
    @click="onArticleClick"
    class="reader-article"
    style="
      white-space: pre-wrap;
      line-height: var(--reader-line-height);
      font-size: var(--reader-font-size);
      max-width: var(--reader-content-width);
      margin: 0 auto;
      padding: 12px 16px;
    "
  >
    <template v-if="sentences.length">
      <span v-for="(s, i) in sentences" :key="i" :data-sid="i" v-html="getSentenceHtml(i, s)" />
    </template>
    <template v-else>
      {{ content }}
    </template>
  </article>
</template>
<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { escapeHtml, clampRanges, mergeRanges, buildSentenceOffsets } from '@/utils/reader'

// Props & Emits
const props = defineProps<{
  content: string
  sentences: string[]
  markRanges: Map<
    number,
    Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>
  >
  markTick: number
  searchHighlight?: {
    keyword: string
    caseSensitive: boolean
    wholeWord: boolean
  } | null // 新增:搜索关键词高亮配置
}>()

const emit = defineEmits<{
  (
    e: 'selection',
    payload: {
      show: boolean
      x?: number
      y?: number
      range?: { start: number; end: number } | null
      text?: string | null
    },
  ): void
  (
    e: 'mark-click',
    payload: { show: boolean; x?: number; y?: number; bookmarkId?: number | null },
  ): void
}>()

// DOM refs
const articleEl = ref<HTMLElement | null>(null)

// Local state
const localSelectionRange = ref<{ start: number; end: number } | null>(null)
const selectionTextBuffer = ref<string | null>(null)

// Helpers from utils

function getSentenceHtml(i: number, s: string): string {
  const ranges = props.markRanges.get(i)
  
  // 处理搜索高亮
  let allRanges: Array<{ start: number; end: number; bookmarkId?: number; color?: string | null; isSearch?: boolean }> = []
  
  if (ranges && ranges.length > 0) {
    allRanges.push(...ranges)
  }
  
  // 添加搜索高亮范围
  if (props.searchHighlight) {
    try {
      const { keyword, caseSensitive, wholeWord } = props.searchHighlight
      // 构建正则表达式
      let pattern = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      if (wholeWord) {
        pattern = `\\b${pattern}\\b`
      }
      const flags = caseSensitive ? 'g' : 'gi'
      const regex = new RegExp(pattern, flags)
      
      let match
      while ((match = regex.exec(s)) !== null) {
        allRanges.push({
          start: match.index,
          end: match.index + match[0].length,
          isSearch: true,
          color: 'rgba(255,235,59,0.6)' // 黄色高亮
        })
      }
    } catch {}
  }
  
  if (allRanges.length === 0) return escapeHtml(s)
  
  const merged = mergeRanges(clampRanges(allRanges as any, s.length) as any) as Array<{
    start: number
    end: number
    bookmarkId?: number
    color?: string | null
    isSearch?: boolean
  }>
  
  let html = ''
  let pos = 0
  for (const r of merged) {
    if (r.start > pos) html += escapeHtml(s.slice(pos, r.start))
    const part = s.slice(r.start, r.end)
    const bg = r.color ? r.color : 'rgba(250,216,120,.9)'
    const className = r.isSearch ? 'search-hl-mark' : 'hl-mark'
    const bidAttr = r.bookmarkId ? `data-bid="${r.bookmarkId}"` : ''
    html += `<mark class="${className}" ${bidAttr} style="background:${bg};">${escapeHtml(part)}</mark>`
    pos = r.end
  }
  if (pos < s.length) html += escapeHtml(s.slice(pos))
  return html
}

function getClosestSentenceSpan(node: Node | null): HTMLElement | null {
  const el = (
    node && (node as HTMLElement).nodeType === 1
      ? (node as HTMLElement)
      : (node as any)?.parentElement
  ) as HTMLElement | null
  return el ? el.closest && (el.closest('span[data-sid]') as HTMLElement | null) : null
}

// Selection interactions

const selectionMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })

function onArticleMouseUp() {
  try {
    const sel = window.getSelection?.()
    if (!sel || sel.rangeCount === 0) return
    const text = sel.toString()
    if (!text || text.trim().length === 0) {
      selectionTextBuffer.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    const root = articleEl.value
    if (!root) return
    const range = sel.getRangeAt(0)
    const startNode = range.startContainer
    const endNode = range.endContainer
    if (!root.contains(startNode) || !root.contains(endNode)) {
      selectionTextBuffer.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    selectionTextBuffer.value = text
    const aSpan = getClosestSentenceSpan(sel.anchorNode)
    const fSpan = getClosestSentenceSpan(sel.focusNode)
    const aId = aSpan ? Number(aSpan.getAttribute('data-sid') || 'NaN') : NaN
    const fId = fSpan ? Number(fSpan.getAttribute('data-sid') || 'NaN') : NaN
    if (Number.isFinite(aId) && Number.isFinite(fId)) {
      const s = Math.min(aId, fId)
      const e = Math.max(aId, fId)
      localSelectionRange.value = { start: s, end: e }
    } else {
      localSelectionRange.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    updateSelectionMenuPositionFromDomSelection(sel as Selection)
    emit('selection', {
      show: true,
      x: selectionMenuPos.value.x,
      y: selectionMenuPos.value.y,
      range: localSelectionRange.value,
      text: selectionTextBuffer.value,
    })
  } catch {}
}

// Use native selectionchange to support mobile browsers and avoid intercepting touch/scroll
function onSelectionChange() {
  try {
    const root = articleEl.value
    if (!root) return
    const sel = document.getSelection?.()
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
      selectionTextBuffer.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    const text = sel.toString()
    if (!text || text.trim().length === 0) {
      selectionTextBuffer.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    const range = sel.getRangeAt(0)
    const startNode = range.startContainer
    const endNode = range.endContainer
    if (!root.contains(startNode) || !root.contains(endNode)) {
      selectionTextBuffer.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    selectionTextBuffer.value = text
    const aSpan = getClosestSentenceSpan(sel.anchorNode)
    const fSpan = getClosestSentenceSpan(sel.focusNode)
    const aId = aSpan ? Number(aSpan.getAttribute('data-sid') || 'NaN') : NaN
    const fId = fSpan ? Number(fSpan.getAttribute('data-sid') || 'NaN') : NaN
    if (Number.isFinite(aId) && Number.isFinite(fId)) {
      const s = Math.min(aId, fId)
      const e = Math.max(aId, fId)
      localSelectionRange.value = { start: s, end: e }
    } else {
      localSelectionRange.value = null
      emit('selection', { show: false, range: null, text: null })
      return
    }
    updateSelectionMenuPositionFromDomSelection(sel as Selection)
    emit('selection', {
      show: true,
      x: selectionMenuPos.value.x,
      y: selectionMenuPos.value.y,
      range: localSelectionRange.value,
      text: selectionTextBuffer.value,
    })
  } catch {}
}

function updateSelectionMenuPositionFromDomSelection(sel: Selection) {
  try {
    if (!sel.rangeCount) return
    const range = sel.getRangeAt(0)
    const rect = range.getBoundingClientRect()
    if (rect && Number.isFinite(rect.left) && Number.isFinite(rect.top)) {
      selectionMenuPos.value = { x: rect.left + rect.width / 2, y: Math.max(8, rect.top - 8) }
      return
    }
  } catch {}
  updateSelectionMenuPositionFromSentenceRange()
}

function updateSelectionMenuPositionFromSentenceRange() {
  try {
    if (!localSelectionRange.value) return
    const s = Math.min(localSelectionRange.value.start, localSelectionRange.value.end)
    const e = Math.max(localSelectionRange.value.start, localSelectionRange.value.end)
    const root = articleEl.value
    if (!root) return
    const first = root.querySelector(`span[data-sid="${s}"]`) as HTMLElement | null
    const last = root.querySelector(`span[data-sid="${e}"]`) as HTMLElement | null
    const r1 = first?.getBoundingClientRect()
    const r2 = last?.getBoundingClientRect()
    const top = Math.min(r1?.top ?? Infinity, r2?.top ?? Infinity)
    const left = Math.min(r1?.left ?? Infinity, r2?.left ?? Infinity)
    const right = Math.max(r1 ? r1.left + r1.width : -Infinity, r2 ? r2.left + r2.width : -Infinity)
    if (isFinite(top) && isFinite(left) && isFinite(right)) {
      selectionMenuPos.value = { x: (left + right) / 2, y: Math.max(8, top - 8) }
    }
  } catch {}
}

function onArticleClick(e: MouseEvent) {
  try {
    const target = e.target as HTMLElement | null
    if (!target) return
    const mark = target.closest && (target.closest('mark.hl-mark') as HTMLElement | null)
    if (!mark) return
    const rect = mark.getBoundingClientRect()
    const pos = { x: rect.left + rect.width / 2, y: Math.max(8, rect.top - 8) }
    const bidRaw = mark.getAttribute('data-bid')
    const bid = bidRaw && bidRaw.trim() ? Number(bidRaw) : null
    emit('mark-click', { show: bid != null, x: pos.x, y: pos.y, bookmarkId: bid })
  } catch {}
}

// Public methods for parent
// buildSentenceOffsets via utils

function flashElement(el: HTMLElement) {
  el.classList.add('ring-2', 'ring-primary', 'bg-yellow-100')
  setTimeout(() => {
    el.classList.remove('bg-yellow-100')
  }, 600)
  setTimeout(() => {
    el.classList.remove('ring-2', 'ring-primary')
  }, 900)
}

function flashMarksInRange(startSid: number, endSid: number): boolean {
  const root = articleEl.value
  if (!root) return false
  let found = false
  const s = Math.min(startSid, endSid)
  const e = Math.max(startSid, endSid)
  for (let i = s; i <= e; i++) {
    const marks = root.querySelectorAll(
      `span[data-sid="${i}"] mark.hl-mark`,
    ) as NodeListOf<HTMLElement>
    if (marks && marks.length) {
      found = true
      marks.forEach(m => {
        m.classList.add('ring-2', 'ring-primary')
        setTimeout(() => {
          m.classList.remove('ring-2', 'ring-primary')
        }, 900)
      })
    }
  }
  return found
}

function scrollToTarget(opts: { startSid?: number; endSid?: number; selectionText?: string; isSearchJump?: boolean }) {
  // Prefer precise by startSid
  if (typeof opts.startSid === 'number') {
    const el = articleEl.value?.querySelector(
      `span[data-sid="${opts.startSid}"]`,
    ) as HTMLElement | null
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      
      // 如果是搜索跳转，只闪烁搜索高亮的 mark
      if (opts.isSearchJump) {
        setTimeout(() => {
          try {
            const marks = el.querySelectorAll('mark.search-hl-mark') as NodeListOf<HTMLElement>
            // 闪烁所有搜索高亮的 mark,不再检查文本内容
            marks.forEach(mark => {
              mark.classList.add('ring-2', 'ring-primary', 'bg-yellow-200')
              setTimeout(() => {
                mark.classList.remove('ring-2', 'ring-primary', 'bg-yellow-200')
              }, 900)
            })
          } catch {}
        }, 100)
      } else {
        // 书签跳转，闪烁所有 mark
        const flashed = flashMarksInRange(
          opts.startSid,
          typeof opts.endSid === 'number' ? opts.endSid : opts.startSid,
        )
        if (!flashed) flashElement(el)
      }
      return
    }
  }
  // Fallback by selectionText: find first occurrence and highlight precisely
  if (opts.selectionText) {
    const text = props.content
    const sub = opts.selectionText
    // 精确查找，去除前后空白和换行符
    const trimmedSub = sub.trim()
    const idx = text.indexOf(trimmedSub)
    if (idx >= 0) {
      const offsets = buildSentenceOffsets(props.sentences)
      for (let i = 0; i < offsets.length; i++) {
        const seg = offsets[i]
        // 检查是否在这个句子范围内
        if (idx >= seg.start && idx < seg.end) {
          const el = articleEl.value?.querySelector(`span[data-sid="${i}"]`) as HTMLElement | null
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            // 查找并高亮具体的文本节点
            setTimeout(() => {
              try {
                if (opts.isSearchJump) {
                  // 搜索跳转：只闪烁搜索高亮的 mark
                  const marks = el.querySelectorAll('mark.search-hl-mark') as NodeListOf<HTMLElement>
                  marks.forEach(mark => {
                    mark.classList.add('ring-2', 'ring-primary', 'bg-yellow-200')
                    setTimeout(() => {
                      mark.classList.remove('ring-2', 'ring-primary', 'bg-yellow-200')
                    }, 900)
                  })
                } else {
                  // 书签跳转：闪烁书签或整个句子
                  const marks = el.querySelectorAll('mark.hl-mark') as NodeListOf<HTMLElement>
                  let foundMark = false
                  
                  marks.forEach(mark => {
                    if (mark.textContent?.includes(trimmedSub)) {
                      mark.classList.add('ring-2', 'ring-primary')
                      setTimeout(() => {
                        mark.classList.remove('ring-2', 'ring-primary')
                      }, 900)
                      foundMark = true
                    }
                  })
                  
                  if (!foundMark) {
                    flashElement(el)
                  }
                }
              } catch {}
            }, 100)
          }
          break
        }
      }
    }
  }
}

// 阻止移动端和桌面端的原生上下文菜单
function onContextMenu(e: Event) {
  const selection = window.getSelection()
  if (selection && selection.toString().length > 0) {
    e.preventDefault()
    e.stopPropagation()
  }
}

defineExpose({ scrollToTarget })

onMounted(() => {
  document.addEventListener('selectionchange', onSelectionChange, { passive: true } as any)
  articleEl.value?.addEventListener('contextmenu', onContextMenu)
})
onUnmounted(() => {
  document.removeEventListener('selectionchange', onSelectionChange as any)
  articleEl.value?.removeEventListener('contextmenu', onContextMenu)
})
</script>

<style scoped>
.reader-article {
  /* 禁用移动端原生选择菜单 */
  -webkit-user-select: text;
  user-select: text;
  -webkit-touch-callout: none; /* iOS Safari */
  -webkit-tap-highlight-color: transparent;
}

/* 隐藏移动端原生的复制/粘贴等菜单 */
.reader-article::selection {
  background: rgba(59, 130, 246, 0.3);
}

.reader-article::-moz-selection {
  background: rgba(59, 130, 246, 0.3);
}
</style>
