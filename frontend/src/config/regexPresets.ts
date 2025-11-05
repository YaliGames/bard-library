export interface RegexPreset {
  id: string
  name: string
  pattern: string // 可为裸正则（无需分隔符），服务端会自动包裹 /.../miu
  note?: string
}

export const builtinRegexPresets: RegexPreset[] = [
  {
    id: 'cn-chapter-0',
    name: '中文·第/卷 N 章/回/部/节/集/卷',
    pattern: '^\\s*[第卷][0123456789一二三四五六七八九十零〇百千两]*[章回部节集卷].*',
  },
  {
    id: 'cn-chapter-1',
    name: '中文·第 N 章/回/部/节/集/卷',
    pattern: '^\\s*第[0123456789一二三四五六七八九十零〇百千两]*[章回部节集卷].*',
  },
  {
    id: 'cn-chapter-2',
    name: '中文·第 N',
    pattern: '^\\s*第[0123456789一二三四五六七八九十零〇百千两]+.*',
  },
  {
    id: 'cn-volume',
    name: '中文·卷/篇 N',
    pattern: '^\\s*(卷|篇)[0123456789一二三四五六七八九十零〇百千两]*.*',
  },
  {
    id: 'en-chapter-1',
    name: 'English·Chapter N',
    pattern: '^\\s*Chapter\\s*\\d+.*',
  },
  {
    id: 'en-chapter-2',
    name: 'English·CHAPTER N',
    pattern: '^\\s*CHAPTER\\s*\\d+.*',
  },
  {
    id: 'number-dot',
    name: '数字序号·N. 标题',
    pattern: '^\\s*\\d+\\..*',
  },
]

export const MY_PRESETS_STORAGE_KEY = 'txt_regex_presets'
