import { ref, watch } from 'vue'
import type { ReaderSettings, ThemeKey } from '@/types/reader'

const SETTINGS_KEY = 'reader.settings.txt'

export const themeColors: Record<ThemeKey, { bg: string; fg: string }> = {
    light: { bg: '#ffffff', fg: '#333333' },
    sepia: { bg: '#f5ecd9', fg: '#3b2f1e' },
    dark: { bg: '#111111', fg: '#dddddd' },
}

export function useReaderSettings() {
    const settings = ref<ReaderSettings>({
        fontSize: 16,
        lineHeight: 1.7,
        contentWidth: 720,
        theme: 'light',
    })

    const settingsVisible = ref(false)

    function loadSettings() {
        try {
            const raw = localStorage.getItem(SETTINGS_KEY)
            if (raw) {
                const obj = JSON.parse(raw)
                settings.value = {
                    fontSize: Number(obj.fontSize) || 16,
                    lineHeight: Number(obj.lineHeight) || 1.7,
                    contentWidth: Number(obj.contentWidth) || 720,
                    theme: (['light', 'sepia', 'dark'].includes(obj.theme) ? obj.theme : 'light') as ThemeKey,
                }
            }
        } catch {
            // ignore
        }
    }

    function persistSettings() {
        try {
            localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings.value))
        } catch { }
    }

    // Auto persist
    watch(settings, persistSettings, { deep: true })

    // Helpers
    function updateTheme(theme: ThemeKey) {
        settings.value.theme = theme
    }

    function updateFontSize(size: number) {
        settings.value.fontSize = size
    }

    function updateLineHeight(height: number) {
        settings.value.lineHeight = height
    }

    function updateContentWidth(width: number) {
        settings.value.contentWidth = width
    }

    return {
        settings,
        settingsVisible,
        themeColors,
        loadSettings,
        updateTheme,
        updateFontSize,
        updateLineHeight,
        updateContentWidth,
    }
}
