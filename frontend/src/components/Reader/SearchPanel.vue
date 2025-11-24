<template>
  <div
    v-if="visible"
    class="fixed top-20 right-6 z-50 w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg"
  >
    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
        {{ mode === 'chapter' ? '章节搜索' : '全文搜索' }}
      </h3>
      <button
        @click="$emit('close')"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
      >
        <span class="material-symbols-outlined text-xl">close</span>
      </button>
    </div>
    
    <div class="p-3">
      <!-- 搜索模式切换 -->
      <el-radio-group v-model="mode" size="small" class="mb-3 w-full">
        <el-radio-button label="chapter">当前章节</el-radio-button>
        <el-radio-button label="global">全文搜索</el-radio-button>
      </el-radio-group>
      
      <!-- 搜索输入框 -->
      <div class="relative mb-3">
        <el-input
          ref="searchInputRef"
          v-model="keyword"
          placeholder="输入关键词..."
          size="small"
          clearable
          @keyup.enter="handleSearch"
          @input="onInputChange"
        >
          <template #suffix>
            <span v-if="searching" class="text-xs text-gray-400">搜索中...</span>
            <span v-else-if="results.length > 0" class="text-xs text-gray-500">
              {{ currentIndex + 1 }} / {{ results.length }}
            </span>
          </template>
        </el-input>
      </div>
      
      <!-- 搜索选项 -->
      <div class="flex items-center gap-3 mb-3 text-sm">
        <el-checkbox v-model="caseSensitive" size="small">区分大小写</el-checkbox>
        <el-checkbox v-model="wholeWord" size="small">全字匹配</el-checkbox>
      </div>
      
      <!-- 搜索按钮 -->
      <div class="flex mb-3">
        <el-button
          size="small"
          type="primary"
          :disabled="!keyword || searching"
          @click="handleSearch"
          class="flex-1"
        >
          <span class="material-symbols-outlined text-base mr-1">search</span>
          搜索
        </el-button>
        <el-button
          size="small"
          :disabled="results.length === 0"
          @click="prevResult"
        >
          <span class="material-symbols-outlined text-base">arrow_upward</span>
        </el-button>
        <el-button
          size="small"
          :disabled="results.length === 0"
          @click="nextResult"
        >
          <span class="material-symbols-outlined text-base">arrow_downward</span>
        </el-button>
      </div>
      
      <!-- 搜索结果列表 -->
      <div v-if="results.length > 0" class="max-h-96 overflow-y-auto">
        <div class="text-xs text-gray-500 mb-2">
          找到 {{ results.length }} 处结果
        </div>
        <div
          v-for="(result, index) in results"
          :key="index"
          @click="jumpToResult(index)"
          :class="[
            'px-2 py-2 mb-1 rounded cursor-pointer text-sm',
            'hover:bg-gray-100 dark:hover:bg-gray-700',
            currentIndex === index ? 'bg-blue-100 dark:bg-blue-900' : 'bg-gray-50 dark:bg-gray-750'
          ]"
        >
          <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
            {{ result.chapterTitle || `第 ${result.chapterIndex + 1} 章` }}
          </div>
          <div class="text-gray-700 dark:text-gray-300 line-clamp-2" v-html="result.preview"></div>
        </div>
      </div>
      
      <!-- 无结果提示 -->
      <div v-else-if="keyword && searched && !searching" class="text-center py-6 text-gray-400">
        <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
        <div class="text-sm">未找到匹配结果</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

interface SearchResult {
  chapterIndex: number
  chapterTitle: string | null | undefined
  position: number // 在章节内容中的字符位置
  sentenceIndex?: number // 句子索引（章节搜索）
  preview: string // 预览文本
}

interface Props {
  visible: boolean
  currentChapterContent: string
  currentChapterIndex: number | null
  chapters: Array<{ index: number; title?: string | null; offset: number; length: number }>
  sentences?: string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  jumpToResult: [result: SearchResult]
  searchGlobal: [keyword: string, caseSensitive: boolean, wholeWord: boolean]
  searchChapter: [keyword: string, caseSensitive: boolean, wholeWord: boolean]
}>()

const searchInputRef = ref<any>(null)
const mode = ref<'chapter' | 'global'>('chapter')
const keyword = ref('')
const caseSensitive = ref(false)
const wholeWord = ref(false)
const searching = ref(false)
const searched = ref(false)
const results = ref<SearchResult[]>([])
const currentIndex = ref(0)

// 监听显示状态，自动聚焦
watch(() => props.visible, (visible) => {
  if (visible) {
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  } else {
    // 关闭时清空状态并通知父组件清除高亮
    results.value = []
    searched.value = false
    currentIndex.value = 0
    emit('close')
  }
})

// 监听模式切换，清空结果
watch(mode, () => {
  results.value = []
  searched.value = false
  currentIndex.value = 0
})

// 监听章节变化，在章节搜索模式下自动重新搜索
watch(() => props.currentChapterIndex, () => {
  if (mode.value === 'chapter' && searched.value && keyword.value) {
    // 自动重新搜索当前章节
    searchInChapter()
  }
})

function onInputChange() {
  if (!keyword.value) {
    results.value = []
    searched.value = false
    currentIndex.value = 0
  }
}

function buildRegex(kw: string): RegExp {
  let pattern = kw
  // 转义特殊字符
  pattern = pattern.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  
  if (wholeWord.value) {
    pattern = `\\b${pattern}\\b`
  }
  
  const flags = caseSensitive.value ? 'g' : 'gi'
  return new RegExp(pattern, flags)
}

function extractPreview(content: string, position: number, matchedText: string): string {
  const contextLength = 20
  const matchLength = matchedText.length
  const start = Math.max(0, position - contextLength)
  const end = Math.min(content.length, position + matchLength + contextLength)
  
  let preview = content.substring(start, end)
  
  // 添加省略号
  if (start > 0) preview = '...' + preview
  if (end < content.length) preview = preview + '...'
  
  // 清理换行符
  preview = preview.replace(/\n/g, ' ')
  
  // HTML 转义
  const escapeHtml = (str: string) => {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
  }
  
  // 转义整个预览
  const escapedPreview = escapeHtml(preview)
  
  // 转义匹配的文本(使用原始匹配结果,不是搜索关键词)
  const escapedKeyword = escapeHtml(matchedText)
  
  // 在转义后的文本中查找转义后的关键字
  const keywordIndex = escapedPreview.indexOf(escapedKeyword)
  if (keywordIndex >= 0) {
    return escapedPreview.substring(0, keywordIndex) +
           '<strong class="text-yellow-600 dark:text-yellow-400">' + escapedKeyword + '</strong>' +
           escapedPreview.substring(keywordIndex + escapedKeyword.length)
  }
  
  // 如果找不到(不应该发生),返回原样
  return escapedPreview
}

function searchInChapter() {
  results.value = []
  currentIndex.value = 0
  
  if (!keyword.value || props.currentChapterIndex == null) {
    return
  }
  
  const content = props.currentChapterContent
  if (!content) return
  
  const regex = buildRegex(keyword.value)
  const matches = [...content.matchAll(regex)]
  
  results.value = matches.map((match) => {
    const position = match.index!
    // 尝试定位到句子索引
    let sentenceIndex: number | undefined
    if (props.sentences) {
      let offset = 0
      for (let i = 0; i < props.sentences.length; i++) {
        const len = props.sentences[i].length
        if (position >= offset && position < offset + len) {
          sentenceIndex = i
          break
        }
        offset += len
      }
    }
    
    return {
      chapterIndex: props.currentChapterIndex!,
      chapterTitle: props.chapters[props.currentChapterIndex!]?.title,
      position,
      sentenceIndex,
      preview: extractPreview(content, position, match[0])
    }
  })
}

async function handleSearch() {
  if (!keyword.value) return
  
  searching.value = true
  searched.value = false
  
  try {
    if (mode.value === 'chapter') {
      // 通知父组件章节搜索的配置
      emit('searchChapter', keyword.value, caseSensitive.value, wholeWord.value)
      searchInChapter()
      searched.value = true
    } else {
      // 全文搜索，发送给父组件处理
      emit('searchGlobal', keyword.value, caseSensitive.value, wholeWord.value)
    }
  } finally {
    searching.value = false
    searched.value = true
  }
}

function jumpToResult(index: number) {
  if (index < 0 || index >= results.value.length) return
  currentIndex.value = index
  emit('jumpToResult', results.value[index])
}

function prevResult() {
  if (results.value.length === 0) return
  currentIndex.value = (currentIndex.value - 1 + results.value.length) % results.value.length
  jumpToResult(currentIndex.value)
}

function nextResult() {
  if (results.value.length === 0) return
  currentIndex.value = (currentIndex.value + 1) % results.value.length
  jumpToResult(currentIndex.value)
}

// 暴露方法供父组件调用
function setGlobalResults(globalResults: SearchResult[]) {
  results.value = globalResults
  searched.value = true
  searching.value = false
  currentIndex.value = 0
}

function setSearching(value: boolean) {
  searching.value = value
}

defineExpose({
  setGlobalResults,
  setSearching
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
