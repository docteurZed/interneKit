<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Button, ThemeSwitcher } from '@/components'
import { ArrowLeft, Home } from 'lucide-vue-next'

const props = defineProps({
    code: { type: Number, default: 404 },
    title: { type: String, default: 'Page introuvable' },
    description: {
        type: String,
        default: "La page que vous recherchez n'existe pas ou a été déplacée.",
    },
})

const router = useRouter()
const goBack = () => router.back()
const goHome = () => router.push({ name: 'home' })
</script>

<template>
    <div class="relative min-h-screen flex items-center justify-center px-4">
        <ThemeSwitcher class="absolute top-4 right-4" />

        <div class="flex flex-col gap-6 items-center">
            <div class="text-7xl font-extrabold text-primary">{{ props.code }}</div>

            <h1 class="text-2xl font-bold tracking-tight">{{ props.title }}</h1>

            <p class="text-muted-foreground text-semibold">{{ props.description }}</p>

            <div class="flex justify-center items-center gap-4 pt-4">
                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Retour
                </Button>
                <Button @click="goHome">
                    <Home class="w-4 h-4 mr-2" />
                    Accueil
                </Button>
            </div>
        </div>
    </div>
</template>
