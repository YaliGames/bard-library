<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <h2 class="text-xl font-semibold mb-3">管理中心</h2>
    <p class="text-gray-600 mb-4">按功能分类，快速抵达你要去的地方。</p>

    <!-- 常用功能 -->
    <div v-if="favoritePages.length > 0" class="mb-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium">常用功能</h3>
        <el-button v-if="favoritePages.length > 0" text size="small" @click="clearAllFavorites">
          清空全部
        </el-button>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="page in favoritePages" :key="page.key" class="relative">
          <router-link class="block" :to="page.to">
            <el-card shadow="hover" class="h-full">
              <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-3xl text-primary">{{ page.icon }}</span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium mb-1">{{ page.title }}</div>
                  <div class="text-sm text-gray-500">{{ page.desc }}</div>
                </div>
              </div>
            </el-card>
          </router-link>
          <el-button
            class="absolute top-2 right-2"
            size="small"
            circle
            @click.prevent="toggleFavorite(page.key)"
          >
            <span
              class="material-symbols-outlined text-yellow-500"
              style="font-variation-settings: 'FILL' 1"
            >
              star
            </span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 分类页面 -->
    <div v-for="category in categories" :key="category.key" class="mb-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium">{{ category.title }}</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="page in category.pages" :key="page.key" class="relative">
          <router-link class="block" :to="page.to">
            <el-card shadow="hover" class="h-full">
              <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-3xl text-primary">{{ page.icon }}</span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium mb-1">{{ page.title }}</div>
                  <div class="text-sm text-gray-500">{{ page.desc }}</div>
                </div>
              </div>
            </el-card>
          </router-link>
          <el-button
            class="absolute top-2 right-2"
            size="small"
            circle
            @click.prevent="toggleFavorite(page.key)"
          >
            <span
              class="material-symbols-outlined"
              :class="isFavorite(page.key) ? 'text-yellow-500' : 'text-gray-400'"
              :style="isFavorite(page.key) ? 'font-variation-settings: \'FILL\' 1' : ''"
            >
              star
            </span>
          </el-button>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import { usePermission } from '@/composables/usePermission'
import { ElMessageBox } from 'element-plus'

const { hasPermission } = usePermission()

interface AdminCard {
  key: string
  title: string
  desc: string
  icon: string
  to: RouteLocationRaw
  permission?: string
  category: 'createBook' | 'books' | 'fields' | 'users' | 'system'
}

const allPages: AdminCard[] = [
  // 图书管理
  {
    key: 'create-book',
    title: '新建图书',
    desc: '从空白开始新建图书',
    icon: 'note_add',
    to: { name: 'admin-book-edit', params: { id: 'new' } },
    permission: 'books.create',
    category: 'createBook',
  },
  {
    key: 'quick-scrape',
    title: '快速刮削',
    desc: '批量从豆瓣等平台搜索并导入图书元数据',
    icon: 'cloud_download',
    to: { name: 'admin-scraping-tasks-create' },
    permission: 'metadata.batch_scrape',
    category: 'createBook',
  },
  {
    key: 'quick-upload',
    title: '快速上传图书',
    desc: '上传文件并直接导入为新的图书',
    icon: 'upload',
    to: { name: 'admin-upload' },
    permission: 'files.upload',
    category: 'createBook',
  },
  // 图书管理
  {
    key: 'books-all',
    title: '图书列表',
    desc: '浏览、编辑、删除图书，支持筛选查看',
    icon: 'menu_book',
    to: { name: 'admin-book-list' },
    permission: 'books.view',
    category: 'books',
  },
  {
    key: 'files-all',
    title: '文件管理',
    desc: '浏览数据库中保存的文件列表,支持文件清理',
    icon: 'folder_open',
    to: { name: 'admin-file-manager' },
    permission: 'files.view',
    category: 'books',
  },
  {
    key: 'txt',
    title: 'TXT 章节管理',
    desc: '解析 TXT 并调整章节结构',
    icon: 'subject',
    to: { name: 'admin-txt-chapters' },
    permission: 'books.edit',
    category: 'books',
  },
  {
    key: 'scraping-tasks',
    title: '刮削任务',
    desc: '查看和管理批量刮削任务',
    icon: 'task',
    to: { name: 'admin-scraping-tasks' },
    permission: 'metadata.batch_scrape',
    category: 'books',
  },

  // 字段管理
  {
    key: 'authors',
    title: '作者管理',
    desc: '维护作者列表',
    icon: 'person_edit',
    to: { name: 'admin-author-list' },
    permission: 'authors.view',
    category: 'fields',
  },
  {
    key: 'tags',
    title: '标签管理',
    desc: '维护标签列表',
    icon: 'sell',
    to: { name: 'admin-tag-list' },
    permission: 'tags.view',
    category: 'fields',
  },
  {
    key: 'series',
    title: '丛书管理',
    desc: '为系列图书建模与排序',
    icon: 'collections_bookmark',
    to: { name: 'admin-series-list' },
    permission: 'series.view',
    category: 'fields',
  },

  // 用户管理
  {
    key: 'users',
    title: '用户管理',
    desc: '管理系统用户,分配角色和权限',
    icon: 'people',
    to: { name: 'admin-user-list' },
    permission: 'users.view',
    category: 'users',
  },
  {
    key: 'roles',
    title: '角色管理',
    desc: '管理角色和权限配置',
    icon: 'admin_panel_settings',
    to: { name: 'admin-role-list' },
    permission: 'roles.view',
    category: 'users',
  },

  // 系统设置
  {
    key: 'sys',
    title: '系统设置',
    desc: '系统级参数配置,仅管理员可用',
    icon: 'settings',
    to: { name: 'system-settings' },
    permission: 'settings.view',
    category: 'system',
  },
]

// 收藏状态（存储在 localStorage）
const STORAGE_KEY = 'admin-favorites'
const favorites = ref<string[]>([])

// 加载收藏
function loadFavorites() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    favorites.value = stored ? JSON.parse(stored) : []
  } catch {
    favorites.value = []
  }
}

// 保存收藏
function saveFavorites() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(favorites.value))
}

// 判断是否收藏
function isFavorite(key: string) {
  return favorites.value.includes(key)
}

// 切换收藏
function toggleFavorite(key: string) {
  if (isFavorite(key)) {
    favorites.value = favorites.value.filter(k => k !== key)
  } else {
    favorites.value.push(key)
  }
  saveFavorites()
}

// 清空所有收藏
async function clearAllFavorites() {
  try {
    await ElMessageBox.confirm('确认清空所有常用功能？', '清空确认', {
      type: 'warning',
      confirmButtonText: '清空',
      cancelButtonText: '取消',
    })
    favorites.value = []
    saveFavorites()
  } catch {
    // 用户取消
  }
}

// 过滤有权限的页面
const visiblePages = computed(() => {
  return allPages.filter(page => {
    return page.permission ? hasPermission(page.permission) : true
  })
})

// 收藏的页面
const favoritePages = computed(() => {
  return visiblePages.value.filter(page => isFavorite(page.key))
})

// 分类定义
interface Category {
  key: string
  title: string
  pages: AdminCard[]
}

const categories = computed<Category[]>(() => {
  const categoryMap = {
    createBook: { key: 'createBook', title: '创建图书', pages: [] as AdminCard[] },
    books: { key: 'books', title: '图书管理', pages: [] as AdminCard[] },
    fields: { key: 'fields', title: '字段管理', pages: [] as AdminCard[] },
    users: { key: 'users', title: '用户管理', pages: [] as AdminCard[] },
    system: { key: 'system', title: '系统设置', pages: [] as AdminCard[] },
  }

  visiblePages.value.forEach(page => {
    categoryMap[page.category].pages.push(page)
  })

  return Object.values(categoryMap).filter(cat => cat.pages.length > 0)
})

onMounted(() => {
  loadFavorites()
})
</script>
