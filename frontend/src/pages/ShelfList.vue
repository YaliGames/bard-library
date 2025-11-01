<template>
    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <h2 class="text-xl font-semibold mb-4">书架</h2>
        <div>
            <el-skeleton :loading="loading" animated>
                <template #template>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="i in 8" :key="i" class="bg-white rounded-lg shadow-sm p-4 h-[160px]"></div>
                    </div>
                </template>
                <ShelfCardList :items="shelves" @open="goDetail" />
            </el-skeleton>
        </div>
        <div v-if="meta" class="mt-4 flex justify-center">
          <el-pagination background layout="prev, pager, next, jumper" :total="meta.total" :page-size="meta.per_page"
            :current-page="meta.current_page" @current-change="(p:number)=>load(p)" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Shelf } from '@/api/types'
import { shelvesApi } from '@/api/shelves'
import ShelfCardList from '@/components/ShelfCardList.vue'
import { useRouter } from 'vue-router'

const shelves = ref<Shelf[]>([])
const loading = ref(true)
const router = useRouter()
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
function goDetail(s: Shelf){ router.push(`/shelf/${s.id}`) }

async function load(page = 1) {
    loading.value = true
    try {
        const res = await shelvesApi.listPage({ page, per_page: 12, bookLimit: 3 })
        shelves.value = res.data
        meta.value = res.meta
    } catch (e: any) {
        shelves.value = []
    } finally {
        loading.value = false
    }
}

onMounted(() => { load() })
</script>

<style scoped></style>