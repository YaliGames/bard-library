<template>
  <AdminCrudList
    title="书架管理"
    :fetch-list="fetchList"
    :create-item-raw="createItemRaw"
    :update-item-raw="updateItemRaw"
    :delete-item="deleteItem"
    :extra-fields="extraFields"
    search-placeholder="搜索书架"
    create-placeholder="新建书架名称"
  />
</template>
<script setup lang="ts">
import AdminCrudList from '@/components/AdminCrudList.vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'

// shelvesApi 提供分页/聚合两种方式，这里使用 listAll 简化
const fetchList = async (q?: string): Promise<Array<Shelf & { name: string }>> => {
  const all = await shelvesApi.listAll()
  // 后端当前不支持 q 过滤时，这里在前端做一次 name 包含过滤
  const list = typeof q === 'string' && q.trim() ? all.filter(s => s.name.includes(q.trim())) : all
  return list as any
}
const createItemRaw = (payload: any) => shelvesApi.createRaw(payload)
const updateItemRaw = (id: number, payload: any) => shelvesApi.updateRaw(id, payload)
const deleteItem = (id: number) => shelvesApi.remove(id)

const extraFields = [
  { key: 'description', label: '描述', placeholder: '描述（可选）' },
]
</script>
