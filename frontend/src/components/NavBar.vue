<template>
  <nav class="flex items-center gap-4 px-4 py-3 bg-blue-900 text-white shadow">
    <div class="flex items-center gap-2">
      <img src="/src/images/logo-white.svg" alt="Logo" class="h-8 w-8" />
      <span class="font-bold text-lg">{{ systemName }}</span>
    </div>

    <div class="hidden md:flex items-center gap-1 ml-2">
      <template v-for="item in menuItems" :key="item.id">
        <router-link
          v-if="!item.external"
          :to="item.path || '/'"
          class="text-white no-underline text-sm hover:bg-white/5 px-3 py-2 rounded transition-colors [&.router-link-active]:bg-white/10 [&.router-link-active]:font-bold"
          v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
        >
          {{ item.label }}
        </router-link>
        <a
          v-else
          :href="item.path"
          class="text-white no-underline text-sm hover:bg-white/5 px-3 py-2 rounded transition-colors"
          target="_blank"
          rel="noopener"
          v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
        >
          {{ item.label }}
        </a>
      </template>
    </div>

    <span class="flex-1"></span>

    <button
      class="md:hidden inline-flex items-center justify-center w-10 h-10 text-white rounded hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40"
      aria-label="打开菜单"
      @click="mobileOpen = true"
    >
      <span class="material-symbols-outlined text-2xl">menu</span>
    </button>

    <template v-if="!user && !loadingUser" class="hidden md:block">
      <router-link
        to="/login"
        class="text-white no-underline text-sm hover:bg-white/5 px-3 py-2 rounded transition-colors"
      >
        登录
      </router-link>
    </template>

    <div v-else-if="loadingUser" class="hidden md:block">
      <el-skeleton animated>
        <template #template>
          <div class="flex items-center gap-2">
            <el-skeleton-item variant="circle" style="width: 28px; height: 28px" />
            <el-skeleton-item variant="text" style="width: 100px; height: 16px" />
          </div>
        </template>
      </el-skeleton>
    </div>

    <template v-else>
      <el-dropdown trigger="hover" class="hidden md:block">
        <div
          class="el-dropdown-link flex items-center gap-2 cursor-pointer text-white hover:bg-white/5 px-2 py-1 rounded transition-colors focus:outline-none"
        >
          <el-avatar :size="28">{{ avatarLetter }}</el-avatar>
          <span class="text-sm">{{ user?.name || user?.email }}</span>
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <template v-for="item in userMenuItems" :key="item.id">
              <el-dropdown-item
                :divided="!!item.divided"
                v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
                @click="handleUserMenuItem(item)"
              >
                {{ item.label }}
              </el-dropdown-item>
            </template>
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
          <span class="font-semibold">{{ systemName }}</span>
        </div>
        <button
          class="inline-flex items-center justify-center w-10 h-10 rounded hover:bg-gray-100"
          aria-label="关闭菜单"
          @click="mobileOpen = false"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="flex flex-col gap-2 py-2">
        <template v-for="item in menuItems" :key="item.id">
          <button
            v-if="!item.external"
            class="w-full text-left px-3 py-2.5 rounded-lg bg-white text-gray-800 hover:bg-gray-100"
            @click="go(item.path || '/')"
            v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
          >
            {{ item.label }}
          </button>
          <a
            v-else
            class="w-full text-left px-3 py-2.5 rounded-lg bg-white text-gray-800 hover:bg-gray-100"
            :href="item.path"
            target="_blank"
            rel="noopener"
          >
            {{ item.label }}
          </a>
        </template>
      </div>

      <div class="border-t my-3"></div>

      <div v-if="!user && !loadingUser" class="py-1">
        <button
          class="w-full text-left px-3 py-2.5 rounded-lg bg-white text-gray-800 hover:bg-gray-100"
          @click="go('/login')"
        >
          登录
        </button>
      </div>
      <div v-else-if="loadingUser">
        <el-skeleton animated>
          <template #template>
            <div class="flex items-center gap-2">
              <el-skeleton-item variant="circle" style="width: 28px; height: 28px" />
              <el-skeleton-item variant="text" style="width: 100px; height: 16px" />
            </div>
          </template>
        </el-skeleton>
      </div>
      <div v-else class="flex flex-col gap-2">
        <div
          class="flex items-center gap-2 text-gray-600 mb-1 cursor-pointer"
          @click="userMenuExpanded = !userMenuExpanded"
        >
          <el-avatar :size="28">{{ avatarLetter }}</el-avatar>
          <span class="text-sm">{{ user?.name || user?.email }}</span>
          <span
            class="material-symbols-outlined text-lg transition-transform ml-auto"
            :class="{ 'rotate-180': userMenuExpanded }"
          >
            expand_more
          </span>
        </div>
        <template v-if="userMenuExpanded" v-for="item in userMenuItems" :key="item.id">
          <button
            v-if="item.action === 'logout'"
            class="w-full text-left px-3 py-2.5 rounded-lg bg-white text-red-600 hover:bg-gray-100"
            @click="logoutAndGo"
            v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
          >
            {{ item.label }}
          </button>
          <button
            v-else
            class="w-full text-left px-3 py-2.5 rounded-lg bg-white text-gray-800 hover:bg-gray-100"
            @click="item.path ? go(item.path) : null"
            v-show="!item.requiresAdminPermission || hasAnyAdminPermission"
          >
            {{ item.label }}
          </button>
        </template>
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
import { navMenu, userMenu } from '@/config/navMenu'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useSimpleLoading } from '@/composables/useLoading'
import { usePermission } from '@/composables/usePermission'
import { useSystemName } from '@/composables/useSystemName'

const { handleError } = useErrorHandler()
const { systemName } = useSystemName()
const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const setAllSettings = settingsStore.setAll
const user = computed(() => authStore.user)
const { loading: loadingUser, setLoading: setLoadingUser } = useSimpleLoading(false)
const mobileOpen = ref(false)
const userMenuExpanded = ref(false)

const { hasAnyPermission } = usePermission()

const hasAnyAdminPermission = computed(() => {
  return hasAnyPermission([
    'books.view',
    'books.create',
    'users.view',
    'settings.view',
    'files.view',
    'authors.view',
    'tags.view',
    'series.view',
    'shelves.view',
    'roles.view',
  ])
})

const avatarLetter = computed(() =>
  (user.value?.name?.[0] || user.value?.email?.[0] || 'U').toUpperCase(),
)

const menuItems = computed(() => {
  return (navMenu || []).filter((i: any) => {
    if (i.requiresAdminPermission && !hasAnyAdminPermission.value) return false
    return true
  })
})

const userMenuItems = computed(() => {
  return (userMenu || []).filter((i: any) => {
    if (i.requiresAdminPermission && !hasAnyAdminPermission.value) return false
    return true
  })
})

const fetchUserFailed = ref(false)

async function fetchUser() {
  if (!authStore.user) {
    fetchUserFailed.value = false
    return
  }
  setLoadingUser(true)
  try {
    const me = await authApi.me()
    authStore.setUser(me)
    fetchUserFailed.value = false
  } catch (e: any) {
    authStore.logout()
    fetchUserFailed.value = true
    handleError(e, { context: 'NavBar.fetchUser', showToast: false })
    const status = e?.status || e?.response?.status
    if (status === 401 || status === 403) {
      const redirect = encodeURIComponent(
        window.location.pathname + window.location.search + window.location.hash,
      )
      router.push({ name: 'login', query: { redirect } })
    }
  } finally {
    setLoadingUser(false)
  }
}

onMounted(fetchUser)

function handleUserMenuItem(item: any) {
  if (!item) return
  if (item.action === 'logout') {
    logoutAndGo()
    return
  }
  if (item.path) {
    go(item.path)
    return
  }
}

watchEffect(async () => {
  if (user.value) {
    try {
      const remote = await settingsApi.get()
      setAllSettings(remote)
    } catch {}
  }
})

function go(path: string) {
  mobileOpen.value = false
  router.push(path)
}

async function logoutAndGo() {
  try {
    await authApi.logout()
  } catch {}
  authStore.setUser(null)
  mobileOpen.value = false
  router.push({ name: 'login' })
}
</script>
