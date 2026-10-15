import { api } from '@/services'

export interface PublicGarde {
    id: number
    date_garde: string
    start: string
    end: string
    type: string | null
    service: string | null
    hopital: string | null
    internes: { id: number; name: string; phone: string | null }[]
}

export interface PublicGardesResponse {
    data: PublicGarde[]
    meta: {
        today: string
        start_date: string
        end_date: string
    }
}

export const PublicGardeService = {
    async get(): Promise<PublicGardesResponse> {
        const response = await api.get<PublicGardesResponse>('/public/gardes')
        return response as unknown as PublicGardesResponse
    },
}
