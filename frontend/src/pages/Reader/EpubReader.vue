<template>
  <section class="flex flex-col h-[100vh]">
    <!-- 顶部栏 -->
    <div
      class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-[var(--el-bg-color-overlay)]"
    >
      <div class="flex items-center gap-2">
        <router-link to="/books">
          <el-button>
            <span class="material-symbols-outlined mr-1">arrow_back</span>
            返回书库
          </el-button>
        </router-link>
        <h1 class="m-0 text-base font-semibold text-gray-800 dark:text-gray-200">
          EPUB 在线阅读（占位）
        </h1>
      </div>
      <div class="flex items-center gap-2">
        <el-button type="primary" @click="download">
          <span class="material-symbols-outlined mr-1">download</span>
          下载原文件
        </el-button>
      </div>
    </div>

    <!-- 预览区：暂未实现，展示提示与下载入口 -->
    <div class="flex-1 min-h-0 flex items-center justify-center bg-gray-50 dark:bg-[#111]">
      <div class="text-center text-gray-700 dark:text-gray-300">
        <div class="text-2xl font-semibold mb-2">EPUB 阅读器正在开发中</div>
        <div class="mb-4">您可以先下载到本地或使用外部阅读器打开。</div>
        <el-button type="primary" @click="download">
          <span class="material-symbols-outlined mr-1">download</span>
          下载原文件
        </el-button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { getDownloadUrl } from '@/utils/signedUrls'

const route = useRoute()
const fileId = computed(() => Number(route.params.id))

async function download() {
  if (!fileId.value) return
  try {
    window.location.href = await getDownloadUrl(fileId.value)
  } catch {}
}
</script>
