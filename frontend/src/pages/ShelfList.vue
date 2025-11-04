<template>
  <ShelfBrowser
    title="书架"
    :show-search="true"
    :can-manage="isLoggedIn"
    :show-visibility="isLoggedIn"
  />
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import ShelfBrowser from '@/components/Shelf/ShelfBrowser.vue'

const { loggedIn } = useAuthStore()
const isLoggedIn = loggedIn
</script>

<style scoped></style>
