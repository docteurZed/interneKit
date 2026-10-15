<script setup lang="ts">
import { ref } from 'vue'
import { Field, FieldLabel, Input, Button, FieldError, Spinner } from '@/components'
import { AuthWrapper } from '../components'
import { toast } from 'vue-sonner'
import type { ResetPayload } from '@/types'
import { sendResetLink } from '@/services'

const form = ref<ResetPayload>({
    email: '',
})

const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function submit() {
    errorMessage.value = ''
    fieldErrors.value = {}
    loading.value = true

    try {
        await sendResetLink(form.value)
        toast.success('Lien de réinitialisation envoyé.')
    } catch (err: any) {
        if (err.errors) {
            fieldErrors.value = err.errors
        } else {
            errorMessage.value = err.message || "Impossible d'envoyer le lien de réinitialisation"
            toast.error(errorMessage.value)
        }
        // console.error(err)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <AuthWrapper
        title="Mot de passe oublié"
        description="Entrez votre email pour recevoir un lien de réinitialisation"
        @submit="submit"
    >
        <Field>
            <FieldLabel for="email">Email</FieldLabel>
            <Input
                id="email"
                type="email"
                placeholder="amakwatcha@gmail.com"
                required
                v-model="form.email"
            />
            <FieldError v-if="fieldErrors.email">
                {{ fieldErrors.email.join(', ') }}
            </FieldError>
        </Field>

        <Field>
            <Button type="submit" :disabled="loading">
                <Spinner v-if="loading" />
                {{ loading ? 'Envoi en cours...' : 'Envoyer le lien' }}
            </Button>
        </Field>

        <p class="text-sm text-center">
            <RouterLink :to="{ name: 'login' }" class="hover:underline">
                Retour à la connexion
            </RouterLink>
        </p>
    </AuthWrapper>
</template>
