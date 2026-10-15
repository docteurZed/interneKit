<script setup lang="ts">
import { useColorMode } from '@vueuse/core'
import { Moon, Sun } from 'lucide-vue-next'

const mode = useColorMode({
    emitAuto: true,
})

const toggleTheme = () => {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
}
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        class="inline-flex items-center justify-center rounded-full p-2 transition-colors hover:bg-primary/10 cursor-pointer"
    >
        <transition name="icon-fade" mode="out-in">
            <component
                v-if="mode"
                :is="mode === 'light' ? Moon : Sun"
                class="h-full w-full transition-all duration-300 ease-in-out"
            />
        </transition>
    </button>
</template>

<style scoped>
.icon-fade-enter-active,
.icon-fade-leave-active {
    transition: all 0.3s ease;
}

.icon-fade-enter-from {
    opacity: 0;
    transform: rotate(-90deg) scale(0.6);
}

.icon-fade-leave-to {
    opacity: 0;
    transform: rotate(90deg) scale(0.6);
}
</style>
