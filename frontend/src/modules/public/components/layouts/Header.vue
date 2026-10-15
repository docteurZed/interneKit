<script setup lang="ts">
import { useRoute } from 'vue-router'
import { Download, HeartHandshake, WifiOff } from 'lucide-vue-next'
import { Button, Logo, ThemeSwitcher } from '@/components'
import { useOnlineStatus } from '@/composables/useOnlineStatus'
import { usePwaInstall } from '@/composables/usePwaInstall'

const route = useRoute()
const { isOnline } = useOnlineStatus()
const { canInstall, install } = usePwaInstall()

const sections = [
    { name: 'Accueil', mobile: 'Accueil', to: '/' },
    { name: 'Gardes', mobile: 'Gardes', to: '/gardes' },
    { name: 'Internes', mobile: 'Internes', to: '/internes' },
    { name: 'Consultations', mobile: 'Consultations', to: '/consultations' },
    { name: 'Actes', mobile: 'Actes', to: '/actes' },
]
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-border bg-muted">
        <p
            v-if="!isOnline"
            role="status"
            class="flex items-center justify-center gap-1.5 bg-destructive/10 px-4 py-1.5 text-center text-xs font-medium text-destructive"
        >
            <WifiOff aria-hidden="true" class="size-3.5" />Pas de connexion internet. Certaines
            données peuvent être indisponibles.
        </p>
        <div
            class="mx-auto flex justify-between items-center max-w-4xl items-center px-4 py-4 sm:px-6 lg:px-8"
        >
            <div class="min-w-0">
                <Logo />
            </div>

            <div class="flex items-center gap-2">
                <Button v-if="canInstall" variant="outline" size="sm" @click="install">
                    <Download aria-hidden="true" class="size-4" />Installer
                </Button>
                <Button as-child variant="outline" size="icon">
                    <RouterLink to="/a-propos" aria-label="À propos et dons" title="À propos et dons">
                        <HeartHandshake aria-hidden="true" class="size-4" />
                    </RouterLink>
                </Button>
                <ThemeSwitcher />
            </div>
        </div>

        <nav
            aria-label="Sections"
            role="tablist"
            class="fixed inset-x-0 bottom-0 z-50 flex min-h-14 justify-around bg-muted sm:bg-transparent border-t border-border px-1 pb-[env(safe-area-inset-bottom)] sm:static sm:mx-auto sm:min-h-0 sm:max-w-4xl sm:justify-start sm:gap-1 sm:border-0 sm:px-6 sm:pb-0 lg:px-8"
        >
            <RouterLink
                v-for="section in sections.filter((item) => item.to)"
                :key="section.name"
                :to="section.to!"
                role="tab"
                :aria-selected="route.path === section.to"
                :data-tab="section.name.toLowerCase()"
                class="flex-1 cursor-pointer border-0 border-t-2 border-transparent bg-transparent px-0.5 py-4 text-xs font-medium text-muted-foreground sm:flex-none sm:border-t-0 sm:border-b-2 sm:px-3.5 sm:py-2.5 sm:text-sm text-center"
                :class="
                    route.path === section.to
                        ? 'border-t-primary font-semibold text-primary sm:border-t-transparent sm:border-b-primary'
                        : ''
                "
            >
                <span class="hidden sm:inline">{{ section.name }}</span>
                <span class="sm:hidden">{{ section.mobile }}</span>
            </RouterLink>
            <button
                v-for="section in sections.filter((item) => !item.to)"
                :key="section.name"
                type="button"
                role="tab"
                aria-selected="false"
                disabled
                :data-tab="section.name.toLowerCase()"
                class="flex-1 cursor-not-allowed border-0 border-t-2 border-transparent bg-transparent px-0.5 py-4 text-xs font-medium text-muted-foreground opacity-50 sm:flex-none sm:border-t-0 sm:border-b-2 sm:px-3.5 sm:py-2.5 sm:text-sm"
            >
                <span class="hidden sm:inline">{{ section.name }}</span>
                <span class="sm:hidden">{{ section.mobile }}</span>
            </button>
        </nav>
    </header>
</template>
