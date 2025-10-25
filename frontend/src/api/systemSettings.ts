import { http } from './http'

export const systemSettingsApi = {
  get: () => http.get<any>('/api/v1/admin/settings'),
  update: (data: any) => http.post<any>('/api/v1/admin/settings', { data: { data } }),
}

export type SystemSettings = any
