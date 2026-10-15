<script setup lang="ts">
import { ref } from 'vue'
import { Header, Sidebar } from './layouts'

const mobileSidebarOpen = ref(false)
const sidebarOpen = ref(true)

const props = defineProps<{
    title: string
}>()
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background text-foreground">
        <Transition name="fade">
            <div
                v-if="mobileSidebarOpen"
                class="fixed inset-0 bg-background/80 backdrop-blur-sm z-30 lg:hidden"
                @click="mobileSidebarOpen = false"
            />
        </Transition>

        <Sidebar
            :sidebar-open="sidebarOpen"
            :mobile-open="mobileSidebarOpen"
            @toggle-sidebar="sidebarOpen = !sidebarOpen"
            @close="mobileSidebarOpen = false"
        />

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <Header
                @toggle-sidebar="sidebarOpen = !sidebarOpen"
                @toggle-mobile-sidebar="mobileSidebarOpen = !mobileSidebarOpen"
            />

            <main class="flex-1 overflow-y-auto bg-background p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold leading-none">
                            {{ title }}
                        </h1>
                    </div>
                    <div class="flex min-w-0 flex-wrap items-center gap-2 sm:flex-shrink-0">
                        <slot name="actions" />
                    </div>
                </div>

                <div class="mt-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap');

.dash-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1rem, 2vw, 1.2rem);
    letter-spacing: -0.03em;
    color: var(--color-foreground);
}
</style>
