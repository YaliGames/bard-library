<template>
  <div class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
      <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-500">bolt</span>
        快速搜书
      </h3>
      <p class="text-xs text-slate-500 mb-4">输入 ISBN 快速获取元数据并建档。</p>
      <div class="space-y-3">
        <el-input placeholder="978-7-xxx-xxxx" v-model="quickSearch">
          <template #prefix>
            <span class="material-symbols-outlined text-gray-400 text-[18px] mt-0.5">
              search
            </span>
          </template>
        </el-input>

        <div class="flex gap-2">
          <el-select v-model="quickSource" placeholder="来源" class="w-28">
            <el-option label="豆瓣" value="douban" />
            <el-option label="Google" value="google" />
          </el-select>
          <el-button type="primary" class="flex-1" @click="handleQuickSearch">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px]">cloud_download</span>
              导入
            </span>
          </el-button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
      <h3 class="font-bold text-slate-800 mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-500">health_and_safety</span>
        系统健康度
      </h3>

      <div class="space-y-5">
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">
                hard_drive
              </span>
              存储空间
            </span>
            <span class="font-mono text-slate-500">128GB / 512GB</span>
          </div>
          <el-progress
            :percentage="25"
            :stroke-width="6"
            :color="progressColors"
            :show-text="false"
          />
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px] text-slate-400">api</span>
              API 配额
            </span>
            <span class="font-mono text-slate-500">850 / 1000</span>
          </div>
          <el-progress :percentage="85" :stroke-width="6" status="warning" :show-text="false" />
        </div>

        <div
          class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100"
        >
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-xs font-medium text-slate-600">后台服务运行中</span>
          </div>
          <span class="material-symbols-outlined text-green-500 text-[18px]">check</span>
        </div>
      </div>
    </div>

    <div
      class="rounded-xl p-5 text-white bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg relative overflow-hidden"
    >
      <span
        class="material-symbols-outlined absolute -right-4 -bottom-4 text-[120px] opacity-10 rotate-12"
      >
        help
      </span>

      <div class="relative z-10">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined">school</span>
          <h3 class="font-bold">管理员指南</h3>
        </div>
        <p class="text-xs opacity-90 leading-relaxed mb-4">
          遇到问题？查看文档了解如何配置自动刮削器和元数据匹配规则。
        </p>
        <button
          class="text-xs text-white font-bold bg-white/20 hover:bg-white/30 transition-colors px-3 py-1.5 rounded-lg border border-white/30"
        >
          阅读文档
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const quickSearch = ref('')
const quickSource = ref('douban')

const progressColors = [
  { color: '#f56c6c', percentage: 90 },
  { color: '#e6a23c', percentage: 70 },
  { color: '#5cb87a', percentage: 40 },
  { color: '#3b82f6', percentage: 20 },
]

const handleQuickSearch = () => {
  console.log(`Searching ${quickSearch.value} from ${quickSource.value}`)
}
</script>
