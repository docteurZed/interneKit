import { useColorMode } from '@vueuse/core'
import { watch } from 'vue'

const mode = useColorMode()

watch(mode, () => {
    document.documentElement.classList.add('theme-transition')

    setTimeout(() => {
        document.documentElement.classList.remove('theme-transition')
    }, 1000)
})
