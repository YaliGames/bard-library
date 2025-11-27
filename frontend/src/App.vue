<template>
  <div v-show="systemStore.initialized">
    <NavBar />
    <main>
      <router-view />
    </main>
  </div>
  <Transition name="slide-down">
    <div v-if="showLoading" class="loading-container">
      <LoadingAnimation :book-size="0.5" />
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import NavBar from './components/NavBar.vue'
import LoadingAnimation from './components/LoadingAnimation.vue'
import { useSystemStore } from '@/stores/system'
import { useAuthStore } from '@/stores/auth'

const systemStore = useSystemStore()
const authStore = useAuthStore()
const router = useRouter()
const showLoading = ref(true)

// 监听初始化状态，触发退场动画
watch(
  () => systemStore.initialized,
  newVal => {
    if (newVal) {
      // 延迟一点时间让用户看到加载完成
      setTimeout(() => {
        showLoading.value = false
      }, 300)
    }
  },
)

function handleUnauthorized(event: Event) {
  const customEvent = event as CustomEvent<{ redirectTo: string }>
  const redirectTo = customEvent.detail?.redirectTo || '/'

  authStore.logout()

  const redirect = encodeURIComponent(redirectTo)
  router.push(`/login?redirect=${redirect}`)
}

onMounted(async () => {
  // 初始化系统配置
  await systemStore.fetchPublicPermissions()

  window.addEventListener('app:unauthorized', handleUnauthorized)

  // 如果已登录，可以在这里加载用户信息
  // if (authStore.isLoggedIn) {
  //   await loadUserInfo()
  // }
})

onBeforeUnmount(() => {
  window.removeEventListener('app:unauthorized', handleUnauthorized)
})
</script>

<style scoped>
.loading-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  z-index: 9999;
}

.slide-down-enter-active {
  transition: transform 0.5s ease-in-out;
}

.slide-down-leave-active {
  transition: transform 0.8s ease-in-out;
}

.slide-down-enter-from {
  transform: translateY(0);
}

.slide-down-leave-to {
  transform: translateY(100%);
}
</style>
