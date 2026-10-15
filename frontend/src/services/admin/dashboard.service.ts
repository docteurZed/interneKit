import { api } from '@/services'

export interface StatCount {
    name: string
    count: number
}

export interface DashboardData {
    kpis: {
        internes: number
        internes_affectes: number
        internes_sans_affectation: number
        services: number
        gardes_mois: number
        gardes_a_venir: number
        periode_en_cours: { id: number; name: string; start_date: string; end_date: string } | null
    }
    gardes_par_jour: { date: string; count: number }[]
    gardes_par_mois: { month: string; count: number }[]
    gardes_par_type: StatCount[]
    prochaines_gardes: {
        id: number
        date_garde: string
        start: string
        end: string
        type: string | null
        service: string | null
        hopital: string | null
        participants: number
    }[]
}

export const DashboardService = {
    async get(): Promise<DashboardData> {
        const response = await api.get<{ data: DashboardData }>('/dashboard')
        return (response as unknown as { data: DashboardData }).data
    },
}
