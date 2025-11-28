export type RangeLike = { start: number; end: number }

// 句子切分（简单中文/英文标点），尽量保留分隔符
export function splitIntoSentences(text: string): string[] {
  if (!text) return []
  const parts: string[] = []
  let buf = ''
  for (const ch of text) {
    buf += ch
    if (/^[。！？!?;；]\s?$/.test(ch)) {
      parts.push(buf)
      buf = ''
    }
  }
  if (buf) parts.push(buf)
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

/**
 * 将文件级的绝对区间 [absStart, absEnd) 映射到所属章节以及章节内的局部区间。
 * chapters 应包含 offset（在全文中的起始绝对位置）和 length（章节长度）。
 * 返回 null 表示无法映射到任何章节。
 */
export function mapAbsRangeToChapter(
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
    // 只要 absStart 落在章节范围内，就认为该区间属于此章节（书签通常不会跨章节）
    if (absStart >= chStart && absStart < chEnd) {
      const localStart = Math.max(0, absStart - chStart)
      const localEnd = Math.max(0, Math.min(len, absEnd - chStart))
      return { chapterIndex: i, localStart, localEnd }
    }
  }
  return null
}

/**
 * 将章节内的局部区间 [localStart, localEnd) 拆分成若干句子的范围，
 * 返回数组，每项包含 sentenceIndex（句子索引）和 start/end（句内偏移，半开区间）。
 */
export function splitLocalRangeToSentenceRanges(
  localStart: number,
  localEnd: number,
  sentenceOffsets: Array<{ start: number; end: number }>,
): Array<{ sentenceIndex: number; start: number; end: number }> {
  const res: Array<{ sentenceIndex: number; start: number; end: number }> = []
  if (!Array.isArray(sentenceOffsets) || sentenceOffsets.length === 0) return res
  const s = Math.max(0, localStart)
  const e = Math.max(s, localEnd)
  for (let i = 0; i < sentenceOffsets.length; i++) {
    const seg = sentenceOffsets[i]
    if (e <= seg.start || s >= seg.end) continue
    const segStart = Math.max(seg.start, s)
    const segEnd = Math.min(seg.end, e)
    res.push({ sentenceIndex: i, start: segStart - seg.start, end: segEnd - seg.start })
  }
  return res
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

// 优先级：搜索高亮 > 书签高亮
export function splitOverlappingRanges<
  T extends RangeLike & { bookmarkId?: number; color?: string | null; isSearch?: boolean },
>(ranges: Array<T>): Array<T> {
  if (ranges.length === 0) return []

  // 创建所有边界点的事件列表
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

  // 按位置排序，start 优先于 end
  events.sort((a, b) => {
    if (a.pos !== b.pos) return a.pos - b.pos
    return a.type === 'start' ? -1 : 1
  })

  const result: Array<T> = []
  const activeRanges: Set<T> = new Set()
  let lastPos = 0

  for (let i = 0; i < events.length; i++) {
    const evt = events[i]

    // 如果位置有变化且有活动范围，输出一个片段
    if (evt.pos > lastPos && activeRanges.size > 0) {
      // 选择优先级最高的范围（搜索 > 书签）
      const active = Array.from(activeRanges)
      const searchRange = active.find(r => (r as any).isSearch)
      const chosen = searchRange || active[0]

      result.push({
        ...(chosen as any),
        start: lastPos,
        end: evt.pos,
      })
    }

    // 更新活动范围集合
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

// 构建搜索正则表达式
export function buildSearchRegex(
  keyword: string,
  caseSensitive: boolean,
  wholeWord: boolean,
): RegExp {
  // 转义特殊字符
  let pattern = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

  if (wholeWord) {
    pattern = `\\b${pattern}\\b`
  }

  const flags = caseSensitive ? 'g' : 'gi'
  return new RegExp(pattern, flags)
}

// 提取搜索预览，带高亮的 HTML 片段
export function extractSearchPreview(
  content: string,
  position: number,
  matchedText: string,
  contextLength = 10,
): string {
  const matchLength = matchedText.length
  const start = Math.max(0, position - contextLength)
  const end = Math.min(content.length, position + matchLength + contextLength)

  // 从原始内容中提取三个部分（在添加省略号和清理之前）
  const beforeMatch = content.substring(start, position)
  const matchPart = content.substring(position, position + matchLength)
  const afterMatch = content.substring(position + matchLength, end)

  // HTML 转义并清理换行符
  const escapeAndClean = (str: string) => {
    return escapeHtml(str).replace(/\n/g, ' ')
  }

  // 构建预览，添加省略号
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
