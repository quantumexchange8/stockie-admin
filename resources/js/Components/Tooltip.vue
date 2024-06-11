<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: String,
    position: {
        type: String,
        default: 'top-center',
    }
});

const { position } = props

const baseClass = [
    'tooltip',
]

const positionClasses = (position) => ({
    'tooltip-top-left': position == 'top-left',
    'tooltip-top': position == 'top-center',
    'tooltip-top-right': position == 'top-right',
    'tooltip-bottom-left': position == 'bottom-left',
    'tooltip-bottom': position == 'bottom-center',
    'tooltip-bottom-right': position == 'bottom-right',
    'tooltip-left': position == 'left',
    'tooltip-right': position == 'right',
})

const classes = computed(() => [
    ...baseClass,
    positionClasses(position),
])
</script>

<style scoped>
.tooltip::before {
    @apply text-2xs font-normal bg-red-50 rounded-[4px] py-1 px-3 text-red-900 shadow-[0px_3px_8.3px_-4px_rgba(16,24,40,0.10)] leading-normal;
}
.tooltip-top-left::after,
.tooltip-top-right::after,
.tooltip-top::after {
    @apply border-[#FFF1F2_transparent_transparent_transparent] z-10;
}

.tooltip-bottom-left::after,
.tooltip-bottom-right::after,
.tooltip-bottom::after {
    @apply border-[transparent_transparent_#FFF1F2_transparent] z-10;
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
    @apply border-[transparent_transparent_transparent_#FFF1F2];
}
.tooltip-right:after {
    @apply border-[transparent_#FFF1F2_transparent_transparent];
}
</style>

<template>
    <div 
        :class="classes" 
        :data-tip="message"
    >
        <slot></slot>
    </div>
</template>
