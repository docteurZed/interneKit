import { Dashboard, Profile } from '@/modules/admin'
import AdminCrudPage from '@/modules/admin/components/AdminCrudPage.vue'

const admin = (route: string) => `admin.${route}`

export default [
    {
        path: '/admin',
        meta: { requiresAuth: true, role: ['admin'] },
        children: [
            {
                path: '',
                // name: admin('dashboard'),
                component: Dashboard,
                meta: { title: 'Tableau de bord' },
            },
            {
                path: 'dashboard',
                name: admin('dashboard'),
                component: Dashboard,
                meta: { title: 'Tableau de bord' },
            },
            {
                path: 'profile',
                name: admin('profile'),
                component: Profile,
                meta: { title: 'Profil' },
            },
            {
                path: 'user',
                name: admin('user'),
                component: AdminCrudPage,
                props: { resource: 'users' },
                meta: { title: 'Administrateurs' },
            },
            {
                path: 'interne',
                name: admin('interne'),
                component: AdminCrudPage,
                props: { resource: 'internes' },
                meta: { title: 'Internes' },
            },
            {
                path: 'gardes',
                name: admin('gardes'),
                component: AdminCrudPage,
                props: { resource: 'gardes' },
                meta: { title: 'Gardes' },
            },
            {
                path: 'affectations',
                name: admin('affectations'),
                component: AdminCrudPage,
                props: { resource: 'affectations' },
                meta: { title: 'Affectations de rotation' },
            },
            {
                path: 'referentiels',
                name: admin('referentiels'),
                component: AdminCrudPage,
                props: { resource: 'referentiels' },
                meta: { title: 'Référentiels' },
            },
        ],
    },
]
