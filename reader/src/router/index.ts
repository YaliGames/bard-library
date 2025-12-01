import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('../views/LoginView.vue'),
            meta: { hideBottomBar: true }
        },
        {
            path: '/',
            component: () => import('../layouts/MainLayout.vue'),
            children: [
                {
                    path: '',
                    name: 'home',
                    component: HomeView
                },
                {
                    path: 'books',
                    name: 'books',
                    component: () => import('../views/BookList.vue')
                },
                {
                    path: 'cache',
                    name: 'cache',
                    component: () => import('../views/CacheManagement.vue')
                },
                {
                    path: 'cache-settings',
                    name: 'cache-settings',
                    component: () => import('../views/CacheSettings.vue')
                }
            ]
        },
        {
            path: '/read/:id',
            name: 'read',
            component: () => import('../views/TxtReader.vue'),
            meta: { hideBottomBar: true }
        }
    ]
})

export default router
