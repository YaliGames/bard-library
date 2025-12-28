<template>
  <div class="flex flex-row flex-wrap">
    <!-- Primary Button -->
    <el-button
      :type="type"
      :size="size"
      :disabled="disabled"
      :class="[primaryButtonClass, 'rounded-r-none']"
      @click="handlePrimaryClick"
    >
      <slot name="icon">
        <span v-if="icon" class="material-symbols-outlined mr-1">{{ icon }}</span>
      </slot>
      <slot>{{ label }}</slot>
    </el-button>

    <!-- Dropdown Button -->
    <el-dropdown trigger="click" class="ml-0" @command="handleCommand">
      <span class="el-dropdown-link">
        <el-button
          :type="type"
          :size="size"
          :disabled="disabled"
          :class="[
            dropdownButtonClass,
            'rounded-l-none px-0',
            type === 'primary' ? 'border-l-white hover:border-l-white' : 'border-l-0',
          ]"
        >
          <span class="material-symbols-outlined">arrow_drop_down</span>
        </el-button>
      </span>
      <template #dropdown>
        <el-dropdown-menu>
          <slot name="dropdown" />
        </el-dropdown-menu>
      </template>
    </el-dropdown>
  </div>
</template>

<script setup lang="ts">
interface Props {
  type?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'default'
  size?: 'large' | 'default' | 'small'
  disabled?: boolean
  label?: string
  icon?: string
  primaryButtonClass?: string
  dropdownButtonClass?: string
  primaryValue?: any
}

const props = withDefaults(defineProps<Props>(), {
  type: 'default',
  size: 'default',
  disabled: false,
  label: '',
  icon: '',
  primaryButtonClass: '',
  dropdownButtonClass: '',
})

const emit = defineEmits<{
  click: [value: any]
  command: [value: any]
}>()

function handlePrimaryClick() {
  emit('click', props.primaryValue)
}

function handleCommand(command: any) {
  emit('command', command)
}
</script>
