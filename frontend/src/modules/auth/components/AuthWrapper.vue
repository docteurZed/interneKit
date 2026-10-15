<script setup lang="ts">
import { FieldGroup, Logo, ThemeSwitcher } from '@/components'

defineProps({
    title: {
        type: String,
        default: 'Connectez - vous',
    },
    description: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['submit'])
</script>

<template>
    <div class="min-h-svh">
        <div class="flex flex-col gap-4 p-6 md:p-10">
            <div class="flex justify-between items-center">
                <Logo />
                <ThemeSwitcher />
            </div>
            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-xs">
                    <div class="flex flex-col gap-6">
                        <FieldGroup>
                            <div class="flex flex-col items-center gap-1 text-center">
                                <h1 class="text-2xl font-bold">{{ title }}</h1>
                                <p class="text-muted-foreground text-sm text-balance">
                                    {{ description }}
                                </p>
                            </div>

                            <form @submit.prevent="emit('submit')" class="space-y-6">
                                <slot />
                            </form>
                        </FieldGroup>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
