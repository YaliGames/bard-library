import { http } from './http'

export const coversApi = {
  upload: (bookId: number, fd: FormData) => {
    return http.post<{ file_id: number }>(`/api/v1/books/${bookId}/cover/upload`, fd)
  },
  fromUrl: (bookId: number, body: { url: string }) => {
    return http.post<{ file_id: number }>(`/api/v1/books/${bookId}/cover/from-url`, body)
  },
  clear: (bookId: number) => {
    return http.delete<void>(`/api/v1/books/${bookId}/cover`)
  },
}
