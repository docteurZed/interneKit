<script setup lang="ts">
import { computed, ref } from 'vue'
import {
    Button,
    Input,
    Badge,
    Card,
    Avatar,
    AvatarFallback,
    CardContent,
    CardTitle,
    CardDescription,
    FieldGroup,
    Field,
    FieldLabel,
    CardHeader,
    FieldError,
    CardFooter,
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components'
import { AdminWrapper } from '../components'
import { User, Mail, Phone, Lock, Eye, EyeOff, ShieldCheck, Save, KeyRound } from 'lucide-vue-next'
import { useAuthStore } from '@/stores'
import { useProfile } from '@/composables'
import { toast } from 'vue-sonner'

const { user } = useAuthStore()

const { validationErrors, updateInfos, updatePassword } = useProfile()

const infosForm = ref({
    name: user?.name ?? '',
    email: user?.email ?? '',
    phone: user?.phone ?? '',
})

const pwForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const showCurrent = ref(false)
const showPassword = ref(false)
const showConfirm = ref(false)

const savingInfos = ref(false)
const savingPassword = ref(false)

const isValid = computed(() => {
    return !!infosForm.value.name?.trim() && !!infosForm.value.email?.trim()
})

async function saveInfos() {
    const userId = user?.id
    if (typeof userId !== 'number') {
        toast.error('Votre session a expiré. Reconnectez-vous.')
        return
    }

    savingInfos.value = true

    try {
        await updateInfos(userId, infosForm.value)
        toast.success('Informations modifiées avec succès.')
    } catch (error) {
        toast.error('Une erreur est survenue.')
    } finally {
        savingInfos.value = false
    }
}

const isPwValid = computed(() => {
    return (
        !!pwForm.value.current_password &&
        !!pwForm.value.password &&
        !!pwForm.value.password_confirmation
    )
})

async function savePassword() {
    savingPassword.value = true

    try {
        await updatePassword(pwForm.value)

        pwForm.value.current_password = ''
        pwForm.value.password = ''
        pwForm.value.password_confirmation = ''

        toast.success('Mot de passe modifié avec succès.')
    } catch (error) {
        toast.error('Une erreur est survenue.')
    } finally {
        savingPassword.value = false
    }
}
</script>

<template>
    <AdminWrapper title="Mon profil">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="relative rounded-xl overflow-hidden border bg-card">
                <div class="h-28 bg-gradient-to-r from-primary/20 via-primary/10 to-transparent" />

                <div class="px-6 pb-6 -mt-10 flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="relative shrink-0">
                        <Avatar class="w-20 h-20">
                            <AvatarFallback class="bg-muted text-primary font-bold text-2xl">
                                {{ user?.initials }}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-3xl md:text-4xl leading-tight truncate">
                            {{ user?.name }}
                        </p>
                        <p class="text-sm text-muted-foreground truncate">{{ user?.email }}</p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <Badge
                            :variant="user?.role === 'admin' ? 'destructive' : 'secondary'"
                            class="gap-1"
                        >
                            <ShieldCheck class="w-3 h-3" />
                            {{ user?.role === 'admin' ? 'Administrateur' : 'Utilisateur' }}
                        </Badge>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="
                                user?.is_active
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
                            "
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full"
                                :class="user?.is_active ? 'bg-emerald-500' : 'bg-zinc-400'"
                            />
                            {{ user?.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
            </div>

            <Tabs default-value="infos" class="space-y-4">
                <TabsList class="grid h-11 w-full max-w-sm grid-cols-2">
                    <TabsTrigger value="infos" class="gap-2">
                        <User class="size-4" />
                        Informations
                    </TabsTrigger>
                    <TabsTrigger value="password" class="gap-2">
                        <KeyRound class="size-4" />
                        Mot de passe
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="infos">
                    <form @submit.prevent="saveInfos">
                        <Card>
                            <CardHeader class="flex gap-4 px-4 pb-4 border-b border-border">
                                <div
                                    class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                                >
                                    <User class="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="mb-1">Informations personnelles</CardTitle>
                                    <CardDescription
                                        >Modifiez vos informations de base</CardDescription
                                    >
                                </div>
                            </CardHeader>

                            <CardContent>
                                <FieldGroup class="grid md:grid-cols-2 gap-4">
                                    <Field>
                                        <FieldLabel>
                                            <User class="w-3.5 h-3.5" />
                                            Nom complet
                                        </FieldLabel>

                                        <Input v-model="infosForm.name" placeholder="John Doe" />

                                        <FieldError :v-if="validationErrors.name?.[0]">
                                            {{ validationErrors.name?.[0] }}
                                        </FieldError>
                                    </Field>

                                    <Field>
                                        <FieldLabel>
                                            <Mail class="w-3.5 h-3.5" /> Email
                                        </FieldLabel>

                                        <Input
                                            type="email"
                                            v-model="infosForm.email"
                                            placeholder="johndoe@mail.com"
                                        />

                                        <FieldError :v-if="validationErrors.email?.[0]">
                                            {{ validationErrors.email?.[0] }}
                                        </FieldError>
                                    </Field>

                                    <Field>
                                        <FieldLabel>
                                            <Phone class="w-3.5 h-3.5" /> Téléphone
                                        </FieldLabel>

                                        <Input
                                            v-model="infosForm.phone"
                                            placeholder="+228 XX XX XX XX"
                                        />

                                        <FieldError :v-if="validationErrors.phone?.[0]">
                                            {{ validationErrors.phone?.[0] }}
                                        </FieldError>
                                    </Field>
                                </FieldGroup>
                            </CardContent>

                            <CardFooter
                                class="px-4 pt-4 flex justify-end gap-4 border-t border-border"
                            >
                                <Button type="submit" :disabled="savingInfos || !isValid">
                                    <Save class="w-4 h-4" />
                                    {{ savingInfos ? 'Enrégistrement...' : 'Enregistrer' }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </TabsContent>

                <TabsContent value="password">
                    <form @submit.prevent="savePassword">
                        <Card>
                            <CardHeader class="flex gap-4 px-4 pb-4 border-b border-border">
                                <div
                                    class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                                >
                                    <KeyRound class="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="mb-1">Mot de passe</CardTitle>
                                    <CardDescription
                                        >Changez votre mot de passe régulièrement</CardDescription
                                    >
                                </div>
                            </CardHeader>

                            <CardContent>
                                <FieldGroup class="grid md:grid-cols-2 gap-4">
                                    <Field class="md:col-span-2">
                                        <FieldLabel> Mot de passe actuel </FieldLabel>

                                        <div class="relative sm:max-w-xs">
                                            <Input
                                                :type="showCurrent ? 'text' : 'password'"
                                                v-model="pwForm.current_password"
                                                placeholder="••••••••"
                                                class="pr-10"
                                            />
                                            <button
                                                type="button"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                                @click="showCurrent = !showCurrent"
                                            >
                                                <EyeOff v-if="showCurrent" class="w-4 h-4" />
                                                <Eye v-else class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <FieldError :v-if="validationErrors.current_password?.[0]">
                                            {{ validationErrors.current_password?.[0] }}
                                        </FieldError>
                                    </Field>

                                    <Field>
                                        <FieldLabel> Nouveau mot de passe </FieldLabel>

                                        <div class="relative sm:max-w-xs">
                                            <Input
                                                :type="showPassword ? 'text' : 'password'"
                                                v-model="pwForm.password"
                                                placeholder="••••••••"
                                                class="pr-10"
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

                                        <FieldError :v-if="validationErrors.password?.[0]">
                                            {{ validationErrors.password?.[0] }}
                                        </FieldError>
                                    </Field>

                                    <Field>
                                        <FieldLabel> Confirmer le mot de passe </FieldLabel>

                                        <div class="relative sm:max-w-xs">
                                            <Input
                                                :type="showConfirm ? 'text' : 'password'"
                                                v-model="pwForm.password_confirmation"
                                                placeholder="••••••••"
                                                class="pr-10"
                                            />
                                            <button
                                                type="button"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                                @click="showConfirm = !showConfirm"
                                            >
                                                <EyeOff v-if="showConfirm" class="w-4 h-4" />
                                                <Eye v-else class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <FieldError
                                            :v-if="validationErrors.password_confirmation?.[0]"
                                        >
                                            {{ validationErrors.password_confirmation?.[0] }}
                                        </FieldError>
                                    </Field>
                                </FieldGroup>
                            </CardContent>

                            <CardFooter
                                class="px-4 pt-4 flex justify-end gap-4 border-t border-border"
                            >
                                <Button type="submit" :disabled="savingPassword || !isPwValid">
                                    <Lock class="w-4 h-4" />
                                    {{
                                        savingPassword ? 'Changement...' : 'Changez le mot de passe'
                                    }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </TabsContent>
            </Tabs>
        </div>
    </AdminWrapper>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.25s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
