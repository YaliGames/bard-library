<template>
  <section class="book-list">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
      <!-- 顶部导航栏 -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3 overflow-hidden">
          <el-button circle class="shrink-0" @click="back">
            <span class="material-symbols-outlined text-xl">arrow_back</span>
          </el-button>
          <div class="flex flex-col overflow-hidden min-w-[200px]">
            <el-skeleton animated :loading="shelfLoading">
              <template #template>
                <div class="flex flex-col gap-2 py-1">
                  <el-skeleton-item variant="h3" style="width: 150px" />
                  <el-skeleton-item variant="text" style="width: 200px" />
                </div>
              </template>
              <template #default>
                <h2 class="text-xl font-bold truncate flex items-center gap-2">
                  {{ shelf?.name }}
                  <el-tag v-if="shelf?.is_public" size="small" type="success" effect="plain" round>
                    公开
                  </el-tag>
                  <el-tag v-else-if="shelf" size="small" type="info" effect="plain" round>
                    私有
                  </el-tag>
                </h2>
                <div class="text-xs text-gray-500 truncate" v-if="shelf?.description">
                  {{ shelf.description }}
                </div>
              </template>
            </el-skeleton>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <template v-if="canManage">
            <el-button v-if="!isManagingBooks" type="primary" @click="openAddDialog">
              <span class="material-symbols-outlined mr-1 text-lg">add</span>
              添加书籍
            </el-button>

            <el-button :type="isManagingBooks ? 'success' : 'default'" @click="toggleManageBooks">
              <span class="material-symbols-outlined mr-1 text-lg">
                {{ isManagingBooks ? 'check' : 'edit_square' }}
              </span>
              {{ isManagingBooks ? '完成' : '批量管理' }}
            </el-button>

            <el-tooltip content="书架设置" placement="bottom">
              <el-button circle @click="openSettings">
                <span class="material-symbols-outlined text-xl">settings</span>
              </el-button>
            </el-tooltip>
          </template>
        </div>
      </div>

      <el-result v-if="shelfError && !shelfLoading" icon="warning" :title="shelfError">
        <template #extra>
          <el-button type="primary" @click="back">返回</el-button>
        </template>
      </el-result>

      <template v-if="!shelfError">
        <div
          class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4"
        >
          <div class="w-full md:w-auto flex-1">
            <BookFilters
              v-if="userSettings.shelfDetail?.showFilters ?? true"
              v-model="filters"
              :showShelves="false"
              :showTags="true"
              :showAuthor="true"
              :showReadState="true"
              :showRating="true"
              :enableExpand="true"
              :defaultExpanded="userSettings.preferences?.expandFilterMenu"
              @search="searchPage(1)"
              @reset="resetFilters"
            />
          </div>
          <SortControl v-model:sort="sort" v-model:order="order" @change="() => searchPage(1)" />
        </div>

        <el-alert v-if="isManagingBooks" type="info" show-icon :closable="false" class="mb-4">
          <template #title>
            <div class="flex items-center justify-between w-full">
              <span>
                管理模式已开启，点击书籍封面上的删除图标可将其从书架移除（不会删除书籍本身）。
              </span>
            </div>
          </template>
        </el-alert>

        <BookGrid
          :data="data"
          :loading="loading"
          :meta="meta"
          :editable="isManagingBooks"
          :show-add-button="canManage && !isManagingBooks"
          @page-change="searchPage"
          @author-click="filterByAuthor"
          @toggle-read="toggleRead"
          @add-click="openAddDialog"
          @remove-click="confirmRemove"
        />
      </template>
    </div>
  </section>

  <AddBooksDialog
    v-if="canManage"
    v-model="addVisible"
    :exclude-book-ids="currentBookIds"
    @add="handleBookAdded"
  />

  <ShelfSettingsDialog
    v-if="shelf && canManage"
    v-model="settingsVisible"
    :shelf="shelf"
    @updated="fetchShelfInfo"
    @deleted="onShelfDeleted"
  />
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import BookFilters from '@/components/Book/Filters.vue'
import BookGrid from '@/components/Book/BookGrid.vue'
import ShelfSettingsDialog from '@/components/Shelf/ShelfSettingsDialog.vue'
import AddBooksDialog from '@/components/Shelf/AddBooksDialog.vue'
import SortControl from '@/components/SortControl.vue'
import { booksApi } from '@/api/books'
import type { Book, Shelf } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
import { shelvesApi } from '@/api/shelves'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'
import { useLoading } from '@/composables/useLoading'
import { useBookActions } from '@/composables/useBookActions'

const { handleError, handleSuccess } = useErrorHandler()
const { toggleReadMark } = useBookActions()
const { hasPermission } = usePermission()

const { isLoadingKey, startLoading, stopLoading } = useLoading()
const shelfLoading = computed(() => isLoadingKey('shelf'))

const router = useRouter()
const route = useRoute()
const shelfId = computed(() => Number(route.params.id))
const shelf = ref<(Shelf & { description?: string }) | null>(null)
const shelfError = ref<string | null>(null)
const authStore = useAuthStore()

// 权限控制
const canManage = computed(() => {
  if (!shelf.value) return false
  // 有 shelves.manage_all 权限可以管理所有书架
  if (hasPermission('shelves.manage_all')) return true
  // 书架所有者可以编辑自己的书架(需要 shelves.edit 权限)
  const isOwner = (shelf.value.user_id ?? 0) === (authStore.user?.id ?? -1)
  return isOwner && hasPermission('shelves.edit')
})

// 统一的筛选模型（固定 shelfId）
const filters = ref({
  q: '',
  authorId: null as number | null,
  tagIds: [] as number[],
  shelfId: shelfId.value as number | null,
  readState: null as any,
  ratingRange: [0, 5] as [number, number],
  publisher: null as string | null,
  publishedRange: null as [string, string] | null,
  language: null as string | null,
  series_value: null as string | number | null,
  isbn: null as string | null,
})

const err = ref('')
const sort = ref<'modified' | 'created' | 'rating' | 'id'>('created')
const order = ref<'asc' | 'desc'>('desc')
const settingsStore = useSettingsStore()
const userSettings = settingsStore.settings

const { data, loading, currentPage, lastPage, total, perPage, loadPage } = usePagination<Book>({
  fetcher: async (page: number) => {
    try {
      const tagParam = filters.value.tagIds.length ? filters.value.tagIds : undefined
      const r = await booksApi.list({
        q: filters.value.q || undefined,
        page,
        authorId: filters.value.authorId || undefined,
        tagId: tagParam,
        shelfId: shelfId.value || undefined,
        read_state: filters.value.readState || undefined,
        min_rating: filters.value.ratingRange?.[0],
        max_rating: filters.value.ratingRange?.[1],
        publisher: filters.value.publisher || undefined,
        published_from: filters.value.publishedRange?.[0] || undefined,
        published_to: filters.value.publishedRange?.[1] || undefined,
        language: filters.value.language || undefined,
        series_value: filters.value.series_value || undefined,
        isbn: filters.value.isbn || undefined,
        sort: sort.value,
        order: order.value,
      })
      return r
    } catch (e: any) {
      err.value = e?.message || '加载失败'
      throw e
    }
  },
  onError: (e: any) => {
    err.value = e?.message || '加载失败'
  },
})

const meta = computed(() => ({
  current_page: currentPage.value,
  last_page: lastPage.value,
  per_page: perPage.value,
  total: total.value,
}))

function back() {
  router.back()
}

// 书籍管理模式
const isManagingBooks = ref(false)
function toggleManageBooks() {
  isManagingBooks.value = !isManagingBooks.value
}

// 设置弹窗
const settingsVisible = ref(false)
function openSettings() {
  settingsVisible.value = true
}

function onShelfDeleted() {
  router.push({ name: 'user-shelves' })
}

function filterByAuthor(id: number) {
  filters.value.authorId = id
  loadPage(1)
}

async function fetchShelfInfo() {
  startLoading('shelf')
  try {
    const s = await shelvesApi.show(shelfId.value)
    shelf.value = s
    shelfError.value = null
  } catch (e: any) {
    // 读取错误：区分 403/404
    const status = (e && e.status) || null
    if (status === 403) shelfError.value = '无权限访问该书架'
    else if (status === 404) shelfError.value = '书架不存在或已删除'
    else shelfError.value = '加载书架失败'
    shelf.value = { id: shelfId.value, name: `#${shelfId.value}` }
  } finally {
    stopLoading('shelf')
  }
}

function searchPage(page: number) {
  loadPage(page)
}

function resetFilters() {
  Object.assign(filters.value, {
    q: '',
    authorId: null,
    tagIds: [],
    readState: null,
    ratingRange: [0, 5] as [number, number],
    publisher: null,
    publishedRange: null,
    language: null,
    series_value: null,
    isbn: null,
  })
  // 保持 shelfId 固定
  filters.value.shelfId = shelfId.value
  sort.value = 'created'
  order.value = 'desc'
  loadPage(1)
}

async function toggleRead(b: Book) {
  await toggleReadMark(b)
}

// 路由变化时，更新 shelfId 并重新加载
watch(
  () => route.params.id,
  () => {
    filters.value.shelfId = shelfId.value
    fetchShelfInfo()
    if (!shelfError.value) loadPage(1)
  },
)

onMounted(() => {
  filters.value.shelfId = shelfId.value
  fetchShelfInfo()
  // 仅在可见（未报错）时加载书籍
  setTimeout(() => {
    if (!shelfError.value) loadPage(1)
  }, 0)
})

// 添加书籍
const addVisible = ref(false)

// 当前书架中的书籍 ID 列表
const currentBookIds = computed(() => data.value.map(b => b.id))

function openAddDialog() {
  addVisible.value = true
}

// 书籍添加成功回调
async function handleBookAdded(book: Book) {
  try {
    if (data.value.find(x => x.id === book.id)) {
      return
    }
    await shelvesApi.attachBooks(shelfId.value, [book.id])
    data.value.unshift(book)
    handleSuccess('已添加书籍')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.handleBookAdded' })
  }
}

// 从书架移除
async function removeFromShelf(b: Book) {
  try {
    await shelvesApi.detachBooks(shelfId.value, [b.id])
    data.value = data.value.filter(x => x.id !== b.id)
    handleSuccess('已移出')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.removeFromShelf' })
  }
}

async function confirmRemove(b: Book) {
  try {
    await ElMessageBox.confirm('确认将该图书从此书架移除？', '移除确认', {
      type: 'warning',
      confirmButtonText: '移除',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }
  await removeFromShelf(b)
}
</script>

<style scoped></style>
