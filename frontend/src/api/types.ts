
export interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
}

export interface Author {
  id: number;
  name: string;
}

export interface Tag {
  id: number;
  name: string;
  type?: string;
}

export interface FileRec {
  id: number;
  format?: string;
  size?: number;
  mime?: string;
  path: string;
  storage?: string;
}

export interface Book {
  id: number;
  title: string;
  subtitle?: string;
  description?: string;
  rating?: number;
  language?: string;
  publisher?: string;
  published_at?: string;
  pages?: string;
  isbn10?: string;
  isbn13?: string;
  series_id?: number;
  series_index?: number;
  series?: Series;
  authors?: Author[];
  tags?: Tag[];
  files?: FileRec[];
  cover_file_id?: number;
  is_read_mark?: 0 | 1 | boolean;
  is_reading?: 0 | 1 | boolean;
  // Detail API 可能返回书架列表（用于前端在“书架详情页”添加/移除书籍时做差异化计算）
  shelves?: Array<{ id: number; name: string; user_id?: number | null }>;
}

export interface Bookmark {
  id: number;
  book_id?: number;
  file_id?: number;
  // 任意位置信息，具体由后端定义（如 chapterIndex/cfi/page 等）
  location?: string | null;
  note?: string | null;
  color?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface Series {
  id: number;
  name: string;
}

export interface Shelf {
  id: number;
  name: string;
  description?: string;
  is_public?: boolean;
  user_id?: number | null;
  cover?: string;
  books?: Book[];
}