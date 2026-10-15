import { api, uploadApi } from '@/services'

export interface ExcelImportCount {
    created: number
    reused: number
}

export type ExcelImportSummary = Record<string, ExcelImportCount>

type ResourceResponse<T> = { data: T }

export const ExcelImportService = {
    async downloadTemplate(): Promise<Blob> {
        const response = await api.get<Blob>('/imports/excel/template', { responseType: 'blob' })
        return response as unknown as Blob
    },

    async import(file: File): Promise<ExcelImportSummary> {
        const formData = new FormData()
        formData.append('file', file)

        const response = await uploadApi.post<ResourceResponse<ExcelImportSummary>>(
            '/imports/excel',
            formData,
        )
        return (response as unknown as ResourceResponse<ExcelImportSummary>).data
    },
}
