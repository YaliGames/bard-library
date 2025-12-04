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
      <span
        v-for="(s, i) in sentences"
        :key="i"
        :data-sid="i"
        v-html="getSentenceHtml(i, s)"
      ></span>
    </template>
    <template v-else>
      {{ content }}
    </template>
  </article>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import {
  escapeHtml,
  clampRanges,
  splitOverlappingRanges,
  buildSentenceOffsets,
  buildSearchRegex,
} from '@/utils/reader'

// Props & Emits
const props = defineProps<{
  content: string
  sentences: string[]
  markRanges: Map<
    number,
    Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>
  >
  markTick: number
  searchHighlight?: { keyword: string; caseSensitive: boolean; wholeWord: boolean } | null
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

// DOM refs & local state
const articleEl = ref<HTMLElement | null>(null)
const localSelectionRange = ref<{ start: number; end: number } | null>(null)
const selectionMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
const selectionTextBuffer = ref<string | null>(null)

// -----------------------------
// HTML 渲染器：将句子及其 mark 范围渲染为安全 HTML
// -----------------------------
const activeSearchRegex = computed(() => {
  if (!props.searchHighlight) return null
  const { keyword, caseSensitive, wholeWord } = props.searchHighlight
  try {
    return buildSearchRegex(keyword, caseSensitive, wholeWord)
  } catch {
    return null
  }
})

function getSentenceHtml(i: number, s: string): string {
  const ranges = props.markRanges.get(i)
  const allRanges: Array<any> = []
  if (ranges && ranges.length) allRanges.push(...ranges)

  if (activeSearchRegex.value) {
    try {
      const regex = activeSearchRegex.value
      regex.lastIndex = 0
      let m
      while ((m = regex.exec(s)) !== null) {
        allRanges.push({
          start: m.index,
          end: m.index + m[0].length,
          isSearch: true,
          color: 'rgba(255,235,59,0.6)',
        })
      }
    } catch {
      /* noop */
    }
  }

  if (!allRanges.length) return escapeHtml(s)

  const processed = splitOverlappingRanges(
    clampRanges(allRanges as any, s.length) as any,
  ) as Array<any>
  let html = ''
  let pos = 0
  for (const r of processed) {
    if (r.start > pos) html += escapeHtml(s.slice(pos, r.start))
    const part = s.slice(r.start, r.end)
    const bg = r.color ?? 'rgba(250,216,120,.9)'
    const cls = r.isSearch ? 'search-hl-mark' : 'hl-mark'
    const bid = r.bookmarkId != null ? `data-bid="${r.bookmarkId}"` : ''
    const posAttr = r.isSearch ? `data-pos="${r.start}"` : ''
    html += `<mark class="${cls}" ${bid} ${posAttr} style="background:${bg};">${escapeHtml(part)}</mark>`
    pos = r.end
  }
  if (pos < s.length) html += escapeHtml(s.slice(pos))
  return html
}

// -----------------------------
// 辅助：定位句子 DOM 元素
// -----------------------------
function getClosestSentenceSpan(node: Node | null): HTMLElement | null {
  if (!node) return null
  const el = node.nodeType === 1 ? (node as HTMLElement) : (node as any)?.parentElement
  return el?.closest ? (el.closest('span[data-sid]') as HTMLElement | null) : null
}

// -----------------------------
// Selection 处理（合并 mouseup 与 selectionchange 的逻辑）
// -----------------------------
function processSelectionFrom(sel: Selection | null) {
  if (!sel || sel.rangeCount === 0) {
    selectionTextBuffer.value = null
    localSelectionRange.value = null
    emit('selection', { show: false, range: null, text: null })
    return
  }
  const text = sel.toString()
  if (!text || !text.trim()) {
    selectionTextBuffer.value = null
    localSelectionRange.value = null
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
    localSelectionRange.value = null
    emit('selection', { show: false, range: null, text: null })
    return
  }

  selectionTextBuffer.value = text
  const a = getClosestSentenceSpan(sel.anchorNode)
  const f = getClosestSentenceSpan(sel.focusNode)
  const aId = a ? Number(a.getAttribute('data-sid')) : NaN
  const fId = f ? Number(f.getAttribute('data-sid')) : NaN
  if (!Number.isFinite(aId) || !Number.isFinite(fId)) {
    localSelectionRange.value = null
    emit('selection', { show: false, range: null, text: null })
    return
  }
  const s = Math.min(aId, fId)
  const e = Math.max(aId, fId)
  localSelectionRange.value = { start: s, end: e }
  updateSelectionPosFromRange(s, e)
  emit('selection', {
    show: true,
    x: selectionMenuPos.value.x,
    y: selectionMenuPos.value.y,
    range: localSelectionRange.value,
    text: selectionTextBuffer.value,
  })
}

function onArticleMouseUp() {
  try {
    const sel = window.getSelection?.() ?? null
    processSelectionFrom(sel)
  } catch {
    /* noop */
  }
}

function onSelectionChange() {
  try {
    processSelectionFrom(document.getSelection?.() ?? null)
  } catch {
    /* noop */
  }
}

// 合并：从选中的 Range（由句子范围计算）设置菜单位置
function updateSelectionPosFromRange(startSid: number, endSid: number) {
  const root = articleEl.value
  if (!root) return
  const s = Math.min(startSid, endSid)
  const e = Math.max(startSid, endSid)
  const first = root.querySelector(`span[data-sid="${s}"]`) as HTMLElement | null
  const last = root.querySelector(`span[data-sid="${e}"]`) as HTMLElement | null
  const r1 = first?.getBoundingClientRect()
  const r2 = last?.getBoundingClientRect()
  const top = Math.min(r1?.top ?? Infinity, r2?.top ?? Infinity)
  const left = Math.min(r1?.left ?? Infinity, r2?.left ?? Infinity)
  const right = Math.max(r1 ? r1.left + r1.width : -Infinity, r2 ? r2.left + r2.width : -Infinity)
  if (isFinite(top) && isFinite(left) && isFinite(right))
    selectionMenuPos.value = { x: (left + right) / 2, y: Math.max(8, top - 8) }
}

// -----------------------------
// 点击处理：只处理点击 mark 的行为并发出 mark-click
// -----------------------------
function onArticleClick(e: MouseEvent) {
  const target = e.target as HTMLElement | null
  if (!target) return
  const mark = target.closest ? (target.closest('mark') as HTMLElement | null) : null
  if (!mark) return
  const rect = mark.getBoundingClientRect()
  const pos = { x: rect.left + rect.width / 2, y: Math.max(8, rect.top - 8) }
  const bidRaw = mark.getAttribute('data-bid')
  const bid = bidRaw && bidRaw.trim() ? Number(bidRaw) : null
  emit('mark-click', { show: bid != null, x: pos.x, y: pos.y, bookmarkId: bid })
}

// -----------------------------
// 闪烁/跳转逻辑
// - flashMarksInRange: 闪烁指定句子范围内的所有书签标记
// - flashElement: 当未找到书签标记时对句子整体做强调
// - flashSearchMarkAtPosition: 精确闪烁某句中匹配位置的 search mark
// - scrollToTarget: 仅使用 matchPosition（绝对位置）为第一路径；若找不到则使用提供的 startSid
// -----------------------------
function flashElement(el: HTMLElement) {
  el.classList.add('ring-2', 'ring-primary', 'bg-yellow-100')
  setTimeout(() => el.classList.remove('bg-yellow-100'), 600)
  setTimeout(() => el.classList.remove('ring-2', 'ring-primary'), 900)
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
    if (marks.length) {
      found = true
      marks.forEach(m => {
        m.classList.add('ring-2', 'ring-primary')
        setTimeout(() => m.classList.remove('ring-2', 'ring-primary'), 900)
      })
    }
  }
  return found
}

function flashSearchMarkAtPosition(sentenceEl: HTMLElement, matchStartInSentence: number): boolean {
  const marks = sentenceEl.querySelectorAll(
    'mark.search-hl-mark[data-pos]',
  ) as NodeListOf<HTMLElement>
  if (!marks.length) return false
  let target: HTMLElement | null = null
  for (const m of Array.from(marks)) {
    const p = parseInt(m.getAttribute('data-pos') || '-1', 10)
    if (p === matchStartInSentence) {
      target = m
      break
    }
  }
  if (!target) {
    let best = Infinity
    for (const m of Array.from(marks)) {
      const p = parseInt(m.getAttribute('data-pos') || '-1', 10)
      if (p >= 0) {
        const d = Math.abs(p - matchStartInSentence)
        if (d < best) {
          best = d
          target = m
        }
      }
    }
  }
  if (!target) return false
  target.classList.add('ring-2', 'ring-primary', 'bg-yellow-200')
  setTimeout(() => target!.classList.remove('ring-2', 'ring-primary', 'bg-yellow-200'), 900)
  return true
}

function scrollToTarget(opts: {
  startSid?: number
  endSid?: number
  isSearchJump?: boolean
  matchPosition?: number
  matchLength?: number
}) {
  // 首选：matchPosition -> 通过 buildSentenceOffsets 定位句子并做精确闪烁
  if (typeof opts.matchPosition === 'number') {
    const offsets = buildSentenceOffsets(props.sentences)
    let targetSid: number | undefined
    for (let i = 0; i < offsets.length; i++) {
      const seg = offsets[i]
      if (opts.matchPosition >= seg.start && opts.matchPosition < seg.end) {
        targetSid = i
        break
      }
    }
    if (typeof targetSid === 'number') {
      const el = articleEl.value?.querySelector(
        `span[data-sid="${targetSid}"]`,
      ) as HTMLElement | null
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        if (opts.isSearchJump && typeof opts.matchLength === 'number') {
          setTimeout(() => {
            const sentenceStart = offsets[targetSid!]?.start ?? 0
            const matchStartInSentence = opts.matchPosition! - sentenceStart
            flashSearchMarkAtPosition(el, matchStartInSentence)
          }, 100)
        } else {
          const flashed = flashMarksInRange(
            targetSid,
            typeof opts.endSid === 'number' ? opts.endSid : targetSid,
          )
          if (!flashed) flashElement(el)
        }
        return
      }
    }
  }

  // 次选：使用 startSid 直接滚动并闪烁（保留，但不作为首要路径）
  if (typeof opts.startSid === 'number') {
    const el = articleEl.value?.querySelector(
      `span[data-sid="${opts.startSid}"]`,
    ) as HTMLElement | null
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      const flashed = flashMarksInRange(
        opts.startSid,
        typeof opts.endSid === 'number' ? opts.endSid : opts.startSid,
      )
      if (!flashed) flashElement(el)
      return
    }
  }
}

function onContextMenu(e: Event) {
  const selection = window.getSelection?.()
  if (selection && selection.toString().length > 0) {
    e.preventDefault()
    e.stopPropagation()
  }
}

defineExpose({ scrollToTarget })

onMounted(() => {
  document.addEventListener('selectionchange', onSelectionChange as any)
  articleEl.value?.addEventListener('contextmenu', onContextMenu)
  document.addEventListener('contextmenu', onContextMenu)
})
onUnmounted(() => {
  document.removeEventListener('selectionchange', onSelectionChange as any)
  articleEl.value?.removeEventListener('contextmenu', onContextMenu)
  document.removeEventListener('contextmenu', onContextMenu)
})
</script>

<style scoped>
.reader-article {
  -webkit-user-select: text;
  user-select: text;
  -webkit-touch-callout: none;
  -webkit-tap-highlight-color: transparent;
}
.reader-article * {
  -webkit-touch-callout: none;
  -webkit-user-select: text;
  user-select: text;
}
.reader-article::selection {
  background: rgba(59, 130, 246, 0.3);
}
.reader-article::-moz-selection {
  background: rgba(59, 130, 246, 0.3);
}
</style>
