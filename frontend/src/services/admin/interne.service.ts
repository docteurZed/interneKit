import { api } from '@/services'
import type { Interne, InterneDto } from '@/types'

const RESOURCE = '/internes'

type ResourceResponse<T> = { data: T }
type CollectionResponse<T> = { data: T[] }

export const InterneService = {
    async getAll(): Promise<Interne[]> {
        const response = await api.get<CollectionResponse<Interne>>(RESOURCE)
        return (response as unknown as CollectionResponse<Interne>).data
    },

    async getById(id: number): Promise<Interne> {
        const response = await api.get<ResourceResponse<Interne>>(`${RESOURCE}/${id}`)
        return (response as unknown as ResourceResponse<Interne>).data
    },

    async create(payload: InterneDto): Promise<Interne> {
        const response = await api.post<ResourceResponse<Interne>>(RESOURCE, payload)
        return (response as unknown as ResourceResponse<Interne>).data
    },

    async update(id: number, payload: Partial<InterneDto>): Promise<Interne> {
        const response = await api.put<ResourceResponse<Interne>>(`${RESOURCE}/${id}`, payload)
        return (response as unknown as ResourceResponse<Interne>).data
    },

    async delete(id: number): Promise<void> {
        await api.delete(`${RESOURCE}/${id}`)
    },
}
