<template>
  <nav class="flex items-center gap-4 px-4 py-3 bg-blue-900 text-white shadow">
    <div class="flex items-center gap-2">
      <img src="/src/images/logo-white.svg" alt="Logo" class="h-8 w-8" />
      <span class="font-bold text-lg">Bard Library</span>
    </div>

    <div class="hidden md:flex items-center gap-4 ml-4">
      <router-link to="/" class="nav-link">首页</router-link>
      <router-link to="/books" class="nav-link">书库</router-link>
      <router-link to="/admin/index" class="nav-link" v-if="isAdmin">管理入口</router-link>
    </div>

    <span class="flex-1"></span>

    <button
      class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40"
      aria-label="打开菜单" @click="mobileOpen = true">
      <span class="material-symbols-outlined text-2xl">menu</span>
    </button>

    <template v-if="!user && !loadingUser" class="hidden md:block">
      <router-link to="/login"
        class="hidden md:inline-block px-3 py-1 rounded bg-white/10 hover:bg-white/20">登录</router-link>
    </template>

    <div v-else-if="loadingUser" class="hidden md:block">
      <el-skeleton animated>
        <template #template>
          <div class="flex items-center gap-2">
            <el-skeleton-item variant="circle" style="width:28px;height:28px" />
            <el-skeleton-item variant="text" style="width:100px;height:16px" />
          </div>
        </template>
      </el-skeleton>
    </div>

    <template v-else>
      <el-dropdown trigger="hover" @command="onCommand" class="hidden md:block">
        <div class="el-dropdown-link flex items-center gap-2 cursor-pointer text-white">
          <el-avatar :size="28">{{ avatarLetter }}</el-avatar>
          <span class="text-sm">{{ user?.name || user?.email }}</span>
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="profile">个人资料</el-dropdown-item>
            <el-dropdown-item command="settings">用户设置</el-dropdown-item>
            <el-dropdown-item v-if="isAdmin" command="system">系统设置</el-dropdown-item>
            <el-dropdown-item v-if="isAdmin" command="admin">管理入口</el-dropdown-item>
            <el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </template>
  </nav>

  <!-- 移动端抽屉菜单 -->
  <el-drawer v-model="mobileOpen" direction="ltr" size="80%" :with-header="false">
    <div class="px-4 py-3">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <img src="/src/images/logo.svg" alt="Logo" class="h-7 w-7" />
          <span class="font-semibold">Bard Library</span>
        </div>
        <button class="inline-flex items-center justify-center w-10 h-10 rounded hover:bg-gray-100" aria-label="关闭菜单"
          @click="mobileOpen = false">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="flex flex-col gap-2 py-2">
        <button class="drawer-link" @click="go('/')">首页</button>
        <button class="drawer-link" @click="go('/books')">书库</button>
        <button v-if="isAdmin" class="drawer-link" @click="go('/admin/index')">管理入口</button>
      </div>

      <div class="border-t my-3"></div>

      <div v-if="!user && !loadingUser" class="py-1">
        <button class="drawer-link" @click="go('/login')">登录</button>
      </div>
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
      <div v-else class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-gray-600 mb-1">
          <el-avatar :size="28">{{ avatarLetter }}</el-avatar>
          <span class="text-sm">{{ user?.name || user?.email }}</span>
        </div>
        <button class="drawer-link" @click="go('/profile')">个人资料</button>
        <button class="drawer-link" @click="go('/user-settings')">用户设置</button>
        <button v-if="isAdmin" class="drawer-link" @click="go('/system-settings')">系统设置</button>
        <button v-if="isAdmin" class="drawer-link" @click="go('/admin/index')">管理入口</button>
        <button class="drawer-link text-red-600" @click="logoutAndGo">退出登录</button>
      </div>
    </div>
  </el-drawer>
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
const mobileOpen = ref(false)

const { isRole } = useAuthStore()
const isAdmin = computed(() => isRole('admin'))
const avatarLetter = computed(() => (user.value?.name?.[0] || user.value?.email?.[0] || 'U').toUpperCase())

async function fetchUser() {
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

async function onCommand(cmd: string) {
  switch (cmd) {
    case 'profile':
      router.push({ name: 'profile' })
      break
    case 'settings':
      router.push({ name: 'user-settings' })
      break
    case 'system':
      router.push({ name: 'system-settings' })
      break
    case 'admin':
      router.push({ name: 'admin-index' })
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
    try { const remote = await settingsApi.get(); setAllSettings(remote) } catch { }
  }
})

function go(path: string) {
  mobileOpen.value = false
  router.push(path)
}

async function logoutAndGo() {
  try { await authApi.logout() } catch { }
  setUser(null)
  mobileOpen.value = false
  router.push({ name: 'login' })
}
</script>
<style scoped>
.nav-link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-size: 14px;
}

.nav-link:hover {
  text-decoration: underline;
}

a.router-link-active.nav-link {
  font-weight: 700;
}

.drawer-link {
  width: 100%;
  text-align: left;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff;
  color: #333;
}

.drawer-link:hover {
  background: #f2f3f5;
}
</style>
