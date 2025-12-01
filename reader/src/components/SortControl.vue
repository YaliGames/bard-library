<template>
  <div class="flex items-center gap-2">
    <el-select
      :model-value="sort"
      placeholder="选择排序"
      @update:model-value="handleSortChange"
      class="min-w-[120px]"
    >
      <el-option label="创建时间" value="created" />
      <el-option label="修改时间" value="modified" />
      <el-option label="评分" value="rating" />
      <el-option label="ID" value="id" />
      <slot name="extra-options"></slot>
    </el-select>
    <el-button @click="handleToggleOrder">
      <span class="material-symbols-outlined" v-if="sort === 'created'">
        {{ order === 'desc' ? 'schedule' : 'schedule' }}
      </span>
      <span class="material-symbols-outlined" v-else-if="sort === 'modified'">
        {{ order === 'desc' ? 'edit_calendar' : 'edit_calendar' }}
      </span>
      <span class="material-symbols-outlined" v-else>
        {{ order === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
      </span>
      <span class="ml-1 text-xs">{{ order === 'desc' ? '降序' : '升序' }}</span>
    </el-button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  sort: string
  order: 'asc' | 'desc'
}>()

const emit = defineEmits<{
  (e: 'update:sort', value: string): void
  (e: 'update:order', value: 'asc' | 'desc'): void
  (e: 'change'): void
}>()

function handleSortChange(value: string) {
  emit('update:sort', value)
  emit('change')
}

function handleToggleOrder() {
  emit('update:order', props.order === 'desc' ? 'asc' : 'desc')
  emit('change')
}
</script>
