<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
    ArrowRight,
    Clock3,
    MapPin,
    Phone,
    RefreshCw,
    Stethoscope,
    UsersRound,
} from 'lucide-vue-next'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger, Button } from '@/components'
import { localNow } from '@/lib/garde-time'
import { normalizeSearch } from '@/lib/search'
import { PublicGardeService, type PublicGarde } from '@/services/public/gardes.service'
import { PublicNotice, PublicWrapper } from '../components'

type Consultation = {
    specialite: string
    site: string
    prix: number
    jours: string[] | null
    horaires: string | null
    sur_rendez_vous: boolean | null
}

const now = ref(localNow())
const today = ref(now.value.slice(0, 10))
const gardes = ref<PublicGarde[]>([])
const consultations = ref<Consultation[]>([])
const loadingGardes = ref(true)
const loadingConsultations = ref(true)
const gardeError = ref(false)
const consultationError = ref(false)

const formattedToday = computed(() =>
    today.value
        ? new Intl.DateTimeFormat('fr-FR', {
              weekday: 'long',
              day: '2-digit',
              month: 'long',
              timeZone: 'UTC',
          }).format(new Date(`${today.value}T12:00:00Z`))
        : '',
)

const weekday = computed(() =>
    today.value
        ? new Intl.DateTimeFormat('fr-FR', { weekday: 'long', timeZone: 'UTC' })
              .format(new Date(`${today.value}T12:00:00Z`))
              .toLocaleLowerCase('fr-FR')
        : '',
)

const currentGuards = computed(() => {
    if (!today.value) return []

    const groupedGuards = new Map<string, PublicGarde>()

    const ongoing = gardes.value.filter(
        (item) => item.start.slice(0, 16) <= now.value && now.value < item.end.slice(0, 16),
    )

    for (const garde of ongoing) {
        const key = JSON.stringify([
            garde.date_garde,
            garde.start,
            garde.end,
            garde.type,
            garde.service,
            garde.hopital,
        ])
        const existingGarde = groupedGuards.get(key)

        if (!existingGarde) {
            groupedGuards.set(key, { ...garde, internes: [...garde.internes] })
            continue
        }

        const interneIds = new Set(existingGarde.internes.map((interne) => interne.id))
        existingGarde.internes.push(
            ...garde.internes.filter((interne) => !interneIds.has(interne.id)),
        )
    }

    return [...groupedGuards.values()]
})

const todaysConsultations = computed(() =>
    consultations.value.filter((consultation) => consultation.jours?.includes(weekday.value)),
)

const groupedConsultations = computed(() => {
    const groups = new Map<string, { specialite: string; items: Consultation[] }>()

    for (const consultation of todaysConsultations.value) {
        const key = normalizeSearch(consultation.specialite)
        const group = groups.get(key) ?? { specialite: consultation.specialite, items: [] }
        group.items.push(consultation)
        groups.set(key, group)
    }

    return [...groups.values()]
})

async function loadGuards() {
    loadingGardes.value = true
    gardeError.value = false

    try {
        const response = await PublicGardeService.get()
        gardes.value = response.data
        today.value = response.meta.today
    } catch {
        gardeError.value = true
    } finally {
        loadingGardes.value = false
    }
}

async function loadConsultations() {
    loadingConsultations.value = true
    consultationError.value = false

    try {
        const response = await fetch('/data/consultations.json')
        if (!response.ok) throw new Error('Consultations indisponibles')
        const data: { consultations: Consultation[] } = await response.json()
        consultations.value = data.consultations
    } catch {
        consultationError.value = true
    } finally {
        loadingConsultations.value = false
    }
}

async function refresh() {
    await Promise.all([loadGuards(), loadConsultations()])
}

function formatPrice(price: number): string {
    return `${new Intl.NumberFormat('fr-FR').format(price)} FCFA`
}

onMounted(refresh)

const clock = window.setInterval(() => {
    now.value = localNow()
}, 60_000)
onUnmounted(() => window.clearInterval(clock))
</script>

<template>
    <PublicWrapper>
        <section aria-labelledby="home-heading" class="scroll-mt-20">
            <header class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h1 id="home-heading" class="text-xl font-semibold sm:text-2xl">
                        Aujourd’hui,
                        <span v-if="formattedToday" class="text-muted-foreground">{{
                            formattedToday
                        }}</span>
                    </h1>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Actualiser l’accueil"
                    title="Actualiser"
                    :disabled="loadingGardes || loadingConsultations"
                    @click="refresh"
                >
                    <RefreshCw
                        class="size-4"
                        :class="{ 'animate-spin': loadingGardes || loadingConsultations }"
                    />
                </Button>
            </header>

            <PublicNotice />

            <Accordion
                type="single"
                collapsible
                default-value="guards"
                class="overflow-hidden rounded-xl border border-border bg-card px-4"
            >
                <AccordionItem value="guards">
                    <div class="grid w-full grid-cols-[minmax(0,1fr)_auto] items-center gap-2">
                        <AccordionTrigger class="min-w-0 hover:no-underline">
                            <span class="flex min-w-0 items-center gap-2">
                                <UsersRound
                                    aria-hidden="true"
                                    class="size-4 shrink-0 text-primary"
                                />
                                <span
                                    id="guard-heading"
                                    class="min-w-0 break-words text-xs font-semibold sm:text-base"
                                    >Internes de garde</span
                                >
                                <span
                                    v-if="!loadingGardes && !gardeError"
                                    class="shrink-0 text-sm font-medium text-muted-foreground"
                                    >{{ currentGuards.length }}</span
                                >
                            </span>
                        </AccordionTrigger>
                        <RouterLink
                            to="/gardes"
                            class="flex w-[5.5rem] shrink-0 items-center justify-end gap-1 text-[10px] font-medium text-primary hover:underline md:w-auto md:whitespace-nowrap md:text-xs"
                        >
                            <span class="min-w-0 break-words text-right leading-tight"
                                >Toutes les gardes</span
                            >
                            <ArrowRight aria-hidden="true" class="size-3 shrink-0" />
                        </RouterLink>
                    </div>
                    <AccordionContent>
                        <p
                            v-if="loadingGardes"
                            class="py-8 text-center text-sm text-muted-foreground"
                            role="status"
                        >
                            Chargement des gardes…
                        </p>
                        <div v-else-if="gardeError" class="py-6 text-center" role="alert">
                            <p class="text-sm font-medium">Les gardes ne sont pas disponibles.</p>
                            <Button variant="outline" class="mt-3" @click="loadGuards"
                                >Réessayer</Button
                            >
                        </div>
                        <div v-else-if="currentGuards.length" class="divide-y divide-border">
                            <article v-for="garde in currentGuards" :key="garde.id" class="py-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold">{{ garde.type ?? 'Garde' }}</h3>
                                        <p
                                            class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <MapPin aria-hidden="true" class="size-3.5 shrink-0" />
                                            {{ garde.hopital ? `(${garde.hopital}) ` : ''
                                            }}{{ garde.service ?? 'Service non précisé' }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="garde.internes.length" class="mt-3 space-y-2">
                                    <div
                                        v-for="interne in garde.internes"
                                        :key="interne.id"
                                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-sm bg-muted/40 px-3 py-2 text-sm"
                                    >
                                        <span class="font-medium">{{ interne.name }}</span>
                                        <span
                                            v-if="interne.phone"
                                            class="inline-flex items-center gap-1 text-primary text-xs hover:underline"
                                        >
                                            <Phone aria-hidden="true" class="size-2.5" />{{
                                                interne.phone
                                            }}
                                        </span>
                                        <span v-else class="text-xs italic text-muted-foreground italic"
                                            >Téléphone non renseigné</span
                                        >
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">
                            Aucun interne de garde en ce moment.
                        </p>
                    </AccordionContent>
                </AccordionItem>

                <AccordionItem value="consultations">
                    <div class="grid w-full grid-cols-[minmax(0,1fr)_auto] items-center gap-2">
                        <AccordionTrigger class="min-w-0 hover:no-underline">
                            <span class="flex min-w-0 items-center gap-2">
                                <Stethoscope
                                    aria-hidden="true"
                                    class="size-4 shrink-0 text-emerald-500"
                                />
                                <span
                                    id="consultation-heading"
                                    class="min-w-0 break-words text-xs font-semibold sm:text-base"
                                    >Consultations aujourd’hui</span
                                >
                                <span
                                    v-if="!loadingConsultations && !consultationError"
                                    class="shrink-0 text-sm font-medium text-muted-foreground"
                                    >{{ groupedConsultations.length }}</span
                                >
                            </span>
                        </AccordionTrigger>
                        <RouterLink
                            to="/consultations"
                            class="flex w-[5.5rem] shrink-0 items-center justify-end gap-1 text-[10px] font-medium text-primary hover:underline md:w-auto md:whitespace-nowrap md:text-xs"
                        >
                            <span class="min-w-0 break-words text-right leading-tight"
                                >Toutes les consultations</span
                            >
                            <ArrowRight aria-hidden="true" class="size-3 shrink-0" />
                        </RouterLink>
                    </div>
                    <AccordionContent>
                        <p
                            v-if="loadingConsultations"
                            class="py-8 text-center text-sm text-muted-foreground"
                            role="status"
                        >
                            Chargement des consultations…
                        </p>
                        <div v-else-if="consultationError" class="py-6 text-center" role="alert">
                            <p class="text-sm font-medium">
                                Les consultations ne sont pas disponibles.
                            </p>
                            <Button variant="outline" class="mt-3" @click="loadConsultations"
                                >Réessayer</Button
                            >
                        </div>
                        <div v-else-if="groupedConsultations.length" class="divide-y divide-border">
                            <article
                                v-for="group in groupedConsultations"
                                :key="group.specialite"
                                class="py-3"
                            >
                                <h3 class="font-semibold">{{ group.specialite }}</h3>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="(consultation, index) in group.items"
                                        :key="`${consultation.site}-${index}`"
                                        class="rounded-sm bg-muted/40 px-3 py-2"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <p
                                                class="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground"
                                            >
                                                <MapPin
                                                    aria-hidden="true"
                                                    class="size-3.5 shrink-0"
                                                />{{ consultation.site }}
                                            </p>
                                            <span class="shrink-0 text-sm font-semibold">{{
                                                formatPrice(consultation.prix)
                                            }}</span>
                                        </div>
                                        <div
                                            v-if="
                                                consultation.horaires ||
                                                consultation.sur_rendez_vous !== null
                                            "
                                            class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                        >
                                            <span
                                                v-if="consultation.horaires"
                                                class="inline-flex items-center gap-1.5"
                                                ><Clock3 aria-hidden="true" class="size-3.5" />{{
                                                    consultation.horaires.replace('-', ' – ')
                                                }}</span
                                            >
                                            <span
                                                v-if="consultation.sur_rendez_vous === true"
                                                class="text-amber-700 dark:text-amber-300"
                                                >Sur rendez-vous</span
                                            >
                                            <span
                                                v-else-if="consultation.sur_rendez_vous === false"
                                                class="text-emerald-700 dark:text-emerald-300"
                                                >Sans rendez-vous</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">
                            Aucune consultation prévue aujourd’hui.
                        </p>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </section>
    </PublicWrapper>
</template>
