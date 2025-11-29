<template>
  <div :class="['flex items-center justify-between gap-2', $attrs.class]">
    <el-button :size="size" :disabled="!hasPrev" @click="$emit('prev')" plain>上一章</el-button>
    <el-button :size="size" @click="$emit('back')" plain>返回图书</el-button>
    <el-button :size="size" :disabled="!hasNext" @click="$emit('next')" plain>下一章</el-button>
  </div>
</template>

<script setup lang="ts">
interface Props {
  hasPrev: boolean
  hasNext: boolean
  size?: 'default' | 'small'
}
withDefaults(defineProps<Props>(), {
  size: 'default',
})

defineEmits<{
  (e: 'prev'): void
  (e: 'back'): void
  (e: 'next'): void
}>()
</script>
