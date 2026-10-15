<script setup lang="ts">
import { Toaster } from 'vue-sonner'
</script>

<template>
    <RouterView />
    <Toaster richColors :expand="true" position="top-right" />
</template>
