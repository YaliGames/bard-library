// 图书相关类型定义
export interface Author {
  id: number
  name: string
  sort_name?: string
  created_at?: string
  updated_at?: string
  pivot?: {
    book_id: number
    author_id: number
    role: string
  }
}

export interface Tag {
  id: number
  name: string
  type?: string | null
  created_at?: string
  updated_at?: string
  pivot?: {
    book_id: number
    tag_id: number
  }
}

export interface FileRec {
  id: number
  book_id?: number
  format?: string
  size?: number
  mime?: string
  sha256?: string
  path: string
  storage?: string
  pages?: number | null
  created_at?: string
  updated_at?: string
}

export interface Series {
  id?: number
  name?: string
  // 根据需要添加更多字段
}

export interface Meta {
  source?: {
    id: string
    description: string
    link: string
  }
}

export interface Book {
  id: number
  title: string
  subtitle?: string | null
  description?: string | null
  rating?: number | null
  language?: string | null
  publisher?: string | null
  published_at?: string | null
  isbn10?: string | null
  isbn13?: string | null
  series_id?: number | null
  series_index?: number | null
  cover_file_id?: number | null
  created_by?: number | null
  meta?: Meta | null
  created_at: string
  updated_at: string
  is_reading: number
  is_read_mark: number
  authors?: Author[]
  tags?: Tag[]
  files?: FileRec[]
  series?: Series | null
}

// 分页响应
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

// 缓存相关类型
export interface CachedBook {
  fileId: number
  bookId?: number
  bookTitle?: string
  fileName: string
  chapters: Chapter[]
  contents: Map<number, string>
  cachedAt: number
  version: number
}

export interface Chapter {
  book_id: number
  file_id: number
  index: number
  title: string
  offset: number
  length: number
}

// TXT章节列表响应
export interface TxtChaptersResponse {
  book: {
    id: number
    title: string
    author: string | null
    cover: string | null
  }
  chapters: Chapter[]
}

// 阅读进度
export interface ReadingProgress {
  bookId: number
  fileId: number
  chapterIndex?: number
  position?: number
  percentage?: number
  updatedAt: number
}

// 书签类型
export interface Bookmark {
  id: number
  book_id: number
  user_id: number
  chapter_index: number
  sentence_index?: number
  start_position: number
  end_position: number
  content: string
  note?: string
  color?: string | null
  created_at: string
  updated_at: string
}