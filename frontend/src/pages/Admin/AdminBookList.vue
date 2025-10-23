<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">图书管理</h2>
      <div class="flex items-center">
        <el-button type="primary" plain @click="goQuickUpload">
          <span class="material-symbols-outlined mr-1 text-lg">upload</span> 快速上传
        </el-button>
        <el-button type="primary" @click="goCreateNew">
          <span class="material-symbols-outlined mr-1 text-lg">add</span> 新建
        </el-button>
      </div>
    </div>

    <BookFilters v-model="filters" :showShelves="false" :showTags="true" :showAuthor="true" :showReadState="false"
      :showRating="true" :enableExpand="true" :defaultExpanded="false" searchPlaceholder="搜索标题…" @search="go(1)"
      @reset="reset" class="mb-4" />

    <div class="bg-white rounded-lg shadow-sm p-4">
      <el-empty description="暂无数据" v-if="!loading && (!rows || rows.length === 0)" />
      <template v-else>
        <el-table :data="rows" v-loading="loading" border>
          <el-table-column label="图书信息" prop="title" min-width="220">
            <template #default="{ row }">
              <div class="flex items-center gap-3">
                <div class="w-16">
                  <CoverImage :file-id="row.cover_file_id || null" iconSize="32px" />
                </div>
                <div class="min-w-0">
                  <div class="font-medium truncate">{{ row.title || ('#' + row.id) }}</div>
                  <div class="text-xs text-gray-500 truncate" v-if="row.authors?.length">
                    {{ row.authors.map((a: any) => a.name).join(' / ') }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="简介" min-width="220">
            <template #default="{ row }">
              <div class="font-medium">{{ row.description || '暂无简介' }}</div>
            </template>
          </el-table-column>
          <el-table-column label="评分" width="160" align="center">
            <template #default="{ row }">
              <el-rate v-model="row.rating" :max="5" disabled show-score score-template="{value}" />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="160" align="center" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="edit(row.id)">编辑</el-button>
              <el-button size="small" type="danger" plain @click="del(row.id)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="mt-3 flex justify-center" v-if="meta">
          <el-pagination background layout="prev, pager, next, jumper" :total="meta.total"
            :page-size="meta.per_page" :current-page="meta.current_page" @current-change="go" />

        </div>
      </template>
    </div>
  </section>

</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CoverImage from '@/components/CoverImage.vue'
import BookFilters from '@/components/BookFilters.vue'
import { booksApi } from '@/api/books'
import type { Book } from '@/api/types'
import { ElMessage, ElMessageBox } from 'element-plus'

const router = useRouter()
const filters = ref({
  q: '',
  authorId: null as number | null,
  tagIds: [] as number[],
  shelfId: null as number | null,
  readState: null as any,
  ratingRange: [0, 10] as [number, number],
})
const rows = ref<Book[]>([])
const meta = ref<any>(null)
const loading = ref(false)

// 封面地址由 CoverImage 组件内部处理

async function fetchList(page = 1) {
  loading.value = true
  try {
    const r = await booksApi.list({
      q: filters.value.q || undefined,
      page,
      author_id: filters.value.authorId || undefined,
      tag_id: filters.value.tagIds.length ? filters.value.tagIds : undefined,
      min_rating: filters.value.ratingRange?.[0],
      max_rating: filters.value.ratingRange?.[1],
      // 管理页暂不提供 shelf/readState
    })
    rows.value = r.data
    meta.value = r.meta
  } catch (e: any) {
    ElMessage.error(e?.message || '加载失败')
  } finally {
    loading.value = false
  }
}
function go(p: number) { fetchList(p) }
function reset() { Object.assign(filters.value, { q: '', authorId: null, tagIds: [], shelfId: null, readState: null, ratingRange: [0, 10] as [number, number] }); go(1) }
function edit(id: number) { router.push({ name: 'admin-book-edit', params: { id } }) }
async function del(id: number) {
  try {
    await ElMessageBox.confirm('确认删除该图书？此操作不可撤销', '删除确认', { type: 'warning', confirmButtonText: '继续', cancelButtonText: '取消' })
  } catch { return }
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
  } catch (e) {
    // 点击“否，仅删除记录”或关闭，则按仅删除记录处理
    withFiles = false
  }
  try {
    await booksApi.remove(id, { withFiles })
    ElMessage.success(withFiles ? '已删除（含封面与附件）' : '已删除记录')
    await fetchList(meta.value?.current_page || 1)
  } catch (e: any) { ElMessage.error(e?.message || '删除失败') }
}
function goCreateNew() { router.push({ name: 'admin-book-edit', params: { id: 'new' } }) }
function goQuickUpload() { router.push({ name: 'admin-upload' }) }

onMounted(() => fetchList(1))
</script>
