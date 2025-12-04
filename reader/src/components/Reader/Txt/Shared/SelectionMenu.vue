<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed z-[1000] bg-gray-800 text-white rounded-lg shadow-xl p-1.5 flex gap-1.5 items-center select-none"
      :style="{ left: x + 'px', top: y + 'px', transform: 'translate(-50%, -100%)' }"
      @mousedown.stop
    >
      <button
        v-for="action in actions"
        :key="action.key"
        @click="action.onClick"
        class="px-2 py-1 text-sm text-white hover:bg-white/10 rounded transition-colors flex items-center gap-1 bg-transparent border-none cursor-pointer"
      >
        <span v-if="action.icon" class="material-symbols-outlined text-base">{{ action.icon }}</span>
        {{ action.label }}
      </button>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
export interface SelectionAction {
  key: string
  label: string
  icon?: string
  onClick: () => void
}

defineProps<{
  show: boolean
  x: number
  y: number
  actions: SelectionAction[]
}>()
</script>
