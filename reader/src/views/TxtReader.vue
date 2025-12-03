<template>
  <TxtReaderDesktop
    v-if="!isMobileView"
    @jump-to-result="handleJumpToSearchResult"
    @search-chapter="handleChapterSearch"
    @search-global="handleGlobalSearch"
  >
    <ReaderBody />
  </TxtReaderDesktop>

  <TxtReaderMobile
    v-else
    @jump-to-result="handleJumpToSearchResult"
    @search-chapter="handleChapterSearch"
    @search-global="handleGlobalSearch"
  >
    <ReaderBody />
  </TxtReaderMobile>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, provide, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useSettingsStore } from '@/stores/settings'
import { useUserStore } from '@/stores/user'

// Components
import TxtReaderDesktop from '@/components/Reader/Txt/Desktop/TxtReaderDesktop.vue'
import TxtReaderMobile from '@/components/Reader/Txt/Mobile/TxtReaderMobile.vue'
import ReaderBody from '@/components/Reader/Txt/Shared/ReaderBody.vue'

// Composables
import { useReaderCore } from '@/composables/reader/useReaderCore'
import { useReaderSettings } from '@/composables/reader/useReaderSettings'
import { useReaderNavigation } from '@/composables/reader/useReaderNavigation'
import { useReaderBookmarks } from '@/composables/reader/useReaderBookmarks'
import { useReaderSearch } from '@/composables/reader/useReaderSearch'

// Types
import type { TxtContentInstance, SearchPanelInstance } from '@/types/reader'
import type { ReaderContext } from '@/types/readerContext'

// Refs
const contentRef = ref<TxtContentInstance | null>(null)
const searchPanelRef = ref<SearchPanelInstance | null>(null)
const mobileSearchDrawerRef = ref<SearchPanelInstance | null>(null)

const route = useRoute()
// const router = useRouter()
const userStore = useUserStore()
const settingsStore = useSettingsStore()
const fileId = Number(route.params.id)

// UI State
const rightPanelOpen = ref(false)
const rightPanelTab = ref<'none' | 'search' | 'cache' | 'settings'>('none')
const showMobileDrawer = ref(false)
const mobileDrawerDefaultTab = ref<'chapters' | 'bookmarks' | 'cache' | 'settings'>('chapters')
const mobileBottomBarVisible = ref(true)
const isMobileView = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false)

// Composables
const settings = useReaderSettings()
const core = useReaderCore(fileId)
const navigation = useReaderNavigation(fileId, core)
const bookmarks = useReaderBookmarks(fileId, core, navigation, contentRef)

function openRightPanel(tab: 'search' | 'cache' | 'settings') {
  rightPanelTab.value = tab
  rightPanelOpen.value = true
}

function closeRightPanel() {
  rightPanelOpen.value = false
  rightPanelTab.value = 'none'
}

const search = useReaderSearch(
  fileId,
  core,
  navigation,
  contentRef,
  searchPanelRef,
  mobileSearchDrawerRef,
  () => openRightPanel('cache'),
)

// Mobile & UI Helpers
function updateIsMobileView() {
  try {
    isMobileView.value = window.innerWidth < 768
  } catch {}
}

function openMobileDrawer(tab: 'chapters' | 'bookmarks' | 'cache' | 'settings' = 'chapters') {
  mobileDrawerDefaultTab.value = tab
  showMobileDrawer.value = true
}

function toggleSearch() {
  const isMobile = window.innerWidth < 768
  if (isMobile) {
    if (search.mobileSearchVisible.value) {
      search.handleMobileSearchClose()
    } else {
      search.mobileSearchVisible.value = true
    }
  } else {
    if (rightPanelOpen.value) {
      closeRightPanel()
    } else {
      openRightPanel('search')
    }
  }
}

let lastScrollY = 0
function handleMobileScroll() {
  const currentScrollY = window.scrollY
  const viewportHeight = window.innerHeight
  const docHeight = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight)
  const distanceToBottom = docHeight - (currentScrollY + viewportHeight)
  const bottomThreshold = 120

  if (distanceToBottom <= bottomThreshold) {
    mobileBottomBarVisible.value = true
  } else if (currentScrollY > lastScrollY && currentScrollY > 100) {
    mobileBottomBarVisible.value = false
  } else if (currentScrollY < lastScrollY) {
    mobileBottomBarVisible.value = true
  }
  lastScrollY = currentScrollY
}

function onKeyDown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !e.shiftKey) {
    e.preventDefault()
    openRightPanel('search')
  } else if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
    e.preventDefault()
    openRightPanel('search')
  } else if (e.key === 'Escape' && rightPanelOpen.value) {
    closeRightPanel()
  }
}

// Lifecycle
onMounted(async () => {
  settings.loadSettings()
  core.resolveInitialContext()
  await core.loadCacheStatus()
  await core.loadChapters({ keepLoading: true })
  await navigation.initializeNavigation()

  window.addEventListener('scroll', handleMobileScroll)
  window.addEventListener('resize', updateIsMobileView)
  document.addEventListener('keydown', onKeyDown)

  window.addEventListener('scroll', bookmarks.hideAllMenus, { passive: true })
  window.addEventListener('resize', bookmarks.hideAllMenus)
  document.addEventListener('mousedown', bookmarks.onDocMouseDown)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleMobileScroll)
  window.removeEventListener('resize', updateIsMobileView)
  document.removeEventListener('keydown', onKeyDown)
  window.removeEventListener('scroll', bookmarks.hideAllMenus)
  window.removeEventListener('resize', bookmarks.hideAllMenus)
  document.removeEventListener('mousedown', bookmarks.onDocMouseDown)
})

// Context Construction
const readerContext: ReaderContext = {
  // Core
  fileId: core.fileId,
  bookId: core.bookId,
  book: core.book,
  chapters: core.chapters,
  currentChapterIndex: navigation.currentChapterIndex,
  content: core.content,
  sentences: core.sentences,
  sentenceOffsets: core.sentenceOffsets,
  loading: core.loading,
  err: core.err,
  cachedBook: core.cachedBook,
  contentRef, // Use local ref
  bookTitle: core.bookTitle,

  // Settings
  settings: settings.settings,
  themeColors: settings.themeColors,
  userSettings: settingsStore.settings,
  settingsVisible: settings.settingsVisible,
  updateTheme: settings.updateTheme,
  updateFontSize: settings.updateFontSize,
  updateLineHeight: settings.updateLineHeight,
  updateContentWidth: settings.updateContentWidth,

  // Navigation
  hasPrevChapter: navigation.hasPrevChapter,
  hasNextChapter: navigation.hasNextChapter,
  openChapter: navigation.openChapter,
  goPrevChapter: navigation.goPrevChapter,
  goNextChapter: navigation.goNextChapter,
  backToBook: navigation.backToBook,
  goEditChapters: navigation.goEditChapters,

  // Bookmarks
  bookmarks: bookmarks.filteredBookmarks,
  filteredBookmarks: bookmarks.filteredBookmarks,
  markRanges: bookmarks.markRanges,
  markTick: bookmarks.markTick,
  showSelectionMenu: bookmarks.showSelectionMenu,
  selectionMenuPos: bookmarks.selectionMenuPos,
  selectionActions: bookmarks.selectionActions,
  showHighlightMenu: bookmarks.showHighlightMenu,
  highlightMenuPos: bookmarks.highlightMenuPos,
  currentHitBookmarkId: bookmarks.currentHitBookmarkId,
  currentHitNote: bookmarks.currentHitNote,
  currentHitColor: bookmarks.currentHitColor,
  onSelectionEvent: bookmarks.onSelectionEvent,
  onMarkClickEvent: bookmarks.onMarkClickEvent,
  onAddNote: bookmarks.onAddNote,
  onPickColor: bookmarks.onPickColor,
  onDeleteFromMenu: bookmarks.onDeleteFromMenu,
  jumpToBookmark: bookmarks.jumpToBookmark,
  removeBookmarkConfirm: bookmarks.removeBookmarkConfirm,
  removeBookmark: bookmarks.removeBookmark,
  loadBookmarksForChapter: bookmarks.loadBookmarksForChapter,

  // Search & UI
  searchVisible: search.searchVisible,
  mobileSearchVisible: search.mobileSearchVisible,
  searchHighlight: search.searchHighlight,
  searchPanelRef,
  mobileSearchDrawerRef,
  rightPanelOpen,
  rightPanelTab,
  openRightPanel,
  closeRightPanel,
  toggleSearch,
  handleSearchClose: search.handleSearchClose,
  handleMobileSearchClose: search.handleMobileSearchClose,
  handleChapterSearch: search.handleChapterSearch,
  handleGlobalSearch: search.handleGlobalSearch,
  handleJumpToSearchResult: search.handleJumpToSearchResult,

  // Mobile UI
  showMobileDrawer,
  mobileDrawerDefaultTab,
  openMobileDrawer,
  mobileBottomBarVisible,

  // Misc
  isLoggedIn: computed(() => userStore.isLoggedIn),
  autoScrollCategory: computed(() => settingsStore.settings.txtReader?.autoScrollCategory),
  showCacheManager: core.showCacheManager,
  loadCacheStatus: core.loadCacheStatus,

  // Left Tab (Legacy support if needed, or remove if unused)
  leftTab: ref('chapters'),
}

// Expose methods to template via context
const { handleJumpToSearchResult, handleChapterSearch, handleGlobalSearch } = search

provide('readerContext', readerContext)
</script>
