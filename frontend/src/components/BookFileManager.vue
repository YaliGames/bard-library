<template>
  <div>
    <!-- 文件列表 -->
    <div v-if="files.length > 0" class="space-y-2">
      <div
        v-for="file in files"
        :key="file.id"
        class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors"
      >
        <!-- 左侧：文件类型图标和文件名 -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <!-- 文件类型标签 -->
          <div
            class="flex-shrink-0 px-2 py-1 rounded text-xs font-medium uppercase"
            :class="getFileTypeClass(file.format)"
          >
            {{ file.format || 'file' }}
          </div>

          <!-- 文件名 -->
          <div class="flex-1 min-w-0">
            <el-tooltip :content="getFileName(file)" placement="top">
              <div class="text-sm font-medium text-gray-900 truncate">
                {{ getFileName(file) }}
              </div>
            </el-tooltip>
            <div v-if="file.mime" class="text-xs text-gray-500 mt-0.5">
              {{ file.mime }}
            </div>
          </div>
        </div>

        <!-- 右侧：操作按钮 -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <el-button-group>
            <el-tooltip content="下载文件" placement="top">
              <el-button
                type="primary"
                size="small"
                v-if="hasPermission('books.download')"
                @click="handleDownload(file.id)"
              >
                <span class="material-symbols-outlined text-lg">download</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="编辑章节" placement="top">
              <el-button
                type="primary"
                size="small"
                v-if="hasPermission('books.edit') && (file.format || '').toLowerCase() === 'txt'"
                @click="handleEditChapters(file.id)"
              >
                <span class="material-symbols-outlined text-lg">edit_note</span>
              </el-button>
            </el-tooltip>
            <el-tooltip content="删除文件" placement="top">
              <el-button
                type="danger"
                size="small"
                v-if="hasPermission('files.delete')"
                @click="handleRemove(file.id)"
              >
                <span class="material-symbols-outlined text-lg">delete</span>
              </el-button>
            </el-tooltip>
          </el-button-group>
        </div>
      </div>
    </div>

    <!-- 空状态提示 -->
    <div v-else class="text-center py-8 text-gray-400 text-sm">
      <span class="material-symbols-outlined text-4xl mb-2">description</span>
      <p>暂无文件</p>
    </div>

    <!-- 上传按钮 -->
    <div v-if="hasPermission('files.upload')" class="mt-4">
      <el-button type="primary" @click="uploadDialogVisible = true" class="w-full">
        <span class="material-symbols-outlined mr-1 text-lg">upload</span>
        追加文件
      </el-button>
    </div>

    <!-- 上传弹窗 -->
    <el-dialog
      v-model="uploadDialogVisible"
      title="上传文件"
      width="500px"
      :close-on-click-modal="false"
      @closed="handleDialogClosed"
    >
      <div class="space-y-4">
        <el-upload
          ref="uploadRef"
          drag
          action="#"
          :auto-upload="false"
          :on-change="onFilePicked"
          :on-remove="onFileRemoved"
          :show-file-list="true"
          :limit="1"
          :file-list="fileList"
          accept=".epub,.pdf,.txt,.zip"
        >
          <div class="flex flex-col items-center justify-center py-8">
            <span class="material-symbols-outlined text-5xl text-gray-400 mb-2">upload_file</span>
            <div class="el-upload__text">
              拖拽文件到此处，或
              <em>点击选择</em>
            </div>
          </div>
          <template #tip>
            <div class="el-upload__tip text-center">
              支持 EPUB、PDF、TXT、ZIP 格式，单次限 1 个文件
            </div>
          </template>
        </el-upload>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="uploadDialogVisible = false">取消</el-button>
          <el-button
            type="primary"
            :disabled="!pickedFile"
            :loading="uploading"
            @click="handleUpload"
          >
            <span class="material-symbols-outlined mr-1 text-lg">cloud_upload</span>
            上传
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import type { FileRec } from '@/api/types'
import { filesApi } from '@/api/files'
import { importsApi } from '@/api/imports'
import { getDownloadUrl } from '@/utils/signedUrls'
import { usePermission } from '@/composables/usePermission'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { ElMessageBox, type UploadInstance, type UploadUserFile } from 'element-plus'

interface Props {
  bookId: number
  files: FileRec[]
}

interface Emits {
  (e: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const router = useRouter()
const { hasPermission } = usePermission()
const { handleError, handleSuccess } = useErrorHandler()

const uploadDialogVisible = ref(false)
const uploading = ref(false)
const pickedFile = ref<File | null>(null)
const uploadRef = ref<UploadInstance>()
const fileList = ref<UploadUserFile[]>([])

// 文件选择回调
function onFilePicked(file: any) {
  pickedFile.value = file?.raw ?? null
}

// 文件移除回调
function onFileRemoved() {
  pickedFile.value = null
}

// 处理上传
async function handleUpload() {
  if (!pickedFile.value) return

  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', pickedFile.value)
    fd.append('book_id', String(props.bookId))
    await importsApi.upload(fd)

    handleSuccess('上传成功')
    uploadDialogVisible.value = false
    emit('refresh')
  } catch (e: any) {
    handleError(e, { context: 'BookFileManager.upload' })
  } finally {
    uploading.value = false
  }
}

// 弹窗关闭后清理
function handleDialogClosed() {
  pickedFile.value = null
  fileList.value = []
  uploadRef.value?.clearFiles()
}

// 下载文件
async function handleDownload(fileId: number) {
  try {
    window.location.href = await getDownloadUrl(fileId)
  } catch (e: any) {
    handleError(e, { context: 'BookFileManager.download' })
  }
}

// 编辑章节
function handleEditChapters(fileId: number) {
  router.push({ name: 'admin-txt-chapters', params: { id: String(fileId) } })
}

// 删除文件
async function handleRemove(fileId: number) {
  try {
    await ElMessageBox.confirm('仅删除记录，不会删除物理文件。确认？', '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }

  try {
    await filesApi.remove(fileId)
    handleSuccess('已删除')
    emit('refresh')
  } catch (e: any) {
    handleError(e, { context: 'BookFileManager.remove' })
  }
}

// 获取文件类型样式类
function getFileTypeClass(format?: string): string {
  const fmt = (format || 'file').toLowerCase()
  const typeMap: Record<string, string> = {
    epub: 'bg-blue-100 text-blue-700',
    pdf: 'bg-red-100 text-red-700',
    txt: 'bg-green-100 text-green-700',
    zip: 'bg-yellow-100 text-yellow-700',
    cover: 'bg-purple-100 text-purple-700',
  }
  return typeMap[fmt] || 'bg-gray-100 text-gray-700'
}

// 获取文件名
function getFileName(file: FileRec): string {
  if (file.path) {
    const parts = file.path.split('/')
    return parts[parts.length - 1] || '未知文件'
  }
  return `${file.format || 'file'}.${file.id}`
}
</script>
