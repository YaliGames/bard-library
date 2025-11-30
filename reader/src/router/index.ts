import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
    history: createWebHashHistory(), // Hash history is better for file:// and Capacitor
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('../views/LoginView.vue')
        },
        {
            path: '/books',
            name: 'books',
            component: () => import('../views/BookList.vue')
        },
        {
            path: '/read/:id',
            name: 'read',
            component: () => import('../views/TxtReader.vue')
        },
        {
            path: '/cache',
            name: 'cache',
            component: () => import('../views/CacheManagement.vue')
        }
    ]
})

export default router
