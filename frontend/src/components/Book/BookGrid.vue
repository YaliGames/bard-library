<template>
  <!-- 骨架屏 -->
  <div
    v-if="loading"
    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
  >
    <div v-for="i in skeletonCount" :key="i" class="bg-white rounded-lg shadow-sm p-4">
      <el-skeleton animated :loading="true">
        <template #template>
          <div class="flex flex-col gap-2">
            <div class="w-full aspect-[3/4] flex">
              <el-skeleton-item variant="image" class="w-full h-full rounded" />
            </div>
            <el-skeleton-item variant="text" class="w-[70%] h-[18px] mt-1.5" />
            <el-skeleton-item variant="text" class="w-[50%] h-[14px]" />
            <div class="flex items-center justify-between mt-2">
              <el-skeleton-item variant="text" class="w-[120px] h-[18px]" />
            </div>
          </div>
        </template>
      </el-skeleton>
    </div>
  </div>

  <!-- 内容区 -->
  <template v-else>
    <!-- 网格视图 -->
    <div
      v-if="data.length > 0"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
    >
      <!-- 添加按钮卡片 (编辑模式) -->
      <slot name="add-card" v-if="editable && showAddButton">
        <div
          class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-center cursor-pointer hover:bg-gray-50 border-2 border-dashed border-gray-200"
          @click="emit('add-click')"
        >
          <div class="flex flex-col items-center text-gray-500">
            <span class="material-symbols-outlined text-3xl mb-1">add</span>
            <div>添加书籍</div>
          </div>
        </div>
      </slot>

      <!-- 图书卡片 -->
      <div
        v-for="book in data"
        :key="book.id"
        :class="['relative group', editable ? 'book-card-editable' : '']"
      >
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex flex-col gap-1.5">
            <router-link :to="`/books/${book.id}`">
              <CoverImage
                :file-id="book.cover_file_id || null"
                :title="book.title"
                :authors="(book.authors || []).map(a => a.name)"
                class="rounded"
              >
                <template #overlay v-if="showReadTag">
                  <el-tag v-if="book.is_read_mark" type="success" effect="dark" size="small">
                    已读
                  </el-tag>
                  <el-tag v-else-if="book.is_reading" type="warning" effect="dark" size="small">
                    正在阅读
                  </el-tag>
                </template>
              </CoverImage>
              <div class="font-semibold mt-2">{{ book.title || '#' + book.id }}</div>
            </router-link>

            <!-- 作者列表 -->
            <div class="text-gray-600 text-sm flex flex-wrap gap-1">
              <template v-for="(author, idx) in book.authors || []" :key="author.id">
                <div class="cursor-pointer text-primary" @click="emit('author-click', author.id)">
                  {{ author.name }}
                </div>
                <span v-if="idx < (book.authors || []).length - 1">/</span>
              </template>
              <div class="text-gray-500" v-if="(book.authors || []).length === 0">暂无作者</div>
            </div>

            <!-- 评分和操作按钮 -->
            <div class="flex items-center justify-between gap-2">
              <el-rate
                v-model="book.rating"
                :max="5"
                allow-half
                disabled
                show-score
                score-template="{value}"
              />
              <template v-if="showMarkReadButton && isLoggedIn">
                <el-tooltip :content="book.is_read_mark ? '取消已读' : '标为已读'" placement="top">
                  <el-button
                    size="small"
                    :type="book.is_read_mark ? 'success' : 'default'"
                    @click="emit('toggle-read', book)"
                    circle
                  >
                    <span class="material-symbols-outlined">done_all</span>
                  </el-button>
                </el-tooltip>
              </template>
            </div>
          </div>
        </div>

        <!-- 编辑模式的删除悬浮层 -->
        <slot name="item-overlay" :book="book" v-if="editable">
          <div
            class="absolute inset-0 rounded-lg bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center"
          >
            <div
              @click.prevent="emit('remove-click', book)"
              class="rounded-full p-2 bg-white cursor-pointer hover:bg-danger hover:text-white flex items-center justify-center"
            >
              <span class="material-symbols-outlined">delete</span>
            </div>
          </div>
        </slot>
      </div>
    </div>

    <!-- 空状态 (编辑模式特殊处理) -->
    <template v-else>
      <div
        v-if="editable && showAddButton"
        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
      >
        <slot name="add-card">
          <div
            class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-center cursor-pointer hover:bg-gray-50 border-2 border-dashed border-gray-200"
            @click="emit('add-click')"
          >
            <div class="flex flex-col items-center text-gray-500">
              <span class="material-symbols-outlined text-3xl mb-1">add</span>
              <div>添加书籍</div>
            </div>
          </div>
        </slot>
      </div>
      <el-empty description="暂无书籍" v-else />
    </template>

    <!-- 分页 -->
    <div v-if="meta && meta.total > 0" class="mt-3 flex justify-center">
      <el-pagination
        background
        layout="prev, pager, next, jumper"
        :total="meta.total"
        :page-size="meta.per_page"
        :current-page="meta.current_page"
        @current-change="handlePageChange"
      />
    </div>
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import CoverImage from '@/components/CoverImage.vue'
import type { Book } from '@/api/types'

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Props {
  data: Book[]
  loading?: boolean
  meta?: PaginationMeta | null
  editable?: boolean // 是否处于编辑模式
  showAddButton?: boolean // 是否显示添加按钮 (编辑模式下)
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  meta: null,
  editable: false,
  showAddButton: true,
})

interface Emits {
  (e: 'page-change', page: number): void
  (e: 'author-click', authorId: number): void
  (e: 'toggle-read', book: Book): void
  (e: 'add-click'): void
  (e: 'remove-click', book: Book): void
}

const emit = defineEmits<Emits>()

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const isLoggedIn = computed(() => authStore.isLoggedIn)
const userSettings = settingsStore.settings

// 根据用户设置决定是否显示相关功能
const showReadTag = computed(() => userSettings.bookList?.showReadTag ?? true)
const showMarkReadButton = computed(() => userSettings.bookList?.showMarkReadButton ?? true)

// 骨架屏数量
const skeletonCount = computed(() => {
  return Math.max(1, props.meta?.per_page || 12)
})

function handlePageChange(page: number) {
  emit('page-change', page)
}
</script>
