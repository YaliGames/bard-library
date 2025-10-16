<template>
  <div v-if="show" :style="wrapperStyle" @mousedown.stop>
    <div class="flex items-start gap-3">
      <div class="flex flex-col gap-1">
        <button class="btn" @click="$emit('add-note')">添加批注</button>
        <div class="text-xs text-gray-200 max-w-[360px] whitespace-pre-wrap" v-if="note && note.trim()">
          {{ note }}
        </div>
        <div class="text-xs text-gray-400" v-else>暂无批注</div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-300">颜色</span>
        <div class="flex items-center gap-1.5">
          <button v-for="c in palette" :key="c" class="w-5 h-5 rounded-full border"
                  :style="swatchStyle(c)" @click="$emit('pick-color', c)" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ show: boolean; x: number; y: number; note?: string | null; palette: string[]; currentColor?: string | null }>()
defineEmits<{ (e:'add-note'): void; (e:'pick-color', color: string): void; (e:'close'): void }>()

const wrapperStyle = computed(() => ({
  position: 'fixed',
  left: `${props.x}px`,
  top: `${props.y}px`,
  transform: 'translate(-50%, -100%)',
  background: '#333',
  color: '#fff',
  borderRadius: '8px',
  padding: '8px 10px',
  boxShadow: '0 4px 12px rgba(0,0,0,.25)',
  zIndex: 1000,
  userSelect: 'none',
  maxWidth: '520px',
} as const))

function swatchStyle(c: string){
  const sel = (props.currentColor || '').toLowerCase() === c.toLowerCase()
  return {
    background: c,
    borderColor: sel ? '#fff' : 'rgba(255,255,255,.45)',
    outline: sel ? '2px solid #fff' : 'none',
    cursor: 'pointer',
  }
}
</script>
<style scoped>
.btn{ background:transparent; color:#fff; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; }
.btn:hover{ background: rgba(255,255,255,.08); }
</style>
