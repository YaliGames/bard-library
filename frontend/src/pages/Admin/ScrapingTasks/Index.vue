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
      <el-table :data="tasks" v-loading="loading">
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
            <el-button size="small" text @click="handleView(row)">查看</el-button>
            <el-button
              v-if="row.status === 'pending' || row.status === 'processing'"
              size="small"
              type="warning"
              text
              @click="handleCancel(row)"
            >
              取消
            </el-button>
            <el-button
              v-if="row.status !== 'processing'"
              size="small"
              type="danger"
              text
              @click="handleDelete(row)"
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { scrapingTasksApi } from '@/api'
import type { ScrapingTask } from '@/api'

const router = useRouter()

const tasks = ref<ScrapingTask[]>([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(15)
const total = ref(0)

onMounted(() => {
  fetchTasks()
})

function back() {
  router.back()
}

const fetchTasks = async () => {
  loading.value = true
  try {
    const response = await scrapingTasksApi.list({
      page: currentPage.value,
      per_page: pageSize.value,
    })
    tasks.value = response.data
    total.value = (response as any).total || response.data.length
  } catch (error: any) {
    ElMessage.error('加载任务列表失败')
  } finally {
    loading.value = false
  }
}

const handleCreate = () => {
  router.push({ name: 'admin-scraping-tasks-create' })
}

const handleView = (task: ScrapingTask) => {
  router.push({ name: 'admin-scraping-tasks-detail', params: { id: task.id } })
}

const handleCancel = async (task: ScrapingTask) => {
  try {
    await ElMessageBox.confirm('确定要取消这个任务吗？', '提示', {
      type: 'warning',
    })
    await scrapingTasksApi.cancel(task.id)
    ElMessage.success('任务已取消')
    fetchTasks()
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
    await scrapingTasksApi.destroy(task.id)
    ElMessage.success('任务已删除')
    fetchTasks()
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
</script>
