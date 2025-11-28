import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

import './styles/main.css'

import App from './App.vue'
import router from './router'
import { useNetworkStore, useReadingStore } from './stores'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(ElementPlus)

// 初始化网络监听
const networkStore = useNetworkStore()
networkStore.initNetworkListener()

// 初始化阅读进度
const readingStore = useReadingStore()
readingStore.loadProgress()

app.mount('#app')
