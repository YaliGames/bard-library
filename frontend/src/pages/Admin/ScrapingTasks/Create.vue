<template>
  <div class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">创建快速刮削任务</h2>
      <div class="flex items-center gap-2">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- 左侧：搜索面板 -->
      <el-card shadow="never" class="search-panel">
        <template #header>
          <span class="font-medium">搜索元数据</span>
        </template>

        <el-form>
          <el-form-item label="元数据提供商">
            <el-select v-model="provider" placeholder="选择提供商" @change="handleProviderChange">
              <el-option v-for="p in providers" :key="p.id" :label="p.name" :value="p.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="搜索关键词">
            <el-input
              v-model="query"
              placeholder="输入书名、作者或ISBN"
              @keyup.enter="handleSearch"
            >
              <template #append>
                <el-button icon="Search" :loading="searching" @click="handleSearch" />
              </template>
            </el-input>
          </el-form-item>
        </el-form>

        <div v-if="searchResults.length > 0" class="space-y-2">
          <div class="text-sm text-gray-500 mb-2">找到 {{ searchResults.length }} 个结果</div>

          <el-card
            v-for="item in searchResults"
            :key="item.id"
            shadow="hover"
            class="cursor-pointer"
            :class="{ 'border-primary': isItemSelected(item) }"
          >
            <div class="flex gap-3">
              <img
                v-if="item.cover"
                :src="getCoverUrl(item)"
                class="w-12 h-16 object-cover rounded"
                @error="onImageError"
              />
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ item.title }}</div>
                <div class="text-sm text-gray-500">
                  <div v-if="item.authors">作者: {{ formatAuthors(item.authors) }}</div>
                  <div v-if="item.rating">评分: {{ item.rating }}</div>
                </div>
              </div>
              <div>
                <el-button
                  v-if="!isItemSelected(item)"
                  type="primary"
                  size="small"
                  @click="handleAddItem(item)"
                >
                  添加
                </el-button>
                <el-tag v-else type="success" size="small">已添加</el-tag>
              </div>
            </div>
          </el-card>
        </div>

        <el-empty v-else-if="!searching && hasSearched" description="暂无搜索结果" />
      </el-card>

      <!-- 右侧：待刮削列表 -->
      <el-card shadow="never" class="selected-panel">
        <template #header>
          <div class="flex items-center justify-between">
            <span class="font-medium">待刮削列表</span>
            <el-tag type="info">{{ selectedItems.length }} 项</el-tag>
          </div>
        </template>

        <div v-if="selectedItems.length > 0" class="space-y-4">
          <div class="space-y-2">
            <el-card v-for="(item, index) in selectedItems" :key="item.tempId" shadow="hover">
              <div class="flex gap-3">
                <img
                  v-if="item.cover"
                  :src="getCoverUrl(item)"
                  class="w-12 h-16 object-cover rounded"
                  @error="onImageError"
                />
                <div class="flex-1 min-w-0">
                  <div class="font-medium truncate">{{ item.title }}</div>
                  <div class="text-xs text-gray-500">
                    {{ formatAuthors(item.authors) }}
                  </div>
                </div>
                <el-button
                  type="danger"
                  size="small"
                  text
                  icon="Delete"
                  @click="handleRemove(index)"
                />
              </div>
            </el-card>
          </div>

          <el-divider />

          <el-form label-position="top">
            <el-form-item label="任务名称" required>
              <el-input v-model="taskName" placeholder="例如：2024年度畅销书" />
            </el-form-item>

            <el-form-item label="选项">
              <div class="space-y-2">
                <el-checkbox v-model="options.autoDownloadCover">自动下载并保存封面</el-checkbox>
                <el-checkbox v-model="options.skipExisting">跳过已存在的图书（按ISBN）</el-checkbox>
              </div>
            </el-form-item>

            <el-button
              type="primary"
              class="w-full"
              :disabled="!canSubmit"
              :loading="submitting"
              @click="handleSubmit"
            >
              创建刮削任务（{{ selectedItems.length }} 项）
            </el-button>
          </el-form>
        </div>

        <el-empty v-else description="从左侧搜索并添加图书" :image-size="80" />
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { metadataApi, scrapingTasksApi } from '@/api'
import type { MetadataProvider } from '@/api/metadata'

const router = useRouter()

function back() {
  router.back()
}

// 搜索相关
const providers = ref<MetadataProvider[]>([])
const provider = ref('')
const query = ref('')
const searching = ref(false)
const searchResults = ref<any[]>([])
const hasSearched = ref(false)

// 待刮削列表
const selectedItems = ref<any[]>([])
const taskName = ref('')
const options = ref({
  autoDownloadCover: true,
  skipExisting: true,
})

const submitting = ref(false)

// 计算属性
const canSubmit = computed(() => {
  return selectedItems.value.length > 0 && taskName.value.trim() !== '' && !submitting.value
})

// 加载提供商列表
onMounted(async () => {
  try {
    providers.value = await metadataApi.listProviders()
    if (providers.value.length > 0 && !provider.value) {
      provider.value = providers.value[0].id
    }
  } catch (error: any) {
    ElMessage.error('加载提供商列表失败')
  }
})

// 搜索
const handleSearch = async () => {
  if (!query.value.trim()) {
    ElMessage.warning('请输入搜索关键词')
    return
  }

  searching.value = true
  hasSearched.value = true

  try {
    // 使用预览模式搜索
    const items = await metadataApi.searchPreview(provider.value, query.value, 10)
    searchResults.value = items.map(item => ({
      ...item,
      tempId: `${provider.value}_${item.id}_${Date.now()}_${Math.random()}`,
    }))
  } catch (error: any) {
    ElMessage.error(error.message || '搜索失败')
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

// 切换提供商时清空搜索结果
const handleProviderChange = () => {
  searchResults.value = []
  hasSearched.value = false
}

// 检查项目是否已选中
const isItemSelected = (item: any) => {
  return selectedItems.value.some(
    selected => selected.url === item.url && selected.provider === provider.value,
  )
}

// 添加到待刮削列表
const handleAddItem = (item: any) => {
  if (isItemSelected(item)) {
    ElMessage.warning('该图书已在列表中')
    return
  }

  selectedItems.value.push({
    ...item,
    provider: provider.value,
    query: query.value,
  })

  ElMessage.success(`已添加《${item.title}》`)
}

// 从待刮削列表移除
const handleRemove = (index: number) => {
  selectedItems.value.splice(index, 1)
}

// 提交任务
const handleSubmit = async () => {
  if (!canSubmit.value) return

  submitting.value = true

  try {
    const payload = {
      name: taskName.value.trim(),
      items: selectedItems.value.map(item => ({
        provider: item.provider,
        source_id: item.id,
        source_url: item.url,
        query: item.query,
        metadata: item,
      })),
      options: {
        auto_download_cover: options.value.autoDownloadCover,
        skip_existing: options.value.skipExisting,
      },
    }

    const task = await scrapingTasksApi.create(payload)

    ElMessage.success('任务创建成功')
    router.push({ name: 'admin-scraping-tasks' })
  } catch (error: any) {
    ElMessage.error(error.message || '创建任务失败')
  } finally {
    submitting.value = false
  }
}

// 工具函数
const getCoverUrl = (item: any) => {
  if (!item.cover) return ''
  return metadataApi.coverUrl(item.provider || provider.value, item.cover)
}

const formatAuthors = (authors: any) => {
  if (!authors) return '未知作者'
  if (typeof authors === 'string') return authors
  if (Array.isArray(authors)) return authors.join(', ')
  return '未知作者'
}

const onImageError = (e: Event) => {
  const img = e.target as HTMLImageElement
  img.style.display = 'none'
}
</script>

<style scoped>
.search-panel,
.selected-panel {
  height: calc(100vh - 200px);
  overflow-y: auto;
}
</style>
