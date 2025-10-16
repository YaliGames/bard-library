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
export function findAllOccurrences(text: string, sub: string): Array<{ start: number; end: number }> {
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

export function mergeRanges<T extends RangeLike & { bookmarkId?: number; color?: string | null }>(ranges: Array<T>): Array<T> {
  if (ranges.length === 0) return []
  const arr = [...ranges].sort((a, b) => a.start - b.start)
  const res: Array<T> = []
  let cur: T = { ...(arr[0] as any) }
  const sameMeta = (a: T, b: T) => (a.bookmarkId ?? null) === (b.bookmarkId ?? null) && (a.color ?? null) === (b.color ?? null)
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

export function escapeHtml(s: string): string {
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}
