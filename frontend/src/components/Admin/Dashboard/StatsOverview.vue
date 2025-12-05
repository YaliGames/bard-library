<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div
      v-for="(stat, index) in statsData"
      :key="index"
      class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow"
    >
      <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ stat.label }}</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ stat.value }}</p>
        <p
          class="text-xs mt-1 font-medium"
          :class="stat.trend > 0 ? 'text-green-600' : 'text-red-500'"
        >
          {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}% 较上周
        </p>
      </div>
      <div
        :class="`w-12 h-12 flex items-center justify-center rounded-full ${stat.bgColor} ${stat.textColor}`"
      >
        <span class="material-symbols-outlined text-[24px] select-none">{{ stat.icon }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const statsData = [
  {
    label: '总藏书量',
    value: '12,450',
    trend: 12,
    bgColor: 'bg-blue-100',
    textColor: 'text-blue-600',
    icon: 'library_books',
  },
  {
    label: '本周新增',
    value: '34',
    trend: -5,
    bgColor: 'bg-emerald-100',
    textColor: 'text-emerald-600',
    icon: 'add_box',
  },
  {
    label: '活跃用户',
    value: '2,105',
    trend: 8,
    bgColor: 'bg-indigo-100',
    textColor: 'text-indigo-600',
    icon: 'person',
  },
  {
    label: '待处理异常',
    value: '3',
    trend: 0,
    bgColor: 'bg-orange-100',
    textColor: 'text-orange-600',
    icon: 'warning',
  },
]
</script>
