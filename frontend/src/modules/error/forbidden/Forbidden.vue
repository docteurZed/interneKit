<script setup lang="ts">
import { ErrorWrapper } from '../components'

const title = 'Accès refusé'
const description = "Vous n'avez pas les autorisations nécessaires pour accéder à cette page."
</script>

<template>
    <ErrorWrapper :code="403" :title="title" :description="description" />
</template>
