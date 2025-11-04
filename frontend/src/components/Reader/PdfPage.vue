<template>
  <!-- prevent flex shrinking so side-by-side pages keep their natural canvas size -->
  <div class="relative w-auto flex-shrink-0" ref="root">
    <canvas ref="canvas" class="block mx-auto" :data-page="pageNumber"></canvas>
    <!-- text layer for native selection -->
    <div ref="textLayer" class="absolute left-0 top-0" style="color: transparent"></div>
    <!-- overlay slot: parent can render annotations into this slot -->
    <div ref="overlay" class="absolute left-0 top-0 w-full h-full">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

const props = defineProps({
  pdfDoc: { type: Object, required: true },
  pageNumber: { type: Number, required: true },
  scale: { type: Number, default: 1 },
})

const emit = defineEmits(['rendered', 'selection', 'error'])

const root = ref<HTMLElement | null>(null)
const canvas = ref<HTMLCanvasElement | null>(null)
const textLayer = ref<HTMLElement | null>(null)
const overlay = ref<HTMLElement | null>(null)

let currentRenderTask: any = null
let currentPage: any = null

async function renderPage() {
  // accept either raw pdfDoc or a ref-like wrapper
  const doc =
    props.pdfDoc && typeof props.pdfDoc.getPage === 'function'
      ? props.pdfDoc
      : props.pdfDoc && props.pdfDoc.value && typeof props.pdfDoc.value.getPage === 'function'
        ? props.pdfDoc.value
        : null
  if (!doc) return
  const c = canvas.value
  if (!c) return

  try {
    if (currentRenderTask && typeof currentRenderTask.cancel === 'function') {
      try {
        currentRenderTask.cancel()
      } catch {}
      currentRenderTask = null
    }

    currentPage = await doc.getPage(props.pageNumber)
    const viewport = currentPage.getViewport({ scale: props.scale })

    // account for devicePixelRatio
    const outputScale = window.devicePixelRatio || 1
    c.width = Math.round(viewport.width * outputScale)
    c.height = Math.round(viewport.height * outputScale)
    c.style.width = Math.round(viewport.width) + 'px'
    c.style.height = Math.round(viewport.height) + 'px'

    const ctx = c.getContext('2d')
    if (!ctx) return
    if (outputScale !== 1) ctx.setTransform(outputScale, 0, 0, outputScale, 0, 0)
    ctx.clearRect(0, 0, c.width, c.height)

    currentRenderTask = currentPage.render({ canvasContext: ctx, viewport })
    await currentRenderTask.promise
    currentRenderTask = null

    // render simple text layer for selection
    await renderTextLayer(currentPage, viewport)

    // sync overlay size/position
    syncOverlay()

    // try to locate an overlay host provided by parent in the slot (class 'pdf-overlay')
    let overlayChild: HTMLDivElement | null = null
    try {
      if (overlay && overlay.value && typeof overlay.value.querySelector === 'function') {
        overlayChild = overlay.value.querySelector('.pdf-overlay') as HTMLDivElement | null
      }
    } catch {}

    // emit rendered with helpful element references so parent can reliably map canvas/overlay
    emit('rendered', {
      page: props.pageNumber,
      el: root.value || null,
      canvasEl: canvas.value || null,
      overlayEl: overlayChild || overlay.value || null,
    })
  } catch (e: any) {
    const isCancelled =
      e && (e.name === 'RenderingCancelledException' || /cancelled/i.test(String(e.message || '')))
    if (!isCancelled) {
      emit('error', e)
      console.error('PdfPage render error', e)
    }
  }
}

async function renderTextLayer(pdfPage: any, viewport: any) {
  const tl = textLayer.value
  const c = canvas.value
  if (!tl || !c) return
  tl.innerHTML = ''
  try {
    const textContent = await pdfPage.getTextContent()
    tl.style.left = '0px'
    tl.style.top = '0px'
    tl.style.width = `${Math.round(viewport.width)}px`
    tl.style.height = `${Math.round(viewport.height)}px`
    tl.style.pointerEvents = 'auto'
    tl.style.userSelect = 'text'
    tl.style.zIndex = '1'
    for (const item of textContent.items) {
      const span = document.createElement('span')
      span.textContent = item.str || ''
      span.style.position = 'absolute'
      span.style.whiteSpace = 'pre'
      // position using pdfjs transform utilities for better accuracy
      try {
        const tx = pdfPage?.pdfjsLib?.Util?.transform || (window as any)?.pdfjsLib?.Util?.transform
        const t = tx ? tx(viewport.transform, item.transform) : item.transform
        const x = (t && t[4]) || item.transform?.[4] || 0
        const y = (t && t[5]) || item.transform?.[5] || 0
        span.style.left = `${x * (viewport.scale || 1)}px`
        span.style.top = `${(y - (item.height || 12)) * (viewport.scale || 1)}px`
      } catch {
        span.style.left = `${(item.transform?.[4] || 0) * (viewport.scale || 1)}px`
        span.style.top = `${((item.transform?.[5] || 0) - (item.height || 12)) * (viewport.scale || 1)}px`
      }
      span.style.fontSize = `${(item.height || 12) * (viewport.scale || 1)}px`
      span.style.color = 'transparent'
      // allow pointer events on text spans so native selection works
      span.style.pointerEvents = 'auto'
      span.style.userSelect = 'text'
      span.style.webkitUserSelect = 'text'
      tl.appendChild(span)
    }
  } catch (e) {
    console.error('renderTextLayer failed', e)
  }
}

// prevent dragging the canvas (which can cause forbidden cursor and interfere with drawing)
function onCanvasDragStart(e: DragEvent) {
  e.preventDefault()
  return false
}

// attach dragstart prevention once mounted
onMounted(() => {
  nextTick(() => {
    if (canvas.value) canvas.value.addEventListener('dragstart', onCanvasDragStart)
  })
})

onBeforeUnmount(() => {
  try {
    if (canvas.value) canvas.value.removeEventListener('dragstart', onCanvasDragStart)
  } catch {}
})

function syncOverlay() {
  const c = canvas.value
  const o = overlay.value
  const r = root.value
  if (!c || !o || !r) return
  o.style.width = c.style.width
  o.style.height = c.style.height
  o.style.left = c.offsetLeft + 'px'
  o.style.top = c.offsetTop + 'px'
}

// capture selection on mouseup and emit normalized rects
function handleSelection() {
  if (!textLayer.value) return
  const sel = window.getSelection()
  if (!sel || sel.isCollapsed) return
  try {
    const range = sel.getRangeAt(0)
    if (!range) return
    const c = canvas.value
    const rootEl = root.value
    if (!c || !rootEl) return

    // Only handle selection that belongs to THIS page (anchor or focus inside our root)
    const anchorInThis = sel.anchorNode ? rootEl.contains(sel.anchorNode) : false
    const focusInThis = sel.focusNode ? rootEl.contains(sel.focusNode) : false
    if (!anchorInThis && !focusInThis) return

    const allRects = Array.from(range.getClientRects())
    if (!allRects.length) return
    const crect = c.getBoundingClientRect()

    // Keep only client rects that actually intersect this page's canvas by a minimal amount
    type R = { top: number; bottom: number; left: number; right: number }
    const intersecting: R[] = []
    const minPx = 2 // minimal visible overlap in px to consider
    for (const r of allRects) {
      const left = Math.max(r.left, crect.left)
      const right = Math.min(r.right, crect.right)
      const top = Math.max(r.top, crect.top)
      const bottom = Math.min(r.bottom, crect.bottom)
      const iw = right - left
      const ih = bottom - top
      if (iw > minPx && ih > minPx) {
        intersecting.push({ top, bottom, left, right })
      }
    }
    if (!intersecting.length) return

    // Group intersecting rects by approximate vertical position (line clustering)
    const groups: Array<R> = []
    const threshold = Math.max(4, crect.height * 0.002)
    for (const r of intersecting) {
      const mid = (r.top + r.bottom) / 2
      let found = false
      for (const g of groups) {
        const gmid = (g.top + g.bottom) / 2
        if (Math.abs(gmid - mid) <= threshold) {
          g.top = Math.min(g.top, r.top)
          g.bottom = Math.max(g.bottom, r.bottom)
          g.left = Math.min(g.left, r.left)
          g.right = Math.max(g.right, r.right)
          found = true
          break
        }
      }
      if (!found) groups.push({ ...r })
    }

    const rects: Array<any> = []
    for (const g of groups) {
      const x = (g.left - crect.left) / crect.width
      const y = (g.top - crect.top) / crect.height
      const w = (g.right - g.left) / crect.width
      const h = (g.bottom - g.top) / crect.height
      if (w <= 0 || h <= 0) continue
      rects.push({
        x: Math.max(0, Math.min(1, x)),
        y: Math.max(0, Math.min(1, y)),
        w: Math.min(1, w),
        h: Math.min(1, h),
      })
    }
    if (rects.length) {
      emit('selection', { page: props.pageNumber, rects })
    }
  } catch {
    // ignore
  }
}

let onMouseUpListener: any = null

onMounted(() => {
  nextTick(() => renderPage())
  onMouseUpListener = () => handleSelection()
  window.addEventListener('mouseup', onMouseUpListener)
})

onBeforeUnmount(() => {
  if (currentRenderTask && typeof currentRenderTask.cancel === 'function') {
    try {
      currentRenderTask.cancel()
    } catch {}
  }
  window.removeEventListener('mouseup', onMouseUpListener)
})

watch(
  () => props.pageNumber,
  () => {
    renderPage()
  },
)

watch(
  () => props.scale,
  () => {
    renderPage()
  },
)

watch(
  () => props.pdfDoc,
  v => {
    if (v) renderPage()
  },
)
</script>
