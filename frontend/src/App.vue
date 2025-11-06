<template>
  <div v-if="systemStore.initialized">
    <NavBar />
    <main>
      <router-view />
    </main>
  </div>
  <div v-else class="loading-container">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <p class="loading-text">正在加载...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import NavBar from './components/NavBar.vue'
import { useSystemStore } from '@/stores/system'
// import { useAuthStore } from '@/stores/auth'

const systemStore = useSystemStore()
// const authStore = useAuthStore()

onMounted(async () => {
  // 初始化系统配置
  await systemStore.fetchPublicPermissions()
  
  // 如果已登录，可以在这里加载用户信息
  // if (authStore.isLoggedIn) {
  //   await loadUserInfo()
  // }
})
</script>

<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.loading-content {
  text-align: center;
  color: white;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  margin: 0 auto 1rem;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  font-size: 1.125rem;
  font-weight: 500;
  margin: 0;
}
</style>
