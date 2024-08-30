<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: String,
    position: {
        type: String,
        default: 'top-center',
    }
});

// const { position } = props

// const baseClass = [
//     'tooltip',
// ]

// const positionClasses = (position) => ({
//     'tooltip-top-left': position == 'top-left',
//     'tooltip-top': position == 'top-center',
//     'tooltip-top-right': position == 'top-right',
//     'tooltip-bottom-left': position == 'bottom-left',
//     'tooltip-bottom': position == 'bottom-center',
//     'tooltip-bottom-right': position == 'bottom-right',
//     'tooltip-left': position == 'left',
//     'tooltip-right': position == 'right',
// })

// const classes = computed(() => [
//     ...baseClass,
//     positionClasses(position),
// ])
</script>

<!-- <style scoped>
.tooltip::before {
    @apply text-2xs z-[10] font-normal bg-primary-50 rounded-[4px] py-1 px-3 text-primary-900 shadow-[0px_6px_8.3px_-4px_rgba(16,24,40,0.10)] leading-normal;
}
.tooltip-top-left::after,
.tooltip-top-right::after,
.tooltip-top::after {
    @apply border-[#fff1f2_transparent_transparent_transparent] z-10;
}

.tooltip-bottom-left::after,
.tooltip-bottom-right::after,
.tooltip-bottom::after {
    @apply border-[transparent_transparent_#fff1f2_transparent] z-10;
}
/* Stylings for additional positions */
.tooltip-top-right:before,
.tooltip-top-left:before {
    @apply -translate-x-1/2 top-auto left-1/2 right-auto bottom-[var(--tooltip-offset)];
}
.tooltip-top-right:after {
    @apply -translate-x-1/2 top-auto left-3/4 right-auto bottom-[var(--tooltip-tail-offset)];
}
.tooltip-top-left:after {
    @apply -translate-x-1/2 top-auto left-1/4 right-auto bottom-[var(--tooltip-tail-offset)];
}
.tooltip-bottom-right:before,
.tooltip-bottom-left:before {
    @apply -translate-x-1/2 top-[var(--tooltip-offset)] left-1/2 right-auto bottom-auto;
}
.tooltip-bottom-right:after {
    @apply -translate-x-1/2 top-[var(--tooltip-tail-offset)] left-3/4 right-auto bottom-auto;
}
.tooltip-bottom-left:after {
    @apply -translate-x-1/2 top-[var(--tooltip-tail-offset)] left-1/4 right-auto bottom-auto;
}
.tooltip-left:after {
    @apply border-[transparent_transparent_transparent_#fff1f2];
}
.tooltip-right:after {
    @apply border-[transparent_#fff1f2_transparent_transparent];
}

.tooltip[data-tip]::before {
    white-space: pre-wrap; /* Ensure it respects line breaks */
    content: attr(data-tip); /* This should now correctly show the tooltip with line breaks */
}
</style> -->

<template>
    <span 
        v-tooltip="{
            value: message,
            pt: {
                root: {
                    class: [
                        // Position and Shadows
                        'absolute bg-primary-50 rounded py-1 px-3 shadow-[0px_6px_8.3px_-4px_rgba(16,24,40,0.10)]',
                        // Spacing
                        {
                            'ml-1': props.position === right,
                            '-ml-1': props.position === left,
                            '-mt-1': props.position === top || (!props.position === right && !props.position === left && !props.position === top && !props.position === bottom),
                            'mt-1': props.position === bottom
                        }
                    ]
                },
                arrow: {
                    class: [
                        'absolute size-0 border-solid bg-transparent',
                        {
                            // Right-aligned arrow (triangle pointing left)
                            'border-y-[0.25rem] border-r-[0.25rem] border-l-0 border-transparent border-r-primary-50': props.position === right || (!props.position === right && !props.position === left && !props.position === top && !props.position === bottom),
                            // Left-aligned arrow (triangle pointing right)
                            'border-y-[0.25rem] border-l-[0.25rem] border-r-0 border-transparent border-l-primary-50': props.position === left,
                            // Top-aligned arrow (triangle pointing down)
                            'border-x-[0.25rem] border-t-[0.25rem] border-b-0 border-transparent border-t-primary-50': props.position === top,
                            // Bottom-aligned arrow (triangle pointing up)
                            'border-x-[0.25rem] border-b-[0.25rem] border-t-0 border-transparent border-b-primary-50': props.position === bottom,
                        },
                        {
                            '-mt-1 !-left-1': props.position === right,
                            '-mt-1 !-right-1': props.position === left,
                            '-ml-1 !-bottom-1': props.position === top || (!props.position === right && !props.position === left && !props.position === top && !props.position === bottom),
                            '-ml-1 !-top-1': props.position === bottom,
                        }
                    ]
                },
                text: {
                    class: ['text-primary-900 font-normal text-2xs leading-normal rounded whitespace-pre-wrap font-normal']
                }
            }
        }"
    >
        <slot></slot>
    </span>

    <!-- <div 
        :class="classes" 
        :data-tip="message"
    >
        <slot></slot>
    </div> -->
</template>
