import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/pages/BookList.vue'),
    },
    {
      path: '/books/:id',
      name: 'book-detail',
      component: () => import('@/pages/BookDetail.vue'),
    },
    {
      path: '/reader',
      children: [
        {
          path: 'txt/:id',
          name: 'reader-txt',
          component: () => import('@/pages/Reader/TxtReader.vue'),
        },
        {
          path: 'pdf/:id',
          name: 'reader-pdf',
          component: () => import('@/pages/Reader/PdfReader.vue'),
        },
        {
          path: 'epub/:id',
          name: 'reader-epub',
          component: () => import('@/pages/Reader/EpubReader.vue'),
        },
      ],
    },
    {
      path: '/cache',
      name: 'cache',
      component: () => import('@/pages/CacheManagement.vue'),
    },
  ],
})

export default router
