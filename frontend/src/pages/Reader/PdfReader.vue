<template>
  <section class="flex flex-col h-[100vh]">
    <div
      class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-[var(--el-bg-color-overlay)]">
      <div class="flex items-center gap-2">
        <router-link to="/books">
          <el-button>
            <span class="material-symbols-outlined mr-1">arrow_back</span>
            返回书库
          </el-button>
        </router-link>
        <h1 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">PDF 阅读器</h1>
        <div v-if="meta.title" class="text-sm text-gray-600">{{ meta.title }}</div>
      </div>

      <div class="flex items-center gap-2">
        <el-input-number v-model="scale" :min="0.25" :max="3" :step="0.25" size="small" />
        <el-button @click="prevPage">上一页</el-button>
        <el-button @click="nextPage">下一页</el-button>
        <div class="text-sm">{{ page }} / {{ totalPages }}</div>
        <el-button type="primary" @click="download">下载原文件</el-button>
        <!-- Annotation toolbar -->
        <el-segmented v-model="tool" :options="['select', 'rect', 'text']" size="small" />
      </div>
    </div>

    <div class="flex-1 min-h-0 bg-gray-50 dark:bg-[#111] overflow-auto flex items-start justify-center">
      <div v-if="loading" class="text-gray-500">正在加载 PDF…</div>
      <div v-else-if="error" class="text-red-500">加载失败：{{ error }}</div>
      <div v-else-if="!useIframeFallback" ref="containerRef" class="relative" style="max-width: 100%;"
        @mousedown="onMouseDown" @mousemove="onMouseMove">
        <canvas ref="canvasRef" style="display:block; border: 1px solid rgba(0,0,0,0.08); background: white;"></canvas>
        <!-- 注释覆盖层 -->
        <div ref="overlayRef" class="absolute top-0 left-0"
          :style="{ width: canvasWidth + 'px', height: canvasHeight + 'px', pointerEvents: 'auto' }">
          <div :key="annotationRenderKey" ref="annotationsHostRef">
            <template v-for="ann in pageAnnotations" :key="ann.id">
              <div class="absolute" :style="annotationStyle(ann)" :data-annotation-id="ann.id"
                @click.stop="selectAnnotation(ann)">
              </div>
            </template>
          </div>
          <!-- drawing preview -->
          <div v-if="drawing" :style="drawingStyleRef" class="absolute bg-yellow-200 opacity-60 pointer-events-none">
          </div>
        </div>
      </div>
      <div v-else class="w-full h-full">
        <iframe :src="fallbackBlobUrl || ''" class="w-full h-full border-0" title="PDF 备用预览"></iframe>
      </div>
    </div>
    <div v-if="!loading && !error" class="absolute bottom-16 right-8 bg-white bg-opacity-80 text-xs p-2 rounded shadow">
      <div>PDF loaded: {{ !!pdfDoc }}</div>
      <div>Canvas: {{ canvasWidth }} x {{ canvasHeight }}</div>
      <!-- HighlightMenu 用于颜色选择与笔记（复用 TxtReader 的组件） -->
      <HighlightMenu :show="!!selectedAnnotation" :x="highlightMenuPos.x" :y="highlightMenuPos.y"
        :note="selectedAnnotation?.note" :current-color="selectedAnnotation?.color" @pick-color="onPickColorFromMenu"
        @add-note="onAddNoteFromMenu" @delete="deleteSelected" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { onUnmounted } from 'vue'
import HighlightMenu from '@/components/Reader/HighlightMenu.vue'
import { useRoute } from 'vue-router'

// route + fileId
const route = useRoute()
const fileId = computed(() => Number(route.params.id))

// pdf.js related refs
// IMPORTANT: pdfDoc MUST NOT be reactive (Vue3 Proxy breaks pdf.js private field access)
let pdfDoc: any = null
const page = ref<number>(1)
const totalPages = ref<number>(0)
const scale = ref<number>(1.0)
const canvasRef = ref<HTMLCanvasElement | null>(null)
const overlayRef = ref<HTMLDivElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)
const meta = ref({ title: '' })
const loading = ref<boolean>(true)
const error = ref<string | null>(null)

const canvasWidth = computed(() => canvasRef.value ? canvasRef.value.width : 0)
const canvasHeight = computed(() => canvasRef.value ? canvasRef.value.height : 0)
const pdfArrayBuffer = ref<ArrayBuffer | null>(null)
const useIframeFallback = ref(false)
const fallbackBlobUrl = ref<string | null>(null)
let triedUrlFallback = false

// annotation tool state
const tool = ref<'select' | 'rect' | 'text'>('rect')
const currentColor = ref<string>('#FAD878')
const drawing = ref(false)
const drawStart = ref<{ x: number, y: number } | null>(null)
const drawCurr = ref<{ x: number, y: number } | null>(null)
const selectedAnnotation = ref<any | null>(null)
// highlight menu position (viewport coords)
const highlightMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })

// reactive annotations list (so UI updates immediately)
const annotations = ref<any[]>(loadAnnotations())
const pageAnnotations = computed(() => annotations.value.filter(a => a.page === page.value))
// key to force re-render only the annotations host (won't remount overlay or textLayer)
const annotationRenderKey = ref(0)

async function forceRerenderAnnotations(retries = 3, delay = 40) {
  for (let i = 0; i < retries; i++) {
    // allow DOM to update
    await nextTick()
  syncOverlayToCanvas()
  // force re-render of annotations container (safe, doesn't remove textLayer)
  annotationRenderKey.value++
    // small pause so layout stabilizes
    await new Promise((r) => setTimeout(r, delay))
  }
}

function annotationStyle(ann: any) {
  const canvas = canvasRef.value
  if (!canvas) return {}
  const left = ann.rect.x * canvas.width
  const top = ann.rect.y * canvas.height
  const w = ann.rect.w * canvas.width
  const h = ann.rect.h * canvas.height
  return ({
    left: left + 'px',
    top: top + 'px',
    width: w + 'px',
    height: h + 'px',
    background: ann.color || 'rgba(255,235,59,0.35)',
    border: '1px solid rgba(0,0,0,0.08)',
    zIndex: 2,
    cursor: 'pointer',
    opacity: ann.opacity ?? 0.6,
    // allow clicks when in select tool or when this annotation is selected
    pointerEvents: (tool.value === 'select' || (selectedAnnotation.value && selectedAnnotation.value.id === ann.id)) ? 'auto' : 'none'
  } as any)
}

function selectAnnotation(ann: any) {
  selectedAnnotation.value = ann
  // compute highlight menu position based on annotation bbox
  const canvas = canvasRef.value
  if (!canvas) return
  const crect = canvas.getBoundingClientRect()
  const left = crect.left + ann.rect.x * crect.width
  const top = crect.top + ann.rect.y * crect.height
  const w = ann.rect.w * crect.width
  highlightMenuPos.value = { x: Math.round(left + w + 8), y: Math.round(top) }
}

function onDocMouseDown(e: MouseEvent) {
  // if menu not visible, nothing to do
  if (!selectedAnnotation.value) return
  const overlay = overlayRef.value
  const target = e.target as Node
  // if clicked inside overlay, but not on an annotation element, hide the menu
  if (overlay && overlay.contains(target)) {
    // check if click landed inside one of annotation elements
    let el: Node | null = target
    while (el && el !== overlay) {
      // annotation divs have no special class; compare dataset or style
      if ((el as HTMLElement).dataset && (el as HTMLElement).dataset['annotationId']) return
      el = el.parentNode
    }
    // clicked inside overlay but not on an annotation
    selectedAnnotation.value = null
  } else {
    // clicked outside overlay entirely
    selectedAnnotation.value = null
  }
}

function deleteSelected() {
  if (!selectedAnnotation.value) return
  annotations.value = annotations.value.filter((a: any) => a.id !== selectedAnnotation.value.id)
  saveAnnotations(annotations.value)
  selectedAnnotation.value = null
}

// handle color picked from HighlightMenu
function onPickColorFromMenu(color: string | Event) {
  const picked = typeof color === 'string' ? color : (color as any)?.target?.value
  if (!picked || !selectedAnnotation.value) return
  const idx = annotations.value.findIndex((a: any) => a.id === selectedAnnotation.value.id)
  if (idx >= 0) {
    annotations.value[idx].color = picked
    selectedAnnotation.value.color = picked
    saveAnnotations(annotations.value)
  }
}

// add/edit note from menu (simple prompt backed by localStorage)
async function onAddNoteFromMenu() {
  if (!selectedAnnotation.value) return
  const prev = selectedAnnotation.value.note || ''
  const text = window.prompt('添加/修改批注', prev || '')
  if (text == null) return
  const idx = annotations.value.findIndex((a: any) => a.id === selectedAnnotation.value.id)
  if (idx >= 0) {
    annotations.value[idx].note = text
    selectedAnnotation.value.note = text
    saveAnnotations(annotations.value)
  }
}

// mouse handlers for rectangle drawing
function toNormalized(evt: MouseEvent) {
  const canvas = canvasRef.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  const x = (evt.clientX - rect.left) / rect.width
  const y = (evt.clientY - rect.top) / rect.height
  return { x: Math.max(0, Math.min(1, x)), y: Math.max(0, Math.min(1, y)) }
}

function onMouseDown(e: MouseEvent) {
  if (tool.value !== 'rect') return
  drawing.value = true
  drawStart.value = toNormalized(e)
  drawCurr.value = drawStart.value
}

function onMouseMove(e: MouseEvent) {
  if (!drawing.value) return
  drawCurr.value = toNormalized(e)
}

function onWindowMouseUp(e: MouseEvent) {
  // finish any ongoing drawing even if mouseup happened outside canvas
  if (drawing.value) {
    // compute end point from last known mouse position if available
    try {
      // stop drawing and finalize
      drawing.value = false
      const end = drawCurr.value || drawStart.value
      if (!drawStart.value || !end) { drawStart.value = null; drawCurr.value = null; return }
      const sx = drawStart.value.x
      const sy = drawStart.value.y
      const ex = end.x
      const ey = end.y
      const x = Math.min(sx, ex)
      const y = Math.min(sy, ey)
      const w = Math.abs(ex - sx)
      const h = Math.abs(ey - sy)
      if (w >= 0.005 && h >= 0.005) {
        const ann = { id: Date.now(), page: page.value, rect: { x, y, w, h }, color: currentColor.value }
        annotations.value.push(ann)
        saveAnnotations(annotations.value)
      }
    } finally {
      drawStart.value = null; drawCurr.value = null
    }
  }
  if (tool.value === 'text') {
    setTimeout(() => handleTextSelection(), 10)
  }
}

const drawingStyleRef = computed(() => {
  if (!drawing.value || !drawStart.value || !drawCurr.value || !canvasRef.value) return {}
  const canvas = canvasRef.value
  const x = Math.min(drawStart.value.x, drawCurr.value.x) * canvas.width
  const y = Math.min(drawStart.value.y, drawCurr.value.y) * canvas.height
  const w = Math.abs(drawCurr.value.x - drawStart.value.x) * canvas.width
  const h = Math.abs(drawCurr.value.y - drawStart.value.y) * canvas.height
  return { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px', background: currentColor.value, opacity: 0.45 }
})

// load pdf.js dynamically
let pdfjsLib: any = null

function previewUrlFor(id: number) {
  return `/api/v1/files/${id}/preview`
}

async function initPdf() {
  loading.value = true
  error.value = null
  if (!fileId.value) { loading.value = false; return }
  try {
    pdfjsLib = await import('pdfjs-dist/legacy/build/pdf.js')
    // Try to set workerSrc using Vite '?url' import so worker file is served correctly in dev/prod
    try {
      const workerMod = await import('pdfjs-dist/legacy/build/pdf.worker.js?url')
      const workerUrl = workerMod?.default || workerMod
      if (workerUrl) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl
      }
    } catch (e) {
      // fallback: attempt to set a common path
      try { pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.js' } catch { }
    }
    // Fetch arrayBuffer first to avoid header/issues; this loads the whole file but is more compatible for MVP
    const resp = await fetch(previewUrlFor(fileId.value), { credentials: 'include' })
    if (!resp.ok) throw new Error(`Failed to fetch PDF: ${resp.status}`)
    const arrayBuffer = await resp.arrayBuffer()
    pdfArrayBuffer.value = arrayBuffer
    const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer })
    pdfDoc = await loadingTask.promise
    totalPages.value = pdfDoc.numPages
    meta.value.title = `File ${fileId.value}`
    // load annotations from storage into reactive ref
    annotations.value = loadAnnotations()
    await renderPage()
  } catch (err: any) {
    console.error('PDF load error', err)
    error.value = err?.message || String(err)
  } finally {
    loading.value = false
  }
}

async function renderPage() {
  if (!pdfDoc || !canvasRef.value) return
  try {
    // debug removed
    let pdfPage
    try {
      pdfPage = await pdfDoc.getPage(page.value)
    } catch (getErr) {
      console.error('getPage threw', getErr)
      // try binding explicitly in case 'this' is incorrect
      try {
        pdfPage = await (pdfDoc.getPage as any).call(pdfDoc, page.value)
      } catch (callErr) {
        console.error('getPage.call threw', callErr)
        throw callErr
      }
    }
    const viewport = pdfPage.getViewport({ scale: scale.value })
    const canvas = canvasRef.value
    const context = canvas.getContext('2d')!
    // set canvas size according to viewport (this includes scale)
    const width = Math.floor(viewport.width)
    const height = Math.floor(viewport.height)
    canvas.width = width
    canvas.height = height
    // ensure CSS size matches actual pixel size (no CSS transform)
    canvas.style.width = width + 'px'
    canvas.style.height = height + 'px'
    context.clearRect(0, 0, canvas.width, canvas.height)

    const renderContext = {
      canvasContext: context,
      viewport: viewport
    }
    await pdfPage.render(renderContext).promise

    // adjust overlay size and draw annotations from localStorage demo
    nextTick(async () => {
      // sync container & overlay sizes
      if (containerRef.value) {
        containerRef.value.style.width = canvas.style.width
        containerRef.value.style.height = canvas.style.height
      }
      // overlay is reactive; ensure text layer is rendered for selection
      renderTextLayer(pdfPage, viewport)
      // force multiple syncs and a DOM re-render of annotations so positions settle
      await forceRerenderAnnotations(3, 40)
    })
  } catch (err: any) {
    console.error('renderPage error', err)
    // fallback: show iframe with blob URL so user can view PDF even if pdf.js failed
    if (!triedUrlFallback) {
      // try reloading via URL (let pdf.js handle range/worker) before blob fallback
      triedUrlFallback = true
      try {
        const loadingTask2 = pdfjsLib.getDocument({ url: previewUrlFor(fileId.value), withCredentials: true })
        pdfDoc = await loadingTask2.promise
        totalPages.value = pdfDoc.numPages
        await renderPage()
        return
      } catch (e) {
        console.error('URL fallback failed', e)
      }
    }
    if (pdfArrayBuffer.value) {
      try {
        const blob = new Blob([pdfArrayBuffer.value], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        fallbackBlobUrl.value = url
        useIframeFallback.value = true
      } catch (e) {
        console.error('create blob fallback failed', e)
      }
    }
    error.value = err?.message || String(err)
  }
}

function prevPage() {
  if (page.value <= 1) return
  page.value--
  renderPage()
  saveProgressDebounced()
}

function nextPage() {
  if (page.value >= totalPages.value) return
  page.value++
  renderPage()
  saveProgressDebounced()
}

function download() {
  if (!fileId.value) return
  try { window.location.href = `/api/v1/files/${fileId.value}/download` } catch { }
}

// Simple localStorage-backed annotations for demo: keyed by fileId
function annotationsKey() { return `pdf:annotations:${fileId.value}` }
function loadAnnotations(): any[] {
  try { return JSON.parse(localStorage.getItem(annotationsKey()) || '[]') } catch { return [] }
}
function saveAnnotations(list: any[]) { localStorage.setItem(annotationsKey(), JSON.stringify(list)) }

// render a selectable text layer (approximate) so native selection works
async function renderTextLayer(pdfPage: any, viewport: any) {
  const overlay = overlayRef.value
  const canvas = canvasRef.value
  if (!overlay || !canvas) return
  // remove existing textLayer
  const existing = overlay.querySelector('#textLayer') as HTMLElement | null
  if (existing) existing.remove()
  try {
    const textContent = await pdfPage.getTextContent()
    const textLayer = document.createElement('div')
    textLayer.id = 'textLayer'
    textLayer.style.position = 'absolute'
    textLayer.style.left = '0'
    textLayer.style.top = '0'
    textLayer.style.width = canvas.width + 'px'
    textLayer.style.height = canvas.height + 'px'
  // keep text selectable and allow pointer events so selection works
  textLayer.style.pointerEvents = 'auto'
    textLayer.style.userSelect = 'text'
    textLayer.style.webkitUserSelect = 'text'
    textLayer.style.overflow = 'hidden'
    textLayer.style.zIndex = '1'

    for (const item of textContent.items) {
      // transform text item coordinates into viewport coordinates
      // item.transform is a 6-element array; pdfjs util transform combines transforms
      const tx = (pdfjsLib?.Util?.transform || ((a: any, b: any) => []))(viewport.transform, item.transform)
      const x = tx[4] || 0
      const y = tx[5] || 0
      // approximate font size from transform matrix
      const fontSize = Math.abs(item.transform[0]) * viewport.scale || 12
      const span = document.createElement('span')
      span.textContent = item.str
      span.style.position = 'absolute'
      // position: y is baseline-like; adjust by fontSize
      span.style.left = x + 'px'
      span.style.top = (y - fontSize) + 'px'
      span.style.fontSize = fontSize + 'px'
      span.style.lineHeight = fontSize + 'px'
      span.style.whiteSpace = 'pre'
      // make text invisible but selectable
      span.style.color = 'transparent'
      span.style.background = 'transparent'
      span.style.pointerEvents = 'auto'
      // allow selection on spans specifically
      span.style.pointerEvents = 'auto'
      textLayer.appendChild(span)
    }

    // prepend text layer so annotations (zIndex 2) sit above it
    if (overlay.firstChild) overlay.insertBefore(textLayer, overlay.firstChild)
    else overlay.appendChild(textLayer)
  } catch (e) {
    // non-fatal: text selection will not be available
    console.error('renderTextLayer failed', e)
  }
}

// ensure overlay size matches canvas and force a reflow so annotations position correctly
function syncOverlayToCanvas() {
  const canvas = canvasRef.value
  const overlay = overlayRef.value
  const container = containerRef.value
  if (!canvas || !overlay || !container) return
  overlay.style.width = canvas.style.width
  overlay.style.height = canvas.style.height
  container.style.width = canvas.style.width
  container.style.height = canvas.style.height
  // force reflow
  // eslint-disable-next-line @typescript-eslint/no-unused-expressions
  overlay.offsetHeight
}

// when using text tool, capture native selection on mouseup and convert to annotation(s)
function handleTextSelection() {
  if (tool.value !== 'text') return
  const sel = window.getSelection()
  if (!sel || sel.isCollapsed) return
  const range = sel.getRangeAt(0)
  const rects = Array.from(range.getClientRects())
  if (!rects.length) return
  const canvas = canvasRef.value
  if (!canvas) return
  const crect = canvas.getBoundingClientRect()
  // create precise rect per clientRect
  const newAnns: any[] = []
  for (const r of rects) {
    const rx = (r.left - crect.left) / crect.width
    const ry = (r.top - crect.top) / crect.height
    const rw = (r.width) / crect.width
    const rh = (r.height) / crect.height
    if (rw <= 0 || rh <= 0) continue
    newAnns.push({ id: Date.now() + Math.floor(Math.random() * 10000), page: page.value, rect: { x: Math.max(0, Math.min(1, rx)), y: Math.max(0, Math.min(1, ry)), w: Math.min(1, rw), h: Math.min(1, rh) }, color: currentColor.value, opacity: 0.6 })
  }
  if (newAnns.length === 0) return
  annotations.value.push(...newAnns)
  saveAnnotations(annotations.value)
  // clear selection
  try { sel.removeAllRanges() } catch { }
}

// reading progress (localStorage demo key)
function progressKey() { return `pdf:progress:${fileId.value}` }
function loadProgress() {
  try { return JSON.parse(localStorage.getItem(progressKey()) || '{}') } catch { return {} }
}
function saveProgress() {
  localStorage.setItem(progressKey(), JSON.stringify({ page: page.value, updated_at: Date.now() }))
}
let saveTimer: any = null
function saveProgressDebounced() {
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => saveProgress(), 500)
}

onMounted(async () => {
  await initPdf()
  // restore progress demo
  const p = loadProgress()
  if (p && p.page) {
    page.value = p.page
  }
  // no manual addEventListener — template has @click binding
  // render after restoring progress
  await renderPage()
  // ensure overlay sync after initial render
  setTimeout(() => syncOverlayToCanvas(), 50)
  // also do a stronger reflow & annotation re-render to ensure positions are correct on first load
  await forceRerenderAnnotations(4, 30)
  // global mouseup to ensure drawing ends even if cursor leaves canvas
  window.addEventListener('mouseup', onWindowMouseUp)
  // click outside to hide menu
  document.addEventListener('mousedown', onDocMouseDown)
})

watch(page, () => {
  // guard: render when page changes
  renderPage()
})

onUnmounted(() => {
  if (fallbackBlobUrl.value) {
    try { URL.revokeObjectURL(fallbackBlobUrl.value) } catch { }
  }
  window.removeEventListener('mouseup', onWindowMouseUp)
  document.removeEventListener('mousedown', onDocMouseDown)
})

watch(scale, () => {
  // when scale changes, re-render and force overlay sync; also scroll container to top to avoid clipped top
  if (containerRef.value) containerRef.value.scrollTop = 0
  renderPage()
  setTimeout(() => syncOverlayToCanvas(), 30)
})
</script>
