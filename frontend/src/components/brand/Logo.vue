<script setup lang="ts">
import config from '@/config'

const name = config.appName

defineProps({
    routeName: {
        type: String,
        default: 'home',
    },
})
</script>

<template>
    <RouterLink :to="{ name: routeName }" class="flex items-center gap-2 font-bold py-2">
        <span
            class="flex items-center justify-center"
        >
            <img class="w-5 sm:w-7 h-5 sm:h-7" src="/logo.png" alt="Logo" />
        </span>
        <h2 class="text-primary text-lg sm:text-xl">
            {{ name }}
        </h2>
    </RouterLink>
</template>
