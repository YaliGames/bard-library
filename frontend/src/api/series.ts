import { http } from './http'
import type { Series } from './types'

export const seriesApi = {
  list: (q?: string) => {
    const u = new URL('/api/v1/series', window.location.origin)
    if (q) u.searchParams.set('q', q)
    return http.get<Series[]>(u.toString())
  },
  create: (name: string) => {
    return http.post<Series>('/api/v1/series', { name })
  },
  update: (id: number, name: string) => {
    return http.patch<Series>(`/api/v1/series/${id}`, { name })
  },
  remove: (id: number) => {
    return http.delete<void>(`/api/v1/series/${id}`)
  },
}
