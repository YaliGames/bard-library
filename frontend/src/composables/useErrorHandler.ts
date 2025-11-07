import { ElMessage } from 'element-plus'

export interface ErrorHandlerOptions {
  /** 错误上下文，用于日志记录 */
  context?: string
  /** 自定义错误消息 */
  message?: string
  /** 是否显示 Toast 提示 */
  showToast?: boolean
  /** 是否记录到控制台 */
  logToConsole?: boolean
  /** 错误回调 */
  onError?: (error: any) => void
}

export function useErrorHandler() {
  /**
   * 统一的错误处理函数
   */
  function handleError(error: any, options: ErrorHandlerOptions = {}) {
    const { context = 'Unknown', message, showToast = true, logToConsole = true, onError } = options

    // 提取错误消息
    const errorMessage =
      message ||
      error?.message ||
      error?.data?.message ||
      error?.response?.data?.message ||
      '操作失败'

    // 控制台记录
    if (logToConsole) {
      console.error(`[${context}]`, {
        message: errorMessage,
        error,
        timestamp: new Date().toISOString(),
      })
    }

    // Toast 提示
    if (showToast) {
      ElMessage.error(errorMessage)
    }

    // 自定义回调
    if (onError) {
      onError(error)
    }

    // 可以在这里添加错误上报
    // reportErrorToMonitoring(error, context)
  }

  /**
   * 包装异步函数，自动处理错误
   */
  function withErrorHandling<T extends (...args: any[]) => Promise<any>>(
    fn: T,
    options: ErrorHandlerOptions = {},
  ): T {
    return (async (...args: any[]) => {
      try {
        return await fn(...args)
      } catch (error) {
        handleError(error, options)
        throw error // 继续抛出，让调用者决定是否需要额外处理
      }
    }) as T
  }

  /**
   * 成功提示的便捷方法
   */
  function handleSuccess(message: string = '操作成功') {
    ElMessage.success(message)
  }

  return {
    handleError,
    withErrorHandling,
    handleSuccess,
  }
}

// 导出类型
export type ErrorHandler = ReturnType<typeof useErrorHandler>
