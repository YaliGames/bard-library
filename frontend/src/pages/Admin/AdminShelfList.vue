<template>
  <ShelfBrowser title="书架管理" :admin="true" :show-search="true" :show-visibility="true" :can-manage="true" />
</template>

<script setup lang="ts">
import ShelfBrowser from '@/components/Shelf/ShelfBrowser.vue'
</script>
