<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <h2 class="text-xl font-semibold mb-3">管理中心</h2>
    <p class="text-gray-600 mb-4">按常用功能与所有页面分类，快速抵达你要去的地方。</p>

    <div class="mb-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium">常用功能</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <router-link
          v-for="action in visibleQuickActions"
          :key="action.key"
          class="block"
          :to="action.to"
        >
          <el-card shadow="hover" class="h-full">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-3xl text-primary">{{ action.icon }}</span>
              <div class="min-w-0">
                <div class="font-medium mb-1">{{ action.title }}</div>
                <div class="text-sm text-gray-500">{{ action.desc }}</div>
              </div>
            </div>
          </el-card>
        </router-link>
      </div>
    </div>

    <div>
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium">所有页面</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <router-link v-for="card in visiblePages" :key="card.key" class="block" :to="card.to">
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
    </div>
  </section>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import { usePermission } from '@/composables/usePermission'

const { hasPermission } = usePermission()

interface AdminCard {
  key: string
  title: string
  desc: string
  icon: string
  to: RouteLocationRaw
  permission?: string
}

const quickActions: AdminCard[] = [
  {
    key: 'quick-upload',
    title: '快速上传图书',
    desc: '上传文件并直接导入为新的图书',
    icon: 'upload',
    to: { name: 'admin-upload' },
    permission: 'files.upload',
  },
  {
    key: 'quick-scrape',
    title: '快速刮削',
    desc: '批量从豆瓣等平台搜索并导入图书元数据',
    icon: 'cloud_download',
    to: { name: 'admin-scraping-tasks-create' },
    permission: 'metadata.batch_scrape',
  },
  {
    key: 'create-book',
    title: '新建图书',
    desc: '从空白开始新建图书',
    icon: 'note_add',
    to: { name: 'admin-book-edit', params: { id: 'new' } },
    permission: 'books.create',
  },
]

const allPages: AdminCard[] = [
  {
    key: 'books-all',
    title: '图书管理',
    desc: '新增、编辑、删除图书，支持筛选查看',
    icon: 'menu_book',
    to: { name: 'admin-book-list' },
    permission: 'books.view',
  },
  {
    key: 'files-all',
    title: '文件管理',
    desc: '浏览数据库中保存的文件列表,支持文件清理',
    icon: 'folder_open',
    to: { name: 'admin-file-manager' },
    permission: 'files.view',
  },
  {
    key: 'authors',
    title: '作者管理',
    desc: '维护作者列表',
    icon: 'person_edit',
    to: { name: 'admin-author-list' },
    permission: 'authors.view',
  },
  {
    key: 'tags',
    title: '标签管理',
    desc: '维护标签列表',
    icon: 'sell',
    to: { name: 'admin-tag-list' },
    permission: 'tags.view',
  },
  {
    key: 'series',
    title: '丛书管理',
    desc: '为系列图书建模与排序',
    icon: 'collections_bookmark',
    to: { name: 'admin-series-list' },
    permission: 'series.view',
  },
  {
    key: 'txt',
    title: 'TXT 章节管理',
    desc: '解析 TXT 并调整章节结构',
    icon: 'subject',
    to: { name: 'admin-txt-chapters' },
    permission: 'books.edit',
  },
  {
    key: 'users',
    title: '用户管理',
    desc: '管理系统用户,分配角色和权限',
    icon: 'people',
    to: { name: 'admin-user-list' },
    permission: 'users.view',
  },
  {
    key: 'roles',
    title: '角色管理',
    desc: '管理角色和权限配置',
    icon: 'admin_panel_settings',
    to: { name: 'admin-role-list' },
    permission: 'roles.view',
  },
  {
    key: 'sys',
    title: '系统设置',
    desc: '系统级参数配置,仅管理员可用',
    icon: 'settings',
    to: { name: 'system-settings' },
    permission: 'settings.view',
  },
]

// 过滤出有权限的卡片
const visibleQuickActions = computed(() => {
  return quickActions.filter(action => {
    return action.permission ? hasPermission(action.permission) : true
  })
})

const visiblePages = computed(() => {
  return allPages.filter(page => {
    return page.permission ? hasPermission(page.permission) : true
  })
})
</script>
