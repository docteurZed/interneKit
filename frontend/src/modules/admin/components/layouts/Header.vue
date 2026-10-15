<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Avatar, AvatarFallback, ThemeSwitcher } from '@/components'
import { Menu } from 'lucide-vue-next'
import { useAuthStore } from '@/stores'

const emit = defineEmits(['toggle-sidebar', 'toggle-mobile-sidebar'])

const router = useRouter()

const { user } = useAuthStore()
</script>

<template>
    <header
        class="flex items-center justify-between px-6 py-4 border-b border-border bg-muted backdrop-blur-md flex-shrink-0 h-20"
    >
        <div class="flex items-center gap-4">
            <button
                class="lg:hidden p-1.5 rounded-lg hover:bg-accent cursor-pointer"
                @click="emit('toggle-mobile-sidebar')"
            >
                <Menu class="w-6 h-6 text-muted-foreground" />
            </button>

            <button
                class="hidden lg:flex rounded-lg hover:bg-accent items-center justify-center transition-colors flex-shrink-0 cursor-pointer p-1.5"
                @click="emit('toggle-sidebar')"
            >
                <Menu class="text-muted-foreground w-6 h-6" />
            </button>
        </div>

        <div class="flex items-center gap-2">
            <ThemeSwitcher class="text-muted-foreground" />

            <Avatar
                class="cursor-pointer w-10 h-10"
                @click="router.push({ name: 'admin.profile' })"
            >
                <AvatarFallback
                    class="bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-xs font-bold text-white"
                >
                    {{ user?.initials }}
                </AvatarFallback>
            </Avatar>
        </div>
    </header>
</template>
