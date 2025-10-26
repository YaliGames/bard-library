<template>
  <section class="flex flex-col h-screen">
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

      <div class="flex items-center gap-3">
        <!-- scale control (left button, text, right button - match page navigation) -->
        <div class="flex items-center gap-1 bg-white/60 rounded px-2 py-1">
          <el-button size="small" type="text" @click="zoomOut" class="p-1">
            <span class="material-symbols-outlined">zoom_out</span>
          </el-button>
          <div class="text-sm px-2">{{ formattedScale }}</div>
          <el-button size="small" type="text" @click="zoomIn" class="p-1">
            <span class="material-symbols-outlined">zoom_in</span>
          </el-button>
        </div>

        <!-- page navigation -->
        <div class="flex items-center gap-1 bg-white/60 rounded px-2 py-1">
          <el-button size="small" type="text" @click="prevPage" class="p-1">
            <span class="material-symbols-outlined">chevron_left</span>
          </el-button>
          <div class="text-sm px-2">{{ page }} / {{ totalPages }}</div>
          <el-button size="small" type="text" @click="nextPage" class="p-1">
            <span class="material-symbols-outlined">chevron_right</span>
          </el-button>
        </div>

        <!-- view mode switcher -->
        <div class="inline-flex items-center rounded overflow-hidden mr-2">
          <el-button-group>
            <el-tooltip content="单页模式">
              <el-button @click="setViewMode('single')" :type="viewMode === 'single' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">crop_landscape</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="双页模式">
              <el-button @click="setViewMode('two')" :type="viewMode === 'two' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">view_column_2</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="连续模式">
              <el-button @click="setViewMode('continuous')" :type="viewMode === 'continuous' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">view_stream</span>
              </el-button>
            </el-tooltip>
          </el-button-group>
        </div>

        <!-- tool switcher (icon buttons) -->
        <div class="inline-flex items-center rounded overflow-hidden">
          <el-button-group>
            <el-tooltip content="选择工具">
              <el-button @click="tool = 'select'" :type="tool === 'select' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">arrow_selector_tool</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="矩形高亮">
              <el-button @click="tool = 'rect'" :type="tool === 'rect' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">crop_square</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="圆形高亮">
              <el-button @click="tool = 'circle'" :type="tool === 'circle' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">circle</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="文本高亮">
              <el-button @click="tool = 'text'" :type="tool === 'text' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">format_ink_highlighter</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="文本划线">
              <el-button @click="tool = 'text_under'" :type="tool === 'text_under' ? 'primary' : 'default'">
                <span class="material-symbols-outlined">border_color</span>
              </el-button>
            </el-tooltip>
          </el-button-group>
        </div>

        <!-- download -->
        <el-button type="primary" @click="download" class="flex items-center gap-1">
          <span class="material-symbols-outlined">download</span>
          <span class="hidden sm:inline">下载</span>
        </el-button>
      </div>
    </div>

    <div ref="scrollRef" class="flex-1 min-h-0 bg-gray-50 dark:bg-[#111] overflow-auto flex items-start justify-center"
      @scroll="onContainerScroll">
      <div v-if="loading" class="text-gray-500">正在加载 PDF…</div>
      <div v-else-if="error" class="text-red-500">加载失败：{{ error }}</div>
      <div v-else-if="!useIframeFallback" ref="containerRef" class="relative" style="max-width: 100%;"
        @mousedown="onMouseDown" @mousemove="onMouseMove">
        <!-- single page mode -->
        <template v-if="viewMode === 'single'">
          <PdfPage :pdfDoc="pdfDocRef" :pageNumber="page" :scale="scale" @rendered="onPdfPageRendered"
            @selection="onPdfPageSelection">
            <div ref="overlayRef" class="pdf-overlay absolute top-0 left-0 w-full h-full">
              <div :key="annotationRenderKey" ref="annotationsHostRef">
                <template v-for="ann in pageAnnotations" :key="ann.id">
                  <template v-if="ann.rects && ann.rects.length">
                    <template v-for="(r, ri) in ann.rects" :key="ri">
                      <div class="absolute" :style="annotationBoxStyle(ann, r)" :data-annotation-id="ann.id"
                        @click.stop="selectAnnotation(ann)"></div>
                    </template>
                  </template>
                  <template v-else>
                    <div class="absolute" :style="annotationBoxStyle(ann, ann.rect)" :data-annotation-id="ann.id"
                      @click.stop="selectAnnotation(ann)"></div>
                  </template>
                </template>
              </div>
              <!-- drawing preview (left) -->
              <div v-if="drawing" :style="getDrawingStyle('left')"
                class="absolute bg-yellow-200 opacity-60 pointer-events-none">
              </div>
            </div>
          </PdfPage>
        </template>

        <!-- two-page side-by-side mode -->
        <template v-else-if="viewMode === 'two'">
          <div class="flex gap-4 items-start justify-center">
            <PdfPage :pdfDoc="pdfDocRef" :pageNumber="page" :scale="scale" @rendered="onPdfPageRendered"
              @selection="onPdfPageSelection">
              <div ref="overlayRef" class="pdf-overlay absolute top-0 left-0 w-full h-full">
                <div :key="annotationRenderKey" ref="annotationsHostRef">
                  <template v-for="ann in pageAnnotations" :key="ann.id">
                    <template v-if="ann.rects && ann.rects.length">
                      <template v-for="(r, ri) in ann.rects" :key="ri">
                        <div class="absolute" :style="annotationBoxStyle(ann, r)" :data-annotation-id="ann.id"
                          @click.stop="selectAnnotation(ann)"></div>
                      </template>
                    </template>
                    <template v-else>
                      <div class="absolute" :style="annotationBoxStyle(ann, ann.rect)" :data-annotation-id="ann.id"
                        @click.stop="selectAnnotation(ann)"></div>
                    </template>
                  </template>
                </div>
                <div v-if="drawing" :style="getDrawingStyle('left')"
                  class="absolute bg-yellow-200 opacity-60 pointer-events-none">
                </div>
              </div>
            </PdfPage>

            <template v-if="page < totalPages">
              <PdfPage :pdfDoc="pdfDocRef" :pageNumber="rightPage" :scale="scale" @rendered="onPdfPageRendered"
                @selection="onPdfPageSelection">
                <div ref="overlayRightRef" class="pdf-overlay absolute top-0 left-0 w-full h-full">
                  <!-- render annotations for right page only -->
                  <div :key="annotationRenderKey + '-r'" ref="annotationsHostRefRight">
                    <template v-for="ann in annotations.filter(a => a.page === rightPage)" :key="ann.id">
                      <template v-if="ann.rects && ann.rects.length">
                        <template v-for="(r, ri) in ann.rects" :key="ri">
                          <div class="absolute" :style="annotationBoxStyle(ann, r)" :data-annotation-id="ann.id"
                            @click.stop="selectAnnotation(ann)"></div>
                        </template>
                      </template>
                      <template v-else>
                        <div class="absolute" :style="annotationBoxStyle(ann, ann.rect)" :data-annotation-id="ann.id"
                          @click.stop="selectAnnotation(ann)"></div>
                      </template>
                    </template>
                  </div>
                  <!-- drawing preview for right page -->
                  <div v-if="drawing" :style="getDrawingStyle('right')"
                    class="absolute bg-yellow-200 opacity-60 pointer-events-none">
                  </div>
                </div>
              </PdfPage>
            </template>
          </div>
        </template>

        <!-- continuous scroll mode (simple vertical stack for now) -->
        <template v-else>
          <div class="flex flex-col items-center gap-4">
            <template v-for="p in totalPages" :key="p">
              <PdfPage :pdfDoc="pdfDocRef" :pageNumber="p" :scale="scale" @rendered="onPdfPageRendered"
                @selection="onPdfPageSelection">
                <div class="pdf-overlay absolute top-0 left-0 w-full h-full">
                  <!-- render annotations for page p in continuous mode -->
                  <div :key="annotationRenderKey + '-c-' + p">
                    <template v-for="ann in annotations.filter(a => a.page === p)" :key="ann.id">
                      <template v-if="ann.rects && ann.rects.length">
                        <template v-for="(r, ri) in ann.rects" :key="ri">
                          <div class="absolute" :style="annotationBoxStyle(ann, r)" :data-annotation-id="ann.id"
                            @click.stop="selectAnnotation(ann)"></div>
                        </template>
                      </template>
                      <template v-else>
                        <div class="absolute" :style="annotationBoxStyle(ann, ann.rect)" :data-annotation-id="ann.id"
                          @click.stop="selectAnnotation(ann)"></div>
                      </template>
                    </template>
                  </div>
                  <!-- drawing preview for continuous mode (only on the page being drawn on) -->
                  <div v-if="drawing && isDrawingOnPage(p)" :style="getContinuousDrawingStyle(p)"
                    class="absolute bg-yellow-200 opacity-60 pointer-events-none"></div>
                </div>
              </PdfPage>
            </template>
          </div>
        </template>
      </div>
      <div v-else class="w-full h-full">
        <iframe :src="fallbackBlobUrl || ''" class="w-full h-full border-0" title="PDF 备用预览"></iframe>
      </div>
    </div>
    <!-- 高亮菜单（脱离调试面板，直接渲染） -->
    <HighlightMenu v-if="!loading && !error" :show="!!selectedAnnotation" :x="highlightMenuPos.x"
      :y="highlightMenuPos.y" :note="selectedAnnotation?.note" :current-color="selectedAnnotation?.color"
      @pick-color="onPickColorFromMenu" @add-note="onAddNoteFromMenu" @delete="deleteSelected" />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick, markRaw } from 'vue'
import { onUnmounted } from 'vue'
import HighlightMenu from '@/components/Reader/HighlightMenu.vue'
import PdfPage from '@/components/Reader/PdfPage.vue'
import { useRoute } from 'vue-router'
import { getPreviewUrl, getDownloadUrl } from '@/utils/signedUrls'

// route + fileId
const route = useRoute()
const fileId = computed(() => Number(route.params.id))

// pdf.js related refs
// IMPORTANT: pdfDoc MUST NOT be reactive (Vue3 Proxy breaks pdf.js private field access)
let pdfDoc: any = null
const pdfDocRef = ref<any>(null)
const page = ref<number>(1)
const totalPages = ref<number>(0)
const scale = ref<number>(1.0)
const canvasRef = ref<HTMLCanvasElement | null>(null)
const overlayRef = ref<HTMLDivElement | null>(null)
// when user is interacting (drawing) track the exact canvas element under the pointer
const drawCanvas = ref<HTMLCanvasElement | null>(null)
// which side the drawCanvas belongs to: 'left' | 'right' | null
const drawCanvasSide = ref<'left' | 'right' | null>(null)
// right-page refs used in two-page mode
const canvasRightRef = ref<HTMLCanvasElement | null>(null)
const overlayRightRef = ref<HTMLDivElement | null>(null)
// removed: annotationsHostRef/annotationsHostRefRight (unused)
// maps for continuous mode: page -> canvas/overlay
const canvasByPage = new Map<number, HTMLCanvasElement>()
const overlayByPage = new Map<number, HTMLDivElement>()

function setViewMode(mode: 'single' | 'two' | 'continuous') {
  if (viewMode.value === mode) return
  viewMode.value = mode
  // if switching to two-page, prefer odd-left pages
  if (mode === 'two' && page.value % 2 === 0) {
    page.value = Math.max(1, page.value - 1)
  }
  // re-render and sync overlays
  renderPage()
  setTimeout(() => syncOverlayToCanvas(), 30)
  // if entering continuous mode, scroll to current page for seamless continuity
  if (mode === 'continuous') {
    // wait a bit for onPdfPageRendered to populate maps
    setTimeout(() => scrollToPage(page.value), 60)
  }
}
// handler called when PdfPage emits a rendered event
function onPdfPageRendered(payload: { page: number, el: HTMLElement | null, canvasEl?: HTMLCanvasElement | null, overlayEl?: HTMLDivElement | null }) {
  const pg = payload?.page
  const el = payload?.el || null
  let canvas: HTMLCanvasElement | null = (payload as any)?.canvasEl || null
  let overlayEl: HTMLDivElement | null = (payload as any)?.overlayEl || null
  if (!canvas && el) {
    try { canvas = (el as HTMLElement).querySelector('canvas') as HTMLCanvasElement | null } catch { canvas = null }
  }
  if (!overlayEl && el) {
    try { overlayEl = (el as HTMLElement).querySelector('.pdf-overlay') as HTMLDivElement | null } catch { overlayEl = null }
  }

  if (canvas) {
    if (pg === page.value) canvasRef.value = canvas
    else if (pg === page.value + 1) canvasRightRef.value = canvas
    // record canvas for continuous mode
    try { if (typeof pg === 'number') canvasByPage.set(pg, canvas) } catch { }
  }
  if (overlayEl) {
    if (pg === page.value) overlayRef.value = overlayEl
    else if (pg === page.value + 1) overlayRightRef.value = overlayEl
    // record overlay for continuous mode
    try { if (typeof pg === 'number') overlayByPage.set(pg, overlayEl) } catch { }
  }

  nextTick(() => {
    syncOverlayToCanvas()
    forceRerenderAnnotations(3, 30)
    // align scroll when the current page finishes rendering in continuous mode
    if (viewMode.value === 'continuous' && typeof pg === 'number' && pg === page.value) {
      setTimeout(() => scrollToPage(page.value, 4), 20)
    }
  })
}

// handler called when PdfPage emits selection rects
function onPdfPageSelection(payload: { page: number, rects: Array<any> }) {
  // child emitted selection — mark to avoid duplicate parent-level handling
  sawChildSelection = true
  if (tool.value !== 'text') return
  if (!payload || !payload.rects || !payload.rects.length) return
  // create a single annotation object that contains an array of rects (one per line/clientRect)
  const ann = {
    id: Date.now() + Math.floor(Math.random() * 10000),
    page: payload.page,
    rects: payload.rects.map((r: any) => ({ x: Math.max(0, Math.min(1, r.x)), y: Math.max(0, Math.min(1, r.y)), w: Math.min(1, r.w), h: Math.min(1, r.h) })),
    color: currentColor.value,
    opacity: 0.6
  }
  annotations.value.push(ann)
  saveAnnotations(annotations.value)
  // for text tool, we created an annotation from selection — clear native selection and restore overlay pointer so menu can be clicked
  try { const sel = window.getSelection(); if (sel) sel.removeAllRanges() } catch { }
  try { setOverlayPointer('auto') } catch { }
}

function annotationBoxStyle(ann: any, rect: any) {
  // choose canvas based on mode and annotation page
  let canvas: HTMLCanvasElement | null = null
  if (viewMode.value === 'continuous' && ann && typeof ann.page === 'number') {
    canvas = canvasByPage.get(ann.page) || null
  } else if (viewMode.value === 'two' && ann && typeof ann.page === 'number') {
    canvas = (ann.page === page.value + 1) ? (canvasRightRef.value || canvasRef.value) : canvasRef.value
  } else {
    canvas = canvasRef.value
  }
  if (!canvas || !rect) return {}
  const cw = canvas.clientWidth || canvas.getBoundingClientRect().width || canvas.width
  const ch = canvas.clientHeight || canvas.getBoundingClientRect().height || canvas.height
  const left = rect.x * cw
  const top = rect.y * ch
  const w = rect.w * cw
  const h = rect.h * ch
  return ({ left: left + 'px', top: top + 'px', width: w + 'px', height: h + 'px', background: ann.color || 'rgba(250,216,120,0.5)', border: '1px solid rgba(0,0,0,0.06)', zIndex: 2, pointerEvents: (tool.value === 'select' || (selectedAnnotation.value && selectedAnnotation.value.id === ann.id)) ? 'auto' : 'none', opacity: ann.opacity ?? 0.6 } as any)
}
const containerRef = ref<HTMLDivElement | null>(null)
// The real scroll container is the full-height wrapper; use this for scrollTop/scroll events
const scrollRef = ref<HTMLDivElement | null>(null)
const meta = ref({ title: '' })
const loading = ref<boolean>(true)
const error = ref<string | null>(null)

const pdfArrayBuffer = ref<ArrayBuffer | null>(null)
const useIframeFallback = ref(false)
const fallbackBlobUrl = ref<string | null>(null)
let triedUrlFallback = false
// track current pdf.js render task so we can cancel it when starting a new render
let currentRenderTask: any = null

// annotation tool state
const tool = ref<'select' | 'rect' | 'circle' | 'text' | 'text_under'>('select')
const currentColor = ref<string>('#FAD878')
const drawing = ref(false)
const drawStart = ref<{ x: number, y: number } | null>(null)
const drawCurr = ref<{ x: number, y: number } | null>(null)
const selectedAnnotation = ref<any | null>(null)
// highlight menu position (viewport coords)
const highlightMenuPos = ref<{ x: number; y: number }>({ x: 0, y: 0 })
// flag to indicate a child PdfPage emitted selection this click cycle
let sawChildSelection = false
// programmatic scroll lock to avoid conflict with auto page detection
const scrollingToPage = ref<number | null>(null)
let scrollLockTimer: any = null

function beginProgrammaticScroll(targetPage: number, duration = 700) {
  scrollingToPage.value = targetPage
  if (scrollLockTimer) clearTimeout(scrollLockTimer)
  scrollLockTimer = setTimeout(() => { scrollingToPage.value = null }, duration)
}

// reactive annotations list (so UI updates immediately)
const annotations = ref<any[]>(loadAnnotations())
const pageAnnotations = computed(() => annotations.value.filter(a => a.page === page.value))
// key to force re-render only the annotations host (won't remount overlay or textLayer)
const annotationRenderKey = ref(0)

const formattedScale = computed(() => Math.round((scale.value || 1) * 100) + '%')
// view mode: single | two | continuous
const viewMode = ref<'single' | 'two' | 'continuous'>('single')
// two-page helpers
// rightPage is the sequential next page number; rendering of right page is gated by page < totalPages
const rightPage = computed(() => page.value + 1)
const canvasLeftWidth = computed(() => canvasRef.value ? canvasRef.value.clientWidth : 0)
const canvasLeftHeight = computed(() => canvasRef.value ? canvasRef.value.clientHeight : 0)
const canvasRightWidth = computed(() => canvasRightRef.value ? canvasRightRef.value.clientWidth : 0)
const canvasRightHeight = computed(() => canvasRightRef.value ? canvasRightRef.value.clientHeight : 0)

function zoomIn() {
  scale.value = Math.min(3, +(Math.round((scale.value + 0.25) * 100) / 100).toFixed(2))
  renderPage()
}

function zoomOut() {
  scale.value = Math.max(0.25, +(Math.round((scale.value - 0.25) * 100) / 100).toFixed(2))
  renderPage()
}

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

function selectAnnotation(ann: any) {
  selectedAnnotation.value = ann
  // compute highlight menu position based on annotation bbox
  // pick canvas based on annotation page (support two-page mode)
  let canvas = canvasRef.value
  if (viewMode.value === 'continuous' && ann && typeof ann.page === 'number') {
    canvas = canvasByPage.get(ann.page) || canvasRef.value
  } else if (viewMode.value === 'two' && ann && typeof ann.page === 'number') {
    canvas = (ann.page === page.value + 1) ? (canvasRightRef.value || canvasRef.value) : canvasRef.value
  }
  if (!canvas) return
  const crect = canvas.getBoundingClientRect()
  // support annotations that contain multiple rects (ann.rects) or a single rect (ann.rect)
  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity
  if (ann.rects && Array.isArray(ann.rects) && ann.rects.length) {
    for (const r of ann.rects) {
      minX = Math.min(minX, r.x)
      minY = Math.min(minY, r.y)
      maxX = Math.max(maxX, r.x + (r.w || 0))
      maxY = Math.max(maxY, r.y + (r.h || 0))
    }
  } else if (ann.rect) {
    const r = ann.rect
    minX = r.x; minY = r.y; maxX = r.x + (r.w || 0); maxY = r.y + (r.h || 0)
  } else {
    // fallback: use whole canvas
    minX = 0; minY = 0; maxX = 1; maxY = 0
  }
  if (!isFinite(minX) || !isFinite(minY) || !isFinite(maxX) || !isFinite(maxY)) return
  const left = crect.left + minX * crect.width
  const top = crect.top + minY * crect.height
  const w = (maxX - minX) * crect.width
  const h = (maxY - minY) * crect.height
  // place menu to the right of bbox; clamp to viewport if needed
  const menuX = Math.round(left + w + 8)
  const menuY = Math.round(top)
  highlightMenuPos.value = { x: menuX, y: menuY }
}

function onDocMouseDown(e: MouseEvent) {
  // if menu not visible, nothing to do
  if (!selectedAnnotation.value) return
  const overlay = overlayRef.value
  const overlayR = overlayRightRef.value
  const target = e.target as Node
  // if clicked inside either overlay, but not on an annotation element, hide the menu
  if ((overlay && overlay.contains(target)) || (overlayR && overlayR.contains(target))) {
    // check if click landed inside one of annotation elements
    let el: Node | null = target
    while (el && el !== overlay && el !== overlayR) {
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

function setOverlayPointer(value: 'auto' | 'none') {
  try {
    if (overlayRef.value) overlayRef.value.style.pointerEvents = value
    if (overlayRightRef.value) overlayRightRef.value.style.pointerEvents = value
    // also apply to all overlays (useful for continuous mode)
    const root = scrollRef.value || containerRef.value
    if (root) {
      const overlays = root.querySelectorAll('.pdf-overlay')
      overlays.forEach(el => ((el as HTMLElement).style.pointerEvents = value))
    }
  } catch { }
}

// prevent default dragstart when drawing or generally on the PDF container
function preventDragStart(e: DragEvent) {
  try { e.preventDefault() } catch { }
}

function preventSelectStart(e: Event) {
  // if we are drawing, prevent native selection/drag of selection
  if (drawing.value) {
    try { (e as Event).preventDefault() } catch { }
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
  // prefer the canvas the user interacted with (drawCanvas) when available
  const canvas = drawCanvas.value || canvasRef.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  const x = (evt.clientX - rect.left) / rect.width
  const y = (evt.clientY - rect.top) / rect.height
  return { x: Math.max(0, Math.min(1, x)), y: Math.max(0, Math.min(1, y)) }
}

function onMouseDown(e: MouseEvent) {
  // handle select or text tool
  if (tool.value === 'select') {
    // do not disable overlay pointers so annotation boxes remain clickable
    return
  }
  if (tool.value === 'text') {
    // Only disable overlay pointer if clicking outside an annotation element
    const target = e.target as HTMLElement | null
    let isOnAnnotation = false
    if (target) {
      let el: HTMLElement | null = target
      while (el && el !== containerRef.value) {
        if (el.dataset && el.dataset['annotationId']) { isOnAnnotation = true; break }
        el = el.parentElement
      }
    }
    if (!isOnAnnotation) setOverlayPointer('none')
    return
  }
  if (tool.value !== 'rect') return
  // prevent browser from starting a drag image/selection drag which causes forbidden cursor
  try { e.preventDefault() } catch { }
  drawing.value = true
  // locate the canvas under the pointer (supports multi-page/multi-canvas)
  const target = e.target as HTMLElement | null
  let clickedCanvas: HTMLCanvasElement | null = null
  try {
    // prefer closest canvas ancestor
    if (target && typeof target.closest === 'function') {
      clickedCanvas = target.closest('canvas') as HTMLCanvasElement | null
    }
  } catch { clickedCanvas = null }
  // if clicked on overlay, walk up to find nearest ancestor that contains a canvas
  if (!clickedCanvas && target) {
    let el: HTMLElement | null = target
    const stopAt = (scrollRef.value || containerRef.value)
    while (el && el !== stopAt) {
      try {
        const canv = el.querySelector ? (el.querySelector('canvas') as HTMLCanvasElement | null) : null
        if (canv) { clickedCanvas = canv; break }
      } catch { }
      el = el.parentElement
    }
  }
  // fallback: hit-test against all canvases in container using client coordinates
  const searchRoot = (scrollRef.value || containerRef.value)
  if (!clickedCanvas && searchRoot) {
    try {
      const canvases = Array.from(searchRoot.querySelectorAll('canvas')) as HTMLCanvasElement[]
      for (const c of canvases) {
        const r = c.getBoundingClientRect()
        if (e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom) {
          clickedCanvas = c
          break
        }
      }
    } catch { }
  }
  if (!clickedCanvas && searchRoot) clickedCanvas = searchRoot.querySelector('canvas') as HTMLCanvasElement | null
  drawCanvas.value = clickedCanvas
  // determine which side this canvas belongs to by dataset or equality with known refs
  try {
    if (clickedCanvas) {
      if (clickedCanvas === canvasRef.value) drawCanvasSide.value = 'left'
      else if (clickedCanvas === canvasRightRef.value) drawCanvasSide.value = 'right'
      else if (clickedCanvas.dataset && clickedCanvas.dataset['page']) {
        const pnum = Number(clickedCanvas.dataset['page'])
        drawCanvasSide.value = (pnum === rightPage.value) ? 'right' : 'left'
      } else drawCanvasSide.value = null
    } else drawCanvasSide.value = null
  } catch { drawCanvasSide.value = null }
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
      // compute normalized rect using CSS pixels (getBoundingClientRect)
      const canvas = drawCanvas.value || canvasRef.value
      if (canvas) {
        const crect = canvas.getBoundingClientRect()
        const nx = Math.min(sx, ex)
        const ny = Math.min(sy, ey)
        const nw = Math.abs(ex - sx)
        const nh = Math.abs(ey - sy)
        if (nw >= 0.005 && nh >= 0.005) {
          // determine actual page from the canvas used for interaction (drawCanvas)
          let annPage = page.value
          try {
            const usedCanvas = drawCanvas.value || canvas
            if (usedCanvas && usedCanvas.dataset && usedCanvas.dataset['page']) annPage = Number(usedCanvas.dataset['page']) || page.value
          } catch { }
          const ann = { id: Date.now(), page: annPage, rect: { x: nx, y: ny, w: nw, h: nh }, color: currentColor.value }
          annotations.value.push(ann)
          saveAnnotations(annotations.value)
        }
      }
    } finally {
      drawStart.value = null; drawCurr.value = null
      // clear the temporary interaction canvas reference
      drawCanvas.value = null
    }
  }
  // restore overlay pointer events after any interaction (delay slightly to avoid stomping native selection)
  try { setTimeout(() => setOverlayPointer('auto'), 100) } catch { }
  if (tool.value === 'text') {
    // only run parent-level selection handling if child PdfPage did not emit a selection
    setTimeout(() => { if (!sawChildSelection) handleTextSelection() }, 10)
  }
  // reset flag for next interaction cycle
  sawChildSelection = false
}

function getDrawingStyle(side: 'left' | 'right') {
  if (!drawing.value || !drawStart.value || !drawCurr.value) return {}
  if (drawCanvasSide.value && drawCanvasSide.value !== side) return {}
  const canvas = side === 'left' ? canvasRef.value : (canvasRightRef.value || null)
  if (!canvas) return {}
  const crect = canvas.getBoundingClientRect()
  const x = Math.min(drawStart.value.x, drawCurr.value.x) * crect.width
  const y = Math.min(drawStart.value.y, drawCurr.value.y) * crect.height
  const w = Math.abs(drawCurr.value.x - drawStart.value.x) * crect.width
  const h = Math.abs(drawCurr.value.y - drawStart.value.y) * crect.height
  return { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px', background: currentColor.value, opacity: 0.45 }
}

// Helpers for continuous mode drawing preview
function isDrawingOnPage(p: number) {
  if (!drawing.value || !drawCanvas.value) return false
  try {
    const dp = drawCanvas.value.dataset && drawCanvas.value.dataset['page'] ? Number(drawCanvas.value.dataset['page']) : NaN
    return isFinite(dp) && dp === p
  } catch {
    return false
  }
}

function getContinuousDrawingStyle(p: number) {
  if (!drawing.value || !drawStart.value || !drawCurr.value) return {}
  // ensure the current drawing interaction belongs to this page
  if (!isDrawingOnPage(p)) return {}
  const canvas = canvasByPage.get(p) || null
  if (!canvas) return {}
  const crect = canvas.getBoundingClientRect()
  const x = Math.min(drawStart.value.x, drawCurr.value.x) * crect.width
  const y = Math.min(drawStart.value.y, drawCurr.value.y) * crect.height
  const w = Math.abs(drawCurr.value.x - drawStart.value.x) * crect.width
  const h = Math.abs(drawCurr.value.y - drawStart.value.y) * crect.height
  return { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px', background: currentColor.value, opacity: 0.45 }
}

// load pdf.js dynamically
let pdfjsLib: any = null
async function resolvePreviewUrl(id: number) { return await getPreviewUrl(id) }

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
    // Fetch arrayBuffer first via signed or direct URL
    const resp = await fetch(await resolvePreviewUrl(fileId.value), { credentials: 'include' })
    if (!resp.ok) throw new Error(`Failed to fetch PDF: ${resp.status}`)
    const arrayBuffer = await resp.arrayBuffer()
    pdfArrayBuffer.value = arrayBuffer
    const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer })
    pdfDoc = await loadingTask.promise
    // expose raw pdfDoc to children to avoid Vue proxying pdf.js internals
    pdfDocRef.value = markRaw(pdfDoc)
    totalPages.value = pdfDoc.numPages
    meta.value.title = `File ${fileId.value}`
    // load annotations from storage into reactive ref
    annotations.value = loadAnnotations()
  } catch (err: any) {
    console.error('PDF load error', err)
    error.value = err?.message || String(err)
  } finally {
    loading.value = false
  }
}

async function renderPage() {
  if (!pdfDoc) return
  try {
    // Clear previous right-side refs; child will emit rendered to set them again
    canvasRightRef.value = null
    overlayRightRef.value = null
    // in case of re-render, do not reuse stale page->canvas for continuous; rebuild lazily as pages emit rendered
    // keep existing entries but they will be overwritten; optionally could clear here if needed
    // Delegated to PdfPage component: ensure child reflow and overlay sync after child emits
    await nextTick()
    // child will emit rendered -> onPdfPageRendered will sync overlay and annotations
  } catch (err: any) {
    console.error('renderPage error', err)
    // fallback: show iframe with blob URL so user can view PDF even if pdf.js failed
    if (!triedUrlFallback) {
      // try reloading via URL (let pdf.js handle range/worker) before blob fallback
      triedUrlFallback = true
      try {
        const loadingTask2 = pdfjsLib.getDocument({ url: await resolvePreviewUrl(fileId.value), withCredentials: true })
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
  if (viewMode.value === 'two') {
    if (page.value <= 1) return
    page.value = Math.max(1, page.value - 2)
  } else {
    if (page.value <= 1) return
    page.value--
  }
  renderPage()
  saveProgressDebounced()
}

function nextPage() {
  if (viewMode.value === 'two') {
    if (page.value >= totalPages.value) return
    page.value = Math.min(totalPages.value, page.value + 2)
  } else {
    if (page.value >= totalPages.value) return
    page.value++
  }
  renderPage()
  if (viewMode.value === 'continuous') {
    // scroll to the new current page in continuous mode
    setTimeout(() => scrollToPage(page.value), 10)
  }
  saveProgressDebounced()
}

async function download() {
  if (!fileId.value) return
  try { window.location.href = await getDownloadUrl(fileId.value) } catch { }
}

// Simple localStorage-backed annotations for demo: keyed by fileId
function annotationsKey() { return `pdf:annotations:${fileId.value}` }
function loadAnnotations(): any[] {
  try { return JSON.parse(localStorage.getItem(annotationsKey()) || '[]') } catch { return [] }
}
function saveAnnotations(list: any[]) { localStorage.setItem(annotationsKey(), JSON.stringify(list)) }

// removed legacy renderTextLayer: text selection handled in PdfPage.vue

// ensure overlay size matches canvas and force a reflow so annotations position correctly
function syncOverlayToCanvas() {
  // helper to sync a single canvas/overlay pair and optionally the container
  const syncSingle = (c: HTMLCanvasElement | null, o: HTMLDivElement | null, applyToContainer = false) => {
    if (!c || !o) return
    o.style.width = c.style.width
    o.style.height = c.style.height
    if (applyToContainer && containerRef.value) {
      containerRef.value.style.width = c.style.width
      containerRef.value.style.height = c.style.height
    }
    // force reflow
    // eslint-disable-next-line @typescript-eslint/no-unused-expressions
    o.offsetHeight
  }

  if (viewMode.value === 'two') {
    // sync left
    syncSingle(canvasRef.value, overlayRef.value, true)
    // sync right (don't resize container again)
    syncSingle(canvasRightRef.value, overlayRightRef.value, false)
  } else {
    syncSingle(canvasRef.value, overlayRef.value, true)
  }
}

// when using text tool, capture native selection on mouseup and convert to annotation(s)
function handleTextSelection() {
  if (tool.value !== 'text') return
  const sel = window.getSelection()
  if (!sel || sel.isCollapsed) return
  let range: Range
  try { range = sel.getRangeAt(0) } catch { return }
  const clientRects = Array.from(range.getClientRects())
  if (!clientRects.length) return
  // find the canvas associated with this selection by walking up the DOM
  let node: Node | null = sel.anchorNode
  let el: HTMLElement | null = (node as HTMLElement) || (node && (node as any).parentElement) || null
  let selCanvas: HTMLCanvasElement | null = null
  try {
    const stopAt = (scrollRef.value || containerRef.value)
    while (el && el !== stopAt) {
      const c = el.querySelector ? (el.querySelector('canvas') as HTMLCanvasElement | null) : null
      if (c) { selCanvas = c; break }
      el = el.parentElement
    }
  } catch { }
  // fallback: choose canvas by which overlay contains the selection anchor/focus
  if (!selCanvas) {
    const anchorInRight = overlayRightRef.value && sel.anchorNode ? overlayRightRef.value.contains(sel.anchorNode) : false
    const focusInRight = overlayRightRef.value && sel.focusNode ? overlayRightRef.value.contains(sel.focusNode) : false
    if (anchorInRight || focusInRight) selCanvas = canvasRightRef.value || null
  }
  // final fallback to left canvas
  if (!selCanvas) selCanvas = canvasRef.value
  if (!selCanvas) return
  const crect = selCanvas.getBoundingClientRect()
  const annPage = (selCanvas.dataset && selCanvas.dataset['page']) ? Number(selCanvas.dataset['page']) : page.value
  // convert rects relative to this canvas
  const rects: any[] = []
  const minPx = 2
  for (const r of clientRects) {
    // intersect with canvas bounds to avoid adding rects from the other page
    const left = Math.max(r.left, crect.left)
    const right = Math.min(r.right, crect.right)
    const top = Math.max(r.top, crect.top)
    const bottom = Math.min(r.bottom, crect.bottom)
    const iw = right - left
    const ih = bottom - top
    if (iw <= minPx || ih <= minPx) continue
    const rx = (left - crect.left) / crect.width
    const ry = (top - crect.top) / crect.height
    const rw = iw / crect.width
    const rh = ih / crect.height
    rects.push({ x: Math.max(0, Math.min(1, rx)), y: Math.max(0, Math.min(1, ry)), w: Math.min(1, rw), h: Math.min(1, rh) })
  }
  if (!rects.length) return
  const ann = { id: Date.now() + Math.floor(Math.random() * 10000), page: annPage, rects, color: currentColor.value, opacity: 0.6 }
  annotations.value.push(ann)
  saveAnnotations(annotations.value)
  try { sel.removeAllRanges() } catch { }
}

// Scroll the container so that the given page's canvas is aligned near the top
function scrollToPage(p: number, retries = 0) {
  const sc = (scrollRef.value || containerRef.value)
  if (!sc) return
  const canvas = canvasByPage.get(p) || null
  if (!canvas) {
    if (retries > 0) setTimeout(() => scrollToPage(p, retries - 1), 40)
    return
  }
  const containerRect = sc.getBoundingClientRect()
  const canvasRect = canvas.getBoundingClientRect()
  const diff = canvasRect.top - containerRect.top
  const offset = 8
  const target = sc.scrollTop + (diff - offset)
  beginProgrammaticScroll(p)
  try {
    sc.scrollTo({ top: target, behavior: 'smooth' })
  } catch {
    // fallback if smooth not supported
    sc.scrollTop = target
  }
}

let scrollRaf = 0
function onContainerScroll() {
  const sc = (scrollRef.value || containerRef.value)
  if (viewMode.value !== 'continuous' || !sc) return
  if (scrollRaf) return
  scrollRaf = requestAnimationFrame(() => {
    scrollRaf = 0
    const container = sc!
    const cRect = container.getBoundingClientRect()
    const containerTop = cRect.top
    let bestTopPage: number | null = null
    let bestTopDist = Infinity
    let firstBelow: number | null = null
    canvasByPage.forEach((canvas, p) => {
      const r = canvas.getBoundingClientRect()
      // page whose top is closest to containerTop
      const dist = Math.abs(r.top - containerTop)
      if (r.top <= containerTop) {
        if (dist < bestTopDist) { bestTopDist = dist; bestTopPage = p }
      }
      if (firstBelow == null && r.top > containerTop) {
        firstBelow = p
      }
    })
    const newPage = (bestTopPage ?? firstBelow ?? page.value)
    // if we are performing a programmatic scroll, don't override the page until we reach the target
    if (scrollingToPage.value != null) {
      if (newPage === scrollingToPage.value) {
        page.value = newPage
        saveProgressDebounced()
        // reached target; release lock sooner
        scrollingToPage.value = null
        if (scrollLockTimer) { clearTimeout(scrollLockTimer); scrollLockTimer = null }
      }
      return
    }
    if (newPage !== page.value) {
      page.value = newPage
      saveProgressDebounced()
    }
  })
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
  // restore progress before initializing so first render targets the saved page
  const p = loadProgress()
  if (p && p.page) {
    page.value = p.page
  }
  await initPdf()
  // no manual addEventListener — template has @click binding
  // render after init
  await renderPage()
  // ensure overlay sync after initial render
  setTimeout(() => syncOverlayToCanvas(), 50)
  // also do a stronger reflow & annotation re-render to ensure positions are correct on first load
  await forceRerenderAnnotations(4, 30)
  // if continuous mode, scroll to the restored page once canvases are present
  if (viewMode.value === 'continuous') {
    setTimeout(() => scrollToPage(page.value), 80)
  }
  // global mouseup to ensure drawing ends even if cursor leaves canvas
  window.addEventListener('mouseup', onWindowMouseUp)
  // click outside to hide menu
  document.addEventListener('mousedown', onDocMouseDown)
  // prevent dragstart on the container (and descendants) to avoid browser drag images
  try { if (containerRef.value) containerRef.value.addEventListener('dragstart', preventDragStart) } catch { }
  // prevent selectstart while drawing
  document.addEventListener('selectstart', preventSelectStart)
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
  try { if (containerRef.value) containerRef.value.removeEventListener('dragstart', preventDragStart) } catch { }
  document.removeEventListener('selectstart', preventSelectStart)
  // cancel any in-flight render
  try { if (currentRenderTask && typeof currentRenderTask.cancel === 'function') currentRenderTask.cancel() } catch { }
})

watch(scale, () => {
  // when scale changes, re-render and force overlay sync
  // In continuous mode, keep current scroll position; in other modes, scroll to top to avoid clipped top
  if (viewMode.value !== 'continuous') {
    const sc = (scrollRef.value || containerRef.value)
    if (sc) sc.scrollTop = 0
  }
  const current = page.value
  renderPage()
  setTimeout(() => {
    syncOverlayToCanvas()
    if (viewMode.value === 'continuous') scrollToPage(current)
  }, 30)
})
</script>
