import { ref, computed } from 'vue'

export function useLoading() {
    const loadingStates = ref<Set<string>>(new Set())

    const isLoading = computed(() => loadingStates.value.size > 0)
    const loadingKeys = computed(() => Array.from(loadingStates.value))

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

    async function withLoading<T>(key: string, fn: () => Promise<T>): Promise<T> {
        startLoading(key)
        try {
            return await fn()
        } finally {
            stopLoading(key)
        }
    }

    function createLoadingFn<T extends (...args: any[]) => Promise<any>>(key: string, fn: T): T {
        return (async (...args: any[]) => {
            return await withLoading(key, () => fn(...args))
        }) as T
    }

    return {
        isLoading,
        loadingKeys,
        startLoading,
        stopLoading,
        isLoadingKey,
        clearAll,
        withLoading,
        createLoadingFn,
    }
}

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

export type UseLoadingReturn = ReturnType<typeof useLoading>
export type UseSimpleLoadingReturn = ReturnType<typeof useSimpleLoading>
