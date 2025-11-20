<template>
  <div class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">快速刮削任务</h2>
      <div class="flex items-center">
        <el-button type="primary" @click="handleCreate">
          <span class="material-symbols-outlined mr-1 text-lg">add</span>
          创建新任务
        </el-button>
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <el-card shadow="never">
      <el-table :data="tasks" v-loading="isLoadingKey('fetch')">
        <el-table-column prop="name" label="任务名称" min-width="200" />
        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="进度" width="180">
          <template #default="{ row }">
            <el-progress
              :percentage="getProgress(row)"
              :status="getProgressStatus(row)"
              :stroke-width="12"
            />
            <div class="text-xs text-gray-500 mt-1">
              {{ row.processed_items }} / {{ row.total_items }} (成功: {{ row.success_items }},
              失败: {{ row.failed_items }})
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button
              size="small"
              text
              @click="handleView(row)"
              :loading="isLoadingKey(`view-${row.id}`)"
            >
              查看
            </el-button>
            <el-button
              v-if="row.status === 'pending' || row.status === 'processing'"
              size="small"
              type="warning"
              text
              @click="handleCancel(row)"
              :loading="isLoadingKey(`cancel-${row.id}`)"
            >
              取消
            </el-button>
            <el-button
              v-if="row.status !== 'processing'"
              size="small"
              type="danger"
              text
              @click="handleDelete(row)"
              :loading="isLoadingKey(`delete-${row.id}`)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="mt-4 flex justify-end">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[15, 30, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchTasks"
          @current-change="fetchTasks"
        />
      </div>
    </el-card>

    <!-- 任务详情弹窗 -->
    <el-dialog v-model="detailVisible" title="任务详情" width="800px" :close-on-click-modal="false">
      <div v-if="currentTask" v-loading="isLoadingKey('detail')">
        <div class="mb-4">
          <h3 class="text-lg font-medium mb-2">基本信息</h3>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="任务名称">{{ currentTask.name }}</el-descriptions-item>
            <el-descriptions-item label="状态">
              <el-tag :type="getStatusType(currentTask.status)">
                {{ getStatusText(currentTask.status) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="总数">{{ currentTask.total_items }}</el-descriptions-item>
            <el-descriptions-item label="已处理">
              {{ currentTask.processed_items }}
            </el-descriptions-item>
            <el-descriptions-item label="成功">
              {{ currentTask.success_items }}
            </el-descriptions-item>
            <el-descriptions-item label="失败">{{ currentTask.failed_items }}</el-descriptions-item>
            <el-descriptions-item label="创建时间" :span="2">
              {{ formatDate(currentTask.created_at) }}
            </el-descriptions-item>
            <el-descriptions-item label="开始时间" :span="2" v-if="currentTask.started_at">
              {{ formatDate(currentTask.started_at) }}
            </el-descriptions-item>
            <el-descriptions-item label="完成时间" :span="2" v-if="currentTask.finished_at">
              {{ formatDate(currentTask.finished_at) }}
            </el-descriptions-item>
            <el-descriptions-item label="错误信息" :span="2" v-if="currentTask.error_message">
              <el-text type="danger">{{ currentTask.error_message }}</el-text>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <div class="mb-4">
          <h3 class="text-lg font-medium mb-2">任务选项</h3>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="自动下载封面">
              <el-tag :type="currentTask.options?.auto_download_cover ? 'success' : ''">
                {{ currentTask.options?.auto_download_cover ? '是' : '否' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="跳过已存在">
              <el-tag :type="currentTask.options?.skip_existing ? 'success' : ''">
                {{ currentTask.options?.skip_existing ? '是' : '否' }}
              </el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium">处理结果</h3>
            <el-button size="small" @click="refreshResults" :loading="isLoadingKey('results')">
              <span class="material-symbols-outlined mr-1 text-sm">refresh</span>
              刷新
            </el-button>
          </div>
          <el-table :data="results" v-loading="isLoadingKey('results')" max-height="400">
            <el-table-column prop="query" label="查询" width="150" show-overflow-tooltip />
            <el-table-column prop="provider" label="平台" width="80" />
            <el-table-column label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="getResultStatusType(row.status)" size="small">
                  {{ getResultStatusText(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="source_url" label="来源" min-width="150" show-overflow-tooltip>
              <template #default="{ row }">
                <a :href="row.source_url" target="_blank" class="text-blue-500 hover:underline">
                  {{ row.source_url }}
                </a>
              </template>
            </el-table-column>
            <el-table-column label="图书" width="80">
              <template #default="{ row }">
                <el-button
                  v-if="row.book_id"
                  size="small"
                  text
                  type="primary"
                  @click="viewBook(row.book_id)"
                >
                  查看
                </el-button>
              </template>
            </el-table-column>
            <el-table-column
              prop="error_message"
              label="错误信息"
              min-width="150"
              show-overflow-tooltip
            >
              <template #default="{ row }">
                <el-text v-if="row.error_message" type="danger" size="small">
                  {{ row.error_message }}
                </el-text>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { scrapingTasksApi } from '@/api'
import type { ScrapingTask, ScrapingResult } from '@/api'
import { useLoading } from '@/composables/useLoading'

const router = useRouter()
const { isLoadingKey, startLoading, stopLoading } = useLoading()

const tasks = ref<ScrapingTask[]>([])
const currentPage = ref(1)
const pageSize = ref(15)
const total = ref(0)

// 详情弹窗相关
const detailVisible = ref(false)
const currentTask = ref<ScrapingTask | null>(null)
const results = ref<ScrapingResult[]>([])

onMounted(() => {
  fetchTasks()
})

function back() {
  router.back()
}

const fetchTasks = async () => {
  startLoading('fetch')
  try {
    const response = await scrapingTasksApi.list({
      page: currentPage.value,
      per_page: pageSize.value,
    })
    tasks.value = response.data
    total.value = (response as any).total || response.data.length
  } catch {
    ElMessage.error('加载任务列表失败')
  } finally {
    stopLoading('fetch')
  }
}

const handleCreate = () => {
  router.push({ name: 'admin-scraping-tasks-create' })
}

const handleView = async (task: ScrapingTask) => {
  const key = `view-${task.id}`
  startLoading(key)
  try {
    // 获取任务详情
    const taskDetail = await scrapingTasksApi.show(task.id)
    currentTask.value = taskDetail

    // 获取处理结果
    startLoading('results')
    try {
      const resultsResponse = await scrapingTasksApi.results(task.id)
      results.value = resultsResponse.data
    } catch {
      // 结果加载失败时，设为空数组
      results.value = []
    } finally {
      stopLoading('results')
    }

    detailVisible.value = true
  } catch (error: any) {
    ElMessage.error(error.message || '加载任务详情失败')
  } finally {
    stopLoading(key)
  }
}

const refreshResults = async () => {
  if (!currentTask.value) return

  startLoading('results')
  try {
    const resultsResponse = await scrapingTasksApi.results(currentTask.value.id)
    results.value = resultsResponse.data

    // 同时刷新任务状态
    const taskDetail = await scrapingTasksApi.show(currentTask.value.id)
    currentTask.value = taskDetail

    // 更新列表中的任务状态
    const index = tasks.value.findIndex(t => t.id === currentTask.value!.id)
    if (index !== -1) {
      tasks.value[index] = taskDetail
    }
  } catch (error: any) {
    ElMessage.error(error.message || '刷新失败')
  } finally {
    stopLoading('results')
  }
}

const viewBook = (bookId: number) => {
  router.push({ name: 'admin-book-edit', params: { id: bookId } })
}

const handleCancel = async (task: ScrapingTask) => {
  try {
    await ElMessageBox.confirm('确定要取消这个任务吗？', '提示', {
      type: 'warning',
    })

    const key = `cancel-${task.id}`
    startLoading(key)
    try {
      await scrapingTasksApi.cancel(task.id)
      ElMessage.success('任务已取消')
      fetchTasks()
    } finally {
      stopLoading(key)
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '取消任务失败')
    }
  }
}

const handleDelete = async (task: ScrapingTask) => {
  try {
    await ElMessageBox.confirm('确定要删除这个任务吗？删除后无法恢复。', '提示', {
      type: 'warning',
    })

    const key = `delete-${task.id}`
    startLoading(key)
    try {
      await scrapingTasksApi.destroy(task.id)
      ElMessage.success('任务已删除')
      fetchTasks()
    } finally {
      stopLoading(key)
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '删除任务失败')
    }
  }
}

const getStatusType = (status: string) => {
  const types: Record<string, any> = {
    pending: '',
    processing: 'primary',
    completed: 'success',
    failed: 'danger',
    cancelled: 'warning',
  }
  return types[status] || ''
}

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '等待中',
    processing: '处理中',
    completed: '已完成',
    failed: '失败',
    cancelled: '已取消',
  }
  return texts[status] || status
}

const getProgress = (task: ScrapingTask) => {
  if (task.total_items === 0) return 0
  return Math.round((task.processed_items / task.total_items) * 100)
}

const getProgressStatus = (task: ScrapingTask) => {
  if (task.status === 'completed') return 'success'
  if (task.status === 'failed') return 'exception'
  return undefined
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('zh-CN')
}

const getResultStatusType = (status: string) => {
  const types: Record<string, any> = {
    pending: '',
    success: 'success',
    failed: 'danger',
    skipped: 'info',
  }
  return types[status] || ''
}

const getResultStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待处理',
    success: '成功',
    failed: '失败',
    skipped: '跳过',
  }
  return texts[status] || status
}
</script>
