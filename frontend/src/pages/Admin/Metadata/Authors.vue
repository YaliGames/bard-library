<template>
  <AdminCrudList
    title="作者管理"
    :fetch-list="fetchList"
    :create-item-raw="createItemRaw"
    :update-item-raw="updateItemRaw"
    :delete-item="deleteItem"
    :extra-fields="extraFields"
    search-placeholder="搜索作者"
    create-placeholder="新建作者姓名"
    create-permission="authors.create"
    edit-permission="authors.edit"
    delete-permission="authors.delete"
  />
</template>
<script setup lang="ts">
import AdminCrudList from '@/components/Admin/CrudList.vue'
import { authorsApi } from '@/api/authors'

const fetchList = async (q?: string) => authorsApi.list(q ? { q } : undefined)
const createItemRaw = (payload: any) => authorsApi.createRaw(payload)
const updateItemRaw = (id: number, payload: any) => authorsApi.updateRaw(id, payload)
const deleteItem = (id: number) => authorsApi.remove(id)

const extraFields = [{ key: 'sort_name', label: '排序名', placeholder: '排序用名称（可选）' }]
</script>
