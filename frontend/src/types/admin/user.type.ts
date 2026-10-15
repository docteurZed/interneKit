export type Role = 'admin' | 'user'

export interface User {
    id: number
    name: string
    initials: string
    email: string
    phone: string | null
    role: Role
    is_active: boolean
}

export interface UserDto {
    name: string
    email: string
    phone: string | null
    role: Role
    is_active: boolean
}

export interface PasswordDto {
    current_password: string
    password: string
    password_confirmation: string
}
