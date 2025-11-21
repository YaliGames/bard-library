<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <!-- 页面标题 -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold">快速上传图书</h2>
      <el-button @click="back">
        <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
        返回
      </el-button>
    </div>

    <!-- 步骤条 -->
    <el-steps :active="step" finish-status="success" class="mb-6">
      <el-step title="选择文件" description="选择要上传的图书文件" />
      <el-step title="上传文件" description="等待文件上传完成" />
      <el-step title="查看结果" description="查看上传结果并编辑图书" />
    </el-steps>

    <!-- 步骤内容 -->
    <div>
      <!-- 步骤 1: 选择文件 -->
      <div v-show="step === 0">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">选择文件</span>
            <div class="flex items-center">
              <el-button size="small" @click="restoreLast" :disabled="!hasSaved">
                <span class="material-symbols-outlined mr-1 text-sm">restore</span>
                恢复上次记录
              </el-button>
              <el-button size="small" @click="clearSaved" :disabled="!hasSaved">
                <span class="material-symbols-outlined mr-1 text-sm">delete_sweep</span>
                清除记录
              </el-button>
            </div>
          </div>

          <el-form label-width="120px" @submit.prevent>
            <el-form-item label="选择文件">
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
                class="w-full"
              >
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">
                  upload_file
                </span>
                <div class="el-upload__text text-base">
                  拖拽文件到此处，或
                  <em class="text-primary">点击选择</em>
                </div>
                <template #tip>
                  <div class="el-upload__tip mt-2">
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

            <el-divider />

            <div class="flex justify-center gap-4">
              <el-button :disabled="uploading" @click="reset">
                <span class="material-symbols-outlined mr-1">refresh</span>
                重置
              </el-button>
              <el-button
                type="primary"
                :loading="uploading"
                :disabled="files.length === 0"
                @click="submit"
              >
                <span class="material-symbols-outlined mr-1">cloud_upload</span>
                <template v-if="!uploading">开始上传 ({{ files.length }})</template>
                <template v-else>上传中…（{{ doneCount }}/{{ files.length }}）</template>
              </el-button>
            </div>
          </el-form>
        </div>
      </div>

      <!-- 步骤 2: 上传中 -->
      <div v-show="step === 1">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">文件上传进度</span>
            <el-tag v-if="uploading" type="warning">上传中</el-tag>
            <el-tag v-else type="success">上传完成</el-tag>
          </div>

          <!-- 总体进度 -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">总体进度</span>
              <span class="text-sm text-gray-600">{{ uploadProgress }}%</span>
            </div>
            <el-progress
              :percentage="uploadProgress"
              :status="uploadProgress === 100 ? 'success' : 'active'"
              :stroke-width="12"
            />
          </div>

          <!-- 当前文件进度 -->
          <div v-if="files.length && uploading" class="mb-6 p-4 bg-gray-50 rounded">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">
                当前文件（{{ currentIndex + 1 }}/{{ files.length }}）
              </span>
              <!-- <span class="text-sm text-gray-600">{{ currentFileProgress }}%</span> -->
            </div>
            <div class="text-xs text-gray-500 mb-2 truncate">{{ files[currentIndex]?.name }}</div>
            <el-progress :percentage="currentFileProgress" :stroke-width="8" />
          </div>

          <!-- 文件列表进度 -->
          <div v-if="files.length > 1">
            <div class="text-sm font-medium text-gray-700 mb-3">文件列表</div>
            <el-table :data="fileProgressRows" size="small" border>
              <el-table-column label="文件名" prop="name" min-width="240" show-overflow-tooltip />
              <el-table-column label="上传进度" width="240">
                <template #default="{ row }">
                  <el-progress :percentage="row.percent" :status="row.status" :stroke-width="6" />
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
      </div>

      <!-- 步骤 3: 查看结果 -->
      <div v-show="step === 2">
        <!-- 单文件结果 -->
        <div v-if="results.length === 1" class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">文件上传进度</span>
            <el-tag v-if="uploading" type="warning">上传中</el-tag>
            <el-tag v-else type="success">上传完成</el-tag>
          </div>
          <template v-if="results[0].status === 'error'">
            <el-result icon="error" title="上传失败" :sub-title="results[0].error">
              <template #extra>
                <el-button type="primary" @click="step = 0">
                  <span class="material-symbols-outlined mr-1">arrow_back</span>
                  返回重新上传
                </el-button>
              </template>
            </el-result>
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
                <div class="flex justify-center gap-3">
                  <router-link
                    v-if="results[0].data?.book?.id"
                    :to="{ name: 'book-detail', params: { id: results[0].data.book.id } }"
                  >
                    <el-button type="primary">
                      <span class="material-symbols-outlined mr-1">visibility</span>
                      查看详情
                    </el-button>
                  </router-link>
                  <router-link
                    v-if="results[0].data?.book?.id"
                    :to="{ name: 'admin-book-edit', params: { id: results[0].data.book.id } }"
                  >
                    <el-button>
                      <span class="material-symbols-outlined mr-1">edit</span>
                      编辑图书
                    </el-button>
                  </router-link>
                  <el-button @click="reset">
                    <span class="material-symbols-outlined mr-1">add</span>
                    继续上传
                  </el-button>
                </div>
              </template>
            </el-result>

            <el-divider />

            <el-descriptions v-if="results[0].data?.book" :column="2" border>
              <el-descriptions-item label="书名" :span="2">
                {{ results[0].data.book?.title || '#' + results[0].data.book?.id }}
              </el-descriptions-item>
              <el-descriptions-item label="图书 ID">
                {{ results[0].data.book?.id }}
              </el-descriptions-item>
              <el-descriptions-item label="状态">
                <el-tag v-if="results[0].data?.duplicate" type="info">已存在</el-tag>
                <el-tag v-else type="success">新建</el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </template>
        </div>

        <!-- 多文件结果 -->
        <div class="bg-white rounded-lg shadow-sm p-4" v-else-if="results.length > 1">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">上传结果</span>
            <div class="flex items-center gap-2">
              <el-tag type="success">成功: {{ successCount }}</el-tag>
              <el-tag v-if="failCount > 0" type="danger">失败: {{ failCount }}</el-tag>
              <el-tag type="info">总计: {{ results.length }}</el-tag>
            </div>
          </div>

          <el-alert
            :type="failCount > 0 ? 'warning' : 'success'"
            :closable="false"
            show-icon
            class="mb-4"
            :title="
              failCount > 0
                ? `部分上传成功：成功 ${successCount} 个，失败 ${failCount} 个`
                : `全部上传成功：共 ${successCount} 个文件`
            "
          />

          <el-table :data="results" border stripe>
            <el-table-column label="文件名" prop="fileName" min-width="200" show-overflow-tooltip />
            <el-table-column label="状态" width="100" align="center">
              <template #default="{ row }">
                <el-tag v-if="row.status === 'success'" type="success" size="small">成功</el-tag>
                <el-tag v-else type="danger" size="small">失败</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="结果" width="100" align="center">
              <template #default="{ row }">
                <template v-if="row.status === 'success'">
                  <el-tag
                    v-if="row.data?.duplicate || row.data?.created === false"
                    type="info"
                    size="small"
                  >
                    已存在
                  </el-tag>
                  <el-tag v-else type="success" size="small">新建</el-tag>
                </template>
                <span v-else class="text-gray-400">-</span>
              </template>
            </el-table-column>
            <el-table-column label="图书信息" min-width="220">
              <template #default="{ row }">
                <template v-if="row.status === 'success' && row.data?.book">
                  <div class="font-medium">{{ row.data.book.title || '#' + row.data.book.id }}</div>
                  <div class="text-xs text-gray-500">#{{ row.data.book.id }}</div>
                </template>
                <template v-else-if="row.status === 'error'">
                  <el-tooltip :content="row.error" placement="top">
                    <span class="text-red-500 text-sm cursor-help">{{ row.error }}</span>
                  </el-tooltip>
                </template>
                <template v-else>
                  <span class="text-gray-400">-</span>
                </template>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.status === 'success' && row.data?.book?.id">
                  <router-link :to="{ name: 'book-detail', params: { id: row.data.book.id } }">
                    <el-button class="mr-2" size="small" type="primary" plain>查看</el-button>
                  </router-link>
                  <router-link :to="{ name: 'admin-book-edit', params: { id: row.data.book.id } }">
                    <el-button size="small">编辑</el-button>
                  </router-link>
                </template>
                <span v-else class="text-gray-400">-</span>
              </template>
            </el-table-column>
          </el-table>

          <el-divider />

          <div class="flex justify-center">
            <el-button type="primary" @click="reset">
              <span class="material-symbols-outlined mr-1">add</span>
              上传更多文件
            </el-button>
          </div>
        </div>

        <!-- 空结果 -->
        <div class="flex items-center gap-2" v-else>
          <el-empty description="暂无上传结果">
            <el-button type="primary" @click="step = 0">开始上传</el-button>
          </el-empty>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { importsApi } from '@/api/imports'
import { useRouter } from 'vue-router'
import { systemSettingsApi } from '@/api/systemSettings'
import { formatBytes } from '@/utils/systemSettings'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useSimpleLoading } from '@/composables/useLoading'

const { handleError, handleSuccess } = useErrorHandler()
const { loading: uploading, setLoading: setUploading } = useSimpleLoading(false)

const uploader = ref()
const uploadKey = ref(0)
const uiFileList = ref<any[]>([])
const files = ref<File[]>([])
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
    handleError(new Error('Exceed max batch'), {
      context: 'Admin.BookUpload.onFileChange',
      message: `最多一次性上传 ${maxBatch.value} 个文件，已保留前 ${maxBatch.value} 个`,
    })
  }

  // 过滤超过大小限制的文件（基于 raw.size）
  let allowed = limited
  if (sizeLimitBytes.value) {
    const limit = sizeLimitBytes.value
    const overs = allowed.filter((it: any) => (it?.raw?.size ?? 0) > limit)
    if (overs.length) {
      handleError(new Error('Exceed size limit'), {
        context: 'Admin.BookUpload.onFileChange',
        message: `已移除 ${overs.length} 个超过大小限制（>${formatBytes(limit)}）的文件`,
      })
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
  handleError(new Error('Exceed max batch'), {
    context: 'Admin.BookUpload.onExceed',
    message: `最多一次性上传 ${maxBatch.value} 个文件`,
  })
}

function reset() {
  files.value = []
  results.value = []
  setUploading(false)
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
  setUploading(true)
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
  setUploading(false)
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
      handleError(new Error('No restore data'), {
        context: 'Admin.BookUpload.restoreLast',
        message: '没有可恢复的导入记录',
      })
      return
    }
    const obj = JSON.parse(raw)
    const arr = Array.isArray(obj?.results) ? obj.results : []
    if (!arr.length) {
      handleError(new Error('No restore data'), {
        context: 'Admin.BookUpload.restoreLast',
        message: '导入记录为空',
      })
      return
    }
    results.value = arr
    setUploading(false)
    doneCount.value = results.value.length
    step.value = 2
    handleSuccess('已恢复上次导入结果')
  } catch {
    handleError(new Error('Illegal restore data format'), {
      context: 'Admin.BookUpload.restoreLast',
      message: '恢复上传记录失败，记录格式不正确',
    })
  }
}

function clearSaved() {
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {}
  refreshHasSaved()
  handleSuccess('已清除保存的导入记录')
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
