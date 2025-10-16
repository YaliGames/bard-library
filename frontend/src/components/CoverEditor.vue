<template>
  <div class="flex flex-col gap-3 w-full">
    <!-- 预览 -->
    <div class="w-full flex justify-center">
      <div class="w-full max-w-[240px]">
        <CoverImage :file-id="modelValue || null" alt="cover" aspect="3/4" />
      </div>
    </div>

    <!-- 上传 + 移除 -->
    <div class="flex items-center gap-2">
      <el-upload :show-file-list="false" :auto-upload="false" accept="image/*" :on-change="onFileChange">
        <el-button :loading="uploading" type="primary">上传封面</el-button>
      </el-upload>
      <el-button v-if="modelValue" @click="$emit('update:modelValue', null)" type="danger" plain>移除封面</el-button>
    </div>

    <!-- 链接导入 -->
    <div class="flex items-center gap-2">
      <el-input v-model="url" placeholder="通过图片链接导入封面" />
      <el-button :loading="linking" @click="importFromUrl" :disabled="!url">导入</el-button>
    </div>

    <div class="text-xs text-gray-500">建议图片比例 3:4，过大文件会自动压缩存储。</div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import CoverImage from './CoverImage.vue'
import { ElMessage } from 'element-plus'
import { coversApi } from '@/api/covers'

const props = defineProps<{ modelValue: number | null | undefined; bookId?: number }>()
const emit = defineEmits<{ (e: 'update:modelValue', v: number | null): void; (e:'changed', v:number|null): void }>()

const uploading = ref(false)
const linking = ref(false)
const url = ref('')

async function onFileChange(file: any) {
  if (!file?.raw) return
  if (!props.bookId) { ElMessage.error('请先保存图书后再上传封面'); return }
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', file.raw)
    const r = await coversApi.upload(props.bookId, fd)
    emit('update:modelValue', r.file_id)
    emit('changed', r.file_id)
    ElMessage.success('封面已更新')
  } catch (e:any) {
    ElMessage.error(e?.message || '上传失败')
  } finally { uploading.value = false }
}

async function importFromUrl() {
  if (!url.value) return
  if (!props.bookId) { ElMessage.error('请先保存图书后再操作'); return }
  linking.value = true
  try {
    const r = await coversApi.fromUrl(props.bookId, { url: url.value })
    emit('update:modelValue', r.file_id)
    emit('changed', r.file_id)
    ElMessage.success('封面已更新')
    url.value = ''
  } catch (e:any) {
    ElMessage.error(e?.message || '导入失败')
  } finally { linking.value = false }
}
</script>
