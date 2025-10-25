<template>
  <section class="container mx-auto px-4 py-6 max-w-4xl">
    <h2 class="text-2xl font-semibold mb-6">系统设置</h2>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <div class="text-sm text-gray-600 mb-4">此页面用于管理全局后端配置，仅管理员可见。修改前请谨慎。</div>

      <el-alert v-if="message" :title="message" :type="messageType" class="mb-4" />

      <el-form :model="local" label-width="140px">
        <el-form-item label="全局配置（JSON）">
          <el-input type="textarea" v-model="jsonText" :rows="14" />
        </el-form-item>
      </el-form>

      <div class="flex gap-2 mt-4">
        <el-button type="primary" :loading="saving" @click="save">保存</el-button>
        <el-button @click="reload">重置为服务器值</el-button>
        <el-button type="danger" plain @click="resetDefaults">恢复为空配置</el-button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { systemSettingsApi } from '@/api/systemSettings'

const local = ref<any>({})
const jsonText = ref('')
const loading = ref(true)
const saving = ref(false)
const message = ref('')
const messageType = ref<'success'|'error'|'info'>('info')

async function load() {
  loading.value = true
  try {
    const data = await systemSettingsApi.get()
    local.value = data || {}
    jsonText.value = JSON.stringify(local.value, null, 2)
  } catch (e: any) {
    message.value = e?.message || '加载失败'
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}

function reload() {
  load()
}

async function save() {
  let parsed
  try {
    parsed = JSON.parse(jsonText.value || '{}')
  } catch (e) {
    ElMessage.error('JSON 语法错误，请修正后再保存')
    return
  }
  saving.value = true
  try {
    const res = await systemSettingsApi.update(parsed)
    local.value = res || {}
    jsonText.value = JSON.stringify(local.value, null, 2)
    message.value = '已保存'
    messageType.value = 'success'
    ElMessage.success('已保存')
  } catch (e: any) {
    message.value = e?.message || '保存失败'
    messageType.value = 'error'
    ElMessage.error(message.value)
  } finally {
    saving.value = false
  }
}

async function resetDefaults() {
  jsonText.value = '{}'
  await save()
}

onMounted(load)
</script>

<style scoped></style>
