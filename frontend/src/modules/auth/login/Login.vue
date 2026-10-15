<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores'
import type { LoginPayload } from '@/types'
import { Field, FieldLabel, Input, Button, FieldError, Spinner } from '@/components'
import { AuthWrapper } from '../components'
import { toast } from 'vue-sonner'
import { useRouter } from 'vue-router'
import { Eye, EyeOff } from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()

const showPassword = ref(false)

const form = ref<LoginPayload>({
    email: '',
    password: '',
})

const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function submit() {
    errorMessage.value = ''
    fieldErrors.value = {}
    loading.value = true

    try {
        await authStore.login(form.value)
        router.push({ name: 'admin.dashboard' })
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
            <div class="flex items-center">
                <FieldLabel for="password">Mot de passe</FieldLabel>
                <RouterLink
                    :to="{ name: 'forgot-password' }"
                    class="ml-auto text-sm underline-offset-4 hover:underline"
                >
                    Mot de passe oublié?
                </RouterLink>
            </div>
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
            <!-- <Input id="password" type="password" required v-model="form.password" /> -->
            <FieldError v-if="fieldErrors.password">
                {{ fieldErrors.password.join(', ') }}
            </FieldError>
        </Field>

        <Field>
            <Button :disabled="loading" type="submit">
                <Spinner v-if="loading" />
                {{ loading ? 'Connexion en cours...' : 'Connexion' }}
            </Button>
        </Field>
    </AuthWrapper>
</template>
