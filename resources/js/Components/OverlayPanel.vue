<script setup>
import { ref, computed } from "vue";
import OverlayPanel from 'primevue/overlaypanel';

const props = defineProps({
    withArrow: {
        type: Boolean,
        default: false
    },
    bgColor: {
        type: String,
        default: 'bg-white'
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(["click", 'close']);

const op = ref();
const opVisible = ref(false);
const arrowPosition = ref('top');

const calculateArrowPosition = (event) => {
    const buttonRect = event.target.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    arrowPosition.value = buttonRect.top > windowHeight /2 ? 'bottom' : 'top';
};

const toggle = (event) => {
    if (!props.disabled) {
        calculateArrowPosition(event);
        opVisible.value = !opVisible.value;
        op.value.toggle(event);
    }
}

const show = (event) => {
    toggle(event);
};

const hide = () => {
    op.value.hide();
};

// Expose methods to allow parent component control
defineExpose({ show, hide });

const overlayPanelRootWithArrowClasses = computed(() => [
    // Before: Triangle
    'before:absolute before:-ml-[9px] before:left-[calc(var(--overlayArrowLeft,0)+1.25rem)] z-0',
    'before:w-0 before:h-0 before:shadow-inner',
    'before:border-transparent before:border-solid',
    'before:border-x-[8px] before:border-[8px]',

    'after:absolute after:-ml-[8px] after:left-[calc(var(--overlayArrowLeft,0)+1.25rem)]',
    'after:w-0 after:h-0 after:shadow-inner',
    'after:border-transparent after:border-solid',
    'after:border-x-[0.5rem] after:border-[0.5rem]',
    {
        'top-0 before:-top-2 after:-top-2 before:border-t-0 before:border-b-grey-100 after:border-t-0 after:border-b-grey-100 after:opacity-95': arrowPosition.value === 'top',
        'before:-bottom-2 after:-bottom-2 before:border-b-0 before:border-t-grey-100 after:border-b-0 after:border-t-grey-100 after:opacity-95 -mt-4': arrowPosition.value === 'bottom',
    }
])

const overlayPanelRootClasses = computed(() => [
    'rounded-[5px] shadow-[0px_24px_40px_0px_rgba(0,0,0,0.25)] border-0 absolute left-0 top-10 mt-2 !z-[1109] transform origin-center opacity-100',
    props.bgColor,
    props.withArrow ? overlayPanelRootWithArrowClasses.value : ''
])

</script>

<template>
    <OverlayPanel 
        ref="op" 
        appendTo="body"
        dismissable
        @hide="$emit('close')"
        :pt="{
            root: {
                class: overlayPanelRootClasses 
            },
        }"
    >
        <slot></slot>
    </OverlayPanel>
</template>
