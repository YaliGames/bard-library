export function parseSizeToBytes(input: string | number | null | undefined): number | null {
  if (input === null || input === undefined) return null
  if (typeof input === 'number' && Number.isFinite(input)) return Math.floor(input)
  const v = String(input).trim()
  const m = v.match(/^([\d.]+)\s*(B|KB|MB|GB|TB)?$/i)
  if (!m) return null
  const num = parseFloat(m[1])
  const unit = (m[2] || 'B').toUpperCase()
  const factor = unit === 'KB' ? 1024 : unit === 'MB' ? 1024 ** 2 : unit === 'GB' ? 1024 ** 3 : unit === 'TB' ? 1024 ** 4 : 1
  return Math.round(num * factor)
}

export function formatBytes(bytes: number | null | undefined): string {
  if (!bytes || bytes <= 0) return '0B'
  const units = ['B','KB','MB','GB','TB']
  let i = 0
  let n = bytes
  while (n >= 1024 && i < units.length - 1) { n /= 1024; i++ }
  return i === 0 ? `${Math.floor(n)}${units[i]}` : `${Math.round(n)}${units[i]}`
}

export function getSetting<T = any>(values: Record<string, any>, key: string, fallback?: T): T {
  return (values && Object.prototype.hasOwnProperty.call(values, key)) ? values[key] : (fallback as T)
}
