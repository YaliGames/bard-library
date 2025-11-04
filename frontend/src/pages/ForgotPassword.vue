<template>
  <div class="text-gray-900 flex justify-center">
    <div
      class="max-w-screen-xl m-0 sm:m-10 bg-white shadow sm:rounded-lg flex justify-center flex-1"
    >
      <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
        <div class="flex flex-col items-center">
          <h1 class="text-2xl xl:text-3xl font-extrabold">找回密码</h1>
          <div class="w-full flex-1 mt-8">
            <div v-if="canRecover" class="mx-auto max-w-xs">
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white"
                type="email"
                v-model="email"
                placeholder="请输入注册邮箱"
              />
              <button
                @click="onSubmit"
                :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                class="mt-5 tracking-wide font-semibold bg-blue-500 text-gray-100 w-full py-4 rounded-lg hover:bg-blue-700 transition-all duration-300 flex items-center justify-center focus:shadow-outline focus:outline-none"
              >
                <span class="material-symbols-outlined text-3xl">
                  <template v-if="!loading">email</template>
                  <template v-else>pending</template>
                </span>
                <span class="ml-3">发送重置邮件</span>
              </button>
              <el-alert v-if="msg" type="success" :title="msg" class="mt-4" />
              <el-alert v-if="err" type="error" :title="err" class="mt-4" />
            </div>
            <div v-else class="mx-auto max-w-xs">
              <el-result icon="warning" title="找回密码已关闭" sub-title="管理员已关闭找回密码功能">
                <template #extra>
                  <router-link :to="{ name: 'login' }">
                    <el-button type="primary">返回登录</el-button>
                  </router-link>
                </template>
              </el-result>
            </div>
            <div class="my-6 border-gray-600 border-b text-center">
              <div
                class="leading-none px-2 inline-block text-sm text-gray-600 tracking-wide font-medium bg-white transform translate-y-1/2"
              >
                已有帐号？
              </div>
            </div>
            <div class="flex flex-col items-center">
              <router-link
                :to="{ name: 'login' }"
                class="w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 outline-none hover:shadow focus:shadow-sm"
              >
                <span class="material-symbols-outlined">person</span>
                <span class="ml-4">立即登录</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
      <div class="flex-1 bg-blue-100 text-center hidden lg:flex">
        <div class="m-12 xl:m-16 w-full bg-contain bg-center bg-no-repeat">
          <img src="/src/images/login-illustration.svg" alt="Forgot image" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { authApi } from '@/api/auth'
import { getPublicPermissions } from '@/utils/publicSettings'

const email = ref('')
const loading = ref(false)
const err = ref('')
const msg = ref('')
const canRecover = ref(true)

onMounted(async () => {
  try {
    const p = await getPublicPermissions()
    canRecover.value = !!p.allow_recover_password
  } catch {}
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
  loading.value = true
  err.value = ''
  msg.value = ''
  try {
    await authApi.forgotPassword(e)
    msg.value = '如果邮箱存在，我们已发送重置邮件'
  } catch (e: any) {
    err.value = e?.message || '请求失败'
  } finally {
    loading.value = false
  }
}
</script>
