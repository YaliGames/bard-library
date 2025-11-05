/**
 * API 统一导出
 *
 * 使用方式:
 * import { authApi, booksApi } from '@/api'
 *
 * 或单独导入:
 * import { authApi } from '@/api/auth'
 */

// 认证和用户
export { authApi } from './auth'
export { settingsApi } from './settings'

// 图书相关
export { booksApi } from './books'
export { authorsApi } from './authors'
export { tagsApi } from './tags'
export { seriesApi } from './series'
export { shelvesApi } from './shelves'

// 文件和内容
export { filesApi } from './files'
export { adminFilesApi } from './adminFiles'
export { coversApi } from './covers'
export { txtApi } from './txt'

// 阅读相关
export { bookmarksApi } from './bookmarks'
export { progressApi } from './progress'

// 其他
export { metadataApi } from './metadata'
export { importsApi } from './imports'
export { systemSettingsApi } from './systemSettings'

// 类型导出
export type * from './types'
export type { LoginResponse } from './auth'
export type { UserSettings } from './settings'
export type { AdminFileItem } from './adminFiles'
export type { SettingDef, CategoryDef, SettingsResponse } from './systemSettings'
export type { Chapter } from './txt'
