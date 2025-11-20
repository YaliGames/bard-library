import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { useSystemStore } from '@/stores/system'
import { usePermission } from '@/composables/usePermission'

// 扩展路由元信息类型
declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    permission?: string // 支持 | 分隔多个权限 (有任一即可)
    requireAllPermissions?: boolean // 是否需要所有权限
  }
}

const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: () => import('./pages/Home.vue') },
  {
    path: '/login',
    name: 'login',
    component: () => import('./pages/Login.vue'),
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('./pages/Register.vue'),
  },
  {
    path: '/forgot',
    name: 'forgot',
    component: () => import('./pages/ForgotPassword.vue'),
  },
  {
    path: '/reset',
    name: 'reset',
    component: () => import('./pages/ResetPassword.vue'),
  },
  {
    path: '/shelf',
    name: 'shelf',
    component: () => import('./pages/ShelfList.vue'),
  },
  {
    path: '/books',
    name: 'books',
    component: () => import('./pages/BookList.vue'),
  },
  {
    path: '/shelf/:id',
    name: 'shelf-detail',
    component: () => import('./pages/ShelfDetail.vue'),
  },
  {
    path: '/books/:id',
    name: 'book-detail',
    component: () => import('./pages/BookDetail.vue'),
  },
  {
    path: '/reader',
    children: [
      {
        path: 'txt/:id',
        name: 'reader-txt',
        component: () => import('./pages/Reader/TxtReader.vue'),
      },
      {
        path: 'pdf/:id',
        name: 'reader-pdf',
        component: () => import('./pages/Reader/PdfReader.vue'),
      },
      {
        path: 'epub/:id',
        name: 'reader-epub',
        component: () => import('./pages/Reader/EpubReader.vue'),
      },
    ],
  },
  {
    path: '/user',
    redirect: {
      name: 'user-profile',
    },
    children: [
      {
        path: 'profile',
        name: 'user-profile',
        component: () => import('./pages/Profile.vue'),
      },
      {
        path: 'settings',
        name: 'user-settings',
        component: () => import('./pages/UserSettings.vue'),
      },
    ],
  },
  {
    path: '/admin',
    redirect: {
      name: 'admin-index',
    },
    meta: { requiresAuth: true, permission: 'books.view|users.view|settings.view|files.view' },
    children: [
      {
        path: 'index',
        name: 'admin-index',
        component: () => import('./pages/Admin/Index.vue'),
        meta: { permission: 'books.view|users.view|settings.view|files.view' },
      },
      {
        path: 'upload',
        name: 'admin-upload',
        component: () => import('./pages/Admin/BookUpload.vue'),
        meta: { permission: 'files.upload', requireAllPermissions: true },
      },
      {
        path: 'books',
        name: 'admin-book-list',
        component: () => import('./pages/Admin/BookList.vue'),
        meta: { permission: 'books.view' },
      },
      {
        path: 'books/:id',
        name: 'admin-book-edit',
        component: () => import('./pages/Admin/BookEdit.vue'),
        meta: { permission: 'books.edit|books.create' },
      },
      {
        path: 'authors',
        name: 'admin-author-list',
        component: () => import('./pages/Admin/AuthorList.vue'),
        meta: { permission: 'authors.view' },
      },
      {
        path: 'tags',
        name: 'admin-tag-list',
        component: () => import('./pages/Admin/TagList.vue'),
        meta: { permission: 'tags.view' },
      },
      {
        path: 'series',
        name: 'admin-series-list',
        component: () => import('./pages/Admin/SeriesList.vue'),
        meta: { permission: 'series.view' },
      },
      {
        path: 'files',
        name: 'admin-file-manager',
        component: () => import('./pages/Admin/FileManager.vue'),
        meta: { permission: 'files.view' },
      },
      {
        path: 'settings',
        name: 'system-settings',
        component: () => import('./pages/Admin/SystemSettings.vue'),
        meta: { permission: 'settings.view' },
      },
      {
        path: 'users',
        name: 'admin-user-list',
        component: () => import('./pages/Admin/UserList.vue'),
        meta: { permission: 'users.view' },
      },
      {
        path: 'roles',
        name: 'admin-role-list',
        component: () => import('./pages/Admin/RoleList.vue'),
        meta: { permission: 'roles.view' },
      },
      {
        path: 'txt/chapters/:id?',
        name: 'admin-txt-chapters',
        component: () => import('./pages/Admin/TxtChapters.vue'),
        meta: { permission: 'books.edit' },
      },
      {
        path: 'scraping-tasks',
        name: 'admin-scraping-tasks',
        component: () => import('./pages/Admin/ScrapingTasks/Index.vue'),
        meta: { permission: 'metadata.batch_scrape' },
      },
      {
        path: 'scraping-tasks/create',
        name: 'admin-scraping-tasks-create',
        component: () => import('./pages/Admin/ScrapingTasks/Create.vue'),
        meta: { permission: 'metadata.batch_scrape' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
router.beforeEach(async to => {
  const authStore = useAuthStore()
  const systemStore = useSystemStore()

  // ✅ 直接读取 store 状态（已在 App.vue 中初始化）
  // 如果系统不允许游客访问,且用户未登录,则重定向到登录页
  if (!systemStore.allowGuestAccess && !authStore.isLoggedIn) {
    const publicRoutes = ['login', 'register', 'forgot', 'reset']
    if (!publicRoutes.includes(String(to.name))) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  // 检查是否需要登录
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // 检查权限
  if (to.meta.permission && authStore.isLoggedIn) {
    // 如果有缓存的权限,直接使用
    // 如果没有缓存,等待 NavBar 加载完成(最多等待 100ms)
    if (!authStore.permissionsLoaded && authStore.permissions.length === 0) {
      // 等待一小段时间让 NavBar 的 fetchUser 完成
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    const { hasAnyPermission, hasAllPermissions } = usePermission()
    const permissions = String(to.meta.permission).split('|')

    // 检查是否需要所有权限
    const hasPermission = to.meta.requireAllPermissions
      ? hasAllPermissions(permissions)
      : hasAnyPermission(permissions)

    if (!hasPermission) {
      ElMessage.error('无权限访问此页面')
      return { name: 'home' }
    }
  }

  // 若已登录仍访问登录页，重定向到书库
  if (to.name === 'login' && authStore.isLoggedIn) {
    return { name: 'books' }
  }
})

export default router
