import type { Ref } from 'vue'
import type { Book, Bookmark } from '@/api/types'
import type { Chapter } from '@/api/txt'
import type { CachedBook } from '@/utils/txtCache'

export type ThemeKey = 'light' | 'sepia' | 'dark'

export interface ReaderContext {
  // basic data
  chapters: Ref<Chapter[]>
  currentChapterIndex: Ref<number | null>
  bookmarks: Ref<Bookmark[]>
  filteredBookmarks: Ref<Bookmark[]>
  book: Ref<Book | {}>
  bookId: Ref<number>
  fileId: number // fileId comes from route, plain number
  bookTitle: Ref<string>

  // settings / UI state
  settings: Ref<{ fontSize: number; lineHeight: number; contentWidth: number; theme: ThemeKey }>
  settingsVisible: Ref<boolean>
  cachedBook: Ref<CachedBook | null>
  showCacheManager: Ref<boolean>
  loading: Ref<boolean>
  themeColors: Record<ThemeKey, { bg: string; fg: string }>
  userSettings: any

  // content
  content: Ref<string>
  sentences: Ref<string[]>
  contentRef: Ref<any>
  sentenceOffsets: Array<{ start: number; end: number }>

  // drawer / mobile
  leftTab: Ref<'chapters' | 'bookmarks'>
  mobileDrawerDefaultTab: Ref<'chapters' | 'bookmarks' | 'cache' | 'settings'>
  showMobileDrawer: Ref<boolean>
  mobileSearchVisible: Ref<boolean>
  mobileBottomBarVisible: Ref<boolean>

  // interaction state
  searchHighlight: Ref<any>
  markRanges: Map<
    number,
    Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>
  >
  markTick: Ref<number>
  showSelectionMenu: Ref<boolean>
  selectionMenuPos: Ref<{ x: number; y: number }>
  selectionActions: Ref<any>
  showHighlightMenu: Ref<boolean>
  highlightMenuPos: Ref<{ x: number; y: number }>
  currentHitBookmarkId: Ref<number | null>
  currentHitNote: Ref<string>
  currentHitColor: Ref<string | null>

  // computed helpers
  isLoggedIn: Ref<boolean>
  hasPrevChapter: Ref<boolean>
  hasNextChapter: Ref<boolean>
  autoScrollCategory: Ref<boolean | undefined>

  // refs for panels
  searchPanelRef: Ref<any>
  mobileSearchDrawerRef: Ref<any>
  searchVisible: Ref<boolean>
  // right-side tools panel
  rightPanelOpen: Ref<boolean>
  rightPanelTab: Ref<'none' | 'search' | 'cache' | 'settings'>

  // methods
  openChapter: (index: number) => Promise<void>
  openMobileDrawer: (tab?: 'chapters' | 'bookmarks' | 'cache' | 'settings') => void
  goPrevChapter: () => void
  goNextChapter: () => void
  backToBook: () => void
  goEditChapters: () => void
  jumpToBookmark: (b: Bookmark) => Promise<void>
  removeBookmarkConfirm: (b: Bookmark) => Promise<void>
  removeBookmark: (b: Bookmark) => Promise<void>
  loadBookmarksForChapter: () => Promise<void>
  loadCacheStatus: () => Promise<void>
  toggleSearch: () => void
  handleMobileSearchClose: () => void
  // settings update helpers (move mutation logic to page-level)
  updateTheme: (theme: ThemeKey) => void
  updateFontSize: (size: number) => void
  updateLineHeight: (height: number) => void
  updateContentWidth: (width: number) => void

  // search handlers
  handleSearchClose: () => void
  handleJumpToSearchResult: (r: any) => void
  handleChapterSearch: (k: string, cs: boolean, ww: boolean) => void
  handleGlobalSearch: (k: string, cs: boolean, ww: boolean) => Promise<void>

  // right-side panel control
  openRightPanel: (tab: 'search' | 'cache' | 'settings') => void
  closeRightPanel: () => void

  // selection / highlight
  onSelectionEvent: (p: any) => void
  onMarkClickEvent: (p: any) => void
  onAddNote: () => Promise<void>
  onPickColor: (c: any) => Promise<void>
  onDeleteFromMenu: () => void
}
