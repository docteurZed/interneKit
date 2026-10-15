<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import {
    Activity,
    Cross,
    HeartPulse,
    Pill,
    Stethoscope,
    Syringe,
    Thermometer,
    UsersRound,
} from 'lucide-vue-next'

const icons = [Stethoscope, HeartPulse, Pill, Cross, Syringe, Activity, Thermometer, UsersRound]

// La grille pivotée fait 150 % de l'écran pour couvrir les coins.
const SCALE = 1.5
const CELL_WIDTH = 108
const ROW_HEIGHT = 84

type Tile = { icon: (typeof icons)[number]; style: Record<string, string> }

const columns = ref(6)
const tiles = ref<Tile[]>([])

function random(min: number, max: number): number {
    return min + Math.random() * (max - min)
}

function build() {
    columns.value = Math.max(1, Math.floor((window.innerWidth * SCALE) / CELL_WIDTH))
    const rows = Math.ceil((window.innerHeight * SCALE) / ROW_HEIGHT)
    const picked: (typeof icons)[number][] = []

    for (let index = 0; index < columns.value * rows; index++) {
        // Une icône identique à sa voisine de gauche ou du dessus rendrait le hasard visible.
        const forbidden = [picked[index - 1], picked[index - columns.value]]
        const choices = icons.filter((icon) => !forbidden.includes(icon))
        picked.push(choices[Math.floor(Math.random() * choices.length)]!)
    }

    tiles.value = picked.map((icon) => ({
        icon,
        style: {
            animationDuration: `${random(4, 8).toFixed(1)}s`,
            animationDelay: `-${random(0, 8).toFixed(1)}s`,
        },
    }))
}

let resizeTimer: number | undefined

function onResize() {
    window.clearTimeout(resizeTimer)
    resizeTimer = window.setTimeout(build, 300)
}

onMounted(() => {
    build()
    window.addEventListener('resize', onResize)
})

onUnmounted(() => {
    window.clearTimeout(resizeTimer)
    window.removeEventListener('resize', onResize)
})
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none fixed inset-0 z-0 overflow-hidden text-foreground opacity-[0.04] dark:opacity-[0.06]"
    >
        <div
            class="absolute top-1/2 left-1/2 grid h-[150%] w-[150%] -translate-x-1/2 -translate-y-1/2 -rotate-12 content-start place-items-center gap-y-12"
            :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }"
        >
            <component
                :is="tile.icon"
                v-for="(tile, index) in tiles"
                :key="index"
                class="watermark-icon size-9"
                :style="tile.style"
            />
        </div>
    </div>
</template>

<style scoped>
.watermark-icon {
    animation: watermark-float ease-in-out infinite alternate;
    /* L'ombre décalée donne l'impression d'un volume. */
    filter: drop-shadow(2px 3px 0 color-mix(in srgb, currentColor 45%, transparent));
}

@keyframes watermark-float {
    from {
        transform: perspective(260px) translate(-8px, -16px) rotateY(-38deg) rotateX(14deg)
            rotate(-14deg) scale(0.95);
    }
    to {
        transform: perspective(260px) translate(8px, 16px) rotateY(38deg) rotateX(-14deg)
            rotate(14deg) scale(1.1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .watermark-icon {
        animation: none;
    }
}
</style>
