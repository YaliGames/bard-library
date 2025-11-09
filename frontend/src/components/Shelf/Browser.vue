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
        <el-form-item v-if="admin" label="全局书架">
          <div>
            <el-switch v-model="form.global" />
            <div class="text-xs text-red-500 mt-1" v-if="form?.global && !form?.is_public">
              请注意，未开启公开模式的全局书架将无法被除管理员外的任何用户访问
            </div>
            <div class="text-xs text-gray-500 mt-1" v-else-if="form?.global && form?.is_public">
              全局书架不属于任何用户，所有用户可见
            </div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="createShelf">创建</el-button>
      </template>
    </el-dialog>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import ShelfCardList from '@/components/Shelf/CardList.vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePagination } from '@/composables/usePagination'

const { handleError, handleSuccess } = useErrorHandler()

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

const {
  data: list,
  loading,
  currentPage,
  lastPage,
  total,
  perPage,
  loadPage,
} = usePagination<Shelf & { books?: any[] }>({
  fetcher: async (page: number) => {
    const res = await shelvesApi.listPage({
      page,
      per_page: props.perPage,
      q: q.value || undefined,
      book_limit: props.bookLimit,
      owner: props.owner,
      visibility:
        props.showVisibility && visibility.value !== 'all' ? visibility.value : (undefined as any),
    })
    return res
  },
  onError: () => {},
})

const meta = {
  get current_page() {
    return currentPage.value
  },
  get last_page() {
    return lastPage.value
  },
  get per_page() {
    return perPage.value
  },
  get total() {
    return total.value
  },
}

function reload(page = 1) {
  loadPage(page)
}

// 创建/编辑
const createVisible = ref(false)
const form = ref<{ name: string; description?: string; is_public?: boolean; global?: boolean }>({
  name: '',
})
const saving = ref(false)

function openCreate() {
  form.value = {
    name: '',
    description: '',
    is_public: props.admin ? true : undefined,
    global: props.admin ? false : undefined,
  }
  createVisible.value = true
}

async function createShelf() {
  if (!props.canManage) return
  if (!form.value.name.trim()) return
  saving.value = true
  try {
    const payload: Record<string, any> = {
      name: form.value.name.trim(),
      description: form.value.description || '',
    }
    if (props.admin) {
      payload.is_public = !!form.value.is_public
      payload.global = !!form.value.global // 传递全局书架标识
    }
    await shelvesApi.createRaw(payload)
    createVisible.value = false
    await reload()
    handleSuccess('已创建')
  } catch (e: any) {
    handleError(e, { context: 'ShelfBrowser.createShelf' })
  } finally {
    saving.value = false
  }
}

onMounted(reload)
</script>

<style scoped></style>
