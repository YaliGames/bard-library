# API 层规范文档

## 📁 文件组织

```
src/api/
├── index.ts              # 统一导出入口
├── http.ts               # HTTP 客户端封装
├── types.ts              # 共享类型定义
│
├── auth.ts               # 认证相关 API
├── settings.ts           # 用户设置 API
│
├── books.ts              # 图书管理 API
├── authors.ts            # 作者管理 API
├── tags.ts               # 标签管理 API
├── series.ts             # 系列管理 API
├── shelves.ts            # 书架管理 API
│
├── files.ts              # 文件管理 API (普通用户)
├── adminFiles.ts         # 文件管理 API (管理员)
├── covers.ts             # 封面管理 API
├── txt.ts                # TXT 章节管理 API
│
├── bookmarks.ts          # 书签 API
├── progress.ts           # 阅读进度 API
├── metadata.ts           # 元数据 API
├── imports.ts            # 导入功能 API
│
└── systemSettings.ts     # 系统设置 API
```

## 🎯 命名规范

### 1. API 对象命名

所有 API 对象统一使用 `xxxApi` 命名格式:

```typescript
export const authApi = { ... }
export const booksApi = { ... }
export const filesApi = { ... }
```

### 2. 方法命名

使用语义化的动词 + 名词组合:

```typescript
// CRUD 操作
list()      // 获取列表
get()       // 获取单个
create()    // 创建
update()    // 更新
remove()    // 删除 (避免使用 delete 保留字)

// 特殊操作
search()    // 搜索
upload()    // 上传
download()  // 下载
import()    // 导入
export()    // 导出
```

### 3. 参数命名

统一使用 `params` 或 `payload` 作为参数名:

```typescript
// GET 请求查询参数
list(params?: { q?: string; page?: number })

// POST/PATCH 请求体
create(payload: Partial<Book>)
update(id: number, payload: Partial<Book>)
```

## 📝 使用方式

### 推荐方式 1: 从 index 统一导入

```typescript
import { authApi, booksApi, filesApi } from '@/api'

// 使用
const books = await booksApi.list({ page: 1 })
const user = await authApi.me()
```

### 推荐方式 2: 单独导入

```typescript
import { booksApi } from '@/api/books'
import { authApi } from '@/api/auth'

// 使用
const books = await booksApi.list()
```

## 🔧 API 方法设计原则

### 1. 返回类型明确

```typescript
// ✅ 好 - 明确的返回类型
list: (): Promise<Book[]> => http.get<Book[]>('/api/v1/books')

// ❌ 差 - 返回类型不明确
list: () => http.get('/api/v1/books')
```

### 2. 参数类型化

```typescript
// ✅ 好 - 使用接口定义参数
interface ListParams {
  q?: string
  page?: number
  per_page?: number
  sort?: 'modified' | 'created'
}
list: (params?: ListParams) => { ... }

// ❌ 差 - 使用 any
list: (params?: any) => { ... }
```

## 📦 类型定义规范

### 1. 共享类型放在 types.ts

```typescript
// types.ts
export interface Book {
  id: number
  title: string
  // ...
}

export interface Author {
  id: number
  name: string
  // ...
}
```

### 2. API 特定类型放在各自文件

```typescript
// auth.ts
export interface LoginResp {
  token: string
  user: User
}

export const authApi = {
  login: (): Promise<LoginResp> => { ... }
}
```

### 3. 从 index.ts 统一导出类型

```typescript
// index.ts
export type * from './types'
export type { LoginResp } from './auth'
export type { Chapter } from './txt'
```
