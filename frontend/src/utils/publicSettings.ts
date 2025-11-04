import { http } from '@/api/http'

export type PublicPermissions = {
  allow_guest_access: boolean
  allow_user_registration: boolean
  allow_recover_password: boolean
}

let cache: PublicPermissions | null = null
let inflight: Promise<PublicPermissions> | null = null

export async function getPublicPermissions(): Promise<PublicPermissions> {
  if (cache) return cache
  if (!inflight) {
    inflight = http
      .get<{ permissions: PublicPermissions }>('/api/v1/settings/public')
      .then((res: any) => {
        cache = (res?.permissions as PublicPermissions) || {
          allow_guest_access: true,
          allow_user_registration: true,
          allow_recover_password: true,
        }
        return cache
      })
      .catch(() => {
        cache = {
          allow_guest_access: true,
          allow_user_registration: true,
          allow_recover_password: true,
        }
        return cache
      })
      .finally(() => {
        inflight = null
      }) as any
  }
  return inflight!
}

export function _resetPublicPermissionsCache() {
  cache = null
}
