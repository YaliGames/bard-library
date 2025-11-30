import { http } from '@/api/http'
import { getPublicPermissions } from '@/utils/publicSettings'

const SKEW_SEC = 15 // 安全缓冲，避免临界过期
const TOKEN_KEY = 'resourceToken'
let inflightToken: Promise<string> | null = null

type TokenCache = { token: string; expAt: number }
function now() {
  return Date.now()
}
function readToken(): string | null {
  try {
    const raw = localStorage.getItem(TOKEN_KEY)
    if (!raw) return null
    const obj = JSON.parse(raw) as TokenCache
    if (!obj || !obj.token || !obj.expAt) return null
    if (now() + SKEW_SEC * 1000 >= obj.expAt) return null
    return obj.token
  } catch {
    return null
  }
}
function writeToken(token: string, expiresInSec: number) {
  const expAt = now() + Math.max(1, expiresInSec - SKEW_SEC) * 1000
  try {
    localStorage.setItem(TOKEN_KEY, JSON.stringify({ token, expAt } satisfies TokenCache))
  } catch { }
}
async function getResourceToken(ttlSeconds?: number): Promise<string> {
  const cached = readToken()
  if (cached) return cached
  if (inflightToken) return inflightToken
  const ttl = minutesForTtl(ttlSeconds)
  inflightToken = http
    .get<{ token: string; expires_in: number }>(`/api/v1/files/access-token?ttl=${ttl}`)
    .then((res: any) => {
      const token: string = res?.token || res?.data?.token || ''
      const exp: number = Number(res?.expires_in || res?.data?.expires_in || 0)
      if (token && exp) writeToken(token, exp)
      return token
    })
    .finally(() => {
      inflightToken = null
    }) as any
  return inflightToken!
}
function appendQuery(url: string, key: string, value: string): string {
  const u = new URL(url, window.location.origin)
  u.searchParams.set(key, value)
  return u.pathname + u.search
}
export async function ensureResourceQuery(
  url: string,
  opts?: { ttlSeconds?: number },
): Promise<string> {
  const { allow_guest_access } = await getPublicPermissions()
  if (allow_guest_access) return url
  const token = await getResourceToken(opts?.ttlSeconds)
  return appendQuery(url, 'rt', token)
}

export function ensureResourceQuerySync(url: string): string {
  try {
    const raw = localStorage.getItem(TOKEN_KEY)
    if (!raw) return url
    const obj = JSON.parse(raw) as TokenCache
    if (!obj || !obj.token || !obj.expAt) return url
    if (now() + SKEW_SEC * 1000 >= obj.expAt) return url
    return appendQuery(url, 'rt', obj.token)
  } catch {
    return url
  }
}

export async function prefetchResourceToken(ttlSeconds?: number): Promise<void> {
  try {
    await getResourceToken(ttlSeconds)
  } catch { }
}

function minutesForTtl(ttlSeconds?: number): number {
  const sec = typeof ttlSeconds === 'number' && ttlSeconds > 0 ? ttlSeconds : 600
  return Math.max(1, Math.min(60, Math.ceil(sec / 60)))
}

export async function getPreviewUrl(
  fileId: number,
  opts?: { forceSigned?: boolean; ttlSeconds?: number },
): Promise<string> {
  const { allow_guest_access } = await getPublicPermissions()
  const base = `/api/v1/files/${fileId}/preview`
  if (allow_guest_access && !opts?.forceSigned) return base
  return ensureResourceQuery(base, { ttlSeconds: opts?.ttlSeconds })
}

export async function getDownloadUrl(
  fileId: number,
  opts?: { forceSigned?: boolean; ttlSeconds?: number },
): Promise<string> {
  const { allow_guest_access } = await getPublicPermissions()
  const base = `/api/v1/files/${fileId}/download`
  if (allow_guest_access && !opts?.forceSigned) return base
  return ensureResourceQuery(base, { ttlSeconds: opts?.ttlSeconds })
}
