<template>
  <div>
    <!-- 搜索栏 -->
    <div class="mb-4">
      <div
        class="relative w-full rounded-lg bg-white shadow-sm overflow-hidden focus-within:ring-2 focus-within:ring-[var(--el-color-primary)]"
      >
        <input
          v-model="filters.q"
          type="text"
          :placeholder="searchPlaceholder"
          @keyup.enter="onSearch"
          class="block w-full max-w-full min-w-0 px-4 py-3 pr-28 appearance-none box-border outline-none focus:outline-none border-0 focus:ring-0"
        />
        <!-- <div class="absolute inset-y-0 right-0 flex items-center gap-2 pr-2">
          <el-button size="small" @click="onReset" plain>重置</el-button>
          <el-button size="small" type="primary" @click="onSearch">查询</el-button>
        </div> -->
        <span
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 select-none"
        >
          <span class="material-symbols-outlined">search</span>
        </span>
      </div>
    </div>

    <!-- 条件卡片 -->
    <div v-if="hasAnyAdvanced" class="bg-white rounded-lg shadow-sm p-4">
      <div class="flex items-center justify-between mb-4">
        <div class="text-lg font-medium my-2">筛选条件</div>
        <button
          v-if="enableExpand"
          type="button"
          @click="ui.expanded = !ui.expanded"
          class="px-3 py-1.5 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors appearance-none border border-transparent focus:outline-none focus:text-white focus:bg-gray-300 shadow-sm"
        >
          {{ ui.expanded ? '收起' : '展开' }}
        </button>
      </div>

      <!-- 书架（单选） -->
      <div v-if="showShelves" class="mb-4">
        <div class="text-sm font-medium mb-2 text-gray-700">书架</div>
        <div class="flex flex-wrap gap-2">
          <el-button
            :type="!filters.shelfId ? 'primary' : 'default'"
            plain
            class="rounded-full"
            @click="onSelectShelf('all')"
          >
            全部
          </el-button>
          <div v-for="s in shelves" :key="s.id">
            <el-button
              :type="filters.shelfId === s.id ? 'primary' : 'default'"
              plain
              class="rounded-full"
              @click="onSelectShelf(String(s.id))"
            >
              {{ s.name }}
            </el-button>
          </div>
        </div>
      </div>

      <div v-show="!enableExpand || ui.expanded">
        <!-- 标签（多选） -->
        <div v-if="showTags" class="mb-4">
          <div class="text-sm font-medium mb-2 text-gray-700">标签</div>
          <div class="flex flex-wrap gap-2">
            <div v-for="t in tags" :key="t.id">
              <el-button
                :type="filters.tagIds.includes(t.id) ? 'primary' : 'default'"
                plain
                class="rounded-full"
                @click="toggleTag(t.id)"
              >
                {{ t.name }}
              </el-button>
            </div>
          </div>
        </div>

        <!-- 四列栅格 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div v-if="showReadState && isLoggedIn">
            <div class="text-sm font-medium mb-2 text-gray-700">阅读状态</div>
            <el-select
              v-model="filters.readState"
              placeholder="选择阅读状态"
              clearable
              style="width: 100%"
            >
              <el-option label="已读" value="read" />
              <el-option label="在读" value="reading" />
              <el-option label="未读" value="unread" />
            </el-select>
          </div>
          <div v-if="showAuthor">
            <div class="text-sm font-medium mb-2 text-gray-700">作者</div>
            <el-select
              v-model="filters.authorId"
              filterable
              clearable
              placeholder="选择作者"
              style="width: 100%"
            >
              <el-option v-for="a in authors" :key="a.id" :label="a.name" :value="a.id" />
            </el-select>
          </div>
          <div v-if="showPublisher">
            <div class="text-sm font-medium mb-2 text-gray-700">出版社</div>
            <el-input v-model="filters.publisher" type="text" placeholder="输入出版社" />
          </div>
          <div v-if="showPublishedAt">
            <div class="text-sm font-medium mb-2 text-gray-700">出版日期</div>
            <el-date-picker
              v-model="filters.publishedRange"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </div>
          <div v-if="showLanguage">
            <div class="text-sm font-medium mb-2 text-gray-700">语言</div>
            <el-input v-model="filters.language" type="text" placeholder="输入语言" />
          </div>
          <div v-if="showSeries">
            <div class="text-sm font-medium mb-2 text-gray-700">丛书</div>
            <el-input v-model="filters.series_value" type="text" placeholder="输入丛书ID或名称" />
          </div>
          <div v-if="showIsbn">
            <div class="text-sm font-medium mb-2 text-gray-700">ISBN（10 或 13）</div>
            <el-input v-model="filters.isbn" type="text" placeholder="输入10或13位ISBN" />
          </div>
          <div v-if="showRating">
            <div class="text-sm font-medium mb-2 text-gray-700">评分</div>
            <el-slider v-model="filters.ratingRange" range :min="0" :max="5" :step="0.1" />
            <div class="text-xs text-gray-500 mt-1">
              {{ filters.ratingRange[0].toFixed(1) }} - {{ filters.ratingRange[1].toFixed(1) }}
            </div>
          </div>
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="mt-4 flex justify-end gap-2">
        <div class="flex-grow text-center">
          <el-button size="large" class="rounded-full px-16" @click="onReset">重置</el-button>
          <el-button type="primary" size="large" class="rounded-full px-16" @click="onSearch">
            搜索
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { reactive, watch, computed, onMounted } from 'vue'
import { authorsApi } from '@/api/authors'
import { tagsApi } from '@/api/tags'
import { shelvesApi } from '@/api/shelves'
import { useAuthStore } from '@/stores/auth'

export interface FiltersModel {
  q: string
  authorId: number | null
  tagIds: number[]
  shelfId: number | null
  readState: 'read' | 'reading' | 'unread' | null
  ratingRange: [number, number]
  publisher?: string | null
  publishedRange?: [string, string] | null
  language?: string | null
  series_value?: string | number | null
  isbn?: string | null
}

const props = withDefaults(
  defineProps<{
    modelValue: FiltersModel
    showShelves?: boolean
    showTags?: boolean
    showAuthor?: boolean
    showReadState?: boolean
    showRating?: boolean
    showPublisher?: boolean
    showPublishedAt?: boolean
    showLanguage?: boolean
    showSeries?: boolean
    showIsbn?: boolean
    enableExpand?: boolean
    defaultExpanded?: boolean
    searchPlaceholder?: string
  }>(),
  {
    showShelves: true,
    showTags: true,
    showAuthor: true,
    showReadState: true,
    showRating: true,
    showPublisher: true,
    showPublishedAt: true,
    showLanguage: true,
    showSeries: true,
    showIsbn: true,
    enableExpand: true,
    defaultExpanded: false,
    searchPlaceholder: '请输入搜索关键字...',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: FiltersModel): void
  (e: 'search'): void
  (e: 'reset'): void
}>()

const { loggedIn } = useAuthStore()
const isLoggedIn = loggedIn

const filters = reactive<FiltersModel>({
  q: '',
  authorId: null,
  tagIds: [],
  shelfId: null,
  readState: null,
  ratingRange: [0, 5],
  publisher: null,
  publishedRange: null,
  language: null,
  series_value: null,
  isbn: null,
})

const ui = reactive({ expanded: props.defaultExpanded })
const authors = reactive<any[]>([])
const tags = reactive<any[]>([])
const shelves = reactive<any[]>([])

const hasAnyAdvanced = computed(
  () =>
    props.showShelves ||
    props.showTags ||
    props.showAuthor ||
    (props.showReadState && isLoggedIn.value) ||
    props.showRating,
)

watch(
  () => props.modelValue,
  v => {
    if (!v) return
    Object.assign(filters, v)
  },
  { immediate: true, deep: true },
)

watch(
  filters,
  v => {
    emit('update:modelValue', { ...v })
  },
  { deep: true },
)

function toggleTag(id: number) {
  const i = filters.tagIds.indexOf(id)
  if (i >= 0) filters.tagIds.splice(i, 1)
  else filters.tagIds.push(id)
}
function onSelectShelf(index: string) {
  if (index === 'all') filters.shelfId = null
  else filters.shelfId = Number(index)
}

function onSearch() {
  emit('search')
}
function onReset() {
  Object.assign(filters, {
    q: '',
    authorId: null,
    tagIds: [],
    shelfId: null,
    readState: null,
    ratingRange: [0, 5] as [number, number],
    publisher: null,
    publishedRange: null,
    language: null,
    series_value: null,
    isbn: null,
  })
  emit('reset')
}

onMounted(async () => {
  try {
    const ps: Promise<any>[] = []
    if (props.showAuthor)
      ps.push(authorsApi.list().then(r => authors.splice(0, authors.length, ...r)))
    if (props.showTags) ps.push(tagsApi.list().then(r => tags.splice(0, tags.length, ...r)))
    if (props.showShelves)
      ps.push(shelvesApi.listAll().then(r => shelves.splice(0, shelves.length, ...r)))
    await Promise.all(ps)
  } catch {}
})
</script>
