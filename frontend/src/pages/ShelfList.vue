<template>
    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <h2 class="text-xl font-semibold mb-4">书架</h2>
        <div>
            <el-skeleton :loading="loading" animated>
                <template #template>
                    <div v-for="i in 6" :key="i" class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-40 h-20 bg-gray-100 rounded-lg" />
                            <div class="flex-1">
                                <div class="h-5 bg-gray-200 rounded w-1/2 mb-2"></div>
                                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-for="s in shelves" :key="s.id" class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <router-link :to="`/shelf/${s.id}`">
                        <div class="flex items-start space-x-4">
                            <div class="w-40 h-20 bg-gray-50 rounded-lg overflow-hidden">
                                <img :src="s.cover || ('/api/v1/shelves/' + s.id + '/cover')" alt="shelf" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold mb-1">
                                        {{ s.name }}
                                </h3>
                                <p class="text-gray-600 text-sm mb-2">{{ s.description || '暂无简介' }}</p>
                            </div>
                        </div>
                    </router-link>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                        <router-link v-for="b in (s.books || [])" :key="b.id" :to="`/books/${b.id}`">
                            <el-button type="primary" class="justify-start w-full">
                                <span class="material-symbols-outlined mr-2">book</span>
                                《{{ b.title }}》
                            </el-button>
                        </router-link>
                    </div>
                </div>
            </el-skeleton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'

const shelves = ref<Shelf[]>([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        const list = await shelvesApi.listSummaries(6)
        shelves.value = list
    } catch (e: any) {
        shelves.value = []
    } finally {
        loading.value = false
    }
}

onMounted(() => { load() })
</script>

<style scoped></style>