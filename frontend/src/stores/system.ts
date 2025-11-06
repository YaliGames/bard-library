import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { http } from '@/api/http'

export interface PublicPermissions {
  allow_guest_access: boolean
  allow_user_registration: boolean
  allow_recover_password: boolean
}

export const useSystemStore = defineStore('system', () => {
  // State
  const permissions = ref<PublicPermissions | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  // Getters (computed)
  const allowGuestAccess = computed(() => permissions.value?.allow_guest_access ?? true)
  const allowUserRegistration = computed(() => permissions.value?.allow_user_registration ?? true)
  const allowRecoverPassword = computed(() => permissions.value?.allow_recover_password ?? true)

  // Actions
  async function fetchPublicPermissions() {
    if (initialized.value) return

    loading.value = true
    try {
      const res = await http.get<{ permissions: PublicPermissions }>('/api/v1/settings/public')
      permissions.value = res.permissions || {
        allow_guest_access: true,
        allow_user_registration: true,
        allow_recover_password: true,
      }
    } catch (error) {
      console.error('Failed to fetch public permissions:', error)
      // 默认值：允许所有操作
      permissions.value = {
        allow_guest_access: true,
        allow_user_registration: true,
        allow_recover_password: true,
      }
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  return {
    // State
    permissions,
    loading,
    initialized,
    // Getters
    allowGuestAccess,
    allowUserRegistration,
    allowRecoverPassword,
    // Actions
    fetchPublicPermissions,
  }
})
