<script setup lang="ts">
import StatsOverview from '@/components/Admin/Dashboard/StatsOverview.vue'
import NavigationConsole from '@/components/Admin/Dashboard/NavigationConsole.vue'
import NotificationCenter from '@/components/Admin/Dashboard/NotificationCenter.vue'
import RightSidebar from '@/components/Admin/Dashboard/RightSidebar.vue'
</script>

<template>
  <div class="min-h-screen bg-[#f3f4f6] p-4 lg:p-6 font-sans text-slate-600">
    <!-- TODO: 数据概览业务逻辑 -->
    <StatsOverview v-if="false" />

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
      <div class="xl:col-span-8 space-y-6">
        <NavigationConsole />

        <!-- TODO: 通知系统业务逻辑 -->
        <NotificationCenter v-if="false" />
      </div>

      <div class="xl:col-span-4">
        <RightSidebar />
      </div>
    </div>
  </div>
</template>
