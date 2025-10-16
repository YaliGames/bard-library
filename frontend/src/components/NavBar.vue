<template>
  <nav class="flex items-center gap-4 px-4 py-3 border-b border-gray-200">
    <div class="flex items-center gap-2">
      <img src="/src/images/logo.svg" alt="Logo" class="h-8 w-8" />
      <span class="font-bold text-lg">Bard Library</span>
    </div>
    <router-link to="/">首页</router-link>
    <router-link to="/books">书库</router-link>
    <router-link to="/admin/index" v-if="isAdmin">管理入口</router-link>
    
    <span class="flex-1"></span>

    <!-- 未登录：显示登录入口 -->
    <template v-if="!user && !loadingUser">
      <router-link to="/login">登录</router-link>
    </template>

    <!-- 加载用户中：骨架与真实 UI 对齐（头像 + 名字块） -->
    <div v-else-if="loadingUser">
      <el-skeleton animated>
        <template #template>
          <div class="flex items-center gap-2">
            <el-skeleton-item variant="circle" style="width:28px;height:28px" />
            <el-skeleton-item variant="text" style="width:100px;height:16px" />
          </div>
        </template>
      </el-skeleton>
    </div>

    <!-- 已登录：用户中心下拉菜单 -->
    <template v-else>
      <el-dropdown trigger="hover" @command="onCommand">
        <div class="el-dropdown-link flex items-center gap-2 cursor-pointer">
          <el-avatar :size="28">{{ avatarLetter }}</el-avatar>
          <span class="text-sm">{{ user?.name || user?.email }}</span>
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="profile">个人资料</el-dropdown-item>
            <el-dropdown-item command="settings">用户设置</el-dropdown-item>
            <el-dropdown-item v-if="isAdmin" command="admin">管理入口</el-dropdown-item>
            <el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </template>
  </nav>
  
</template>
<script setup lang="ts">
import { ref, onMounted, computed, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { settingsApi } from '@/api/settings'
const router = useRouter()
const { state: authState, setUser } = useAuthStore()
const { setAll: setAllSettings } = useSettingsStore()
const user = computed(() => authState.user)
const loadingUser = ref(false)

const { isRole } = useAuthStore()
const isAdmin = computed(()=> isRole('admin'))
const avatarLetter = computed(()=> (user.value?.name?.[0] || user.value?.email?.[0] || 'U').toUpperCase())

async function fetchUser(){
  const token = localStorage.getItem('token')
  if (!token) { setUser(null); return }
  loadingUser.value = true
  try {
    const me = await authApi.me()
    setUser(me)
  } catch {
    setUser(null)
  } finally { loadingUser.value = false }
}

onMounted(fetchUser)

// 响应登录后 localStorage token/role 变化：登录页成功后会设置 token，可触发刷新
watchEffect(() => {
  const token = localStorage.getItem('token') || ''
  // 当 token 存在但本地 user 还没就绪时尝试读取一次
  if (token && !user.value && !loadingUser.value) {
    fetchUser()
  }
})

async function onCommand(cmd: string){
  switch (cmd) {
    case 'profile':
      router.push({ name: 'profile' })
      break
    case 'admin':
      router.push({ name: 'admin-index' })
      break
    case 'settings':
      router.push({ name: 'user-settings' })
      break
    case 'logout':
      await authApi.logout()
      setUser(null)
      router.push({ name: 'login' })
      break
  }
}

// 登录后首次拉取用户设置
watchEffect(async () => {
  const token = localStorage.getItem('token') || ''
  if (token && user.value) {
    try { const remote = await settingsApi.get(); setAllSettings(remote) } catch {}
  }
})
</script>
<style scoped>
a.router-link-active { font-weight: 600; }
nav a { text-decoration: none; color: #333; }
nav a:hover { text-decoration: underline; }
</style>
