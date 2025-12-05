<template>
  <div class="min-h-screen bg-gray-50/50 p-4 sm:p-6 lg:p-8">
    <!-- 头部区域：问候与搜索 -->
    <div class="mb-8 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">{{ greeting }}，管理员</h1>
        <p class="mt-1 text-sm text-gray-500">今天是 {{ currentDate }}，准备好管理图书馆了吗？</p>
      </div>

      <div class="w-full md:w-80">
        <el-input
          v-model="searchQuery"
          placeholder="搜索功能 / 描述..."
          clearable
          size="large"
          class="nav-search"
        >
          <template #prefix>
            <el-icon class="text-gray-400">
              <span class="material-symbols-outlined">search</span>
            </el-icon>
          </template>
        </el-input>
      </div>
    </div>

    <!-- 快捷访问区域 (仅当有收藏时显示) -->
    <transition name="el-fade-in">
      <div v-if="favoriteItems.length > 0 && !searchQuery" class="mb-8">
        <div class="mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined">link</span>
          <h2 class="text-lg font-medium text-gray-700">快捷访问</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
          <NavCard
            v-for="item in favoriteItems"
            :key="'fav-' + item.key"
            :item="item"
            is-favorite
            @toggle-fav="toggleFavorite"
          />
        </div>
      </div>
    </transition>

    <!-- 主功能区域 -->
    <div v-if="!searchQuery" class="space-y-8">
      <div v-for="(group, groupKey) in groupedPages" :key="groupKey">
        <!-- 分类标题 -->
        <div class="mb-4 flex items-center gap-2 border-l-4 border-blue-500 pl-3">
          <h2 class="text-lg font-bold text-gray-800">{{ categoryMap[groupKey] || groupKey }}</h2>
          <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
            {{ group.length }}
          </span>
        </div>

        <!-- 卡片网格 -->
        <div
          class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
        >
          <NavCard
            v-for="item in group"
            :key="item.key"
            :item="item"
            :is-favorite="favorites.includes(item.key)"
            @toggle-fav="toggleFavorite"
          />
        </div>
      </div>
    </div>

    <!-- 搜索结果显示 -->
    <div v-else>
      <div class="mb-4 text-sm text-gray-500">搜索结果：共 {{ searchResults.length }} 项</div>
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <NavCard
          v-for="item in searchResults"
          :key="item.key"
          :item="item"
          :is-favorite="favorites.includes(item.key)"
          @toggle-fav="toggleFavorite"
        />
      </div>
      <el-empty
        v-if="searchResults.length === 0"
        description="没有找到相关功能，换个关键词试试？"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import dayjs from 'dayjs'
import NavCard, { type AdminCard } from '@/components/Admin/NavCard.vue'

const allPages: AdminCard[] = [
  // 图书管理 - Create
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
  // 图书管理 - Manage
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

const categoryMap: Record<string, string> = {
  createBook: '内容生产',
  books: '馆藏管理',
  fields: '元数据维护',
  users: '人员与权限',
  system: '系统配置',
}

const searchQuery = ref('')
const favorites = ref<string[]>([])

onMounted(() => {
  const saved = localStorage.getItem('admin-nav-favorites')
  if (saved) {
    favorites.value = JSON.parse(saved)
  }
})

watch(
  favorites,
  val => {
    localStorage.setItem('admin-nav-favorites', JSON.stringify(val))
  },
  { deep: true },
)

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 6) return '夜深了'
  if (hour < 12) return '早上好'
  if (hour < 14) return '中午好'
  if (hour < 18) return '下午好'
  return '晚上好'
})

const currentDate = dayjs().format('YYYY年MM月DD日')

const toggleFavorite = (key: string) => {
  const index = favorites.value.indexOf(key)
  if (index > -1) {
    favorites.value.splice(index, 1)
  } else {
    favorites.value.push(key)
  }
}

const favoriteItems = computed(() => {
  return allPages.filter(p => favorites.value.includes(p.key))
})

const searchResults = computed(() => {
  if (!searchQuery.value) return []
  const query = searchQuery.value.toLowerCase()
  return allPages.filter(
    p => p.title.toLowerCase().includes(query) || p.desc.toLowerCase().includes(query),
  )
})

const groupedPages = computed(() => {
  const groups: Record<string, AdminCard[]> = {}
  const orderedKeys = Object.keys(categoryMap)

  orderedKeys.forEach(key => {
    groups[key] = []
  })

  allPages.forEach(page => {
    if (!groups[page.category]) groups[page.category] = []
    groups[page.category].push(page)
  })

  Object.keys(groups).forEach(key => {
    if (groups[key].length === 0) delete groups[key]
  })

  return groups
})
</script>

<style scoped>
:deep(.nav-search .el-input__wrapper) {
  border-radius: 9999px; /* full rounded */
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  padding-left: 1.25rem;
  padding-right: 1.25rem;
  background-color: white;
}
:deep(.nav-search .el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px #3b82f6; /* blue-500 */
}
</style>
