import { defineStore } from 'pinia'
import { reactive, watch } from 'vue'
import type { UserSettings } from '@/api/settings'

const STORAGE_KEY = 'userSettings'

export const defaultSettings: UserSettings = {
  bookList: {
    showReadTag: true,
    showMarkReadButton: true,
  },
  bookDetail: {
    showReadTag: true,
  },
  txtReader: {
    autoScrollCategory: true,
  },
  preferences: {
    expandFilterMenu: false,
  },
}

function loadSettings(): UserSettings {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    return saved ? { ...defaultSettings, ...JSON.parse(saved) } : defaultSettings
  } catch (error) {
    console.error('Failed to load settings:', error)
    return defaultSettings
  }
}

export const useSettingsStore = defineStore('settings', () => {
  const settings = reactive<UserSettings>(loadSettings())

  let saveTimer: number | null = null
  function debouncedSave() {
    if (saveTimer) clearTimeout(saveTimer)
    saveTimer = window.setTimeout(() => {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(settings))
      } catch (error) {
        console.error('Failed to save settings:', error)
      }
    }, 500)
  }

  watch(settings, debouncedSave, { deep: true })

  function update(patch: Partial<UserSettings>) {
    Object.assign(settings, patch)
  }

  function reset() {
    Object.assign(settings, defaultSettings)
  }

  function setAll(newSettings: UserSettings) {
    Object.assign(settings, defaultSettings, newSettings)
  }

  return {
    settings,
    update,
    reset,
    setAll,
  }
})
