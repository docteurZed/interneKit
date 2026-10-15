import { api, http } from '@/services'
import type { LoginPayload, NewPasswordPayload, ResetPayload, User } from '@/types'

export function login(payload: LoginPayload) {
    return http.post<void>('/login', payload)
}

export function logout() {
    return http.post<void>('/logout')
}

export function sendResetLink(payload: ResetPayload) {
    return http.post<void>('/forgot-password', payload)
}

export function resetPassword(payload: NewPasswordPayload) {
    return http.post<void>('/reset-password', payload)
}

export function fetchUser() {
    return api.get<User>('/user')
}
