<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">文件管理</h2>
      <div class="flex items-center gap-2">
        <el-button @click="back"><span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回</el-button>
      </div>
    </div>

    <el-card shadow="never" class="mb-3">
      <div class="flex flex-wrap items-center gap-2">
          <el-input v-model="q" placeholder="按路径/MIME/SHA 搜索" class="w-full md:w-[260px]" clearable @keyup.enter="reload" />
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
          <el-button @click="showCleanup=true">清理工具</el-button>
      </div>
    </el-card>

    <el-dialog v-model="showCleanup" title="清理工具" width="680px">
      <div class="space-y-3 text-sm leading-6">
        <div>
          <div class="font-medium mb-1">说明</div>
          <ul class="list-disc pl-5 text-gray-600">
            <li><span class="font-medium text-gray-800">未引用封面</span>：被上传为封面的图片，但当前没有任何书籍将其设为封面。</li>
            <li><span class="font-medium text-gray-800">悬挂记录</span>：数据库中存在文件记录，但其关联的书籍已不存在。</li>
            <li><span class="font-medium text-gray-800">物理缺失</span>：数据库中存在文件记录，但磁盘上找不到对应文件。</li>
            <li><span class="font-medium text-gray-800">无记录的物理文件</span>：磁盘上存在文件，但数据库中没有对应记录。</li>
          </ul>
        </div>
        <div>
          <div class="font-medium mb-1">选择清理范围（可多选）</div>
          <div class="flex flex-wrap gap-4">
            <el-checkbox v-model="ckCovers">未引用封面</el-checkbox>
            <el-checkbox v-model="ckDangling">悬挂记录</el-checkbox>
            <el-checkbox v-model="ckMissing">物理缺失</el-checkbox>
            <el-checkbox v-model="ckOrphans">无记录的物理文件</el-checkbox>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <el-button @click="previewCleanup" :loading="cleaning">预览扫描</el-button>
          <el-switch v-model="removePhysical" :disabled="ckOrphans" active-text="同时删除物理文件" />
          <el-tooltip content="仅在执行时生效；当选择‘无记录的物理文件’时会强制开启此项" placement="top">
            <span class="text-gray-400 cursor-help material-symbols-outlined text-base">info</span>
          </el-tooltip>
          <el-button type="danger" :disabled="!cleanupPreview" @click="executeCleanup" :loading="cleaning">执行清理</el-button>
        </div>
        <el-alert v-if="cleanupPreview" type="info" show-icon :closable="false" class="mt-2">
          <template #title>扫描结果</template>
          <div class="mt-1 text-gray-700">
            <div class="flex flex-wrap gap-x-6 gap-y-1">
              <span>未引用封面：{{ cleanupPreview.summary.unused_covers }}</span>
              <span>悬挂记录：{{ cleanupPreview.summary.dangling_records }}</span>
              <span>物理缺失：{{ cleanupPreview.summary.missing_physical }}</span>
              <span>无记录的物理文件：{{ cleanupPreview.summary.orphans_physical }}</span>
            </div>
            <div class="mt-3 space-y-2 max-h-64 overflow-auto pr-2">
              <div v-if="cleanupPreview.preview?.covers?.length">
                <div class="font-medium mb-1">未引用封面列表</div>
                <ul class="list-disc pl-5">
                  <li v-for="it in cleanupPreview.preview.covers" :key="'c-'+it.id">#{{ it.id }} · {{ it.path }}</li>
                </ul>
              </div>
              <div v-if="cleanupPreview.preview?.dangling?.length">
                <div class="font-medium mb-1">悬挂记录列表</div>
                <ul class="list-disc pl-5">
                  <li v-for="it in cleanupPreview.preview.dangling" :key="'d-'+it.id">#{{ it.id }} · {{ it.path }}</li>
                </ul>
              </div>
              <div v-if="cleanupPreview.preview?.missing_physical?.length">
                <div class="font-medium mb-1">物理缺失记录</div>
                <ul class="list-disc pl-5">
                  <li v-for="it in cleanupPreview.preview.missing_physical" :key="'m-'+it.id">#{{ it.id }} · {{ it.path }}</li>
                </ul>
              </div>
              <div v-if="cleanupPreview.preview?.orphans_physical?.length">
                <div class="font-medium mb-1">无记录的物理文件</div>
                <ul class="list-disc pl-5">
                  <li v-for="it in cleanupPreview.preview.orphans_physical" :key="'o-'+it.path">{{ it.path }}</li>
                </ul>
              </div>
            </div>
          </div>
        </el-alert>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showCleanup=false">关闭</el-button>
        </span>
      </template>
    </el-dialog>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between"><span>文件列表</span><el-button text @click="reload">刷新</el-button></div>
      </template>
      <el-empty v-if="!loading && items.length===0" description="暂无数据" />
      <div v-else class="overflow-x-auto">
        <el-table :data="items" border stripe size="small" class="min-w-[980px]"
          :default-sort="{ prop: 'id', order: 'descending' }" @sort-change="onSortChange">
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
                <span class="text-gray-500 truncate inline-block max-w-[220px] align-middle">{{ row.path }}</span>
              </el-tooltip>
              <el-button link type="primary" size="small" class="ml-1 align-middle" @click="copy(row.path)">复制</el-button>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="360" align="center">
            <template #default="{ row }">
              <el-button size="small" @click="preview(row.id)">预览</el-button>
              <el-button size="small" @click="download(row.id)">下载</el-button>
              <el-popconfirm title="仅删除记录？" @confirm="remove(row.id,false)">
                <template #reference>
                  <el-button size="small" type="warning">删除记录</el-button>
                </template>
              </el-popconfirm>
              <el-popconfirm title="删除记录并删除物理文件？" @confirm="remove(row.id,true)">
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
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { adminFilesApi, type AdminFileItem } from '@/api/adminFiles'

const router = useRouter()

// 查询条件
const q = ref('')
const format = ref<string>('')
const unusedCovers = ref(false)
const missingPhysical = ref(false)

// 排序
const sortKey = ref<string>('id')
const sortOrder = ref<'asc'|'desc'>('desc')

// 列表
const items = ref<AdminFileItem[]>([])
const loading = ref(false)

// 清理
const showCleanup = ref(false)
const ckCovers = ref(true)
const ckDangling = ref(false)
const ckMissing = ref(false)
const ckOrphans = ref(false)
const cleanupPreview = ref<any|null>(null)
const removePhysical = ref(false)
const cleaning = ref(false)

// 当选择“无记录的物理文件”时，强制开启并锁定删除物理文件选项
watch(ckOrphans, (v) => {
  if (v) removePhysical.value = true
})

function back(){ router.back() }

function formatSize(n: number){
  if (!n || n<=0) return '0'
  const units = ['B','KB','MB','GB','TB']
  let i=0, v=n
  while (v>=1024 && i<units.length-1){ v/=1024; i++ }
  return `${v.toFixed( (i>=2)?2:0 )} ${units[i]}`
}

async function reload(){
  loading.value = true
  try {
    const data = await adminFilesApi.list({ q: q.value.trim() || undefined, format: format.value || undefined, unused_covers: unusedCovers.value || undefined, missing_physical: missingPhysical.value || undefined, sortKey: sortKey.value, sortOrder: sortOrder.value })
    items.value = data.items
  } catch(e:any){
    ElMessage.error(e?.message || '加载失败')
  } finally { loading.value = false }
}

function onSortChange(e: { prop?: string; order?: 'ascending'|'descending'|null }){
  if (e.prop) sortKey.value = e.prop
  if (e.order === 'ascending') sortOrder.value = 'asc'
  else if (e.order === 'descending') sortOrder.value = 'desc'
  reload()
}

function preview(id:number){ window.open(`/api/v1/files/${id}/preview`, '_blank') }
function download(id:number){ window.location.href = `/api/v1/files/${id}/download` }

async function remove(id:number, physical:boolean){
  try { await adminFilesApi.remove(id, physical); ElMessage.success('已删除'); reload() }
  catch(e:any){ ElMessage.error(e?.message || '删除失败') }
}

function copy(text: string){
  navigator.clipboard?.writeText(text).then(()=> ElMessage.success('已复制路径')).catch(()=> ElMessage.error('复制失败'))
}

async function previewCleanup(){
  cleaning.value = true
  try {
    const kinds = [] as Array<'covers'|'dangling'|'missing'|'orphans'>
    if (ckCovers.value) kinds.push('covers')
    if (ckDangling.value) kinds.push('dangling')
    if (ckMissing.value) kinds.push('missing')
    if (ckOrphans.value) kinds.push('orphans')
    if (kinds.length === 0) { ElMessage.error('请至少选择一项清理内容'); return }
    if (ckOrphans.value) removePhysical.value = true
    cleanupPreview.value = await adminFilesApi.cleanup({ kinds, dry: true, removePhysical: removePhysical.value })
  } catch(e:any){
    ElMessage.error(e?.message || '预览失败')
  } finally { cleaning.value = false }
}

async function executeCleanup(){
  if (!cleanupPreview.value) return
  cleaning.value = true
  try {
    const kinds = [] as Array<'covers'|'dangling'|'missing'|'orphans'>
    if (ckCovers.value) kinds.push('covers')
    if (ckDangling.value) kinds.push('dangling')
    if (ckMissing.value) kinds.push('missing')
    if (ckOrphans.value) kinds.push('orphans')
    if (kinds.length === 0) { ElMessage.error('请至少选择一项清理内容'); return }
    if (ckOrphans.value) removePhysical.value = true
    await adminFilesApi.cleanup({ kinds, dry: false, removePhysical: removePhysical.value })
    ElMessage.success('清理完成')
    cleanupPreview.value = null
    showCleanup.value = false
    reload()
  } catch(e:any){
    ElMessage.error(e?.message || '清理失败')
  } finally { cleaning.value = false }
}

reload()
</script>
