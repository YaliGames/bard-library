<template>
  <section class="flex flex-col h-[100vh]">
    <!-- 顶部栏 -->
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-[var(--el-bg-color-overlay)]">
      <div class="flex items-center gap-2">
        <router-link to="/books">
          <el-button>
            <span class="material-symbols-outlined mr-1">arrow_back</span>
            返回书库
          </el-button>
        </router-link>
        <h1 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">PDF 在线预览（占位）</h1>
      </div>
      <div class="flex items-center gap-2">
        <el-button type="primary" @click="download">
          <span class="material-symbols-outlined mr-1">download</span>
          下载原文件
        </el-button>
      </div>
    </div>

    <!-- 预览区：浏览器支持的情况下直接内联显示 -->
    <div class="flex-1 min-h-0 bg-gray-50 dark:bg-[#111]">
      <iframe :src="previewUrl" class="w-full h-full border-0" title="PDF 预览"></iframe>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const fileId = computed(() => Number(route.params.id))
const previewUrl = computed(() => `/api/v1/files/${fileId.value}/preview`)

function download() {
  if (!fileId.value) return
  try { window.location.href = `/api/v1/files/${fileId.value}/download` } catch {}
}
</script>
