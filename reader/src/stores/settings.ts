import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSettingsStore = defineStore('settings', () => {
    const settings = ref({
        bookList: {
            showReadTag: true,
            showMarkReadButton: true
        },
        preferences: {
            expandFilterMenu: false
        }
    })

    return {
        settings
    }
}, {
    persist: true
})
