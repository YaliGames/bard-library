<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">TXT 章节管理</h2>
      <div class="flex items-center">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <el-card v-if="!hasRouteFileId" shadow="never" class="mb-4">
      <template #header>
        <div class="font-medium">选择 TXT 文件</div>
      </template>
      <div class="flex gap-2 items-start">
        <el-select
          v-model="selectedFileId"
          filterable
          remote
          reserve-keyword
          placeholder="输入关键字搜索 TXT 文件"
          :remote-method="searchFiles"
          :loading="filesLoading"
          clearable
          style="flex: 1; max-width: 500px"
          @change="onFileSelected"
        >
          <el-option
            v-for="file in txtFiles"
            :key="file.id"
            :label="`[${file.id}] ${file.book?.title || '未关联图书'} - ${file.filename}`"
            :value="file.id"
          >
            <div class="flex items-center justify-between h-full">
              <span class="text-sm">{{ file.book?.title || '未关联图书' }}</span>
              <span class="text-xs text-gray-400 ml-2">{{ file.filename }}</span>
            </div>
          </el-option>
        </el-select>
        <el-button type="primary" :disabled="!selectedFileId" @click="load">加载目录</el-button>
      </div>
      <div class="text-xs text-gray-500 mt-2">
        也可从图书编辑页的文件列表点击"编辑章节"直接跳转。
      </div>
    </el-card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <el-card class="lg:col-span-2" shadow="never">
        <template #header>
          <div class="font-medium">目录</div>
        </template>
        <div v-if="chaptersLoading" class="text-gray-500">加载中…</div>
        <el-empty v-else-if="chapters.length === 0" description="暂无目录，尝试右侧使用规则预览" />
        <div v-else>
          <div
            v-if="isPreviewMode"
            class="mb-2 text-sm text-orange-600 flex items-center justify-between bg-orange-50 border border-orange-200 rounded px-3 py-2"
          >
            <div class="flex items-center">
              <span class="material-symbols-outlined mr-1 text-base">visibility</span>
              <span>预览模式：当前显示的是使用规则生成的预览结果，不会影响现有目录</span>
            </div>
            <el-button size="small" @click="exitPreviewMode">
              <span class="material-symbols-outlined mr-1 text-sm">close</span>
              退出预览
            </el-button>
          </div>
          <el-table :data="chapters" border height="520">
            <el-table-column label="#" prop="index" width="80" />
            <el-table-column label="标题" prop="title" />
            <el-table-column label="偏移" prop="offset" width="120" />
            <el-table-column label="长度" prop="length" width="120" />
            <el-table-column v-if="!isPreviewMode" label="操作" width="220" align="center">
              <template #default="{ row }">
                <el-button
                  v-permission="'books.edit'"
                  class="mr-3"
                  size="small"
                  @click="promptRename(row)"
                >
                  重命名
                </el-button>
                <el-dropdown @command="(cmd: string) => onDeleteMerge(row, cmd as any)">
                  <el-button v-permission="'books.edit'" size="small" type="danger" plain>
                    删除并合并
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="prev">合并到上一个</el-dropdown-item>
                      <el-dropdown-item command="next">合并到下一个</el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </el-card>
      <el-card shadow="never">
        <template #header>
          <div class="font-medium">规则与操作</div>
        </template>
        <div class="flex flex-col gap-3">
          <div>
            <div class="text-sm text-gray-600 mb-1">规则类型</div>
            <el-radio-group v-model="ruleType">
              <el-radio value="builtin">内置规则</el-radio>
              <el-radio value="custom">自定义规则</el-radio>
            </el-radio-group>
          </div>

          <!-- 内置规则选择 -->
          <div v-if="ruleType === 'builtin'">
            <div class="text-sm text-gray-600 mb-1">选择内置规则</div>
            <el-select v-model="selectedPresetId" placeholder="选择一个内置规则" filterable>
              <el-option v-for="p in allPresets" :key="p.id" :label="p.name" :value="p.id" />
            </el-select>
            <div v-if="currentPresetPattern" class="mt-2 p-2 bg-gray-50 rounded text-xs">
              <div class="text-gray-600 mb-1">正则表达式:</div>
              <code class="text-primary break-all">{{ currentPresetPattern }}</code>
            </div>
          </div>

          <!-- 自定义规则输入 -->
          <div v-else>
            <div class="text-sm text-gray-600 mb-1">自定义正则表达式</div>
            <el-input v-model="pattern" placeholder="输入正则表达式" type="textarea" :rows="3" />
          </div>

          <div class="flex gap-2">
            <el-button @click="preview" :loading="chaptersLoading">预览</el-button>
            <el-button type="primary" @click="save" :loading="saving">保存</el-button>
          </div>
        </div>
      </el-card>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { txtApi, type Chapter } from '@/api/txt'
import { adminFilesApi, type AdminFileItem } from '@/api/adminFiles'
import { ElMessageBox } from 'element-plus'
import { builtinRegexPresets } from '@/config/regexPresets'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useLoading } from '@/composables/useLoading'

const { handleError, handleSuccess } = useErrorHandler()

const { isLoadingKey, startLoading, stopLoading } = useLoading()
const filesLoading = computed(() => isLoadingKey('files'))
const chaptersLoading = computed(() => isLoadingKey('chapters'))

const router = useRouter()
const route = useRoute()
const fileId = ref<number | undefined>(undefined)
const selectedFileId = ref<number | undefined>(undefined)
const txtFiles = ref<AdminFileItem[]>([])
const chapters = ref<Chapter[]>([])
const pattern = ref('')
const saving = ref(false)
const ruleType = ref<'builtin' | 'custom'>('builtin') // 规则类型
const selectedPresetId = ref<string>('')
const allPresets = computed(() => builtinRegexPresets)
const isPreviewMode = ref(false) // 标记是否为预览模式

// 当前选中的内置规则的正则表达式
const currentPresetPattern = computed(() => {
  if (ruleType.value === 'builtin' && selectedPresetId.value) {
    return allPresets.value.find(p => p.id === selectedPresetId.value)?.pattern || ''
  }
  return ''
})

// 监听规则类型变化,切换到内置规则时默认选择第一个
watch(ruleType, newType => {
  if (newType === 'builtin' && !selectedPresetId.value && allPresets.value.length > 0) {
    selectedPresetId.value = allPresets.value[0].id
  }
})

// 检查是否有路由参数(从其他页面跳转过来)
const hasRouteFileId = computed(() => {
  const fidParam = Number(route.params.id || 0)
  const fidQuery = Number(route.query.fileId || 0)
  return (Number.isFinite(fidParam) && fidParam > 0) || (Number.isFinite(fidQuery) && fidQuery > 0)
})

function back() {
  router.back()
}

// 退出预览模式
function exitPreviewMode() {
  isPreviewMode.value = false
  fetchChapters()
}

// 搜索 TXT 文件
async function searchFiles(query: string) {
  if (!query) {
    txtFiles.value = []
    return
  }
  startLoading('files')
  try {
    const res = await adminFilesApi.list({ q: query, format: 'txt' })
    txtFiles.value = res.items
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.searchFiles' })
    txtFiles.value = []
  } finally {
    stopLoading('files')
  }
}

// 文件选择变化
function onFileSelected(id: number | undefined) {
  if (id) {
    fileId.value = id
  }
}

async function load() {
  if (!selectedFileId.value) {
    handleError(new Error('No file selected'), {
      context: 'Admin.TxtChapters.load',
      message: '请先选择一个 TXT 文件',
    })
    return
  }
  fileId.value = selectedFileId.value
  isPreviewMode.value = false
  await fetchChapters()
}

async function fetchChapters() {
  if (!fileId.value) return
  startLoading('chapters')
  isPreviewMode.value = false // 加载实际目录时退出预览模式
  try {
    chapters.value = await txtApi.listChapters(fileId.value)
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.fetchChapters' })
  } finally {
    stopLoading('chapters')
  }
}

async function preview() {
  if (!fileId.value) {
    handleError(new Error('No file selected'), {
      context: 'Admin.TxtChapters.preview',
      message: '请先选择一个 TXT 文件',
    })
    return
  }

  // 根据规则类型获取正则表达式
  let pat: string | undefined
  if (ruleType.value === 'builtin') {
    pat = allPresets.value.find(p => p.id === selectedPresetId.value)?.pattern
  } else {
    pat = pattern.value.trim() || undefined
  }

  if (!pat) {
    handleError(new Error('No pattern provided'), {
      context: 'Admin.TxtChapters.preview',
      message: '请选择内置规则或输入自定义正则表达式',
    })
    return
  }

  startLoading('chapters')
  isPreviewMode.value = true // 进入预览模式
  try {
    chapters.value = await txtApi.listChapters(fileId.value, { pattern: pat, dry: true })
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.preview' })
  } finally {
    stopLoading('chapters')
  }
}

async function save() {
  if (!fileId.value) return

  // 根据规则类型获取正则表达式
  let pat: string | undefined
  if (ruleType.value === 'builtin') {
    pat = allPresets.value.find(p => p.id === selectedPresetId.value)?.pattern
  } else {
    pat = pattern.value.trim() || undefined
  }

  if (!pat) {
    handleError(new Error('No pattern provided'), {
      context: 'Admin.TxtChapters.save',
      message: '请选择内置规则或输入自定义正则表达式',
    })
    return
  }

  saving.value = true
  try {
    await txtApi.saveChapters(fileId.value, { pattern: pat, replace: true })
    handleSuccess('章节目录已保存')
    isPreviewMode.value = false // 退出预览模式
    await fetchChapters()
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.save' })
  } finally {
    saving.value = false
  }
}

// 重命名
async function promptRename(row: Chapter) {
  try {
    const { value } = await ElMessageBox.prompt('输入新的章节标题（可留空）', '重命名章节', {
      inputValue: row.title || '',
      inputPlaceholder: '新的标题',
      confirmButtonText: '确定',
      cancelButtonText: '取消',
    })
    const title = (value ?? '').trim()
    if (!fileId.value) return
    await txtApi.renameChapter(fileId.value, row.index, title || null)
    handleSuccess('已重命名章节')
    await fetchChapters()
  } catch {
    // cancelled
  }
}

// 删除并合并
async function onDeleteMerge(row: Chapter, direction: 'prev' | 'next') {
  if (!fileId.value) return
  // 边界保护：第一章不能合并到 prev，最后一章不能合并到 next
  const isFirst = row.index === 0
  const isLast = chapters.value.length > 0 && row.index === chapters.value.length - 1
  if ((direction === 'prev' && isFirst) || (direction === 'next' && isLast)) {
    handleError(new Error('Cannot merge with non-existing chapter'), {
      context: 'Admin.TxtChapters.onDeleteMerge',
      message: '无法与不存在的相邻章节合并',
    })
    return
  }
  try {
    await ElMessageBox.confirm(
      `将删除“${row.title || '（无题）'}”，并与${direction === 'prev' ? '上一个' : '下一个'}章节合并。确认？`,
      '删除确认',
      {
        type: 'warning',
        confirmButtonText: '删除',
        cancelButtonText: '取消',
        dangerouslyUseHTMLString: false,
      },
    )
  } catch {
    return
  }
  try {
    await txtApi.deleteChapterWithMerge(fileId.value, row.index, direction)
    handleSuccess('已删除并合并章节')
    await fetchChapters()
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.onDeleteMerge' })
  }
}

// 初始化默认选中第一个内置规则
if (allPresets.value.length > 0) {
  selectedPresetId.value = allPresets.value[0].id
}

// 支持从 params.id 或 query.fileId 进入
const fidParam = Number(route.params.id || 0)
const fidQuery = Number(route.query.fileId || 0)
const initId =
  Number.isFinite(fidParam) && fidParam > 0
    ? fidParam
    : Number.isFinite(fidQuery) && fidQuery > 0
      ? fidQuery
      : 0
if (initId > 0) {
  selectedFileId.value = initId
  fileId.value = initId
  fetchChapters()
}
</script>
