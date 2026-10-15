<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Building2, Phone, Search, UsersRound, X } from 'lucide-vue-next'
import {
    Button,
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components'
import { matchesSearch } from '@/lib/search'
import { PublicInterneService, type PublicInterne } from '@/services/public/internes.service'
import { PublicNotice, PublicWrapper } from '../components'

const internes = ref<PublicInterne[]>([])
const currentPeriod =
    ref<Awaited<ReturnType<typeof PublicInterneService.get>>['meta']['current_period']>(null)
const view = ref<'all' | 'services'>('all')
const search = ref('')
const currentPage = ref(1)
const pageTop = ref<HTMLElement | null>(null)
const pageSize = 20
const loading = ref(true)
const loadError = ref(false)

const filteredInternes = computed(() => {
    if (!search.value.trim()) return internes.value

    return internes.value.filter((interne) =>
        matchesSearch(
            [interne.name, interne.phone ?? '', interne.service ?? '', interne.hopital ?? ''].join(
                ' ',
            ),
            search.value,
        ),
    )
})

const serviceGroups = computed(() => {
    const groups = new Map<string, PublicInterne[]>()

    for (const interne of filteredInternes.value) {
        const service = interne.service
            ? `${interne.hopital ? `(${interne.hopital}) ` : '(Hôpital à associer) '}${interne.service}`
            : 'Sans affectation en cours'
        const members = groups.get(service) ?? []
        members.push(interne)
        groups.set(service, members)
    }

    return [...groups.entries()]
        .sort(([a], [b]) => {
            if (a === 'Sans affectation en cours') return 1
            if (b === 'Sans affectation en cours') return -1
            return a.localeCompare(b, 'fr-FR')
        })
        .map(([service, members]) => ({ service, members }))
})

const pageItemCount = computed(() =>
    view.value === 'all' ? filteredInternes.value.length : serviceGroups.value.length,
)
const paginatedInternes = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return filteredInternes.value.slice(start, start + pageSize)
})
const paginatedServiceGroups = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return serviceGroups.value.slice(start, start + pageSize)
})

watch([search, view], () => {
    currentPage.value = 1
})

watch(currentPage, async () => {
    await nextTick()
    pageTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
})

const currentPeriodLabel = computed(() => {
    if (!currentPeriod.value) return 'Aucune période de rotation en cours'
    return `${currentPeriod.value.name} · ${dateLabel(currentPeriod.value.start_date)} – ${dateLabel(currentPeriod.value.end_date)}`
})

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${value.slice(0, 10)}T12:00:00Z`))
}

async function load() {
    loading.value = true
    loadError.value = false

    try {
        const response = await PublicInterneService.get()
        internes.value = response.data
        currentPeriod.value = response.meta.current_period
    } catch {
        loadError.value = true
    } finally {
        loading.value = false
    }
}

onMounted(load)
</script>

<template>
    <PublicWrapper>
        <section ref="pageTop" aria-labelledby="internes-heading" class="scroll-mt-20">
            <header class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h1 id="internes-heading" class="text-xl font-semibold sm:text-2xl">
                        Internes
                    </h1>
                </div>
            </header>

            <PublicNotice />

            <div class="mb-6 space-y-4">
                <div class="relative">
                    <label class="sr-only" for="interne-search"
                        >Rechercher un interne, un contact ou un service</label
                    >
                    <Search
                        aria-hidden="true"
                        class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        id="interne-search"
                        v-model="search"
                        type="search"
                        placeholder="Nom, téléphone ou service"
                        class="h-10 w-full rounded-xl border border-input bg-card pl-10 pr-10 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <button
                        v-if="search"
                        type="button"
                        aria-label="Effacer la recherche"
                        title="Effacer la recherche"
                        class="absolute right-1 top-1/2 z-10 grid size-8 -translate-y-1/2 place-items-center rounded-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                        @click="search = ''"
                    >
                        <X aria-hidden="true" class="size-4" />
                    </button>
                </div>

                <div
                    class="grid grid-cols-2 gap-2"
                    role="tablist"
                    aria-label="Affichage des internes"
                >
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="view === 'all'"
                        class="rounded-xl border px-3 py-2 text-sm font-medium transition-colors cursor-pointer"
                        :class="
                            view === 'all'
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="view = 'all'"
                    >
                        Tous les internes
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="view === 'services'"
                        class="rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors cursor-pointer"
                        :class="
                            view === 'services'
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="view = 'services'"
                    >
                        Par service actuel
                    </button>
                </div>
            </div>

            <p
                v-if="view === 'services' && !loading && !loadError && filteredInternes.length"
                class="mb-4 flex items-center justify-center gap-2 text-center text-sm text-muted-foreground"
            >
                <Building2 aria-hidden="true" class="size-4 shrink-0 text-primary" />{{
                    currentPeriodLabel
                }}
            </p>

            <p v-if="loading" class="py-12 text-center text-sm text-muted-foreground" role="status">
                Chargement des internes…
            </p>

            <div v-else-if="loadError" class="py-8 text-center" role="alert">
                <p class="font-medium">La liste des internes n'est pas disponible.</p>
                <Button variant="outline" class="mt-4" @click="load">Réessayer</Button>
            </div>

            <template v-else-if="view === 'all'">
                <div
                    v-if="filteredInternes.length"
                    class="overflow-hidden rounded-md border border-border bg-card"
                >
                    <div
                        v-for="(interne, index) in paginatedInternes"
                        :key="interne.id"
                        class="flex items-center justify-between gap-4 px-4 py-3"
                        :class="
                            index < paginatedInternes.length - 1 ? 'border-b border-border' : ''
                        "
                    >
                        <p class="text-sm font-medium">{{ interne.name }}</p>

                        <span
                            v-if="interne.phone"
                            class="inline-flex shrink-0 items-center gap-1 text-xs text-primary"
                        >
                            <Phone aria-hidden="true" class="size-2.5" />{{ interne.phone }}
                        </span>
                        <span v-else class="shrink-0 text-xs text-muted-foreground italic"
                            >Téléphone non renseigné</span
                        >
                    </div>
                </div>
                <div v-else class="py-8 text-center">
                    <UsersRound
                        aria-hidden="true"
                        class="mx-auto mb-3 size-8 text-muted-foreground/60"
                    />
                    <p class="font-medium">Aucun interne trouvé</p>
                </div>
            </template>

            <div v-else-if="serviceGroups.length" class="space-y-3">
                <article
                    v-for="group in paginatedServiceGroups"
                    :key="group.service"
                    class="overflow-hidden rounded-md border border-border bg-card"
                >
                    <header
                        class="flex items-center justify-between gap-3 border-b border-border bg-muted/40 px-4 py-3"
                    >
                        <h2 class="text-base font-semibold">{{ group.service }}</h2>
                        <span class="shrink-0 text-xs font-medium text-muted-foreground"
                            >{{ group.members.length }} interne{{
                                group.members.length > 1 ? 's' : ''
                            }}</span
                        >
                    </header>
                    <div>
                        <div
                            v-for="(interne, index) in group.members"
                            :key="interne.id"
                            class="flex items-center justify-between gap-4 px-4 py-3"
                            :class="
                                index < group.members.length - 1 ? 'border-b border-border' : ''
                            "
                        >
                            <p class="text-sm font-medium">{{ interne.name }}</p>

                            <span
                                v-if="interne.phone"
                                class="inline-flex shrink-0 items-center gap-1 text-xs text-primary"
                            >
                                <Phone aria-hidden="true" class="size-2.5" />{{ interne.phone }}
                            </span>
                            <span v-else class="shrink-0 text-xs italic text-muted-foreground"
                                >Téléphone non renseigné</span
                            >
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="py-8 text-center">
                <Building2
                    aria-hidden="true"
                    class="mx-auto mb-2 size-8 text-muted-foreground/60"
                />
                <p class="font-medium">Aucun interne trouvé</p>
            </div>

            <div
                v-if="!loading && !loadError && pageItemCount > pageSize"
                class="flex flex-col items-center justify-between gap-3 pt-4 sm:flex-row"
            >
                <p class="text-xs text-muted-foreground">
                    {{ (currentPage - 1) * pageSize + 1 }}–{{
                        Math.min(currentPage * pageSize, pageItemCount)
                    }}
                    sur {{ pageItemCount }} {{ view === 'all' ? 'internes' : 'services' }}
                </p>
                <Pagination
                    v-model:page="currentPage"
                    :total="pageItemCount"
                    :items-per-page="pageSize"
                >
                    <PaginationContent v-slot="{ items }">
                        <PaginationPrevious
                            class="border-border bg-card text-foreground hover:bg-muted"
                        >
                            Précédent
                        </PaginationPrevious>
                        <template v-for="(item, index) in items" :key="index">
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                :is-active="item.value === currentPage"
                                :class="
                                    item.value === currentPage
                                        ? 'border-primary bg-primary text-foreground hover:bg-primary/90'
                                        : 'border-border bg-card text-muted-foreground hover:bg-muted'
                                "
                            >
                                {{ item.value }}
                            </PaginationItem>
                            <PaginationEllipsis v-else />
                        </template>
                        <PaginationNext
                            class="border-border bg-card text-foreground hover:bg-muted"
                        >
                            Suivant
                        </PaginationNext>
                    </PaginationContent>
                </Pagination>
            </div>
        </section>
    </PublicWrapper>
</template>
