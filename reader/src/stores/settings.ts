import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { UserSettings } from '@/api/settings'

export const useSettingsStore = defineStore('settings', () => {
    const settings = ref<UserSettings>({
        bookList: {
            showReadTag: true,
            showMarkReadButton: false
        },
        bookDetail: {
            showReadTag: true
        },
        shelfDetail: {
            showFilters: true
        },
        txtReader: {
            autoScrollCategory: true
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
