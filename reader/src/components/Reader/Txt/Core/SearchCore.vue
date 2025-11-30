<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { extractSearchPreview, buildSearchRegex } from '@/utils/reader'
import type { SearchResult } from '@/types/reader'

interface Chapter {
  index?: number
  title?: string | null
  offset?: number
  length?: number
}

interface Props {
  visible: boolean
  currentChapterContent: string
  currentChapterIndex: number | null
  chapters: Chapter[]
  sentences?: string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'jump-to-result': [result: SearchResult]
  'search-global': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
  'search-chapter': [keyword: string, caseSensitive: boolean, wholeWord: boolean]
}>()

// 状态
const mode = ref<'chapter' | 'global'>('chapter')
const keyword = ref('')
const caseSensitive = ref(false)
const wholeWord = ref(false)
const searching = ref(false)
const searched = ref(false)
const results = ref<SearchResult[]>([])
const currentIndex = ref(0)

// 监听visible变化，自动重置
watch(
  () => props.visible,
  visible => {
    if (visible) {
      nextTick(() => {
        // 可以聚焦输入框
      })
    } else {
      // 关闭时清空状态
      keyword.value = ''
      results.value = []
      searched.value = false
      currentIndex.value = 0
    }
  },
)

// 监听搜索模式切换
watch(mode, () => {
  if (keyword.value && searched.value) {
    handleSearch()
  }
})

// 监听搜索选项变化
watch([caseSensitive, wholeWord], () => {
  if (keyword.value && results.value.length > 0) {
    handleSearch()
  }
})

// 方法
function setMode(newMode: 'chapter' | 'global') {
  mode.value = newMode
}

function toggleCaseSensitive() {
  caseSensitive.value = !caseSensitive.value
}

function toggleWholeWord() {
  wholeWord.value = !wholeWord.value
}

function setKeyword(value: string) {
  keyword.value = value
}

function clearKeyword() {
  keyword.value = ''
  results.value = []
  searched.value = false
  currentIndex.value = 0
}

function handleSearch() {
  if (!keyword.value.trim()) {
    results.value = []
    return
  }

  currentIndex.value = 0
  searching.value = true
  searched.value = false

  if (mode.value === 'chapter') {
    emit('search-chapter', keyword.value, caseSensitive.value, wholeWord.value)
    searchInChapter()
  } else {
    emit('search-global', keyword.value, caseSensitive.value, wholeWord.value)
  }
}

// 章节搜索实现
function searchInChapter() {
  if (!props.currentChapterContent || !keyword.value.trim()) {
    results.value = []
    searching.value = false
    searched.value = true
    return
  }

  try {
    const regex = buildSearchRegex(keyword.value, caseSensitive.value, wholeWord.value)
    const matches: SearchResult[] = []
    let match: RegExpExecArray | null

    while ((match = regex.exec(props.currentChapterContent)) !== null) {
      const position = match.index
      const matchedText = match[0]

      // 提取预览文本
      const preview = extractSearchPreview(props.currentChapterContent, position, matchedText)

      // 计算句子索引
      let sentenceIndex: number | undefined
      if (props.sentences) {
        let offset = 0
        for (let j = 0; j < props.sentences.length; j++) {
          const len = props.sentences[j].length
          if (position >= offset && position < offset + len) {
            sentenceIndex = j
            break
          }
          offset += len
        }
      }

      matches.push({
        chapterIndex: props.currentChapterIndex || 0,
        chapterTitle: props.chapters[props.currentChapterIndex || 0]?.title || null,
        position,
        matchLength: matchedText.length,
        sentenceIndex,
        preview,
      })
    }

    results.value = matches
  } catch (error) {
    console.error('章节搜索失败:', error)
    results.value = []
  } finally {
    searching.value = false
    searched.value = true
  }
}

function jumpToResult(index: number) {
  if (index < 0 || index >= results.value.length) return
  currentIndex.value = index
  emit('jump-to-result', results.value[index])
}

function prevResult() {
  if (currentIndex.value > 0) {
    jumpToResult(currentIndex.value - 1)
  }
}

function nextResult() {
  if (currentIndex.value < results.value.length - 1) {
    jumpToResult(currentIndex.value + 1)
  }
}

// 供父组件调用的方法
function setGlobalResults(globalResults: SearchResult[]) {
  results.value = globalResults
  currentIndex.value = 0
  searching.value = false
  searched.value = true
}

function setSearching(value: boolean) {
  searching.value = value
}

// 暴露方法给父组件
defineExpose({
  setGlobalResults,
  setSearching,
  mode,
  keyword,
  caseSensitive,
  wholeWord,
  searching,
  searched,
  results,
  currentIndex,
})
</script>

<template>
  <slot
    :mode="mode"
    :keyword="keyword"
    :case-sensitive="caseSensitive"
    :whole-word="wholeWord"
    :searching="searching"
    :searched="searched"
    :results="results"
    :current-index="currentIndex"
    :set-mode="setMode"
    :set-keyword="setKeyword"
    :toggle-case-sensitive="toggleCaseSensitive"
    :toggle-whole-word="toggleWholeWord"
    :clear-keyword="clearKeyword"
    :handle-search="handleSearch"
    :jump-to-result="jumpToResult"
    :prev-result="prevResult"
    :next-result="nextResult"
  >
    <!-- 默认UI -->
    <div>
      <div>
        <button @click="setMode('chapter')">章节搜索</button>
        <button @click="setMode('global')">全书搜索</button>
      </div>
      <input v-model="keyword" @keyup.enter="handleSearch" placeholder="输入关键词..." />
      <label>
        <input type="checkbox" :checked="caseSensitive" @change="toggleCaseSensitive" />
        区分大小写
      </label>
      <label>
        <input type="checkbox" :checked="wholeWord" @change="toggleWholeWord" />
        全字匹配
      </label>
      <button @click="handleSearch" :disabled="!keyword || searching">搜索</button>
      <div v-if="!searched">输入关键词开始搜索</div>
      <div v-else-if="results.length === 0">未找到结果</div>
      <div v-else>
        <div>找到 {{ results.length }} 个结果</div>
        <div v-for="(result, index) in results" :key="index" @click="jumpToResult(index)">
          {{ result.preview }}
        </div>
      </div>
    </div>
  </slot>
</template>
