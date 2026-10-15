<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    points: { label: string; value: number }[]
    highlightIndex?: number
    color?: string
}>()

const WIDTH = 640
const HEIGHT = 220
const PAD = { top: 16, right: 16, bottom: 28, left: 32 }

const color = computed(() => props.color ?? 'var(--primary)')

const max = computed(() => Math.max(1, ...props.points.map((p) => p.value)))
const niceMax = computed(() => Math.max(4, Math.ceil(max.value / 4) * 4))

const innerWidth = WIDTH - PAD.left - PAD.right
const innerHeight = HEIGHT - PAD.top - PAD.bottom

const coords = computed(() =>
    props.points.map((point, index) => ({
        ...point,
        x:
            PAD.left +
            (props.points.length > 1
                ? (index / (props.points.length - 1)) * innerWidth
                : innerWidth / 2),
        y: PAD.top + innerHeight - (point.value / niceMax.value) * innerHeight,
    })),
)

const linePath = computed(() =>
    coords.value
        .map((c, i) => `${i === 0 ? 'M' : 'L'}${c.x.toFixed(1)},${c.y.toFixed(1)}`)
        .join(' '),
)

const areaPath = computed(() => {
    if (!coords.value.length) return ''
    const first = coords.value[0]!
    const last = coords.value[coords.value.length - 1]!
    const base = PAD.top + innerHeight
    return `${linePath.value} L${last.x.toFixed(1)},${base} L${first.x.toFixed(1)},${base} Z`
})

const gridLines = computed(() =>
    [0, 1, 2, 3, 4].map((step) => ({
        y: PAD.top + innerHeight - (step / 4) * innerHeight,
        label: Math.round((step / 4) * niceMax.value),
    })),
)

const labelIndexes = computed(() => {
    const last = props.points.length - 1
    if (last < 1) return [0]
    const step = Math.max(1, Math.ceil(props.points.length / 6))
    const indexes: number[] = []
    for (let i = 0; i <= last; i += step) indexes.push(i)
    return indexes
})

const gradientId = `line-fill-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
    <svg :viewBox="`0 0 ${WIDTH} ${HEIGHT}`" class="h-auto w-full" role="img">
        <defs>
            <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="color" stop-opacity="0.35" />
                <stop offset="100%" :stop-color="color" stop-opacity="0" />
            </linearGradient>
        </defs>

        <g v-for="line in gridLines" :key="line.y">
            <line
                :x1="PAD.left"
                :x2="WIDTH - PAD.right"
                :y1="line.y"
                :y2="line.y"
                stroke="var(--border)"
                stroke-dasharray="3 4"
            />
            <text
                :x="PAD.left - 8"
                :y="line.y + 4"
                text-anchor="end"
                class="fill-muted-foreground"
                font-size="11"
            >
                {{ line.label }}
            </text>
        </g>

        <path :d="areaPath" :fill="`url(#${gradientId})`" />
        <path
            :d="linePath"
            fill="none"
            :stroke="color"
            stroke-width="2.5"
            stroke-linejoin="round"
            stroke-linecap="round"
        />

        <line
            v-if="highlightIndex !== undefined && coords[highlightIndex]"
            :x1="coords[highlightIndex]!.x"
            :x2="coords[highlightIndex]!.x"
            :y1="PAD.top"
            :y2="PAD.top + innerHeight"
            stroke="var(--muted-foreground)"
            stroke-dasharray="4 4"
            stroke-opacity="0.6"
        />

        <circle
            v-for="(c, i) in coords"
            :key="i"
            :cx="c.x"
            :cy="c.y"
            :r="i === highlightIndex ? 5 : 3.5"
            :fill="i === highlightIndex ? color : 'var(--card)'"
            :stroke="color"
            stroke-width="2"
        >
            <title>{{ c.label }} : {{ c.value }}</title>
        </circle>

        <text
            v-for="i in labelIndexes"
            :key="`x-${i}`"
            :x="coords[i]?.x"
            :y="HEIGHT - 8"
            text-anchor="middle"
            class="fill-muted-foreground"
            font-size="11"
        >
            {{ coords[i]?.label }}
        </text>
    </svg>
</template>
