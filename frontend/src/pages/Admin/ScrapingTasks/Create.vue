<template>
  <div class="container mx-auto px-4 py-4 max-w-7xl">
    <!-- 页面标题 -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold">创建快速刮削任务</h2>
      <el-button @click="back">
        <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
        返回
      </el-button>
    </div>

    <!-- 步骤条 -->
    <el-steps :active="currentStep" finish-status="success" class="mb-6">
      <el-step title="搜索元数据" description="搜索并添加待刮削的图书" />
      <el-step title="检查刮削列表" description="确认已添加的图书信息" />
      <el-step title="编辑任务信息" description="设置任务名称和选项" />
    </el-steps>

    <!-- 步骤内容 -->
    <div>
      <!-- 步骤 1: 搜索元数据 -->
      <div v-show="currentStep === 0" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 搜索面板 (占2列) -->
        <div class="bg-white rounded-lg shadow-sm p-4 lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">搜索元数据</span>
          </div>

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

          <div class="h-[480px] flex flex-col">
            <div v-if="searchResults.length > 0" class="flex flex-col h-full">
              <div class="text-sm text-gray-500 mb-2 bg-white flex-shrink-0">
                找到 {{ searchResults.length }} 个结果
              </div>
              <div class="space-y-2 flex-1 overflow-y-auto">
                <el-card
                  v-for="item in searchResults"
                  :key="item.id"
                  shadow="hover"
                  :class="{ 'border-2 border-primary': isItemSelected(item) }"
                  class="transition-all"
                >
                  <div class="flex gap-3">
                    <img
                      v-if="item.cover"
                      :src="getCoverUrl(item)"
                      class="w-12 h-16 object-cover rounded"
                      @error="onImageError"
                    />
                    <div
                      v-else
                      class="w-12 h-16 bg-gray-100 rounded flex items-center justify-center"
                    >
                      <span class="material-symbols-outlined text-gray-400">book</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="font-medium truncate">{{ item.title }}</div>
                      <div class="text-sm text-gray-500 space-y-1">
                        <div v-if="item.authors">作者: {{ formatAuthors(item.authors) }}</div>
                        <div v-if="item.rating" class="flex items-center gap-1">
                          <span
                            class="material-symbols-outlined text-yellow-500"
                            style="font-size: 16px"
                          >
                            star
                          </span>
                          {{ item.rating }}
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center">
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
            </div>

            <el-empty
              v-else-if="!searching && hasSearched"
              description="暂无搜索结果，请尝试其他关键词"
            />
            <el-empty v-else-if="!searching" description="输入关键词开始搜索" :image-size="80" />
          </div>
        </div>

        <!-- 快速预览 (PC端显示，移动端隐藏) -->
        <div class="hidden lg:block">
          <div class="bg-white rounded-lg shadow-sm p-4 top-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="font-medium text-lg">已选图书</span>
                <el-tag v-if="selectedItems.length > 0" type="success" size="small">
                  {{ selectedItems.length }}
                </el-tag>
              </div>
              <el-button
                v-if="selectedItems.length > 0"
                type="text"
                size="small"
                @click="confirmClearSelected"
              >
                清空
              </el-button>
            </div>

            <div v-if="selectedItems.length > 0" class="space-y-2 max-h-[480px] overflow-y-auto">
              <div
                v-for="(item, index) in selectedItems"
                :key="item.tempId"
                class="flex gap-2 items-start p-2 hover:bg-gray-50 rounded transition-colors group cursor-pointer"
              >
                <div class="text-gray-400 font-mono text-xs mt-1 flex-shrink-0 w-6">
                  {{ index + 1 }}.
                </div>
                <img
                  v-if="item.cover"
                  :src="getCoverUrl(item)"
                  class="w-12 h-16 object-cover rounded flex-shrink-0"
                  @error="onImageError"
                />
                <div
                  v-else
                  class="w-12 h-16 bg-gray-100 rounded flex items-center justify-center flex-shrink-0"
                >
                  <span class="material-symbols-outlined text-gray-400 text-sm">book</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-xs leading-tight mb-1 line-clamp-2">
                    {{ item.title }}
                  </div>
                  <div class="text-xs text-gray-500 truncate">
                    {{ formatAuthors(item.authors) }}
                  </div>
                </div>
                <el-button
                  type="danger"
                  size="small"
                  text
                  class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                  @click.stop="handleRemove(index)"
                >
                  删除
                </el-button>
              </div>
            </div>

            <el-empty v-else description="从左侧添加图书" :image-size="60" class="py-8" />

            <div v-if="selectedItems.length > 0" class="mt-4 pt-4 border-t space-y-2">
              <div class="text-xs text-gray-500 text-center mb-2">
                共 {{ selectedItems.length }} 本待刮削图书
              </div>
              <el-button type="primary" class="w-full" size="default" @click="currentStep = 1">
                下一步：检查列表
                <span class="material-symbols-outlined ml-1 text-sm">arrow_forward</span>
              </el-button>
            </div>
          </div>
        </div>

        <!-- 移动端：底部固定按钮 -->
        <div
          v-if="selectedItems.length > 0"
          class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 z-50"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium">已选 {{ selectedItems.length }} 本图书</span>
            <el-button type="text" size="small" @click="confirmClearSelected">清空</el-button>
          </div>
          <el-button type="primary" class="w-full" @click="currentStep = 1">
            下一步
            <span class="material-symbols-outlined ml-1">arrow_forward</span>
          </el-button>
        </div>
      </div>

      <!-- 步骤 2: 检查刮削列表 -->
      <div v-show="currentStep === 1" key="step2">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-medium text-lg">确认待刮削列表</h3>
              <p class="text-sm text-gray-500 mt-1">共 {{ selectedItems.length }} 本图书</p>
            </div>
            <div class="flex">
              <el-button type="text" size="small" @click="confirmClearSelected">清空列表</el-button>
            </div>
          </div>

          <div v-if="selectedItems.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div
                v-for="(item, index) in selectedItems"
                :key="item.tempId"
                class="relative bg-white border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer group"
              >
                <el-button
                  type="danger"
                  size="small"
                  text
                  class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity"
                  @click="handleRemove(index)"
                >
                  删除
                </el-button>

                <div class="flex flex-col">
                  <div class="mb-2 text-gray-400 font-mono text-xs">
                    #{{ String(index + 1).padStart(2, '0') }}
                  </div>

                  <div class="flex gap-3 mb-3">
                    <img
                      v-if="item.cover"
                      :src="getCoverUrl(item)"
                      class="w-16 h-22 object-cover rounded flex-shrink-0"
                      @error="onImageError"
                    />
                    <div
                      v-else
                      class="w-16 h-22 bg-gray-100 rounded flex items-center justify-center flex-shrink-0"
                    >
                      <span class="material-symbols-outlined text-gray-400 text-2xl">book</span>
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="font-medium text-sm mb-1 line-clamp-2 leading-tight">
                        {{ item.title }}
                      </div>
                      <div v-if="item.rating" class="flex items-center gap-1 text-xs text-gray-600">
                        <span
                          class="material-symbols-outlined text-yellow-500"
                          style="font-size: 14px"
                        >
                          star
                        </span>
                        {{ item.rating }}
                      </div>
                    </div>
                  </div>

                  <div class="space-y-1 text-xs text-gray-600">
                    <div v-if="item.authors" class="flex items-start gap-1">
                      <span class="material-symbols-outlined text-gray-400" style="font-size: 14px">
                        person
                      </span>
                      <span class="flex-1 line-clamp-1">{{ formatAuthors(item.authors) }}</span>
                    </div>
                    <div v-if="item.publisher" class="flex items-start gap-1">
                      <span class="material-symbols-outlined text-gray-400" style="font-size: 14px">
                        apartment
                      </span>
                      <span class="flex-1 line-clamp-1">{{ item.publisher }}</span>
                    </div>
                    <div v-if="item.published_at" class="flex items-start gap-1">
                      <span class="material-symbols-outlined text-gray-400" style="font-size: 14px">
                        calendar_today
                      </span>
                      <span class="flex-1">{{ item.published_at }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-center gap-4">
              <el-button @click="currentStep = 0">
                <span class="material-symbols-outlined mr-1">arrow_back</span>
                上一步
              </el-button>
              <el-button type="primary" @click="currentStep = 2">
                下一步
                <span class="material-symbols-outlined ml-1">arrow_forward</span>
              </el-button>
            </div>
          </div>

          <el-empty v-else description="列表为空，请返回搜索并添加图书">
            <el-button type="primary" @click="currentStep = 0">返回搜索</el-button>
          </el-empty>
        </div>
      </div>

      <!-- 步骤 3: 编辑任务信息 -->
      <div v-show="currentStep === 2">
        <div class="max-w-2xl mx-auto">
          <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between mb-4">
              <div class="font-medium text-lg">编辑任务信息</div>
            </div>

            <el-alert
              type="success"
              :closable="false"
              class="mb-4"
              show-icon
              title="即将创建刮削任务"
              :description="`共 ${selectedItems.length} 本图书将被添加到刮削队列`"
            />

            <el-form label-position="top" label-width="120px">
              <el-form-item label="任务名称" required>
                <el-input v-model="taskName" maxlength="100" show-word-limit />
              </el-form-item>

              <el-form-item label="刮削选项">
                <div class="space-y-3">
                  <el-checkbox v-model="options.autoDownloadCover" disabled>
                    <div>
                      <div class="font-medium">自动下载并保存封面</div>
                      <div class="text-xs text-gray-500">从元数据源下载封面图片并保存到服务器</div>
                    </div>
                  </el-checkbox>
                  <el-checkbox v-model="options.skipExisting" disabled>
                    <div>
                      <div class="font-medium">跳过已存在的图书（按ISBN）</div>
                      <div class="text-xs text-gray-500">
                        如果图书库中已有相同ISBN的图书，则跳过该条目
                      </div>
                    </div>
                  </el-checkbox>
                </div>
              </el-form-item>

              <el-divider />

              <div class="bg-gray-50 -mx-4 -mb-4 px-4 py-4 rounded-b-lg">
                <div class="flex justify-between items-center mb-4">
                  <div class="text-sm text-gray-600">
                    <div class="font-medium mb-1">任务摘要</div>
                    <div>• 待刮削图书：{{ selectedItems.length }} 本</div>
                    <div>• 自动下载封面：{{ options.autoDownloadCover ? '是' : '否' }}</div>
                    <div>• 跳过已存在：{{ options.skipExisting ? '是' : '否' }}</div>
                  </div>
                </div>

                <div class="flex justify-center gap-4">
                  <el-button @click="currentStep = 1">
                    <span class="material-symbols-outlined mr-1">arrow_back</span>
                    上一步
                  </el-button>
                  <el-button
                    type="primary"
                    :disabled="!canSubmit"
                    :loading="submitting"
                    @click="handleSubmit"
                  >
                    <span class="material-symbols-outlined mr-1">check_circle</span>
                    确认创建任务
                  </el-button>
                </div>
              </div>
            </el-form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { metadataApi, scrapingTasksApi } from '@/api'
import type { MetadataProvider } from '@/api/metadata'

const router = useRouter()

function back() {
  router.back()
}

// 步骤管理
const currentStep = ref(0)

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

// 监听选中项数量，自动生成任务名称
watch(
  () => selectedItems.value.length,
  (newLength, oldLength) => {
    // 只在第一次添加或清空后重新添加时自动生成
    if (newLength > 0 && oldLength === 0 && !taskName.value) {
      const date = new Date().toISOString().split('T')[0]
      taskName.value = `刮削任务 ${date}`
    }
  },
)

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
  } catch {
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

const confirmClearSelected = async () => {
  if (!selectedItems.value.length) return
  try {
    await ElMessageBox.confirm('确认清空已选图书？此操作不可撤销。', '清空确认', {
      type: 'warning',
      confirmButtonText: '清空',
      cancelButtonText: '取消',
    })
    selectedItems.value = []
    ElMessage.success('已清空已选图书')
  } catch {
    // 用户取消，不做处理
  }
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

    await scrapingTasksApi.create(payload)

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

<style scoped></style>
