<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">文件管理</h2>
      <div class="flex items-center gap-2">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <el-card shadow="never" class="mb-3">
      <div class="flex flex-wrap items-center gap-2">
        <el-input
          v-model="q"
          placeholder="按路径/MIME/SHA 搜索"
          class="w-full md:w-[260px]"
          clearable
          @keyup.enter="reload"
        />
        <el-select v-model="format" placeholder="格式" class="w-[140px]">
          <el-option label="全部" value="" />
          <el-option label="封面" value="cover" />
          <el-option label="EPUB" value="epub" />
          <el-option label="PDF" value="pdf" />
          <el-option label="TXT" value="txt" />
          <el-option label="其它" value="file" />
        </el-select>
        <el-checkbox v-model="unusedCovers">仅未被引用的封面</el-checkbox>
        <el-checkbox v-model="missingPhysical">仅物理缺失</el-checkbox>
        <el-button type="primary" @click="reload" :loading="loading">搜索</el-button>
        <el-button @click="showCleanup = true">清理工具</el-button>
      </div>
    </el-card>

    <FileCleanupDialog v-model="showCleanup" @executed="reload" />

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span>文件列表</span>
          <el-button text @click="reload">刷新</el-button>
        </div>
      </template>
      <el-empty v-if="!loading && items.length === 0" description="暂无数据" />
      <div v-else class="overflow-x-auto">
        <el-table
          :data="items"
          border
          stripe
          size="small"
          class="min-w-[980px]"
          :default-sort="{ prop: 'id', order: 'descending' }"
          @sort-change="onSortChange"
        >
          <el-table-column label="#" prop="id" width="80" sortable="custom" />
          <el-table-column label="文件名" prop="filename" min-width="180">
            <template #default="{ row }">
              <span class="font-medium">{{ row.filename }}</span>
            </template>
          </el-table-column>
          <el-table-column label="书籍" width="220">
            <template #default="{ row }">
              <div v-if="row.book">#{{ row.book.id }} · {{ row.book.title }}</div>
              <div v-else class="text-gray-400">无</div>
            </template>
          </el-table-column>
          <el-table-column label="格式" prop="format" width="100" sortable="custom" />
          <el-table-column label="大小" prop="size" width="100" sortable="custom">
            <template #default="{ row }">{{ formatSize(row.size) }}</template>
          </el-table-column>
          <el-table-column label="MIME" prop="mime" width="160" />
          <el-table-column label="作为封面" prop="used_as_cover" width="110" sortable="custom">
            <template #default="{ row }">
              <el-tag size="small" type="success" v-if="row.used_as_cover">是</el-tag>
              <el-tag size="small" type="info" v-else>否</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="物理存在" prop="physical_exists" width="110" sortable="custom">
            <template #default="{ row }">
              <el-tag size="small" type="success" v-if="row.physical_exists">存在</el-tag>
              <el-tag size="small" type="danger" v-else>缺失</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="路径" prop="path" min-width="240">
            <template #default="{ row }">
              <el-tooltip :content="row.path" placement="top-start" effect="dark">
                <span class="text-gray-500 truncate inline-block max-w-[220px] align-middle">
                  {{ row.path }}
                </span>
              </el-tooltip>
              <el-button
                link
                type="primary"
                size="small"
                class="ml-1 align-middle"
                @click="copy(row.path)"
              >
                复制
              </el-button>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="360" align="center">
            <template #default="{ row }">
              <el-button size="small" @click="preview(row.id)">预览</el-button>
              <el-button size="small" @click="download(row.id)">下载</el-button>
              <el-popconfirm title="仅删除记录？" @confirm="remove(row.id, false)">
                <template #reference>
                  <el-button size="small" type="warning">删除记录</el-button>
                </template>
              </el-popconfirm>
              <el-popconfirm title="删除记录并删除物理文件？" @confirm="remove(row.id, true)">
                <template #reference>
                  <el-button size="small" type="danger">删除记录及文件</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminFilesApi, type AdminFileItem } from '@/api/adminFiles'
import { getPreviewUrl, getDownloadUrl } from '@/utils/signedUrls'
import { useErrorHandler } from '@/composables/useErrorHandler'
import FileCleanupDialog from '@/components/Admin/FileCleanupDialog.vue'
import { useSimpleLoading } from '@/composables/useLoading'

const { handleError, handleSuccess } = useErrorHandler()
const { loading, setLoading } = useSimpleLoading(false)
const router = useRouter()

// 查询条件
const q = ref('')
const format = ref<string>('')
const unusedCovers = ref(false)
const missingPhysical = ref(false)

// 排序
const sortKey = ref<string>('id')
const sortOrder = ref<'asc' | 'desc'>('desc')

// 列表
const items = ref<AdminFileItem[]>([])

const showCleanup = ref(false)

function back() {
  router.back()
}

function formatSize(n: number) {
  if (!n || n <= 0) return '0'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0,
    v = n
  while (v >= 1024 && i < units.length - 1) {
    v /= 1024
    i++
  }
  return `${v.toFixed(i >= 2 ? 2 : 0)} ${units[i]}`
}

async function reload() {
  setLoading(true)
  try {
    const data = await adminFilesApi.list({
      q: q.value.trim() || undefined,
      format: format.value || undefined,
      unused_covers: unusedCovers.value || undefined,
      missing_physical: missingPhysical.value || undefined,
      sortKey: sortKey.value,
      sortOrder: sortOrder.value,
    })
    items.value = data.items
  } catch (e: any) {
    handleError(e, { context: 'Admin.FileManager.reload' })
  } finally {
    setLoading(false)
  }
}

function onSortChange(e: { prop?: string; order?: 'ascending' | 'descending' | null }) {
  if (e.prop) sortKey.value = e.prop
  if (e.order === 'ascending') sortOrder.value = 'asc'
  else if (e.order === 'descending') sortOrder.value = 'desc'
  reload()
}

async function preview(id: number) {
  const u = await getPreviewUrl(id)
  window.open(u, '_blank')
}
async function download(id: number) {
  window.location.href = await getDownloadUrl(id)
}

async function remove(id: number, physical: boolean) {
  try {
    await adminFilesApi.remove(id, physical)
    handleSuccess('已删除文件')
    reload()
  } catch (e: any) {
    handleError(e, { context: 'Admin.FileManager.remove' })
  }
}

function copy(text: string) {
  navigator.clipboard
    ?.writeText(text)
    .then(() => handleSuccess('已复制路径'))
    .catch(() =>
      handleError(new Error('Copy failed'), {
        context: 'Admin.FileManager.copy',
        message: '复制路径失败',
      }),
    )
}

reload()
</script>
