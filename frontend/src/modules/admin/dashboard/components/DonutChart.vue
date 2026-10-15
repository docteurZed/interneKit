<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ items: { name: string; count: number }[] }>()

const COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
]

const RADIUS = 52
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const total = computed(() => props.items.reduce((sum, item) => sum + item.count, 0))

const segments = computed(() => {
    let offset = 0
    return props.items.map((item, index) => {
        const length = total.value ? (item.count / total.value) * CIRCUMFERENCE : 0
        const segment = {
            ...item,
            color: COLORS[index % COLORS.length]!,
            percent: total.value ? Math.round((item.count / total.value) * 100) : 0,
            dasharray: `${length} ${CIRCUMFERENCE - length}`,
            dashoffset: -offset,
        }
        offset += length
        return segment
    })
})
</script>

<template>
    <div v-if="total" class="flex flex-col items-center gap-5 sm:flex-row">
        <svg viewBox="0 0 140 140" class="h-36 w-36 shrink-0 -rotate-90" role="img">
            <circle
                cx="70"
                cy="70"
                :r="RADIUS"
                fill="none"
                stroke="var(--muted)"
                stroke-width="16"
            />
            <circle
                v-for="segment in segments"
                :key="segment.name"
                cx="70"
                cy="70"
                :r="RADIUS"
                fill="none"
                :stroke="segment.color"
                stroke-width="16"
                :stroke-dasharray="segment.dasharray"
                :stroke-dashoffset="segment.dashoffset"
            >
                <title>{{ segment.name }} : {{ segment.count }}</title>
            </circle>
        </svg>
        <ul class="w-full min-w-0 space-y-2 text-sm">
            <li v-for="segment in segments" :key="segment.name" class="flex items-center gap-2">
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                    :style="{ background: segment.color }"
                />
                <span class="min-w-0 flex-1 truncate">{{ segment.name }}</span>
                <span class="font-semibold">{{ segment.count }}</span>
                <span class="w-10 text-right text-muted-foreground">{{ segment.percent }}%</span>
            </li>
        </ul>
    </div>
    <p v-else class="py-10 text-center text-sm text-muted-foreground">
        Aucune garde sur la période.
    </p>
</template>
