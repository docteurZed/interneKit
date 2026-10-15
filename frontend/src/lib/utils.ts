import type { ClassValue } from 'clsx'
import { clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function formatDate(date: string | Date, withTime: boolean = false): string {
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime && {
            hour: '2-digit',
            minute: '2-digit',
        }),
    }

    return new Intl.DateTimeFormat('fr-FR', options).format(new Date(date))
}

export function ucfirst(str: string): string {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
}
