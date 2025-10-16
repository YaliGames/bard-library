<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">快速上传图书</h2>
      <div class="flex items-center">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回
        </el-button>
      </div>
    </div>

    <el-alert type="info" show-icon :closable="false" class="mb-4"
      description="支持上传 EPUB/PDF/TXT/ZIP 等格式，系统会自动解析生成图书记录。" />

    <el-card shadow="never">
      <el-form label-width="100px" @submit.prevent>
        <el-form-item label="文件">
          <el-upload drag :auto-upload="false" :on-change="onFileChange" :show-file-list="true" :limit="1"
            accept=".epub,.pdf,.txt,.zip">
            <i class="el-icon--upload" />
            <div class="el-upload__text">
              拖拽文件到此处，或 <em>点击选择</em>
            </div>
            <template #tip>
              <div class="el-upload__tip">单次仅限 1 个文件</div>
            </template>
          </el-upload>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="uploading" :disabled="!file" @click="submit">
            {{ uploading ? '上传中…' : '开始上传' }}
          </el-button>
          <el-button @click="reset">重置</el-button>
        </el-form-item>
      </el-form>

      <div v-if="result" class="mt-4">
        <el-alert v-if="result.error" type="error" show-icon :title="result.error" class="mb-3" />
        <template v-else>
          <el-result icon="success" title="上传完成" :sub-title="result.duplicate ? '文件已存在，已定位到原书籍' : '已成功创建/更新图书'">
            <template #extra>
              <router-link v-if="result.book?.id" :to="{ name: 'book-detail', params: { id: result.book.id } }">
                <el-button type="primary">查看详情</el-button>
              </router-link>
            </template>
          </el-result>
          <el-descriptions v-if="result.book" :column="1" border class="mt-3">
            <el-descriptions-item label="书名">{{ result.book?.title || ('#' + result.book?.id) }}</el-descriptions-item>
            <el-descriptions-item label="ID">{{ result.book?.id }}</el-descriptions-item>
          </el-descriptions>
        </template>
      </div>
    </el-card>
  </section>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { importsApi } from '@/api/imports'
import { useRoute, useRouter } from 'vue-router'

const file = ref<File | null>(null)
const uploading = ref(false)
const result = ref<any>(null)
const router = useRouter()

function back() { router.push({ name: 'admin-book-list' }) }

function onFileChange(fileObj: any) {
  // el-upload on-change 回调返回 UploadFile 对象
  const raw = fileObj?.raw as File | undefined
  file.value = raw || null
}

function reset() { file.value = null; result.value = null }

async function submit() {
  if (!file.value) return
  uploading.value = true
  result.value = null
  try {
    const fd = new FormData()
    fd.append('file', file.value)
    const data = await importsApi.upload(fd)
    result.value = data
  } catch (err: any) {
    result.value = { error: err.message || String(err) }
  } finally {
    uploading.value = false
  }
}
</script>
