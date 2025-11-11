<template>
  <ShelfBrowser
    title="书架"
    :owner="owner"
    :show-search="true"
    :can-manage="canManage"
    :show-visibility="showVisibility"
    :admin="hasAdminPermission"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
import ShelfBrowser from '@/components/Shelf/Browser.vue'

const authStore = useAuthStore()
const { hasPermission, hasAnyPermission } = usePermission()

const isLoggedIn = computed(() => authStore.isLoggedIn)

// 根据权限确定显示模式
const hasManageAllPermission = computed(() => hasPermission('shelves.manage_all'))
const hasAdminPermission = computed(() =>
  hasAnyPermission(['shelves.create_global', 'shelves.create_public']),
)

// owner: 如果有 manage_all 权限,显示所有书架(admin模式),否则只显示用户自己的
const owner = computed(() => (hasManageAllPermission.value ? 'admin' : undefined))

// canManage: 可以创建书架的条件
const canManage = computed(() => {
  return (
    isLoggedIn.value &&
    hasAnyPermission(['shelves.create', 'shelves.create_public', 'shelves.create_global'])
  )
})

// showVisibility: 有管理权限时显示可见性筛选
const showVisibility = computed(() => hasManageAllPermission.value)
</script>

<style scoped></style>
