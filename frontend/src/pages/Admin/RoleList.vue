<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">角色管理</h2>
      <div class="flex items-center">
        <el-button v-permission="'roles.create'" type="primary" @click="handleCreate">
          <span class="material-symbols-outlined mr-1 text-lg">add</span>
          创建角色
        </el-button>
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
      <el-table :data="roles" v-loading="loading" stripe>
        <el-table-column prop="display_name" label="角色名称" min-width="120">
          <template #default="{ row }">
            <div>
              <strong>{{ row.display_name }}</strong>
              <el-tag v-if="row.is_system" size="small" type="info" class="ml-2">系统角色</el-tag>
            </div>
            <div class="text-xs text-gray-500">
              {{ row.name }}
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />

        <el-table-column prop="priority" label="优先级" width="100" align="center" sortable />

        <el-table-column prop="permissions" label="权限数量" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ row.permissions?.length || 0 }} 个权限</el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="users_count" label="用户数" width="100" align="center" />

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button
              v-permission="'roles.edit'"
              link
              :type="isSuperAdmin(row) ? 'info' : 'primary'"
              size="small"
              @click="handleEdit(row)"
            >
              {{ isSuperAdmin(row) ? '查看' : '编辑' }}
            </el-button>

            <el-button
              v-permission="'roles.view'"
              link
              type="info"
              size="small"
              @click="handleViewPermissions(row)"
            >
              查看权限
            </el-button>

            <el-popconfirm
              v-if="!row.is_system && !isSuperAdmin(row)"
              v-permission="'roles.delete'"
              title="确定要删除这个角色吗?"
              confirm-button-text="确定"
              cancel-button-text="取消"
              @confirm="handleDelete(row)"
            >
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>

            <el-tooltip
              v-else
              :content="isSuperAdmin(row) ? '超级管理员不能删除' : '系统角色不能删除'"
              placement="top"
            >
              <el-button link type="info" size="small" disabled>删除</el-button>
            </el-tooltip>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 创建/编辑对话框 -->
    <RoleDialog v-model:visible="dialogVisible" :role="currentRole" @success="loadRoles" />

    <!-- 权限详情对话框 -->
    <el-dialog v-model="permissionsDialogVisible" title="角色权限" width="600px">
      <div v-if="currentRole">
        <h3>{{ currentRole.display_name }}</h3>
        <el-divider />

        <div v-if="rolePermissions.length > 0">
          <div v-for="[group, permissions] in groupedPermissions" :key="group" class="mb-4">
            <h4 class="my-2 text-sm text-gray-700">{{ getGroupDisplayName(group) }}</h4>
            <el-tag v-for="permission in permissions" :key="permission.id" size="small" class="m-1">
              {{ permission.display_name }}
            </el-tag>
          </div>
        </div>
        <el-empty v-else description="该角色还没有分配任何权限" />
      </div>

      <template #footer>
        <el-button @click="permissionsDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { rolesApi } from '@/api/roles'
import { useRouter } from 'vue-router'
import type { Role, Permission } from '@/api/types'
import RoleDialog from '@/components/Admin/RoleDialog.vue'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

const router = useRouter()
const loading = ref(false)
const roles = ref<Role[]>([])
const dialogVisible = ref(false)
const permissionsDialogVisible = ref(false)
const currentRole = ref<Role>()
const rolePermissions = ref<Permission[]>([])

function back() {
  router.back()
}

// 检测是否为超级管理员
const isSuperAdmin = (role: Role): boolean => {
  const superAdminNames = ['super_admin']
  if (superAdminNames.includes(role.name.toLowerCase())) {
    return true
  }
  return false
}

// 加载角色列表
const loadRoles = async () => {
  loading.value = true
  try {
    roles.value = await rolesApi.list()
  } catch (error) {
    handleError(error, { context: 'RoleList.loadRoles' })
  } finally {
    loading.value = false
  }
}

// 创建角色
const handleCreate = () => {
  currentRole.value = undefined
  dialogVisible.value = true
}

// 编辑角色
const handleEdit = (role: Role) => {
  currentRole.value = role
  dialogVisible.value = true
}

// 删除角色
const handleDelete = async (role: Role) => {
  // 超级管理员不允许删除
  if (isSuperAdmin(role)) {
    handleError(new Error('超级管理员不可删除'), {
      context: 'RoleList.handleDelete',
      message: '超级管理员角色受保护，不可删除',
    })
    return
  }

  try {
    await rolesApi.delete(role.id)
    handleSuccess('角色删除成功')
    loadRoles()
  } catch (error: any) {
    handleError(error, { context: 'RoleList.handleDelete' })
  }
}

// 查看权限
const handleViewPermissions = async (role: Role) => {
  currentRole.value = role
  permissionsDialogVisible.value = true

  try {
    rolePermissions.value = await rolesApi.getPermissions(role.id)
  } catch (error) {
    handleError(error, { context: 'RoleList.handleViewPermissions' })
  }
}

// 按分组整理权限
const groupedPermissions = computed(() => {
  const grouped = new Map<string, Permission[]>()

  rolePermissions.value.forEach(permission => {
    if (!grouped.has(permission.group)) {
      grouped.set(permission.group, [])
    }
    grouped.get(permission.group)!.push(permission)
  })

  return Array.from(grouped.entries())
})

// 获取分组显示名称
const getGroupDisplayName = (group: string): string => {
  const groupNames: Record<string, string> = {
    books: '图书管理',
    authors: '作者管理',
    tags: '标签管理',
    series: '系列管理',
    shelves: '书架管理',
    files: '文件管理',
    users: '用户管理',
    roles: '角色管理',
    settings: '系统设置',
    metadata: '元数据',
  }
  return groupNames[group] || group
}

onMounted(() => {
  loadRoles()
})
</script>
