import http from './http'
import type { Chapter, TxtChaptersResponse } from '@/types'

// API响应包装器
interface ApiResponse<T> {
  code: number
  data: T
  message: string
}

/**
 * 获取TXT文件章节信息
 */
export async function getTxtChapters(fileId: number): Promise<ApiResponse<TxtChaptersResponse>> {
  const response = await http.get<ApiResponse<TxtChaptersResponse>>(`/txt/${fileId}/chapters`)
  return response.data
}

/**
 * 获取TXT文件内容（指定章节）
 */
export async function getTxtContent(fileId: number, chapterIndex: number): Promise<ApiResponse<string>> {
  const response = await http.get<ApiResponse<string>>(`/txt/${fileId}/chapters/${chapterIndex}`)
  return response.data
}

/**
 * 获取TXT文件完整内容
 */
export async function getTxtFullContent(fileId: number): Promise<ApiResponse<string>> {
  const response = await http.get<ApiResponse<string>>(`/txt/${fileId}/full-content`)
  return response.data
}

// 导出API对象
export const txtApi = {
  listChapters: getTxtChapters,
  getChapterContent: getTxtContent,
  getFullContent: getTxtFullContent,
}