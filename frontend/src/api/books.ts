import { http } from './http'
import type { Book, FileRec } from './types'

// 常量定义
const BASE = '/api/v1/books'

// 类型定义
export interface BookListParams {
  q?: string
  page?: number
  perPage?: number
  sortBy?: string
  sort?: string
  order?: 'asc' | 'desc'
  shelfId?: number
  authorId?: number
  tagId?: number | number[]
  unread?: boolean
  reading?: boolean
  read?: boolean
  cover?: boolean
  format?: string
  epub?: boolean
  pdf?: boolean
  txt?: boolean
  azw3?: boolean
  seriesId?: number
  // 高级筛选参数
  read_state?: string
  min_rating?: number
  max_rating?: number
  publisher?: string
  published_from?: string
  published_to?: string
  language?: string
  series_value?: string | number
  isbn?: string
}

export interface BookListResponse {
  data: Book[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// 辅助函数
function normalizeBookListResponse(res: Book[] | BookListResponse): BookListResponse {
  if (Array.isArray(res)) {
    const meta = {
      current_page: 1,
      last_page: 1,
      per_page: res.length,
      total: res.length,
    }
    return {
      data: res,
      current_page: 1,
      last_page: 1,
      per_page: res.length,
      total: res.length,
      meta,
    }
  }
  // 确保返回的对象包含 meta 属性
  if (!res.meta) {
    res.meta = {
      current_page: res.current_page,
      last_page: res.last_page,
      per_page: res.per_page,
      total: res.total,
    }
  }
  return res
}

function normalizeBookListParams(params?: BookListParams) {
  if (!params) return undefined
  const normalized: Record<string, any> = {}
  const toCsv = (v: any) => (Array.isArray(v) ? v.join(',') : (v ?? ''))
  if (params.q) normalized.q = params.q
  if (params.page) normalized.page = params.page
  if (params.perPage) normalized.per_page = params.perPage
  if (params.sortBy) normalized.sort_by = params.sortBy
  if (params.sort) normalized.sort = params.sort
  if (params.order) normalized.order = params.order
  if (params.shelfId) normalized.shelf_id = params.shelfId
  if (params.authorId) normalized.author_id = toCsv(params.authorId)
  if (params.tagId) normalized.tag_id = toCsv(params.tagId)
  if (params.unread) normalized.unread = 'true'
  if (params.reading) normalized.reading = 'true'
  if (params.read) normalized.read = 'true'
  if (params.cover !== undefined) normalized.cover = params.cover ? 'true' : 'false'
  if (params.format) normalized.format = params.format
  if (params.epub) normalized.epub = 'true'
  if (params.pdf) normalized.pdf = 'true'
  if (params.txt) normalized.txt = 'true'
  if (params.azw3) normalized.azw3 = 'true'
  if (params.seriesId) normalized.series_id = params.seriesId
  if (params.read_state) normalized.read_state = params.read_state
  if (params.min_rating !== undefined) normalized.min_rating = params.min_rating
  if (params.max_rating !== undefined) normalized.max_rating = params.max_rating
  if (params.publisher) normalized.publisher = params.publisher
  if (params.published_from) normalized.published_from = params.published_from
  if (params.published_to) normalized.published_to = params.published_to
  if (params.language) normalized.language = params.language
  if (params.series_value !== undefined) normalized.series_value = params.series_value
  if (params.isbn) normalized.isbn = params.isbn
  return normalized
}

// API 对象
export const booksApi = {
  /**
   * 获取书籍列表
   * @param params - 查询参数
   * @returns 分页的书籍列表
   */
  list: async (params?: BookListParams) => {
    const res = await http.get<Book[] | BookListResponse>(BASE, {
      params: normalizeBookListParams(params),
    })
    return normalizeBookListResponse(res)
  },

  /**
   * 获取单本书籍详情
   * @param id - 书籍 ID
   * @returns 书籍详情
   */
  get: (id: number) => {
    return http.get<Book>(`${BASE}/${id}`)
  },

  /**
   * 创建新书籍
   * @param payload - 书籍数据
   * @returns 创建的书籍
   */
  create: (payload: Partial<Book>) => {
    return http.post<Book>(BASE, payload)
  },

  /**
   * 更新书籍信息
   * @param id - 书籍 ID
   * @param payload - 要更新的字段
   * @returns 更新后的书籍
   */
  update: (id: number, payload: Partial<Book>) => {
    return http.patch<Book>(`${BASE}/${id}`, payload)
  },

  /**
   * 删除书籍
   * @param id - 书籍 ID
   * @param opts - 删除选项
   * @param opts.withFiles - 是否同时删除关联文件
   */
  remove: (id: number, opts?: { withFiles?: boolean }) => {
    return http.delete<void>(`${BASE}/${id}`, {
      params: opts?.withFiles ? { with_files: 'true' } : undefined,
    })
  },

  /**
   * 获取书籍文件列表
   * @param id - 书籍 ID
   * @param opts - 查询选项
   * @param opts.include_cover - 是否包含封面文件
   * @returns 文件列表
   */
  files: (id: number, opts?: { include_cover?: boolean }) => {
    return http.get<FileRec[]>(`${BASE}/${id}/files`, {
      params: opts?.include_cover ? { include_cover: 'true' } : undefined,
    })
  },

  /**
   * 设置书籍作者
   * @param id - 书籍 ID
   * @param author_ids - 作者 ID 列表
   * @returns 更新后的书籍
   */
  setAuthors: (id: number, author_ids: number[]) => {
    return http.post<Book>(`${BASE}/${id}/authors`, { author_ids })
  },

  /**
   * 设置书籍标签
   * @param id - 书籍 ID
   * @param tag_ids - 标签 ID 列表
   * @returns 更新后的书籍
   */
  setTags: (id: number, tag_ids: number[]) => {
    return http.post<Book>(`${BASE}/${id}/tags`, { tag_ids })
  },

  /**
   * 标记书籍已读/未读状态
   * @param id - 书籍 ID
   * @param is_read - 是否已读
   * @returns 操作结果
   */
  markRead: (id: number, is_read: boolean) => {
    return http.post<{ success: boolean }>(`${BASE}/${id}/mark-read`, { is_read })
  },

  /**
   * 发送图书文件到邮箱
   * @param bookId - 书籍 ID
   * @param fileId - 文件 ID
   * @param email - 邮箱地址
   * @returns 发送结果
   */
  sendEmail: (bookId: number, fileId: number, email: string) => {
    return http.post<{ message: string; email: string }>(
      `${BASE}/${bookId}/files/${fileId}/send-email`,
      { email },
    )
  },
}
