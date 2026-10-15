export interface LoginPayload {
    email: string
    password: string
}

export interface ResetPayload {
    email: string
}

export interface NewPasswordPayload {
    token: string
    email: string
    password: string
    password_confirmation: string
}
