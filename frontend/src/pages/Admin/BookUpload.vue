<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">快速上传图书</h2>
      <div class="flex items-center">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <el-alert
      type="info"
      show-icon
      :closable="false"
      class="mb-4"
      description="支持上传 EPUB/PDF/TXT/ZIP 等格式，系统会自动解析生成图书记录。支持单文件或多文件批量上传。"
    />

    <el-card shadow="never">
      <div class="flex items-center justify-between mb-2">
        <div></div>
        <div class="flex items-center gap-2">
          <el-button size="small" @click="restoreLast" :disabled="!hasSaved">
            恢复上次导入
          </el-button>
          <el-button size="small" @click="clearSaved" :disabled="!hasSaved">清除记录</el-button>
        </div>
      </div>
      <el-steps :active="step" finish-status="success" class="mb-4">
        <el-step title="选择文件" />
        <el-step title="上传文件" />
        <el-step title="编辑图书" />
      </el-steps>
      <el-form label-width="100px" @submit.prevent v-if="step === 0">
        <el-form-item label="文件">
          <el-upload
            :key="uploadKey"
            ref="uploader"
            drag
            multiple
            :limit="maxBatch"
            v-model:file-list="uiFileList"
            :auto-upload="false"
            :on-change="onFileChange"
            :on-remove="onFileRemove"
            :on-exceed="onExceed"
            :show-file-list="true"
            accept=".epub,.pdf,.txt,.zip"
          >
            <i class="el-icon--upload" />
            <div class="el-upload__text">
              拖拽文件到此处，或
              <em>点击选择</em>
            </div>
            <template #tip>
              <div class="el-upload__tip">
                单次最多 {{ maxBatch }} 个文件；支持 EPUB/PDF/TXT/ZIP
                <span v-if="sizeLimitText">；单文件不超过 {{ sizeLimitText }}</span>
              </div>
            </template>
          </el-upload>
        </el-form-item>
        <el-form-item label="TXT 章节解析" v-if="hasTxt">
          <el-switch v-model="parseTxt" />
          <span class="text-gray-500 text-sm ml-2">
            勾选后将尝试在上传后解析 TXT 章节（可稍后在章节工具中处理）
          </span>
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            :loading="uploading"
            :disabled="files.length === 0"
            @click="submit"
          >
            <template v-if="!uploading">开始上传 ({{ files.length }})</template>
            <template v-else>上传中…（{{ doneCount }}/{{ files.length }}）</template>
          </el-button>
          <el-button :disabled="uploading" @click="reset">重置</el-button>
        </el-form-item>
      </el-form>

      <div v-if="step === 1" class="mt-4">
        <div class="mb-2 text-sm text-gray-600">总体进度：{{ uploadProgress }}%</div>
        <el-progress :percentage="uploadProgress" :status="uploading ? 'active' : 'success'" />
        <div class="mt-4" v-if="files.length">
          <div class="mb-1 text-sm text-gray-600">
            当前文件（{{ currentIndex + 1 }}/{{ files.length }}）：{{ files[currentIndex]?.name }}
          </div>
          <el-progress
            :percentage="currentFileProgress"
            :status="uploading ? 'active' : 'success'"
          />
        </div>
        <div class="mt-4" v-if="files.length > 1">
          <el-table :data="fileProgressRows" size="small" border>
            <el-table-column label="文件" prop="name" min-width="240" />
            <el-table-column label="进度" width="220">
              <template #default="{ row }">
                <el-progress :percentage="row.percent" :status="row.status" />
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <div v-if="step === 2 && results.length > 0" class="mt-4">
        <!-- 单文件结果：保持原有成功展示 -->
        <template v-if="results.length === 1">
          <template v-if="results[0].status === 'error'">
            <el-result icon="error" title="上传失败" :sub-title="results[0].error"></el-result>
          </template>
          <template v-else>
            <el-result
              icon="success"
              title="上传完成"
              :sub-title="
                results[0].data?.duplicate ? '文件已存在，已定位到原书籍' : '已成功创建/更新图书'
              "
            >
              <template #extra>
                <router-link
                  v-if="results[0].data?.book?.id"
                  :to="{ name: 'book-detail', params: { id: results[0].data.book.id } }"
                >
                  <el-button type="primary" class="mr-2">查看详情</el-button>
                </router-link>
                <router-link
                  v-if="results[0].data?.book?.id"
                  :to="{ name: 'admin-book-edit', params: { id: results[0].data.book.id } }"
                >
                  <el-button>前往编辑</el-button>
                </router-link>
              </template>
            </el-result>
            <el-descriptions v-if="results[0].data?.book" :column="1" border class="mt-3">
              <el-descriptions-item label="书名">
                {{ results[0].data.book?.title || '#' + results[0].data.book?.id }}
              </el-descriptions-item>
              <el-descriptions-item label="ID">{{ results[0].data.book?.id }}</el-descriptions-item>
            </el-descriptions>
          </template>
        </template>
        <!-- 多文件结果列表 -->
        <template v-else>
          <el-alert
            type="success"
            :closable="false"
            show-icon
            class="mb-3"
            :title="`上传完成：成功 ${successCount} / ${results.length}，失败 ${failCount}`"
          />
          <el-table :data="results" border>
            <el-table-column label="文件" prop="fileName" min-width="200" />
            <el-table-column label="状态" width="120">
              <template #default="{ row }">
                <el-tag v-if="row.status === 'success'" type="success">成功</el-tag>
                <el-tag v-else type="danger">失败</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="结果" width="120">
              <template #default="{ row }">
                <template v-if="row.status === 'success'">
                  <el-tag v-if="row.data?.duplicate || row.data?.created === false" type="info">
                    已存在
                  </el-tag>
                  <el-tag v-else type="success">新建</el-tag>
                </template>
                <span v-else class="text-gray-400">-</span>
              </template>
            </el-table-column>
            <el-table-column label="书籍" min-width="220">
              <template #default="{ row }">
                <template v-if="row.status === 'success' && row.data?.book">
                  <div class="font-medium">{{ row.data.book.title || '#' + row.data.book.id }}</div>
                  <div class="text-xs text-gray-500">#{{ row.data.book.id }}</div>
                </template>
                <template v-else>
                  <span class="text-gray-500">-</span>
                </template>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="200" align="center">
              <template #default="{ row }">
                <template v-if="row.status === 'success' && row.data?.book?.id">
                  <router-link :to="{ name: 'book-detail', params: { id: row.data.book.id } }">
                    <el-button size="small" type="primary" plain>查看</el-button>
                  </router-link>
                  <router-link :to="{ name: 'admin-book-edit', params: { id: row.data.book.id } }">
                    <el-button size="small" class="ml-2">编辑</el-button>
                  </router-link>
                </template>
                <template v-else-if="row.status === 'error'">
                  <el-tooltip :content="row.error" placement="top">
                    <el-button size="small" type="danger" plain>错误</el-button>
                  </el-tooltip>
                </template>
              </template>
            </el-table-column>
          </el-table>
        </template>
      </div>
    </el-card>
  </section>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { importsApi } from '@/api/imports'
import { useRouter } from 'vue-router'
import { systemSettingsApi } from '@/api/systemSettings'
import { formatBytes } from '@/utils/systemSettings'

const uploader = ref()
const uploadKey = ref(0)
const uiFileList = ref<any[]>([])
const files = ref<File[]>([])
const uploading = ref(false)
const results = ref<any[]>([])
const parseTxt = ref(false)
const router = useRouter()
const maxBatch = ref(10)
const sizeLimitBytes = ref<number | null>(null)
const sizeLimitText = computed(() =>
  sizeLimitBytes.value ? formatBytes(sizeLimitBytes.value) : '',
)

const STORAGE_KEY = 'admin.upload.results'
const hasSaved = ref<boolean>(false)
function refreshHasSaved() {
  hasSaved.value = !!localStorage.getItem(STORAGE_KEY)
}
refreshHasSaved()

function back() {
  router.push({ name: 'admin-book-list' })
}

function onFileChange(_fileObj: any, fileList?: any[]) {
  const list = (fileList || uiFileList.value || []) as any[]
  // 按顺序保留前 maxBatch 个
  const limited = list.slice(0, Math.max(1, maxBatch.value))
  if (list.length > limited.length) {
    ElMessage.warning(`最多一次性上传 ${maxBatch.value} 个文件，已保留前 ${maxBatch.value} 个`)
  }

  // 过滤超过大小限制的文件（基于 raw.size）
  let allowed = limited
  if (sizeLimitBytes.value) {
    const limit = sizeLimitBytes.value
    const overs = allowed.filter((it: any) => (it?.raw?.size ?? 0) > limit)
    if (overs.length) {
      ElMessage.warning(`已移除 ${overs.length} 个超过大小限制（>${formatBytes(limit)}）的文件`)
      allowed = allowed.filter((it: any) => (it?.raw?.size ?? 0) <= limit)
    }
  }

  // 更新上传组件可见列表与内部 files 原始 File 列表
  uiFileList.value = allowed
  files.value = allowed.map((it: any) => it?.raw).filter((f: File) => !!f) as File[]
  step.value = 0
}
function onFileRemove(_fileObj: any, fileList?: any[]) {
  const raws = (fileList || []).map((f: any) => f?.raw).filter((f: File) => !!f)
  files.value = raws as File[]
}

function onExceed() {
  ElMessage.warning(`最多一次性上传 ${maxBatch.value} 个文件`)
}

function reset() {
  files.value = []
  results.value = []
  uploading.value = false
  step.value = 0
  try {
    uploader.value?.clearFiles?.()
  } catch {}
  uiFileList.value = []
  uploadKey.value++
}

const step = ref(0) // 0: 选择文件, 1: 上传, 2: 编辑
const doneCount = ref(0)
const successCount = computed(() => results.value.filter(r => r.status === 'success').length)
const failCount = computed(() => results.value.filter(r => r.status === 'error').length)
const hasTxt = computed(() => files.value.some(f => f.name.toLowerCase().endsWith('.txt')))

// 进度条：每个文件的百分比与总体百分比
const filePercents = ref<number[]>([])
const currentIndex = ref(0)
const uploadProgress = computed(() => {
  if (!files.value.length) return 0
  const sum = filePercents.value
    .slice(0, files.value.length)
    .reduce((a, b) => a + (isFinite(b as number) ? (b as number) : 0), 0)
  const avg = Math.round(sum / files.value.length)
  return Math.min(100, Math.max(0, avg))
})
const currentFileProgress = computed(() => filePercents.value[currentIndex.value] ?? 0)
const fileProgressRows = computed(() =>
  files.value.map((f, i) => ({
    name: f.name,
    percent: filePercents.value[i] ?? 0,
    status:
      (filePercents.value[i] ?? 0) >= 100 ? 'success' : uploading.value ? 'active' : undefined,
  })),
)

async function submit() {
  if (files.value.length === 0) return
  step.value = 1
  uploading.value = true
  results.value = []
  doneCount.value = 0
  filePercents.value = files.value.map(() => 0)
  for (const f of files.value) {
    const idx = files.value.indexOf(f)
    currentIndex.value = idx >= 0 ? idx : 0
    try {
      const fd = new FormData()
      fd.append('file', f)
      if (parseTxt.value) fd.append('parse_txt', '1')
      const data = await importsApi.upload(fd, {
        onUploadProgress: (e: ProgressEvent) => {
          if (e && (e as any).total) {
            const total = (e as any).total as number
            const loaded = (e as any).loaded as number
            const percent = total ? Math.round((loaded / total) * 100) : 0
            filePercents.value[idx] = Math.min(100, Math.max(0, percent))
          }
        },
      })
      filePercents.value[idx] = 100
      results.value.push({ fileName: f.name, status: 'success', data })
    } catch (err: any) {
      filePercents.value[idx] = 100
      results.value.push({ fileName: f.name, status: 'error', error: err?.message || String(err) })
    } finally {
      doneCount.value++
    }
  }
  uploading.value = false
  step.value = 2
  saveResults()
}

function saveResults() {
  try {
    const payload = { time: Date.now(), results: results.value }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
    refreshHasSaved()
  } catch {}
}

function restoreLast() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      ElMessage.info('没有可恢复的导入记录')
      return
    }
    const obj = JSON.parse(raw)
    const arr = Array.isArray(obj?.results) ? obj.results : []
    if (!arr.length) {
      ElMessage.info('记录为空')
      return
    }
    results.value = arr
    uploading.value = false
    doneCount.value = results.value.length
    step.value = 2
    ElMessage.success('已恢复上次导入结果')
  } catch {
    ElMessage.error('恢复失败：记录格式不正确')
  }
}

function clearSaved() {
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {}
  refreshHasSaved()
  ElMessage.success('已清除保存的导入记录')
}

onMounted(async () => {
  try {
    const res = await systemSettingsApi.get()
    const vals = res.values || {}
    if (typeof vals['book.upload_max_batch'] === 'number') {
      maxBatch.value = Math.max(1, Math.min(100, vals['book.upload_max_batch']))
    }
    if (typeof vals['book.upload_max_size'] === 'number') {
      sizeLimitBytes.value = vals['book.upload_max_size']
    }
  } catch {}
})
</script>
