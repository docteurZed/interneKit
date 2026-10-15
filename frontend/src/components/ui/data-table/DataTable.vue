<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    Card,
    CardContent,
    CardHeader,
    CardFooter,
    Input,
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
    PaginationEllipsis,
    Spinner,
} from '@/components'

import { ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-vue-next'
import { matchesSearch } from '@/lib/search'

export interface Column<T> {
    key: keyof T | string
    label: string
    sortable?: boolean
}

const props = defineProps<{
    data: any[]
    columns: Column<any>[]
    perPage?: number
    loading?: boolean
}>()

const tableSearch = defineModel<string>('tableSearch', { default: '' })

const currentPage = ref(1)
const perPage = ref<number>(props.perPage && props.perPage > 0 ? props.perPage : 10)

type SortOrder = 'asc' | 'desc' | null
const sortKey = ref<string | null>(null)
const sortOrder = ref<SortOrder>(null)

function toggleSort(key: string, sortable = true) {
    if (!sortable) return

    if (sortKey.value !== key) {
        sortKey.value = key
        sortOrder.value = 'asc'
    } else if (sortOrder.value === 'asc') {
        sortOrder.value = 'desc'
    } else {
        sortKey.value = null
        sortOrder.value = null
    }
}

function getSortIcon(key: string) {
    if (sortKey.value !== key) return ArrowUpDown
    return sortOrder.value === 'asc' ? ArrowUp : ArrowDown
}

const filteredData = computed(() => {
    let result = props.data.filter((row) =>
        matchesSearch(Object.values(row).join(' '), tableSearch.value),
    )

    if (sortKey.value && sortOrder.value) {
        result = [...result].sort((a, b) => {
            const aVal = a[sortKey.value!]
            const bVal = b[sortKey.value!]

            if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
            if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
            return 0
        })
    }

    return result
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage.value))

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredData.value.slice(start, start + perPage.value)
})

function setPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

watch(filteredData, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = totalPages.value || 1
    }
})

watch(perPage, () => {
    currentPage.value = 1
})
</script>

<template>
    <div v-if="loading" class="text-center py-12 flex gap-4 items-center justify-center">
        <Spinner />
        <span class="text-muted-foreground">Chargement...</span>
    </div>

    <Card v-else>
        <CardHeader class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <Input v-model="tableSearch" placeholder="Rechercher..." class="sm:max-w-xs" />
            <slot name="actions" />
        </CardHeader>

        <CardContent class="">
            <div class="border-y overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                v-for="col in columns"
                                :key="col.key"
                                class="select-none text-primary"
                                :class="col.sortable !== false ? 'cursor-pointer' : ''"
                                @click="toggleSort(String(col.key), col.sortable)"
                            >
                                <div class="flex items-center justify-between font-bold pr-8">
                                    {{ col.label }}
                                    <component
                                        v-if="col.sortable !== false"
                                        :is="getSortIcon(String(col.key))"
                                        class="h-4 w-4 text-primary"
                                    />
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="row in paginatedData" :key="row.id">
                            <TableCell v-for="col in columns" :key="col.key">
                                <div class="font-semibold">
                                    <slot
                                        :name="`cell-${String(col.key)}`"
                                        :row="row"
                                        :value="row[col.key]"
                                    >
                                        {{ row[col.key] }}
                                    </slot>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="paginatedData.length === 0">
                            <TableCell
                                :colspan="columns.length"
                                class="text-center text-muted-foreground"
                            >
                                Aucun résultat
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>

        <CardFooter
            class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between w-full"
            v-if="filteredData.length"
        >
            <div class="text-xs font-semibold text-center">
                Page {{ currentPage }} sur {{ totalPages }}
            </div>

            <Pagination
                v-model:page="currentPage"
                :total="filteredData.length"
                :items-per-page="perPage"
            >
                <PaginationContent v-slot="{ items }" class="flex items-center">
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
                            @click="setPage(item.value)"
                        >
                            {{ item.value }}
                        </PaginationItem>

                        <PaginationEllipsis v-else />
                    </template>

                    <PaginationNext class="border-border bg-card text-foreground hover:bg-muted">
                        Suivant
                    </PaginationNext>
                </PaginationContent>
            </Pagination>

            <div class="text-xs font-semibold text-center">
                Affichage de
                {{ (currentPage - 1) * perPage + 1 }} -
                {{ Math.min(currentPage * perPage, filteredData.length) }}
                sur {{ filteredData.length }}
            </div>
        </CardFooter>
    </Card>
</template>
