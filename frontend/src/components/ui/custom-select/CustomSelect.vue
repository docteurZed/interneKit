<script setup lang="ts">
import { ref, computed } from 'vue'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { Check, ChevronsUpDown } from 'lucide-vue-next'

interface Option {
    value: string | number
    label: string
}

const props = defineProps<{
    options: Option[]
    modelValue: string | number | (string | number)[] | null
    placeholder?: string
    multiple?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const open = ref(false)

function toggleValue(value: string | number) {
    if (props.multiple) {
        const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []

        const index = current.indexOf(value)

        if (index > -1) current.splice(index, 1)
        else current.push(value)

        emit('update:modelValue', current)
    } else {
        emit('update:modelValue', value)
        open.value = false
    }
}

const selectedLabels = computed(() => {
    if (props.multiple) {
        if (!Array.isArray(props.modelValue)) return []

        return props.modelValue
            .map((value) => props.options.find((o) => o.value === value)?.label)
            .filter(Boolean) as string[]
    } else {
        const option = props.options.find((o) => o.value === props.modelValue)
        return option ? [option.label] : []
    }
})
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button variant="outline" class="w-full justify-between">
                <span class="truncate">
                    {{
                        selectedLabels.length
                            ? selectedLabels.join(', ')
                            : placeholder || 'Sélectionner...'
                    }}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 opacity-50" />
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-full p-0">
            <Command>
                <CommandInput placeholder="Rechercher..." />
                <CommandList>
                    <CommandEmpty>Aucun résultat.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option.value"
                            :value="option.value"
                            @select="() => toggleValue(option.value)"
                            class="hover:bg-muted"
                        >
                            <Check
                                v-if="multiple"
                                class="mr-2 h-4 w-4"
                                :class="
                                    Array.isArray(modelValue) && modelValue.includes(option.value)
                                        ? 'opacity-100'
                                        : 'opacity-0'
                                "
                            />

                            <Check
                                v-else
                                class="mr-2 h-4 w-4"
                                :class="modelValue === option.value ? 'opacity-100' : 'opacity-0'"
                            />

                            {{ option.label }}
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
