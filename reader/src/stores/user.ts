import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/api/types'

export const useUserStore = defineStore('user', () => {
    const user = ref<User | null>(null)

    const isLoggedIn = computed(() => !!user.value)

    function setUser(userData: User) {
        user.value = userData
    }

    function logout() {
        user.value = null
    }

    return {
        user,
        isLoggedIn,
        setUser,
        logout
    }
}, {
    persist: true
})
