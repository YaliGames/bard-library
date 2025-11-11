<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">用户管理</h2>
      <div class="flex items-center">
        <el-button v-permission="'users.create'" type="primary" @click="handleCreate">
          <span class="material-symbols-outlined mr-1 text-lg">add</span>
          创建用户
        </el-button>
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
          返回
        </el-button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <el-form :inline="true" :model="filters">
        <el-form-item label="搜索">
          <el-input
            v-model="filters.search"
            placeholder="用户名或邮箱"
            clearable
            style="width: 200px"
            @clear="loadUsers"
          >
            <template #append>
              <el-button :icon="Search" @click="loadUsers" />
            </template>
          </el-input>
        </el-form-item>

        <el-form-item label="角色筛选">
          <el-select
            v-model="filters.role_id"
            placeholder="全部角色"
            clearable
            style="width: 150px"
            @change="loadUsers"
          >
            <el-option
              v-for="role in roles"
              :key="role.id"
              :label="role.display_name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item>
          <el-button @click="resetFilters">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 用户表格 -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <el-table :data="users" v-loading="loading" stripe>
        <el-table-column prop="name" label="用户名" min-width="120">
          <template #default="{ row }">
            <div>
              <strong>{{ row.name }}</strong>
            </div>
            <div class="text-xs text-gray-500">
              {{ row.email }}
            </div>
          </template>
        </el-table-column>

        <el-table-column label="角色" min-width="200">
          <template #default="{ row }">
            <el-tag
              v-for="role in row.roles"
              :key="role.id"
              size="small"
              :type="getRoleTagType(role)"
              class="m-0.5"
            >
              {{ role.display_name }}
            </el-tag>
            <span v-if="!row.roles || row.roles.length === 0" class="text-gray-500">无角色</span>
          </template>
        </el-table-column>

        <el-table-column prop="location" label="位置" width="120" show-overflow-tooltip />

        <el-table-column label="邮箱验证" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.email_verified_at" size="small" type="success">已验证</el-tag>
            <el-tag v-else size="small" type="warning">未验证</el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="created_at" label="创建时间" width="160">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button
              v-permission="'users.edit'"
              link
              type="primary"
              size="small"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>

            <el-button
              v-permission="'users.view'"
              link
              type="info"
              size="small"
              @click="handleViewRoles(row)"
            >
              查看角色
            </el-button>

            <el-popconfirm
              v-if="canDelete && !isSelf(row)"
              title="确定要删除这个用户吗?"
              confirm-button-text="确定"
              cancel-button-text="取消"
              @confirm="handleDelete(row)"
            >
              <template #reference>
                <el-button link type="danger" size="small">删除</el-button>
              </template>
            </el-popconfirm>

            <el-tooltip v-else content="不能删除自己" placement="top">
              <el-button link type="info" size="small" disabled>删除</el-button>
            </el-tooltip>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="mt-5 flex justify-center">
        <el-pagination
          v-model:current-page="pagination.current_page"
          v-model:page-size="pagination.per_page"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadUsers"
          @current-change="loadUsers"
        />
      </div>
    </div>

    <!-- 创建/编辑对话框 -->
    <UserDialog v-model:visible="dialogVisible" :user="currentUser" @success="loadUsers" />

    <!-- 角色详情对话框 -->
    <el-dialog v-model="rolesDialogVisible" title="用户角色" width="500px">
      <div v-if="currentUser">
        <h3>{{ currentUser.name }}</h3>
        <p class="text-gray-500">{{ currentUser.email }}</p>
        <el-divider />

        <div v-if="userRoles.length > 0">
          <el-tag
            v-for="role in userRoles"
            :key="role.id"
            size="large"
            :type="getRoleTagType(role)"
            class="m-2"
          >
            <div>
              <strong>{{ role.display_name }}</strong>
              <div class="text-xs mt-1">
                优先级: {{ role.priority }} | {{ role.permissions?.length || 0 }} 个权限
              </div>
            </div>
          </el-tag>
        </div>
        <el-empty v-else description="该用户还没有分配任何角色" />
      </div>

      <template #footer>
        <el-button @click="rolesDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </section>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { usersApi } from '@/api/users'
import { rolesApi } from '@/api/roles'
import { useRouter } from 'vue-router'
import type { User, Role } from '@/api/types'
import UserDialog from '@/components/Admin/UserDialog.vue'
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

const router = useRouter()
const authStore = useAuthStore()
const { hasPermission } = usePermission()
const loading = ref(false)
const users = ref<User[]>([])
const roles = ref<Role[]>([])
const dialogVisible = ref(false)
const rolesDialogVisible = ref(false)
const currentUser = ref<User>()
const userRoles = ref<Role[]>([])

function back() {
  router.back()
}

// 权限检查
const canDelete = computed(() => hasPermission('users.delete'))

const filters = reactive({
  search: '',
  role_id: undefined as number | undefined,
})

const pagination = reactive({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

// 加载用户列表
const loadUsers = async () => {
  loading.value = true
  try {
    const response = await usersApi.list({
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: filters.search || undefined,
      role_id: filters.role_id,
    })

    users.value = response.data
    pagination.total = response.total
    pagination.last_page = response.last_page
  } catch (error) {
    handleError(error, { context: 'UserList.loadUsers' })
  } finally {
    loading.value = false
  }
}

// 加载角色列表(用于筛选)
const loadRoles = async () => {
  try {
    roles.value = await rolesApi.list()
  } catch (error) {
    handleError(error, { context: 'UserList.loadRoles', showToast: false })
  }
}

// 重置筛选
const resetFilters = () => {
  filters.search = ''
  filters.role_id = undefined
  pagination.current_page = 1
  loadUsers()
}

// 创建用户
const handleCreate = () => {
  currentUser.value = undefined
  dialogVisible.value = true
}

// 编辑用户
const handleEdit = (user: User) => {
  currentUser.value = user
  dialogVisible.value = true
}

// 删除用户
const handleDelete = async (user: User) => {
  try {
    await usersApi.delete(user.id)
    handleSuccess('用户删除成功')
    loadUsers()
  } catch (error: any) {
    handleError(error, { context: 'UserList.handleDelete' })
  }
}

// 查看角色
const handleViewRoles = async (user: User) => {
  currentUser.value = user
  rolesDialogVisible.value = true

  try {
    userRoles.value = await usersApi.getRoles(user.id)
  } catch (error) {
    handleError(error, { context: 'UserList.handleViewRoles' })
  }
}

// 判断是否是当前用户
const isSelf = (user: User): boolean => {
  return user.id === authStore.user?.id
}

// 获取角色标签类型
const getRoleTagType = (role: Role): string => {
  if (role.priority >= 900) return 'danger'
  if (role.priority >= 500) return 'warning'
  if (role.priority >= 100) return 'success'
  return 'info'
}

// 格式化日期时间
const formatDateTime = (dateString?: string): string => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  loadUsers()
  loadRoles()
})
</script>
