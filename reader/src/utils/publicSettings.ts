import { http } from '@/api/http'

export type PublicSettings = {
    system_name: string
    permissions: {
        allow_guest_access: boolean
        allow_user_registration: boolean
        allow_recover_password: boolean
    }
    ui: {
        placeholder_cover: boolean
    }
}

export type PublicPermissions = PublicSettings['permissions']

let cache: PublicSettings | null = null
let inflight: Promise<PublicSettings> | null = null

export async function getPublicSettings(): Promise<PublicSettings> {
    if (cache) return cache
    if (!inflight) {
        inflight = http
            .get<PublicSettings>('/api/v1/settings/public')
            .then((res: any) => {
                cache = res || {
                    system_name: 'Bard Library',
                    permissions: {
                        allow_guest_access: true,
                        allow_user_registration: true,
                        allow_recover_password: true,
                    },
                    ui: {
                        placeholder_cover: true,
                    },
                }
                return cache
            })
            .catch(() => {
                cache = {
                    system_name: 'Bard Library',
                    permissions: {
                        allow_guest_access: true,
                        allow_user_registration: true,
                        allow_recover_password: true,
                    },
                    ui: {
                        placeholder_cover: true,
                    },
                }
                return cache
            })
            .finally(() => {
                inflight = null
            }) as any
    }
    return inflight!
}

// 向后兼容的函数
export async function getPublicPermissions(): Promise<PublicPermissions> {
    const settings = await getPublicSettings()
    return settings.permissions
}

// 获取系统名称
export async function getSystemName(): Promise<string> {
    const settings = await getPublicSettings()
    return settings.system_name
}

export function _resetPublicSettingsCache() {
    cache = null
}

// 向后兼容
export const _resetPublicPermissionsCache = _resetPublicSettingsCache
