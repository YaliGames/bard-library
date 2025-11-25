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
