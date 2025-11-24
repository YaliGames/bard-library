<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
      <h2 class="text-xl font-semibold">图书管理</h2>
      <div class="flex items-center gap-2 flex-wrap">
        <el-button class="hidden sm:inline-flex" @click="goLibrary">
          <span class="material-symbols-outlined mr-1 text-lg">library_books</span>
          前往书库
        </el-button>
        <el-button
          v-permission="'files.upload'"
          type="primary"
          plain
          class="hidden sm:inline-flex"
          @click="goQuickUpload"
        >
          <span class="material-symbols-outlined mr-1 text-lg">upload</span>
          快速上传
        </el-button>
        <el-button
          v-permission="'metadata.batch_scrape'"
          type="primary"
          plain
          class="hidden sm:inline-flex"
          @click="goScrapingTasks"
        >
          <span class="material-symbols-outlined mr-1 text-lg">cloud_download</span>
          快速刮削
        </el-button>
        <el-button
          v-permission="'books.create'"
          type="primary"
          class="hidden sm:inline-flex"
          @click="goCreateNew"
        >
          <span class="material-symbols-outlined mr-1 text-lg">add</span>
          新建
        </el-button>

        <el-button circle class="sm:hidden" @click="goLibrary" title="前往书库">
          <span class="material-symbols-outlined text-lg">library_books</span>
        </el-button>
        <el-button
          v-permission="'files.upload'"
          type="primary"
          plain
          circle
          class="sm:hidden"
          @click="goQuickUpload"
          title="快速上传"
        >
          <span class="material-symbols-outlined text-lg">upload</span>
        </el-button>
        <el-button
          v-permission="'metadata.batch_scrape'"
          type="primary"
          plain
          circle
          class="sm:hidden"
          @click="goScrapingTasks"
          title="快速刮削"
        >
          <span class="material-symbols-outlined text-lg">cloud_download</span>
        </el-button>
        <el-button
          v-permission="'books.create'"
          type="primary"
          circle
          class="sm:hidden"
          @click="goCreateNew"
          title="新建"
        >
          <span class="material-symbols-outlined text-lg">add</span>
        </el-button>
      </div>
    </div>

    <BookFilters
      v-model="filters"
      :showShelves="false"
      :showTags="true"
      :showAuthor="true"
      :showReadState="false"
      :showRating="true"
      :enableExpand="true"
      :defaultExpanded="false"
      searchPlaceholder="搜索标题…"
      @search="go(1)"
      @reset="reset"
      class="mb-4"
    />

    <div class="bg-white rounded-lg shadow-sm p-4">
      <el-empty description="暂无数据" v-if="!loading && (!rows || rows.length === 0)" />
      <template v-else>
        <el-table :data="rows" v-loading="loading" border stripe>
          <el-table-column label="图书信息" prop="title" min-width="200" fixed>
            <template #default="{ row }">
              <div class="flex items-center gap-3">
                <div class="w-14 flex-shrink-0">
                  <CoverImage
                    :file-id="row.cover_file_id || null"
                    :title="row.title"
                    :authors="(row.authors || []).map((a: any) => a.name)"
                    fontSize="11px"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-medium truncate text-sm">{{ row.title || '#' + row.id }}</div>
                  <div class="text-xs text-gray-500 truncate" v-if="row.authors?.length">
                    {{ row.authors.map((a: any) => a.name).join(' / ') }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="简介" min-width="220">
            <template #default="{ row }">
              <div v-if="row.description" class="text-xs text-gray-700">
                <el-tooltip placement="top" :show-after="300" popper-class="max-w-md">
                  <template #content>
                    <div class="max-w-md whitespace-pre-wrap break-words">
                      {{ row.description }}
                    </div>
                  </template>
                  <div class="desc-clamp cursor-help">
                    {{ truncate(row.description, 150) }}
                  </div>
                </el-tooltip>
              </div>
              <div v-else class="text-xs text-gray-400">暂无简介</div>
            </template>
          </el-table-column>

          <el-table-column label="出版信息" min-width="150">
            <template #default="{ row }">
              <div class="text-xs">
                <span v-if="row.publisher" class="text-gray-600">
                  {{ row.publisher }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </div>
              <div class="text-xs">
                <span v-if="row.published_at" class="text-gray-600">
                  {{ formatDate(row.published_at) }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="ISBN" width="135">
            <template #default="{ row }">
              <div class="text-xs text-gray-600" v-if="row.isbn13 || row.isbn10">
                <div v-if="row.isbn13">{{ row.isbn13 }}</div>
                <div v-else-if="row.isbn10">{{ row.isbn10 }}</div>
              </div>
              <div v-else class="text-gray-400 text-center">-</div>
            </template>
          </el-table-column>

          <el-table-column label="附件" width="80" align="center">
            <template #default="{ row }">
              <div class="flex items-center justify-center gap-1">
                <span
                  class="material-symbols-outlined text-lg"
                  :class="getAttachmentCount(row) > 0 ? 'text-primary' : 'text-gray-400'"
                >
                  {{ getAttachmentCount(row) > 0 ? 'attach_file' : 'attach_file_off' }}
                </span>
                <span
                  class="text-xs font-medium"
                  :class="getAttachmentCount(row) > 0 ? 'text-primary' : 'text-gray-400'"
                >
                  {{ getAttachmentCount(row) }}
                </span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="标签" width="140">
            <template #default="{ row }">
              <div v-if="row.tags?.length">
                <el-tooltip
                  :content="row.tags.map((t: any) => t.name).join('、')"
                  placement="top"
                  :show-after="300"
                >
                  <div class="text-xs text-gray-600">
                    {{
                      row.tags
                        .slice(0, 3)
                        .map((t: any) => t.name)
                        .join('、')
                    }}
                    <span v-if="row.tags.length > 3" class="text-gray-400">
                      等{{ row.tags.length }}个
                    </span>
                  </div>
                </el-tooltip>
              </div>
              <div v-else class="text-xs text-gray-400 text-center">-</div>
            </template>
          </el-table-column>

          <el-table-column label="系列" width="110">
            <template #default="{ row }">
              <div class="text-xs text-gray-600">{{ row.series?.title || '-' }}</div>
            </template>
          </el-table-column>

          <el-table-column label="页数" width="70" align="center">
            <template #default="{ row }">
              <div class="text-xs text-gray-600">{{ row.pages ?? '-' }}</div>
            </template>
          </el-table-column>

          <el-table-column label="评分" width="130" align="center">
            <template #default="{ row }">
              <el-rate
                v-model="row.rating"
                :max="5"
                disabled
                show-score
                score-template="{value}"
                size="small"
              />
            </template>
          </el-table-column>

          <el-table-column label="操作" width="160" align="center" fixed="right">
            <template #default="{ row }">
              <el-button
                v-permission="'books.edit'"
                size="small"
                type="primary"
                @click="edit(row.id)"
              >
                编辑
              </el-button>
              <el-button
                v-permission="'books.delete'"
                size="small"
                type="danger"
                plain
                @click="del(row.id)"
              >
                删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="mt-3 flex justify-center" v-if="meta">
          <el-pagination
            background
            layout="prev, pager, next, jumper"
            :total="meta.total"
            :page-size="meta.per_page"
            :current-page="meta.current_page"
            @current-change="go"
          />
        </div>
      </template>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CoverImage from '@/components/CoverImage.vue'
import BookFilters from '@/components/Book/Filters.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { ElMessageBox } from 'element-plus'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'

const { handleError, handleSuccess } = useErrorHandler()

const router = useRouter()
const filters = ref({
  q: '',
  authorId: null as number | null,
  tagIds: [] as number[],
  shelfId: null as number | null,
  readState: null as any,
  ratingRange: [0, 5] as [number, number],
})

// 最大简介字符数与辅助函数
const MAX_DESC_CHARS = 300
function truncate(text: string, max = MAX_DESC_CHARS) {
  if (!text) return ''
  return text.length <= max ? text : text.slice(0, max) + '…'
}

function formatDate(dateStr: string | null | undefined) {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString()
  } catch {
    return dateStr as any
  }
}

// 计算附件数量（排除封面文件）
function getAttachmentCount(book: Book) {
  if (!book.files || book.files.length === 0) return 0
  return book.files.filter((f: any) => f.format !== 'cover').length
}

const {
  data: rows,
  loading,
  currentPage,
  lastPage,
  total,
  perPage,
  loadPage,
} = usePagination<Book>({
  fetcher: async (page: number) => {
    const r = await booksApi.list({
      q: filters.value.q || undefined,
      page,
      authorId: filters.value.authorId || undefined,
      tagId: filters.value.tagIds.length ? filters.value.tagIds[0] : undefined,
      // 管理页暂不提供 shelf/readState
    })
    return r
  },
  onError: (e: any) => {
    handleError(e, { context: 'Admin.BookList.fetchList' })
  },
})

const meta = {
  get current_page() {
    return currentPage.value
  },
  get last_page() {
    return lastPage.value
  },
  get per_page() {
    return perPage.value
  },
  get total() {
    return total.value
  },
}

function go(p: number) {
  loadPage(p)
}

function reset() {
  Object.assign(filters.value, {
    q: '',
    authorId: null,
    tagIds: [],
    shelfId: null,
    readState: null,
    ratingRange: [0, 5] as [number, number],
  })
  go(1)
}
function edit(id: number) {
  router.push({ name: 'admin-book-edit', params: { id } })
}
async function del(id: number) {
  try {
    await ElMessageBox.confirm('确认删除该图书？此操作不可撤销', '删除确认', {
      type: 'warning',
      confirmButtonText: '继续',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }
  // 询问是否连同封面与附件一起删除
  let withFiles = false
  try {
    await ElMessageBox.confirm('是否连同封面与附件一起删除？', '删除方式', {
      type: 'warning',
      confirmButtonText: '是，连同文件',
      cancelButtonText: '否，仅删除记录',
      distinguishCancelAndClose: true,
    })
    withFiles = true
  } catch {
    // 点击“否，仅删除记录”或关闭，则按仅删除记录处理
    withFiles = false
  }
  try {
    await booksApi.remove(id, { withFiles })
    handleSuccess(withFiles ? '已删除（含封面与附件）' : '已删除记录')
    await loadPage(currentPage.value)
  } catch (e: any) {
    handleError(e, { context: 'Admin.BookList.del' })
  }
}
function goCreateNew() {
  router.push({ name: 'admin-book-edit', params: { id: 'new' } })
}
function goQuickUpload() {
  router.push({ name: 'admin-upload' })
}
function goScrapingTasks() {
  router.push({ name: 'admin-scraping-tasks-create' })
}
function goLibrary() {
  router.push({ name: 'books' })
}

onMounted(() => loadPage(1))
</script>

<style scoped>
.desc-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 3; /* 显示行数 */
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
