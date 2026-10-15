<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
    Activity,
    Building2,
    CalendarClock,
    CalendarDays,
    Repeat,
    RefreshCw,
    UserCheck,
    Users,
} from 'lucide-vue-next'
import { Button } from '@/components'
import { DashboardService, type DashboardData } from '@/services/admin/dashboard.service'
import { toast } from 'vue-sonner'
import { AdminWrapper } from '../components'
import DonutChart from './components/DonutChart.vue'
import LineChart from './components/LineChart.vue'

const data = ref<DashboardData | null>(null)
const loading = ref(false)

async function load() {
    loading.value = true
    try {
        data.value = await DashboardService.get()
    } catch (error) {
        const message =
            error && typeof error === 'object' && 'message' in error ? String(error.message) : ''
        toast.error(message || 'Impossible de charger le tableau de bord.')
    } finally {
        loading.value = false
    }
}

onMounted(load)

function dateLabel(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function timeLabel(value: string): string {
    return value.slice(11, 16).replace(':', 'h')
}

function localToday(): string {
    const now = new Date()
    return new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 10)
}

const kpiCards = computed(() => {
    const kpis = data.value?.kpis
    if (!kpis) return []

    return [
        {
            label: 'Internes',
            value: String(kpis.internes),
            hint: 'enregistrés',
            icon: Users,
            tone: 'bg-primary/10 text-primary',
        },
        {
            label: 'Internes affectés',
            value: String(kpis.internes_affectes),
            hint: `${kpis.internes_sans_affectation} sans affectation`,
            icon: UserCheck,
            tone: 'bg-emerald-500/10 text-emerald-600',
        },
        {
            label: 'Services',
            value: String(kpis.services),
            hint: 'hospitaliers',
            icon: Building2,
            tone: 'bg-violet-500/10 text-violet-600',
        },
        {
            label: 'Gardes ce mois',
            value: String(kpis.gardes_mois),
            hint: 'planifiées',
            icon: CalendarDays,
            tone: 'bg-amber-500/10 text-amber-600',
        },
        {
            label: 'Gardes à 7 jours',
            value: String(kpis.gardes_a_venir),
            hint: 'à venir',
            icon: CalendarClock,
            tone: 'bg-pink-500/10 text-pink-600',
        },
        {
            label: 'Rotation en cours',
            value: kpis.periode_en_cours?.name ?? 'Aucune',
            hint: kpis.periode_en_cours
                ? `${dateLabel(kpis.periode_en_cours.start_date)} – ${dateLabel(kpis.periode_en_cours.end_date)}`
                : 'aucune période active',
            icon: Repeat,
            tone: 'bg-sky-500/10 text-sky-600',
        },
    ]
})

const dailyPoints = computed(() =>
    (data.value?.gardes_par_jour ?? []).map((day) => ({
        label: `${day.date.slice(8, 10)}/${day.date.slice(5, 7)}`,
        value: day.count,
    })),
)

const todayIndex = computed(() => {
    const index = (data.value?.gardes_par_jour ?? []).findIndex((day) => day.date === localToday())
    return index === -1 ? undefined : index
})

const monthlyPoints = computed(() =>
    (data.value?.gardes_par_mois ?? []).map((month) => ({
        label: new Date(`${month.month}-01T00:00:00`).toLocaleDateString('fr-FR', {
            month: 'short',
        }),
        value: month.count,
    })),
)
</script>

<template>
    <AdminWrapper title="Tableau de bord">
        <template #actions>
            <Button
                variant="outline"
                size="icon"
                title="Actualiser"
                :disabled="loading"
                @click="load"
            >
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
            </Button>
        </template>

        <div v-if="!data" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="i in 6" :key="i" class="h-20 animate-pulse rounded-xl border bg-card" />
        </div>

        <template v-else>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="flex items-center gap-3 rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="card.tone"
                    >
                        <component :is="card.icon" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">{{ card.label }}</p>
                        <p class="truncate text-xl font-bold leading-tight">{{ card.value }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ card.hint }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <section class="rounded-xl border bg-card p-5 lg:col-span-2">
                    <header class="mb-3 flex items-center gap-2">
                        <Activity class="h-4 w-4 text-primary" />
                        <h2 class="font-semibold">Gardes sur 30 jours</h2>
                        <span class="text-xs text-muted-foreground"
                            >14 jours passés · 15 à venir</span
                        >
                    </header>
                    <LineChart :points="dailyPoints" :highlight-index="todayIndex" />
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">
                        Types de garde
                        <span class="text-xs font-normal text-muted-foreground"
                            >6 derniers mois</span
                        >
                    </h2>
                    <DonutChart :items="data.gardes_par_type" />
                </section>
            </div>

            <section class="mt-4 rounded-xl border bg-card p-5">
                <h2 class="mb-3 font-semibold">Évolution mensuelle des gardes</h2>
                <LineChart :points="monthlyPoints" color="var(--chart-2)" />
            </section>

            <section class="mt-4 rounded-xl border bg-card p-5">
                <h2 class="mb-4 font-semibold">Prochaines gardes</h2>
                <ul v-if="data.prochaines_gardes.length" class="divide-y">
                    <li
                        v-for="garde in data.prochaines_gardes"
                        :key="garde.id"
                        class="flex items-center justify-between gap-4 py-3 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ garde.type ?? '—' }} ·
                                <span v-if="garde.hopital">({{ garde.hopital }}) </span
                                >{{ garde.service ?? '—' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ dateLabel(garde.date_garde) }} · {{ timeLabel(garde.start) }}–{{
                                    timeLabel(garde.end)
                                }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                        >
                            {{ garde.participants }} interne{{ garde.participants > 1 ? 's' : '' }}
                        </span>
                    </li>
                </ul>
                <p v-else class="py-10 text-center text-sm text-muted-foreground">
                    Aucune garde à venir.
                </p>
            </section>
        </template>
    </AdminWrapper>
</template>
