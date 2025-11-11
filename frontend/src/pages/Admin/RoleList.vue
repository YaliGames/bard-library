<template>
  <div class="role-list-page">
    <div class="page-header">
      <h1>角色管理</h1>
      <el-button v-permission="'roles.create'" type="primary" @click="handleCreate">
        <el-icon><Plus /></el-icon>
        创建角色
      </el-button>
    </div>

    <el-card>
      <el-table :data="roles" v-loading="loading" stripe>
        <el-table-column prop="display_name" label="角色名称" min-width="120">
          <template #default="{ row }">
            <div>
              <strong>{{ row.display_name }}</strong>
              <el-tag v-if="row.is_system" size="small" type="info" style="margin-left: 8px">
                系统角色
              </el-tag>
            </div>
            <div style="font-size: 12px; color: var(--el-text-color-secondary)">
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
              type="primary"
              size="small"
              @click="handleEdit(row)"
            >
              编辑
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
              v-if="!row.is_system"
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

            <el-tooltip v-else content="系统角色不能删除" placement="top">
              <el-button link type="info" size="small" disabled>删除</el-button>
            </el-tooltip>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 创建/编辑对话框 -->
    <RoleDialog v-model:visible="dialogVisible" :role="currentRole" @success="loadRoles" />

    <!-- 权限详情对话框 -->
    <el-dialog v-model="permissionsDialogVisible" title="角色权限" width="600px">
      <div v-if="currentRole">
        <h3>{{ currentRole.display_name }}</h3>
        <el-divider />

        <div v-if="rolePermissions.length > 0">
          <div
            v-for="[group, permissions] in groupedPermissions"
            :key="group"
            class="permission-group"
          >
            <h4>{{ getGroupDisplayName(group) }}</h4>
            <el-tag
              v-for="permission in permissions"
              :key="permission.id"
              size="small"
              style="margin: 4px"
            >
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { rolesApi } from '@/api/roles'
import type { Role, Permission } from '@/api/types'
import RoleDialog from '@/components/Admin/RoleDialog.vue'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

const loading = ref(false)
const roles = ref<Role[]>([])
const dialogVisible = ref(false)
const permissionsDialogVisible = ref(false)
const currentRole = ref<Role>()
const rolePermissions = ref<Permission[]>([])

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

<style scoped>
.role-list-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
}

.permission-group {
  margin-bottom: 16px;
}

.permission-group h4 {
  margin: 8px 0;
  font-size: 14px;
  color: var(--el-text-color-regular);
}
</style>
