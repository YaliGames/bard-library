<template>
  <div class="text-gray-900 flex justify-center">
    <div
      class="max-w-screen-xl m-0 sm:m-10 bg-white shadow sm:rounded-lg flex justify-center flex-1"
    >
      <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
        <div class="flex flex-col items-center">
          <h1 class="text-2xl xl:text-3xl font-extrabold">重置密码</h1>
          <div class="w-full flex-1 mt-8">
            <div v-if="canRecover" class="mx-auto max-w-xs">
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white"
                type="email"
                v-model="email"
                placeholder="请输入邮箱"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="text"
                v-model="token"
                placeholder="请输入邮件中的重置令牌"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="password"
                v-model="password"
                placeholder="请输入新密码"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="password"
                v-model="password2"
                placeholder="请再次输入新密码"
              />
              <button
                @click="onSubmit"
                :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                class="mt-5 tracking-wide font-semibold bg-blue-500 text-gray-100 w-full py-4 rounded-lg hover:bg-blue-700 transition-all duration-300 flex items-center justify-center focus:shadow-outline focus:outline-none"
              >
                <span class="material-symbols-outlined text-3xl">
                  <template v-if="!loading">lock_reset</template>
                  <template v-else>pending</template>
                </span>
                <span class="ml-3">提交</span>
              </button>
              <el-alert v-if="msg" type="success" :title="msg" class="mt-4" />
              <el-alert v-if="err" type="error" :title="err" class="mt-4" />
            </div>
            <div v-else class="mx-auto max-w-xs">
              <el-result icon="warning" title="重置密码已关闭" sub-title="管理员已关闭找回密码功能">
                <template #extra>
                  <router-link :to="{ name: 'login' }">
                    <el-button type="primary">返回登录</el-button>
                  </router-link>
                </template>
              </el-result>
            </div>
          </div>
        </div>
      </div>
      <div class="flex-1 bg-blue-100 text-center hidden lg:flex">
        <div class="m-12 xl:m-16 w-full bg-contain bg-center bg-no-repeat">
          <img src="/src/images/login-illustration.svg" alt="Reset image" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { getPublicPermissions } from '@/utils/publicSettings'

const route = useRoute()
const router = useRouter()

const email = ref('')
const token = ref('')
const password = ref('')
const password2 = ref('')
const loading = ref(false)
const err = ref('')
const msg = ref('')
const canRecover = ref(true)

onMounted(() => {
  // 支持从 query 带入 token/email
  const q = route.query
  if (typeof q.token === 'string') token.value = q.token
  if (typeof q.email === 'string') email.value = q.email
  getPublicPermissions()
    .then(p => {
      canRecover.value = !!p.allow_recover_password
    })
    .catch(() => {})
})

async function onSubmit() {
  const e = (email.value || '').trim()
  if (!e) {
    err.value = '请输入邮箱'
    return
  }
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRe.test(e)) {
    err.value = '邮箱格式不正确'
    return
  }
  if (!token.value) {
    err.value = '请输入重置令牌'
    return
  }
  if (!password.value) {
    err.value = '请输入新密码'
    return
  }
  if (password.value.length < 6) {
    err.value = '密码至少 6 位'
    return
  }
  if (password.value !== password2.value) {
    err.value = '两次输入的密码不一致'
    return
  }
  loading.value = true
  err.value = ''
  msg.value = ''
  try {
    await authApi.resetPassword({ email: e, token: token.value, password: password.value })
    msg.value = '密码已重置，请使用新密码登录'
    setTimeout(() => router.replace({ name: 'login' }), 1200)
  } catch (e: any) {
    err.value = e?.message || '重置失败'
  } finally {
    loading.value = false
  }
}
</script>
