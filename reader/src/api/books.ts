import http from './http'
import type { Book, PaginatedResponse } from '@/types'

// API响应包装器
interface ApiResponse<T> {
  code: number
  data: T
  message: string
}

export interface BookListParams {
  page?: number
  per_page?: number
  search?: string
  author?: string
  tag?: string
  sort?: 'title' | 'created_at' | 'rating'
  order?: 'asc' | 'desc'
}

/**
 * 获取图书列表
 */
export async function getBooks(params: BookListParams = {}): Promise<ApiResponse<PaginatedResponse<Book>>> {
  const response = await http.get<ApiResponse<PaginatedResponse<Book>>>('/books', { params })
  return response.data
}

/**
 * 获取图书详情
 */
export async function getBook(id: number): Promise<ApiResponse<Book>> {
  const response = await http.get<ApiResponse<Book>>(`/books/${id}`)
  return response.data
}

/**
 * 获取图书封面
 */
export function getBookCoverUrl(book: Book): string | null {
  if (!book.cover_file_id) return null
  return `/api/v1/files/${book.cover_file_id}/preview`
}

/**
 * 获取图书文件下载URL
 */
export function getBookFileUrl(fileId: number): string {
  return `/api/v1/files/${fileId}/download`
}