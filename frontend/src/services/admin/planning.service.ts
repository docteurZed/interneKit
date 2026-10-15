import { api } from '@/services'

export interface OptionItem {
    id: number
    name: string
}

export interface ServiceOption extends OptionItem {
    hopital?: string | null
}

export interface GardeRecord {
    id: number
    date_garde: string
    start: string
    end: string
    type_garde: OptionItem | null
    service: ServiceOption | null
    participants: OptionItem[]
}

export interface AffectationRecord {
    id: number
    interne: OptionItem | null
    service: ServiceOption | null
    periode_rotation: (OptionItem & { start_date: string; end_date: string }) | null
}

export interface HopitalRecord extends OptionItem {
    services_count: number
}

export interface ServiceRecord extends OptionItem {
    hopital_id: number | null
    hopital: OptionItem | null
}

export interface PeriodeRecord extends OptionItem {
    start_date: string
    end_date: string
}

export interface TypeGardeRecord extends OptionItem {
    start_time: string
    end_time: string
    decalage_end: number
}

export interface JourFerieRecord {
    id: number
    date: string
    name: string
}

type ResourceResponse<T> = { data: T }
type CollectionResponse<T> = { data: T[] }

async function list<T>(path: string): Promise<T[]> {
    const response = await api.get<CollectionResponse<T>>(path)
    return (response as unknown as CollectionResponse<T>).data
}

async function create<T>(path: string, payload: unknown): Promise<T> {
    const response = await api.post<ResourceResponse<T>>(path, payload)
    return (response as unknown as ResourceResponse<T>).data
}

async function update<T>(path: string, id: number, payload: unknown): Promise<T> {
    const response = await api.put<ResourceResponse<T>>(`${path}/${id}`, payload)
    return (response as unknown as ResourceResponse<T>).data
}

async function remove(path: string, id: number): Promise<void> {
    await api.delete(`${path}/${id}`)
}

export const PlanningService = {
    getGardes: () => list<GardeRecord>('/gardes'),
    createGarde: (payload: unknown) => create<GardeRecord>('/gardes', payload),
    updateGarde: (id: number, payload: unknown) => update<GardeRecord>('/gardes', id, payload),
    deleteGarde: (id: number) => remove('/gardes', id),

    getAffectations: () => list<AffectationRecord>('/affectations'),
    createAffectation: (payload: unknown) => create<AffectationRecord>('/affectations', payload),
    updateAffectation: (id: number, payload: unknown) =>
        update<AffectationRecord>('/affectations', id, payload),
    deleteAffectation: (id: number) => remove('/affectations', id),

    getServices: () => list<ServiceRecord>('/services'),
    createService: (payload: unknown) => create<ServiceRecord>('/services', payload),
    updateService: (id: number, payload: unknown) =>
        update<ServiceRecord>('/services', id, payload),
    deleteService: (id: number) => remove('/services', id),

    getHopitaux: () => list<HopitalRecord>('/hopitaux'),
    createHopital: (payload: unknown) => create<HopitalRecord>('/hopitaux', payload),
    updateHopital: (id: number, payload: unknown) =>
        update<HopitalRecord>('/hopitaux', id, payload),
    deleteHopital: (id: number) => remove('/hopitaux', id),

    getPeriodes: () => list<PeriodeRecord>('/periodes-rotation'),
    createPeriode: (payload: unknown) => create<PeriodeRecord>('/periodes-rotation', payload),
    updatePeriode: (id: number, payload: unknown) =>
        update<PeriodeRecord>('/periodes-rotation', id, payload),
    deletePeriode: (id: number) => remove('/periodes-rotation', id),

    getTypesGarde: () => list<TypeGardeRecord>('/types-garde'),
    createTypeGarde: (payload: unknown) => create<TypeGardeRecord>('/types-garde', payload),
    updateTypeGarde: (id: number, payload: unknown) =>
        update<TypeGardeRecord>('/types-garde', id, payload),
    deleteTypeGarde: (id: number) => remove('/types-garde', id),

    getJoursFeries: () => list<JourFerieRecord>('/jours-feries'),
    createJourFerie: (payload: unknown) => create<JourFerieRecord>('/jours-feries', payload),
    updateJourFerie: (id: number, payload: unknown) =>
        update<JourFerieRecord>('/jours-feries', id, payload),
    deleteJourFerie: (id: number) => remove('/jours-feries', id),
}
