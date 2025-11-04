import { http } from './http'

export interface UserSettings {
  bookList: {
    showReadTag: boolean
    showMarkReadButton: boolean
  }
  bookDetail: {
    showReadTag: boolean
  }
  txtReader: {
    autoScrollCategory: boolean
  }
  preferences: {
    expandFilterMenu: boolean
  }
}

export const settingsApi = {
  get: () => {
    return http.get<UserSettings>('/api/v1/me/settings')
  },
  update: (data: UserSettings) => {
    return http.post<UserSettings>('/api/v1/me/settings', { data })
  },
}
