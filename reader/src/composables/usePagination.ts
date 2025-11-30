import { ref, computed } from 'vue'

export interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface PaginationResult<T> {
    data: T[]
    meta?: PaginationMeta
}

export interface UsePaginationOptions<T> {
    /** 数据获取函数 */
    fetcher: (page: number) => Promise<PaginationResult<T>>
    /** 初始页码 */
    initialPage?: number
    /** 成功回调 */
    onSuccess?: (data: T[]) => void
    /** 错误回调 */
    onError?: (error: any) => void
}

export function usePagination<T>(options: UsePaginationOptions<T>) {
    const { fetcher, initialPage = 1, onSuccess, onError } = options

    // State
    const loading = ref(false)
    const data = ref<T[]>([])
    const currentPage = ref(initialPage)
    const lastPage = ref(1)
    const total = ref(0)
    const perPage = ref(20)

    // Computed
    const hasNextPage = computed(() => currentPage.value < lastPage.value)
    const hasPrevPage = computed(() => currentPage.value > 1)
    const totalPages = computed(() => lastPage.value)
    const isEmpty = computed(() => data.value.length === 0)

    // Methods
    async function loadPage(page: number) {
        if (page < 1) return

        loading.value = true
        try {
            const result = await fetcher(page)
            data.value = result.data

            if (result.meta) {
                currentPage.value = result.meta.current_page
                lastPage.value = result.meta.last_page
                total.value = result.meta.total
                perPage.value = result.meta.per_page
            } else {
                currentPage.value = page
            }

            onSuccess?.(result.data)
        } catch (error) {
            onError?.(error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function nextPage() {
        if (hasNextPage.value) {
            await loadPage(currentPage.value + 1)
        }
    }

    async function prevPage() {
        if (hasPrevPage.value) {
            await loadPage(currentPage.value - 1)
        }
    }

    async function firstPage() {
        await loadPage(1)
    }

    async function lastPageFunc() {
        if (lastPage.value > 0) {
            await loadPage(lastPage.value)
        }
    }

    async function refresh() {
        await loadPage(currentPage.value)
    }

    async function reset() {
        await loadPage(initialPage)
    }

    return {
        // State
        loading,
        data,
        currentPage,
        lastPage,
        total,
        perPage,
        // Computed
        hasNextPage,
        hasPrevPage,
        totalPages,
        isEmpty,
        // Methods
        loadPage,
        nextPage,
        prevPage,
        firstPage,
        goToLastPage: lastPageFunc,
        refresh,
        reset,
    }
}

export type UsePaginationReturn<T> = ReturnType<typeof usePagination<T>>
