<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import {
    CalendarDays,
    Clock3,
    MapPin,
    Phone,
    RefreshCw,
    Search,
    UsersRound,
    X,
} from 'lucide-vue-next'
import { Button } from '@/components'
import { gardeStatus, localNow, type GardeStatus } from '@/lib/garde-time'
import { matchesSearch } from '@/lib/search'
import { whatsappUrl } from '@/lib/whatsapp'
import { PublicGardeService, type PublicGarde } from '@/services/public/gardes.service'
import { PublicNotice, PublicWrapper } from '../components'

const gardes = ref<PublicGarde[]>([])
const today = ref('')
const selectedDate = ref('')
const search = ref('')
const loading = ref(true)
const loadError = ref(false)
const now = ref(localNow())

const statusBadges: Record<GardeStatus, { label: string; class: string }> = {
    upcoming: {
        label: 'À venir',
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    ongoing: {
        label: 'En cours',
        class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    past: { label: 'Passée', class: 'bg-muted text-muted-foreground' },
}

function addDays(date: string, offset: number): string {
    const value = new Date(`${date}T12:00:00Z`)
    value.setUTCDate(value.getUTCDate() + offset)
    return value.toISOString().slice(0, 10)
}

function formatDate(date: string, options: Intl.DateTimeFormatOptions): string {
    return new Intl.DateTimeFormat('fr-FR', { ...options, timeZone: 'UTC' }).format(
        new Date(`${date}T12:00:00Z`),
    )
}

const days = computed(() => {
    if (!today.value) return []

    return [-1, 0, 1, 2].map((offset) => {
        const date = addDays(today.value, offset)
        const label =
            offset === -1
                ? 'Hier'
                : offset === 0
                  ? 'Aujourd’hui'
                  : offset === 1
                    ? 'Demain'
                    : formatDate(date, { weekday: 'long' })

        return {
            date,
            label,
            fullDate: formatDate(date, { day: '2-digit', month: 'long', year: 'numeric' }),
            hasOngoing: gardes.value.some(
                (garde) => garde.date_garde === date && gardeStatus(garde, now.value) === 'ongoing',
            ),
        }
    })
})

const visibleGardes = computed(() => {
    return gardes.value.filter((garde) => {
        if (garde.date_garde !== selectedDate.value) return false
        if (!search.value.trim()) return true

        const searchable = [
            garde.type,
            garde.service,
            garde.hopital,
            ...garde.internes.flatMap((interne) => [interne.name, interne.phone]),
        ]
            .filter(Boolean)
            .join(' ')

        return matchesSearch(searchable, search.value)
    })
})

async function load() {
    loading.value = true
    loadError.value = false

    try {
        const response = await PublicGardeService.get()
        gardes.value = response.data
        today.value = response.meta.today
        selectedDate.value ||= response.meta.today
    } catch {
        loadError.value = true
    } finally {
        loading.value = false
    }
}

function timeLabel(value: string): string {
    return value.slice(11, 16).replace(':', 'h')
}

function relativeDayLabel(date: string): string {
    const labels: Record<string, string> = {
        [addDays(today.value, -1)]: 'Hier',
        [today.value]: 'Aujourd’hui',
        [addDays(today.value, 1)]: 'Demain',
        [addDays(today.value, 2)]: 'Après-demain',
    }

    return labels[date] ?? formatDate(date, { weekday: 'short', day: 'numeric', month: 'short' })
}

function guardRange(garde: PublicGarde): string {
    const startDate = garde.start.slice(0, 10)
    const endDate = garde.end.slice(0, 10)
    const start = timeLabel(garde.start)
    const end = timeLabel(garde.end)

    return startDate === endDate
        ? `${start} – ${end}`
        : `${relativeDayLabel(startDate)} ${start} → ${relativeDayLabel(endDate)} ${end}`
}

onMounted(load)

const clock = window.setInterval(() => {
    now.value = localNow()
}, 60_000)
onUnmounted(() => window.clearInterval(clock))
</script>

<template>
    <PublicWrapper>
        <section aria-labelledby="gardes-heading" class="scroll-mt-20">
            <header class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h1 id="gardes-heading" class="text-xl font-semibold sm:text-2xl">Gardes</h1>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Actualiser les gardes"
                    title="Actualiser"
                    :disabled="loading"
                    @click="load"
                >
                    <RefreshCw class="size-4" :class="{ 'animate-spin': loading }" />
                </Button>
            </header>

            <PublicNotice />

            <div class="mb-5 space-y-3">
                <div class="relative">
                    <label class="sr-only" for="guard-search"
                        >Rechercher un type, un service ou un interne</label
                    >
                    <Search
                        aria-hidden="true"
                        class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        id="guard-search"
                        v-model="search"
                        type="search"
                        placeholder="Type, service ou interne"
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

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button
                        v-for="day in days"
                        :key="day.date"
                        type="button"
                        :aria-pressed="selectedDate === day.date"
                        class="relative flex min-h-11 flex-col items-start justify-center rounded-md border px-3 py-2 text-left transition-colors"
                        :class="
                            selectedDate === day.date
                                ? 'border-primary bg-primary/10 text-primary'
                                : day.hasOngoing
                                  ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/15 dark:text-emerald-300'
                                  : 'border-border bg-card text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="selectedDate = day.date"
                    >
                        <span
                            v-if="day.hasOngoing"
                            class="absolute right-2 top-2 flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                        >
                            <span class="size-1.5 animate-pulse rounded-full bg-emerald-500" />En
                            cours
                        </span>
                        <span class="text-xs font-semibold capitalize">{{ day.label }}</span>
                        <span class="text-xs">{{ day.fullDate }}</span>
                    </button>
                </div>
            </div>

            <p v-if="loading" class="py-12 text-center text-sm text-muted-foreground" role="status">
                Chargement du planning…
            </p>

            <div v-else-if="loadError" class="py-8 text-center" role="alert">
                <p class="font-medium">Le planning des gardes n’est pas disponible.</p>
                <Button variant="outline" class="mt-4" @click="load">Réessayer</Button>
            </div>

            <div v-else-if="visibleGardes.length" class="space-y-3">
                <article
                    v-for="garde in visibleGardes"
                    :key="garde.id"
                    class="overflow-hidden rounded-md border border-border bg-card"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold">{{ garde.type ?? 'Garde' }}</h2>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="statusBadges[gardeStatus(garde, now)].class"
                                    >{{ statusBadges[gardeStatus(garde, now)].label }}</span
                                >
                            </div>
                            <p
                                class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <MapPin aria-hidden="true" class="size-3.5 shrink-0" />{{
                                    garde.hopital ? `(${garde.hopital}) ` : ''
                                }}{{ garde.service ?? 'Service non précisé' }}
                            </p>
                        </div>
                        <span
                            class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-primary"
                        >
                            <Clock3 aria-hidden="true" class="size-3.5" />{{ guardRange(garde) }}
                        </span>
                    </header>

                    <div
                        v-if="garde.internes.length"
                        class="grid gap-x-4 divide-y divide-border px-4 sm:grid-cols-2 sm:divide-y-0"
                    >
                        <div
                            v-for="(interne, index) in garde.internes"
                            :key="interne.id"
                            class="flex min-w-0 items-center justify-between gap-3 py-3 text-sm"
                            :class="index >= 2 ? 'sm:border-t sm:border-border' : ''"
                        >
                            <span class="flex min-w-0 items-center gap-2 font-medium">
                                <UsersRound
                                    aria-hidden="true"
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span class="truncate">{{ interne.name }}</span>
                            </span>
                            <a
                                v-if="interne.phone && whatsappUrl(interne.phone)"
                                :href="whatsappUrl(interne.phone)!"
                                target="_blank"
                                rel="noopener noreferrer"
                                :aria-label="`Contacter ${interne.name} sur WhatsApp`"
                                class="inline-flex shrink-0 items-center gap-1.5 text-sm text-primary hover:underline"
                            >
                                <Phone aria-hidden="true" class="size-3.5" />{{ interne.phone }}
                            </a>
                            <span v-else class="shrink-0 text-xs text-muted-foreground"
                                >Téléphone non renseigné</span
                            >
                        </div>
                    </div>
                    <p v-else class="px-4 py-4 text-sm text-muted-foreground">
                        Aucun interne associé à cette garde.
                    </p>
                </article>
            </div>

            <div v-else class="py-8 text-center">
                <CalendarDays
                    aria-hidden="true"
                    class="mx-auto mb-3 size-8 text-muted-foreground/60"
                />
                <p class="font-medium">
                    {{ search ? 'Aucune garde trouvée' : 'Aucune garde pour cette date' }}
                </p>
            </div>
        </section>
    </PublicWrapper>
</template>
