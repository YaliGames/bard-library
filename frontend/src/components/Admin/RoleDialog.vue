<template>
  <el-dialog
    :model-value="visible"
    :title="isEdit ? '编辑角色' : '创建角色'"
    width="700px"
    @update:model-value="$emit('update:visible', $event)"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
      <el-form-item label="角色名称" prop="name">
        <el-input
          v-model="formData.name"
          placeholder="英文标识,如: editor"
          :disabled="isEdit && role?.is_system"
        />
        <div
          v-if="isEdit && role?.is_system"
          style="font-size: 12px; color: var(--el-text-color-secondary); margin-top: 4px"
        >
          系统角色不能修改标识名
        </div>
      </el-form-item>

      <el-form-item label="显示名称" prop="display_name">
        <el-input v-model="formData.display_name" placeholder="中文显示名,如: 编辑员" />
      </el-form-item>

      <el-form-item label="描述">
        <el-input v-model="formData.description" type="textarea" :rows="2" placeholder="角色描述" />
      </el-form-item>

      <el-form-item label="优先级" prop="priority">
        <el-input-number
          v-model="formData.priority"
          :min="0"
          :max="999"
          :step="10"
          style="width: 200px"
        />
        <div style="font-size: 12px; color: var(--el-text-color-secondary); margin-top: 4px">
          数值越高优先级越高 (0-999)
        </div>
      </el-form-item>

      <el-form-item label="权限配置">
        <div style="width: 100%">
          <el-checkbox v-model="selectAll" :indeterminate="indeterminate" @change="handleSelectAll">
            全选
          </el-checkbox>
          <el-divider />

          <div v-for="group in permissionGroups" :key="group.name" style="margin-bottom: 16px">
            <div style="margin-bottom: 8px; font-weight: bold">
              <el-checkbox
                v-model="groupCheckState[group.name]"
                :indeterminate="groupIndeterminate[group.name]"
                @change="handleGroupChange(group.name)"
              >
                {{ group.display }}
              </el-checkbox>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; padding-left: 24px">
              <el-checkbox
                v-for="permission in group.permissions"
                :key="permission.id"
                v-model="selectedPermissions"
                :label="permission.id"
              >
                {{ permission.display_name }}
              </el-checkbox>
            </div>
          </div>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        {{ isEdit ? '保存' : '创建' }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { type FormInstance, type FormRules } from 'element-plus'
import { rolesApi, permissionsApi } from '@/api/roles'
import type { Role, Permission } from '@/api/types'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

interface Props {
  visible: boolean
  role?: Role
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const permissions = ref<Permission[]>([])
const selectedPermissions = ref<number[]>([])

const formData = reactive({
  name: '',
  display_name: '',
  description: '',
  priority: 0,
})

const isEdit = computed(() => !!props.role)

const rules: FormRules = {
  name: [
    { required: true, message: '请输入角色名称', trigger: 'blur' },
    { pattern: /^[a-z_]+$/, message: '只能使用小写字母和下划线', trigger: 'blur' },
  ],
  display_name: [{ required: true, message: '请输入显示名称', trigger: 'blur' }],
  priority: [
    { required: true, message: '请输入优先级', trigger: 'blur' },
    { type: 'number', min: 0, max: 999, message: '优先级范围 0-999', trigger: 'blur' },
  ],
}

// 按分组整理权限
interface PermissionGroup {
  name: string
  display: string
  permissions: Permission[]
}

const permissionGroups = computed((): PermissionGroup[] => {
  const groupMap = new Map<string, Permission[]>()

  permissions.value.forEach(permission => {
    if (!groupMap.has(permission.group)) {
      groupMap.set(permission.group, [])
    }
    groupMap.get(permission.group)!.push(permission)
  })

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

  return Array.from(groupMap.entries()).map(([name, perms]) => ({
    name,
    display: groupNames[name] || name,
    permissions: perms,
  }))
})

// 分组选择状态
const groupCheckState = computed(() => {
  const state: Record<string, boolean> = {}
  permissionGroups.value.forEach(group => {
    const allSelected = group.permissions.every(p => selectedPermissions.value.includes(p.id))
    state[group.name] = allSelected
  })
  return state
})

const groupIndeterminate = computed(() => {
  const state: Record<string, boolean> = {}
  permissionGroups.value.forEach(group => {
    const someSelected = group.permissions.some(p => selectedPermissions.value.includes(p.id))
    const allSelected = group.permissions.every(p => selectedPermissions.value.includes(p.id))
    state[group.name] = someSelected && !allSelected
  })
  return state
})

// 全选状态
const selectAll = computed({
  get: () => {
    return (
      permissions.value.length > 0 && selectedPermissions.value.length === permissions.value.length
    )
  },
  set: (value: boolean) => {
    if (value) {
      selectedPermissions.value = permissions.value.map(p => p.id)
    } else {
      selectedPermissions.value = []
    }
  },
})

const indeterminate = computed(() => {
  const count = selectedPermissions.value.length
  return count > 0 && count < permissions.value.length
})

// 全选/取消全选
const handleSelectAll = (value: boolean) => {
  if (value) {
    selectedPermissions.value = permissions.value.map(p => p.id)
  } else {
    selectedPermissions.value = []
  }
}

// 分组选择变化
const handleGroupChange = (groupName: string) => {
  const group = permissionGroups.value.find(g => g.name === groupName)
  if (!group) return

  const allSelected = group.permissions.every(p => selectedPermissions.value.includes(p.id))

  if (allSelected) {
    // 取消选择该组所有权限
    selectedPermissions.value = selectedPermissions.value.filter(
      id => !group.permissions.some(p => p.id === id),
    )
  } else {
    // 选择该组所有权限
    const groupIds = group.permissions.map(p => p.id)
    selectedPermissions.value = [...new Set([...selectedPermissions.value, ...groupIds])]
  }
}

// 加载权限列表
const loadPermissions = async () => {
  try {
    permissions.value = await permissionsApi.list()
  } catch (error) {
    handleError(error, { context: 'RoleDialog.loadPermissions' })
  }
}

// 监听对话框打开
watch(
  () => props.visible,
  visible => {
    if (visible) {
      loadPermissions()

      if (props.role) {
        // 编辑模式 - 填充表单
        formData.name = props.role.name
        formData.display_name = props.role.display_name
        formData.description = props.role.description || ''
        formData.priority = props.role.priority
        selectedPermissions.value = props.role.permissions?.map(p => p.id) || []
      } else {
        // 创建模式 - 重置表单
        formRef.value?.resetFields()
        formData.name = ''
        formData.display_name = ''
        formData.description = ''
        formData.priority = 0
        selectedPermissions.value = []
      }
    }
  },
)

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async valid => {
    if (!valid) return

    loading.value = true
    try {
      const data = {
        name: formData.name,
        display_name: formData.display_name,
        description: formData.description || undefined,
        priority: formData.priority,
      }

      if (isEdit.value && props.role) {
        // 更新角色
        await rolesApi.update(props.role.id, data)

        // 更新权限
        await rolesApi.syncPermissions(props.role.id, selectedPermissions.value)

        handleSuccess('角色更新成功')
      } else {
        // 创建角色
        const newRole = await rolesApi.create(data)

        // 分配权限
        if (selectedPermissions.value.length > 0) {
          await rolesApi.syncPermissions(newRole.id, selectedPermissions.value)
        }

        handleSuccess('角色创建成功')
      }

      emit('success')
      handleClose()
    } catch (error: any) {
      handleError(error, { context: 'RoleDialog.handleSubmit' })
    } finally {
      loading.value = false
    }
  })
}

// 关闭对话框
const handleClose = () => {
  emit('update:visible', false)
}
</script>
