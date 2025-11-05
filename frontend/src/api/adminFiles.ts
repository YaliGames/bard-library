import { http } from './http'

// 常量定义
const BASE = '/api/v1/admin/files'

// 类型定义
export interface AdminFileItem {
  id: number
  book_id: number | null
  book: { id: number; title: string } | null
  used_as_cover: boolean
  format: string
  size: number
  mime: string
  sha256: string
  filename: string
  path: string
  storage: string
  created_at: string
  physical_exists: boolean
}

export interface AdminFileListParams {
  q?: string
  format?: string
  unused_covers?: boolean
  missing_physical?: boolean
  sortKey?: string
  sortOrder?: 'asc' | 'desc'
}

export interface AdminFileListResponse {
  count: number
  items: AdminFileItem[]
}

export interface FileCleanupPayload {
  kind?: 'covers' | 'all' | 'orphans'
  kinds?: Array<'covers' | 'dangling' | 'missing' | 'orphans'>
  dry?: boolean
  removePhysical?: boolean
}

export interface FileCleanupResponse {
  dry: boolean
  summary: any
  removed?: any
}

// API 对象
export const adminFilesApi = {
  /**
   * 获取管理员文件列表
   * @param params - 查询参数
   * @returns 文件列表及总数
   */
  list: (params?: AdminFileListParams) => {
    return http.get<AdminFileListResponse>(BASE, { params })
  },

  /**
   * 删除文件
   * @param id - 文件 ID
   * @param physical - 是否同时删除物理文件
   */
  remove: (id: number, physical = false) => {
    return http.delete<void>(`${BASE}/${id}`, {
      params: physical ? { physical: 'true' } : undefined,
    })
  },

  /**
   * 清理文件
   * @param payload - 清理配置
   * @param payload.kind - 清理类型(单个)
   * @param payload.kinds - 清理类型列表(多个)
   * @param payload.dry - 是否为试运行(不实际删除)
   * @param payload.removePhysical - 是否删除物理文件
   * @returns 清理结果
   */
  cleanup: (payload: FileCleanupPayload) => {
    return http.post<FileCleanupResponse>(`${BASE}/cleanup`, payload)
  },
}
