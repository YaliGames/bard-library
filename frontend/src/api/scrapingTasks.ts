import { http } from './http'

// 类型定义
export interface ScrapingTask {
  id: number
  user_id: number
  name: string
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  total_items: number
  processed_items: number
  success_items: number
  failed_items: number
  options: {
    auto_download_cover?: boolean
    skip_existing?: boolean
  }
  error_message?: string
  started_at?: string
  finished_at?: string
  created_at: string
  updated_at: string
}

export interface ScrapingResult {
  id: number
  task_id: number
  provider: string
  source_id: string
  source_url: string
  query: string
  metadata: Record<string, any>
  status: 'pending' | 'success' | 'failed' | 'skipped'
  book_id?: number
  error_message?: string
  processed_at?: string
  created_at: string
  updated_at: string
}

export interface CreateScrapingTaskParams {
  name: string
  items: Array<{
    provider: string
    source_id: string
    source_url: string
    query: string
    metadata: Record<string, any>
  }>
  options?: {
    auto_download_cover?: boolean
    skip_existing?: boolean
  }
}

// API 对象
export const scrapingTasksApi = {
  /**
   * 获取任务列表
   */
  list: async (params?: { status?: string; page?: number; per_page?: number }) => {
    return http.get<{ data: ScrapingTask[] }>('/api/v1/admin/scraping-tasks', { params })
  },

  /**
   * 创建任务
   */
  create: async (data: CreateScrapingTaskParams) => {
    return http.post<ScrapingTask>('/api/v1/admin/scraping-tasks', data)
  },

  /**
   * 获取任务详情
   */
  show: async (id: number) => {
    return http.get<ScrapingTask>(`/api/v1/admin/scraping-tasks/${id}`)
  },

  /**
   * 取消任务
   */
  cancel: async (id: number) => {
    return http.post<ScrapingTask>(`/api/v1/admin/scraping-tasks/${id}/cancel`)
  },

  /**
   * 删除任务
   */
  destroy: async (id: number) => {
    return http.delete<{ message: string }>(`/api/v1/admin/scraping-tasks/${id}`)
  },

  /**
   * 获取任务结果列表
   */
  results: async (id: number, params?: { status?: string; page?: number; per_page?: number }) => {
    return http.get<{ data: ScrapingResult[] }>(`/api/v1/admin/scraping-tasks/${id}/results`, {
      params,
    })
  },
}
