# Composables 使用指南

本文档说明如何使用项目中的 Composables 来复用常见逻辑。

## 目录

- [useErrorHandler - 统一错误处理](#useerrorhandler---统一错误处理)
- [usePagination - 分页逻辑](#usepagination---分页逻辑)
- [useLoading - 加载状态管理](#useloading---加载状态管理)
- [useBookActions - 书籍操作](#usebookactions---书籍操作)

---

## useErrorHandler - 统一错误处理

封装统一的错误处理逻辑,避免重复的 `try-catch + ElMessage.error()`。

### 基本用法

```typescript
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

// 使用示例
async function saveData() {
  try {
    await api.save(data)
    handleSuccess('保存成功')
  } catch (error) {
    handleError(error, { context: 'ComponentName.saveData' })
  }
}
```

### API

```typescript
interface ErrorHandlerOptions {
  context?: string        // 错误上下文,用于日志
  message?: string        // 自定义错误消息
  showToast?: boolean     // 是否显示 Toast (默认 true)
  logToConsole?: boolean  // 是否记录到控制台 (默认 true)
  onError?: (error: any) => void  // 自定义错误回调
}

handleError(error: any, options?: ErrorHandlerOptions): void
handleSuccess(message?: string): void
withErrorHandling<T>(fn: T, options?: ErrorHandlerOptions): T
```

---

## usePagination - 分页逻辑

封装分页数据加载逻辑,包括翻页、刷新等功能。

### 基本用法

```typescript
import { usePagination } from '@/composables/usePagination'
import { booksApi } from '@/api/books'

const {
  data: books,
  loading,
  currentPage,
  lastPage,
  total,
  loadPage,
  nextPage,
  prevPage,
  refresh,
} = usePagination({
  fetcher: async page => {
    return await booksApi.list({ page, ...filters.value })
  },
  onSuccess: data => {
    console.log('加载成功', data.length, '条数据')
  },
  onError: error => {
    handleError(error, { context: 'BookList.loadBooks' })
  },
})

// 在 onMounted 中加载第一页
onMounted(() => {
  loadPage(1)
})
```

### 在模板中使用

```vue
<template>
  <div>
    <el-skeleton v-if="loading" :rows="5" animated />

    <div v-else>
      <div v-for="book in books" :key="book.id">
        {{ book.title }}
      </div>
    </div>

    <el-pagination
      :current-page="currentPage"
      :page-count="lastPage"
      :total="total"
      @current-change="loadPage"
    />
  </div>
</template>
```

### API

```typescript
interface UsePaginationOptions<T> {
  fetcher: (page: number) => Promise<PaginationResult<T>>
  initialPage?: number
  onSuccess?: (data: T[]) => void
  onError?: (error: any) => void
}

// 返回值
{
  // State
  loading: Ref<boolean>
  data: Ref<T[]>
  currentPage: Ref<number>
  lastPage: Ref<number>
  total: Ref<number>
  perPage: Ref<number>

  // Computed
  hasNextPage: ComputedRef<boolean>
  hasPrevPage: ComputedRef<boolean>
  totalPages: ComputedRef<number>
  isEmpty: ComputedRef<boolean>

  // Methods
  loadPage: (page: number) => Promise<void>
  nextPage: () => Promise<void>
  prevPage: () => Promise<void>
  firstPage: () => Promise<void>
  goToLastPage: () => Promise<void>
  refresh: () => Promise<void>
  reset: () => Promise<void>
}
```

---

## useLoading - 加载状态管理

封装加载状态管理,支持多个并发的加载状态。

### 基本用法 - useLoading (多状态)

```typescript
import { useLoading } from '@/composables/useLoading'

const { isLoading, startLoading, stopLoading, withLoading } = useLoading()

// 方式1: 手动控制
async function fetchBooks() {
  startLoading('fetchBooks')
  try {
    const data = await api.getBooks()
    return data
  } finally {
    stopLoading('fetchBooks')
  }
}

// 方式2: 自动包装
async function fetchAuthors() {
  return await withLoading('fetchAuthors', async () => {
    return await api.getAuthors()
  })
}

// 检查特定加载状态
const isLoadingBooks = computed(() => isLoadingKey('fetchBooks'))
```

### 基本用法 - useSimpleLoading (单状态)

```typescript
import { useSimpleLoading } from '@/composables/useLoading'

const { loading, withLoading } = useSimpleLoading()

async function fetchData() {
  return await withLoading(async () => {
    return await api.getData()
  })
}
```

### API

```typescript
// useLoading
{
  isLoading: ComputedRef<boolean>
  loadingKeys: ComputedRef<string[]>
  startLoading: (key?: string) => void
  stopLoading: (key?: string) => void
  isLoadingKey: (key: string) => boolean
  clearAll: () => void
  withLoading: <T>(key: string, fn: () => Promise<T>) => Promise<T>
  createLoadingFn: <T>(key: string, fn: T) => T
}

// useSimpleLoading
{
  loading: Ref<boolean>
  withLoading: <T>(fn: () => Promise<T>) => Promise<T>
  setLoading: (value: boolean) => void
}
```

---

## useBookActions - 书籍操作

封装常见的书籍操作逻辑,如标记已读、删除等。

### 基本用法

```typescript
import { useBookActions } from '@/composables/useBookActions'

const { toggleReadMark, deleteBook } = useBookActions()

// 切换已读状态
async function handleToggleRead(book: Book) {
  const success = await toggleReadMark(book, () => {
    // 成功后刷新列表
    refresh()
  })

  if (success) {
    console.log('操作成功')
  }
}

// 删除书籍
async function handleDelete(book: Book) {
  const confirmed = await ElMessageBox.confirm(`确定要删除《${book.title}》吗?`, '确认删除', {
    type: 'warning',
  })

  if (confirmed) {
    const success = await deleteBook(book.id, book.title, false, () => {
      // 成功后刷新列表
      refresh()
    })
  }
}
```

### API

```typescript
{
  // 切换单本书籍已读状态
  toggleReadMark: (
    book: Book | { id: number; is_read_mark?: boolean | 0 | 1 },
    onSuccess?: () => void,
  ) => Promise<boolean>

  // 批量标记已读/未读
  batchToggleReadMark: (bookIds: number[], targetState: boolean, onSuccess?: () => void) =>
    Promise<boolean>

  // 删除书籍
  deleteBook: (bookId: number, bookTitle: string, withFiles?: boolean, onSuccess?: () => void) =>
    Promise<boolean>

  // 批量删除
  batchDeleteBooks: (bookIds: number[], withFiles?: boolean, onSuccess?: () => void) =>
    Promise<boolean>
}
```

---

## 组合使用示例

在实际组件中,通常会组合使用多个 Composables:

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { booksApi } from '@/api/books'
import { usePagination, useBookActions, useErrorHandler } from '@/composables'
import type { Book } from '@/api/types'

// 错误处理
const { handleError } = useErrorHandler()

// 筛选条件
const filters = ref({
  q: '',
  authorId: undefined,
  tagId: undefined,
})

// 分页
const {
  data: books,
  loading,
  currentPage,
  lastPage,
  total,
  loadPage,
  refresh,
} = usePagination<Book>({
  fetcher: async page => {
    return await booksApi.list({
      page,
      ...filters.value,
    })
  },
  onError: error => {
    handleError(error, { context: 'BookList.loadBooks' })
  },
})

// 书籍操作
const { toggleReadMark } = useBookActions()

// 切换已读状态
async function handleToggleRead(book: Book) {
  await toggleReadMark(book, refresh)
}

// 搜索
function handleSearch() {
  loadPage(1)
}

// 初始化
onMounted(() => {
  loadPage(1)
})
</script>

<template>
  <div>
    <!-- 搜索栏 -->
    <el-input v-model="filters.q" @change="handleSearch" />

    <!-- 书籍列表 -->
    <el-skeleton v-if="loading" :rows="5" animated />

    <div v-else>
      <div v-for="book in books" :key="book.id" class="book-item">
        <h3>{{ book.title }}</h3>
        <el-button @click="handleToggleRead(book)">
          {{ book.is_read_mark ? '取消已读' : '标记已读' }}
        </el-button>
      </div>
    </div>

    <!-- 分页 -->
    <el-pagination
      :current-page="currentPage"
      :page-count="lastPage"
      :total="total"
      @current-change="loadPage"
    />
  </div>
</template>
```

---

## 最佳实践

1. **错误处理**: 所有异步操作都应该使用 `useErrorHandler`
2. **分页列表**: 使用 `usePagination` 统一管理分页逻辑
3. **加载状态**: 复杂加载场景使用 `useLoading`,简单场景使用 `useSimpleLoading`
4. **业务逻辑**: 将可复用的业务逻辑抽取到独立的 Composable (如 `useBookActions`)
5. **命名规范**: Composable 函数以 `use` 开头,返回值使用解构赋值

---

## 迁移指南

将现有组件迁移到使用 Composables:

### Before (旧代码)

```typescript
const loading = ref(false)
const books = ref<Book[]>([])
const currentPage = ref(1)
const lastPage = ref(1)

async function loadBooks(page: number) {
  loading.value = true
  try {
    const res = await booksApi.list({ page })
    books.value = res.data
    currentPage.value = res.meta.current_page
    lastPage.value = res.meta.last_page
  } catch (e: any) {
    ElMessage.error(e?.message || '加载失败')
  } finally {
    loading.value = false
  }
}

async function toggleRead(book: Book) {
  try {
    const target = !book.is_read_mark
    await booksApi.markRead(book.id, target)
    book.is_read_mark = target
    ElMessage.success(target ? '已标记为已读' : '已取消已读')
  } catch (e: any) {
    ElMessage.error(e?.message || '操作失败')
  }
}
```

### After (新代码)

```typescript
const { handleError } = useErrorHandler()

const {
  data: books,
  loading,
  currentPage,
  lastPage,
  loadPage,
  refresh,
} = usePagination({
  fetcher: async page => {
    return await booksApi.list({ page })
  },
  onError: error => {
    handleError(error, { context: 'BookList.loadBooks' })
  },
})

const { toggleReadMark } = useBookActions()

async function handleToggleRead(book: Book) {
  await toggleReadMark(book, refresh)
}
```
