import { ref, type Ref } from 'vue'
import { ElMessage } from 'element-plus'
import { extractSearchPreview, buildSearchRegex } from '@/utils/reader'
import type { SearchResult, SearchHighlightOptions, TxtContentInstance, SearchPanelInstance } from '@/types/reader'
import type { useReaderCore } from '@/composables/reader/useReaderCore'
import type { useReaderNavigation } from '@/composables/reader/useReaderNavigation'

export function useReaderSearch(
  fileId: number,
  core: ReturnType<typeof useReaderCore>,
  nav: ReturnType<typeof useReaderNavigation>,
  contentRef: Ref<TxtContentInstance | null>,
  searchPanelRef: Ref<SearchPanelInstance | null>,
  mobileSearchDrawerRef: Ref<SearchPanelInstance | null>,
  openCachePanel: () => void
) {
  const searchVisible = ref(false)
  const mobileSearchVisible = ref(false)
  const searchHighlight = ref<SearchHighlightOptions | null>(null)

  function handleChapterSearch(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
    if (!keyword) {
      searchHighlight.value = null
      return
    }
    searchHighlight.value = { keyword, caseSensitive, wholeWord }
    
    const results: SearchResult[] = []
    const regex = buildSearchRegex(keyword, caseSensitive, wholeWord)
    const content = core.content.value
    const matches = [...content.matchAll(regex)]
    
    const currentIdx = nav.currentChapterIndex.value || 0
    const title = core.chapters.value[currentIdx]?.title
    
    matches.forEach(m => {
      results.push({
        chapterIndex: currentIdx,
        chapterTitle: title,
        position: m.index!,
        matchLength: m[0].length,
        preview: extractSearchPreview(content, m.index!, m[0])
      })
    })
    
    searchPanelRef.value?.setGlobalResults(results)
    mobileSearchDrawerRef.value?.setGlobalResults(results)
  }

  async function handleGlobalSearch(keyword: string, caseSensitive: boolean, wholeWord: boolean) {
    if (!keyword) return

    if (!core.cachedBook.value) {
      ElMessage.warning('全文搜索需要先缓存书籍内容，请先缓存后再试')
      openCachePanel()
      searchPanelRef.value?.setSearching(false)
      mobileSearchDrawerRef.value?.setSearching(false)
      return
    }

    searchHighlight.value = { keyword, caseSensitive, wholeWord }
    searchPanelRef.value?.setSearching(true)
    mobileSearchDrawerRef.value?.setSearching(true)

    try {
      const regex = buildSearchRegex(keyword, caseSensitive, wholeWord)
      const results: SearchResult[] = []

      // 简单的分片处理以避免阻塞 UI
      const chunkSize = 10
      const chapters = core.chapters.value
      
      for (let i = 0; i < chapters.length; i += chunkSize) {
        await new Promise(resolve => setTimeout(resolve, 0)) // Yield to UI
        
        const end = Math.min(i + chunkSize, chapters.length)
        for (let j = i; j < end; j++) {
          const chapter = chapters[j]
          const content = core.cachedBook.value.contents.get(j)
          if (!content) continue

          let match
          // 重置 lastIndex
          regex.lastIndex = 0
          // 限制每章匹配数量，防止过多
          let count = 0
          while ((match = regex.exec(content)) !== null && count < 50) {
            results.push({
              chapterIndex: j,
              chapterTitle: chapter.title,
              position: match.index,
              matchLength: match[0].length,
              preview: extractSearchPreview(content, match.index, match[0])
            })
            count++
          }
        }
      }
      
      searchPanelRef.value?.setGlobalResults(results)
      mobileSearchDrawerRef.value?.setGlobalResults(results)
    } catch (e) {
      console.error(e)
    } finally {
      searchPanelRef.value?.setSearching(false)
      mobileSearchDrawerRef.value?.setSearching(false)
    }
  }

  function handleJumpToSearchResult(result: SearchResult) {
    const targetChapterIndex = result.chapterIndex
    
    if (targetChapterIndex !== nav.currentChapterIndex.value) {
      nav.openChapter(targetChapterIndex).then(() => {
        // 等待内容加载和渲染
        setTimeout(() => {
          contentRef.value?.scrollToTarget({
            matchPosition: result.position,
            matchLength: result.matchLength,
            isSearchJump: true
          })
        }, 100)
      })
    } else {
      contentRef.value?.scrollToTarget({
        matchPosition: result.position,
        matchLength: result.matchLength,
        isSearchJump: true
      })
    }
    
    // 移动端关闭搜索抽屉
    if (mobileSearchVisible.value) {
      mobileSearchVisible.value = false
    }
  }

  function toggleSearch() {
    searchVisible.value = !searchVisible.value
  }

  function handleSearchClose() {
    searchVisible.value = false
    searchHighlight.value = null
  }

  function handleMobileSearchClose() {
    mobileSearchVisible.value = false
    searchHighlight.value = null
  }

  return {
    searchVisible,
    mobileSearchVisible,
    searchHighlight,
    handleChapterSearch,
    handleGlobalSearch,
    handleJumpToSearchResult,
    toggleSearch,
    handleSearchClose,
    handleMobileSearchClose
  }
}
