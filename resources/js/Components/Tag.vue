<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator(value) {
            return ['default', 'red', 'green', 'blue', 'grey', 'yellow'].includes(value)
        },
    },
    value: String,
})

const baseClasses = [
    'inline-flex py-1.5 px-2.5 gap-2.5 rounded-[5px] border items-center justify-center whitespace-nowrap text-2xs font-semibold',
]

const variantClasses = (variant) => ({
    'bg-primary-50 border-primary-100 text-primary-900': variant == 'default',
    'bg-primary-50 border-primary-200 text-primary-500': variant == 'red',
    'bg-green-50 border-green-100 text-green-700': variant == 'green',
    'bg-blue-50 border-blue-100 text-blue-700': variant == 'blue',
    'bg-grey-50 border-grey-100 text-grey-700': variant == 'grey',
    'bg-yellow-50 border-yellow-100 text-yellow-600': variant == 'yellow',
})

const classes = computed(() => [
    ...baseClasses,
    variantClasses(props.variant),
])
</script>

<template>
    <span :class="classes">
        {{ props.value }}
    </span>
</template>
