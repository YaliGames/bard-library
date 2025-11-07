/**
 * Composables 索引文件
 *
 * 统一导出所有 composables,方便使用
 */

export { useErrorHandler } from './useErrorHandler'
export type { ErrorHandler, ErrorHandlerOptions } from './useErrorHandler'

export { usePagination } from './usePagination'
export type {
  PaginationMeta,
  PaginationResult,
  UsePaginationOptions,
  UsePaginationReturn,
} from './usePagination'

export { useLoading, useSimpleLoading } from './useLoading'
export type { UseLoadingReturn, UseSimpleLoadingReturn } from './useLoading'

export { useBookActions } from './useBookActions'
export type { UseBookActionsReturn } from './useBookActions'
