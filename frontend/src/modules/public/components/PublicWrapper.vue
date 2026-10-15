<script setup lang="ts">
import { Header } from './layouts'
import WatermarkIcons from './WatermarkIcons.vue'
</script>

<template>
    <div class="relative min-h-screen bg-background overflow-hidden">
        <WatermarkIcons />
        <Header />

        <div class="relative z-10 flex flex-1 flex-col justify-center lg:px-0 lg:py-0">
            <main class="mx-auto w-full max-w-4xl px-4 pb-16 pt-4">
                <slot />
            </main>
        </div>
    </div>
</template>
