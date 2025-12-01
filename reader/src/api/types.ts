export interface Permission {
    id: number
    name: string
    display_name: string
    group: string
    description?: string
    is_system: boolean
}

export interface Role {
    id: number
    name: string
    display_name: string
    description?: string
    is_system: boolean
    priority: number
    permissions?: Permission[]
    users_count?: number
}

export interface User {
    id: number
    name: string
    email: string
    role?: string
    roles?: Role[]
    location?: string
    website?: string
    bio?: string
    email_verified_at?: string
    created_at?: string
    token?: string
}

export interface Author {
    id: number
    name: string
}

export interface Tag {
    id: number
    name: string
    type?: string
}

export interface FileRec {
    id: number
    format?: string
    size?: number
    mime?: string
    path: string
    storage?: string
}

export interface Book {
    id: number
    title: string
    subtitle?: string
    description?: string
    rating?: number
    language?: string
    publisher?: string
    published_at?: string
    pages?: string
    isbn10?: string
    isbn13?: string
    series_id?: number
    series_index?: number
    authors?: Author[]
    tags?: Tag[]
    files?: FileRec[]
    cover_file_id?: number
    is_read_mark?: 0 | 1 | boolean
    is_reading?: 0 | 1 | boolean
}

export interface Bookmark {
    id: number
    book_id?: number
    file_id?: number
    location?: string | null
    note?: string | null
    color?: string | null
    created_at?: string
    updated_at?: string
}

export interface Shelf {
    id: number
    name: string
    description?: string
    public: 0 | 1 | boolean
    created_at?: string
    updated_at?: string
    books_count?: number
}

export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from?: number
    to?: number
}
