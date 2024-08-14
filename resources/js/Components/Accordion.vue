<script setup>
import { computed } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

const props = defineProps({
    accordionClasses: {
        type: String,
        default: ''
    },
    headClasses: {
        type: String,
        default: ''
    },
    bodyClasses: {
        type: String,
        default: ''
    },
})

const computedAccordionClasses = computed(() => [
    'flex flex-col justify-center',
    props.accordionClasses
]);

const computedAccordionHeadClasses = computed(() => [
    'flex items-center justify-between gap-2.5 rounded-sm bg-grey-50 border-t border-grey-200 py-1 px-2.5',
    props.headClasses
]);

const computedAccordionBodyClasses = computed(() => [
    props.bodyClasses,
]);

</script>

<template>
    <Disclosure 
        as="div" 
        :defaultOpen="true"
        v-slot="{ open }" 
        :class="computedAccordionClasses"
    >
        <DisclosureButton :class="computedAccordionHeadClasses">
            <slot name="head"></slot>
            <svg 
                width="20" 
                height="20" 
                viewBox="0 0 20 20" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg"
                class="inline-block text-grey-900"
                :class="[open ? 'rotate-180 transform' : '']"
            >
                <path d="M15.8337 7.08337L10.0003 12.9167L4.16699 7.08337" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
            </svg>
        </DisclosureButton>
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <DisclosurePanel :class="computedAccordionBodyClasses">
                <slot name="body"></slot>
            </DisclosurePanel>
        </transition>
    </Disclosure>
</template>
