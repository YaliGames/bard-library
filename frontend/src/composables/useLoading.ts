import { ref, computed } from 'vue'

/**
 * 加载状态管理 Composable
 *
 * 支持多个并发的加载状态管理
 *
 * 使用示例:
 * ```typescript
 * const { isLoading, startLoading, stopLoading, withLoading } = useLoading()
 *
 * // 方式1: 手动控制
 * startLoading('fetchBooks')
 * try {
 *   await fetchBooks()
 * } finally {
 *   stopLoading('fetchBooks')
 * }
 *
 * // 方式2: 自动包装
 * await withLoading('fetchBooks', async () => {
 *   await fetchBooks()
 * })
 * ```
 */
export function useLoading() {
  const loadingStates = ref<Set<string>>(new Set())

  // Computed
  const isLoading = computed(() => loadingStates.value.size > 0)
  const loadingKeys = computed(() => Array.from(loadingStates.value))

  // Methods
  function startLoading(key: string = 'default') {
    loadingStates.value.add(key)
  }

  function stopLoading(key: string = 'default') {
    loadingStates.value.delete(key)
  }

  function isLoadingKey(key: string): boolean {
    return loadingStates.value.has(key)
  }

  function clearAll() {
    loadingStates.value.clear()
  }

  /**
   * 包装异步函数，自动管理加载状态
   */
  async function withLoading<T>(key: string, fn: () => Promise<T>): Promise<T> {
    startLoading(key)
    try {
      return await fn()
    } finally {
      stopLoading(key)
    }
  }

  /**
   * 创建带加载状态的异步函数
   */
  function createLoadingFn<T extends (...args: any[]) => Promise<any>>(key: string, fn: T): T {
    return (async (...args: any[]) => {
      return await withLoading(key, () => fn(...args))
    }) as T
  }

  return {
    // State
    isLoading,
    loadingKeys,
    // Methods
    startLoading,
    stopLoading,
    isLoadingKey,
    clearAll,
    withLoading,
    createLoadingFn,
  }
}

/**
 * 简单的单一加载状态管理
 *
 * 使用示例:
 * ```typescript
 * const { loading, withLoading } = useSimpleLoading()
 *
 * const data = await withLoading(async () => {
 *   return await fetchData()
 * })
 * ```
 */
export function useSimpleLoading(initialValue = false) {
  const loading = ref(initialValue)

  async function withLoading<T>(fn: () => Promise<T>): Promise<T> {
    loading.value = true
    try {
      return await fn()
    } finally {
      loading.value = false
    }
  }

  function setLoading(value: boolean) {
    loading.value = value
  }

  return {
    loading,
    withLoading,
    setLoading,
  }
}

// 导出类型
export type UseLoadingReturn = ReturnType<typeof useLoading>
export type UseSimpleLoadingReturn = ReturnType<typeof useSimpleLoading>
