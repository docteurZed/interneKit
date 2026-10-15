<script setup lang="ts">
import { HeartHandshake, MessageCircle, Stethoscope, UserRound } from 'lucide-vue-next'
import { Button } from '@/components'
import config from '@/config'
import { whatsappUrl } from '@/lib/whatsapp'
import { PublicWrapper } from '../components'

const baseUrl = whatsappUrl(config.appPhone)

const contactUrl = baseUrl
    ? `${baseUrl}?text=${encodeURIComponent(`Bonjour, je vous écris à propos de ${config.appName}.`)}`
    : null
const donationUrl = baseUrl
    ? `${baseUrl}?text=${encodeURIComponent(`Bonjour, je souhaite soutenir ${config.appName} par un don.`)}`
    : null
</script>

<template>
    <PublicWrapper>
        <section aria-labelledby="about-heading" class="scroll-mt-20">
            <header class="mb-5">
                <h1 id="about-heading" class="text-xl font-semibold sm:text-2xl">À propos</h1>
            </header>

            <div class="space-y-4">
                <article class="rounded-md border border-border bg-card px-4 py-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold">
                        <Stethoscope aria-hidden="true" class="size-4 text-primary" />{{
                            config.appName
                        }}
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Planning des gardes, annuaire des internes, consultations et tarifs des actes
                        hospitaliers, réunis au même endroit et consultables depuis un téléphone.
                    </p>
                </article>

                <article class="rounded-md border border-border bg-card px-4 py-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold">
                        <UserRound aria-hidden="true" class="size-4 text-primary" />Contacter le
                        créateur
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Une erreur, une suggestion ou une information à corriger ? Écris-moi
                        directement sur WhatsApp.
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ config.appPhone }}</p>
                    <Button v-if="contactUrl" as-child variant="outline" class="mt-3">
                        <a :href="contactUrl" target="_blank" rel="noopener noreferrer">
                            <MessageCircle aria-hidden="true" class="size-4" />Me contacter sur
                            WhatsApp
                        </a>
                    </Button>
                </article>

                <article class="rounded-md border border-border bg-card px-4 py-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold">
                        <HeartHandshake aria-hidden="true" class="size-4 text-primary" />Soutenir
                        le projet
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        L'application est gratuite. Si elle t'est utile, tu peux participer aux frais
                        d'hébergement et à son amélioration par un don. Contacte-moi sur WhatsApp
                        pour connaître les moyens de paiement disponibles.
                    </p>
                    <Button v-if="donationUrl" as-child class="mt-3">
                        <a :href="donationUrl" target="_blank" rel="noopener noreferrer">
                            <HeartHandshake aria-hidden="true" class="size-4" />Faire un don
                        </a>
                    </Button>
                </article>
            </div>
        </section>
    </PublicWrapper>
</template>
