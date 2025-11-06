<template>
  <ShelfBrowser
    title="书架"
    :show-search="true"
    :can-manage="isLoggedIn"
    :show-visibility="isLoggedIn"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import ShelfBrowser from '@/components/Shelf/ShelfBrowser.vue'

const authStore = useAuthStore()
const isLoggedIn = computed(() => authStore.isLoggedIn)
</script>

<style scoped></style>
