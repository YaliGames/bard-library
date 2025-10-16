import { http } from "./http";
import type { Book, Bookmark } from './types'

export const bookmarksApi = {
  list: (bookId: number) => {
    return http.get<{book: Book, bookmarks: Bookmark[]}>(`/api/v1/books/${bookId}/bookmarks`);
  },
  create: (bookId: number, payload: Partial<Bookmark>) => {
    return http.post<Bookmark>(`/api/v1/books/${bookId}/bookmarks`, payload);
  },
  update: (bookId: number, bookmarkId: number, payload: Partial<Bookmark>) => {
    return http.patch<Bookmark>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`, payload);
  },
  remove: (bookId: number, bookmarkId: number) => {
    return http.delete<void>(`/api/v1/books/${bookId}/bookmarks/${bookmarkId}`);
  },
};
