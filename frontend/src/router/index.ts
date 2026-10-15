import {
    createRouter,
    createWebHistory,
    type RouteLocationNormalized,
    type Router,
} from 'vue-router'

import authRoutes from './authRoutes'
import publicRoutes from './publicRoutes'
import adminRoutes from './adminRoutes'
import errorRoutes from './errorRoutes'
import { useAuthStore } from '@/stores'
import config from '@/config'

const routes = [...adminRoutes, ...authRoutes, ...errorRoutes, ...publicRoutes]

const name = config.appName

interface RouteMeta {
    title?: string
    requiresAuth?: boolean
    role?: string[]
    internalOnly?: boolean
}

export function setupRouterTitle(router: Router) {
    router.afterEach((to: RouteLocationNormalized) => {
        const meta = to.meta as RouteMeta
        if (meta?.title) {
            document.title = `${name} | ${meta.title}`
        } else {
            document.title = name
        }
    })
}

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        }
        return { top: 0, behavior: 'smooth' }
    },
})

router.beforeEach(async (to, from) => {
    const auth = useAuthStore()

    if (auth.loadingUser) {
        await auth.loadUser()
    }

    const isAuthenticated = auth.isLogged()
    const meta = to.meta as RouteMeta

    if (meta.requiresAuth && !isAuthenticated) {
        return { name: 'login' }
    }

    if (isAuthenticated && to.name && ['login', 'forgot-password'].includes(to.name as string)) {
        return { name: 'admin.dashboard' }
    }

    if (meta.role && meta.role.length > 0) {
        const userRoleName = auth.user?.role ?? ''
        const hasAccess = meta.role.includes(userRoleName)
        if (!hasAccess) {
            return { name: 'forbidden' }
        }
    }

    if (meta.internalOnly && !from.name) {
        return { name: 'not-found' }
    }

    return true
})

setupRouterTitle(router)

export default router
