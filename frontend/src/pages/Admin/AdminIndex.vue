<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <h2 class="text-xl font-semibold mb-3">管理导航</h2>
    <p class="text-gray-600 mb-4">选择一个管理功能进入对应页面。</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <router-link
        v-for="card in cards"
        :key="card.key"
        class="block"
        :to="card.to"
      >
        <el-card shadow="hover" class="h-full">
          <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-3xl text-primary">{{ card.icon }}</span>
            <div class="min-w-0">
              <div class="font-medium mb-1">{{ card.title }}</div>
              <div class="text-sm text-gray-500">{{ card.desc }}</div>
            </div>
          </div>
        </el-card>
      </router-link>
    </div>
  </section>
</template>
<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

interface AdminCard { key: string; title: string; desc: string; icon: string; to: RouteLocationRaw }

const cards: AdminCard[] = [
  { key: 'books', title: '图书管理', desc: '图书新增、编辑、删除等基础操作', icon: 'book_2', to: { name: 'admin-book-list' } },
  { key: 'authors', title: '作者管理', desc: '作者信息为图书的可选属性，管理作者列表', icon: 'person_edit', to: { name: 'admin-author-list' } },
  { key: 'tags', title: '标签管理', desc: '标签为图书的可选属性，管理标签列表', icon: 'sell', to: { name: 'admin-tag-list' } },
  { key: 'shelves', title: '书架管理', desc: '书架用于对图书进行分类，管理书架列表', icon: 'newsstand', to: { name: 'admin-shelf-list' } },
  { key: 'series', title: '丛书管理', desc: '丛书为图书的可选属性，当图书属于某个系列时，可以进行归类', icon: 'collections_bookmark', to: { name: 'admin-series-list' } },
  { key: 'files', title: '文件管理', desc: '查看、下载、删除文件；清理未引用封面/悬挂记录', icon: 'folder_managed', to: { name: 'admin-file-manager' } },
  { key: 'txt', title: 'TXT 章节管理', desc: '当图书包含TXT附件时，可以解析与调整章节', icon: 'subject', to: { name: 'admin-txt-chapters', params: { id: '' } } },
]
</script>
