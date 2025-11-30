export type RangeLike = { start: number; end: number }

// 按换行符拆分为段（保留换行符在段尾，除最后一段），以便构建与原文字符位置严格对应的偏移映射
export function splitByLines(text: string): string[] {
    if (!text) return []
    // 统一使用 '\n' 作为换行分隔符；保留换行符在每段末尾（除了最后一段）
    const parts: string[] = []
    let start = 0
    for (let i = 0; i < text.length; i++) {
        const ch = text[i]
        if (ch === '\r') {
            // 支持 CRLF 或 CR，统一处理为一处换行
            const next = text[i + 1]
            if (next === '\n') {
                parts.push(text.slice(start, i + 2))
                i += 1
                start = i + 1
            } else {
                parts.push(text.slice(start, i + 1))
                start = i + 1
            }
        } else if (ch === '\n') {
            parts.push(text.slice(start, i + 1))
            start = i + 1
        }
    }
    if (start < text.length) parts.push(text.slice(start))
    return parts
}

export function buildSentenceOffsets(sents: string[]): Array<{ start: number; end: number }> {
    const arr: Array<{ start: number; end: number }> = []
    let pos = 0
    for (const s of sents) {
        const start = pos
        const end = start + s.length
        arr.push({ start, end })
        pos = end
    }
    return arr
}

// 在全文中找到子串所有出现位置，考虑 \r\n/\r -> \n 和零宽字符过滤
export function findAllOccurrences(
    text: string,
    sub: string,
): Array<{ start: number; end: number }> {
    const normalizeWithMap = (t: string): { norm: string; map: number[] } => {
        const out: string[] = []
        const map: number[] = [0]
        let i = 0
        while (i < t.length) {
            const ch = t[i]
            if (ch === '\r') {
                if (t[i + 1] === '\n') {
                    out.push('\n')
                    i += 2
                    map.push(map[map.length - 1] + 2)
                } else {
                    out.push('\n')
                    i += 1
                    map.push(map[map.length - 1] + 1)
                }
                continue
            }
            if (ch === '\u200B' || ch === '\uFEFF') {
                i += 1
                continue
            }
            out.push(ch)
            i += 1
            map.push(map[map.length - 1] + 1)
        }
        return { norm: out.join(''), map }
    }

    const normalizeOnly = (t: string): string => normalizeWithMap(t).norm

    const res: Array<{ start: number; end: number }> = []
    if (!sub) return res
    const { norm: tN, map } = normalizeWithMap(text)
    const sN = normalizeOnly(sub)
    if (!sN) return res
    let idx = 0
    while (true) {
        const found = tN.indexOf(sN, idx)
        if (found === -1) break
        const startN = found
        const endN = found + sN.length
        const start = map[startN]
        const end = map[endN]
        res.push({ start, end })
        idx = endN
    }
    return res
}

export function clampRanges<T extends RangeLike>(ranges: Array<T>, maxLen: number): Array<T> {
    return ranges
        .map(r => ({
            ...(r as any),
            start: Math.max(0, Math.min(r.start, maxLen)),
            end: Math.max(0, Math.min(r.end, maxLen)),
        }))
        .filter((r: any) => r.end > r.start) as Array<T>
}

export function mapAbsToChapter(
    absStart: number,
    absEnd: number,
    chapters: Array<{ offset?: number; length?: number }>,
): { chapterIndex: number; localStart: number; localEnd: number } | null {
    if (!Array.isArray(chapters) || chapters.length === 0) return null
    for (let i = 0; i < chapters.length; i++) {
        const ch = chapters[i]
        const off = Number(ch.offset || 0)
        const len = Number(ch.length || 0)
        const chStart = off
        const chEnd = off + len
        if (absStart >= chStart && absStart < chEnd) {
            const localStart = Math.max(0, absStart - chStart)
            const localEnd = Math.max(0, Math.min(len, absEnd - chStart))
            return { chapterIndex: i, localStart, localEnd }
        }
    }
    return null
}

export function splitRangeToSegments(
    localStart: number,
    localEnd: number,
    offsets: Array<{ start: number; end: number }>,
): Array<{ idx: number; start: number; end: number }> {
    const out: Array<{ idx: number; start: number; end: number }> = []
    if (!Array.isArray(offsets) || offsets.length === 0) return out
    const s = Math.max(0, localStart)
    const e = Math.max(s, localEnd)
    for (let i = 0; i < offsets.length; i++) {
        const seg = offsets[i]
        if (e <= seg.start || s >= seg.end) continue
        const segStart = Math.max(seg.start, s)
        const segEnd = Math.min(seg.end, e)
        out.push({ idx: i, start: segStart - seg.start, end: segEnd - seg.start })
    }
    return out
}

export function mergeRanges<T extends RangeLike & { bookmarkId?: number; color?: string | null }>(
    ranges: Array<T>,
): Array<T> {
    if (ranges.length === 0) return []
    const arr = [...ranges].sort((a, b) => a.start - b.start)
    const res: Array<T> = []
    let cur: T = { ...(arr[0] as any) }
    const sameMeta = (a: T, b: T) =>
        (a.bookmarkId ?? null) === (b.bookmarkId ?? null) && (a.color ?? null) === (b.color ?? null)
    for (let i = 1; i < arr.length; i++) {
        const r = arr[i]
        if (r.start <= cur.end && sameMeta(cur, r)) {
            cur.end = Math.max(cur.end, r.end)
        } else {
            res.push(cur)
            cur = { ...(r as any) }
        }
    }
    res.push(cur)
    return res
}

export function splitOverlappingRanges<
    T extends RangeLike & { bookmarkId?: number; color?: string | null; isSearch?: boolean },
>(ranges: Array<T>): Array<T> {
    if (ranges.length === 0) return []

    interface Event {
        pos: number
        type: 'start' | 'end'
        range: T
    }

    const events: Event[] = []
    for (const r of ranges) {
        events.push({ pos: r.start, type: 'start', range: r })
        events.push({ pos: r.end, type: 'end', range: r })
    }

    events.sort((a, b) => {
        if (a.pos !== b.pos) return a.pos - b.pos
        return a.type === 'start' ? -1 : 1
    })

    const result: Array<T> = []
    const activeRanges: Set<T> = new Set()
    let lastPos = 0

    for (let i = 0; i < events.length; i++) {
        const evt = events[i]

        if (evt.pos > lastPos && activeRanges.size > 0) {
            const active = Array.from(activeRanges)
            const searchRange = active.find(r => (r as any).isSearch)
            const chosen = searchRange || active[0]

            result.push({
                ...(chosen as any),
                start: lastPos,
                end: evt.pos,
            })
        }

        if (evt.type === 'start') {
            activeRanges.add(evt.range)
        } else {
            activeRanges.delete(evt.range)
        }

        lastPos = evt.pos
    }

    return result
}

export function escapeHtml(s: string): string {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

export function buildSearchRegex(
    keyword: string,
    caseSensitive: boolean,
    wholeWord: boolean,
): RegExp {
    let pattern = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

    if (wholeWord) {
        pattern = `\\b${pattern}\\b`
    }

    const flags = caseSensitive ? 'g' : 'gi'
    return new RegExp(pattern, flags)
}

export function extractSearchPreview(
    content: string,
    position: number,
    matchedText: string,
    contextLength = 10,
): string {
    const matchLength = matchedText.length
    const start = Math.max(0, position - contextLength)
    const end = Math.min(content.length, position + matchLength + contextLength)

    const beforeMatch = content.substring(start, position)
    const matchPart = content.substring(position, position + matchLength)
    const afterMatch = content.substring(position + matchLength, end)

    const escapeAndClean = (str: string) => {
        return escapeHtml(str).replace(/\n/g, ' ')
    }

    let result = ''
    if (start > 0) result += '...'
    result += escapeAndClean(beforeMatch)
    result += '<strong class="text-yellow-600 dark:text-yellow-400">'
    result += escapeAndClean(matchPart)
    result += '</strong>'
    result += escapeAndClean(afterMatch)
    if (end < content.length) result += '...'

    return result
}
