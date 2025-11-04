import { http } from './http'

export interface SettingDef {
  type: 'bool' | 'int' | 'string' | 'json' | 'size'
  default?: any
  label?: string
  description?: string
  default_bytes?: number | null
}

export interface SettingsResponse {
  values: Record<string, any>
  schema: Record<string, SettingDef>
}

export const systemSettingsApi = {
  get: () => http.get<SettingsResponse>('/api/v1/admin/settings'),
  update: (data: Record<string, any>) =>
    http.post<SettingsResponse>('/api/v1/admin/settings', { data }),
}

export type SystemSettings = SettingsResponse
