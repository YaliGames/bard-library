<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed z-[100] bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-2 flex flex-col gap-2 min-w-[200px]"
      :style="{ left: x + 'px', top: y + 'px' }"
      @mousedown.stop
    >
      <!-- 颜色选择 -->
      <div class="flex justify-between gap-2">
        <button
          v-for="color in colors"
          :key="color"
          class="w-6 h-6 rounded-full border border-gray-200 dark:border-gray-600 hover:scale-110 transition-transform"
          :style="{ backgroundColor: color }"
          :class="{ 'ring-2 ring-offset-1 ring-blue-500': currentColor === color }"
          @click="$emit('pick-color', color)"
        ></button>
      </div>

      <!-- 笔记输入 -->
      <textarea
        v-model="noteContent"
        placeholder="添加笔记..."
        class="w-full h-20 text-sm p-2 border border-gray-200 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-900 resize-none outline-none focus:border-blue-500"
        @keydown.stop
      ></textarea>

      <!-- 操作按钮 -->
      <div class="flex justify-between items-center">
        <button
          @click="$emit('delete')"
          class="text-xs text-red-500 hover:text-red-600 px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"
        >
          删除
        </button>
        <button
          @click="saveNote"
          class="text-xs text-white bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded"
        >
          保存
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

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

const colors = ['#ffeb3b', '#ff9800', '#f44336', '#4caf50', '#2196f3']
const noteContent = ref('')

watch(
  () => props.show,
  (val) => {
    if (val) {
      noteContent.value = props.note || ''
    }
  }
)

function saveNote() {
  emit('add-note', noteContent.value)
}
</script>
