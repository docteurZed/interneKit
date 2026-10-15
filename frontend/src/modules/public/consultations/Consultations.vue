<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Banknote, CalendarDays, Clock3, MapPin, Search, X } from 'lucide-vue-next'
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components'
import { matchesSearch } from '@/lib/search'
import { PublicNotice, PublicWrapper } from '../components'

type Consultation = {
    specialite: string
    site: string
    prix: number
    jours: string[] | null
    horaires: string | null
    sur_rendez_vous: boolean | null
}

const weekdays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']
const formatPrice = (price: number) => new Intl.NumberFormat('fr-FR').format(price)
const today = new Intl.DateTimeFormat('fr-FR', { weekday: 'long' })
    .format(new Date())
    .toLocaleLowerCase('fr-FR')
const consultations = ref<Consultation[]>([])
const search = ref('')
const selectedSite = ref('Tous les sites')
const selectedDay = ref('all')
const loading = ref(true)
const loadError = ref(false)
const pageSize = 20
const currentPage = ref(1)
const pageTop = ref<HTMLElement | null>(null)

const sites = computed(() => [
    'Tous les sites',
    ...new Set(consultations.value.map((consultation) => consultation.site)),
])

const filteredConsultations = computed(() => {
    return consultations.value.filter((consultation) => {
        const searchableText = `${consultation.specialite} ${consultation.site}`

        return (
            (selectedSite.value === 'Tous les sites' || consultation.site === selectedSite.value) &&
            (selectedDay.value === 'all' || consultation.jours?.includes(selectedDay.value)) &&
            matchesSearch(searchableText, search.value)
        )
    })
})

const groupedConsultations = computed(() => {
    const groups = new Map<string, Consultation[]>()

    for (const consultation of filteredConsultations.value) {
        const group = groups.get(consultation.specialite) ?? []
        group.push(consultation)
        groups.set(consultation.specialite, group)
    }

    return Array.from(groups, ([specialite, locations]) => ({ specialite, locations }))
})

const visibleSiteCount = computed(
    () => new Set(filteredConsultations.value.map((consultation) => consultation.site)).size,
)
const paginatedConsultations = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return groupedConsultations.value.slice(start, start + pageSize)
})

watch([search, selectedSite, selectedDay], () => {
    currentPage.value = 1
})

watch(currentPage, async () => {
    await nextTick()
    pageTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
})

onMounted(async () => {
    try {
        const response = await fetch('/data/consultations.json')
        if (!response.ok) throw new Error('Unable to load consultations')

        const data: { consultations: Consultation[] } = await response.json()
        consultations.value = data.consultations
    } catch {
        loadError.value = true
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <PublicWrapper>
        <section ref="pageTop" aria-labelledby="consultations-heading" class="scroll-mt-20">
            <header class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h1 id="consultations-heading" class="text-xl font-semibold sm:text-2xl">
                        Consultations
                    </h1>
                </div>
            </header>

            <PublicNotice />

            <div class="mb-6 space-y-4">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem_14rem]">
                    <div class="relative">
                        <label class="sr-only" for="consultation-search"
                            >Rechercher une spécialité ou un site</label
                        >
                        <Search
                            aria-hidden="true"
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            id="consultation-search"
                            v-model="search"
                            type="search"
                            placeholder="Spécialité ou site"
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

                    <Select v-model="selectedSite">
                        <div class="relative">
                            <MapPin
                                aria-hidden="true"
                                class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <SelectTrigger aria-label="Filtrer par site" class="bg-card pl-10">
                                <SelectValue placeholder="Tous les sites" />
                            </SelectTrigger>
                        </div>
                        <SelectContent>
                            <SelectItem v-for="site in sites" :key="site" :value="site">
                                {{ site }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedDay">
                        <div class="relative">
                            <CalendarDays
                                aria-hidden="true"
                                class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <SelectTrigger aria-label="Filtrer par jour" class="bg-card pl-10">
                                <SelectValue placeholder="Tous les jours" />
                            </SelectTrigger>
                        </div>
                        <SelectContent>
                            <SelectItem value="all">Tous les jours</SelectItem>
                            <SelectItem
                                v-for="day in weekdays"
                                :key="day"
                                :value="day"
                                class="capitalize"
                            >
                                {{ day }}{{ day === today ? ' (aujourd’hui)' : '' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <p v-if="loading" class="py-8 text-center text-sm text-muted-foreground" role="status">
                Chargement des consultations...
            </p>

            <div v-else-if="loadError" class="py-8 text-center" role="alert">
                <p class="font-medium">Les consultations ne sont pas disponibles.</p>
            </div>

            <div v-else-if="groupedConsultations.length" class="space-y-3">
                <article
                    v-for="group in paginatedConsultations"
                    :key="group.specialite"
                    class="overflow-hidden rounded-md border border-border bg-card"
                >
                    <header
                        class="flex items-center justify-between gap-3 border-b border-border bg-muted/40 px-4 py-3"
                    >
                        <h2 class="text-lg font-semibold">{{ group.specialite }}</h2>
                        <span class="shrink-0 text-xs font-medium text-muted-foreground">
                            {{ group.locations.length }} site{{
                                group.locations.length > 1 ? 's' : ''
                            }}
                        </span>
                    </header>

                    <div class="divide-y divide-border">
                        <div
                            v-for="consultation in group.locations"
                            :key="consultation.site"
                            class="grid gap-3 px-4 py-3 sm:grid-cols-[minmax(10rem,0.75fr)_minmax(0,1.8fr)_auto] sm:items-center"
                        >
                            <p class="flex items-center gap-2 text-sm font-medium">
                                <MapPin aria-hidden="true" class="size-4 shrink-0 text-primary" />
                                {{ consultation.site }}
                            </p>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                <span class="flex items-start gap-1.5 text-muted-foreground">
                                    <CalendarDays
                                        aria-hidden="true"
                                        class="mt-0.5 size-4 shrink-0"
                                    />
                                    <span
                                        v-if="consultation.jours?.length"
                                        class="flex flex-wrap gap-x-1"
                                    >
                                        <span
                                            v-for="(day, index) in consultation.jours"
                                            :key="day"
                                            class="capitalize"
                                        >
                                            {{ day
                                            }}<span v-if="index < consultation.jours.length - 1"
                                                >,</span
                                            >
                                        </span>
                                    </span>
                                    <span v-else>Jours non précisés</span>
                                </span>

                                <span
                                    v-if="consultation.horaires"
                                    class="flex items-center gap-1.5 text-muted-foreground"
                                >
                                    <Clock3 aria-hidden="true" class="size-4 shrink-0" />
                                    {{ consultation.horaires }}
                                </span>

                                <span
                                    v-if="consultation.sur_rendez_vous !== null"
                                    class="rounded-sm px-2 py-1 text-xs font-medium"
                                    :class="
                                        consultation.sur_rendez_vous
                                            ? 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200'
                                            : 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                                    "
                                >
                                    {{
                                        consultation.sur_rendez_vous
                                            ? 'Sur rendez-vous'
                                            : 'Sans rendez-vous'
                                    }}
                                </span>
                            </div>

                            <p
                                class="flex items-center gap-1.5 text-sm font-semibold text-foreground sm:justify-self-end"
                            >
                                <Banknote aria-hidden="true" class="size-4 shrink-0 text-primary" />
                                {{ formatPrice(consultation.prix) }} FCFA
                            </p>
                        </div>
                    </div>
                </article>

                <div
                    v-if="groupedConsultations.length > pageSize"
                    class="flex flex-col items-center justify-between gap-3 pt-3 sm:flex-row"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ (currentPage - 1) * pageSize + 1 }}–{{
                            Math.min(currentPage * pageSize, groupedConsultations.length)
                        }}
                        sur {{ groupedConsultations.length }} spécialités
                    </p>
                    <Pagination
                        v-model:page="currentPage"
                        :total="groupedConsultations.length"
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
            </div>

            <div v-else class="border-y border-border py-12 text-center">
                <p class="font-medium">Aucune consultation trouvée</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Modifiez la recherche ou les filtres pour afficher d'autres résultats.
                </p>
            </div>
        </section>
    </PublicWrapper>
</template>

<style scoped>
input[type='search']::-webkit-search-cancel-button,
input[type='search']::-webkit-search-decoration {
    display: none;
    -webkit-appearance: none;
}
</style>
