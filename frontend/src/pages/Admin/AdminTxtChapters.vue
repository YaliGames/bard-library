<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">TXT 章节管理</h2>
      <div class="flex items-center">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回
        </el-button>
      </div>
    </div>

    <el-card shadow="never" class="mb-4">
      <template #header>
        <div class="font-medium">选择 TXT 文件</div>
      </template>
      <div class="flex gap-2 items-center">
        <el-input v-model="fileIdInput" placeholder="输入 TXT 文件ID" style="width:220px" />
        <el-button type="primary" @click="load">加载目录</el-button>
      </div>
      <div class="text-xs text-gray-500 mt-2">也可从图书编辑页的文件列表点击“编辑章节”跳转携带 fileId。</div>
    </el-card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <el-card class="lg:col-span-2" shadow="never">
        <template #header>
          <div class="font-medium">目录</div>
        </template>
        <div v-if="chaptersLoading" class="text-gray-500">加载中…</div>
        <el-empty v-else-if="chapters.length === 0" description="暂无目录，尝试右侧使用规则预览" />
        <el-table v-else :data="chapters" border height="520">
          <el-table-column label="#" prop="index" width="80" />
          <el-table-column label="标题" prop="title" />
          <el-table-column label="偏移" prop="offset" width="120" />
          <el-table-column label="长度" prop="length" width="120" />
          <el-table-column label="操作" width="220" align="center">
            <template #default="{ row }">
              <el-button class="mr-3" size="small" @click="promptRename(row)">重命名</el-button>
              <el-dropdown @command="(cmd:string)=>onDeleteMerge(row, cmd as any)">
                <el-button size="small" type="danger" plain>
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
      </el-card>
      <el-card shadow="never">
        <template #header>
          <div class="font-medium">规则与操作</div>
        </template>
        <div class="flex flex-col gap-3">
          <div>
            <div class="text-sm text-gray-600 mb-1">内置规则</div>
            <el-select v-model="selectedPresetId" placeholder="选择一个内置规则（可选）" filterable clearable>
              <el-option :key="''" :label="'（不使用内置规则）'" :value="''" />
              <el-option v-for="p in allPresets" :key="p.id" :label="p.name" :value="p.id" />
            </el-select>
          </div>
          <div>
            <div class="text-sm text-gray-600 mb-1">自定义正则</div>
            <el-input v-model="pattern" placeholder="自定义正则（空用默认或使用上方内置）" />
          </div>
          <div class="flex gap-2">
            <el-button @click="preview" :loading="chaptersLoading">预览</el-button>
            <el-button type="primary" @click="save" :loading="saving">保存</el-button>
          </div>
          <div class="text-xs text-gray-500">提示：若同时选择内置与填写自定义，则优先使用自定义。</div>
        </div>
      </el-card>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { txtApi, type Chapter } from '@/api/txt'
import { ElMessage, ElMessageBox } from 'element-plus'
import { builtinRegexPresets } from '@/constants/regexPresets'

const router = useRouter()
const route = useRoute()
const fileIdInput = ref<string>('')
const fileId = ref<number | undefined>(undefined)
const chapters = ref<Chapter[]>([])
const chaptersLoading = ref(false)
const pattern = ref('')
const saving = ref(false)
const selectedPresetId = ref<string>('')
const allPresets = computed(() => builtinRegexPresets)

function back() { router.back() }

async function load() {
  const idStr = fileIdInput.value.trim()
  const idNum = Number(idStr)
  if (!Number.isFinite(idNum)) { ElMessage.error('请输入有效的文件ID'); return }
  fileId.value = idNum
  await fetchChapters()
}

async function fetchChapters() {
  if (!fileId.value) return
  chaptersLoading.value = true
  try { chapters.value = await txtApi.listChapters(fileId.value) } catch (e: any) { ElMessage.error(e?.message || '加载失败') }
  finally { chaptersLoading.value = false }
}

async function preview() {
  if (!fileId.value) return
  chaptersLoading.value = true
  try {
    const pat = pattern.value.trim() || allPresets.value.find(p => p.id === selectedPresetId.value)?.pattern || undefined
    chapters.value = await txtApi.listChapters(fileId.value, { pattern: pat, dry: true })
  }
  catch (e: any) { ElMessage.error(e?.message || '预览失败') }
  finally { chaptersLoading.value = false }
}

async function save() {
  if (!fileId.value) return
  saving.value = true
  try {
    const pat = pattern.value || allPresets.value.find(p => p.id === selectedPresetId.value)?.pattern || undefined
    await txtApi.saveChapters(fileId.value, { pattern: pat, replace: true }); ElMessage.success('已保存'); await fetchChapters()
  }
  catch (e: any) { ElMessage.error(e?.message || '保存失败') }
  finally { saving.value = false }
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
    ElMessage.success('已重命名')
    await fetchChapters()
  } catch {
    // cancelled
  }
}

// 删除并合并
async function onDeleteMerge(row: Chapter, direction: 'prev'|'next') {
  if (!fileId.value) return
  // 边界保护：第一章不能合并到 prev，最后一章不能合并到 next
  const isFirst = row.index === 0
  const isLast = chapters.value.length > 0 && row.index === chapters.value.length - 1
  if ((direction === 'prev' && isFirst) || (direction === 'next' && isLast)) {
    ElMessage.warning('无法与不存在的相邻章节合并')
    return
  }
  try {
    await ElMessageBox.confirm(`将删除“${row.title || '（无题）'}”，并与${direction==='prev'?'上一个':'下一个'}章节合并。确认？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消',
      dangerouslyUseHTMLString: false,
    })
  } catch { return }
  try {
    await txtApi.deleteChapterWithMerge(fileId.value, row.index, direction)
    ElMessage.success('已删除并合并')
    await fetchChapters()
  } catch (e:any) {
    ElMessage.error(e?.message || '操作失败')
  }
}

// 支持从 params.id 或 query.fileId 进入
const fidParam = Number(route.params.id || 0)
const fidQuery = Number(route.query.fileId || 0)
const initId = Number.isFinite(fidParam) && fidParam > 0 ? fidParam : (Number.isFinite(fidQuery) && fidQuery > 0 ? fidQuery : 0)
if (initId > 0) { fileIdInput.value = String(initId); fileId.value = initId; fetchChapters() }
</script>
