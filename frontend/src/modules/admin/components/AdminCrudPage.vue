<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
    CalendarDays,
    Check,
    Clock3,
    Download,
    FileSpreadsheet,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash2,
    UsersRound,
    Upload,
    X,
} from 'lucide-vue-next'
import {
    Button,
    CustomSelect,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    Input,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Switch,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components'
import { matchesSearch } from '@/lib/search'
import { InterneService } from '@/services/admin/interne.service'
import { UserService } from '@/services/admin/user.service'
import { ExcelImportService, type ExcelImportSummary } from '@/services/admin/excel-import.service'
import {
    PlanningService,
    type HopitalRecord,
    type PeriodeRecord,
    type ServiceRecord,
    type TypeGardeRecord,
} from '@/services/admin/planning.service'
import { AdminWrapper } from './index'
import type { Interne, UserDto, InterneDto } from '@/types'
import { toast } from 'vue-sonner'

type RouteResource = 'users' | 'internes' | 'gardes' | 'affectations' | 'referentiels'
type ResourceKey =
    | 'users'
    | 'internes'
    | 'gardes'
    | 'affectations'
    | 'hopitaux'
    | 'services'
    | 'periodes'
    | 'types'
    | 'feries'
type FieldKind = 'text' | 'email' | 'tel' | 'date' | 'time' | 'select' | 'multiselect'

type SelectOption = { value: string; label: string }
type FieldDefinition = {
    key: string
    label: string
    kind: FieldKind
    required?: boolean
    placeholder?: string
    options?: SelectOption[]
}
type CrudRow = Record<string, any> & { id: number }
type ColumnDefinition = { key: string; label: string; value: (row: CrudRow) => string }
type ResourceDefinition = {
    title: string
    description: string
    singular: string
    fields: FieldDefinition[]
    columns: ColumnDefinition[]
}

const props = defineProps<{ resource: RouteResource }>()
const referenceTabs: { key: ResourceKey; label: string }[] = [
    { key: 'hopitaux', label: 'Hôpitaux' },
    { key: 'services', label: 'Services' },
    { key: 'periodes', label: 'Périodes' },
    { key: 'types', label: 'Types de garde' },
    { key: 'feries', label: 'Jours fériés' },
]
const activeReference = ref<ResourceKey>('hopitaux')
const resourceKey = computed<ResourceKey>(() =>
    props.resource === 'referentiels' ? activeReference.value : props.resource,
)

const rows = ref<CrudRow[]>([])
const internes = ref<Interne[]>([])
const hopitaux = ref<HopitalRecord[]>([])
const services = ref<ServiceRecord[]>([])
const periodes = ref<PeriodeRecord[]>([])
const typesGarde = ref<TypeGardeRecord[]>([])
const saving = ref(false)
const loading = ref(false)
const formOpen = ref(false)
const editingId = ref<number | null>(null)
const query = ref('')
const form = ref<Record<string, unknown>>({})
const participantSearch = ref('')
const deleteTarget = ref<CrudRow | null>(null)
const deleteDialogOpen = ref(false)
const deleting = ref(false)
const participantsTarget = ref<CrudRow | null>(null)
const participantsDialogOpen = ref(false)
const excelDialogOpen = ref(false)
const excelFileInput = ref<HTMLInputElement | null>(null)
const excelFile = ref<File | null>(null)
const excelImporting = ref(false)
const excelDownloading = ref(false)
const excelSummary = ref<ExcelImportSummary | null>(null)
const excelErrors = ref<string[]>([])

const excelSheets = [
    { name: 'Utilisateurs', columns: 'ref, nom, email, telephone, role, actif, mot_de_passe' },
    { name: 'Hopitaux', columns: 'ref, nom' },
    { name: 'Services', columns: 'ref, hopital_ref, nom' },
    { name: 'Internes', columns: 'ref, nom, telephone' },
    { name: 'Rotations', columns: 'ref, nom, debut, fin' },
    { name: 'TypesGarde', columns: 'ref, nom, heure_debut, heure_fin, fin_jour_suivant' },
    { name: 'JoursFeries', columns: 'date, nom' },
    { name: 'Affectations', columns: 'interne_ref, service_ref, rotation_ref' },
    { name: 'Gardes', columns: 'date_garde, type_garde_ref, service_ref, internes_refs' },
]

const excelSummaryRows = computed(() =>
    Object.entries(excelSummary.value ?? {}).map(([sheet, counts]) => ({ sheet, ...counts })),
)

function optionItems(items: { id: number; name: string }[] | null | undefined): SelectOption[] {
    return (items ?? []).map((item) => ({ value: String(item.id), label: item.name }))
}

const currentPeriod = computed(() => {
    const today = new Date().toISOString().slice(0, 10)
    return (
        periodes.value.find(
            (periode) =>
                periode.start_date.slice(0, 10) <= today && periode.end_date.slice(0, 10) >= today,
        ) ?? null
    )
})

const definition = computed<ResourceDefinition>(() => {
    const interneOptions = optionItems(internes.value)
    const selectedPeriodId = Number(form.value.periode_rotation_id || currentPeriod.value?.id)
    const assignedInterneIds = new Set(
        rows.value
            .filter(
                (row) =>
                    row.periode_rotation?.id === selectedPeriodId && row.id !== editingId.value,
            )
            .map((row) => row.interne?.id),
    )
    const availableInterneOptions = interneOptions.filter(
        (option) => !assignedInterneIds.has(Number(option.value)),
    )
    const serviceOptions = services.value.map((service) => ({
        value: String(service.id),
        label: `${service.hopital ? `(${service.hopital.name}) ` : '(Hôpital à associer) '}${service.name}`,
    }))
    const hopitalOptions = optionItems(hopitaux.value)
    const periodeOptions = periodes.value.map((item) => ({
        value: String(item.id),
        label: `${item.name} (${dateLabel(item.start_date)} – ${dateLabel(item.end_date)})`,
    }))
    const typeOptions = optionItems(typesGarde.value)
    const columns = (defs: [string, string, (row: CrudRow) => string][]): ColumnDefinition[] =>
        defs.map(([key, label, value]) => ({ key, label, value }))

    switch (resourceKey.value) {
        case 'users':
            return {
                title: 'Administrateurs',
                singular: 'utilisateur',
                description: 'Gérez les comptes et leurs niveaux d’accès.',
                fields: [
                    { key: 'name', label: 'Nom complet', kind: 'text', required: true },
                    { key: 'email', label: 'Adresse e-mail', kind: 'email', required: true },
                    { key: 'phone', label: 'Téléphone', kind: 'tel' },
                    {
                        key: 'role',
                        label: 'Rôle',
                        kind: 'select',
                        required: true,
                        options: [
                            { value: 'user', label: 'Utilisateur' },
                            { value: 'admin', label: 'Administrateur' },
                        ],
                    },
                    {
                        key: 'is_active',
                        label: 'Compte',
                        kind: 'select',
                        required: true,
                        options: [
                            { value: 'true', label: 'Actif' },
                            { value: 'false', label: 'Inactif' },
                        ],
                    },
                ],
                columns: columns([
                    ['name', 'Nom', (row) => row.name],
                    ['email', 'E-mail', (row) => row.email],
                    ['phone', 'Téléphone', (row) => row.phone || '—'],
                    [
                        'role',
                        'Rôle',
                        (row) => (row.role === 'admin' ? 'Administrateur' : 'Utilisateur'),
                    ],
                    ['is_active', 'Statut', (row) => (row.is_active ? 'Actif' : 'Inactif')],
                ]),
            }
        case 'internes':
            return {
                title: 'Internes',
                singular: 'interne',
                description: 'Gérez les internes et leurs coordonnées.',
                fields: [
                    { key: 'name', label: 'Nom complet', kind: 'text', required: true },
                    { key: 'phone', label: 'Téléphone', kind: 'tel' },
                ],
                columns: columns([
                    ['name', 'Nom', (row) => row.name],
                    ['phone', 'Téléphone', (row) => row.phone || '—'],
                ]),
            }
        case 'gardes':
            return {
                title: 'Gardes',
                singular: 'garde',
                description:
                    'Planifiez les gardes. Les horaires sont calculés à partir du type choisi.',
                fields: [
                    { key: 'date_garde', label: 'Date', kind: 'date', required: true },
                    {
                        key: 'type_garde_id',
                        label: 'Type de garde',
                        kind: 'select',
                        required: true,
                        options: typeOptions,
                    },
                    {
                        key: 'service_id',
                        label: 'Service',
                        kind: 'select',
                        required: true,
                        options: serviceOptions,
                    },
                    {
                        key: 'interne_ids',
                        label: 'Internes participants',
                        kind: 'multiselect',
                        required: true,
                        options: interneOptions,
                    },
                ],
                columns: columns([
                    [
                        'date',
                        'Date et horaires',
                        (row) =>
                            `${dateLabel(row.date_garde)} · ${timeLabel(row.start)}–${timeLabel(row.end)}`,
                    ],
                    [
                        'type',
                        'Garde',
                        (row) => `${row.type_garde?.name ?? '—'} · ${serviceLabel(row.service)}`,
                    ],
                    [
                        'participants',
                        'Participants',
                        (row) => {
                            const count = row.participants?.length ?? 0
                            return `${count} interne${count === 1 ? '' : 's'}`
                        },
                    ],
                ]),
            }
        case 'affectations':
            return {
                title: 'Affectations de rotation',
                singular: 'affectation',
                description: 'Affectez chaque interne à un service pour une période commune.',
                fields: [
                    {
                        key: 'interne_id',
                        label: 'Interne',
                        kind: 'select',
                        required: true,
                        options: availableInterneOptions,
                    },
                    {
                        key: 'service_id',
                        label: 'Service',
                        kind: 'select',
                        required: true,
                        options: serviceOptions,
                    },
                    {
                        key: 'periode_rotation_id',
                        label: 'Période',
                        kind: 'select',
                        required: true,
                        options: periodeOptions,
                    },
                ],
                columns: columns([
                    ['interne', 'Interne', (row) => row.interne?.name ?? '—'],
                    ['service', 'Service', (row) => serviceLabel(row.service)],
                    ['periode', 'Période', (row) => row.periode_rotation?.name ?? '—'],
                    [
                        'dates',
                        'Dates',
                        (row) =>
                            row.periode_rotation
                                ? `${dateLabel(row.periode_rotation.start_date)} – ${dateLabel(row.periode_rotation.end_date)}`
                                : '—',
                    ],
                ]),
            }
        case 'services':
            return {
                title: 'Services',
                singular: 'service',
                description:
                    'Chaque service appartient à un hôpital et peut être utilisé pour les rotations et les gardes.',
                fields: [
                    {
                        key: 'name',
                        label: 'Nom du service',
                        kind: 'text',
                        required: true,
                        placeholder: 'Ex. Cardiologie',
                    },
                    {
                        key: 'hopital_id',
                        label: 'Hôpital',
                        kind: 'select',
                        required: true,
                        options: hopitalOptions,
                    },
                ],
                columns: columns([
                    ['name', 'Service', (row) => row.name],
                    ['hopital', 'Hôpital', (row) => row.hopital?.name ?? 'À associer'],
                ]),
            }
        case 'hopitaux':
            return {
                title: 'Hôpitaux',
                singular: 'hôpital',
                description: 'Gérez les établissements auxquels les services sont rattachés.',
                fields: [
                    {
                        key: 'name',
                        label: 'Nom de l’hôpital',
                        kind: 'text',
                        required: true,
                        placeholder: 'Ex. CHU Campus',
                    },
                ],
                columns: columns([
                    ['name', 'Hôpital', (row) => row.name],
                    ['services_count', 'Services', (row) => String(row.services_count ?? 0)],
                ]),
            }
        case 'periodes':
            return {
                title: 'Périodes de rotation',
                singular: 'période',
                description: 'Définissez le calendrier commun des rotations.',
                fields: [
                    { key: 'name', label: 'Nom', kind: 'text', required: true },
                    { key: 'start_date', label: 'Date de début', kind: 'date', required: true },
                    { key: 'end_date', label: 'Date de fin', kind: 'date', required: true },
                ],
                columns: columns([
                    ['name', 'Période', (row) => row.name],
                    [
                        'dates',
                        'Dates',
                        (row) => `${dateLabel(row.start_date)} – ${dateLabel(row.end_date)}`,
                    ],
                ]),
            }
        case 'types':
            return {
                title: 'Types de garde',
                singular: 'type de garde',
                description:
                    'Définissez les horaires par défaut appliqués sans règle de service spécifique.',
                fields: [
                    { key: 'name', label: 'Nom', kind: 'text', required: true },
                    { key: 'start_time', label: 'Heure de début', kind: 'time', required: true },
                    { key: 'end_time', label: 'Heure de fin', kind: 'time', required: true },
                    {
                        key: 'decalage_end',
                        label: 'Fin le lendemain',
                        kind: 'select',
                        required: true,
                        options: [
                            { value: '0', label: 'Non' },
                            { value: '1', label: 'Oui' },
                        ],
                    },
                ],
                columns: columns([
                    ['name', 'Type', (row) => row.name],
                    [
                        'hours',
                        'Horaires par défaut',
                        (row) =>
                            `${timeLabel(row.start_time)}–${timeLabel(row.end_time)}${Number(row.decalage_end) ? ' · lendemain' : ''}`,
                    ],
                ]),
            }
        case 'feries':
            return {
                title: 'Jours fériés',
                singular: 'jour férié',
                description: 'Gérez le référentiel des jours fériés.',
                fields: [
                    { key: 'date', label: 'Date', kind: 'date', required: true },
                    { key: 'name', label: 'Nom', kind: 'text', required: true },
                ],
                columns: columns([
                    ['name', 'Jour férié', (row) => row.name],
                    ['date', 'Date', (row) => dateLabel(row.date)],
                ]),
            }
    }
})

const filteredRows = computed(() => {
    if (!query.value.trim()) return rows.value
    return rows.value.filter((row) =>
        definition.value.columns.some((column) => matchesSearch(column.value(row), query.value)),
    )
})

function dateLabel(value: unknown): string {
    if (typeof value !== 'string' || !value) return '—'
    const date = new Date(`${value.slice(0, 10)}T00:00:00`)
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function timeLabel(value: unknown): string {
    if (typeof value !== 'string' || !value) return '—'

    const time =
        value.includes('T') || value.includes(' ') ? value.slice(11, 16) : value.slice(0, 5)

    return time ? time.replace(':', 'h') : '—'
}

function serviceLabel(service: CrudRow['service']): string {
    if (!service) return '—'
    return `${service.hopital ? `(${service.hopital}) ` : '(Hôpital à associer) '}${service.name}`
}

function emptyForm(): Record<string, unknown> {
    const initial: Record<string, unknown> = {}
    for (const field of definition.value.fields) {
        if (field.kind === 'multiselect') initial[field.key] = []
        else if (field.key === 'date_garde')
            initial[field.key] = new Date(Date.now() - new Date().getTimezoneOffset() * 60_000)
                .toISOString()
                .slice(0, 10)
        else if (field.key === 'role') initial[field.key] = 'user'
        else if (field.key === 'is_active') initial[field.key] = 'true'
        else if (field.key === 'start_time') initial[field.key] = '08h00'
        else if (field.key === 'end_time') initial[field.key] = '17h00'
        else if (field.key === 'decalage_end') initial[field.key] = '0'
        else if (field.key === 'categorie_day') initial[field.key] = 'semaine'
        else if (resourceKey.value === 'affectations' && field.key === 'periode_rotation_id')
            initial[field.key] = currentPeriod.value ? String(currentPeriod.value.id) : ''
        else if (
            field.kind === 'select' &&
            field.options?.some((option) => option.value === '__none__')
        )
            initial[field.key] = '__none__'
        else initial[field.key] = ''
    }
    return initial
}

function setField(key: string, value: unknown) {
    form.value = {
        ...form.value,
        [key]: value,
        ...(resourceKey.value === 'affectations' && key === 'periode_rotation_id'
            ? { interne_id: '' }
            : {}),
    }
}

function toggleParticipant(field: FieldDefinition, value: string, checked: boolean) {
    const selected = Array.isArray(form.value[field.key]) ? (form.value[field.key] as number[]) : []
    const id = Number(value)
    setField(field.key, checked ? [...selected, id] : selected.filter((item) => item !== id))
}

const visibleParticipantOptions = computed(() => {
    const options =
        definition.value.fields.find((field) => field.key === 'interne_ids')?.options ?? []
    const search = participantSearch.value.trim()

    return search ? options.filter((option) => matchesSearch(option.label, search)) : []
})

const selectedParticipantCount = computed(() =>
    Array.isArray(form.value.interne_ids) ? form.value.interne_ids.length : 0,
)

function openCreate() {
    editingId.value = null
    participantSearch.value = ''
    form.value = emptyForm()
    formOpen.value = true
}

function editRow(row: CrudRow) {
    editingId.value = row.id
    participantSearch.value = ''
    const values = emptyForm()
    for (const field of definition.value.fields) {
        switch (field.key) {
            case 'type_garde_id':
                values[field.key] = String(row.type_garde?.id ?? '')
                break
            case 'service_id':
                values[field.key] = String(row.service?.id ?? '')
                break
            case 'hopital_id':
                values[field.key] = String(row.hopital?.id ?? row.hopital_id ?? '')
                break
            case 'interne_id':
                values[field.key] = String(row.interne?.id ?? '')
                break
            case 'periode_rotation_id':
                values[field.key] = String(row.periode_rotation?.id ?? '')
                break
            case 'interne_ids':
                values[field.key] = (row.participants ?? []).map((item: any) => item.id)
                break
            case 'date_garde':
                values[field.key] = String(row.date_garde ?? '').slice(0, 10)
                break
            case 'start_date':
                values[field.key] = String(row.start_date ?? '').slice(0, 10)
                break
            case 'end_date':
                values[field.key] = String(row.end_date ?? '').slice(0, 10)
                break
            case 'date':
                values[field.key] = String(row.date ?? '').slice(0, 10)
                break
            case 'start_time':
                values[field.key] = timeLabel(row.start_time)
                break
            case 'end_time':
                values[field.key] = timeLabel(row.end_time)
                break
            case 'decalage_end':
                values[field.key] = String(row.decalage_end ?? '0')
                break
            case 'is_active':
                values[field.key] = String(Boolean(row.is_active))
                break
            default:
                values[field.key] = row[field.key] ?? ''
        }
    }
    form.value = values
    formOpen.value = true
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function resetForm() {
    formOpen.value = false
    editingId.value = null
    participantSearch.value = ''
    form.value = emptyForm()
}

function errorMessage(error: unknown, fallback: string): string {
    if (error && typeof error === 'object') {
        if ('errors' in error && error.errors && typeof error.errors === 'object') {
            const validationMessage = Object.values(error.errors as Record<string, unknown>)
                .flatMap((messages) => (Array.isArray(messages) ? messages : [messages]))
                .find((message): message is string => typeof message === 'string')
            if (validationMessage) return validationMessage
        }
        if ('message' in error && typeof error.message === 'string') return error.message
    }
    return fallback
}

function openExcelImport() {
    excelFile.value = null
    excelSummary.value = null
    excelErrors.value = []
    if (excelFileInput.value) excelFileInput.value.value = ''
    excelDialogOpen.value = true
}

function selectExcelFile(event: Event) {
    const input = event.target as HTMLInputElement
    excelFile.value = input.files?.[0] ?? null
    excelSummary.value = null
    excelErrors.value = []
}

async function downloadExcelTemplate() {
    excelDownloading.value = true
    try {
        const blob = await ExcelImportService.downloadTemplate()
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = 'internekit_import.xlsx'
        link.click()
        window.setTimeout(() => URL.revokeObjectURL(url), 1000)
    } catch (error) {
        toast.error(errorMessage(error, 'Impossible de télécharger le modèle Excel.'))
    } finally {
        excelDownloading.value = false
    }
}

function excelErrorMessages(error: unknown): string[] {
    if (
        error &&
        typeof error === 'object' &&
        'errors' in error &&
        error.errors &&
        typeof error.errors === 'object'
    ) {
        const messages = Object.values(error.errors as Record<string, unknown>)
            .flatMap((value) => (Array.isArray(value) ? value : [value]))
            .filter((value): value is string => typeof value === 'string')
        if (messages.length) return messages
    }

    return [errorMessage(error, 'Le classeur n’a pas pu être importé.')]
}

async function importExcel() {
    if (!excelFile.value) {
        excelErrors.value = ['Sélectionnez un fichier Excel avant de lancer l’import.']
        return
    }

    excelImporting.value = true
    excelErrors.value = []
    excelSummary.value = null

    try {
        excelSummary.value = await ExcelImportService.import(excelFile.value)
        excelFile.value = null
        if (excelFileInput.value) excelFileInput.value.value = ''
        toast.success('Import terminé; les données existantes ont été conservées.')
        await loadRows()
    } catch (error) {
        excelErrors.value = excelErrorMessages(error)
        toast.error('Import annulé. Aucune ligne du classeur n’a été conservée.')
    } finally {
        excelImporting.value = false
    }
}

async function loadOptions() {
    const key = resourceKey.value
    const tasks: Promise<void>[] = []
    if (['gardes', 'affectations'].includes(key)) {
        tasks.push(
            InterneService.getAll().then((data) => {
                internes.value = data
            }),
        )
    }
    if (['gardes', 'affectations'].includes(key)) {
        tasks.push(
            PlanningService.getServices().then((data) => {
                services.value = data
            }),
        )
    }
    if (['affectations'].includes(key)) {
        tasks.push(
            PlanningService.getPeriodes().then((data) => {
                periodes.value = data
            }),
        )
    }
    if (key === 'gardes') {
        tasks.push(
            PlanningService.getTypesGarde().then((data) => {
                typesGarde.value = data
            }),
        )
    }
    if (key === 'services') {
        tasks.push(
            PlanningService.getHopitaux().then((data) => {
                hopitaux.value = data
            }),
        )
    }
    await Promise.all(tasks)
}

async function loadRows() {
    loading.value = true
    try {
        await loadOptions()
        let data: any[]
        switch (resourceKey.value) {
            case 'users':
                data = await UserService.getAll()
                break
            case 'internes':
                data = await InterneService.getAll()
                break
            case 'gardes':
                data = await PlanningService.getGardes()
                break
            case 'affectations':
                data = await PlanningService.getAffectations()
                break
            case 'hopitaux':
                data = await PlanningService.getHopitaux()
                break
            case 'services':
                data = await PlanningService.getServices()
                break
            case 'periodes':
                data = await PlanningService.getPeriodes()
                break
            case 'types':
                data = await PlanningService.getTypesGarde()
                break
            case 'feries':
                data = await PlanningService.getJoursFeries()
                break
        }
        rows.value = Array.isArray(data) ? (data as CrudRow[]) : []
    } catch (error) {
        toast.error(errorMessage(error, 'Impossible de charger les données.'))
    } finally {
        loading.value = false
    }
}

onMounted(loadRows)
watch(resourceKey, () => {
    query.value = ''
    resetForm()
    loadRows()
})

function payloadForSave(): Record<string, any> {
    const payload: Record<string, any> = {}
    for (const field of definition.value.fields) {
        const value = form.value[field.key]
        if (field.key === 'phone') payload.phone = value || null
        else if (field.key === 'service_id') payload.service_id = Number(value)
        else if (['type_garde_id', 'interne_id', 'periode_rotation_id'].includes(field.key))
            payload[field.key] = Number(value)
        else if (field.key === 'hopital_id') payload.hopital_id = Number(value)
        else if (field.key === 'decalage_end') payload[field.key] = Number(value)
        else if (['start_time', 'end_time'].includes(field.key))
            payload[field.key] = String(value ?? '')
                .trim()
                .replace('h', ':')
        else if (field.key === 'interne_ids')
            payload[field.key] = Array.isArray(value) ? value.map(Number) : []
        else if (field.key === 'is_active') payload[field.key] = value === true || value === 'true'
        else payload[field.key] = value
    }
    return payload
}

async function save() {
    if (resourceKey.value === 'gardes') {
        if (!form.value.type_garde_id || !form.value.service_id || !form.value.date_garde) {
            toast.error('Renseignez la date, le type de garde et le service.')
            return
        }
        if (!Array.isArray(form.value.interne_ids) || form.value.interne_ids.length === 0) {
            toast.error('Sélectionnez au moins un interne participant.')
            return
        }
    }

    saving.value = true
    const payload = payloadForSave()
    try {
        const id = editingId.value
        switch (resourceKey.value) {
            case 'users':
                if (id) await UserService.update(id, payload as Partial<UserDto>)
                else await UserService.create(payload as UserDto)
                break
            case 'internes':
                if (id) await InterneService.update(id, payload as Partial<InterneDto>)
                else await InterneService.create(payload as InterneDto)
                break
            case 'gardes':
                if (id) await PlanningService.updateGarde(id, payload)
                else await PlanningService.createGarde(payload)
                break
            case 'affectations':
                if (id) await PlanningService.updateAffectation(id, payload)
                else await PlanningService.createAffectation(payload)
                break
            case 'services':
                if (id) await PlanningService.updateService(id, payload)
                else await PlanningService.createService(payload)
                break
            case 'hopitaux':
                if (id) await PlanningService.updateHopital(id, payload)
                else await PlanningService.createHopital(payload)
                break
            case 'periodes':
                if (id) await PlanningService.updatePeriode(id, payload)
                else await PlanningService.createPeriode(payload)
                break
            case 'types':
                if (id) await PlanningService.updateTypeGarde(id, payload)
                else await PlanningService.createTypeGarde(payload)
                break
            case 'feries':
                if (id) await PlanningService.updateJourFerie(id, payload)
                else await PlanningService.createJourFerie(payload)
                break
        }
        toast.success(id ? 'Modifications enregistrées.' : 'Élément créé.')
        resetForm()
        await loadRows()
    } catch (error) {
        toast.error(errorMessage(error, `Impossible d’enregistrer ${definition.value.singular}.`))
    } finally {
        saving.value = false
    }
}

const deleteLabel = computed(() =>
    deleteTarget.value
        ? (definition.value.columns[0]?.value(deleteTarget.value) ?? definition.value.singular)
        : definition.value.singular,
)

function deleteRow(row: CrudRow) {
    deleteTarget.value = row
    deleteDialogOpen.value = true
}

function showParticipants(row: CrudRow) {
    participantsTarget.value = row
    participantsDialogOpen.value = true
}

async function confirmDelete() {
    const row = deleteTarget.value
    if (!row) return

    deleting.value = true
    try {
        switch (resourceKey.value) {
            case 'users':
                await UserService.delete(row.id)
                break
            case 'internes':
                await InterneService.delete(row.id)
                break
            case 'gardes':
                await PlanningService.deleteGarde(row.id)
                break
            case 'affectations':
                await PlanningService.deleteAffectation(row.id)
                break
            case 'hopitaux':
                await PlanningService.deleteHopital(row.id)
                break
            case 'services':
                await PlanningService.deleteService(row.id)
                break
            case 'periodes':
                await PlanningService.deletePeriode(row.id)
                break
            case 'types':
                await PlanningService.deleteTypeGarde(row.id)
                break
            case 'feries':
                await PlanningService.deleteJourFerie(row.id)
                break
        }
        if (editingId.value === row.id) resetForm()
        deleteDialogOpen.value = false
        deleteTarget.value = null
        toast.success(`${definition.value.singular} supprimé.`)
        await loadRows()
    } catch (error) {
        toast.error(
            errorMessage(error, 'Suppression impossible. Cet élément peut être utilisé ailleurs.'),
        )
    } finally {
        deleting.value = false
    }
}
</script>

<template>
    <AdminWrapper :title="definition.title">
        <template #actions>
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="query"
                    class="pl-9"
                    :placeholder="`Rechercher ${definition.singular}…`"
                />
            </div>
            <Button
                variant="outline"
                size="icon"
                title="Actualiser"
                :disabled="loading"
                @click="loadRows"
                ><RefreshCw class="h-4 w-4"
            /></Button>
            <Button class="gap-2" @click="openCreate"><Plus class="h-4 w-4" />Ajouter</Button>
        </template>

        <div
            v-if="props.resource === 'referentiels'"
            class="mb-5 flex gap-1 overflow-x-auto border-b"
            role="tablist"
            aria-label="Référentiels"
        >
            <button
                v-for="tab in referenceTabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="resourceKey === tab.key"
                class="shrink-0 border-b-2 px-3 py-3 text-sm transition-colors"
                :class="
                    resourceKey === tab.key
                        ? 'border-primary font-semibold text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
                @click="activeReference = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
                <CalendarDays class="h-5 w-5 text-primary" />
                <div>
                    <p class="text-xs text-muted-foreground">Total</p>
                    <p class="font-semibold">{{ rows.length }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
                <Search class="h-5 w-5 text-emerald-600" />
                <div>
                    <p class="text-xs text-muted-foreground">Résultats filtrés</p>
                    <p class="font-semibold">{{ filteredRows.length }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
                <Clock3 class="h-5 w-5 text-amber-600" />
                <div>
                    <p class="text-xs text-muted-foreground">Vue</p>
                    <p class="font-semibold">{{ definition.title }}</p>
                </div>
            </div>
        </div>

        <section v-if="formOpen" class="mb-5 rounded-xl border bg-card">
            <header class="flex items-start justify-between gap-4 border-b px-5 py-4">
                <div>
                    <h2 class="font-semibold">
                        {{ editingId ? 'Modifier' : 'Ajouter' }} {{ definition.singular }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ definition.description }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Button type="button" variant="outline" class="gap-2" @click="openExcelImport">
                        <FileSpreadsheet class="h-4 w-4" />Importer Excel
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Fermer le formulaire"
                        @click="resetForm"
                        ><X class="h-4 w-4"
                    /></Button>
                </div>
            </header>
            <form class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3" @submit.prevent="save">
                <template v-for="field in definition.fields" :key="field.key">
                    <fieldset
                        v-if="field.kind === 'multiselect'"
                        class="min-w-0 border px-3 pb-3 pt-2 sm:col-span-2 xl:col-span-3"
                    >
                        <legend class="px-1 text-sm font-medium">
                            {{ field.label }}
                            <span class="text-muted-foreground"
                                >({{ selectedParticipantCount }} sélectionné{{
                                    selectedParticipantCount > 1 ? 's' : ''
                                }})</span
                            >
                        </legend>
                        <div class="relative mb-3">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="participantSearch"
                                type="text"
                                class="pl-9 pr-10"
                                placeholder="Rechercher un interne…"
                            />
                            <Button
                                v-if="participantSearch"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="absolute right-1 top-1/2 h-8 w-8 -translate-y-1/2"
                                aria-label="Effacer la recherche"
                                title="Effacer la recherche"
                                @click="participantSearch = ''"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                        <div
                            class="grid max-h-56 grid-cols-1 gap-x-6 overflow-y-auto sm:grid-cols-2"
                        >
                            <div
                                v-for="option in visibleParticipantOptions"
                                :key="option.value"
                                class="flex min-w-0 items-center justify-between gap-4 border-b py-2 text-sm"
                            >
                                <span class="min-w-0 truncate">{{ option.label }}</span>
                                <Switch
                                    :model-value="
                                        Array.isArray(form[field.key]) &&
                                        (form[field.key] as number[]).includes(Number(option.value))
                                    "
                                    :aria-label="`Inclure ${option.label}`"
                                    @update:model-value="
                                        toggleParticipant(field, option.value, Boolean($event))
                                    "
                                />
                            </div>
                            <p
                                v-if="!participantSearch.trim()"
                                class="py-3 text-sm text-muted-foreground"
                            >
                                Saisissez un nom pour rechercher les internes.
                            </p>
                            <p
                                v-else-if="!visibleParticipantOptions.length"
                                class="py-3 text-sm text-muted-foreground"
                            >
                                Aucun interne trouvé.
                            </p>
                        </div>
                    </fieldset>
                    <label
                        v-else
                        class="grid gap-1.5 text-sm font-medium"
                        :class="
                            field.kind === 'text' &&
                            field.key === 'name' &&
                            resourceKey === 'feries'
                                ? ''
                                : ''
                        "
                    >
                        {{ field.label }}
                        <CustomSelect
                            v-if="
                                field.kind === 'select' &&
                                ((resourceKey === 'affectations' &&
                                    ['interne_id', 'service_id'].includes(field.key)) ||
                                    (resourceKey === 'services' && field.key === 'hopital_id'))
                            "
                            :model-value="String(form[field.key] ?? '')"
                            :options="field.options ?? []"
                            :placeholder="`Choisir ${field.label.toLocaleLowerCase('fr-FR')}`"
                            @update:model-value="setField(field.key, $event)"
                        />
                        <Select
                            v-else-if="field.kind === 'select'"
                            :model-value="String(form[field.key] ?? '')"
                            :required="field.required"
                            @update:model-value="setField(field.key, $event)"
                        >
                            <SelectTrigger :aria-label="field.label"
                                ><SelectValue
                                    :placeholder="
                                        field.placeholder ?? `Choisir ${field.label.toLowerCase()}`
                                    "
                            /></SelectTrigger>
                            <SelectContent
                                ><SelectItem
                                    v-for="option in field.options"
                                    :key="option.value"
                                    :value="option.value"
                                    >{{ option.label }}</SelectItem
                                ></SelectContent
                            >
                        </Select>
                        <Input
                            v-else
                            :type="field.kind === 'time' ? 'text' : field.kind"
                            :inputmode="field.kind === 'time' ? 'numeric' : undefined"
                            :model-value="String(form[field.key] ?? '')"
                            :required="field.required"
                            :placeholder="field.kind === 'time' ? '08h43' : field.placeholder"
                            @update:model-value="setField(field.key, $event)"
                        />
                    </label>
                </template>
                <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-3">
                    <Button type="submit" class="gap-2" :disabled="saving"
                        ><Check class="h-4 w-4" />{{
                            saving
                                ? 'Enregistrement…'
                                : editingId
                                  ? 'Enregistrer'
                                  : `Créer ${definition.singular}`
                        }}</Button
                    >
                    <Button type="button" variant="outline" @click="resetForm">Annuler</Button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border bg-card">
            <header class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <h2 class="font-semibold">{{ definition.title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ definition.description }}</p>
                </div>
                <span class="text-sm text-muted-foreground">{{ filteredRows.length }}</span>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th
                                v-for="column in definition.columns"
                                :key="column.key"
                                class="px-4 py-3"
                            >
                                {{ column.label }}
                            </th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td
                                :colspan="definition.columns.length + 1"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Chargement des données…
                            </td>
                        </tr>
                        <tr
                            v-for="row in filteredRows"
                            :key="row.id"
                            class="border-t hover:bg-muted/20"
                        >
                            <td
                                v-for="column in definition.columns"
                                :key="column.key"
                                class="px-4 py-3"
                                :class="
                                    column.key === definition.columns[0]?.key
                                        ? 'font-medium'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ column.value(row) }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="resourceKey === 'gardes'"
                                        variant="ghost"
                                        size="icon"
                                        title="Voir les internes de la garde"
                                        @click="showParticipants(row)"
                                        ><UsersRound class="h-4 w-4" /></Button
                                    ><Button
                                        variant="ghost"
                                        size="icon"
                                        title="Modifier"
                                        @click="editRow(row)"
                                        ><Pencil class="h-4 w-4" /></Button
                                    ><Button
                                        variant="ghost"
                                        size="icon"
                                        title="Supprimer"
                                        class="text-destructive hover:text-destructive"
                                        @click="deleteRow(row)"
                                        ><Trash2 class="h-4 w-4"
                                    /></Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!loading && filteredRows.length === 0">
                            <td
                                :colspan="definition.columns.length + 1"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                Aucun élément à afficher.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="rounded-xl">
                <DialogHeader>
                    <DialogTitle>Confirmer la suppression</DialogTitle>
                    <DialogDescription>
                        Supprimer « {{ deleteLabel }} » ? Cette action est irréversible.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        :disabled="deleting"
                        @click="deleteDialogOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="deleting"
                        class="gap-2"
                        @click="confirmDelete"
                    >
                        <Trash2 class="h-4 w-4" />
                        {{ deleting ? 'Suppression…' : 'Supprimer' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="participantsDialogOpen">
            <DialogContent class="rounded-xl sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Internes de la garde</DialogTitle>
                    <DialogDescription>
                        {{ participantsTarget?.type_garde?.name ?? 'Garde' }} ·
                        {{ participantsTarget ? dateLabel(participantsTarget.date_garde) : '' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="max-h-80 overflow-y-auto rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/30 hover:bg-muted/30">
                                <TableHead
                                    class="text-xs font-bold uppercase tracking-wide text-primary"
                                    >Interne</TableHead
                                >
                                <TableHead
                                    class="text-xs font-bold uppercase tracking-wide text-primary"
                                    >Contact</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="interne in participantsTarget?.participants ?? []"
                                :key="interne.id"
                            >
                                <TableCell class="font-medium">{{ interne.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{
                                    interne.phone || '—'
                                }}</TableCell>
                            </TableRow>
                            <TableRow v-if="!participantsTarget?.participants?.length">
                                <TableCell
                                    colspan="2"
                                    class="py-8 text-center text-muted-foreground"
                                    >Aucun interne n’est associé à cette garde.</TableCell
                                >
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="participantsDialogOpen = false"
                        >Fermer</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="excelDialogOpen">
            <DialogContent class="rounded-xl sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Importer depuis Excel</DialogTitle>
                    <DialogDescription>
                        Depuis « {{ definition.title }} », tu peux importer les feuilles concernées;
                        les autres peuvent rester vides.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="grid max-h-[65vh] gap-5 overflow-y-auto lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]"
                >
                    <div class="space-y-4">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full gap-2"
                            :disabled="excelDownloading"
                            @click="downloadExcelTemplate"
                        >
                            <Download class="h-4 w-4" />{{
                                excelDownloading ? 'Préparation…' : 'Télécharger le modèle'
                            }}
                        </Button>
                        <label class="grid gap-2 text-sm font-medium">
                            Classeur Excel (.xlsx, .xls)
                            <input
                                ref="excelFileInput"
                                type="file"
                                accept=".xlsx,.xls"
                                class="w-full rounded-md border bg-background p-2 text-sm file:mr-3 file:rounded-sm file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-primary-foreground"
                                @change="selectExcelFile"
                            />
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ excelFile?.name ?? 'Aucun fichier choisi' }} · limite 15 Mo
                        </p>
                        <Button
                            type="button"
                            class="w-full gap-2"
                            :disabled="!excelFile || excelImporting"
                            @click="importExcel"
                        >
                            <Upload class="h-4 w-4" />{{
                                excelImporting ? 'Import en cours…' : 'Importer le classeur'
                            }}
                        </Button>
                        <div
                            v-if="excelErrors.length"
                            class="rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm"
                            role="alert"
                        >
                            <p class="mb-2 font-semibold text-destructive">Import annulé</p>
                            <ul class="max-h-36 list-inside list-disc space-y-1 overflow-y-auto">
                                <li v-for="(message, index) in excelErrors" :key="index">
                                    {{ message }}
                                </li>
                            </ul>
                        </div>
                        <div
                            v-if="excelSummary"
                            class="rounded-md border border-emerald-600/30 bg-emerald-500/5 p-3 text-sm"
                            role="status"
                        >
                            <p class="mb-2 font-semibold text-emerald-700 dark:text-emerald-300">
                                Import terminé
                            </p>
                            <p
                                v-for="(counts, sheet) in excelSummary"
                                :key="sheet"
                                class="flex justify-between gap-3"
                            >
                                <span>{{ sheet }}</span
                                ><span
                                    >{{ counts.created }} créé(s) · {{ counts.reused }} déjà
                                    présent(s)</span
                                >
                            </p>
                        </div>
                    </div>

                    <div class="rounded-md border">
                        <div
                            class="border-b bg-muted/40 px-3 py-2 text-xs font-semibold uppercase text-muted-foreground"
                        >
                            Feuilles et colonnes attendues
                        </div>
                        <div class="max-h-80 divide-y overflow-y-auto">
                            <div
                                v-for="sheet in excelSheets"
                                :key="sheet.name"
                                class="px-3 py-2 text-xs"
                            >
                                <p class="font-semibold">{{ sheet.name }}</p>
                                <code class="text-primary">{{ sheet.columns }}</code>
                            </div>
                        </div>
                        <p class="border-t bg-muted/20 px-3 py-2 text-xs text-muted-foreground">
                            Les références sont locales au classeur. Les mots de passe de la feuille
                            Utilisateurs sont en clair dans le fichier: protège-le et supprime-le
                            après l’import.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="excelImporting"
                        @click="excelDialogOpen = false"
                        >Fermer</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminWrapper>
</template>
