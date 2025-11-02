<template>
  <ShelvesBrowser title="我的书架" owner="me" :show-search="true" :can-manage="true" :show-visibility="false" search-placeholder="搜索我的书架" />
</template>

<script setup lang="ts">
import ShelvesBrowser from '@/components/ShelvesBrowser.vue'
</script>
