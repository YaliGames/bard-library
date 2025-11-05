import { http } from './http'
import { ensureResourceQuery, ensureResourceQuerySync } from '@/utils/signedUrls'
import type { MetaRecord } from '@/types/metadata'

// 常量定义
const BASE = '/api/v1/metadata'

// 类型定义
export interface MetadataProvider {
  id: string
  name: string
  description?: string
}

export interface MetadataBookParams {
  id?: string
  url?: string
}

// API 对象
export const metadataApi = {
  /**
   * 获取所有元数据提供商列表
   * @returns 提供商列表
   */
  listProviders: async (): Promise<MetadataProvider[]> => {
    return http.get<MetadataProvider[]>(`${BASE}/providers`)
  },

  /**
   * 搜索书籍元数据
   * @param provider - 提供商 ID
   * @param q - 搜索关键词
   * @param limit - 返回结果数量限制
   * @returns 元数据记录列表
   */
  search: async (provider: string, q: string, limit = 5): Promise<MetaRecord[]> => {
    const data = await http.get<any>(`${BASE}/${provider}/search`, { params: { q, limit } })
    return Array.isArray(data?.items) ? (data.items as MetaRecord[]) : []
  },

  /**
   * 获取书籍详细元数据
   * @param provider - 提供商 ID
   * @param params - 查询参数(id 或 url)
   * @returns 元数据记录
   */
  book: async (provider: string, params: MetadataBookParams): Promise<MetaRecord | null> => {
    return http.get<MetaRecord>(`${BASE}/${provider}/book`, { params })
  },

  /**
   * 获取封面图片 URL(同步,不带 token)
   * @param provider - 提供商 ID
   * @param cover - 封面标识
   * @returns 封面 URL
   */
  coverUrl: (provider: string, cover: string): string => {
    const path = `${BASE}/${provider}/cover?cover=${encodeURIComponent(cover)}`
    return ensureResourceQuerySync(path)
  },

  /**
   * 获取封面图片 URL(异步,带 token)
   * @param provider - 提供商 ID
   * @param cover - 封面标识
   * @returns 封面 URL
   */
  coverUrlWithToken: async (provider: string, cover: string): Promise<string> => {
    const path = `${BASE}/${provider}/cover?cover=${encodeURIComponent(cover)}`
    return ensureResourceQuery(path)
  },

  /**
   * 获取封面图片绝对 URL
   * @param provider - 提供商 ID
   * @param cover - 封面标识
   * @returns 完整的封面 URL(包含域名)
   */
  coverAbsoluteUrl: async (provider: string, cover: string): Promise<string> => {
    const path = await metadataApi.coverUrlWithToken(provider, cover)
    const origin = window.location.origin
    return `${origin}${path}`
  },
}
