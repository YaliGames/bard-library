<template>
  <div class="text-gray-900 flex justify-center">
    <div
      class="max-w-screen-xl m-0 sm:m-10 bg-white shadow sm:rounded-lg flex justify-center flex-1"
    >
      <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
        <div class="flex flex-col items-center">
          <h1 class="text-2xl xl:text-3xl font-extrabold">登录 Bard Library</h1>
          <div class="w-full flex-1 mt-8">
            <div class="mx-auto max-w-xs">
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white"
                type="email"
                v-model="email"
                placeholder="邮箱"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="password"
                v-model="password"
                placeholder="密码"
              />
              <button
                @click="
                  () => {
                    loading ? null : onSubmit()
                  }
                "
                :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                class="mt-5 tracking-wide font-semibold bg-blue-500 text-gray-100 w-full py-4 rounded-lg hover:bg-blue-700 transition-all duration-300 flex items-center justify-center focus:shadow-outline focus:outline-none"
              >
                <span class="material-symbols-outlined text-3xl">
                  <template v-if="!loading">person</template>
                  <template v-else>pending</template>
                </span>
                <span class="ml-3">登录</span>
              </button>
              <button
                v-if="needVerify"
                @click="
                  () => {
                    resending ? null : onResend()
                  }
                "
                :class="resending ? 'opacity-50 cursor-not-allowed' : ''"
                class="w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 focus:outline-none hover:shadow focus:shadow-sm focus:shadow-outline mt-4"
              >
                重发验证邮件
              </button>
              <el-alert v-if="err" type="error" :title="err" show-icon class="mt-4" />
              <el-alert v-if="msg" type="success" :title="msg" show-icon class="mt-4" />
              <p class="mt-6 text-xs text-gray-600 text-center">
                登录则表示您同意我们的
                <a href="#" class="border-b border-gray-500 border-dotted">服务条款</a>
                和
                <a href="#" class="border-b border-gray-500 border-dotted">隐私政策</a>
              </p>
            </div>
            <div class="my-6 border-gray-600 border-b text-center">
              <div
                class="leading-none px-2 inline-block text-sm text-gray-600 tracking-wide font-medium bg-white transform translate-y-1/2"
              >
                没有帐号或忘记密码？
              </div>
            </div>
            <div class="flex flex-col items-center">
              <router-link
                v-if="canRegister"
                :to="{ name: 'register' }"
                class="w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 outline-none hover:shadow focus:shadow-sm"
              >
                <span class="material-symbols-outlined">person_add</span>
                <span class="ml-4">立即注册</span>
              </router-link>
              <router-link
                v-if="canRecover"
                :to="{ name: 'forgot' }"
                class="w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 outline-none hover:shadow focus:shadow-sm mt-4"
              >
                <span class="material-symbols-outlined">passkey</span>
                <span class="ml-4">找回密码</span>
              </router-link>
              <div v-if="!canRegister || !canRecover" class="text-xs text-gray-500 mt-3">
                <template v-if="!canRegister">管理员已关闭注册</template>
                <template v-if="!canRegister && !canRecover">；</template>
                <template v-if="!canRecover">管理员已关闭找回密码</template>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="flex-1 bg-blue-100 text-center hidden lg:flex">
        <div class="m-12 xl:m-16 w-full bg-contain bg-center bg-no-repeat">
          <img src="/src/images/login-illustration.svg" alt="Login image" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { getPublicPermissions } from '@/utils/publicSettings'

const router = useRouter()
const { setUser } = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const err = ref('')
const msg = ref('')
const needVerify = ref(false)
const resending = ref(false)
const canRegister = ref(true)
const canRecover = ref(true)

onMounted(async () => {
  try {
    const p = await getPublicPermissions()
    canRegister.value = !!p.allow_user_registration
    canRecover.value = !!p.allow_recover_password
  } catch {}
})

async function onSubmit() {
  if (!validateForm()) return
  loading.value = true
  err.value = ''
  try {
    const res = await authApi.login(email.value, password.value)
    if (res?.user) {
      setUser(res.user as any)
    } else {
      try {
        const me = await authApi.me()
        setUser(me as any)
      } catch {}
    }
    const redirect = (router.currentRoute.value.query.redirect as string) || ''
    if (redirect) {
      router.replace(redirect)
    } else {
      router.replace({ name: 'admin-book-list' })
    }
  } catch (e: any) {
    if (e?.status === 403) {
      needVerify.value = true
      err.value = '邮箱未验证，请先完成邮箱验证'
    } else {
      err.value = e?.message || '登录失败'
    }
  } finally {
    loading.value = false
  }
}

function validateForm(): boolean {
  err.value = ''
  const e = (email.value || '').trim()
  if (!e) {
    err.value = '请输入邮箱'
    return false
  }
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRe.test(e)) {
    err.value = '邮箱格式不正确'
    return false
  }

  if (!password.value) {
    err.value = '请输入密码'
    return false
  }
  if (password.value.length < 6) {
    err.value = '至少 6 位'
    return false
  }
  return true
}

async function onResend() {
  if (!email.value) {
    err.value = '请先填写邮箱'
    return
  }
  resending.value = true
  err.value = ''
  msg.value = ''
  try {
    await authApi.resendVerification(email.value)
    msg.value = '验证邮件已发送，请前往邮箱查收'
  } catch (e: any) {
    err.value = e?.message || '验证邮件发送失败'
  } finally {
    resending.value = false
  }
}
</script>
