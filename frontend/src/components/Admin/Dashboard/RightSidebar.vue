<template>
  <div class="space-y-6">
    <!-- 快速搜书 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
      <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-500">bolt</span>
        快速搜书
      </h3>
      <p class="text-xs text-slate-500 mb-4">快速搜索元数据并创建图书刮削任务。</p>
      <div class="space-y-3">
        <el-input
          placeholder="输入书名/ISBN/作者"
          v-model="quickSearch"
          :disabled="loadingProviders || searching"
          @keyup.enter="handleQuickSearch"
          clearable
        >
          <template #prefix>
            <span class="material-symbols-outlined text-gray-400 text-[18px] mt-0.5">search</span>
          </template>
        </el-input>

        <div class="flex gap-2">
          <el-select
            v-model="quickSource"
            placeholder="来源"
            class="w-28"
            :disabled="loadingProviders || searching"
            :loading="loadingProviders"
          >
            <el-option v-for="p in providers" :key="p.id" :label="p.name" :value="p.id" />
          </el-select>
          <el-button
            type="primary"
            class="flex-1"
            @click="handleQuickSearch"
            :loading="searching"
            :disabled="!quickSearch.trim() || loadingProviders || !quickSource"
          >
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px]">cloud_download</span>
              导入
            </span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 系统健康度 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
      <h3 class="font-bold text-slate-800 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-500">health_and_safety</span>
        系统健康度
      </h3>

      <!-- 骨架屏加载动画 -->
      <div v-if="loadingHealth" class="space-y-5">
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">hard_drive</span>
              存储空间
            </span>
            <div class="h-4 w-20 bg-slate-200 rounded animate-pulse"></div>
          </div>
          <div class="h-1 bg-slate-200 rounded-full animate-pulse"></div>
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">api</span>
              API 配额
            </span>
            <div class="h-4 w-20 bg-slate-200 rounded animate-pulse"></div>
          </div>
          <div class="h-1 bg-slate-200 rounded-full animate-pulse"></div>
        </div>

        <div class="h-12 bg-slate-200 rounded-lg animate-pulse"></div>
      </div>

      <div
        v-else-if="healthLoadError"
        class="bg-red-50 border border-red-200 rounded-lg p-4 text-center"
      >
        <span class="material-symbols-outlined text-red-400 text-2xl mb-2 block">error_circle</span>
        <p class="text-xs text-red-600 font-medium">数据加载失败</p>
        <p class="text-xs text-red-500 mt-1">请稍后刷新重试</p>
      </div>

      <div v-else class="space-y-5">
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">hard_drive</span>
              存储空间
            </span>
            <span class="font-mono text-slate-500">
              {{ healthData?.storage?.used || '-/-' }} / {{ healthData?.storage?.total || '-/-' }}
            </span>
          </div>
          <el-progress
            :percentage="healthData?.storage?.percentage || 0"
            :stroke-width="6"
            :color="progressColors"
            :show-text="false"
          />
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">api</span>
              API 配额
            </span>
            <span class="font-mono text-slate-500">
              {{ healthData?.api?.used || '-/-' }} / {{ healthData?.api?.total || '-/-' }}
            </span>
          </div>
          <el-progress
            :percentage="healthData?.api?.percentage || 0"
            :stroke-width="6"
            :status="
              healthData?.api?.percentage && healthData.api.percentage > 80 ? 'warning' : undefined
            "
            :show-text="false"
          />
        </div>

        <div
          class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100"
        >
          <div class="flex items-center gap-2">
            <div
              :class="`w-2 h-2 rounded-full animate-pulse ${
                healthData?.service_status === 'running'
                  ? 'bg-green-500'
                  : healthData?.service_status === 'unknown'
                    ? 'bg-gray-400'
                    : 'bg-red-500'
              }`"
            ></div>
            <span class="text-xs font-medium text-slate-600">
              {{
                healthData?.service_status === 'running'
                  ? '后台服务运行中'
                  : healthData?.service_status === 'unknown'
                    ? '后台服务状态未知'
                    : '后台服务离线'
              }}
            </span>
          </div>
          <span
            class="material-symbols-outlined text-[18px]"
            :class="{
              'text-green-500': healthData?.service_status === 'running',
              'text-gray-400': healthData?.service_status === 'unknown',
              'text-red-500': healthData?.service_status === 'offline',
            }"
          >
            {{
              healthData?.service_status === 'running'
                ? 'check'
                : healthData?.service_status === 'offline'
                  ? 'close'
                  : 'help'
            }}
          </span>
        </div>
      </div>
    </div>

    <div
      class="rounded-xl p-5 text-white bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg relative overflow-hidden"
    >
      <span
        class="material-symbols-outlined absolute -right-4 -bottom-4 text-[120px] opacity-10 rotate-12"
      >
        help
      </span>

      <div class="relative z-10">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined">school</span>
          <h3 class="font-bold">管理员指南</h3>
        </div>
        <p class="text-xs opacity-90 leading-relaxed mb-4">
          遇到问题？查看文档了解如何配置自动刮削器和元数据匹配规则。
        </p>
        <button
          class="text-xs text-white font-bold bg-white/20 hover:bg-white/30 transition-colors px-3 py-1.5 rounded-lg border border-white/30"
        >
          阅读文档
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { metadataApi, type MetadataProvider } from '@/api/metadata'
import { ElMessage } from 'element-plus'

const router = useRouter()

// 快速搜书相关
const quickSearch = ref('')
const quickSource = ref('')
const searching = ref(false)
const loadingProviders = ref(false)
const providers = ref<MetadataProvider[]>([])

// 系统健康度相关
interface HealthData {
  storage?: {
    used: string
    total: string
    percentage: number
  }
  api?: {
    used: number
    total: number
    percentage: number
  }
  service_status: 'running' | 'offline' | 'unknown'
}

const healthData = ref<HealthData>({
  service_status: 'unknown',
})
const loadingHealth = ref(false)
const healthLoadError = ref(false)

const progressColors = [
  { color: '#f56c6c', percentage: 90 },
  { color: '#e6a23c', percentage: 70 },
  { color: '#5cb87a', percentage: 40 },
  { color: '#3b82f6', percentage: 20 },
]

// 加载 providers 列表
const loadProviders = async () => {
  loadingProviders.value = true
  try {
    providers.value = await metadataApi.listProviders()
    // 默认选择第一个
    if (providers.value.length > 0 && !quickSource.value) {
      quickSource.value = providers.value[0].id
    }
  } catch (error) {
    console.error('Failed to load providers:', error)
  } finally {
    loadingProviders.value = false
  }
}

// 加载系统健康度数据
const loadHealthData = async () => {
  loadingHealth.value = true
  healthLoadError.value = false
  try {
    // TODO: 替换为实际的 API 调用
    // const data = await systemApi.getHealth()
    // 暂时使用模拟数据
    const data = await mockGetHealth()
    healthData.value = data
  } catch (error) {
    console.error('Failed to load health data:', error)
    healthLoadError.value = true
    // 设置默认的失败状态
    healthData.value = {
      storage: {
        used: '-/-',
        total: '-/-',
        percentage: 0,
      },
      api: {
        used: 0,
        total: 0,
        percentage: 0,
      },
      service_status: 'unknown',
    }
  } finally {
    loadingHealth.value = false
  }
}

// 模拟 API 调用（实际开发时替换）
const mockGetHealth = (): Promise<HealthData> => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        storage: {
          used: '128GB',
          total: '512GB',
          percentage: 25,
        },
        api: {
          used: 850,
          total: 1000,
          percentage: 85,
        },
        service_status: 'running',
      })
    }, 1000)
  })
}

// 点击搜索按钮，跳转到创建任务页面并带上搜索条件
const handleQuickSearch = async () => {
  if (!quickSearch.value.trim() || !quickSource.value) return

  searching.value = true
  try {
    // 跳转到创建任务页面，并通过 query 参数传递搜索条件
    await router.push({
      name: 'admin-scraping-tasks-create',
      query: {
        provider: quickSource.value,
        query: quickSearch.value,
      },
    })
  } catch (error) {
    console.error('Navigation error:', error)
  } finally {
    searching.value = false
  }
}

onMounted(() => {
  loadProviders()
  loadHealthData()
})
</script>
