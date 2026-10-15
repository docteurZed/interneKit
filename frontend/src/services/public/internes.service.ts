import { api } from '@/services'

export interface PublicInterne {
    id: number
    name: string
    initials: string
    phone: string | null
    service: string | null
    hopital: string | null
}

export interface PublicInternesResponse {
    data: PublicInterne[]
    meta: {
        date: string
        current_period: { id: number; name: string; start_date: string; end_date: string } | null
    }
}

export const PublicInterneService = {
    async get(): Promise<PublicInternesResponse> {
        const response = await api.get<PublicInternesResponse>('/public/internes')
        return response as unknown as PublicInternesResponse
    },
}
