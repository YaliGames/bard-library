import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSystemStore } from '@/stores/system'

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
    children: [
      {
        path: 'index',
        name: 'admin-index',
        component: () => import('./pages/Admin/Index.vue'),
      },
      {
        path: 'upload',
        name: 'admin-upload',
        component: () => import('./pages/Admin/BookUpload.vue'),
      },
      {
        path: 'books',
        name: 'admin-book-list',
        component: () => import('./pages/Admin/BookList.vue'),
      },
      {
        path: 'books/:id',
        name: 'admin-book-edit',
        component: () => import('./pages/Admin/BookEdit.vue'),
      },
      {
        path: 'authors',
        name: 'admin-author-list',
        component: () => import('./pages/Admin/AuthorList.vue'),
      },
      {
        path: 'tags',
        name: 'admin-tag-list',
        component: () => import('./pages/Admin/TagList.vue'),
      },
      {
        path: 'shelves',
        name: 'admin-shelf-list',
        component: () => import('./pages/Admin/ShelfList.vue'),
      },
      {
        path: 'series',
        name: 'admin-series-list',
        component: () => import('./pages/Admin/SeriesList.vue'),
      },
      {
        path: 'files',
        name: 'admin-file-manager',
        component: () => import('./pages/Admin/FileManager.vue'),
      },
      {
        path: 'settings',
        name: 'system-settings',
        component: () => import('./pages/Admin/SystemSettings.vue'),
      },
      {
        path: 'txt/chapters/:id?',
        name: 'admin-txt-chapters',
        component: () => import('./pages/Admin/TxtChapters.vue'),
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
  // 如果系统不允许游客访问，且用户未登录，则重定向到登录页
  if (!systemStore.allowGuestAccess && !authStore.isLoggedIn) {
    const publicRoutes = ['login', 'register', 'forgot', 'reset']
    if (!publicRoutes.includes(String(to.name))) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  // 管理员路由检查
  const isAdminRoute = to.path.startsWith('/admin')

  // 未登录禁止访问 /admin
  if (isAdminRoute && !authStore.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // 管理路由：要求 admin 角色
  if (isAdminRoute && authStore.isLoggedIn) {
    if (!authStore.isRole('admin')) {
      return { name: 'home' }
    }
  }

  // 若已登录仍访问登录页，重定向到书库
  if (to.name === 'login' && authStore.isLoggedIn) {
    return { name: 'books' }
  }
})

export default router
