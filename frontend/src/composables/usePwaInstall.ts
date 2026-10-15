import { computed, ref } from 'vue'

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

// L'évènement peut survenir avant le montage d'un composant : on l'écoute dès l'import.
const installEvent = ref<BeforeInstallPromptEvent | null>(null)

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault()
        installEvent.value = event as BeforeInstallPromptEvent
    })

    window.addEventListener('appinstalled', () => {
        installEvent.value = null
    })
}

export function usePwaInstall() {
    const canInstall = computed(() => installEvent.value !== null)

    async function install() {
        const event = installEvent.value
        if (!event) return

        await event.prompt()
        await event.userChoice
        installEvent.value = null
    }

    return { canInstall, install }
}
