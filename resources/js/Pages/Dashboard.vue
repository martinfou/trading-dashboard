<script setup>
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

onMounted(() => {
    router.visit('/trading');
});
</script>
<template><div></div></template>
