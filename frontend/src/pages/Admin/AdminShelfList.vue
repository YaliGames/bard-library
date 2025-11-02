<template>
  <ShelvesBrowser title="书架管理" :admin="true" :show-search="true" :show-visibility="true" :can-manage="true" />
</template>

<script setup lang="ts">
import ShelvesBrowser from '@/components/ShelvesBrowser.vue'
</script>
