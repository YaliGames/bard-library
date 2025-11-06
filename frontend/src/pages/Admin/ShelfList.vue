<template>
  <ShelfBrowser
    title="书架管理"
    owner="admin"
    :admin="true"
    :show-search="true"
    :show-visibility="true"
    :can-manage="true"
  />
</template>

<script setup lang="ts">
import ShelfBrowser from '@/components/Shelf/Browser.vue'
</script>
