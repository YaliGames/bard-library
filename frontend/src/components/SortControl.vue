<template>
  <div class="flex items-center gap-2">
    <el-select
      :model-value="sort"
      placeholder="选择排序"
      @update:model-value="handleSortChange"
      class="min-w-[150px]"
    >
      <el-option label="创建时间" value="created" />
      <el-option label="修改时间" value="modified" />
      <el-option label="评分" value="rating" />
      <el-option label="ID" value="id" />
    </el-select>
    <el-button @click="handleToggleOrder">
      <span class="material-symbols-outlined" v-if="sort === 'created'">
        {{ order === 'desc' ? 'clock_arrow_down' : 'clock_arrow_up' }}
      </span>
      <span class="material-symbols-outlined" v-else-if="sort === 'modified'">
        {{ order === 'desc' ? 'edit_arrow_down' : 'edit_arrow_up' }}
      </span>
      <span class="material-symbols-outlined" v-else>
        {{ order === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
      </span>
    </el-button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  sort: 'created' | 'modified' | 'rating' | 'id'
  order: 'asc' | 'desc'
}>()

const emit = defineEmits<{
  (e: 'update:sort', value: 'created' | 'modified' | 'rating' | 'id'): void
  (e: 'update:order', value: 'asc' | 'desc'): void
  (e: 'change'): void
}>()

function handleSortChange(value: 'created' | 'modified' | 'rating' | 'id') {
  emit('update:sort', value)
  emit('change')
}

function handleToggleOrder() {
  emit('update:order', props.order === 'desc' ? 'asc' : 'desc')
  emit('change')
}
</script>

<style scoped></style>
