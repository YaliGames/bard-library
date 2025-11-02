<template>
    <div class="border-b pb-6 space-y-4">
        <el-skeleton :loading="loading" animated>
            <template #template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="w-full">
                            <el-skeleton-item variant="text" class="w-40 h-5 mb-1" />
                        </div>
                        <div class="w-full">
                            <el-skeleton-item variant="p" class="w-64 h-4" />
                        </div>
                    </div>
                    <div class="flex items-center justify-start md:justify-end">
                        <el-skeleton-item variant="rect" class="w-24 h-6 rounded" />
                    </div>
                </div>
            </template>
            <template #default>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 左侧：标题+描述 -->
                    <div>
                        <div class="font-medium text-gray-800">{{ title }}</div>
                        <p v-if="description" class="text-gray-500 text-sm mt-1">
                            {{ description }}
                        </p>
                        <slot name="desc" />
                    </div>
                    <!-- 右侧：控件区 -->
                    <div class="flex flex-wrap items-center justify-start md:justify-end gap-3">
                        <slot />
                    </div>
                </div>
            </template>
        </el-skeleton>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    title: string
    description?: string
    loading?: boolean
}>(), {})
</script>

<style scoped></style>
