<template>
  <div
    class="flex flex-col md:flex-row max-w-7xl mx-auto mt-4 md:mt-8 px-4 md:px-6 md:space-x-10 gap-4 md:gap-0"
  >
    <!-- 左侧菜单 -->
    <aside class="w-full md:w-64 space-y-4 text-sm">
      <div>
        <h2 class="text-xl font-semibold mb-4">个人资料</h2>
        <div class="space-y-1">
          <div
            v-for="m in menu"
            :key="m.id"
            @click="active = m.id"
            :class="[
              'flex items-center px-3 py-2 rounded-md cursor-pointer',
              active === m.id
                ? 'bg-gray-200 text-primary font-medium'
                : 'hover:bg-gray-200 text-gray-700',
            ]"
          >
            <span class="material-symbols-outlined mr-2 text-lg">{{ m.icon }}</span>
            <span>{{ m.label }}</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- 右侧内容 -->
    <section class="flex-1 space-y-6">
      <div class="bg-white shadow-sm rounded-xl p-6">
        <!-- 用户信息 -->
        <div v-if="active === 'intro'">
          <div class="flex items-center gap-4 mb-4">
            <el-avatar :size="48">{{ avatarLetter }}</el-avatar>
            <div>
              <div class="font-medium">{{ profile.email || '—' }}</div>
              <div class="text-xs text-gray-500">
                角色：
                <span
                  v-for="role in profile.roles"
                  :key="(role && (role.id || role.name)) || role"
                  class="inline-block mr-2"
                >
                  {{ role?.display_name }}
                </span>
              </div>
            </div>
          </div>
          <p class="text-sm text-gray-600">
            {{ profile.bio || '暂无简介' }}
          </p>
        </div>

        <!-- 基本信息 -->
        <div v-if="active === 'profile'">
          <h2 class="text-xl font-semibold mb-4">基本信息</h2>
          <el-form ref="profileRef" :model="profile" :rules="profileRules" label-width="0">
            <template v-if="loadingProfile">
              <SettingsItem
                v-for="i in 4"
                :key="i"
                :title="' '"
                :description="''"
                :loading="true"
              />
            </template>
            <template v-else>
              <SettingsItem
                title="昵称"
                description="昵称为必填选项，为显示在站内的名称，用户间的昵称不可重复"
              >
                <el-form-item prop="name" class="w-full">
                  <el-input v-model="profile.name" placeholder="请输入昵称" />
                </el-form-item>
              </SettingsItem>
              <SettingsItem
                title="位置"
                description="可选择填写你的所在城市/地区，将展示在你的个人主页上"
              >
                <el-form-item prop="location" class="w-full">
                  <el-input v-model="profile.location" placeholder="请输入位置" />
                </el-form-item>
              </SettingsItem>
              <SettingsItem
                title="网站"
                description="可选择填写你的个人网站或社交媒体链接，将展示在你的个人主页上"
              >
                <el-form-item prop="website" class="w-full">
                  <el-input v-model="profile.website" placeholder="https://example.com" />
                </el-form-item>
              </SettingsItem>
              <SettingsItem title="简介" description="可选择填写个人简介，将展示在你的个人主页上">
                <el-form-item prop="bio" class="w-full">
                  <el-input
                    v-model="profile.bio"
                    type="textarea"
                    :rows="3"
                    maxlength="2000"
                    show-word-limit
                    placeholder="简要介绍自己"
                  />
                </el-form-item>
              </SettingsItem>
              <div class="flex justify-end">
                <el-button type="primary" :loading="savingProfile" @click="onSaveProfile">
                  保存
                </el-button>
              </div>
            </template>
          </el-form>
        </div>

        <!-- 修改密码 -->
        <div v-if="active === 'password'">
          <h2 class="text-xl font-semibold mb-4">修改密码</h2>
          <el-form ref="pwdRef" :model="pwd" :rules="pwdRules" label-width="0">
            <SettingsItem title="当前密码" description="请输入当前密码">
              <el-form-item prop="current_password" class="w-full">
                <el-input v-model="pwd.current_password" type="password" show-password />
              </el-form-item>
            </SettingsItem>
            <SettingsItem title="新密码" description="请设定一个新密码">
              <el-form-item prop="new_password" class="w-full">
                <el-input v-model="pwd.new_password" type="password" show-password />
              </el-form-item>
            </SettingsItem>
            <SettingsItem title="确认新密码" description="请再次输入新密码">
              <el-form-item prop="new_password2" class="w-full">
                <el-input v-model="pwd.new_password2" type="password" show-password />
              </el-form-item>
            </SettingsItem>
            <div class="flex justify-end">
              <el-button type="primary" :loading="savingPwd" @click="onChangePwd">
                更新密码
              </el-button>
            </div>
          </el-form>
        </div>

        <!-- 删除账号 -->
        <div v-if="active === 'danger'">
          <h2 class="text-xl font-semibold mb-4 text-red-600">删除账号</h2>
          <SettingsItem
            title="危险操作"
            description="申请后 7 个工作日执行删除，可在此期间撤回，删除后不可恢复。"
          >
            <div class="flex items-center justify-end">
              <el-button type="danger" :loading="deleting" @click="onRequestDelete">
                申请删除账号
              </el-button>
            </div>
          </SettingsItem>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { authApi } from '@/api/auth'
import { type User } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import SettingsItem from '@/components/Settings/SettingsItem.vue'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useSimpleLoading } from '@/composables/useLoading'

const { handleError, handleSuccess } = useErrorHandler()
const { setUser } = useAuthStore()

const profileRef = ref()
const pwdRef = ref()
const profile = reactive<User & { roles?: Array<any> }>({ id: 0, name: '', email: '', roles: [] })
const savingProfile = ref(false)
const { loading: loadingProfile, setLoading: setLoadingProfile } = useSimpleLoading(true)

const pwd = reactive<{ current_password: string; new_password: string; new_password2: string }>({
  current_password: '',
  new_password: '',
  new_password2: '',
})
const savingPwd = ref(false)

const deleting = ref(false)

const avatarLetter = computed(() => (profile.name?.[0] || profile.email?.[0] || 'U').toUpperCase())

type MenuId = 'intro' | 'profile' | 'password' | 'danger'
const active = ref<MenuId>('intro')
const menu: Array<{ id: MenuId; label: string; icon: string }> = [
  { id: 'intro', label: '用户信息', icon: 'info' },
  { id: 'profile', label: '基本信息', icon: 'person' },
  { id: 'password', label: '修改密码', icon: 'lock' },
  { id: 'danger', label: '删除账号', icon: 'delete_forever' },
]

const profileRules = {
  name: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  location: [{ max: 120, message: '最多 120 字符', trigger: 'blur' }],
  website: [
    { max: 200, message: '最多 200 字符', trigger: 'blur' },
    { pattern: /^(https?:)?\/\//i, message: '应以 http(s):// 开头', trigger: 'blur' },
  ],
  bio: [{ max: 2000, message: '最多 2000 字符', trigger: 'blur' }],
}

const pwdRules = {
  current_password: [{ required: true, message: '请输入当前密码', trigger: 'blur' }],
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '至少 6 位', trigger: 'blur' },
  ],
  new_password2: [
    { required: true, message: '请再次输入新密码', trigger: 'blur' },
    {
      validator: (_: any, v: string, cb: any) => {
        if (v !== pwd.new_password) cb(new Error('两次输入不一致'))
        else cb()
      },
      trigger: 'blur',
    },
  ],
}

onMounted(async () => {
  try {
    const me = await authApi.me()
    profile.name = me.name
    profile.email = me.email
    const rawRoles = Array.isArray(me.roles) ? me.roles : me.role ? [me.role] : []
    profile.roles = rawRoles.map((r: any) =>
      typeof r === 'string' ? { id: 0, name: r } : r,
    ) as any[]
    profile.location = me.location || ''
    profile.website = me.website || ''
    profile.bio = me.bio || ''
  } catch (e: any) {
    handleError(e, { context: 'Profile.loadUserInfo' })
  } finally {
    setLoadingProfile(false)
  }
})

function onSaveProfile() {
  ;(profileRef.value as any).validate(async (ok: boolean) => {
    if (!ok) return
    savingProfile.value = true
    try {
      const updated = await authApi.updateMe({
        name: profile.name,
        location: profile.location,
        website: profile.website,
        bio: profile.bio,
      })
      // 同步到全局 user（至少 name 会更新）
      setUser(updated as any)
      handleSuccess('已保存')
    } catch (e: any) {
      handleError(e, { context: 'Profile.saveProfile' })
    } finally {
      savingProfile.value = false
    }
  })
}

function onChangePwd() {
  ;(pwdRef.value as any).validate(async (ok: boolean) => {
    if (!ok) return
    savingPwd.value = true
    try {
      await authApi.changePassword({
        current_password: pwd.current_password,
        new_password: pwd.new_password,
      })
      handleSuccess('密码已更新')
      pwd.current_password = pwd.new_password = pwd.new_password2 = ''
    } catch (e: any) {
      handleError(e, { context: 'Profile.changePassword' })
    } finally {
      savingPwd.value = false
    }
  })
}

async function onRequestDelete() {
  deleting.value = true
  try {
    await authApi.requestDelete()
    handleSuccess('已提交删除申请，我们会尽快处理')
  } catch (e: any) {
    handleError(e, { context: 'Profile.requestDelete' })
  } finally {
    deleting.value = false
  }
}
</script>
