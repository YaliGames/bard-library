<template>
  <section class="book-list">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">书架 · {{ shelf?.name || '#' + shelfId }}</h2>
        <div class="flex items-center gap-2">
          <el-button v-if="canManage" type="primary" @click="toggleEdit">
            {{ isEditing ? '退出编辑' : '编辑' }}
          </el-button>
          <el-button @click="back">
            <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
            返回
          </el-button>
        </div>
      </div>

      <!-- 未授权/不存在 显示错误提示 -->
      <el-result v-if="shelfError && !shelfLoading" icon="warning" :title="shelfError">
        <template #extra>
          <el-button type="primary" @click="back">返回</el-button>
        </template>
      </el-result>
      <template v-if="shelfError && !shelfLoading">
        <div class="h-4"></div>
      </template>
      <template v-if="!shelfError">
        <!-- 书架信息 -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
          <el-skeleton animated :loading="shelfLoading">
            <template #template>
              <el-skeleton-item variant="text" class="w-[40%] h-[18px]" />
              <el-skeleton-item variant="text" class="w-[70%] h-[14px]" />
              <div class="flex items-center justify-between mt-2">
                <el-skeleton-item variant="text" class="w-[50%] h-[18px]" />
              </div>
            </template>
            <template v-if="!isEditing">
              <div class="text-gray-700 text-sm leading-6">
                <div class="mb-1">
                  <span class="text-gray-500">名称：</span>
                  <span class="font-medium">{{ shelf?.name || '未命名书架' }}</span>
                  <el-tag v-if="shelf?.is_public" size="small" type="success" class="ml-2">
                    公开
                  </el-tag>
                  <el-tag v-else size="small" class="ml-2">私有</el-tag>
                </div>
                <div class="mb-1">
                  <span class="text-gray-500">简介：</span>
                  <span>{{ shelf?.description || '暂无简介' }}</span>
                </div>
                <div class="mt-2 text-gray-400">更多信息区域 · 占位</div>
              </div>
            </template>
            <template v-else>
              <div class="space-y-3">
                <el-form label-width="90px">
                  <el-form-item label="名称">
                    <el-input v-model="form.name" maxlength="190" />
                  </el-form-item>
                  <el-form-item label="描述">
                    <el-input v-model="form.description" type="textarea" maxlength="500" />
                  </el-form-item>
                  <el-form-item v-if="authStore.isRole('admin')" label="公开">
                    <el-switch v-model="form.is_public" />
                  </el-form-item>
                </el-form>
                <div class="flex justify-end gap-2">
                  <el-button type="danger" @click="deleteShelf">删除书架</el-button>
                  <el-button type="primary" @click="saveShelf">保存</el-button>
                </div>
              </div>
            </template>
          </el-skeleton>
        </div>

        <template v-if="userSettings.shelfDetail?.showFilters ?? true">
          <BookFilters
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
            class="mb-4"
          />
        </template>

        <!-- 排序控件（与书库页一致） -->
        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
          <div class="flex flex-row items-center">
            <el-select
              v-model="sort"
              placeholder="选择排序"
              @change="() => searchPage(1)"
              class="min-w-[150px]"
            >
              <el-option label="创建时间" value="created" />
              <el-option label="修改时间" value="modified" />
              <el-option label="评分" value="rating" />
              <el-option label="ID" value="id" />
            </el-select>
          </div>
          <el-button @click="toggleOrder">
            <span class="material-symbols-outlined" v-if="sort == 'created'">
              {{ order === 'desc' ? 'clock_arrow_down' : 'clock_arrow_up' }}
            </span>
            <span class="material-symbols-outlined" v-else-if="sort == 'modified'">
              {{ order === 'desc' ? 'edit_arrow_down' : 'edit_arrow_up' }}
            </span>
            <span class="material-symbols-outlined" v-else>
              {{ order === 'desc' ? 'arrow_downward' : 'arrow_upward' }}
            </span>
          </el-button>
        </div>
      </template>

      <BookGrid
        v-if="!shelfError"
        :data="data"
        :loading="loading"
        :meta="meta"
        :editable="isEditing"
        :show-add-button="true"
        @page-change="searchPage"
        @author-click="filterByAuthor"
        @toggle-read="toggleRead"
        @add-click="openAddDialog"
        @remove-click="confirmRemove"
      />
    </div>
  </section>

  <!-- 添加书籍 -->
  <el-dialog v-model="addVisible" title="添加书籍到当前书架" width="720px">
    <div class="flex items-center gap-2 mb-3">
      <el-input
        v-model="addQ"
        placeholder="搜索书名/作者"
        class="w-[300px]"
        @keyup.enter="searchAdd"
      />
      <el-button type="primary" :loading="addLoading" @click="searchAdd">搜索</el-button>
    </div>
    <el-empty v-if="!addLoading && addList.length === 0" description="输入关键字搜索书籍" />
    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <div v-for="b in addList" :key="b.id" class="bg-white rounded-lg shadow-sm p-2">
        <router-link :to="`/books/${b.id}`" class="block font-semibold truncate mb-1">
          {{ b.title }}
        </router-link>
        <div class="text-xs text-gray-500 truncate mb-2">
          {{ (b.authors || []).map(a => a.name).join(' / ') || '无作者' }}
        </div>
        <el-button size="small" type="primary" @click="addToShelf(b)">添加</el-button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import BookFilters from '@/components/Book/Filters.vue'
import BookGrid from '@/components/Book/BookGrid.vue'
import { booksApi } from '@/api/books'
import type { Book, Shelf } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { shelvesApi } from '@/api/shelves'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'
import { useLoading } from '@/composables/useLoading'
import { useBookActions } from '@/composables/useBookActions'

const { handleError, handleSuccess } = useErrorHandler()
const { toggleReadMark } = useBookActions()

const { isLoadingKey, startLoading, stopLoading } = useLoading()
const shelfLoading = computed(() => isLoadingKey('shelf'))
const addLoading = computed(() => isLoadingKey('addBooks'))

const router = useRouter()
const route = useRoute()
const shelfId = computed(() => Number(route.params.id))
const shelf = ref<(Shelf & { description?: string }) | null>(null)
const shelfError = ref<string | null>(null)
const authStore = useAuthStore()
const canManage = computed(() => {
  if (!shelf.value) return false
  if (authStore.isRole('admin')) return true
  return (shelf.value.user_id ?? 0) === (authStore.user?.id ?? -1)
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

// 编辑模式切换与表单
const isEditing = ref(false)
const form = ref<{ name: string; description?: string; is_public?: boolean }>({ name: '' })
function toggleEdit() {
  if (!canManage.value) return
  isEditing.value = !isEditing.value
  if (isEditing.value && shelf.value) {
    form.value = {
      name: shelf.value.name,
      description: shelf.value.description || '',
      is_public: shelf.value.is_public,
    }
  }
}
async function saveShelf() {
  if (!shelf.value) return
  try {
    await shelvesApi.updateRaw(shelf.value.id, {
      name: form.value.name.trim(),
      description: form.value.description || '',
      is_public: form.value.is_public,
    })
    await fetchShelfInfo()
    handleSuccess('已保存')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.saveShelf' })
  }
}

async function deleteShelf() {
  if (!shelf.value) return
  try {
    await ElMessageBox.confirm('确认删除该书架？该操作不可恢复', '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }
  try {
    await shelvesApi.remove(shelf.value.id)
    handleSuccess('已删除')
    if (authStore.isRole('admin')) {
      router.push({ name: 'admin-shelf-list' })
    } else {
      router.push({ name: 'user-shelves' })
    }
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.deleteShelf' })
  }
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

function toggleOrder() {
  order.value = order.value === 'desc' ? 'asc' : 'desc'
  loadPage(1)
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
const addQ = ref('')
const addList = ref<Book[]>([])
function openAddDialog() {
  addVisible.value = true
  addQ.value = ''
  addList.value = []
}
async function searchAdd() {
  startLoading('addBooks')
  try {
    const r = await booksApi.list({ q: addQ.value || undefined, perPage: 12 })
    addList.value = r.data
  } catch {
    addList.value = []
  } finally {
    stopLoading('addBooks')
  }
}
async function addToShelf(b: Book) {
  if (!shelf.value) return
  try {
    const currentBookIds = data.value.map(book => book.id)
    const newBookIds = Array.from(new Set([...currentBookIds, b.id]))
    await shelvesApi.setBooks(shelf.value.id, newBookIds)
    if (!data.value.find(x => x.id === b.id)) {
      data.value.push(b)
    }
    handleSuccess('已添加')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.addToShelf' })
  }
}

// 从书架移除
async function removeFromShelf(b: Book) {
  if (!shelf.value) return
  try {
    const newBookIds = data.value.filter(book => book.id !== b.id).map(book => book.id)
    await shelvesApi.setBooks(shelf.value.id, newBookIds)
    // 本页列表移除该书
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
