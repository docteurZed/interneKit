<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
    Badge,
    Button,
    Input,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
    SheetClose,
    Switch,
    FieldGroup,
    Field,
    FieldLabel,
    FieldError,
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from '@/components'
import { AdminWrapper } from '../components'
import {
    Eye,
    Pencil,
    Plus,
    Trash,
    User,
    Mail,
    Phone,
    LayoutGrid,
    List,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    ShieldCheck,
    UserCheck,
    UserX,
    Search,
    MoreHorizontal,
} from 'lucide-vue-next'
import type { User as UserType, Role } from '@/types'
import { useUser } from '@/composables'
import { matchesSearch } from '@/lib/search'
import { toast } from 'vue-sonner'

const { users, loading, validationErrors, fetchUsers, createUser, updateUser, deleteUser } =
    useUser()

onMounted(fetchUsers)

type ViewMode = 'list' | 'card'
const viewMode = ref<ViewMode>('list')

const columns = [
    { key: 'name', label: 'Nom', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'phone', label: 'Téléphone', sortable: false },
    { key: 'role', label: 'Rôle', sortable: true },
    { key: 'is_active', label: 'Statut', sortable: true },
    { key: 'actions', label: 'Actions', sortable: false },
]

type DrawerMode = 'view' | 'add' | 'edit'
const drawerOpen = ref(false)
const drawerMode = ref<DrawerMode>('add')
const selectedUser = ref<UserType | null>(null)

const form = ref({
    name: '',
    email: '',
    phone: '',
    role: 'user' as Role,
    is_active: false,
})

function openAdd() {
    form.value = { name: '', email: '', phone: '', role: 'user', is_active: false }
    drawerMode.value = 'add'
    drawerOpen.value = true
}
function openView(user: UserType) {
    selectedUser.value = user
    drawerMode.value = 'view'
    drawerOpen.value = true
}
function openEdit(user: UserType) {
    form.value = {
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        role: user.role,
        is_active: user.is_active,
    }
    selectedUser.value = user
    drawerMode.value = 'edit'
    drawerOpen.value = true
}

const saving = ref(false)
const deleting = ref(false)

const isValid = computed(
    () => !!form.value.email?.trim() && !!form.value.name?.trim() && !!form.value.role?.trim(),
)

async function saveUser() {
    saving.value = true
    try {
        if (drawerMode.value === 'add') {
            await createUser({ ...form.value, phone: form.value.phone || null })
            toast.success('Utilisateur créé avec succès.')
        } else if (drawerMode.value === 'edit' && selectedUser.value) {
            await updateUser(selectedUser.value.id, {
                ...form.value,
                phone: form.value.phone || null,
            })
            toast.success('Utilisateur mis à jour avec succès.')
        }
        drawerOpen.value = false
    } catch {
        toast.error('Une erreur est survenue.')
    } finally {
        saving.value = false
    }
}

const deleteDialogOpen = ref(false)
const userToDelete = ref<UserType | null>(null)

function openDelete(user: UserType) {
    userToDelete.value = user
    deleteDialogOpen.value = true
}

async function confirmDelete() {
    deleting.value = true
    try {
        if (userToDelete.value) {
            await deleteUser(userToDelete.value.id)
            toast.success('Utilisateur supprimé avec succès.')
        }
        deleteDialogOpen.value = false
        userToDelete.value = null
    } catch {
        toast.error('Une erreur est survenue.')
    } finally {
        deleting.value = false
    }
}

const tableSearch = ref('')
const currentPage = ref(1)
const perPage = ref(10)

type SortOrder = 'asc' | 'desc' | null
const sortKey = ref<string | null>(null)
const sortOrder = ref<SortOrder>(null)

function toggleSort(key: string, sortable = true) {
    if (!sortable) return
    if (sortKey.value !== key) {
        sortKey.value = key
        sortOrder.value = 'asc'
    } else if (sortOrder.value === 'asc') {
        sortOrder.value = 'desc'
    } else {
        sortKey.value = null
        sortOrder.value = null
    }
}

function getSortIcon(key: string) {
    if (sortKey.value !== key) return ArrowUpDown
    return sortOrder.value === 'asc' ? ArrowUp : ArrowDown
}

const filteredData = computed(() => {
    let result = (users.value ?? []).filter((row: any) =>
        matchesSearch(Object.values(row).join(' '), tableSearch.value),
    )
    if (sortKey.value && sortOrder.value) {
        result = [...result].sort((a: any, b: any) => {
            const aVal = a[sortKey.value!]
            const bVal = b[sortKey.value!]
            if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
            if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
            return 0
        })
    }
    return result
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage.value))
const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredData.value.slice(start, start + perPage.value)
})

function setPage(page: number) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

watch(filteredData, () => {
    if (currentPage.value > totalPages.value) currentPage.value = totalPages.value || 1
})
watch(perPage, () => {
    currentPage.value = 1
})

const drawerTitle: Record<DrawerMode, string> = {
    view: 'Détails utilisateur',
    add: 'Ajouter un utilisateur',
    edit: "Modifier l'utilisateur",
}

function roleLabel(role: string) {
    return role === 'admin' ? 'Administrateur' : 'Utilisateur'
}

function roleBadgeVariant(role: string) {
    return role === 'admin' ? 'destructive' : 'secondary'
}

const avatarColors = [
    'bg-blue-500',
    'bg-violet-500',
    'bg-emerald-500',
    'bg-orange-500',
    'bg-pink-500',
    'bg-teal-500',
]

function avatarColor(name: string) {
    const idx = (name?.charCodeAt(0) ?? 0) % avatarColors.length
    return avatarColors[idx]
}

const visiblePages = computed(() => {
    const total = totalPages.value
    const cur = currentPage.value
    if (total <= 5) return Array.from({ length: total }, (_, i) => i + 1)
    const pages: (number | '...')[] = [1]
    if (cur > 3) pages.push('...')
    for (let p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) pages.push(p)
    if (cur < total - 2) pages.push('...')
    pages.push(total)
    return pages
})
</script>

<template>
    <AdminWrapper title="Administrateurs">
        <template #actions>
            <!-- Recherche -->
            <div class="relative sm:max-w-xs w-full">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none"
                />
                <Input
                    v-model="tableSearch"
                    placeholder="Rechercher un utilisateur..."
                    class="pl-9"
                />
            </div>

            <div class="flex items-center rounded-lg border bg-muted/40 p-1 gap-1">
                <Button
                    :variant="viewMode === 'list' ? 'default' : 'ghost'"
                    size="icon"
                    class="h-7 w-7"
                    @click="viewMode = 'list'"
                    title="Vue liste"
                >
                    <List class="w-4 h-4" />
                </Button>
                <Button
                    :variant="viewMode === 'card' ? 'default' : 'ghost'"
                    size="icon"
                    class="h-7 w-7"
                    @click="viewMode = 'card'"
                    title="Vue cartes"
                >
                    <LayoutGrid class="w-4 h-4" />
                </Button>
            </div>

            <Button @click="openAdd" class="gap-2 shrink-0">
                <Plus class="w-4 h-4" />
                Ajouter
            </Button>
        </template>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="rounded-xl border bg-card p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0"
                >
                    <User class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Total</p>
                    <p class="text-xl font-bold leading-none">{{ users?.length ?? 0 }}</p>
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0"
                >
                    <UserCheck class="w-5 h-5 text-emerald-600" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Actifs</p>
                    <p class="text-xl font-bold leading-none">
                        {{ users?.filter((u: any) => u.is_active).length ?? 0 }}
                    </p>
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-destructive/10 flex items-center justify-center shrink-0"
                >
                    <UserX class="w-5 h-5 text-destructive" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Inactifs</p>
                    <p class="text-xl font-bold leading-none">
                        {{ users?.filter((u: any) => !u.is_active).length ?? 0 }}
                    </p>
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-violet-500/10 flex items-center justify-center shrink-0"
                >
                    <ShieldCheck class="w-5 h-5 text-violet-600" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Admins</p>
                    <p class="text-xl font-bold leading-none">
                        {{ users?.filter((u: any) => u.role === 'admin').length ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════
             VUE LISTE (tableau)
        ════════════════════════════════════════════════════════ -->
        <div v-if="viewMode === 'list'" class="rounded-xl border overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow class="bg-muted/30 hover:bg-muted/30">
                        <TableHead
                            v-for="col in columns"
                            :key="col.key"
                            class="select-none text-primary font-bold text-xs uppercase tracking-wide"
                            :class="col.sortable !== false ? 'cursor-pointer' : ''"
                            @click="toggleSort(String(col.key), col.sortable)"
                        >
                            <div class="flex items-center gap-2">
                                {{ col.label }}
                                <component
                                    v-if="col.sortable !== false"
                                    :is="getSortIcon(String(col.key))"
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 5" :key="i" class="animate-pulse">
                            <TableCell v-for="col in columns" :key="col.key">
                                <div class="h-4 bg-muted rounded w-3/4" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else>
                        <TableRow
                            v-for="row in paginatedData"
                            :key="row.id"
                            class="group hover:bg-muted/20 transition-colors"
                        >
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                        :class="avatarColor(row.name)"
                                    >
                                        {{ row.initials }}
                                    </div>
                                    <span class="font-semibold">{{ row.name }}</span>
                                </div>
                            </TableCell>

                            <TableCell>
                                <span class="text-muted-foreground text-sm">{{ row.email }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm">{{ row.phone ?? '—' }}</span>
                            </TableCell>

                            <TableCell>
                                <Badge :variant="roleBadgeVariant(row.role)" class="text-xs">
                                    {{ roleLabel(row.role) }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full shrink-0"
                                        :class="
                                            row.is_active
                                                ? 'bg-emerald-500'
                                                : 'bg-muted-foreground/40'
                                        "
                                    />
                                    <span
                                        class="text-sm font-medium"
                                        :class="
                                            row.is_active
                                                ? 'text-emerald-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ row.is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="flex items-center gap-1 transition-opacity">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        @click="openView(row)"
                                        title="Voir"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        @click="openEdit(row)"
                                        title="Modifier"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        @click="openDelete(row)"
                                        title="Supprimer"
                                    >
                                        <Trash class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="paginatedData.length === 0">
                            <TableCell
                                :colspan="columns.length"
                                class="text-center py-12 text-muted-foreground"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <Search class="w-8 h-8 opacity-30" />
                                    <p>Aucun utilisateur trouvé</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- ════════════════════════════════════════════════════════
             VUE CARTES (grid)
        ════════════════════════════════════════════════════════ -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template v-if="loading">
                <div
                    v-for="i in 8"
                    :key="i"
                    class="rounded-xl border bg-card p-5 animate-pulse space-y-3"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-muted shrink-0" />
                        <div class="space-y-2 flex-1">
                            <div class="h-3.5 bg-muted rounded w-3/4" />
                            <div class="h-3 bg-muted rounded w-1/2" />
                        </div>
                    </div>
                    <div class="h-3 bg-muted rounded w-2/3" />
                    <div class="h-3 bg-muted rounded w-1/2" />
                </div>
            </template>

            <template v-else>
                <div
                    v-if="paginatedData.length === 0"
                    class="col-span-full text-center py-16 text-muted-foreground"
                >
                    <div class="flex flex-col items-center gap-2">
                        <Search class="w-8 h-8 opacity-30" />
                        <p>Aucun utilisateur trouvé</p>
                    </div>
                </div>

                <div
                    v-for="row in paginatedData"
                    :key="row.id"
                    class="group relative rounded-xl border bg-card p-5 hover:shadow-md hover:border-primary/30 transition-all duration-200 flex flex-col gap-4"
                >
                    <!-- Badge statut en haut à droite -->
                    <span
                        class="absolute top-4 right-4 flex items-center gap-1.5 text-xs font-medium"
                        :class="row.is_active ? 'text-emerald-600' : 'text-muted-foreground'"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full"
                            :class="row.is_active ? 'bg-emerald-500' : 'bg-muted-foreground/40'"
                        />
                        {{ row.is_active ? 'Actif' : 'Inactif' }}
                    </span>

                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0"
                            :class="avatarColor(row.name)"
                        >
                            {{ row.initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold truncate leading-tight">{{ row.name }}</p>
                            <Badge :variant="roleBadgeVariant(row.role)" class="text-[10px] mt-1">
                                {{ roleLabel(row.role) }}
                            </Badge>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <Mail class="w-3.5 h-3.5 shrink-0" />
                            <span class="truncate">{{ row.email }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <Phone class="w-3.5 h-3.5 shrink-0" />
                            <span>{{ row.phone ?? 'Non renseigné' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t">
                        <Button
                            variant="outline"
                            size="sm"
                            class="flex-1 gap-1.5 text-xs h-8"
                            @click="openView(row)"
                        >
                            <Eye class="w-3.5 h-3.5" /> Voir
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="flex-1 gap-1.5 text-xs h-8"
                            @click="openEdit(row)"
                        >
                            <Pencil class="w-3.5 h-3.5" /> Modifier
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8 text-destructive border-destructive/30 hover:bg-destructive/10 hover:border-destructive"
                            @click="openDelete(row)"
                        >
                            <Trash class="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>
            </template>
        </div>

        <div
            v-if="totalPages > 1 || filteredData.length > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 mt-2 text-sm text-muted-foreground"
        >
            <p>
                {{ filteredData.length }} résultat{{ filteredData.length > 1 ? 's' : '' }} — page
                {{ currentPage }} / {{ totalPages }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="currentPage === 1"
                    @click="setPage(currentPage - 1)"
                >
                    <ArrowUp class="w-3.5 h-3.5 -rotate-90" />
                </Button>
                <template v-for="p in visiblePages" :key="String(p)">
                    <span v-if="p === '...'" class="px-1 text-muted-foreground">…</span>
                    <Button
                        v-else
                        :variant="currentPage === p ? 'default' : 'outline'"
                        size="icon"
                        class="h-8 w-8 text-xs"
                        @click="setPage(Number(p))"
                    >
                        {{ p }}
                    </Button>
                </template>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="currentPage === totalPages"
                    @click="setPage(currentPage + 1)"
                >
                    <ArrowDown class="w-3.5 h-3.5 -rotate-90" />
                </Button>
            </div>
        </div>
    </AdminWrapper>

    <!-- ════════════════════════════════════════════════════════════
         SHEET — Voir / Ajouter / Modifier
    ════════════════════════════════════════════════════════════ -->
    <Sheet v-model:open="drawerOpen">
        <SheetContent class="w-full sm:max-w-xl overflow-y-auto p-4">
            <SheetHeader class="border-b pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-lg flex items-center justify-center"
                        :class="{
                            'bg-primary/10': drawerMode === 'add',
                            'bg-blue-500/10': drawerMode === 'view',
                            'bg-orange-500/10': drawerMode === 'edit',
                        }"
                    >
                        <Plus v-if="drawerMode === 'add'" class="w-4 h-4 text-primary" />
                        <Eye v-if="drawerMode === 'view'" class="w-4 h-4 text-blue-500" />
                        <Pencil v-if="drawerMode === 'edit'" class="w-4 h-4 text-orange-500" />
                    </div>
                    <SheetTitle class="text-base font-bold">{{
                        drawerTitle[drawerMode]
                    }}</SheetTitle>
                </div>
                <SheetDescription class="sr-only">
                    {{
                        drawerMode === 'view'
                            ? 'Informations détaillées sur cet utilisateur.'
                            : drawerMode === 'add'
                              ? 'Formulaire de création d’un utilisateur.'
                              : 'Formulaire de modification d’un utilisateur.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <template v-if="drawerMode === 'view' && selectedUser">
                <div class="space-y-6 px-1">
                    <div class="flex flex-col items-center gap-3 py-6 rounded-xl bg-muted/30">
                        <div
                            class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                            :class="avatarColor(selectedUser.name)"
                        >
                            {{ selectedUser.initials }}
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-lg">{{ selectedUser.name }}</p>
                            <Badge :variant="roleBadgeVariant(selectedUser.role)" class="mt-1">
                                {{ roleLabel(selectedUser.role) }}
                            </Badge>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-4 p-4 rounded-xl border bg-card">
                            <div
                                class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0"
                            >
                                <Mail class="w-4 h-4 text-primary" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-0.5">Email</p>
                                <p class="font-semibold text-sm">{{ selectedUser.email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 rounded-xl border bg-card">
                            <div
                                class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0"
                            >
                                <Phone class="w-4 h-4 text-primary" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-0.5">Téléphone</p>
                                <p class="font-semibold text-sm">
                                    {{ selectedUser.phone ?? 'Non renseigné' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 rounded-xl border bg-card">
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                :class="selectedUser.is_active ? 'bg-emerald-500/10' : 'bg-muted'"
                            >
                                <UserCheck
                                    v-if="selectedUser.is_active"
                                    class="w-4 h-4 text-emerald-600"
                                />
                                <UserX v-else class="w-4 h-4 text-muted-foreground" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-0.5">Statut</p>
                                <p
                                    class="font-semibold text-sm"
                                    :class="
                                        selectedUser.is_active
                                            ? 'text-emerald-600'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{ selectedUser.is_active ? 'Actif' : 'Inactif' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <SheetFooter class="flex gap-2 pt-2">
                        <Button @click="openEdit(selectedUser)" class="flex-1 gap-2">
                            <Pencil class="w-4 h-4" /> Modifier
                        </Button>
                        <SheetClose as-child>
                            <Button variant="outline" class="flex-1">Fermer</Button>
                        </SheetClose>
                    </SheetFooter>
                </div>
            </template>

            <template v-else-if="drawerMode !== 'view'">
                <form @submit.prevent="saveUser" class="space-y-5 px-1">
                    <FieldGroup class="grid md:grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>
                                Nom complet
                                <span class="text-destructive text-xs">*</span>
                            </FieldLabel>
                            <Input id="name" v-model="form.name" placeholder="John DOE" required />
                            <FieldError v-if="validationErrors.name?.[0]">
                                {{ validationErrors.name?.[0] }}
                            </FieldError>
                        </Field>

                        <Field>
                            <FieldLabel>
                                Email
                                <span class="text-destructive text-xs">*</span>
                            </FieldLabel>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="johndoe@mail.com"
                                required
                            />
                            <FieldError v-if="validationErrors.email?.[0]">
                                {{ validationErrors.email?.[0] }}
                            </FieldError>
                        </Field>

                        <Field>
                            <FieldLabel> Téléphone </FieldLabel>
                            <Input id="phone" v-model="form.phone" placeholder="+228 XX XX XX XX" />
                            <FieldError v-if="validationErrors.phone?.[0]">
                                {{ validationErrors.phone?.[0] }}
                            </FieldError>
                        </Field>

                        <Field>
                            <FieldLabel>
                                Rôle
                                <span class="text-destructive text-xs">*</span>
                            </FieldLabel>
                            <Select v-model="form.role">
                                <SelectTrigger>
                                    <SelectValue placeholder="Sélectionner un rôle" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="admin">Administrateur</SelectItem>
                                    <SelectItem value="user">Utilisateur</SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError v-if="validationErrors.role?.[0]">
                                {{ validationErrors.role?.[0] }}
                            </FieldError>
                        </Field>

                        <Field class="md:col-span-2">
                            <div class="flex items-center gap-3 p-4 rounded-xl border bg-muted/20">
                                <Switch
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="data-[state=checked]:bg-emerald-600 data-[state=unchecked]:bg-muted"
                                />
                                <div>
                                    <label
                                        for="is_active"
                                        class="cursor-pointer font-semibold text-sm block"
                                    >
                                        Compte actif
                                    </label>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            form.is_active
                                                ? "L'utilisateur peut se connecter"
                                                : "L'accès est désactivé"
                                        }}
                                    </p>
                                </div>
                            </div>
                            <FieldError v-if="validationErrors.is_active?.[0]">
                                {{ validationErrors.is_active?.[0] }}
                            </FieldError>
                        </Field>
                    </FieldGroup>

                    <SheetFooter class="flex gap-2 pt-2">
                        <Button type="submit" class="flex-1" :disabled="!isValid || saving">
                            <span v-if="saving" class="flex items-center gap-2">
                                <span
                                    class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                                />
                                Enregistrement…
                            </span>
                            <span v-else>
                                {{
                                    drawerMode === 'add'
                                        ? "Créer l'utilisateur"
                                        : 'Enregistrer les modifications'
                                }}
                            </span>
                        </Button>
                        <SheetClose as-child>
                            <Button variant="outline" class="flex-1">Annuler</Button>
                        </SheetClose>
                    </SheetFooter>
                </form>
            </template>
        </SheetContent>
    </Sheet>

    <!-- ════════════════════════════════════════════════════════════
         DIALOG — Suppression
    ════════════════════════════════════════════════════════════ -->
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <div
                    class="w-14 h-14 rounded-2xl bg-destructive/10 flex items-center justify-center mb-3"
                >
                    <Trash class="w-7 h-7 text-destructive" />
                </div>
                <DialogTitle class="text-base">Supprimer l'utilisateur ?</DialogTitle>
                <DialogDescription class="text-sm leading-relaxed">
                    Vous êtes sur le point de supprimer
                    <span class="font-semibold text-foreground">{{ userToDelete?.name }}</span
                    >. Cette action est
                    <span class="text-destructive font-medium">irréversible</span>.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 mt-2">
                <Button variant="outline" class="flex-1" @click="deleteDialogOpen = false"
                    >Annuler</Button
                >
                <Button
                    variant="destructive"
                    class="flex-1 gap-2"
                    @click="confirmDelete"
                    :disabled="deleting"
                >
                    <span
                        v-if="deleting"
                        class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                    />
                    <Trash v-else class="w-4 h-4" />
                    {{ deleting ? 'Suppression…' : 'Supprimer' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
