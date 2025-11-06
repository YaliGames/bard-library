<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div v-for="s in items" :key="s.id" class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <router-link :to="`/shelf/${s.id}`">
        <div class="flex items-start space-x-4">
          <div class="w-40 h-20 bg-gray-50 rounded-lg overflow-hidden">
            <img
              src="/src/images/shelf-placeholder.png"
              alt="shelf"
              class="w-full h-full object-cover"
            />
          </div>
          <div class="flex-1">
            <div class="flex items-start justify-between">
              <h3 class="text-lg font-semibold mb-1">
                {{ s.name }}
              </h3>
              <div class="flex items-center gap-1">
                <el-tag v-if="s.is_public" type="success" size="small">公开</el-tag>
                <el-tag v-else size="small">私有</el-tag>
              </div>
            </div>
            <p class="text-gray-600 text-sm mb-2">{{ s.description || '暂无简介' }}</p>
            <p v-if="(s as any).user && (s as any).user.name" class="text-gray-400 text-xs">
              创建者：{{ (s as any).user.name }}
            </p>
          </div>
        </div>
      </router-link>
      <div v-if="s.books && s.books.length === 0" class="text-gray-500 mt-5">该书架暂无图书</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
        <router-link v-for="b in s.books || []" :key="b.id" :to="`/books/${b.id}`">
          <el-button type="primary" class="justify-start w-full">
            <span class="material-symbols-outlined mr-2">book</span>
            《{{ b.title }}》
          </el-button>
        </router-link>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import type { Book, Shelf } from '@/api/types'

defineProps<{ items: (Shelf & { books?: Book[] })[]; canManage?: boolean }>()
</script>
<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  /* optional standard */
  line-clamp: 2;
}
</style>
