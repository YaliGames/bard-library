<script setup lang="ts">
interface ReaderSettings {
  theme: 'light' | 'sepia' | 'dark'
  fontSize: number
  lineHeight: number
  contentWidth: number
}

interface Props {
  settings: ReaderSettings
}

defineProps<Props>()

const emit = defineEmits<{
  'update-theme': [theme: 'light' | 'sepia' | 'dark']
  'update-font-size': [size: number]
  'update-line-height': [height: number]
  'update-content-width': [width: number]
}>()

// 主题选项
const themeOptions = [
  { value: 'light', label: '明亮' },
  { value: 'sepia', label: '米黄' },
  { value: 'dark', label: '深色' },
] as const

// 字体大小范围
const fontSizeRange = { min: 14, max: 24, step: 1 }
// 行高范围
const lineHeightRange = { min: 1.4, max: 2.2, step: 0.1 }
// 内容宽度范围
const contentWidthRange = { min: 560, max: 960, step: 10 }

// 方法
function updateTheme(theme: 'light' | 'sepia' | 'dark') {
  emit('update-theme', theme)
}

function updateFontSize(size: number) {
  emit('update-font-size', size)
}

function updateLineHeight(height: number) {
  emit('update-line-height', height)
}

function updateContentWidth(width: number) {
  emit('update-content-width', width)
}

// 暴露给父组件
defineExpose({
  themeOptions,
  fontSizeRange,
  lineHeightRange,
  contentWidthRange,
  updateTheme,
  updateFontSize,
  updateLineHeight,
  updateContentWidth,
})
</script>

<template>
  <slot
    :settings="settings"
    :theme-options="themeOptions"
    :font-size-range="fontSizeRange"
    :line-height-range="lineHeightRange"
    :content-width-range="contentWidthRange"
    :update-theme="updateTheme"
    :update-font-size="updateFontSize"
    :update-line-height="updateLineHeight"
    :update-content-width="updateContentWidth"
  >
    <!-- 默认UI -->
    <div>
      <!-- 主题 -->
      <div>
        <div>主题</div>
        <button
          v-for="option in themeOptions"
          :key="option.value"
          @click="updateTheme(option.value)"
          :class="{ active: settings.theme === option.value }"
        >
          {{ option.label }}
        </button>
      </div>

      <!-- 字体大小 -->
      <div>
        <div>字体大小: {{ settings.fontSize }}px</div>
        <input
          type="range"
          :value="settings.fontSize"
          @input="updateFontSize(Number(($event.target as HTMLInputElement).value))"
          :min="fontSizeRange.min"
          :max="fontSizeRange.max"
          :step="fontSizeRange.step"
        />
      </div>

      <!-- 行高 -->
      <div>
        <div>行高: {{ settings.lineHeight.toFixed(1) }}</div>
        <input
          type="range"
          :value="settings.lineHeight"
          @input="updateLineHeight(Number(($event.target as HTMLInputElement).value))"
          :min="lineHeightRange.min"
          :max="lineHeightRange.max"
          :step="lineHeightRange.step"
        />
      </div>

      <!-- 内容宽度 -->
      <div>
        <div>内容宽度: {{ settings.contentWidth }}px</div>
        <input
          type="range"
          :value="settings.contentWidth"
          @input="updateContentWidth(Number(($event.target as HTMLInputElement).value))"
          :min="contentWidthRange.min"
          :max="contentWidthRange.max"
          :step="contentWidthRange.step"
        />
      </div>
    </div>
  </slot>
</template>
