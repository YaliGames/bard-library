import { reactive, watch } from 'vue'
import type { UserSettings } from '@/api/settings'

const KEY = 'userSettings'

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

const savedRaw = localStorage.getItem(KEY)
const initial: UserSettings = savedRaw
  ? { ...defaultSettings, ...JSON.parse(savedRaw) }
  : defaultSettings

const state = reactive<UserSettings>({ ...initial })

export function useSettingsStore() {
  function setAll(s: UserSettings) {
    Object.assign(state, defaultSettings, s)
  }
  function update(patch: Partial<UserSettings>) {
    Object.assign(state, patch)
  }
  // 本地持久化
  watch(
    state,
    v => {
      localStorage.setItem(KEY, JSON.stringify(v))
    },
    { deep: true },
  )

  return { state, setAll, update }
}
