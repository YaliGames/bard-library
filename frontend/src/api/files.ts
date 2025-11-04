import { http } from './http'

export const filesApi = {
  remove: (id: number) => {
    return http.delete<void>(`/api/v1/files/${id}`)
  },
}
