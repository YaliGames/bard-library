<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed z-[100] bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-1 flex gap-1"
      :style="{ left: x + 'px', top: y + 'px' }"
      @mousedown.stop
    >
      <button
        v-for="action in actions"
        :key="action.label"
        @click="action.handler"
        class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors flex items-center gap-1"
      >
        <span v-if="action.icon" class="material-symbols-outlined text-base">{{ action.icon }}</span>
        {{ action.label }}
      </button>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
interface Action {
  label: string
  icon?: string
  handler: () => void
}

defineProps<{
  show: boolean
  x: number
  y: number
  actions: Action[]
}>()
</script>
