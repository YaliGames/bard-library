<template>
  <article
    ref="articleEl"
    :key="'article-' + markTick"
    @mouseup="onArticleMouseUp"
    @click="onArticleClick"
    style="white-space:pre-wrap; line-height:var(--reader-line-height); font-size:var(--reader-font-size); max-width:var(--reader-content-width); margin:0 auto; padding: 12px 16px;"
  >
    <template v-if="sentences.length">
      <span
        v-for="(s, i) in sentences"
        :key="i"
        :data-sid="i"
        v-html="getSentenceHtml(i, s)"
      />
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
  markRanges: Map<number, Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>>
  markTick: number
}>()

const emit = defineEmits<{
  (e: 'selection', payload: { show: boolean; x?: number; y?: number; range?: { start: number; end: number } | null; text?: string | null }): void
  (e: 'mark-click', payload: { show: boolean; x?: number; y?: number; bookmarkId?: number | null }): void
}>()

// DOM refs
const articleEl = ref<HTMLElement | null>(null)

// Local state
const localSelectionRange = ref<{ start: number; end: number } | null>(null)
const selectionTextBuffer = ref<string | null>(null)

// Helpers from utils

function getSentenceHtml(i: number, s: string): string {
  const ranges = props.markRanges.get(i)
  if (!ranges || ranges.length === 0) return escapeHtml(s)
  const merged = mergeRanges(clampRanges(ranges as any, s.length) as any) as Array<{ start:number; end:number; bookmarkId?: number; color?: string | null }>
  let html = ''
  let pos = 0
  for (const r of merged) {
    if (r.start > pos) html += escapeHtml(s.slice(pos, r.start))
    const part = s.slice(r.start, r.end)
    const bg = r.color ? r.color : 'rgba(250,216,120,.9)'
    html += `<mark class="hl-mark" data-bid="${r.bookmarkId ?? ''}" style="background:${bg};">${escapeHtml(part)}</mark>`
    pos = r.end
  }
  if (pos < s.length) html += escapeHtml(s.slice(pos))
  return html
}

function getClosestSentenceSpan(node: Node | null): HTMLElement | null {
  const el = (node && (node as HTMLElement).nodeType === 1 ? (node as HTMLElement) : (node as any)?.parentElement) as HTMLElement | null
  return el ? (el.closest && el.closest('span[data-sid]') as HTMLElement | null) : null
}

// Selection interactions

const selectionMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })

function onArticleMouseUp() {
  try {
    const sel = window.getSelection?.()
    if (!sel || sel.rangeCount === 0) return
    const text = sel.toString()
    if (!text || text.trim().length === 0) { selectionTextBuffer.value = null; emit('selection', { show: false, range: null, text: null }); return }
    const root = articleEl.value
    if (!root) return
    const range = sel.getRangeAt(0)
    const startNode = range.startContainer
    const endNode = range.endContainer
    if (!root.contains(startNode) || !root.contains(endNode)) { selectionTextBuffer.value = null; emit('selection', { show: false, range: null, text: null }); return }
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
    emit('selection', { show: true, x: selectionMenuPos.value.x, y: selectionMenuPos.value.y, range: localSelectionRange.value, text: selectionTextBuffer.value })
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
    emit('selection', { show: true, x: selectionMenuPos.value.x, y: selectionMenuPos.value.y, range: localSelectionRange.value, text: selectionTextBuffer.value })
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
  } catch { }
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
    const right = Math.max((r1 ? r1.left + r1.width : -Infinity), (r2 ? r2.left + r2.width : -Infinity))
    if (isFinite(top) && isFinite(left) && isFinite(right)) {
      selectionMenuPos.value = { x: (left + right) / 2, y: Math.max(8, top - 8) }
    }
  } catch { }
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
  setTimeout(() => { el.classList.remove('bg-yellow-100') }, 600)
  setTimeout(() => { el.classList.remove('ring-2', 'ring-primary') }, 900)
}

function flashMarksInRange(startSid: number, endSid: number): boolean {
  const root = articleEl.value
  if (!root) return false
  let found = false
  const s = Math.min(startSid, endSid)
  const e = Math.max(startSid, endSid)
  for (let i = s; i <= e; i++) {
    const marks = root.querySelectorAll(`span[data-sid="${i}"] mark.hl-mark`) as NodeListOf<HTMLElement>
    if (marks && marks.length) {
      found = true
      marks.forEach(m => {
        m.classList.add('ring-2', 'ring-primary')
        setTimeout(() => { m.classList.remove('ring-2', 'ring-primary') }, 900)
      })
    }
  }
  return found
}

function scrollToTarget(opts: { startSid?: number; endSid?: number; selectionText?: string }) {
  // Prefer precise by startSid
  if (typeof opts.startSid === 'number') {
    const el = articleEl.value?.querySelector(`span[data-sid="${opts.startSid}"]`) as HTMLElement | null
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      const flashed = flashMarksInRange(opts.startSid, typeof opts.endSid === 'number' ? opts.endSid : opts.startSid)
      if (!flashed) flashElement(el)
      return
    }
  }
  // Fallback by selectionText approximate: find first sentence containing text
  if (opts.selectionText) {
    const text = props.content
    const sub = opts.selectionText
    const idx = text.indexOf(sub)
    if (idx >= 0) {
      const offsets = buildSentenceOffsets(props.sentences)
      for (let i = 0; i < offsets.length; i++) {
        const seg = offsets[i]
        if (idx >= seg.start && idx < seg.end) {
          const el = articleEl.value?.querySelector(`span[data-sid="${i}"]`) as HTMLElement | null
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            const flashed = flashMarksInRange(i, i)
            if (!flashed) flashElement(el)
          }
          break
        }
      }
    }
  }
}

defineExpose({ scrollToTarget })

onMounted(() => {
  document.addEventListener('selectionchange', onSelectionChange, { passive: true } as any)
})
onUnmounted(() => {
  document.removeEventListener('selectionchange', onSelectionChange as any)
})
</script>
