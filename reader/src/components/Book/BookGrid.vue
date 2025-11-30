<template>
  <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
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

  <template v-else>
    <div
      v-if="data.length > 0"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
    >
      <div
        v-for="book in data"
        :key="book.id"
        class="relative group"
      >
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex flex-col gap-1.5">
            <div @click="handleBookClick(book)" class="cursor-pointer">
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
            </div>

            <div class="text-gray-600 text-sm flex flex-wrap gap-1">
              <template v-for="(author, idx) in book.authors || []" :key="author.id">
                <div class="cursor-pointer text-primary" @click="emit('author-click', author.id)">
                  {{ author.name }}
                </div>
                <span v-if="idx < (book.authors || []).length - 1">/</span>
              </template>
              <div class="text-gray-500" v-if="(book.authors || []).length === 0">暂无作者</div>
            </div>

            <div class="flex items-center justify-between gap-2">
              <el-rate
                v-model="book.rating"
                :max="5"
                allow-half
                disabled
                show-score
                score-template="{value}"
              />
              <div class="flex items-center gap-1">
                <template v-if="showDelete">
                  <el-button
                    size="small"
                    type="danger"
                    plain
                    @click.stop="emit('delete', book)"
                    circle
                  >
                    <span class="material-symbols-outlined text-base">delete</span>
                  </el-button>
                </template>
                <template v-if="showMarkReadButton && isLoggedIn && !showDelete">
                  <el-tooltip :content="book.is_read_mark ? '取消已读' : '标为已读'" placement="top">
                    <el-button
                      size="small"
                      :type="book.is_read_mark ? 'success' : 'default'"
                      @click="emit('toggle-read', book)"
                      circle
                    >
                      <span class="text-lg">✓</span>
                    </el-button>
                  </el-tooltip>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <el-empty description="暂无书籍" v-else />

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
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSettingsStore } from '@/stores/settings'
import { ElMessage } from 'element-plus'
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
  showDelete?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  meta: null,
  showDelete: false,
})

interface Emits {
  (e: 'page-change', page: number): void
  (e: 'author-click', authorId: number): void
  (e: 'toggle-read', book: Book): void
  (e: 'delete', book: Book): void
}

const emit = defineEmits<Emits>()

const router = useRouter()
const userStore = useUserStore()
const settingsStore = useSettingsStore()

const isLoggedIn = computed(() => userStore.isLoggedIn)
const userSettings = settingsStore.settings

const showReadTag = computed(() => userSettings.bookList?.showReadTag ?? true)
const showMarkReadButton = computed(() => userSettings.bookList?.showMarkReadButton ?? true)

const skeletonCount = computed(() => {
  return Math.max(1, props.meta?.per_page || 12)
})

function handlePageChange(page: number) {
  emit('page-change', page)
}

function handleBookClick(book: Book) {
  // 查找TXT文件
  const txtFile = book.files?.find(f => f.format === 'txt' || f.mime?.includes('text'))
  
  if (txtFile) {
    // 直接跳转到阅读器
    router.push(`/read/${txtFile.id}`)
  } else {
    // 没有TXT文件，提示用户
    ElMessage.warning('该书籍没有可阅读的TXT文件')
  }
}
</script>
