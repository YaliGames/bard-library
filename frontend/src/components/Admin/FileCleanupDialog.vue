<template>
  <el-dialog :model-value="modelValue" title="清理工具" width="720px" @close="close">
    <!-- 步骤指引 -->
    <div class="mb-3">
      <el-steps :active="currentStep" finish-status="success" align-center>
        <el-step title="清理扫描" />
        <el-step title="选择清理内容" />
        <el-step title="完成清理" />
      </el-steps>
    </div>

    <!-- Step 1: 扫描 -->
    <div v-if="currentStep===0" class="space-y-3 text-sm leading-6">
      <div>
        <div class="font-medium mb-1">说明</div>
        <ul class="list-disc pl-5 text-gray-600">
          <li><span class="font-medium text-gray-800">未引用封面</span>：被上传为封面的图片，但当前没有任何书籍将其设为封面。</li>
          <li><span class="font-medium text-gray-800">悬挂记录</span>：数据库中存在文件记录，但其关联的书籍已不存在。</li>
          <li><span class="font-medium text-gray-800">物理缺失</span>：数据库中存在文件记录，但磁盘上找不到对应文件。</li>
          <li><span class="font-medium text-gray-800">无记录的物理文件</span>：磁盘上存在文件，但数据库中没有对应记录。</li>
        </ul>
      </div>
      <div class="flex items-center gap-3">
        <el-button type="primary" @click="startScan" :loading="cleaning">开始扫描</el-button>
      </div>
      <el-alert v-if="cleanupPreview" type="success" show-icon :closable="false" class="mt-2">
        <template #title>扫描完成</template>
        <div class="mt-1 text-gray-700">
          <div class="flex flex-wrap gap-x-6 gap-y-1">
            <span>未引用封面：{{ cleanupPreview.summary.unused_covers }}</span>
            <span>悬挂记录：{{ cleanupPreview.summary.dangling_records }}</span>
            <span>物理缺失：{{ cleanupPreview.summary.missing_physical }}</span>
            <span>无记录的物理文件：{{ cleanupPreview.summary.orphans_physical }}</span>
          </div>
        </div>
      </el-alert>
    </div>

    <!-- Step 2: 选择与执行 -->
    <div v-else-if="currentStep===1" class="space-y-3 text-sm leading-6">
      <div class="font-medium">扫描结果与清理选项</div>
      <el-alert v-if="cleanupPreview" type="info" show-icon :closable="false">
        <template #title>扫描结果</template>
        <div class="mt-1 text-gray-700">
          <div class="flex flex-wrap gap-x-6 gap-y-1">
            <span>未引用封面：{{ cleanupPreview.summary.unused_covers }}</span>
            <span>悬挂记录：{{ cleanupPreview.summary.dangling_records }}</span>
            <span>物理缺失：{{ cleanupPreview.summary.missing_physical }}</span>
            <span>无记录的物理文件：{{ cleanupPreview.summary.orphans_physical }}</span>
          </div>
          <div class="mt-3 space-y-2 max-h-64 overflow-auto pr-2">
            <div v-if="cleanupPreview.preview?.covers?.length">
              <div class="font-medium mb-1">未引用封面列表</div>
              <ul class="list-disc pl-5">
                <li v-for="it in cleanupPreview.preview.covers" :key="'c-'+it.id">#{{ it.id }} · {{ it.path }}</li>
              </ul>
            </div>
            <div v-if="cleanupPreview.preview?.dangling?.length">
              <div class="font-medium mb-1">悬挂记录列表</div>
              <ul class="list-disc pl-5">
                <li v-for="it in cleanupPreview.preview.dangling" :key="'d-'+it.id">#{{ it.id }} · {{ it.path }}</li>
              </ul>
            </div>
            <div v-if="cleanupPreview.preview?.missing_physical?.length">
              <div class="font-medium mb-1">物理缺失记录</div>
              <ul class="list-disc pl-5">
                <li v-for="it in cleanupPreview.preview.missing_physical" :key="'m-'+it.id">#{{ it.id }} · {{ it.path }}</li>
              </ul>
            </div>
            <div v-if="cleanupPreview.preview?.orphans_physical?.length">
              <div class="font-medium mb-1">无记录的物理文件</div>
              <ul class="list-disc pl-5">
                <li v-for="it in cleanupPreview.preview.orphans_physical" :key="'o-'+it.path">{{ it.path }}</li>
              </ul>
            </div>
          </div>
        </div>
      </el-alert>

      <div>
        <div class="font-medium mb-1">选择要执行清理的范围（可多选）</div>
        <div class="flex flex-wrap gap-4">
          <el-checkbox v-model="ckCovers">未引用封面</el-checkbox>
          <el-checkbox v-model="ckDangling">悬挂记录</el-checkbox>
          <el-checkbox v-model="ckMissing">物理缺失</el-checkbox>
          <el-checkbox v-model="ckOrphans">无记录的物理文件</el-checkbox>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <el-switch v-model="removePhysical" :disabled="ckOrphans" active-text="同时删除物理文件" />
        <el-tooltip content="仅在执行时生效；当选择‘无记录的物理文件’时会强制开启此项" placement="top">
          <span class="text-gray-400 cursor-help material-symbols-outlined text-base">info</span>
        </el-tooltip>
      </div>

      <div class="flex items-center gap-3">
        <el-button @click="goToStep(0)" :disabled="cleaning">上一步</el-button>
        <el-button type="danger" @click="executeCleanup" :disabled="cleaning || !hasAnySelection" :loading="cleaning">执行清理</el-button>
      </div>
    </div>

    <!-- Step 3: 完成 -->
    <div v-else-if="currentStep===2" class="space-y-3 text-sm leading-6">
      <el-result icon="success" title="清理完成" sub-title="已根据选择执行清理操作。">
        <template #extra>
          <el-button type="primary" @click="close">关闭</el-button>
          <el-button @click="restart">再次扫描</el-button>
        </template>
      </el-result>
    </div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="close">关闭</el-button>
      </span>
    </template>
  </el-dialog>

</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { adminFilesApi } from '@/api/adminFiles'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ (e:'update:modelValue', v:boolean): void; (e:'executed'): void }>()

const ckCovers = ref(true)
const ckDangling = ref(false)
const ckMissing = ref(false)
const ckOrphans = ref(false)
const cleanupPreview = ref<any|null>(null)
const removePhysical = ref(false)
const cleaning = ref(false)
const currentStep = ref(0) // 0: 扫描 1: 选择 2: 完成

const hasAnySelection = computed(() => ckCovers.value || ckDangling.value || ckMissing.value || ckOrphans.value)

watch(ckOrphans, (v) => { if (v) removePhysical.value = true })

function close(){ emit('update:modelValue', false) }

watch(() => props.modelValue, (v) => {
  if (v) {
    // 每次打开时回到第 1 步并清理上次扫描结果
    currentStep.value = 0
    cleanupPreview.value = null
    cleaning.value = false
  }
})

function goToStep(i:number){ currentStep.value = i }
function restart(){ currentStep.value = 0; cleanupPreview.value = null }

async function startScan(){
  await previewCleanup()
  if (cleanupPreview.value) currentStep.value = 1
}

async function previewCleanup(){
  cleaning.value = true
  try {
    const kinds = ['covers','dangling','missing','orphans'] as Array<'covers'|'dangling'|'missing'|'orphans'>
    if (ckOrphans.value) removePhysical.value = true
    cleanupPreview.value = await adminFilesApi.cleanup({ kinds, dry: true, removePhysical: removePhysical.value })
  } catch(e:any){
    ElMessage.error(e?.message || '预览失败')
  } finally { cleaning.value = false }
}

async function executeCleanup(){
  if (!cleanupPreview.value) return
  cleaning.value = true
  try {
    const kinds = [] as Array<'covers'|'dangling'|'missing'|'orphans'>
    if (ckCovers.value) kinds.push('covers')
    if (ckDangling.value) kinds.push('dangling')
    if (ckMissing.value) kinds.push('missing')
    if (ckOrphans.value) kinds.push('orphans')
    if (kinds.length === 0) { ElMessage.error('请至少选择一项清理内容'); return }
    if (ckOrphans.value) removePhysical.value = true
    await adminFilesApi.cleanup({ kinds, dry: false, removePhysical: removePhysical.value })
    ElMessage.success('清理完成')
    cleanupPreview.value = null
    emit('executed')
    currentStep.value = 2
  } catch(e:any){
    ElMessage.error(e?.message || '清理失败')
  } finally { cleaning.value = false }
}
</script>

<style scoped>
</style>
