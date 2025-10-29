import { http } from './http'
import { ensureResourceQuery, ensureResourceQuerySync } from '@/utils/signedUrls'
import type { MetaRecord } from "@/types/metadata";

const BASE = "/api/v1/metadata";

export const metadataApi = {
  async listProviders(): Promise<{ id: string; name: string; description?: string }[]> {
    return http.get<{ id: string; name: string; description?: string }[]>(`${BASE}/providers`)
  },
  async search(provider: string, q: string, limit = 5): Promise<MetaRecord[]> {
    const u = new URL(`${BASE}/${provider}/search`, window.location.origin)
    if (q) u.searchParams.set('q', q)
    if (limit) u.searchParams.set('limit', String(limit))
    const data = await http.get<any>(u.toString())
    return Array.isArray(data?.items) ? (data.items as MetaRecord[]) : []
  },
  async book(provider: string, params: { id?: string; url?: string }): Promise<MetaRecord | null> {
    const u = new URL(`${BASE}/${provider}/book`, window.location.origin)
    if (params.id) u.searchParams.set('id', params.id)
    if (params.url) u.searchParams.set('url', params.url)
    return http.get<MetaRecord>(u.toString())
  },
  coverUrl(provider: string, cover: string): string {
    const path = `${BASE}/${provider}/cover?cover=${encodeURIComponent(cover)}`
    return ensureResourceQuerySync(path)
  },
  async coverUrlWithToken(provider: string, cover: string): Promise<string> {
    const path = `${BASE}/${provider}/cover?cover=${encodeURIComponent(cover)}`
    return ensureResourceQuery(path)
  },
  async coverAbsoluteUrl(provider: string, cover: string): Promise<string> {
    const path = await this.coverUrlWithToken(provider, cover)
    const origin = window.location.origin
    return `${origin}${path}`
  },
}
