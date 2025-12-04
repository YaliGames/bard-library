<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed z-[1000] rounded-lg shadow-xl flex flex-col gap-2 min-w-[240px] select-none"
      :style="wrapperStyle"
      @mousedown.stop
    >
      <!-- 颜色选择 -->
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-1.5">
          <button
            v-for="c in palette"
            :key="c"
            class="w-5 h-5 rounded-full border transition-transform hover:scale-110"
            :style="swatchStyle(c)"
            @click="$emit('pick-color', c)"
          />
        </div>
        <button
          @click="$emit('delete')"
          class="text-xs text-red-300 hover:text-red-100 px-2 py-1 rounded hover:bg-white/10 transition-colors"
        >
          删除
        </button>
      </div>

      <!-- 笔记输入 -->
      <div class="relative">
        <textarea
          v-model="noteContent"
          placeholder="添加批注..."
          class="w-full h-20 text-sm p-2 rounded bg-black/20 text-white resize-none outline-none border border-transparent focus:border-white/30 placeholder-gray-400"
          @keydown.stop
        ></textarea>
        <button
          v-if="isDirty"
          @click="saveNote"
          class="absolute bottom-2 right-2 text-xs bg-blue-600 hover:bg-blue-500 text-white px-2 py-1 rounded shadow-sm"
        >
          保存
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'

const props = defineProps<{
  show: boolean
  x: number
  y: number
  note?: string
  currentColor?: string
}>()

const emit = defineEmits<{
  'add-note': [note: string]
  'pick-color': [color: string]
  'delete': []
}>()

const defaultPalette = ['#FAD878', '#A0E7E5', '#B4F8C8', '#FBE7C6', '#FFD6E7', '#C9C9FF']
const palette = defaultPalette
const noteContent = ref('')

const isDirty = computed(() => noteContent.value !== (props.note || ''))

const wrapperStyle = computed(() => ({
  left: `${props.x}px`,
  top: `${props.y}px`,
  transform: 'translate(-50%, -100%)',
  background: '#333',
  color: '#fff',
  padding: '8px 10px',
  boxShadow: '0 4px 12px rgba(0,0,0,.25)',
}))

watch(
  () => props.show,
  (val) => {
    if (val) {
      noteContent.value = props.note || ''
    }
  }
)

function swatchStyle(c: string) {
  const sel = (props.currentColor || '').toLowerCase() === c.toLowerCase()
  return {
    background: c,
    borderColor: sel ? '#fff' : 'rgba(255,255,255,.45)',
    outline: sel ? '2px solid #fff' : 'none',
    cursor: 'pointer',
  }
}

function saveNote() {
  emit('add-note', noteContent.value)
}
</script>
