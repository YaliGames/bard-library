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

        <template v-if="false">
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

      <div
        v-if="!shelfError && loading"
        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
      >
        <div v-for="i in skeletonCount" :key="i" class="bg-white rounded-lg shadow-sm p-4">
          <el-skeleton animated :loading="true">
            <template #template>
              <div class="flex flex-col gap-2">
                <div class="w-full aspect-[3/4] flex">
                  <el-skeleton-item variant="image" class="w-full h-full rounded" />
                </div>
                <el-skeleton-item variant="text" class="w-[70%] h-[18px] mt-1.5" />
                <el-skeleton-item variant="text" class="w-[50%] h-[14px]" />
                <div class="flex items-center justify-between mt-2">
                  <el-skeleton-item variant="text" class="w-[120px] h-[18px]" />
                </div>
              </div>
            </template>
          </el-skeleton>
        </div>
      </div>
      <template v-else-if="!shelfError">
        <div
          v-if="data.length > 0"
          class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
        >
          <div
            v-if="isEditing"
            class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-center cursor-pointer hover:bg-gray-50 border-2 border-dashed border-gray-200"
            @click="openAddDialog"
          >
            <div class="flex flex-col items-center text-gray-500">
              <span class="material-symbols-outlined text-3xl mb-1">add</span>
              <div>添加书籍</div>
            </div>
          </div>
          <div class="relative group" v-for="b in data" :key="b.id">
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="flex flex-col gap-1.5">
                <router-link :to="`/books/${b.id}`">
                  <CoverImage
                    :file-id="b.cover_file_id || null"
                    :title="b.title"
                    :authors="(b.authors || []).map(a => a.name)"
                    class="rounded"
                  >
                    <template #overlay v-if="userSettings.bookList?.showReadTag">
                      <el-tag v-if="b.is_read_mark" type="success" effect="dark" size="small">
                        已读
                      </el-tag>
                      <el-tag v-else-if="b.is_reading" type="warning" effect="dark" size="small">
                        正在阅读
                      </el-tag>
                    </template>
                  </CoverImage>
                  <div class="font-semibold mt-2">{{ b.title || '#' + b.id }}</div>
                </router-link>
                <div class="text-gray-600 text-sm flex flex-wrap gap-1">
                  <template v-for="(a, idx) in b.authors || []" :key="a.id">
                    <div class="cursor-pointer text-primary" @click="filterByAuthor(a.id)">
                      {{ a.name }}
                    </div>
                    <span v-if="idx < (b?.authors ?? []).length - 1">/</span>
                  </template>
                  <div class="text-gray-500" v-if="(b.authors || []).length === 0">暂无作者</div>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <el-rate
                    v-model="b.rating"
                    :max="5"
                    allow-half
                    disabled
                    show-score
                    score-template="{value}"
                  />
                  <template v-if="userSettings.bookList?.showMarkReadButton && isLoggedIn">
                    <el-tooltip :content="b.is_read_mark ? '取消已读' : '标为已读'" placement="top">
                      <el-button
                        size="small"
                        :type="b.is_read_mark ? 'success' : 'default'"
                        @click="toggleRead(b)"
                        circle
                      >
                        <span class="material-symbols-outlined">done_all</span>
                      </el-button>
                    </el-tooltip>
                  </template>
                </div>
              </div>
            </div>
            <div
              v-if="isEditing"
              class="absolute inset-0 rounded-lg bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center"
            >
              <div
                @click.prevent="confirmRemove(b)"
                class="rounded-full p-2 bg-white cursor-pointer hover:bg-danger hover:text-white flex items-center justify-center"
              >
                <span class="material-symbols-outlined">delete</span>
              </div>
            </div>
          </div>
        </div>
        <div
          v-else-if="isEditing"
          class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
        >
          <div
            class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-center cursor-pointer hover:bg-gray-50 border-2 border-dashed border-gray-200"
            @click="openAddDialog"
          >
            <div class="flex flex-col items-center text-gray-500">
              <span class="material-symbols-outlined text-3xl mb-1">add</span>
              <div>添加书籍</div>
            </div>
          </div>
        </div>
        <el-empty description="暂无书籍" v-else />

        <div v-if="meta" class="mt-3 flex justify-center">
          <el-pagination
            background
            layout="prev, pager, next, jumper"
            :total="meta.total"
            :page-size="meta.per_page"
            :current-page="meta.current_page"
            @current-change="searchPage"
          />
        </div>
      </template>
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
import CoverImage from '@/components/CoverImage.vue'
import BookFilters from '@/components/Book/Filters.vue'
import { booksApi } from '@/api/books'
import type { Book, Shelf } from '@/api/types'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { shelvesApi } from '@/api/shelves'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'
import { useLoading } from '@/composables/useLoading'

const { handleError, handleSuccess } = useErrorHandler()

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
const isLoggedIn = computed(() => authStore.isLoggedIn)
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

const skeletonCount = computed(() => Math.max(1, Number(perPage.value || 12)))

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
  const target = !(b as any).is_read_mark || (b as any).is_read_mark === 0 ? true : false
  try {
    await booksApi.markRead(b.id, target)
    ;(b as any).is_read_mark = target ? 1 : 0
    handleSuccess(target ? '已标记为已读' : '已取消已读')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.toggleRead' })
  }
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
    // 取该书当前的书架列表
    const full = await booksApi.get(b.id)
    const allIds = (full.shelves || []).map((s: any) => s.id)
    // 根据权限构造要提交的 shelf_ids
    let submitIds: number[] = []
    if (authStore.isRole('admin')) {
      submitIds = Array.from(new Set([...allIds, shelf.value.id]))
    } else {
      const myIds = (full.shelves || [])
        .filter((s: any) => (s.user_id ?? 0) === (authStore.user?.id ?? -1))
        .map((s: any) => s.id)
      submitIds = Array.from(new Set([...myIds, shelf.value.id]))
    }
    await booksApi.setShelves(b.id, submitIds)
    handleSuccess('已添加')
  } catch (e: any) {
    handleError(e, { context: 'ShelfDetail.addToShelf' })
  }
}

// 从书架移除
async function removeFromShelf(b: Book) {
  if (!shelf.value) return
  try {
    const full = await booksApi.get(b.id)
    const allIds = (full.shelves || []).map((s: any) => s.id)
    let submitIds: number[] = []
    if (authStore.isRole('admin')) {
      submitIds = allIds.filter((id: number) => id !== shelf.value!.id)
    } else {
      const myIds = (full.shelves || [])
        .filter((s: any) => (s.user_id ?? 0) === (authStore.user?.id ?? -1))
        .map((s: any) => s.id)
      submitIds = myIds.filter((id: number) => id !== shelf.value!.id)
    }
    await booksApi.setShelves(b.id, submitIds)
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
