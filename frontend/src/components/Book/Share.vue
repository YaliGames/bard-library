<template>
  <div class="flex flex-row">
    <el-button
      size="large"
      class="rounded-r-none"
      :disabled="files.length === 0"
      @click="handleSendCommand('account')"
    >
      发送到
      <span class="material-symbols-outlined ml-1">mail</span>
    </el-button>
    <el-dropdown trigger="click" class="ml-0" @command="handleSendCommand">
      <span class="el-dropdown-link">
        <el-button
          size="large"
          class="rounded-l-none border-l-0 px-0"
          :disabled="files.length === 0"
        >
          <span class="material-symbols-outlined">arrow_drop_down</span>
        </el-button>
      </span>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item command="email">
            <span class="material-symbols-outlined mr-1">mail</span>
            <span>邮箱</span>
          </el-dropdown-item>
          <el-dropdown-item command="kindle">
            <span class="material-symbols-outlined mr-1">aod_tablet</span>
            <span>Kindle</span>
          </el-dropdown-item>
          <el-dropdown-item command="kindle">
            <span class="material-symbols-outlined mr-1">add_to_drive</span>
            <span>GoogleDrive</span>
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>

    <!-- 发送邮件弹窗 -->
    <el-dialog v-model="showEmailDialog" title="发送图书到邮箱" width="500px">
      <el-form :model="emailForm" label-width="90px">
        <el-form-item label="选择文件">
          <el-select v-model="emailForm.fileId" placeholder="选择要发送的文件" style="width: 100%">
            <el-option
              v-for="f in files"
              :key="f.id"
              :label="`${(f.format || '').toUpperCase()} - ${humanSize(f.size || 0)}`"
              :value="f.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="邮箱类型">
          <el-radio-group v-model="emailForm.emailType" @change="handleEmailTypeChange">
            <el-radio value="account">账号注册邮箱</el-radio>
            <el-radio value="kindle">Kindle 邮箱</el-radio>
            <el-radio value="other">其他邮箱</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="邮箱地址">
          <el-input
            v-model="emailForm.email"
            :placeholder="
              emailForm.emailType === 'kindle' && !kindleEmail
                ? '请设置 Kindle 邮箱'
                : '请输入接收邮箱地址'
            "
            type="email"
            :disabled="
              emailForm.emailType === 'account' ||
              (emailForm.emailType === 'kindle' && !!kindleEmail) ||
              (emailForm.emailType === 'kindle' && !kindleEmail)
            "
            clearable
          />
        </el-form-item>
        <el-alert
          v-if="emailForm.emailType === 'account'"
          type="info"
          :closable="false"
          class="mb-4"
          title="提示"
          :description="`图书文件将发送到您的账号邮箱: ${userEmail}`"
        />
        <el-alert
          v-else-if="emailForm.emailType === 'kindle' && kindleEmail"
          type="info"
          :closable="false"
          class="mb-4"
          title="提示"
          :description="`图书文件将发送到您的 Kindle 邮箱: ${kindleEmail}`"
        />
        <el-alert
          v-else-if="emailForm.emailType === 'kindle' && !kindleEmail"
          type="warning"
          :closable="false"
          class="mb-4"
          title="未设置 Kindle 邮箱"
        >
          <template #default>
            <p class="mb-2">您还没有设置 Kindle 邮箱地址。</p>
            <p>
              请前往
              <router-link to="/user/profile" class="text-blue-600 hover:underline font-medium">
                个人资料页面
              </router-link>
              维护您的 Kindle 邮箱，或者选择"其他邮箱"手动输入。
            </p>
          </template>
        </el-alert>
        <el-alert
          v-else
          type="info"
          :closable="false"
          class="mb-4"
          title="提示"
          description="图书文件将作为附件发送到指定邮箱。"
        />
      </el-form>
      <template #footer>
        <el-button @click="showEmailDialog = false">取消</el-button>
        <el-button
          type="primary"
          :loading="sending"
          :disabled="!emailForm.fileId || !emailForm.email"
          @click="handleSendEmail"
        >
          发送
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import type { FileRec } from '@/api/types'
import { computed, ref, watch } from 'vue'
import { booksApi } from '@/api'
import { ElMessage } from 'element-plus'

const props = defineProps<{
  files: FileRec[]
  bookId: number
  user?: any
}>()

const files = computed(() => props.files || [])
const userEmail = computed(() => props.user?.email || '')
const kindleEmail = computed(() => props.user?.kindle_email || '')

// 邮件发送相关状态
const showEmailDialog = ref(false)
const sending = ref(false)
const emailForm = ref({
  fileId: null as number | null,
  email: '',
  emailType: 'account' as 'account' | 'kindle' | 'other',
})

// 当弹窗打开时，默认选择第一个文件
watch(showEmailDialog, newVal => {
  if (newVal && files.value.length > 0) {
    emailForm.value.fileId = files.value[0].id
  } else if (!newVal) {
    // 关闭时重置表单
    emailForm.value = {
      fileId: null,
      email: '',
      emailType: 'account',
    }
  }
})

// 处理发送到下拉菜单命令
function handleSendCommand(command: 'account' | 'kindle' | 'email') {
  showEmailDialog.value = true

  // 根据命令设置默认的邮箱类型和地址
  if (command === 'account' || command === 'email') {
    emailForm.value.emailType = 'account'
    emailForm.value.email = userEmail.value
  } else if (command === 'kindle') {
    emailForm.value.emailType = 'kindle'
    emailForm.value.email = kindleEmail.value
  }
}

// 处理邮箱类型变更
function handleEmailTypeChange(type: 'account' | 'kindle' | 'other') {
  if (type === 'account') {
    emailForm.value.email = userEmail.value
  } else if (type === 'kindle') {
    emailForm.value.email = kindleEmail.value
  } else {
    emailForm.value.email = ''
  }
}

// 发送邮件
async function handleSendEmail() {
  if (!emailForm.value.fileId || !emailForm.value.email) {
    return
  }

  sending.value = true
  try {
    await booksApi.sendEmail(props.bookId, emailForm.value.fileId, emailForm.value.email)
    ElMessage.success('邮件发送成功！请查收邮箱。')
    showEmailDialog.value = false
  } catch (error: any) {
    ElMessage.error(error.message || '发送失败，请稍后重试')
  } finally {
    sending.value = false
  }
}

function humanSize(n: number) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  let v = n
  while (v >= 1024 && i < units.length - 1) {
    v /= 1024
    i++
  }
  return `${v.toFixed(1)} ${units[i]}`
}
</script>
