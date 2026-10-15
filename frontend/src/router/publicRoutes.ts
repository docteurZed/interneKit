import { About, Actes, Consultations, Gardes, Home, Internes } from '@/modules/public'

export default [
    {
        path: '/',
        children: [
            {
                path: '',
                name: 'home',
                component: Home,
            },
            {
                path: 'consultations',
                name: 'consultations',
                component: Consultations,
                meta: { title: 'Consultations' },
            },
            {
                path: 'actes',
                name: 'actes',
                component: Actes,
                meta: { title: 'Actes' },
            },
            {
                path: 'gardes',
                name: 'gardes',
                component: Gardes,
                meta: { title: 'Gardes' },
            },
            {
                path: 'internes',
                name: 'internes',
                component: Internes,
                meta: { title: 'Internes' },
            },
            {
                path: 'a-propos',
                name: 'about',
                component: About,
                meta: { title: 'À propos' },
            },
        ],
    },
]
