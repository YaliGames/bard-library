<template>
  <el-dialog
    :model-value="visible"
    :title="isEdit ? '编辑用户' : '创建用户'"
    width="600px"
    @update:model-value="$emit('update:visible', $event)"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
      <el-form-item label="用户名" prop="name">
        <el-input v-model="formData.name" placeholder="请输入用户名" />
      </el-form-item>

      <el-form-item label="邮箱" prop="email">
        <el-input v-model="formData.email" type="email" placeholder="请输入邮箱" />
      </el-form-item>

      <el-form-item label="密码" :prop="isEdit ? '' : 'password'">
        <el-input
          v-model="formData.password"
          type="password"
          :placeholder="isEdit ? '留空则不修改密码' : '请输入密码'"
          show-password
        />
      </el-form-item>

      <el-form-item label="位置">
        <el-input v-model="formData.location" placeholder="请输入位置" />
      </el-form-item>

      <el-form-item label="网站">
        <el-input v-model="formData.website" placeholder="请输入网站 URL" />
      </el-form-item>

      <el-form-item label="简介">
        <el-input v-model="formData.bio" type="textarea" :rows="3" placeholder="请输入个人简介" />
      </el-form-item>

      <el-form-item label="角色">
        <el-select v-model="formData.role_ids" multiple placeholder="选择角色" style="width: 100%">
          <el-option
            v-for="role in roles"
            :key="role.id"
            :label="role.display_name"
            :value="role.id"
          >
            <span>{{ role.display_name }}</span>
            <span style="float: right; color: var(--el-text-color-secondary); font-size: 12px">
              优先级: {{ role.priority }}
            </span>
          </el-option>
        </el-select>
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
import { usersApi } from '@/api/users'
import { rolesApi } from '@/api/roles'
import type { User, Role } from '@/api/types'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { handleError, handleSuccess } = useErrorHandler()

interface Props {
  visible: boolean
  user?: User
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const roles = ref<Role[]>([])

const formData = reactive({
  name: '',
  email: '',
  password: '',
  location: '',
  website: '',
  bio: '',
  role_ids: [] as number[],
})

const isEdit = computed(() => !!props.user)

const rules: FormRules = {
  name: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 2, max: 50, message: '用户名长度在 2 到 50 个字符', trigger: 'blur' },
  ],
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' },
  ],
  password: [
    { required: !isEdit.value, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码至少 6 个字符', trigger: 'blur' },
  ],
}

// 加载角色列表
const loadRoles = async () => {
  try {
    roles.value = await rolesApi.list()
  } catch (error) {
    handleError(error, { context: 'UserDialog.loadRoles' })
  }
}

// 监听对话框打开
watch(
  () => props.visible,
  visible => {
    if (visible) {
      loadRoles()

      if (props.user) {
        // 编辑模式 - 填充表单
        formData.name = props.user.name
        formData.email = props.user.email
        formData.location = props.user.location || ''
        formData.website = props.user.website || ''
        formData.bio = props.user.bio || ''
        formData.password = ''
        formData.role_ids = props.user.roles?.map(r => r.id) || []
      } else {
        // 创建模式 - 重置表单
        formRef.value?.resetFields()
        formData.name = ''
        formData.email = ''
        formData.password = ''
        formData.location = ''
        formData.website = ''
        formData.bio = ''
        formData.role_ids = []
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
      const data: any = {
        name: formData.name,
        email: formData.email,
        location: formData.location || undefined,
        website: formData.website || undefined,
        bio: formData.bio || undefined,
      }

      if (formData.password) {
        data.password = formData.password
      }

      if (isEdit.value && props.user) {
        // 更新用户
        await usersApi.update(props.user.id, data)

        // 更新角色
        if (formData.role_ids.length > 0) {
          await usersApi.syncRoles(props.user.id, formData.role_ids)
        }

        handleSuccess('用户更新成功')
      } else {
        // 创建用户
        data.role_ids = formData.role_ids
        await usersApi.create(data)
        handleSuccess('用户创建成功')
      }

      emit('success')
      handleClose()
    } catch (error: any) {
      handleError(error, { context: 'UserDialog.handleSubmit' })
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
