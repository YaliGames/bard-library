import type { FileRec } from '@/api/types'

/**
 * Convert bytes to human-readable file size
 * @param bytes - File size in bytes
 * @returns Formatted string like "1.5 MB"
 */
export function humanSize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  let value = bytes
  while (value >= 1024 && i < units.length - 1) {
    value /= 1024
    i++
  }
  return `${value.toFixed(1)} ${units[i]}`
}

/**
 * Get Material Icon name for file format
 * @param file - File record or format string
 * @returns Material icon name
 */
export function getFileIcon(file: FileRec | string): string {
  const format = typeof file === 'string' ? file : file.format || ''
  const fmt = format.toLowerCase()

  if (fmt === 'epub') return 'auto_stories'
  if (fmt === 'pdf') return 'picture_as_pdf'
  if (fmt === 'txt') return 'menu_book'
  return 'play_arrow'
}

/**
 * Get readable text for file read button
 * @param file - File record or format string
 * @param hasContinue - Whether user has reading progress
 * @returns Button text
 */
export function getFileReadText(file: FileRec | string, hasContinue = false): string {
  const format = typeof file === 'string' ? file : file.format || ''
  const fmt = format.toLowerCase()

  if (fmt === 'epub') return 'EPUB 阅读'
  if (fmt === 'pdf') return 'PDF 预览'
  if (fmt === 'txt') return hasContinue ? '继续阅读' : '开始阅读'
  return '开始阅读'
}

/**
 * Format file info for display
 * @param file - File record
 * @returns Formatted string like "EPUB, 2.5 MB"
 */
export function formatFileInfo(file: FileRec): string {
  const format = (file.format || '').toUpperCase()
  const size = file.size ? humanSize(file.size) : ''
  return size ? `${format}, ${size}` : format
}

/**
 * Get file format display label
 * @param file - File record
 * @returns Uppercase format or fallback
 */
export function getFileFormatLabel(file: FileRec): string {
  return (file.format || '').toUpperCase() || '文件'
}
