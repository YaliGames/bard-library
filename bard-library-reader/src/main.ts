import { createApp } from 'vue'import { createApp } from 'vue'

import { createPinia } from 'pinia'import { createPinia } from 'pinia'

import ElementPlus from 'element-plus'import ElementPlus from 'element-plus'

import 'element-plus/dist/index.css'import 'element-plus/dist/index.css'

import './style.css'

import App from './App.vue'import './styles/main.css'



const app = createApp(App)import App from './App.vue'

import router from './router'

app.use(createPinia())import { useNetworkStore, useReadingStore } from './stores'

app.use(ElementPlus)

const app = createApp(App)

app.mount('#app')
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
