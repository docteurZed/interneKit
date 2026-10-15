<script setup lang="ts">
import { Info } from 'lucide-vue-next'
</script>

<template>
    <aside
        aria-label="Avertissement sur les informations publiées"
        class="mb-5 flex gap-3 border-l-2 border-amber-500 bg-amber-50 px-4 py-3 text-xs text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
        role="note"
    >
        <Info aria-hidden="true" class="mt-0.5 size-4 shrink-0" />
        <div>
            <p>Les informations publiées peuvent comporter des erreurs ou ne pas être à jour.</p>
        </div>
    </aside>
</template>
