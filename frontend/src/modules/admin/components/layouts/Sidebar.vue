<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    LayoutDashboard,
    LogOut,
    User,
    GalleryVerticalEnd,
    Users,
    CalendarDays,
    ClipboardList,
    Settings2,
} from 'lucide-vue-next'
import {
    Avatar,
    AvatarFallback,
    Button,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    Logo,
} from '@/components'
import { useAuthStore } from '@/stores'
import { toast } from 'vue-sonner'

const router = useRouter()
const route = useRoute()

const props = defineProps<{
    mobileOpen: boolean
    sidebarOpen: boolean
}>()

const emit = defineEmits(['close', 'toggle-sidebar'])

function goToProfile() {
    router.push({ name: 'admin.profile' })
    emit('close')
}

const navItems = [
    { name: 'Tableau de bord', routeName: 'admin.dashboard', icon: LayoutDashboard },
    { name: 'Administrateurs', routeName: 'admin.user', icon: User },
    { name: 'Internes', routeName: 'admin.interne', icon: Users },
    { name: 'Gardes', routeName: 'admin.gardes', icon: CalendarDays },
    { name: 'Affectations', routeName: 'admin.affectations', icon: ClipboardList },
    { name: 'Référentiels', routeName: 'admin.referentiels', icon: Settings2 },
]

const auth = useAuthStore()

const logoutDialogOpen = ref(false)
const isLoggingOut = ref(false)

const logout = async () => {
    if (isLoggingOut.value) return
    isLoggingOut.value = true

    try {
        await auth.logout()
        logoutDialogOpen.value = false
        router.push({ name: 'login' })
    } catch (error) {
        toast.error('Une erreur est survenue lors de la déconnexion.')
    } finally {
        isLoggingOut.value = false
    }
}
</script>

<template>
    <aside
        class="fixed lg:relative z-40 flex flex-col h-full bg-sidebar border-r border-sidebar-border transition-all duration-300"
        :class="[
            props.sidebarOpen ? 'w-64' : 'w-[72px]',
            props.mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
    >
        <!-- top glow -->
        <div
            class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-primary to-transparent"
        />

        <!-- Logo -->
        <div
            class="flex items-center gap-3 px-4 py-5 border-b border-border h-20"
            :class="props.sidebarOpen ? 'justify-between' : 'justify-center'"
        >
            <div v-if="props.sidebarOpen" class="flex items-center gap-3 min-w-0">
                <div class="relative flex-shrink-0">
                    <Logo />
                </div>
            </div>

            <div v-else class="relative flex-shrink-0">
                <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
                    <GalleryVerticalEnd class="w-4 h-4 text-foreground" />
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto scrollbar-none">
            <button
                v-for="item in navItems"
                :key="item.routeName"
                class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 group relative cursor-pointer"
                :class="[
                    route.name === item.routeName ||
                    route.path.startsWith(`/admin/${item.routeName.replace('admin.', '')}`)
                        ? 'bg-primary/10 text-primary shadow-inner'
                        : 'text-muted-foreground hover:bg-card/40 hover:text-foreground',

                    !props.sidebarOpen ? 'justify-center' : '',
                ]"
                @click="(router.push({ name: item.routeName }), emit('close'))"
            >
                <!-- active indicator -->
                <div
                    v-if="
                        route.name === item.routeName ||
                        route.path.startsWith(`/admin/${item.routeName.replace('admin.', '')}`)
                    "
                    class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 bg-primary rounded-r-full"
                />

                <!-- icon -->
                <div class="relative flex-shrink-0">
                    <component :is="item.icon" class="w-4 h-4" />
                </div>

                <!-- label -->
                <template v-if="props.sidebarOpen">
                    <span class="truncate">
                        {{ item.name }}
                    </span>
                </template>

                <div
                    v-if="!props.sidebarOpen"
                    class="absolute left-full ml-3 px-2.5 py-1.5 bg-primary text-white text-xs font-medium rounded-lg border border-border whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-60 shadow-xl"
                >
                    {{ item.name }}

                    <div
                        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 w-2 h-2 bg-primary border-l border-b border-border rotate-45"
                    />
                </div>
            </button>
        </nav>

        <!-- divider -->
        <div class="mx-3 h-px bg-border" />

        <!-- logout -->
        <div class="p-3 space-y-1">
            <button
                class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-destructive/70 hover:text-destructive hover:bg-destructive/5 transition-all group relative cursor-pointer"
                :class="!props.sidebarOpen ? 'justify-center' : ''"
                @click="logoutDialogOpen = true"
            >
                <LogOut class="w-4 h-4 flex-shrink-0" />

                <span v-if="props.sidebarOpen"> Déconnexion </span>

                <!-- tooltip -->
                <div
                    v-if="!props.sidebarOpen"
                    class="absolute left-full ml-3 px-2.5 py-1.5 bg-primary text-white text-xs font-medium rounded-lg border border-white/10 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-xl"
                >
                    Déconnexion
                </div>
            </button>
        </div>

        <!-- profile -->
        <div class="border-t border-border p-3">
            <div
                class="flex items-center gap-3 rounded-xl p-2.5 hover:bg-card/40 transition-colors cursor-pointer"
                :class="!props.sidebarOpen ? 'justify-center' : ''"
                @click="goToProfile"
            >
                <Avatar class="cursor-pointer w-8 h-8">
                    <AvatarFallback
                        class="bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-xs font-bold text-white"
                    >
                        {{ auth.user?.initials }}
                    </AvatarFallback>
                </Avatar>

                <div v-if="props.sidebarOpen" class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-foreground truncate leading-none">
                        {{ auth.user?.name }}
                    </p>

                    <p
                        class="text-[10px] font-semibold text-muted-foreground mt-0.5 truncate uppercase"
                    >
                        {{ auth.user?.role }}
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Logout dialog -->
    <Dialog v-model:open="logoutDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle> Confirmer la déconnexion </DialogTitle>

                <DialogDescription> Êtes-vous sûr de vouloir vous déconnecter ? </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="logoutDialogOpen = false"> Annuler </Button>

                <Button :disabled="isLoggingOut" variant="destructive" @click="logout">
                    <template v-if="isLoggingOut"> Déconnexion... </template>

                    <template v-else>
                        <LogOut />
                        Se déconnecter
                    </template>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
