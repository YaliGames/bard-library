<template>
  <AdminCrudList
    title="标签管理"
    :fetch-list="fetchList"
    :create-item-raw="createItemRaw"
    :update-item-raw="updateItemRaw"
    :delete-item="deleteItem"
    :extra-fields="extraFields"
    search-placeholder="搜索标签"
    create-placeholder="新建标签名称"
  />
</template>
<script setup lang="ts">
import AdminCrudList from '@/components/AdminCrudList.vue'
import { tagsApi } from '@/api/tags'

const fetchList = async (q?: string) => tagsApi.list(q ? { q } : undefined)
const createItemRaw = (payload: any) => tagsApi.createRaw(payload)
const updateItemRaw = (id: number, payload: any) => tagsApi.updateRaw(id, payload)
const deleteItem = (id: number) => tagsApi.remove(id)

const extraFields = [
  { key: 'type', label: '类型', placeholder: '类型（可选）' },
]
</script>
