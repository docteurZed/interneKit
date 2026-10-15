import { ref } from 'vue'
import { UserService } from '@/services'
import type { User, UserDto } from '@/types'

export function useUser() {
    const users = ref<User[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)
    const validationErrors = ref<Record<string, string[]>>({})

    function clearErrors() {
        error.value = null
        validationErrors.value = {}
    }

    async function fetchUsers() {
        loading.value = true
        clearErrors()

        try {
            users.value = await UserService.getAll()
        } catch (e: any) {
            error.value = 'Erreur lors du chargement'
        } finally {
            loading.value = false
        }
    }

    async function createUser(payload: UserDto) {
        clearErrors()

        try {
            const user = await UserService.create(payload)
            users.value.push(user)
            return user
        } catch (e: any) {
            if (e?.status === 422) {
                validationErrors.value = e.errors
            } else {
                error.value = 'Erreur lors de la création'
            }
            throw e
        }
    }

    async function updateUser(id: number, payload: Partial<UserDto>) {
        clearErrors()

        try {
            const updated = await UserService.update(id, payload)
            const idx = users.value.findIndex((u) => u.id === id)
            if (idx !== -1) users.value[idx] = updated
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

    async function deleteUser(id: number) {
        clearErrors()

        try {
            await UserService.delete(id)
            users.value = users.value.filter((u) => u.id !== id)
        } catch (e: any) {
            error.value = 'Erreur lors de la suppression'
            throw e
        }
    }

    return {
        users,
        loading,
        error,
        validationErrors,
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
    }
}
