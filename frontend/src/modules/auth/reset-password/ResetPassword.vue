<script setup lang="ts">
import { ref } from 'vue'
import type { NewPasswordPayload } from '@/types'
import { Field, FieldLabel, Input, Button, FieldError, Spinner } from '@/components'
import { AuthWrapper } from '../components'
import { toast } from 'vue-sonner'
import { useRouter } from 'vue-router'
import { resetPassword } from '@/services'

const router = useRouter()
const props = defineProps<{
    token: string
    email: string
}>()

const form = ref<NewPasswordPayload>({
    token: props.token || '',
    email: props.email || '',
    password: '',
    password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function submit() {
    errorMessage.value = ''
    fieldErrors.value = {}
    loading.value = true

    try {
        await resetPassword(form.value)
        router.push({ name: 'login' })
        toast.success('Connexion réussie.')
    } catch (err: any) {
        if (err.errors) {
            fieldErrors.value = err.errors
        } else {
            errorMessage.value = err.message || 'Impossible de se connecter'
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
        title="Connectez-vous"
        description="Entrez vos informations pour vous connecter"
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
            <FieldLabel for="password">Mot de passe</FieldLabel>

            <div class="relative">
                <Input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="form.password"
                    placeholder="••••••••"
                    class="pr-10"
                    required
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                    @click="showPassword = !showPassword"
                >
                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                    <Eye v-else class="w-4 h-4" />
                </button>
            </div>

            <FieldError v-if="fieldErrors.password">
                {{ fieldErrors.password.join(', ') }}
            </FieldError>
        </Field>

        <Field>
            <FieldLabel for="password_confirmation">Confirmer le mot de passe</FieldLabel>

            <div class="relative">
                <Input
                    id="password"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    v-model="form.password_confirmation"
                    placeholder="••••••••"
                    class="pr-10"
                    required
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                >
                    <EyeOff v-if="showPasswordConfirmation" class="w-4 h-4" />
                    <Eye v-else class="w-4 h-4" />
                </button>
            </div>

            <FieldError v-if="fieldErrors.password_confirmation">
                {{ fieldErrors.password_confirmation.join(', ') }}
            </FieldError>
        </Field>

        <Field>
            <Button type="submit" :disabled="loading">
                <Spinner v-if="loading" />
                {{ loading ? 'Connexion en cours...' : 'Connexion' }}
            </Button>
        </Field>
    </AuthWrapper>
</template>
