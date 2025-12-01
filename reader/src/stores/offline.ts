import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useOfflineStore = defineStore('offline', () => {
    const isOffline = ref(!navigator.onLine)

    function updateStatus() {
        isOffline.value = !navigator.onLine
    }

    function init() {
        window.addEventListener('online', updateStatus)
        window.addEventListener('offline', updateStatus)
        updateStatus()
    }

    function cleanup() {
        window.removeEventListener('online', updateStatus)
        window.removeEventListener('offline', updateStatus)
    }

    return {
        isOffline,
        init,
        cleanup,
    }
})
