# BardLibrary Frontend

BardLibrary 是一个现代化的个人图书馆管理系统前端应用，基于 Vue 3 + TypeScript + Vite 构建，提供图书管理、阅读、元数据编辑等完整功能。

## 📋 目录

- [技术栈](#技术栈)
- [项目结构](#项目结构)
- [快速开始](#快速开始)
- [开发规范](#开发规范)
- [核心功能](#核心功能)
- [项目配置](#项目配置)

## 🛠️ 技术栈

### 核心框架
- **Vue 3** - 渐进式 JavaScript 框架
- **TypeScript** - 类型安全的 JavaScript 超集
- **Vite** - 下一代前端构建工具
- **Vue Router** - 官方路由管理器
- **Pinia** - 轻量级状态管理

### UI 框架
- **Element Plus** - 桌面端组件库
- **Tailwind CSS** - 实用优先的 CSS 框架
- **Material Symbols** - Google Material 图标

### 工具库
- **Axios** - HTTP 客户端
- **PDF.js** - PDF 文档渲染

### 代码质量
- **ESLint** - 代码检查工具
- **Prettier** - 代码格式化工具
- **TypeScript ESLint** - TypeScript 代码规范

## 📁 项目结构

```
frontend/
├── public/                    # 静态资源
│   └── pdf.worker.js         # PDF.js Worker 文件
│
├── src/                       # 源代码目录
│   ├── api/                  # API 接口层
│   │   ├── http.ts           # HTTP 客户端封装
│   │   ├── types.ts          # 共享类型定义
│   │   ├── index.ts          # 统一导出入口
│   │   ├── auth.ts           # 认证 API
│   │   ├── books.ts          # 图书 API
│   │   ├── authors.ts        # 作者 API
│   │   ├── tags.ts           # 标签 API
│   │   ├── series.ts         # 系列 API
│   │   ├── shelves.ts        # 书架 API
│   │   ├── files.ts          # 文件管理 API
│   │   ├── covers.ts         # 封面管理 API
│   │   ├── metadata.ts       # 元数据 API
│   │   ├── progress.ts       # 阅读进度 API
│   │   ├── bookmarks.ts      # 书签 API
│   │   ├── settings.ts       # 用户设置 API
│   │   └── systemSettings.ts # 系统设置 API
│   │
│   ├── components/           # 组件目录
│   │   ├── Admin/            # 管理后台组件
│   │   ├── Book/             # 图书相关组件
│   │   ├── Metadata/         # 元数据编辑组件
│   │   ├── Reader/           # 阅读器组件
│   │   ├── Settings/         # 设置页面组件
│   │   ├── Shelf/            # 书架组件
│   │   └── NavBar.vue        # 导航栏
│   │
│   ├── composables/          # 组合式函数
│   │   ├── useErrorHandler.ts   # 错误处理
│   │   ├── usePagination.ts     # 分页逻辑
│   │   ├── useLoading.ts        # 加载状态管理
│   │   ├── useBookActions.ts    # 图书操作
│   │   └── usePermission.ts     # 权限检查
│   │
│   ├── directives/           # 自定义指令
│   │   └── permission.ts     # 权限指令 (v-permission, v-role, v-any-permission)
│   │
│   ├── stores/               # Pinia 状态管理
│   │   ├── auth.ts           # 认证状态
│   │   ├── settings.ts       # 用户设置
│   │   └── system.ts         # 系统状态
│   │
│   ├── pages/                # 页面组件
│   │   ├── Admin/            # 管理后台页面
│   │   │   ├── UserList.vue        # 用户管理
│   │   │   ├── RoleList.vue        # 角色管理
│   │   │   ├── BookList.vue        # 图书管理
│   │   │   ├── BookEdit.vue        # 图书编辑
│   │   │   ├── FileManager.vue     # 文件管理
│   │   │   ├── TxtChapters.vue     # TXT章节管理
│   │   │   └── SystemSettings.vue  # 系统设置
│   │   ├── Reader/           # 阅读器页面
│   │   │   ├── PdfReader.vue       # PDF 阅读器
│   │   │   ├── EpubReader.vue      # EPUB 阅读器
│   │   │   └── TxtReader.vue       # TXT 阅读器
│   │   ├── Home.vue          # 首页
│   │   ├── Login.vue         # 登录
│   │   ├── Register.vue      # 注册
│   │   ├── BookList.vue      # 图书列表
│   │   ├── BookDetail.vue    # 图书详情
│   │   ├── ShelfList.vue     # 书架列表
│   │   ├── ShelfDetail.vue   # 书架详情
│   │   ├── Profile.vue       # 个人资料
│   │   └── UserSettings.vue  # 用户设置
│   │
│   ├── types/                # TypeScript 类型定义
│   │   └── metadata.ts       # 元数据类型
│   │
│   ├── utils/                # 工具函数
│   │   ├── reader.ts         # 阅读器工具
│   │   ├── signedUrls.ts     # 签名 URL 处理
│   │   ├── publicSettings.ts # 公共设置工具
│   │   └── systemSettings.ts # 系统设置工具
│   │
│   ├── config/               # 配置文件
│   │   ├── navMenu.ts        # 导航菜单配置
│   │   └── regexPresets.ts   # 正则表达式预设
│   │
│   ├── styles/               # 全局样式
│   │   ├── reset.css         # CSS 重置
│   │   ├── tailwind.css      # Tailwind 入口
│   │   └── material-symbols.css  # 图标样式
│   │
│   ├── App.vue               # 根组件
│   ├── main.ts               # 应用入口
│   └── router.ts             # 路由配置
│
├── .eslintrc.cjs             # ESLint 配置
├── .prettierrc.json          # Prettier 配置
├── tailwind.config.js        # Tailwind 配置
├── tsconfig.json             # TypeScript 配置
├── vite.config.ts            # Vite 配置
├── package.json              # 项目依赖
└── README.md                 # 项目文档
```

## 🚀 快速开始

### 环境要求

- Node.js >= 16.x
- npm >= 8.x

### 安装依赖

```bash
npm install
```

### 开发模式

启动开发服务器，默认运行在 `http://localhost:5173`：

```bash
npm run dev
```

开发服务器会自动代理 `/api` 请求到后端服务器 `http://localhost:8000`。

### 构建生产版本

```bash
npm run build
```

构建产物会输出到 `dist/` 目录。

### 预览生产构建

```bash
npm run preview
```

### 代码检查

```bash
# 检查代码问题
npm run lint

# 自动修复问题
npm run lint:fix
```

### 代码格式化

```bash
# 格式化所有文件
npm run format

# 检查格式
npm run format:check
```

## 📐 开发规范

### API 调用规范

所有 API 接口统一通过 `src/api/` 目录管理，使用 `xxxApi` 命名格式：

```typescript
// 推荐：从 index 统一导入
import { authApi, booksApi, shelvesApi } from '@/api'

// 使用示例
const books = await booksApi.list({ page: 1 })
const user = await authApi.me()
```

API 方法命名遵循 RESTful 风格：
- `list()` - 获取列表
- `get(id)` - 获取单个资源
- `create(data)` - 创建资源
- `update(id, data)` - 更新资源
- `remove(id)` - 删除资源

详见 [API 层规范文档](API.md)。

### Composables 使用

项目提供多个可复用的组合式函数，避免重复代码：

#### 1. useErrorHandler - 统一错误处理

```typescript
import { useErrorHandler } from '@/composables'

const { handleError, handleSuccess } = useErrorHandler()

async function saveData() {
  try {
    await api.save(data)
    handleSuccess('保存成功')
  } catch (error) {
    handleError(error, { context: 'ComponentName.saveData' })
  }
}
```

#### 2. usePagination - 分页逻辑

```typescript
import { usePagination } from '@/composables'

const {
  data: books,
  loading,
  currentPage,
  lastPage,
  total,
  loadPage,
  refresh
} = usePagination({
  fetcher: async (page) => await booksApi.list({ page }),
  onSuccess: (data) => console.log('加载成功'),
  onError: (error) => handleError(error)
})

onMounted(() => loadPage(1))
```

#### 3. useLoading - 加载状态管理

```typescript
import { useLoading } from '@/composables'

const { loading, withLoading } = useLoading()

async function fetchData() {
  await withLoading(async () => {
    // 异步操作，loading 自动管理
    await api.fetchData()
  })
}
```

#### 4. usePermission - 权限检查

```typescript
import { usePermission } from '@/composables'

const { hasPermission, hasRole, hasAnyPermission } = usePermission()

if (hasPermission('books.edit')) {
  // 用户有编辑图书权限
}

if (hasRole('admin')) {
  // 用户是管理员
}

if (hasAnyPermission(['books.edit', 'books.create'])) {
  // 用户有任一权限
}
```

详见 [Composables 使用指南](COMPOSABLES.md)。

### 权限指令

项目提供三个自定义指令用于权限控制：

```vue
<template>
  <!-- 单个权限检查 -->
  <el-button v-permission="'books.edit'">编辑</el-button>
  
  <!-- 角色检查 -->
  <el-button v-role="'admin'">管理员功能</el-button>
  
  <!-- 多个权限任一满足 -->
  <el-button v-any-permission="['books.edit', 'books.create']">
    创建或编辑
  </el-button>
</template>
```

权限指令会自动控制元素的显示/隐藏，无权限时元素会从 DOM 中移除。

### 路由守卫

路由支持基于元信息的权限控制：

```typescript
// 路由定义示例
{
  path: '/admin/users',
  name: 'admin-users',
  component: () => import('./pages/Admin/UserList.vue'),
  meta: {
    requiresAuth: true,           // 需要登录
    permission: 'users.view',     // 需要查看用户权限
  }
}

// 支持多权限（任一满足）
{
  meta: {
    permission: 'books.edit|books.create',  // 有任一权限即可
  }
}

// 要求所有权限
{
  meta: {
    permission: 'books.edit|books.delete',
    requireAllPermissions: true,   // 需要所有权限
  }
}
```

### 状态管理

使用 Pinia 进行状态管理，主要 Store 包括：

#### authStore - 认证状态

```typescript
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// 状态
authStore.user          // 当前用户
authStore.isLoggedIn    // 是否已登录

// 方法
authStore.login(credentials)
authStore.logout()
authStore.setUser(user)
```

#### settingsStore - 用户设置

```typescript
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

// 获取设置
const value = settingsStore.get('key', defaultValue)

// 保存设置
await settingsStore.set('key', value)
```

#### systemStore - 系统状态

```typescript
import { useSystemStore } from '@/stores/system'

const systemStore = useSystemStore()

// 系统设置
systemStore.settings
systemStore.permissions
```

### 类型安全

项目使用 TypeScript 提供完整的类型支持。路径别名配置：

```typescript
// tsconfig.json 和 vite.config.ts 中配置了 @ 别名
import { booksApi } from '@/api/books'
import { useErrorHandler } from '@/composables'
import Button from '@/components/Button.vue'
```

### 代码风格

项目使用 ESLint + Prettier 统一代码风格：

- **缩进**：2 空格
- **引号**：单引号
- **组件命名**：PascalCase
- **文件命名**：PascalCase（组件）、camelCase（工具）

## 🎯 核心功能

### 图书管理
- 图书列表展示（支持分页、搜索、筛选）
- 图书详情查看
- 图书创建/编辑/删除（管理员）
- 元数据编辑（作者、标签、系列等）
- 封面上传与编辑
- 文件管理（上传、删除、格式转换）

### 阅读功能
- PDF 阅读器（支持书签、进度保存）
- EPUB 阅读器（支持主题、字体设置）
- TXT 阅读器（支持章节管理）
- 阅读进度自动保存
- 阅读设置个性化

### 书架管理
- 个人书架创建与管理
- 全局书架浏览
- 图书收藏到书架
- 书架权限控制

### 用户系统
- 用户注册/登录
- Cookie 认证（Session）
- 密码找回/重置
- 个人资料管理
- 用户设置

### 权限管理（RBAC）
- 基于角色的访问控制
- 细粒度权限系统
- 用户角色管理（管理员）
- 权限指令与路由守卫

### 管理后台
- 用户管理
- 角色权限管理
- 图书管理
- 文件管理与清理
- 系统设置
- 批量操作支持

## ⚙️ 项目配置

### Vite 配置

```typescript
// vite.config.ts
{
  server: {
    port: 5173,              // 开发服务器端口
    open: true,              // 自动打开浏览器
    proxy: {
      '/api': {
        target: 'http://localhost:8000',  // 后端 API 地址
        changeOrigin: true
      }
    }
  }
}
```

### Tailwind 配置

项目使用 Tailwind CSS 作为主要样式方案，配置文件：`tailwind.config.js`。

入口文件：`src/styles/tailwind.css`

### Element Plus 配置

Element Plus 已在 `main.ts` 中全局注册，默认使用中文语言包：

```typescript
import ElementPlus from 'element-plus'
import zhCn from 'element-plus/es/locale/lang/zh-cn'

app.use(ElementPlus, { locale: zhCn })
```

### TypeScript 配置

路径别名配置：
- `@/` → `src/`
- `@/*` → `src/*`

## 📝 开发建议

### 组件开发
- 组件尽量保持单一职责
- 复用逻辑提取到 composables
- 使用 TypeScript 提供完整类型定义
- 大组件考虑拆分为多个子组件

### API 调用
- 统一使用 `@/api` 中的 API 对象
- 使用 `useErrorHandler` 处理错误
- 异步操作使用 `useLoading` 管理状态
- 列表数据使用 `usePagination` 简化分页逻辑

### 权限控制
- 页面级权限在路由 meta 中配置
- 组件级权限使用 `v-permission` 等指令
- 逻辑级权限使用 `usePermission` composable

### 状态管理
- 跨组件共享状态使用 Pinia
- 组件内部状态使用 `ref`/`reactive`
- 避免过度使用全局状态

### 性能优化
- 大列表使用虚拟滚动
- 图片使用懒加载
- 路由使用动态导入（懒加载）
- 合理使用 `computed` 缓存计算结果

## 📚 相关文档

- [API 层规范文档](API.md)
- [Composables 使用指南](COMPOSABLES.md)
- [权限系统设计](../RBAC.md)

## 🤝 贡献指南

1. 遵循项目现有的代码风格和规范
2. 提交前运行 `npm run lint` 和 `npm run format`
3. 确保 TypeScript 编译无错误
4. 新功能需要更新相关文档
