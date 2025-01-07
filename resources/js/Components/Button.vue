<script setup>
import { toRefs, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator(value) {
            return ['primary', 'secondary', 'tertiary', 'red', 'red-tertiary', 'green', 'green-tertiary'].includes(value)
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
    'flex w-full items-center justify-center whitespace-nowrap transition-colors font-medium select-none disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none',
]

const variantClasses = computed(() => ({
    'primary': 'bg-primary-900 text-white hover:bg-primary-800',
    'secondary': 'bg-primary-50 text-primary-900 hover:bg-primary-25 hover:text-primary-700',
    'tertiary': 'bg-transparent text-primary-900 hover:text-primary-800 border border-primary-800',
    'red': 'bg-primary-600 text-white hover:bg-primary-500',
    'red-tertiary': 'bg-transparent text-primary-500 border border-primary-600 hover:text-primary-600',
    'green': 'bg-green-500 text-white hover:bg-green-600',
    'green-tertiary': 'bg-transparent text-green-600 border border-green-500 hover:text-green-500',
}[variant]));

const sizeClasses = computed(() => ({
    'md': iconOnly ? 'p-2' : 'px-4 py-2 text-sm',
    'lg': iconOnly ? 'p-3' : 'px-6 py-3 text-base',
}[size]));

const shapeClasses = computed(() => ({
    'rounded-md': !squared && !pill,
    'rounded-full': pill,
}));

const disabledClasses = computed(() => ({
    'pointer-events-none': href && disabled.value === true,
    '!bg-grey-100 !text-grey-200': disabled.value === true && variant === 'primary',
    '!bg-primary-100': disabled.value === true && variant === 'secondary',
    '!bg-primary-200': disabled.value === true && variant === 'tertiary',
    '!bg-primary-300': disabled.value === true && variant === 'red',
    '!border-primary-300 !text-primary-300': disabled.value === true && variant === 'red-tertiary',
    '!bg-green-200': disabled.value === true && variant === 'green',
    '!border-green-200 !text-green-200': disabled.value === true && variant === 'green-tertiary',
}));

const classes = computed(() => [
    baseClasses,
    variantClasses.value,
    sizeClasses.value,
    shapeClasses.value,
    disabledClasses.value,
]);

const iconSizeClasses = [
    {
        'h-[18px]': size == 'md',
        'h-[20px]': size == 'lg',
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

const Tag = computed(() => {
    if (href) {
        return external ? 'a' : Link;
    }
    return 'button';
});
</script>

<style scoped>
/* Add styles for the slot container */
.icon-slot-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
}

.margin-right {
  margin-right: 6px;
}

.margin-left {
  margin-left: 6px;
}

</style>

<template>
     <component
        :is="Tag"
        :href="!disabled && href ? href : null"
        :class="classes"
        :aria-disabled="disabled.toString()"
        :type="href ? null : type"
        @click="handleClick"
        :disabled="disabled"
    >
        <span
            v-if="srText"
            class="sr-only"
        >
            {{ srText }}
        </span>

        <span 
            class="flex items-center gap-2"
            :class="{
                'min-h-[18px] max-h-[18px]': size === 'md',
                'min-h-[20px] max-h-[20px]': size === 'lg',
            }"
            v-if="iconPosition === 'left'"
        >
            <span class="inline-flex items-center justify-center size-[18px]">
                <slot name="icon">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="16" 
                        height="16" 
                        viewBox="0 0 16 16" 
                        fill="none"
                        class="self-center"
                    >
                        <g clip-path="url(#clip0_16_1351)">
                            <path 
                                d="M8.00016 14.6666C11.6821 14.6666 14.6668 11.6818 14.6668 7.99992C14.6668 
                                    4.31802 11.6821 1.33325 8.00016 1.33325C4.31826 1.33325 1.3335 4.31802 
                                    1.3335 7.99992C1.3335 11.6818 4.31826 14.6666 8.00016 14.6666Z" 
                                stroke="currentcolor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
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
                </slot>
            </span>
            <slot :iconSizeClasses="iconSizeClasses" />
        </span>
        <span 
            class="flex items-center gap-2"
            :class="{
                'min-h-[18px] max-h-[18px]': size === 'md',
                'min-h-[20px] max-h-[20px]': size === 'lg',
            }"
            v-else-if="iconPosition === 'right'"
        >
            <slot :iconSizeClasses="iconSizeClasses" />
        
            <span class="inline-flex items-center justify-center size-[18px]">
                <slot name="icon">
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
                                stroke="currentcolor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
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
                </slot>
            </span>
        </span>
        <span 
            class="flex justify-center items-center"
            :class="{
                'min-h-[18px] max-h-[18px]': size === 'md',
                'min-h-[20px] max-h-[20px]': size === 'lg',
            }"
            v-else
        >
            <slot :iconSizeClasses="iconSizeClasses" />
        </span>
    </component>
</template>
