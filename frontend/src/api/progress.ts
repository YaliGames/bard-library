import { http } from './http'

export interface ProgressPayload {
  file_id?: number | null
  // 0.0 ~ 1.0 的整体进度；对 txt 通常是章节占比或字符位点；对 pdf/epub 可用页码/CFI 计算
  progress?: number | null
  // 任意 JSON 序列化的位置信息，约定 shape：{ format: 'txt'|'pdf'|'epub'|string, chapterIndex?: number, cfi?: string, page?: number, offset?: number, extra?: any }
  location?: string | null
  // 可选：服务端目前会从 token 取 user，也兼容 user_id
  user_id?: number
}

export const progressApi = {
  get: (bookId: number, fileId?: number | null) => {
    const path = fileId
      ? `/api/v1/books/${bookId}/${fileId}/progress`
      : `/api/v1/books/${bookId}/progress`
    return http.get<any>(path)
  },
  save: (bookId: number, payload: ProgressPayload, fileId?: number | null) => {
    const path = fileId
      ? `/api/v1/books/${bookId}/${fileId}/progress`
      : `/api/v1/books/${bookId}/progress`
    return http.post<any>(path, payload)
  },
}
