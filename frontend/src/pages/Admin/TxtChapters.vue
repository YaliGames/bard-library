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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">目录</span>
            <el-button
              v-if="fileId"
              type="primary"
              size="small"
              plain
              @click="showFileSelectDialog"
            >
              <span class="material-symbols-outlined mr-1 text-sm">swap_horiz</span>
              切换文件
            </el-button>
            <el-button v-else type="primary" size="small" @click="showFileSelectDialog">
              <span class="material-symbols-outlined mr-1 text-sm">folder_open</span>
              选择文件
            </el-button>
          </div>
          <el-empty
            v-if="!fileId && !chaptersLoading"
            description="请先打开 TXT 文件"
            :image-size="100"
          >
            <el-button type="primary" @click="showFileSelectDialog">
              <span class="material-symbols-outlined mr-1">folder_open</span>
              选择 TXT 文件
            </el-button>
          </el-empty>
          <el-empty
            v-else-if="fileId && !chaptersLoading && chapters.length === 0"
            description="暂无目录，尝试右侧使用规则预览"
          />
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
            <el-table :data="chapters" border height="520" v-loading="chaptersLoading">
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
        </div>
      </div>
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="font-medium text-lg">规则与操作</span>
          </div>
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
                <div class="flex items-center justify-between mb-1">
                  <div class="text-gray-600">正则表达式:</div>
                  <el-button size="small" text type="primary" @click="editPresetPattern">
                    <span class="material-symbols-outlined text-sm mr-1">edit</span>
                    编辑
                  </el-button>
                </div>
                <code class="text-primary break-all">{{ currentPresetPattern }}</code>
              </div>
            </div>

            <!-- 自定义规则输入 -->
            <div v-else>
              <div class="text-sm text-gray-600 mb-1">自定义正则表达式</div>
              <el-input v-model="pattern" placeholder="输入正则表达式" type="textarea" :rows="3" />
            </div>

            <div class="flex">
              <el-button @click="preview" :loading="chaptersLoading">预览</el-button>
              <el-button type="primary" @click="save" :loading="saving">保存</el-button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 选择文件弹窗 -->
    <el-dialog
      v-model="fileSelectDialogVisible"
      title="选择 TXT 文件"
      width="800px"
      :close-on-click-modal="false"
    >
      <div class="mb-3">
        <el-input
          v-model="searchQuery"
          placeholder="搜索图书标题或文件名..."
          clearable
          @input="handleSearchInput"
        >
          <template #prefix>
            <span class="material-symbols-outlined text-gray-400">search</span>
          </template>
        </el-input>
      </div>

      <div v-loading="filesLoading" style="min-height: 200px">
        <el-empty v-if="!filesLoading && txtFiles.length === 0" description="未找到 TXT 文件" />
        <el-table
          v-else
          :data="txtFiles"
          border
          stripe
          height="400"
          highlight-current-row
          @row-click="selectFile"
        >
          <el-table-column label="ID" prop="id" width="80" />
          <el-table-column label="图书" prop="book.title" min-width="200">
            <template #default="{ row }">
              <span v-if="row.book?.title">{{ row.book.title }}</span>
              <span v-else class="text-gray-400">未关联图书</span>
            </template>
          </el-table-column>
          <el-table-column label="文件名" prop="filename" min-width="180">
            <template #default="{ row }">
              <span class="text-xs text-gray-600">{{ row.filename }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="100" align="center">
            <template #default="{ row }">
              <el-button type="primary" size="small" @click.stop="selectFile(row)">选择</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <template #footer>
        <el-button @click="fileSelectDialogVisible = false">取消</el-button>
      </template>
    </el-dialog>
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
const txtFiles = ref<AdminFileItem[]>([])
const chapters = ref<Chapter[]>([])
const pattern = ref('')
const saving = ref(false)
const ruleType = ref<'builtin' | 'custom'>('builtin') // 规则类型
const selectedPresetId = ref<string>('')
const allPresets = computed(() => builtinRegexPresets)
const isPreviewMode = ref(false) // 标记是否为预览模式
const fileSelectDialogVisible = ref(false)
const searchQuery = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

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

// 编辑内置规则的正则表达式
function editPresetPattern() {
  if (currentPresetPattern.value) {
    pattern.value = currentPresetPattern.value
    ruleType.value = 'custom'
  }
}

function back() {
  router.back()
}

// 退出预览模式
function exitPreviewMode() {
  isPreviewMode.value = false
  fetchChapters()
}

// 显示文件选择弹窗
async function showFileSelectDialog() {
  fileSelectDialogVisible.value = true
  searchQuery.value = ''
  await loadFiles()
}

// 加载文件列表
async function loadFiles(query?: string) {
  startLoading('files')
  try {
    const res = await adminFilesApi.list({
      q: query || undefined,
      format: 'txt',
    })
    txtFiles.value = res.items
  } catch (e: any) {
    handleError(e, { context: 'Admin.TxtChapters.loadFiles' })
    txtFiles.value = []
  } finally {
    stopLoading('files')
  }
}

// 搜索输入防抖
function handleSearchInput() {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    loadFiles(searchQuery.value.trim())
  }, 300)
}

// 选择文件
function selectFile(row: AdminFileItem) {
  fileId.value = row.id
  fileSelectDialogVisible.value = false
  isPreviewMode.value = false
  fetchChapters()
}

async function fetchChapters() {
  if (!fileId.value) return
  startLoading('chapters')
  isPreviewMode.value = false // 加载实际目录时退出预览模式
  try {
    const response = await txtApi.listChapters(fileId.value)
    chapters.value = response.chapters
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
    const response = await txtApi.listChapters(fileId.value, { pattern: pat, dry: true })
    chapters.value = response.chapters
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
  fileId.value = initId
  fetchChapters()
}
</script>
