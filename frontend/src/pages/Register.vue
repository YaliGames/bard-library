<template>
  <div class="text-gray-900 flex justify-center">
    <div
      class="max-w-screen-xl m-0 sm:m-10 bg-white shadow sm:rounded-lg flex justify-center flex-1"
    >
      <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
        <div class="flex flex-col items-center">
          <h1 class="text-2xl xl:text-3xl font-extrabold">加入 Bard Library</h1>
          <div class="w-full flex-1 mt-8">
            <div v-if="canRegister" class="mx-auto max-w-xs">
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white"
                type="text"
                v-model="form.name"
                placeholder="请输入昵称"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="email"
                v-model="form.email"
                placeholder="请输入邮箱"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="password"
                v-model="form.password"
                placeholder="请输入密码"
              />
              <input
                class="w-full px-8 py-4 rounded-lg font-medium bg-gray-100 border border-gray-200 placeholder-gray-500 text-sm focus:outline-none focus:border-gray-400 focus:bg-white mt-4"
                type="password"
                v-model="form.password2"
                placeholder="请再次输入密码"
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
                  <template v-if="!loading">person_add</template>
                  <template v-else>pending</template>
                </span>
                <span class="ml-3">注册</span>
              </button>
              <el-alert v-if="err" type="error" :title="err" class="mt-4" />
              <el-alert v-if="msg" type="success" :title="msg" class="mt-4" />
              <p class="mt-6 text-xs text-gray-600 text-center">
                注册则表示您同意我们的
                <a href="#" class="border-b border-gray-500 border-dotted">服务条款</a>
                和
                <a href="#" class="border-b border-gray-500 border-dotted">隐私政策</a>
              </p>
            </div>
            <div v-else class="mx-auto max-w-xs">
              <el-result icon="warning" title="注册已关闭" sub-title="管理员已关闭新用户注册功能">
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
              <button
                class="cursor-not-allowed bg-gray-200 w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 outline-none hover:shadow focus:shadow-sm mt-4"
              >
                <div class="bg-white p-2 rounded-full">
                  <svg class="w-4" viewBox="0 0 533.5 544.3">
                    <path
                      d="M533.5 278.4c0-18.5-1.5-37.1-4.7-55.3H272.1v104.8h147c-6.1 33.8-25.7 63.7-54.4 82.7v68h87.7c51.5-47.4 81.1-117.4 81.1-200.2z"
                      fill="#4285f4"
                    />
                    <path
                      d="M272.1 544.3c73.4 0 135.3-24.1 180.4-65.7l-87.7-68c-24.4 16.6-55.9 26-92.6 26-71 0-131.2-47.9-152.8-112.3H28.9v70.1c46.2 91.9 140.3 149.9 243.2 149.9z"
                      fill="#34a853"
                    />
                    <path
                      d="M119.3 324.3c-11.4-33.8-11.4-70.4 0-104.2V150H28.9c-38.6 76.9-38.6 167.5 0 244.4l90.4-70.1z"
                      fill="#fbbc04"
                    />
                    <path
                      d="M272.1 107.7c38.8-.6 76.3 14 104.4 40.8l77.7-77.7C405 24.6 339.7-.8 272.1 0 169.2 0 75.1 58 28.9 150l90.4 70.1c21.5-64.5 81.8-112.4 152.8-112.4z"
                      fill="#ea4335"
                    />
                  </svg>
                </div>
                <span class="ml-4">通过 Google 登录</span>
              </button>
              <router-link
                :to="{ name: 'forgot' }"
                v-if="false"
                class="w-full max-w-xs font-bold shadow-sm rounded-lg py-3 bg-blue-100 text-gray-800 flex items-center justify-center transition-all duration-300 outline-none hover:shadow focus:shadow-sm mt-4"
              >
                <span class="material-symbols-outlined">passkey</span>
                <span class="ml-4">找回密码</span>
              </router-link>
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
import { reactive, ref, onMounted } from 'vue'
import { authApi } from '@/api/auth'
import { getPublicPermissions } from '@/utils/publicSettings'

const form = reactive({ name: '', email: '', password: '', password2: '' })
const loading = ref(false)
const err = ref('')
const msg = ref('')
const canRegister = ref(true)

onMounted(async () => {
  try {
    const p = await getPublicPermissions()
    canRegister.value = !!p.allow_user_registration
  } catch {}
})

function validateForm(): boolean {
  err.value = ''
  const name = (form.name || '').trim()
  if (!name) {
    err.value = '请输入昵称'
    return false
  }
  if (name.length < 2 || name.length > 20) {
    err.value = '长度 2-20 个字符'
    return false
  }

  const email = (form.email || '').trim()
  if (!email) {
    err.value = '请输入邮箱'
    return false
  }
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRe.test(email)) {
    err.value = '邮箱格式不正确'
    return false
  }

  if (!form.password) {
    err.value = '请输入密码'
    return false
  }
  if (form.password.length < 6) {
    err.value = '至少 6 位'
    return false
  }

  if (!form.password2) {
    err.value = '请再次输入密码'
    return false
  }
  if (form.password !== form.password2) {
    err.value = '两次输入的密码不一致'
    return false
  }

  return true
}

async function onSubmit() {
  if (loading.value) return
  if (!validateForm()) return
  loading.value = true
  err.value = ''
  msg.value = ''
  try {
    await authApi.register(form.name, form.email, form.password)
    msg.value = '注册成功，请前往邮箱点击验证链接完成激活后再登录'
  } catch (e: any) {
    err.value = e?.message || '注册失败'
  } finally {
    loading.value = false
  }
}
</script>
