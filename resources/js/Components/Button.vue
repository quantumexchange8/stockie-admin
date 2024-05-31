<script setup>
import { toRefs, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator(value) {
            return ['primary', 'secondary', 'tertiary', 'red'].includes(value)
        },
    },
    type: {
        type: String,
        default: 'submit',
    },
    size: {
        type: String,
        default: 'md',
        validator(value) {
            return ['md', 'lg'].includes(value)
        },
    },
    squared: {
        type: Boolean,
        default: false,
    },
    pill: {
        type: Boolean,
        default: false,
    },
    href: {
        type: String,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    iconPosition: {
        type: String,
        default: '',
    },
    iconOnly: {
        type: Boolean,
        default: false,
    },
    srText: {
        type: String || undefined,
        default: undefined,
    },
    external: {
        type: Boolean,
        default: false,
    }
})

const emit = defineEmits(['click'])

const { type, variant, size, squared, pill, href, iconOnly, iconPosition, srText, external } = props

const { disabled } = toRefs(props)

const baseClasses = [
    'flex items-end justify-center transition-colors font-medium select-none disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-dark-eval-2',
]

const variantClasses = (variant) => ({
    'bg-primary-900 text-white hover:bg-primary-800': variant == 'primary',
    'bg-primary-50 text-primary-900 hover:bg-primary-25 hover:text-primary-700': variant == 'secondary',
    'bg-white text-primary-900 hover:text-primary-800 border border-primary-800': variant == 'tertiary',
    'bg-primary-600 text-white hover:bg-primary-500': variant == 'red',
})

const classes = computed(() => [
    ...baseClasses,
    iconOnly
        ? {
                'p-2': size == 'md',
                'p-3': size == 'lg',
            }
        : {
                'px-4 py-2 text-base': size == 'md',
                'px-6 py-3 text-xl': size == 'lg',
            },
    variantClasses(variant),
    {
        'rounded-md': !squared && !pill,
        'rounded-full': pill,
    },
    {
        'pointer-events-none': href && disabled.value,
    },
    {
        '!bg-grey-100 !text-grey-200': disabled.value === true && variant === 'primary',
        '!bg-primary-100': disabled.value === true && variant === 'secondary',
        '!bg-primary-200': disabled.value === true && variant === 'tertiary',
        '!bg-primary-300': disabled.value === true && variant === 'red',
    },
    {
        'text-sm self-center': size == 'md',
        'text-base': size == 'lg',
    },
])

const iconSizeClasses = [
    {
        'w-[76px] h-[18px]': size == 'md',
        'w-[86px] h-[20px]': size == 'lg',
    },
]


const handleClick = (e) => {
    if (disabled.value) {
        e.preventDefault()
        e.stopPropagation()
        return
    }
    emit('click', e)
}

const Tag = external ?  'a' : Link
</script>

<template>
    <component
        :is="Tag"
        v-if="href"
        :href="!disabled ? href : null"
        :class="classes"
        :aria-disabled="disabled.toString()"
    >
        <span
            v-if="srText"
            class="sr-only"
        >
            {{ srText }}
        </span>

        <slot :iconSizeClasses="iconSizeClasses" />
    </component>

    <button
        v-else
        :type="type"
        :class="classes"
        @click="handleClick"
        :disabled="disabled"
    >
        <span
            v-if="srText"
            class="sr-only"
        >
            {{ srText }}
        </span>

        <div v-if="iconPosition === 'left'">
            <span class="flex min-w-[96px] max-w-[96px] max-h-[18px]">
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="16" 
                    height="16" 
                    viewBox="0 0 16 16" 
                    fill="none"
                    class="self-center mr-[6px]"
                >
                    <g clip-path="url(#clip0_16_1351)">
                        <path 
                            d="M8.00016 14.6666C11.6821 14.6666 14.6668 11.6818 14.6668 7.99992C14.6668 
                                4.31802 11.6821 1.33325 8.00016 1.33325C4.31826 1.33325 1.3335 4.31802 
                                1.3335 7.99992C1.3335 11.6818 4.31826 14.6666 8.00016 14.6666Z" 
                            stroke="#FDF6EF" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            v-if="variant === 'primary' || variant === 'red'"
                        />
                        <path 
                            d="M8.00016 14.6666C11.6821 14.6666 14.6668 11.6818 14.6668 7.99992C14.6668 
                                4.31802 11.6821 1.33325 8.00016 1.33325C4.31826 1.33325 1.3335 4.31802 
                                1.3335 7.99992C1.3335 11.6818 4.31826 14.6666 8.00016 14.6666Z" 
                            stroke="#9F151A" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            v-else
                        />
                    </g>
                    <defs>
                        <clipPath id="clip0_16_1351">
                            <rect 
                                width="16" 
                                height="16" 
                                fill="white"
                            />
                        </clipPath>
                    </defs>
                </svg>
                <slot :iconSizeClasses="iconSizeClasses" />
            </span>
        </div>
        <div v-else-if="iconPosition === 'right'">
            <span class="flex min-w-[96px] max-w-[96px] max-h-[18px]">
                <slot :iconSizeClasses="iconSizeClasses" />
            
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="16" 
                    height="16" 
                    viewBox="0 0 16 16" 
                    fill="none"
                    class="self-center ml-[6px]"
                >
                    <g clip-path="url(#clip0_16_1351)">
                        <path 
                            d="M8.00016 14.6666C11.6821 14.6666 14.6668 11.6818 14.6668 7.99992C14.6668 
                                4.31802 11.6821 1.33325 8.00016 1.33325C4.31826 1.33325 1.3335 4.31802 
                                1.3335 7.99992C1.3335 11.6818 4.31826 14.6666 8.00016 14.6666Z" 
                            stroke="#FDF6EF" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            v-if="variant === 'primary' || variant === 'red'"
                        />
                        <path 
                            d="M8.00016 14.6666C11.6821 14.6666 14.6668 11.6818 14.6668 7.99992C14.6668 
                                4.31802 11.6821 1.33325 8.00016 1.33325C4.31826 1.33325 1.3335 4.31802 
                                1.3335 7.99992C1.3335 11.6818 4.31826 14.6666 8.00016 14.6666Z" 
                            stroke="#9F151A" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            v-else
                        />
                    </g>
                    <defs>
                        <clipPath id="clip0_16_1351">
                            <rect 
                                width="16" 
                                height="16" 
                                fill="white"
                            />
                        </clipPath>
                    </defs>
                </svg>
            </span>
        </div>
        <div v-else>
            <slot :iconSizeClasses="iconSizeClasses" />
        </div>
    </button>
</template>
