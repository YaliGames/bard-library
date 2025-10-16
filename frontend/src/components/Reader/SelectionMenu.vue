<template>
  <div v-if="show" :style="wrapperStyle" @mousedown.stop>
    <template v-for="act in actions" :key="act.key">
      <button class="btn" @click="act.onClick()">{{ act.label }}</button>
    </template>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'

export interface SelectionAction { key: string; label: string; onClick: () => void }

const props = defineProps<{ show: boolean; x: number; y: number; actions: SelectionAction[] }>()

const wrapperStyle = computed(() => ({
  position: 'fixed',
  left: `${props.x}px`,
  top: `${props.y}px`,
  transform: 'translate(-50%, -100%)',
  background: '#333',
  color: '#fff',
  borderRadius: '8px',
  padding: '6px 8px',
  boxShadow: '0 4px 12px rgba(0,0,0,.25)',
  zIndex: 1000,
  display: 'flex',
  gap: '6px',
  alignItems: 'center',
  userSelect: 'none',
} as const))
</script>
<style scoped>
.btn{ background:transparent; color:#fff; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; }
.btn:hover{ background: rgba(255,255,255,.08); }
</style>
