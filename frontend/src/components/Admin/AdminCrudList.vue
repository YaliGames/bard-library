<template>
  <section class="container mx-auto px-4 py-4 max-w-7xl">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">{{ title }}</h2>
        <div class="flex items-center">
        <el-button @click="back">
          <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回
        </el-button>
        <slot name="actions"></slot>
      </div>
    </div>

    <el-card shadow="never" class="mb-3">
      <!-- Row 1: 搜索 -->
      <div class="flex flex-wrap items-center gap-2">
        <el-input v-model="q" :placeholder="searchPlaceholder || '搜索名称'" class="w-full md:w-[260px]" clearable @keyup.enter="reload" />
        <el-button type="primary" @click="reload" :loading="loading">搜索</el-button>
      </div>

      <!-- Row 2: 新建区域 -->
      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <el-input v-model="newName" :placeholder="createPlaceholder || '新建名称'" />
        <template v-for="ef in extraFields" :key="ef.key">
          <el-input v-model="newExtras[ef.key]" :placeholder="ef.placeholder || ('新建' + ef.label)" />
        </template>
        <div class="flex items-center">
          <el-button class="w-full sm:w-auto" @click="create" :loading="creating">新建</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between"><span>列表</span><el-button text @click="reload">刷新</el-button></div>
      </template>
      <el-empty v-if="!loading && items.length===0" description="暂无数据" />
      <div v-else class="overflow-x-auto">
        <el-table
          :data="items"
          border
          stripe
          :height="tableHeight"
          size="small"
          class="min-w-[640px]"
          :default-sort="defaultSort"
          @sort-change="onSortChange"
        >
        <el-table-column label="#" prop="id" width="100" :sortable="sortableMode" />
        <el-table-column label="名称" prop="name" :sortable="sortableMode">
          <template #default="{ row }">
            <template v-if="editingId===row.id">
              <el-input v-model="editingName" size="small" @keyup.enter="update(row.id)" />
            </template>
            <template v-else>{{ row.name }}</template>
          </template>
        </el-table-column>
        <template v-for="ef in extraFields" :key="ef.key">
          <el-table-column :label="ef.label" :prop="ef.key" :sortable="sortableMode">
            <template #default="{ row }">
              <template v-if="editingId===row.id">
                <el-input v-model="editingExtras[ef.key]" size="small" />
              </template>
              <template v-else>{{ row[ef.key] }}</template>
            </template>
          </el-table-column>
        </template>
        <el-table-column label="操作" width="200" align="center">
          <template #default="{ row }">
            <template v-if="editingId===row.id">
              <el-button size="small" type="primary" @click="update(row.id)" :loading="updating">保存</el-button>
              <el-button size="small" @click="cancelEdit">取消</el-button>
            </template>
            <template v-else>
              <el-button size="small" @click="startEdit(row)">编辑</el-button>
              <el-popconfirm title="确认删除该项？" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger" :loading="removingId===row.id">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </template>
        </el-table-column>
        </el-table>
      </div>
    </el-card>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

interface Item { id: number; name: string; [key: string]: any }

const props = defineProps<{
  title: string
  searchPlaceholder?: string
  createPlaceholder?: string
  fetchList: (q?: string) => Promise<Item[]>
  // 可选：带排序的拉取函数；若提供则优先使用
  fetchListEx?: (params: { q?: string; sortKey?: string; sortOrder?: 'asc'|'desc' }) => Promise<Item[]>
  createItem?: (name: string) => Promise<any>
  updateItem?: (id: number, name: string) => Promise<any>
  deleteItem: (id: number) => Promise<any>
  createItemRaw?: (payload: Record<string, any>) => Promise<any>
  updateItemRaw?: (id: number, payload: Record<string, any>) => Promise<any>
  extraFields?: Array<{ key: string; label: string; placeholder?: string }>
  // 排序配置（不提供则默认提供 ID/名称 + extraFields）
  sortOptions?: Array<{ key: string; label: string }>
  defaultSortKey?: string
  defaultSortOrder?: 'asc' | 'desc'
  tableHeight?: number | string
}>()

const router = useRouter()
const q = ref('')
const newName = ref('')
const items = ref<Item[]>([])
const loading = ref(false)
const creating = ref(false)
const updating = ref(false)
const removingId = ref<number | null>(null)

const editingId = ref<number | null>(null)
const editingName = ref('')
const newExtras = ref<Record<string, any>>({})
const editingExtras = ref<Record<string, any>>({})

const tableHeight = computed(()=> props.tableHeight ?? 520)

// 使用 Element Plus 表格自带排序
const remoteSort = computed(() => !!props.fetchListEx)
const sortableMode = computed(() => remoteSort.value ? 'custom' : true)
const sortProp = ref<string | undefined>(props.defaultSortKey || undefined)
const sortOrderEp = ref<'ascending'|'descending'|null>(
  props.defaultSortOrder === 'asc' ? 'ascending' : props.defaultSortOrder === 'desc' ? 'descending' : null
)
const defaultSort = computed(() => (sortProp.value && sortOrderEp.value) ? { prop: sortProp.value, order: sortOrderEp.value } : undefined)

function back() { router.back() }

async function load(){
  loading.value = true
  try { items.value = await props.fetchList(q.value.trim() || undefined) }
  catch (e:any) { ElMessage.error(e?.message || '加载失败') }
  finally { loading.value = false }
}

async function loadRemote(){
  if (!props.fetchListEx) return load()
  loading.value = true
  try {
    const order = sortOrderEp.value === 'ascending' ? 'asc' : sortOrderEp.value === 'descending' ? 'desc' : undefined
    items.value = await props.fetchListEx({ q: q.value.trim() || undefined, sortKey: sortProp.value, sortOrder: order as any })
  } catch (e:any) {
    ElMessage.error(e?.message || '加载失败')
  } finally {
    loading.value = false
  }
}

function reload(){
  if (remoteSort.value) return loadRemote()
  return load()
}

function onSortChange(e: { prop?: string; order?: 'ascending'|'descending'|null }){
  sortProp.value = e.prop || undefined
  sortOrderEp.value = e.order ?? null
  if (remoteSort.value) { loadRemote() }
}

async function create(){
  if (!newName.value.trim() && !(props.extraFields && props.extraFields.length)) return
  creating.value = true
  try {
    if (props.createItemRaw) {
      const payload: Record<string, any> = { name: newName.value.trim() }
      for (const ef of (props.extraFields || [])) payload[ef.key] = newExtras.value[ef.key] ?? ''
      await props.createItemRaw(payload)
    } else if (props.createItem) {
      await props.createItem(newName.value.trim())
    } else {
      throw new Error('未提供 createItem 或 createItemRaw')
    }
    newName.value=''
    newExtras.value = {}
  await reload(); ElMessage.success('已创建')
  }
  catch (e:any) { ElMessage.error(e?.message || '创建失败') }
  finally { creating.value = false }
}

function startEdit(row: Item){
  editingId.value = row.id; editingName.value = row.name
  const ex: Record<string, any> = {}
  for (const ef of (props.extraFields || [])) ex[ef.key] = row[ef.key] ?? ''
  editingExtras.value = ex
}
function cancelEdit(){ editingId.value = null; editingName.value = ''; editingExtras.value = {} }

async function update(id:number){
  if (!editingId.value) return
  updating.value = true
  try {
    if (props.updateItemRaw) {
      const payload: Record<string, any> = { name: editingName.value.trim() }
      for (const ef of (props.extraFields || [])) payload[ef.key] = editingExtras.value[ef.key] ?? ''
      await props.updateItemRaw(id, payload)
    } else if (props.updateItem) {
      await props.updateItem(id, editingName.value.trim())
    } else {
      throw new Error('未提供 updateItem 或 updateItemRaw')
    }
  cancelEdit(); await reload(); ElMessage.success('已保存')
  }
  catch (e:any) { ElMessage.error(e?.message || '保存失败') }
  finally { updating.value = false }
}

async function remove(id:number){
  removingId.value = id
  try { await props.deleteItem(id); await reload(); ElMessage.success('已删除') }
  catch (e:any) { ElMessage.error(e?.message || '删除失败') }
  finally { removingId.value = null }
}

onMounted(reload)
</script>

<style scoped>
</style>
