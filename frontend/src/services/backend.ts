import config from '@/config'
import axios, { type AxiosInstance } from 'axios'

const BACKEND_URL = config.backendUrl

// ─── Instances Axios ────────────────────────────────────────────────────────

const http = axios.create({
    baseURL: BACKEND_URL,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
})

const api = axios.create({
    baseURL: `${BACKEND_URL}/api`,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
})

const uploadApi = axios.create({
    baseURL: `${BACKEND_URL}/api`,
    headers: {
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
})

// ─── CSRF Cookie ────────────────────────────────────────────────────────────

let csrfInitialized = false
let csrfPromise: Promise<void> | null = null

export async function getCookies(): Promise<void> {
    if (csrfInitialized) return

    if (csrfPromise) return csrfPromise

    csrfPromise = http
        .get('/sanctum/csrf-cookie')
        .then(() => {
            csrfInitialized = true
        })
        .catch(() => {
            csrfPromise = null
        })

    return csrfPromise
}

export function resetCsrf(): void {
    csrfInitialized = false
    csrfPromise = null
}

// ─── Intercepteurs ──────────────────────────────────────────────────────────

export interface ApiError {
    status: number | null
    message: string
    errors?: Record<string, string[]>
}

function attachInterceptors(client: AxiosInstance) {
    // Garantit le cookie CSRF avant tout POST/PUT/PATCH/DELETE
    client.interceptors.request.use(async (config) => {
        const method = config.method?.toLowerCase() ?? ''
        if (['post', 'put', 'patch', 'delete'].includes(method)) {
            await getCookies()
        }
        return config
    })

    client.interceptors.response.use(
        (response) => response.data,
        (error) => {
            const status: number | null = error.response?.status ?? null
            let message = 'Une erreur est survenue.'
            let errors: Record<string, string[]> | undefined

            if (!error.response) {
                message = 'Serveur injoignable.'
            } else if (status === 422) {
                message = 'Erreur de validation.'
                errors = error.response.data?.errors
            } else {
                message = error.response.data?.message || 'Erreur interne du serveur.'
            }

            console.error(`[API ERROR] ${status ?? 'NO_RESPONSE'} → ${message}`)

            const formattedError: ApiError = {
                status,
                message,
                ...(errors && { errors }),
            }

            return Promise.reject(formattedError)
        },
    )
}

attachInterceptors(api)
attachInterceptors(http)
attachInterceptors(uploadApi)

export { api, http, uploadApi }
