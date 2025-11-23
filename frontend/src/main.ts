import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import ElementPlus from 'element-plus'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import 'element-plus/dist/index.css'
import 'material-symbols/index.css'
import './styles/reset.css'
import './styles/material-symbols.css'
import './styles/tailwind.css'
import { vPermission, vRole, vAnyPermission } from './directives/permission'
import { useAuthStore } from './stores/auth'
import { authApi } from './api/auth'
import { getSystemName } from './utils/publicSettings'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(ElementPlus, {
  locale: zhCn,
})

// 注册权限指令
app.directive('permission', vPermission)
app.directive('role', vRole)
app.directive('any-permission', vAnyPermission)

// 初始化系统名称
getSystemName().then(systemName => {
  document.title = systemName
})

// Cookie 认证: 如果有缓存的用户信息,尝试验证 Session
const authStore = useAuthStore()
if (authStore.user) {
  // 验证 Session 是否有效
  authApi
    .me()
    .then(user => {
      authStore.setUser(user)
    })
    .catch(() => {
      // Session 过期,清除缓存的用户信息
      authStore.logout()
    })
}

app.mount('#app')
