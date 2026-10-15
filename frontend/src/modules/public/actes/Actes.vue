<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Banknote, MapPin, Search, X } from 'lucide-vue-next'
import { matchesSearch } from '@/lib/search'
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
import { PublicNotice, PublicWrapper } from '../components'

type TarifActe = {
    etablissement: string
    prix: number | null
    prix_a_confirmer?: number[]
}

type Acte = {
    nom: string
    categorie: string
    tarifs: TarifActe[]
}

type ActesMetadata = {
    avertissement: string
    devise: string
}

const actes = ref<Acte[]>([])
const metadata = ref<ActesMetadata | null>(null)
const search = ref('')
const selectedSite = ref('all')
const selectedCategory = ref('all')
const loading = ref(true)
const loadError = ref(false)
const pageSize = 20
const currentPage = ref(1)
const pageTop = ref<HTMLElement | null>(null)

const sites = computed(() => [
    ...new Set(actes.value.flatMap((acte) => acte.tarifs.map((tarif) => tarif.etablissement))),
])
const categories = computed(() => [...new Set(actes.value.map((acte) => acte.categorie))])

const filteredActes = computed(() => {
    return actes.value.flatMap((acte) => {
        if (selectedCategory.value !== 'all' && acte.categorie !== selectedCategory.value) return []

        const tarifs = acte.tarifs.filter(
            (tarif) => selectedSite.value === 'all' || tarif.etablissement === selectedSite.value,
        )
        const searchableText = `${acte.nom} ${acte.categorie} ${tarifs
            .map((tarif) => tarif.etablissement)
            .join(' ')}`

        return tarifs.length && matchesSearch(searchableText, search.value)
            ? [{ ...acte, tarifs }]
            : []
    })
})

const paginatedActes = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return filteredActes.value.slice(start, start + pageSize)
})

watch([search, selectedSite, selectedCategory], () => {
    currentPage.value = 1
})

watch(currentPage, async () => {
    await nextTick()
    pageTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
})

const formatPrice = (price: number) => new Intl.NumberFormat('fr-FR').format(price)
const formatUnconfirmedPrices = (prices: number[]) =>
    `${prices.map(formatPrice).join(' / ')} ${metadata.value?.devise ?? 'FCFA'}`

onMounted(async () => {
    try {
        const response = await fetch('/data/actes_sante.json')
        if (!response.ok) throw new Error('Unable to load acts')

        const data: { metadata: ActesMetadata; actes: Acte[] } = await response.json()
        actes.value = data.actes
        metadata.value = data.metadata
    } catch {
        loadError.value = true
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <PublicWrapper>
        <section ref="pageTop" aria-labelledby="actes-heading" class="scroll-mt-20">
            <header class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h1 id="actes-heading" class="text-xl font-semibold sm:text-2xl">Actes</h1>
                </div>
            </header>

            <PublicNotice />

            <div class="mb-6 grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem_14rem]">
                <div class="relative">
                    <label class="sr-only" for="act-search"
                        >Rechercher un acte, une catégorie ou un établissement</label
                    >
                    <Search
                        aria-hidden="true"
                        class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        id="act-search"
                        v-model="search"
                        type="search"
                        placeholder="Acte, catégorie ou établissement"
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
                        <SelectTrigger aria-label="Filtrer par établissement" class="bg-card pl-10">
                            <SelectValue placeholder="Tous les établissements" />
                        </SelectTrigger>
                    </div>
                    <SelectContent>
                        <SelectItem value="all">Tous les établissements</SelectItem>
                        <SelectItem v-for="site in sites" :key="site" :value="site">
                            {{ site }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedCategory">
                    <div class="relative">
                        <Banknote
                            aria-hidden="true"
                            class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <SelectTrigger aria-label="Filtrer par catégorie" class="bg-card pl-10">
                            <SelectValue placeholder="Toutes les catégories" />
                        </SelectTrigger>
                    </div>
                    <SelectContent>
                        <SelectItem value="all">Toutes les catégories</SelectItem>
                        <SelectItem
                            v-for="category in categories"
                            :key="category"
                            :value="category"
                        >
                            {{ category }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <p v-if="loading" class="py-8 text-center text-sm text-muted-foreground" role="status">
                Chargement des actes...
            </p>

            <div v-else-if="loadError" class="py-8 text-center" role="alert">
                <p class="font-medium">Le catalogue des actes n'est pas disponible.</p>
            </div>

            <div v-else-if="filteredActes.length" class="space-y-3">
                <article
                    v-for="acte in paginatedActes"
                    :key="acte.nom"
                    class="overflow-hidden rounded-md border border-border bg-card"
                >
                    <header
                        class="flex items-center justify-between gap-3 border-b border-border bg-muted/40 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">{{ acte.nom }}</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ acte.categorie }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-medium text-muted-foreground">
                            {{ acte.tarifs.length }} site{{ acte.tarifs.length > 1 ? 's' : '' }}
                        </span>
                    </header>

                    <div class="divide-y divide-border">
                        <div
                            v-for="tarif in acte.tarifs"
                            :key="`${tarif.etablissement}-${tarif.prix}-${tarif.prix_a_confirmer?.join('|') ?? ''}`"
                            class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                        >
                            <span class="flex min-w-0 items-center gap-1.5 text-muted-foreground">
                                <MapPin aria-hidden="true" class="size-3.5 shrink-0 text-primary" />
                                <span class="truncate">{{ tarif.etablissement }}</span>
                            </span>
                            <span
                                v-if="tarif.prix_a_confirmer?.length"
                                class="shrink-0 text-xs font-medium text-amber-800 dark:text-amber-200"
                            >
                                À confirmer · {{ formatUnconfirmedPrices(tarif.prix_a_confirmer) }}
                            </span>
                            <span
                                v-else-if="tarif.prix && tarif.prix > 0"
                                class="shrink-0 font-semibold text-foreground"
                            >
                                {{ formatPrice(tarif.prix) }} {{ metadata?.devise ?? 'FCFA' }}
                            </span>
                            <span
                                v-else-if="tarif.prix === 0"
                                class="shrink-0 text-xs font-medium text-amber-800 dark:text-amber-200"
                            >
                                Tarif à confirmer
                            </span>
                            <span v-else class="shrink-0 text-xs text-muted-foreground">
                                Tarif non indiqué
                            </span>
                        </div>
                    </div>
                </article>

                <div
                    v-if="filteredActes.length > pageSize"
                    class="flex flex-col items-center justify-between gap-3 pt-3 sm:flex-row"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ (currentPage - 1) * pageSize + 1 }}–{{
                            Math.min(currentPage * pageSize, filteredActes.length)
                        }}
                        sur {{ filteredActes.length }} actes
                    </p>
                    <Pagination
                        v-model:page="currentPage"
                        :total="filteredActes.length"
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
                <p class="font-medium">Aucun acte trouvé</p>
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
