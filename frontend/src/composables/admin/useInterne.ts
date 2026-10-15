import { ref } from 'vue'
import { InterneService } from '@/services'
import type { Interne, InterneDto } from '@/types'

export function useInterne() {
    const internes = ref<Interne[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)
    const validationErrors = ref<Record<string, string[]>>({})

    function clearErrors() {
        error.value = null
        validationErrors.value = {}
    }

    async function fetchInternes() {
        loading.value = true
        clearErrors()

        try {
            internes.value = await InterneService.getAll()
        } catch (e: any) {
            error.value = 'Erreur lors du chargement'
        } finally {
            loading.value = false
        }
    }

    async function createInterne(payload: InterneDto) {
        clearErrors()

        try {
            const interne = await InterneService.create(payload)
            internes.value.push(interne)
            return interne
        } catch (e: any) {
            if (e?.status === 422) {
                validationErrors.value = e.errors
            } else {
                error.value = 'Erreur lors de la création'
            }
            throw e
        }
    }

    async function updateInterne(id: number, payload: Partial<InterneDto>) {
        clearErrors()

        try {
            const updated = await InterneService.update(id, payload)
            const idx = internes.value.findIndex((i) => i.id === id)
            if (idx !== -1) internes.value[idx] = updated
            return updated
        } catch (e: any) {
            if (e?.status === 422) {
                validationErrors.value = e.errors
            } else {
                error.value = 'Erreur lors de la mise à jour'
            }
            throw e
        }
    }

    async function deleteInterne(id: number) {
        clearErrors()

        try {
            await InterneService.delete(id)
            internes.value = internes.value.filter((i) => i.id !== id)
        } catch (e: any) {
            error.value = 'Erreur lors de la suppression'
            throw e
        }
    }

    return {
        internes,
        loading,
        error,
        validationErrors,
        fetchInternes,
        createInterne,
        updateInterne,
        deleteInterne,
    }
}
