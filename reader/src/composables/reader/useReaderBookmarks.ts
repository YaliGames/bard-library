import { ref, computed, reactive, watch, type Ref, nextTick } from 'vue'
import { bookmarksApi } from '@/api/bookmarks'
import type { Bookmark } from '@/api/types'
import { useUserStore } from '@/stores/user'
import { ElMessage, ElMessageBox } from 'element-plus'
import { mapAbsToChapter, splitRangeToSegments, findAllOccurrences } from '@/utils/reader'
import type { useReaderCore } from './useReaderCore'
import type { useReaderNavigation } from './useReaderNavigation'
import type { TxtContentInstance, SelectionAction } from '@/types/reader'

export function useReaderBookmarks(
    fileId: number,
    core: ReturnType<typeof useReaderCore>,
    nav: ReturnType<typeof useReaderNavigation>,
    contentRef: Ref<TxtContentInstance | null>,
) {
    const userStore = useUserStore()
    const isLoggedIn = computed(() => userStore.isLoggedIn)

    const bookmarks = ref<Bookmark[]>([])

    const filteredBookmarks = computed(() => {
        if (!core.bookId.value || !fileId || bookmarks.value.length === 0) return []
        return bookmarks.value.filter(b => {
            try {
                const loc = JSON.parse(b.location || '{}')
                const fid = Number(loc?.fileId ?? b.file_id ?? 0)
                return fid === fileId
            } catch {
                return false
            }
        })
    })

    // 句内精准标注：sentence index -> array of segments
    const markRanges = reactive(
        new Map<
            number,
            Array<{ start: number; end: number; bookmarkId?: number; color?: string | null }>
        >(),
    )
    const markTick = ref(0)

    // 选区状态
    const selectionRange = ref<{ start: number; end: number } | null>(null)
    const selectionTextBuffer = ref<string | null>(null)

    // 菜单状态
    const showSelectionMenu = ref(false)
    const selectionMenuPos = ref({ x: 0, y: 0 })
    const showHighlightMenu = ref(false)
    const highlightMenuPos = ref({ x: 0, y: 0 })

    const currentHitBookmarkId = ref<number | null>(null)
    const currentHitBookmark = computed(() =>
        bookmarks.value.find(b => b.id === currentHitBookmarkId.value),
    )
    const currentHitNote = computed(() => currentHitBookmark.value?.note || '')
    const currentHitColor = computed(() => currentHitBookmark.value?.color || null)

    const selectionActions = computed(() => {
        const acts: SelectionAction[] = []
        if (isLoggedIn.value) {
            if (selectionRange.value) {
                acts.push({
                    key: 'highlight',
                    label: '高亮',
                    onClick: () => {
                        addBookmark() // highlightSelection
                        hideSelectionMenu()
                    },
                })
            }
            acts.push({
                key: 'copy',
                label: '复制',
                onClick: () => {
                    copySelection()
                    hideSelectionMenu()
                },
            })
        } else {
            acts.push({
                key: 'copy',
                label: '复制',
                onClick: () => {
                    copySelection()
                    hideSelectionMenu()
                },
            })
        }
        return acts
    })

    async function loadBookmarks() {
        // Alias for loadBookmarksForChapter
        if (!isLoggedIn.value || !core.bookId.value) return
        try {
            const res = await bookmarksApi.list(core.bookId.value, fileId)
            bookmarks.value = res.bookmarks || []
            rebuildMarkRanges()
        } catch (e) {
            console.error(e)
        }
    }

    // 监听 bookId 变化，自动加载书签
    watch(
        () => core.bookId.value,
        newVal => {
            if (newVal) {
                loadBookmarks()
            }
        },
    )

    function rebuildMarkRanges() {
        markRanges.clear()
        if (!core.sentenceOffsets.value.length) return

        const currentIdx = nav.currentChapterIndex.value
        if (currentIdx === null) return

        for (const b of bookmarks.value) {
            try {
                const loc = JSON.parse(b.location || '{}')
                if (loc?.format !== 'txt') continue

                if (typeof loc.absStart === 'number' && typeof loc.absEnd === 'number') {
                    const mapped = mapAbsToChapter(loc.absStart, loc.absEnd, core.chapters.value)
                    if (!mapped || mapped.chapterIndex !== currentIdx) continue

                    const segs = splitRangeToSegments(
                        mapped.localStart,
                        mapped.localEnd,
                        core.sentenceOffsets.value,
                    )
                    for (const seg of segs) {
                        const arr = markRanges.get(seg.idx) || []
                        arr.push({
                            start: seg.start,
                            end: seg.end,
                            bookmarkId: b.id,
                            color: b.color || null,
                        })
                        markRanges.set(seg.idx, arr)
                    }
                }
            } catch { }
        }
        markTick.value++
    }

    watch(() => nav.currentChapterIndex.value, rebuildMarkRanges)
    watch(() => core.sentenceOffsets.value, rebuildMarkRanges)

    async function copySelection() {
        let text = ''
        if (selectionTextBuffer.value) {
            text = selectionTextBuffer.value
        } else if (selectionRange.value && core.sentences.value.length > 0) {
            const s = Math.min(selectionRange.value.start, selectionRange.value.end)
            const e = Math.max(selectionRange.value.start, selectionRange.value.end)
            text = core.sentences.value.slice(s, e + 1).join('')
        } else {
            return
        }
        try {
            await navigator.clipboard.writeText(text)
        } catch { }
        hideSelectionMenu()
    }

    async function addBookmark(color?: string) {
        if (!selectionRange.value || !core.bookId.value || nav.currentChapterIndex.value === null)
            return

        const currentIdx = nav.currentChapterIndex.value
        const chap = core.chapters.value[currentIdx]
        if (!chap) return

        const s = Math.min(selectionRange.value.start, selectionRange.value.end)
        const e = Math.max(selectionRange.value.start, selectionRange.value.end)

        const selectionText =
            selectionTextBuffer.value && selectionTextBuffer.value.length > 0
                ? selectionTextBuffer.value
                : core.sentences.value.slice(s, e + 1).join('')

        // 计算绝对偏移锚点
        const baseOffset = chap.offset ?? 0
        let absStart: number | null = null
        let absEnd: number | null = null
        const addedBySentence = new Map<number, Array<{ start: number; end: number }>>()

        if (selectionText) {
            const occ = findAllOccurrences(core.content.value, selectionText)

            let chosen: { start: number; end: number } | undefined = undefined
            if (occ.length > 0) {
                const sentStart = core.sentenceOffsets.value[s]?.start ?? 0
                const sentEnd = core.sentenceOffsets.value[e]?.end ?? Infinity
                chosen = occ.find(o => !(o.end <= sentStart || o.start >= sentEnd)) || occ[0]
                absStart = baseOffset + chosen.start
                absEnd = baseOffset + chosen.end
            }

            if (chosen) {
                const r = chosen
                for (let i = 0; i < core.sentenceOffsets.value.length; i++) {
                    const seg = core.sentenceOffsets.value[i]
                    if (r.end <= seg.start || r.start >= seg.end) continue
                    const localStart = Math.max(0, r.start - seg.start)
                    const localEnd = Math.min(seg.end - seg.start, r.end - seg.start)
                    const addArr = addedBySentence.get(i) || []
                    addArr.push({ start: localStart, end: localEnd })
                    addedBySentence.set(i, addArr)
                }
            }
        }

        for (const [i, arrAdded] of addedBySentence.entries()) {
            const cur = markRanges.get(i) || []
            cur.push(
                ...arrAdded.map(r => ({ start: r.start, end: r.end, bookmarkId: undefined, color: null })),
            )
            markRanges.set(i, cur)
        }
        markTick.value++

        // 关闭菜单
        selectionRange.value = null
        showSelectionMenu.value = false

        try {
            const payload: Partial<Bookmark> = {
                location: JSON.stringify({ format: 'txt', fileId, absStart, absEnd, selectionText }),
                file_id: fileId,
                color: color || null,
            }
            const b = await bookmarksApi.create(core.bookId.value, payload, fileId)
            bookmarks.value.push(b)

            // 回填 ID
            for (const [i, arrAdded] of addedBySentence.entries()) {
                const cur = markRanges.get(i) || []
                for (const seg of cur) {
                    if (
                        arrAdded.some(a => a.start === seg.start && a.end === seg.end) &&
                        seg.bookmarkId == null
                    ) {
                        seg.bookmarkId = b.id
                        seg.color = b.color || null
                    }
                }
                markRanges.set(i, cur)
            }
            markTick.value++
        } catch (e) {
            console.error(e)
            ElMessage.error('添加书签失败')
            // 回滚
            for (const [i, arrAdded] of addedBySentence.entries()) {
                const cur = markRanges.get(i) || []
                if (cur.length === 0) continue
                const filtered = cur.filter(
                    r => !arrAdded.some(a => a.start === r.start && a.end === r.end),
                )
                if (filtered.length > 0) markRanges.set(i, filtered)
                else markRanges.delete(i)
            }
            markTick.value++
        }
    }

    async function removeBookmark(b: Bookmark) {
        try {
            await bookmarksApi.remove(core.bookId.value, b.id)
            bookmarks.value = bookmarks.value.filter(x => x.id !== b.id)
            rebuildMarkRanges()
            ElMessage.success('已删除')
        } catch {
            ElMessage.error('删除失败')
        }
    }

    function removeBookmarkConfirm(b: Bookmark) {
        ElMessageBox.confirm('是否删除该书签？', '删除确认', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning',
        })
            .then(() => {
                removeBookmark(b)
            })
            .catch(() => { })
    }

    function onDeleteFromMenu() {
        if (!currentHitBookmark.value) return
        removeBookmarkConfirm(currentHitBookmark.value)
        hideHighlightMenu()
    }

    async function onAddNote() {
        if (!isLoggedIn.value || !core.bookId.value || !currentHitBookmark.value) return
        const text = window.prompt('添加/修改批注', currentHitBookmark.value.note || '')
        if (text == null) return
        try {
            const updated = await bookmarksApi.update(core.bookId.value, currentHitBookmark.value.id, {
                note: text,
            })
            updateLocalBookmark(updated)
            hideHighlightMenu()
        } catch { }
    }

    async function onPickColor(color: string | Event) {
        if (!isLoggedIn.value || !core.bookId.value || !currentHitBookmark.value) return
        const picked = typeof color === 'string' ? color : (color as any)?.target?.value
        if (!picked) return
        try {
            const updated = await bookmarksApi.update(core.bookId.value, currentHitBookmark.value.id, {
                color: picked,
            })
            updateLocalBookmark(updated)
            // 更新 markRanges 中颜色
            for (const [sid, arr] of markRanges.entries()) {
                let changed = false
                for (const seg of arr) {
                    if (seg.bookmarkId === updated.id) {
                        seg.color = (updated as any).color || null
                        changed = true
                    }
                }
                if (changed) markRanges.set(sid, arr)
            }
            markTick.value++
            hideHighlightMenu()
        } catch { }
    }

    function updateLocalBookmark(updated: Bookmark) {
        const idx = bookmarks.value.findIndex(b => b.id === updated.id)
        if (idx >= 0) {
            bookmarks.value[idx] = Object.assign({}, bookmarks.value[idx], updated)
        }
    }

    async function jumpToBookmark(b: Bookmark) {
        try {
            const loc = JSON.parse(b.location || '{}')
            const text = typeof loc.selectionText === 'string' ? loc.selectionText : undefined

            if (
                typeof loc?.absStart === 'number' &&
                typeof loc?.absEnd === 'number' &&
                core.chapters.value.length > 0
            ) {
                const absStart = Number(loc.absStart)
                const absEnd = Number(loc.absEnd)
                const mapped = mapAbsToChapter(absStart, absEnd, core.chapters.value)
                if (!mapped) return

                if (mapped.chapterIndex !== nav.currentChapterIndex.value) {
                    await nav.openChapter(mapped.chapterIndex)
                    await nextTick()
                    await new Promise(resolve => requestAnimationFrame(resolve))
                    scrollToMapped(mapped, text)
                    return
                }

                scrollToMapped(mapped, text)
                return
            }
        } catch { }
    }

    function scrollToMapped(mapped: any, text?: string) {
        const localPos = mapped.localStart
        const localEndPos = Math.max(0, mapped.localEnd - 1)
        let targetSid: number | undefined = undefined
        let endSid: number | undefined = undefined

        const offsets = core.sentenceOffsets.value
        for (let i = 0; i < offsets.length; i++) {
            const seg = offsets[i]
            if (targetSid === undefined && localPos >= seg.start && localPos < seg.end) {
                targetSid = i
            }
            if (endSid === undefined && localEndPos >= seg.start && localEndPos < seg.end) {
                endSid = i
            }
            if (targetSid !== undefined && endSid !== undefined) break
        }

        contentRef.value?.scrollToTarget({
            startSid: targetSid,
            endSid,
            selectionText: text,
            matchPosition: mapped.localStart,
            matchLength: mapped.localEnd - mapped.localStart,
        })
    }

    function hideSelectionMenu() {
        showSelectionMenu.value = false
        selectionRange.value = null
    }

    function hideHighlightMenu() {
        showHighlightMenu.value = false
        currentHitBookmarkId.value = null
    }

    function hideAllMenus() {
        hideSelectionMenu()
        hideHighlightMenu()
    }

    function onDocMouseDown() {
        hideAllMenus()
    }

    function onSelectionEvent(p: {
        show: boolean
        x?: number
        y?: number
        range?: { start: number; end: number } | null
        text?: string | null
    }) {
        if (p.range) selectionRange.value = p.range
        selectionTextBuffer.value = p.text ?? null
        if (typeof p.x === 'number' && typeof p.y === 'number')
            selectionMenuPos.value = { x: p.x, y: p.y }
        showSelectionMenu.value = !!p.show
    }

    function onMarkClickEvent(p: {
        show: boolean
        x?: number
        y?: number
        bookmarkId?: number | null
    }) {
        hideSelectionMenu()
        if (typeof p.x === 'number' && typeof p.y === 'number')
            highlightMenuPos.value = { x: p.x, y: p.y }
        currentHitBookmarkId.value = p.bookmarkId ?? null
        showHighlightMenu.value = !!(p.show && currentHitBookmarkId.value != null)
    }

    return {
        bookmarks,
        filteredBookmarks,
        markRanges,
        markTick,
        selectionRange,
        selectionTextBuffer,
        showSelectionMenu,
        selectionMenuPos,
        selectionActions,
        showHighlightMenu,
        highlightMenuPos,
        currentHitBookmarkId,
        currentHitBookmark,
        currentHitNote,
        currentHitColor,
        loadBookmarks,
        loadBookmarksForChapter: loadBookmarks, // Alias
        addBookmark,
        removeBookmark,
        removeBookmarkConfirm,
        hideSelectionMenu,
        hideHighlightMenu,
        hideAllMenus,
        rebuildMarkRanges,
        onSelectionEvent,
        onMarkClickEvent,
        onAddNote,
        onPickColor,
        onDeleteFromMenu,
        jumpToBookmark,
        onDocMouseDown,
    }
}
