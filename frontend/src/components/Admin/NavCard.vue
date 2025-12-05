<template>
  <div
    class="group relative h-full cursor-pointer transition-all duration-300 hover:-translate-y-1"
    @click="handleClick"
  >
    <el-card
      class="h-full border-none shadow-sm hover:shadow-lg transition-shadow duration-300 rounded-xl relative overflow-hidden"
      :body-style="{ padding: '1.5rem', height: '100%', display: 'flex', flexDirection: 'column' }"
    >
      <!-- 背景装饰圆圈 -->
      <div
        class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-blue-50 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
      ></div>

      <!-- 顶部：图标 + 收藏按钮 -->
      <div class="mb-4 flex items-start justify-between relative z-10">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white"
        >
          <span class="material-symbols-outlined">{{ item.icon }}</span>
        </div>
        <el-tooltip :content="isFavorite ? '取消收藏' : '加入常用'" placement="top">
          <button
            class="rounded-full p-2 transition-colors hover:bg-gray-100"
            :class="isFavorite ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-400'"
            @click="handleFav"
          >
            <span class="material-symbols-outlined">star</span>
          </button>
        </el-tooltip>
      </div>

      <!-- 内容：标题 + 描述 -->
      <div class="relative z-10 flex-1">
        <h3 class="mb-2 text-lg font-bold text-gray-800 group-hover:text-blue-600">
          {{ item.title }}
        </h3>
        <p class="text-sm leading-relaxed text-gray-500 line-clamp-2">{{ item.desc }}</p>
      </div>

      <!-- 底部：箭头提示 (Hover时显示) -->
      <div
        class="mt-4 flex items-center text-sm font-medium text-blue-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
      >
        <span>立即进入</span>
        <span class="material-symbols-outlined">arrow_right</span>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ElCard, ElIcon, ElTooltip } from 'element-plus'
import type { RouteLocationRaw } from 'vue-router'

export interface AdminCard {
  key: string
  title: string
  desc: string
  icon: string
  to: RouteLocationRaw
  permission?: string
  category: 'createBook' | 'books' | 'fields' | 'users' | 'system'
}

interface Props {
  item: AdminCard
  isFavorite?: boolean
}

interface Emits {
  (e: 'toggle-fav', key: string): void
}

const props = withDefaults(defineProps<Props>(), {
  isFavorite: false,
})

const emit = defineEmits<Emits>()

const router = useRouter()

const handleClick = () => {
  if (props.item.to) {
    router.push(props.item.to)
  }
}

const handleFav = (e: Event) => {
  e.stopPropagation() // 阻止冒泡，避免触发卡片点击
  emit('toggle-fav', props.item.key)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
