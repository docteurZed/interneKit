import { fetchUser, getCookies, login as userLogin, logout as userLogout } from '@/services'
import type { AuthState, LoginPayload } from '@/types'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        loadingUser: true,
    }),

    actions: {
        async loadUser() {
            try {
                const res = await fetchUser()
                this.user = res.data
            } catch {
                this.user = null
            } finally {
                this.loadingUser = false
            }
        },

        async login(credentials: LoginPayload) {
            await getCookies()
            await userLogin(credentials)
            await this.loadUser()
        },

        async logout() {
            await userLogout()
            this.user = null
        },

        isLogged(): boolean {
            return !!this.user
        },
    },
})
