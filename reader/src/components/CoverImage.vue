<template>
  <div :class="wrapperClass" :style="wrapperStyle">
    <img v-if="displaySrc" :src="displaySrc" :alt="alt" class="w-full h-full object-cover" />
    <template v-else>
      <div
        v-if="renderPlaceholder"
        class="placeholder relative w-full h-full flex flex-col"
        :style="placeholderStyle"
      >
        <div
          class="image absolute right-0 bottom-[6%] left-[14%] top-1/2 bg-no-repeat bg-right-bottom bg-contain"
        ></div>
        <div class="texture absolute inset-0 pointer-events-none"></div>
        <div class="flex-grow"></div>
        <div class="bg-white/25 px-[5%] py-[5%]">
          <div class="title" :style="titleStyle">{{ displayTitle }}</div>
        </div>
        <div v-if="displayAuthor" class="author pl-[5%] pr-[2%] mt-[4%]" :style="authorStyle">
          {{ displayAuthor }}
        </div>
        <div class="flex-grow-[3.6]"></div>
      </div>
      <div v-else class="w-full h-full flex items-center justify-center text-gray-300 bg-gray-100">
        <span class="text-6xl">📚</span>
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
  aspect?: string
  rounded?: boolean
  class?: string
  iconSize?: string
  fontSize?: string
  title?: string
  authors?: string | string[]
  mode?: 'auto' | 'placeholder' | 'icon'
}>()

const aspect = computed(() => props.aspect || '3/4')
const wrapperClass = computed(() =>
  [
    'relative w-full overflow-hidden bg-gray-100 select-none',
    props.rounded !== false ? 'rounded' : '',
    props.class || '',
  ]
    .join(' ')
    .trim(),
)

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
  if (!fid) {
    displaySrc.value = ''
    return
  }
  try {
    const url = await getPreviewUrl(Number(fid))
    await preload(url)
    if (my === ticket) displaySrc.value = url
  } catch {
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

function px(n: number) {
  return `${n}px`
}
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
  text-shadow: 1px 1px 0px #ffffff44;
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
  text-shadow: 1px 1px 0px #ffffff44;
}

.texture {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: 6px 6px;
  background-blend-mode: multiply;
  mix-blend-mode: multiply;
  pointer-events: none;
}
</style>
