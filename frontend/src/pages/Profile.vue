<template>
  <section class="container mx-auto px-4 py-6 max-w-4xl">
    <h2 class="text-2xl font-semibold mb-6">个人资料</h2>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <div class="flex items-center gap-4">
        <el-avatar :size="40">{{ avatarLetter }}</el-avatar>
        <div>
          <div class="font-medium">{{ profile.email }}</div>
          <div class="text-xs text-gray-500">角色：{{ profile.role || 'user' }}</div>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <div class="text-lg font-medium mb-2">个人资料</div>
      <el-form ref="profileRef" :model="profile" :rules="profileRules" label-width="100px">
        <el-form-item label="昵称" prop="name">
          <el-input v-model="profile.name" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="位置" prop="location">
          <el-input v-model="profile.location" placeholder="请输入位置" />
        </el-form-item>
        <el-form-item label="网站" prop="website">
          <el-input v-model="profile.website" placeholder="https://example.com" />
        </el-form-item>
        <el-form-item label="简介" prop="bio">
          <el-input v-model="profile.bio" type="textarea" :rows="3" maxlength="2000" show-word-limit
            placeholder="简要介绍自己" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="savingProfile" @click="onSaveProfile">保存</el-button>
          <span class="text-green-600 text-sm" v-if="profileSaved">已保存</span>
        </el-form-item>
      </el-form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
      <div class="text-lg font-medium mb-2">修改密码</div>
      <el-form ref="pwdRef" :model="pwd" :rules="pwdRules" label-width="100px">
        <el-form-item label="当前密码" prop="current_password">
          <el-input v-model="pwd.current_password" type="password" show-password />
        </el-form-item>
        <el-form-item label="新密码" prop="new_password">
          <el-input v-model="pwd.new_password" type="password" show-password />
        </el-form-item>
        <el-form-item label="确认新密码" prop="new_password2">
          <el-input v-model="pwd.new_password2" type="password" show-password />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="savingPwd" @click="onChangePwd">更新密码</el-button>
          <el-alert class="mt-4" v-if="pwdMsg" :title="pwdMsg" :type="pwdOk ? 'success' : 'error'" show-icon />
        </el-form-item>
      </el-form>
    </div>

    <div class="border-red-300 bg-white rounded-lg shadow-sm p-4 mb-4">
      <div class="text-lg font-medium mb-2">删除账号</div>
      <div class="space-y-4">
        <p class="text-gray-600 text-sm">
          你可以申请删除账号，申请提交后7个工作日将执行删除，期间你可以访问个人资料页面撤回申请，删除后不可找回。<br />
          账号删除后，相关的数据将被清理或匿名化。
        </p>
        <el-button type="danger" :loading="deleting" @click="onRequestDelete">申请删除账号</el-button>
        <el-alert v-if="deleteMsg" :title="deleteMsg" :type="deleteOk ? 'success' : 'error'" show-icon />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const { setUser } = useAuthStore()

const profileRef = ref()
const pwdRef = ref()
const profile = reactive<{ name: string; email: string; role?: string; location?: string; website?: string; bio?: string }>({ name: '', email: '' })
const savingProfile = ref(false)
const profileSaved = ref(false)

const pwd = reactive<{ current_password: string; new_password: string; new_password2: string }>({ current_password: '', new_password: '', new_password2: '' })
const savingPwd = ref(false)
const pwdMsg = ref('')
const pwdOk = ref(false)

const deleting = ref(false)
const deleteMsg = ref('')
const deleteOk = ref(false)

const avatarLetter = computed(() => (profile.name?.[0] || profile.email?.[0] || 'U').toUpperCase())

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
  new_password: [{ required: true, message: '请输入新密码', trigger: 'blur' }, { min: 6, message: '至少 6 位', trigger: 'blur' }],
  new_password2: [{ required: true, message: '请再次输入新密码', trigger: 'blur' }, {
    validator: (_: any, v: string, cb: any) => { if (v !== pwd.new_password) cb(new Error('两次输入不一致')); else cb(); }, trigger: 'blur'
  }],
}

onMounted(async () => {
  const me = await authApi.me()
  profile.name = me.name
  profile.email = me.email
  profile.role = me.role
  // @ts-ignore 兼容后端新增字段
  profile.location = me.location || ''
  // @ts-ignore
  profile.website = me.website || ''
  // @ts-ignore
  profile.bio = me.bio || ''
})

function onSaveProfile() {
  (profileRef.value as any).validate(async (ok: boolean) => {
    if (!ok) return
    savingProfile.value = true
    profileSaved.value = false
    try {
      const updated = await authApi.updateMe({ name: profile.name, location: profile.location, website: profile.website, bio: profile.bio })
      // 同步到全局 user（至少 name 会更新）
      setUser(updated as any)
      profileSaved.value = true
      setTimeout(() => profileSaved.value = false, 1500)
    } catch (e: any) { /* 可加 toast */ }
    finally { savingProfile.value = false }
  })
}

function onChangePwd() {
  (pwdRef.value as any).validate(async (ok: boolean) => {
    if (!ok) return
    savingPwd.value = true
    pwdMsg.value = ''
    pwdOk.value = false
    try {
      await authApi.changePassword({ current_password: pwd.current_password, new_password: pwd.new_password })
      pwdOk.value = true
      pwdMsg.value = '密码已更新'
      pwd.current_password = pwd.new_password = pwd.new_password2 = ''
    } catch (e: any) {
      pwdOk.value = false
      pwdMsg.value = e?.message || '更新失败'
    } finally { savingPwd.value = false }
  })
}

async function onRequestDelete() {
  deleting.value = true
  deleteMsg.value = ''
  deleteOk.value = false
  try {
    await authApi.requestDelete()
    deleteOk.value = true
    deleteMsg.value = '已提交删除申请，我们会尽快处理'
  } catch (e: any) {
    deleteOk.value = false
    deleteMsg.value = e?.message || '操作失败'
  } finally { deleting.value = false }
}
</script>
