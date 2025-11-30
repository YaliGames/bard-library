import { booksApi } from '@/api/books'
import { useErrorHandler } from './useErrorHandler'
import type { Book } from '@/api/types'

/**
 * 书籍操作逻辑 Composable
 *
 * 封装常见的书籍操作,如标记已读、添加到书架等
 *
 * 使用示例:
 * ```typescript
 * const { toggleReadMark, isTogglingRead } = useBookActions()
 *
 * await toggleReadMark(book, () => {
 *   // 成功后的回调,如更新列表
 *   book.is_read_mark = !book.is_read_mark
 * })
 * ```
 */
export function useBookActions() {
  const { handleError, handleSuccess } = useErrorHandler()

  /**
   * 切换书籍已读状态
   */
  async function toggleReadMark(
    book: Book | { id: number; is_read_mark?: boolean | 0 | 1 },
    onSuccess?: () => void,
  ): Promise<boolean> {
    const currentState = !!(book as any).is_read_mark
    const targetState = !currentState

    try {
      await booksApi.markRead(book.id, targetState)

      // 乐观更新
      ;(book as any).is_read_mark = targetState ? 1 : 0

      handleSuccess(targetState ? '已标记为已读' : '已取消已读')
      onSuccess?.()

      return true
    } catch (error) {
      handleError(error, { context: 'BookActions.toggleReadMark' })
      return false
    }
  }

  /**
   * 批量标记书籍已读/未读
   */
  async function batchToggleReadMark(
    bookIds: number[],
    targetState: boolean,
    onSuccess?: () => void,
  ): Promise<boolean> {
    if (bookIds.length === 0) {
      handleError(new Error('请选择要操作的书籍'), {
        context: 'BookActions.batchToggleReadMark',
        message: '请选择要操作的书籍',
      })
      return false
    }

    try {
      // 如果 API 支持批量操作,使用批量接口
      // 否则串行执行
      await Promise.all(bookIds.map(id => booksApi.markRead(id, targetState)))

      handleSuccess(
        `已${targetState ? '标记' : '取消'}${bookIds.length}本书为${targetState ? '已读' : '未读'}`,
      )
      onSuccess?.()

      return true
    } catch (error) {
      handleError(error, { context: 'BookActions.batchToggleReadMark' })
      return false
    }
  }

  /**
   * 删除书籍
   */
  async function deleteBook(
    bookId: number,
    bookTitle: string,
    withFiles = false,
    onSuccess?: () => void,
  ): Promise<boolean> {
    try {
      await booksApi.remove(bookId, { withFiles })
      handleSuccess(`已删除《${bookTitle}》`)
      onSuccess?.()

      return true
    } catch (error) {
      handleError(error, { context: 'BookActions.deleteBook' })
      return false
    }
  }

  /**
   * 批量删除书籍
   */
  async function batchDeleteBooks(
    bookIds: number[],
    withFiles = false,
    onSuccess?: () => void,
  ): Promise<boolean> {
    if (bookIds.length === 0) {
      handleError(new Error('请选择要删除的书籍'), {
        context: 'BookActions.batchDeleteBooks',
        message: '请选择要删除的书籍',
      })
      return false
    }

    try {
      await Promise.all(bookIds.map(id => booksApi.remove(id, { withFiles })))
      handleSuccess(`已删除${bookIds.length}本书`)
      onSuccess?.()

      return true
    } catch (error) {
      handleError(error, { context: 'BookActions.batchDeleteBooks' })
      return false
    }
  }

  return {
    toggleReadMark,
    batchToggleReadMark,
    deleteBook,
    batchDeleteBooks,
  }
}

// 导出类型
export type UseBookActionsReturn = ReturnType<typeof useBookActions>
