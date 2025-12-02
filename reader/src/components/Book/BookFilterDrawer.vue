<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" @click="close" class="fixed inset-0 bg-black/50 z-50"></div>
    </Transition>

    <Transition name="slide-up">
      <div
        v-if="visible"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl max-h-[80vh] flex flex-col"
      >
        <!-- 顶部拖拽条 -->
        <div
          class="flex items-center justify-center py-2 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        <!-- 标题 -->
        <!-- <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">筛选条件</h3>
            <button
              @click="close"
              class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <span class="material-symbols-outlined text-xl">close</span>
            </button>
          </div>
        </div> -->

        <!-- 固定搜索框 -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <div
            class="relative w-full rounded-lg bg-gray-50 dark:bg-gray-700 shadow-sm overflow-hidden focus-within:ring-2 focus-within:ring-primary"
          >
            <input
              v-model="localFilters.q"
              type="text"
              :placeholder="searchPlaceholder"
              @keyup.enter="onSearch"
              class="block w-full max-w-full min-w-0 px-4 py-3 pr-28 appearance-none box-border outline-none focus:outline-none border-0 focus:ring-0 bg-transparent text-gray-900 dark:text-gray-100"
            />
            <span
              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 select-none"
            >
              <span class="material-symbols-outlined">search</span>
            </span>
          </div>
        </div>

        <!-- 内容区 -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden min-h-0">
          <div class="p-4 space-y-4">
            <!-- 书架（单选） -->
            <div v-if="showShelves && shelves.length > 0">
              <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">书架</div>
              <div class="flex flex-wrap gap-2">
                <el-button
                  :type="!localFilters.shelfId ? 'primary' : 'default'"
                  plain
                  class="rounded-full"
                  size="small"
                  @click="onSelectShelf('all')"
                >
                  全部
                </el-button>
                <div v-for="s in shelves" :key="s.id">
                  <el-button
                    :type="localFilters.shelfId === s.id ? 'primary' : 'default'"
                    plain
                    class="rounded-full"
                    size="small"
                    @click="onSelectShelf(String(s.id))"
                  >
                    {{ s.name }}
                  </el-button>
                </div>
              </div>
            </div>

            <!-- 标签（多选） -->
            <div v-if="showTags && tags.length > 0">
              <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">标签</div>
                <button
                  v-if="tags.length > 10"
                  @click="tagsExpanded = !tagsExpanded"
                  class="text-xs text-blue-500 hover:text-blue-600"
                >
                  {{ tagsExpanded ? '收起' : '展开' }}
                </button>
              </div>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="t in tagsExpanded ? tags : tags.slice(0, 10)"
                  :key="t.id"
                >
                  <el-button
                    :type="localFilters.tagIds.includes(t.id) ? 'primary' : 'default'"
                    plain
                    class="rounded-full"
                    size="small"
                    @click="toggleTag(t.id)"
                  >
                    {{ t.name }}
                  </el-button>
                </div>
                <div v-if="!tagsExpanded && tags.length > 10">
                  <el-button
                    type="default"
                    plain
                    class="rounded-full"
                    size="small"
                    @click="tagsExpanded = true"
                  >
                    +{{ tags.length - 10 }}
                  </el-button>
                </div>
              </div>
            </div>

            <!-- 四列栅格 -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div v-if="showReadState && isLoggedIn">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">阅读状态</div>
                <el-select
                  v-model="localFilters.readState"
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
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">作者</div>
                <el-select
                  v-model="localFilters.authorId"
                  filterable
                  clearable
                  placeholder="选择作者"
                  style="width: 100%"
                >
                  <el-option v-for="a in authors" :key="a.id" :label="a.name" :value="a.id" />
                </el-select>
              </div>
              <div v-if="showPublisher">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">出版社</div>
                <el-input v-model="localFilters.publisher" type="text" placeholder="输入出版社" />
              </div>
              <div v-if="showPublishedAt">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">出版日期</div>
                <el-date-picker
                  v-model="localFilters.publishedRange"
                  type="daterange"
                  range-separator="至"
                  start-placeholder="开始日期"
                  end-placeholder="结束日期"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </div>
              <div v-if="showLanguage">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">语言</div>
                <el-input v-model="localFilters.language" type="text" placeholder="输入语言" />
              </div>
              <div v-if="showSeries">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">丛书</div>
                <el-input v-model="localFilters.series_value" type="text" placeholder="输入丛书ID或名称" />
              </div>
              <div v-if="showIsbn">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">ISBN</div>
                <el-input v-model="localFilters.isbn" type="text" placeholder="输入10或13位ISBN" />
              </div>
              <div v-if="showRating">
                <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">评分</div>
                <el-slider v-model="localFilters.ratingRange" range :min="0" :max="5" :step="0.1" />
                <div class="text-xs text-gray-500 mt-1">
                  {{ localFilters.ratingRange[0].toFixed(1) }} - {{ localFilters.ratingRange[1].toFixed(1) }}
                </div>
              </div>
            </div>

            <!-- 排序 -->
            <div>
              <div class="text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">排序</div>
              <SortControl
                :sort="localSort"
                :order="localOrder"
                @update:sort="$emit('update:sort', $event)"
                @update:order="$emit('update:order', $event)"
                @change="onSearch"
              >
                <template #extra-options>
                  <el-option v-if="showCacheSort" label="缓存时间" value="cached" />
                  <el-option v-if="showSizeSort" label="大小" value="size" />
                </template>
              </SortControl>
            </div>

            <slot name="extra"></slot>
          </div>
        </div>

        <!-- 固定底部按钮 -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
          <div class="flex justify-center gap-4">
            <el-button size="large" class="rounded-full px-8" @click="onReset">重置</el-button>
            <el-button type="primary" size="large" class="rounded-full px-8" @click="onSearch">
              搜索
            </el-button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { authorsApi } from '@/api/authors'
import { tagsApi } from '@/api/tags'
import { shelvesApi } from '@/api/shelves'
import { useUserStore } from '@/stores/user'
import SortControl from '@/components/SortControl.vue'
import type { FiltersModel } from './BookFilter.vue'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    filters: FiltersModel
    sort?: string
    order?: 'asc' | 'desc'
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
    showCacheSort?: boolean
    showSizeSort?: boolean
    searchPlaceholder?: string
  }>(),
  {
    sort: 'created',
    order: 'desc',
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
    showCacheSort: false,
    showSizeSort: false,
    searchPlaceholder: '请输入搜索关键字...',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'update:filters', v: FiltersModel): void
  (e: 'update:sort', value: string): void
  (e: 'update:order', value: 'asc' | 'desc'): void
  (e: 'search'): void
  (e: 'reset'): void
}>()

const userStore = useUserStore()
const isLoggedIn = computed(() => userStore.isLoggedIn)

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// 本地状态
const localFilters = reactive<FiltersModel>({ ...props.filters })
const localSort = ref(props.sort)
const localOrder = ref<'asc' | 'desc'>(props.order)
const tagsExpanded = ref(false)

// 数据
const authors = reactive<any[]>([])
const tags = reactive<any[]>([])
const shelves = reactive<any[]>([])

// 监听props变化，更新本地状态
watch(
  () => props.filters,
  (newFilters) => {
    Object.assign(localFilters, newFilters)
  },
  { deep: true, immediate: true },
)

watch(
  () => props.sort,
  (newSort) => {
    localSort.value = newSort
  },
  { immediate: true },
)

watch(
  () => props.order,
  (newOrder) => {
    localOrder.value = newOrder
  },
  { immediate: true },
)

function toggleTag(id: number) {
  const i = localFilters.tagIds.indexOf(id)
  if (i >= 0) localFilters.tagIds.splice(i, 1)
  else localFilters.tagIds.push(id)
}

function onSelectShelf(index: string) {
  if (index === 'all') localFilters.shelfId = null
  else localFilters.shelfId = Number(index)
}

function onSearch() {
  emit('update:filters', { ...localFilters })
  emit('update:sort', localSort.value)
  emit('update:order', localOrder.value)
  emit('search')
  close()
}

function onReset() {
  Object.assign(localFilters, {
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
  localSort.value = 'created'
  localOrder.value = 'desc'
  emit('update:filters', { ...localFilters })
  emit('update:sort', localSort.value)
  emit('update:order', localOrder.value)
  emit('reset')
}

function close() {
  visible.value = false
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>