<template>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-slate-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-orange-500">notifications_active</span>
        系统通知
      </h3>
      <div class="flex gap-2">
        <el-button link size="small" type="primary">全部已读</el-button>
      </div>
    </div>

    <div class="space-y-1">
      <div
        v-for="note in notifications"
        :key="note.id"
        class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group cursor-default"
      >
        <div
          :class="`w-10 h-10 shrink-0 flex items-center justify-center rounded-full ${getNoticeColor(note.type).bg} ${getNoticeColor(note.type).text}`"
        >
          <span class="material-symbols-outlined text-[20px]">
            {{ getNoticeIcon(note.type) }}
          </span>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-start">
            <h4 class="text-sm font-semibold text-slate-700 truncate pr-2">
              {{ note.title }}
            </h4>
            <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ note.time }}</span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-relaxed">
            {{ note.content }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Notification {
  id: number
  type: 'info' | 'success' | 'warning' | 'error'
  title: string
  content: string
  time: string
}

const notifications = ref<Notification[]>([
  {
    id: 1,
    type: 'warning',
    title: '存储空间不足',
    content: 'SSD 剩余空间低于 20%，建议清理旧缓存。',
    time: '10分钟前',
  },
  {
    id: 2,
    type: 'success',
    title: '批量刮削完成',
    content: '任务 #202505 成功导入 128 本图书元数据。',
    time: '1小时前',
  },
  {
    id: 3,
    type: 'info',
    title: '新用户注册',
    content: '用户 "LibraryAdmin" 刚刚加入了系统。',
    time: '2小时前',
  },
])

const getNoticeColor = (type: string) => {
  const map: any = {
    info: { bg: 'bg-blue-100', text: 'text-blue-600' },
    success: { bg: 'bg-emerald-100', text: 'text-emerald-600' },
    warning: { bg: 'bg-orange-100', text: 'text-orange-600' },
    error: { bg: 'bg-red-100', text: 'text-red-600' },
  }
  return map[type] || map.info
}

const getNoticeIcon = (type: string) => {
  const map: any = { info: 'info', success: 'check', warning: 'priority_high', error: 'error' }
  return map[type] || 'info'
}
</script>
