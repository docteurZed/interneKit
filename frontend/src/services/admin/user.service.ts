import { api } from '@/services'
import type { User, UserDto } from '@/types'

const RESOURCE = '/users'

type ResourceResponse<T> = { data: T }
type CollectionResponse<T> = { data: T[] }

export const UserService = {
    async getAll(): Promise<User[]> {
        const response = await api.get<CollectionResponse<User>>(RESOURCE)
        return (response as unknown as CollectionResponse<User>).data
    },

    async getById(id: number): Promise<User> {
        const response = await api.get<ResourceResponse<User>>(`${RESOURCE}/${id}`)
        return (response as unknown as ResourceResponse<User>).data
    },

    async create(payload: UserDto): Promise<User> {
        const response = await api.post<ResourceResponse<User>>(RESOURCE, payload)
        return (response as unknown as ResourceResponse<User>).data
    },

    async update(id: number, payload: Partial<UserDto>): Promise<User> {
        const response = await api.put<ResourceResponse<User>>(`${RESOURCE}/${id}`, payload)
        return (response as unknown as ResourceResponse<User>).data
    },

    async delete(id: number): Promise<void> {
        await api.delete(`${RESOURCE}/${id}`)
    },
}
