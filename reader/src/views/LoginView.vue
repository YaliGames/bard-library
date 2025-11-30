<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { authApi } from '@/api/auth'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const userStore = useUserStore()

const form = ref({
  email: '',
  password: ''
})

const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  try {
    if (form.value.email && form.value.password) {
      await authApi.login(form.value.email, form.value.password)
      ElMessage.success('Login successful')
      router.push('/')
    } else {
      throw new Error('Please enter email and password')
    }
  } catch (error: any) {
    ElMessage.error(error.message || 'Login failed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="h-full flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <el-card class="w-full max-w-md">
      <template #header>
        <div class="text-center">
          <h2 class="text-xl font-bold">Bard Reader Login</h2>
        </div>
      </template>
      
      <el-form :model="form" @submit.prevent="handleLogin">
        <el-form-item>
          <el-input 
            v-model="form.email" 
            placeholder="Email"
            :prefix-icon="User"
          />
        </el-form-item>
        
        <el-form-item>
          <el-input 
            v-model="form.password" 
            type="password" 
            placeholder="Password"
            :prefix-icon="Lock"
            show-password
          />
        </el-form-item>
        
        <el-button 
          type="primary" 
          class="w-full" 
          :loading="loading" 
          @click="handleLogin"
        >
          Login
        </el-button>
      </el-form>
    </el-card>
  </div>
</template>
