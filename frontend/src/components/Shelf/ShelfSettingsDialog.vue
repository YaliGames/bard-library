<template>
  <el-dialog
    :model-value="modelValue"
    title="书架设置"
    width="500px"
    @update:model-value="emit('update:modelValue', $event)"
    @closed="resetForm"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="80px" class="mt-2">
      <el-form-item label="名称" prop="name">
        <el-input v-model="form.name" maxlength="190" show-word-limit placeholder="书架名称" />
      </el-form-item>

      <el-form-item label="描述" prop="description">
        <el-input
          v-model="form.description"
          type="textarea"
          :rows="3"
          maxlength="500"
          show-word-limit
          placeholder="可选：书架简介"
        />
      </el-form-item>

      <div v-if="canSetPublic || canSetGlobal" class="border-t border-gray-100 my-4 pt-4">
        <el-form-item v-if="canSetPublic" label="公开书架" prop="is_public">
          <div class="flex flex-col">
            <el-switch v-model="form.is_public" />
            <span class="text-xs text-gray-400 mt-1">开启后，所有用户均可查看此书架</span>
          </div>
        </el-form-item>

        <el-form-item v-if="canSetGlobal" label="全局书架" prop="global">
          <div class="flex flex-col">
            <el-switch v-model="form.global" />
            <span class="text-xs text-gray-400 mt-1" v-if="form.global">
              全局书架不属于任何个人，通常用于系统推荐
            </span>
          </div>
        </el-form-item>

        <el-alert
          v-if="form.global && !form.is_public"
          title="注意：未公开的全局书架仅管理员可见"
          type="warning"
          :closable="false"
          show-icon
          class="mt-2"
        />
      </div>
    </el-form>

    <template #footer>
      <div class="flex justify-between items-center w-full">
        <div>
          <el-popconfirm
            v-if="canDelete"
            title="确定要删除这个书架吗？无法恢复。"
            confirm-button-text="删除"
            cancel-button-text="取消"
            confirm-button-type="danger"
            @confirm="handleDelete"
          >
            <template #reference>
              <el-button link type="danger">删除书架</el-button>
            </template>
          </el-popconfirm>
        </div>
        <div class="flex gap-2">
          <el-button @click="close">取消</el-button>
          <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePermission } from '@/composables/usePermission'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  modelValue: boolean
  shelf: Shelf
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'updated'): void
  (e: 'deleted'): void
}>()

const { handleError, handleSuccess } = useErrorHandler()
const { hasPermission } = usePermission()
const authStore = useAuthStore()

const formRef = ref()
const saving = ref(false)
const form = ref({
  name: '',
  description: '',
  is_public: false,
  global: false,
})

const rules = {
  name: [{ required: true, message: '请输入书架名称', trigger: 'blur' }],
}

// 权限判断
const canSetPublic = computed(
  () => hasPermission('shelves.create_public') || hasPermission('shelves.manage_all'),
)
const canSetGlobal = computed(
  () => hasPermission('shelves.create_global') || hasPermission('shelves.manage_all'),
)
const canDelete = computed(() => {
  if (hasPermission('shelves.manage_all')) return true
  const isOwner = (props.shelf.user_id ?? 0) === (authStore.user?.id ?? -1)
  return isOwner && hasPermission('shelves.delete')
})

// 初始化表单
watch(
  () => props.modelValue,
  val => {
    if (val && props.shelf) {
      form.value = {
        name: props.shelf.name,
        description: props.shelf.description || '',
        is_public: !!props.shelf.is_public,
        global: props.shelf.user_id === null,
      }
    }
  },
)

function close() {
  emit('update:modelValue', false)
}

function resetForm() {
  if (formRef.value) formRef.value.resetFields()
}

async function submit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return
    saving.value = true
    try {
      const payload: Record<string, any> = {
        name: form.value.name.trim(),
        description: form.value.description || '',
      }
      if (canSetPublic.value) payload.is_public = !!form.value.is_public
      if (canSetGlobal.value) payload.global = !!form.value.global

      await shelvesApi.updateRaw(props.shelf.id, payload)
      handleSuccess('书架设置已更新')
      emit('updated')
      close()
    } catch (e: any) {
      handleError(e, { context: 'ShelfSettingsDialog.submit' })
    } finally {
      saving.value = false
    }
  })
}

async function handleDelete() {
  try {
    await shelvesApi.remove(props.shelf.id)
    handleSuccess('书架已删除')
    emit('deleted')
    close()
  } catch (e: any) {
    handleError(e, { context: 'ShelfSettingsDialog.delete' })
  }
}
</script>
