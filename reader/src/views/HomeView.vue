<script setup lang="ts">
import { useUserStore } from '@/stores/user'
import { useNetworkStatus } from '@/utils/network'
import { useRouter } from 'vue-router'

const userStore = useUserStore()
const { isOnline } = useNetworkStatus()
const router = useRouter()

const handleLogout = () => {
  userStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="h-full flex flex-col items-center justify-center p-4">
    <div v-if="!isOnline" class="absolute top-0 left-0 w-full bg-red-500 text-white text-center py-1 text-sm">
      You are currently offline
    </div>

    <h1 class="text-2xl font-bold mb-4">Bard Reader</h1>
    
    <div v-if="userStore.isLoggedIn" class="text-center mb-6">
      <p class="text-lg mb-2">Welcome, {{ userStore.user?.name }}</p>
      <p class="text-gray-500 text-sm mb-4">{{ userStore.user?.email }}</p>
      <div class="flex gap-2 justify-center flex-wrap">
        <el-button type="primary" @click="$router.push('/books')">Browse Books</el-button>
        <el-button type="info" @click="$router.push('/cache')">Cache Management</el-button>
        <el-button type="danger" @click="handleLogout">Logout</el-button>
      </div>
    </div>
    
    <div v-else class="text-center">
      <p class="mb-4 text-gray-500">Please login to access your library</p>
      <el-button type="primary" @click="$router.push('/login')">Go to Login</el-button>
    </div>
  </div>
</template>
