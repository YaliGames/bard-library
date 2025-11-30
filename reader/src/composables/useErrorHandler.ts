import { ElMessage } from 'element-plus'

export interface ErrorHandlerOptions {
    context?: string
    message?: string
    showToast?: boolean
    logToConsole?: boolean
    onError?: (error: any) => void
}

export function useErrorHandler() {
    function handleError(error: any, options: ErrorHandlerOptions = {}) {
        const { context = 'Unknown', message, showToast = true, logToConsole = true, onError } = options

        const errorMessage =
            message ||
            error?.message ||
            error?.data?.message ||
            error?.response?.data?.message ||
            '操作失败'

        if (logToConsole) {
            console.error(`[${context}]`, {
                message: errorMessage,
                error,
                timestamp: new Date().toISOString(),
            })
        }

        if (showToast) {
            ElMessage.error(errorMessage)
        }

        if (onError) {
            onError(error)
        }
    }

    function withErrorHandling<T extends (...args: any[]) => Promise<any>>(
        fn: T,
        options: ErrorHandlerOptions = {},
    ): T {
        return (async (...args: any[]) => {
            try {
                return await fn(...args)
            } catch (error) {
                handleError(error, options)
                throw error
            }
        }) as T
    }

    function handleSuccess(message: string = '操作成功') {
        ElMessage.success(message)
    }

    return {
        handleError,
        withErrorHandling,
        handleSuccess,
    }
}

export type ErrorHandler = ReturnType<typeof useErrorHandler>
