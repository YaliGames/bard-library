import { http } from './http'

export const importsApi = {
  upload: (fd: FormData) => {
    return http.post<any>('/api/v1/books/import', fd);
  },
}
