<template>
  <AdminCrudList
    title="丛书管理"
    :fetch-list="fetchList"
    :create-item="createItem"
    :update-item="updateItem"
    :delete-item="deleteItem"
    search-placeholder="搜索丛书"
    create-placeholder="新建丛书名称"
  />
</template>
<script setup lang="ts">
import AdminCrudList from '@/components/AdminCrudList.vue'
import { seriesApi } from '@/api/series'

const fetchList = async (q?: string) => seriesApi.list(q)
const createItem = (name: string) => seriesApi.create(name)
const updateItem = (id: number, name: string) => seriesApi.update(id, name)
const deleteItem = (id: number) => seriesApi.remove(id)
</script>
