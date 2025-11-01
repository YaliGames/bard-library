<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">我的书架</h2>
      <div class="flex items-center gap-2">
        <el-input v-model="q" placeholder="搜索我的书架" clearable class="w-[200px]" @keyup.enter="reload" />
        <el-button type="primary" @click="openCreate">新建书架</el-button>
      </div>
    </div>

    <el-skeleton :loading="loading" animated>
      <template #template>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="i in 8" :key="i" class="bg-white rounded-lg shadow-sm p-4 h-[160px]"></div>
        </div>
      </template>
      <ShelfCardList :items="viewList" :can-manage="true" @open="goDetail" @edit="openEdit" @delete="onDelete" />
      <el-empty v-if="!loading && viewList.length===0" description="暂无书架" />
    </el-skeleton>

    <div v-if="meta" class="mt-4 flex justify-center">
      <el-pagination background layout="prev, pager, next, jumper" :total="meta.total" :page-size="meta.per_page"
        :current-page="meta.current_page" @current-change="(p:number)=>reload(p)" />
    </div>

    <el-dialog v-model="createVisible" title="新建书架" width="420px">
      <el-form label-width="80px">
        <el-form-item label="名称"><el-input v-model="form.name" maxlength="190" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" maxlength="500" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createVisible=false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="createShelf">创建</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="editVisible" title="编辑书架" width="420px">
      <el-form label-width="80px">
        <el-form-item label="名称"><el-input v-model="form.name" maxlength="190" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" maxlength="500" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible=false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveEdit">保存</el-button>
      </template>
    </el-dialog>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ShelfCardList from '@/components/ShelfCardList.vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const { state } = useAuthStore()
const q = ref('')
const loading = ref(false)
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
const list = ref<Array<Shelf & { books?: any[] }>>([])
const viewList = computed(() => {
  const meId = state.user?.id
  const mine = typeof meId === 'number' ? list.value.filter(s => (s.user_id ?? 0) === meId) : []
  const kw = q.value.trim()
  return kw ? mine.filter(s => s.name.includes(kw)) : mine
})

async function reload(page = 1){
  loading.value = true
  try {
    const res = await shelvesApi.listPage({ page, per_page: 12, q: q.value || undefined, bookLimit: 3, owner: 'me' })
    list.value = res.data
    meta.value = res.meta
  } catch { list.value = [] } finally { loading.value = false }
}

function goDetail(s: Shelf){ router.push(`/shelf/${s.id}`) }

// 新建/编辑
const createVisible = ref(false)
const editVisible = ref(false)
const currentId = ref<number | null>(null)
const form = ref<{ name: string; description?: string }>({ name: '', description: '' })
const saving = ref(false)

function openCreate(){ currentId.value = null; form.value = { name: '', description: ''}; createVisible.value = true }
function openEdit(s: Shelf){ currentId.value = s.id; form.value = { name: s.name, description: s.description || '' }; editVisible.value = true }

async function createShelf(){
  if (!form.value.name.trim()) return
  saving.value = true
  try { await shelvesApi.createRaw({ name: form.value.name.trim(), description: form.value.description || '' }); createVisible.value=false; await reload(); ElMessage.success('已创建') }
  catch (e:any){ ElMessage.error(e?.message || '创建失败') }
  finally { saving.value = false }
}
async function saveEdit(){
  if (!currentId.value) return
  saving.value = true
  try { await shelvesApi.updateRaw(currentId.value, { name: form.value.name.trim(), description: form.value.description || '' }); editVisible.value=false; await reload(); ElMessage.success('已保存') }
  catch (e:any){ ElMessage.error(e?.message || '保存失败') }
  finally { saving.value = false }
}

async function onDelete(s: Shelf){
  try { await shelvesApi.remove(s.id); await reload(); ElMessage.success('已删除') }
  catch (e:any){ ElMessage.error(e?.message || '删除失败') }
}

onMounted(reload)
</script>
