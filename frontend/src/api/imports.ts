import { http } from './http'

export const importsApi = {
  upload: (fd: FormData, config?: any) => {
    return http.post<any>('/api/v1/books/import', fd, config);
  },
}
