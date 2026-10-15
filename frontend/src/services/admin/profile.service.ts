import { api } from '@/services'
import type { User, PasswordDto, UserDto } from '@/types'

const RESOURCE = '/profile'

export const ProfileService = {
    async updateInfos(id: number, payload: Partial<UserDto>): Promise<User> {
        const { data } = await api.put<User>(`${RESOURCE}/${id}/infos`, payload)
        return data
    },

    async updatePassword(payload: Partial<PasswordDto>): Promise<User> {
        const { data } = await api.put<User>(`${RESOURCE}/password`, payload)
        return data
    },
}
