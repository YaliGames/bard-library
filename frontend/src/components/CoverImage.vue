<template>
  <div :class="wrapperClass" :style="wrapperStyle">
    <img v-if="displaySrc" :src="displaySrc" :alt="alt" class="w-full h-full object-cover" />
    <template v-else>
      <div v-if="renderPlaceholder" class="placeholder relative w-full h-full flex flex-col" :style="placeholderStyle">
        <div class="image absolute right-0 bottom-[6%] left-[14%] top-1/2 bg-no-repeat bg-right-bottom bg-contain"></div>
        <div class="texture absolute inset-0 pointer-events-none"></div>
        <div class="flex-grow"></div>
        <div class="bg-white/25 px-[5%] py-[5%]">
          <div class="title" :style="titleStyle">{{ displayTitle }}</div>
        </div>
        <div v-if="displayAuthor" class="author pl-[5%] pr-[2%] mt-[4%]" :style="authorStyle">{{ displayAuthor }}</div>
        <div class="flex-grow-[3.6]"></div>
      </div>
      <!-- 回退为原始图标样式（book_2） -->
      <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
        <span class="material-symbols-outlined" :style="{ fontSize: iconSizeComputed }">book_2</span>
      </div>
    </template>
    <div class="absolute top-2 right-2 flex gap-1 flex-wrap justify-end">
      <slot name="overlay"></slot>
    </div>
  </div>
  
</template>
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { getPreviewUrl } from '@/utils/signedUrls'

const props = defineProps<{
  fileId?: number | null
  alt?: string
  aspect?: string // e.g. '3/4'
  rounded?: boolean
  class?: string
  iconSize?: string
  fontSize?: string
  title?: string
  authors?: string | string[]
  mode?: 'auto' | 'placeholder' | 'icon' // auto：有标题时占位，否则图标
}>()

// 默认 3:4（宽:高）
const aspect = computed(() => props.aspect || '3/4')
const wrapperClass = computed(() => [
  'relative w-full overflow-hidden bg-gray-100 select-none',
  props.rounded !== false ? 'rounded' : '',
  props.class || '',
].join(' ').trim())

const wrapperStyle = computed(() => {
  const ratio = String(aspect.value).replace('/', ' / ')
  return { aspectRatio: ratio }
})

const displaySrc = ref<string>('')
let ticket = 0
function preload(url: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve()
    img.onerror = () => reject(new Error('image load failed'))
    img.src = url
  })
}

async function resolveCover() {
  const fid = props.fileId
  const my = ++ticket
  if (!fid) { displaySrc.value = ''; return }
  try {
    const url = await getPreviewUrl(Number(fid))
    await preload(url)
    if (my === ticket) displaySrc.value = url
  } catch {
    // 回退直链再尝试一次
    try {
      const fallback = `/api/v1/files/${fid}/preview`
      await preload(fallback)
      if (my === ticket) displaySrc.value = fallback
    } catch {
      if (my === ticket) displaySrc.value = ''
    }
  }
}
watch(() => props.fileId, resolveCover, { immediate: true })

const iconSizeComputed = computed(() => props.fontSize || props.iconSize || '48px')

// ---------- Placeholder generation (Z-LIB-like) ----------
const mode = computed(() => props.mode || 'auto')
const rawTitle = computed(() => (props.title || props.alt || '').trim())
const displayTitle = computed(() => rawTitle.value || '未命名图书')
const displayAuthor = computed(() => {
  const a = props.authors
  if (!a) return ''
  if (Array.isArray(a)) return a.filter(Boolean).join(', ')
  return String(a).trim()
})
const renderPlaceholder = computed(() => {
  if (mode.value === 'placeholder') return true
  if (mode.value === 'icon') return false
  // auto：有标题则使用占位
  return !!rawTitle.value
})

function hashString(s: string): number {
  let h = 0
  for (let i = 0; i < s.length; i++) {
    h = (h << 5) - h + s.charCodeAt(i)
    h |= 0
  }
  return Math.abs(h)
}

function hsl(h: number, s: number, l: number) {
  return `hsl(${h} ${s}% ${l}%)`
}

const theme = computed(() => {
  const seed = hashString(`${displayTitle.value}·${displayAuthor.value}`)
  const baseHue = seed % 360
  const start = hsl(baseHue, 45, 55)
  const end = hsl((baseHue + 40) % 360, 85, 92)
  const accent = hsl((baseHue + 320) % 360, 55, 40)
  return { start, end, accent }
})

const placeholderStyle = computed(() => ({
  background: `linear-gradient(313deg, ${theme.value.start} 0%, ${theme.value.end} 100%)`,
}))

function px(n: number) { return `${n}px` }
function parsePx(s?: string | null): number | null {
  if (!s) return null
  const m = String(s).match(/^(\d+(?:\.\d+)?)px$/)
  return m ? Number(m[1]) : null
}

const titleFontSize = computed(() => {
  const p = parsePx(props.fontSize)
  if (p) return px(p)
  if (props.fontSize && !p) return props.fontSize as string
  const len = displayTitle.value.length
  if (len > 60) return '13px'
  if (len > 28) return '15px'
  return '17px'
})

const authorFontSize = computed(() => {
  const p = parsePx(props.fontSize)
  if (p) return px(Math.max(10, p - 4))
  if (props.fontSize && !p) return props.fontSize as string
  const len = displayAuthor.value.length
  if (len > 60) return '11px'
  if (len > 28) return '12px'
  return '13px'
})

const titleStyle = computed(() => ({
  color: theme.value.accent,
  fontSize: titleFontSize.value,
  opacity: 1,
}))

const authorStyle = computed(() => ({
  color: theme.value.accent,
  fontSize: authorFontSize.value,
  opacity: 1,
}))
</script>

<style scoped>
.material-symbols-outlined { line-height: 1; }

/* Placeholder layout inspired by Z-LIB */
.placeholder {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.placeholder .title {
  line-height: 1.2em;
  text-align: left;
  white-space: initial;
  overflow: hidden;
  line-clamp: 3;
  -webkit-line-clamp: 3;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  text-shadow: 1px 1px 0px #FFFFFF44;
  font-weight: bold;
  word-break: keep-all;
}

.placeholder .author {
  line-height: 1.2em;
  text-align: left;
  white-space: initial;
  overflow: hidden;
  line-clamp: 3;
  -webkit-line-clamp: 3;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  max-height: 3.5em;
  text-shadow: 1px 1px 0px #FFFFFF44;
}

.placeholder .image {
  /* decorative image area (optional) */
  position: absolute;
  right: 0;
  bottom: 6%;
  left: 14%;
  top: 50%;
  background-repeat: no-repeat;
  background-position: right bottom;
  background-size: contain;
  /* a simple geometric svg as fallback */
  background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAVwAAACECAMAAADr0mPoAAAAOVBMVEUAAAD///////////////////////////////////////////////////////////////////////8KOjVvAAAAEnRSTlMAIL+AQBCgr2CQcDBQ0N/vb8AGJxVGAAAT1ElEQVR42uyci3qjNhCFmYtmNELCWb//w7YIzIBBkLibbpJv52vX2QYS++dw5iLR7gcH9JFEEFGxho7/CKXMofsbL0fgJIhCkeGZIzAnU0RL/HwSjMFcX7q/cQg2WtGUr/D08TZUcScSVURUETGaQkTvt7+An4KtSHoHlT7hvehwv2HKPexcIlChv9bxRBaJ3wNWbsPI1WLjMsSSup8Z/cuaJXiHZWARIkFrJzXWH6tauL3ks2p8Dbag5UyobhzHaAF6ZoYfhxiwfBztpUMGtoLEEOWGKZwcl/CuqHNiExS0/IPyGrKcQUpDeNBCe6BVvpJ1MQ4h4u1G/dkFwD3LPptI/BkatpzjiUKVKnoWTT2Toz3VIo5EWfCGbUiBRZvJMGTRH8CXqBNoGYYp9wpdF7VmrtoEvBU+1SxWFwg0mCmfJMPLHBZR+u5bxxt1nTY+PwqzInX54a8aOsZ0Wj/8qoeyaibl15KhB4jE7vtGtK7j1ErhrNQjuMQ0mIUTYoPxfGp/hpaG9xdeEb8t3n70U4EGWrTQqSksVApyW7M3zKHiUIJTtGXC9dPx9hjGe+8QbTCErqNbWnMJLbR4M547LQpRXkcLuY7VaohR5L4LJN+wNgMdYe1IZKUwpy0a4F3WeJuxc6HA2nblc7SctEiKy+ABes4kiGoDdV8/3sKaCUL986ATBay0SMN7+gmNfmrDlRf0Z710qz8OTIpfvS5jlB3bTmDf5L9pP1Vp70E7zEIFtVDPbASghbMcx903jmBawo5tlB1awAmX0TvQ3hAe+oUONLWPRD7RPtdX4DH67+ewjIywZesvNeaa663003fo2mVut7T89FHw0HaEdIoWoiEKUYyRyFDR0vdR8gjO8o5tp9mFXdEEsfBO3TJimWWrFE5lqxKaF6gwW5G4GzAQqn2LlbdRU2+0Z0vkR3D9TGWife23gGYW5nP7tmzPG2ZWtcG4OWNX/epezBhH7W5rsDEy+hETBgTvii9KhH5WKmiqf7yQyIINBS/gMYnk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);
}

.texture {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url(data:image/png;base64,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);
  background-size: 6px 6px;
  background-blend-mode: multiply;
  mix-blend-mode: multiply;
  pointer-events: none;
}
</style>
