<template>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div
      class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50"
    >
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-600">dashboard</span>
          控制台导航
        </h2>
      </div>

      <div class="w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0 no-scrollbar">
        <div class="flex gap-2 min-w-max">
          <button
            v-for="cat in categories"
            :key="cat.key"
            @click="currentCategory = cat.key"
            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all border select-none whitespace-nowrap"
            :class="
              currentCategory === cat.key
                ? 'bg-white border-blue-200 text-blue-600 shadow-sm'
                : 'bg-slate-100 border-transparent text-slate-500 hover:bg-slate-200'
            "
          >
            {{ cat.label }}
          </button>
        </div>
      </div>
    </div>

    <div class="p-5">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="item in filteredPages"
          :key="item.key"
          @click="handleNavClick(item)"
          class="group relative flex flex-col items-center justify-center p-4 rounded-xl border border-slate-100 bg-slate-50/30 hover:bg-blue-50 hover:border-blue-100 cursor-pointer transition-all duration-200"
        >
          <div
            @click.stop="toggleFavorite(item.key)"
            class="absolute top-2 right-2 p-1 rounded-full transition-all hover:bg-slate-100"
            :class="
              isFavorite(item.key)
                ? 'text-yellow-400'
                : 'text-slate-300 opacity-0 group-hover:opacity-100'
            "
          >
            <span
              class="material-symbols-outlined text-[20px]"
              :style="isFavorite(item.key) ? 'font-variation-settings: \'FILL\' 1' : ''"
            >
              star
            </span>
          </div>

          <span
            class="material-symbols-outlined text-[32px] mb-2 text-slate-500 group-hover:text-blue-600 transition-colors duration-300"
          >
            {{ item.icon }}
          </span>

          <span class="text-sm font-medium text-slate-700 group-hover:text-blue-700 text-center">
            {{ item.title }}
          </span>

          <span
            class="text-[10px] text-slate-400 mt-2 text-center line-clamp-2 px-1 leading-tight group-hover:text-slate-500"
          >
            {{ item.desc }}
          </span>
        </div>

        <div
          class="flex flex-col items-center justify-center p-4 rounded-xl border border-dashed border-slate-200 text-slate-400 hover:border-slate-400 hover:text-slate-600 cursor-pointer transition-colors"
        >
          <span
            class="material-symbols-outlined text-[32px] mb-2 text-slate-500 group-hover:text-blue-600 transition-colors duration-300"
          >
            add
          </span>
          <span class="text-xs">添加快捷方式</span>
        </div>

        <div
          v-if="filteredPages.length === 0"
          class="col-span-full py-8 text-center text-slate-400"
        >
          <span class="material-symbols-outlined text-4xl mb-2">lock_person</span>
          <p class="text-xs">暂无该分类下的权限</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePermission } from '@/composables/usePermission'

interface AdminCard {
  key: string
  title: string
  desc: string
  icon: string
  to: { name: string; params?: any }
  permission?: string
  category: 'createBook' | 'books' | 'fields' | 'users' | 'system'
}

const router = useRouter()
const { hasPermission } = usePermission()

const currentCategory = ref('all')
const favorites = ref<string[]>([])
const STORAGE_KEY = 'admin-favorites'

const categories = [
  { key: 'all', label: '全部功能' },
  { key: 'createBook', label: '内容生产' },
  { key: 'books', label: '图书管理' },
  { key: 'fields', label: '字段管理' },
  { key: 'users', label: '用户管理' },
  { key: 'system', label: '系统设置' },
]

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
  // 图书管理 (列表)
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

const visiblePages = computed(() => {
  return allPages.filter(page => {
    return page.permission ? hasPermission(page.permission) : true
  })
})

const filteredPages = computed(() => {
  if (currentCategory.value === 'all') {
    return [...visiblePages.value].sort((a, b) => {
      const aFav = isFavorite(a.key) ? 1 : 0
      const bFav = isFavorite(b.key) ? 1 : 0
      return bFav - aFav
    })
  }
  return visiblePages.value.filter(page => page.category === currentCategory.value)
})

function loadFavorites() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    favorites.value = stored ? JSON.parse(stored) : []
  } catch {
    favorites.value = []
  }
}

function saveFavorites() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(favorites.value))
}

function isFavorite(key: string) {
  return favorites.value.includes(key)
}

function toggleFavorite(key: string) {
  if (isFavorite(key)) {
    favorites.value = favorites.value.filter(k => k !== key)
  } else {
    favorites.value.push(key)
  }
  saveFavorites()
}

const handleNavClick = (item: AdminCard) => {
  if (item.to) router.push(item.to)
}

onMounted(() => {
  loadFavorites()
})
</script>
