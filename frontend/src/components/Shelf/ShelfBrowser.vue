<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">{{ title }}</h2>
      <div class="flex items-center gap-2">
        <el-input
          v-if="showSearch"
          v-model="q"
          :placeholder="searchPlaceholder"
          clearable
          class="w-[220px]"
          @keyup.enter="() => reload(1)"
          @clear="() => reload(1)"
        />
        <el-select
          v-if="showVisibility"
          v-model="visibility"
          placeholder="可见性"
          class="w-[140px]"
          @change="() => reload(1)"
        >
          <el-option label="全部" value="all" />
          <el-option label="公开" value="public" />
          <el-option label="私有" value="private" />
        </el-select>
        <el-button v-if="canManage" type="primary" @click="openCreate">新建书架</el-button>
      </div>
    </div>

    <el-skeleton :loading="loading" animated>
      <template #template>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="i in 8" :key="i" class="bg-white rounded-lg shadow-sm p-4 h-[160px]"></div>
        </div>
      </template>
      <ShelfCardList :items="list" />
      <el-empty v-if="!loading && list.length === 0" description="暂无书架" />
    </el-skeleton>

    <div v-if="meta" class="mt-4 flex justify-center">
      <el-pagination
        background
        layout="prev, pager, next, jumper"
        :total="meta.total"
        :page-size="meta.per_page"
        :current-page="meta.current_page"
        @current-change="(p: number) => reload(p)"
      />
    </div>

    <!-- 创建 -->
    <el-dialog v-model="createVisible" title="新建书架" width="460px">
      <el-form label-width="90px">
        <el-form-item label="名称"><el-input v-model="form.name" maxlength="190" /></el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" maxlength="500" />
        </el-form-item>
        <el-form-item v-if="admin" label="公开">
          <el-switch v-model="form.is_public" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="createShelf">创建</el-button>
      </template>
    </el-dialog>

    <!-- 列表页不再提供编辑/删除弹窗，编辑能力移动至详情页 -->
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import ShelfCardList from '@/components/Shelf/ShelfCardList.vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'
import { ElMessage } from 'element-plus'

const props = withDefaults(
  defineProps<{
    title: string
    owner?: 'me' | 'admin'
    showSearch?: boolean
    showVisibility?: boolean
    canManage?: boolean
    admin?: boolean
    perPage?: number
    bookLimit?: number
    searchPlaceholder?: string
  }>(),
  {
    showSearch: true,
    showVisibility: false,
    canManage: false,
    admin: false,
    perPage: 12,
    bookLimit: 3,
    searchPlaceholder: '搜索书架',
  },
)

const q = ref('')
const visibility = ref<'all' | 'public' | 'private'>('all')
const loading = ref(false)
const list = ref<Array<Shelf & { books?: any[] }>>([])
const meta = ref<{
  current_page: number
  last_page: number
  per_page: number
  total: number
} | null>(null)

async function reload(page = 1) {
  loading.value = true
  try {
    const res = await shelvesApi.listPage({
      page,
      per_page: props.perPage,
      q: q.value || undefined,
      book_limit: props.bookLimit,
      owner: props.owner,
      visibility:
        props.showVisibility && visibility.value !== 'all' ? visibility.value : (undefined as any),
    })
    list.value = res.data
    meta.value = res.meta
  } catch {
    list.value = []
  } finally {
    loading.value = false
  }
}

// 点击卡片直接通过卡片内部的 router-link 进入详情

// 创建/编辑
const createVisible = ref(false)
// 移除废弃变量，避免未使用告警
const form = ref<{ name: string; description?: string; is_public?: boolean }>({ name: '' }) // 已废弃
const saving = ref(false)

function openCreate() {
  form.value = { name: '', description: '', is_public: props.admin ? true : undefined }
  createVisible.value = true
}
// 列表不再提供编辑入口

async function createShelf() {
  if (!props.canManage) return
  if (!form.value.name.trim()) return
  saving.value = true
  try {
    const payload: Record<string, any> = {
      name: form.value.name.trim(),
      description: form.value.description || '',
    }
    if (props.admin) payload.is_public = !!form.value.is_public
    await shelvesApi.createRaw(payload)
    createVisible.value = false
    await reload()
    ElMessage.success('已创建')
  } catch (e: any) {
    ElMessage.error(e?.message || '创建失败')
  } finally {
    saving.value = false
  }
}
// 列表不再保存编辑

// 列表不再提供删除入口

onMounted(reload)
</script>

<style scoped></style>
