<template>
  <div class="h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">picture_as_pdf</span>
      <h1 class="text-2xl font-bold text-gray-900 mb-2">PDF 阅读器</h1>
      <p class="text-gray-600 mb-4">PDF 阅读器功能开发中...</p>
      <el-button @click="goBack" type="primary">
        <span class="material-symbols-outlined mr-1">arrow_back</span>
        返回
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goBack() {
  router.go(-1)
}
</script>