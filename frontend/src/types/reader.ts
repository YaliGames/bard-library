import type { ComponentPublicInstance } from 'vue'

export type ThemeKey = 'light' | 'sepia' | 'dark'

export interface ReaderSettings {
  fontSize: number
  lineHeight: number
  contentWidth: number
  theme: ThemeKey
}

export interface SearchHighlightOptions {
  keyword: string
  caseSensitive: boolean
  wholeWord: boolean
}

export interface SelectionAction {
  key: string
  label: string
  onClick: () => void
}

export interface TxtContentInstance extends ComponentPublicInstance {
  scrollToTarget: (opts: {
    startSid?: number
    endSid?: number
    selectionText?: string
    isSearchJump?: boolean
    matchPosition?: number
    matchLength?: number
  }) => void
}

export interface SearchPanelInstance {
  setGlobalResults: (results: SearchResult[]) => void
  setSearching: (value: boolean) => void
}

export interface SelectionEventPayload {
  show: boolean
  x?: number
  y?: number
  range?: { start: number; end: number } | null
  text?: string | null
}

export interface MarkClickEventPayload {
  show: boolean
  x?: number
  y?: number
  bookmarkId?: number | null
}

export type ColorPickPayload = string | Event

/**
 * 搜索结果接口
 */
export interface SearchResult {
  chapterIndex: number
  chapterTitle: string | null | undefined
  position: number // 在章节内容中的字符位置
  matchLength: number // 匹配文本的长度
  sentenceIndex?: number // 句子索引
  preview: string // 预览文本
}
