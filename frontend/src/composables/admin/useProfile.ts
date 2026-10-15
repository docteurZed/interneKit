import { ref } from 'vue'
import type { PasswordDto, UserDto } from '@/types'
import { ProfileService } from '@/services'

export function useProfile() {
    const loading = ref(false)
    const error = ref<string | null>(null)
    const validationErrors = ref<Record<string, string[]>>({})

    function clearErrors() {
        error.value = null
        validationErrors.value = {}
    }

    async function updateInfos(id: number, payload: Partial<UserDto>) {
        clearErrors()

        try {
            await ProfileService.updateInfos(id, payload)
        } catch (e: any) {
            if (e?.status === 422) {
                validationErrors.value = e.errors
            } else {
                error.value = 'Erreur lors de la mise à jour'
            }
            throw e
        }
    }

    async function updatePassword(payload: Partial<PasswordDto>) {
        clearErrors()

        try {
            await ProfileService.updatePassword(payload)
        } catch (e: any) {
            if (e?.status === 422) {
                validationErrors.value = e.errors
            } else {
                error.value = 'Erreur lors de la mise à jour'
            }
            throw e
        }
    }

    return {
        loading,
        error,
        validationErrors,
        updateInfos,
        updatePassword,
    }
}
