<script setup>
import { watch, onMounted, onUnmounted, ref } from 'vue'
import Sidebar from 'primevue/sidebar';
import { ArrowLeftIcon, TimesIcon } from '../Icons/solid';

const props = defineProps({
    title: String,
    previousTab: {
        type: Boolean,
        default: false
    },
    show: {
        type: Boolean,
        default: false,
    },
    header: {
        type: String,
        default: '',
    },
    withHeader: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['close']);

const visible = ref(props.show);

watch(() => props.show, (newValue) => {
    visible.value = newValue;
}, { immediate: true });

const close = () => {
    visible.value = false;
    emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

watch(visible, (newValue) => {
    if (!newValue) {
        emit('close');
    }
});

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});
</script>

<template>
    <Sidebar 
        v-model:visible="visible" 
        position="right"
        @hide="close"
        :pt="{
            root: ({ props }) => ({
                class: [
                    // Flexbox
                    'flex flex-col',

                    // Position
                    'relative',
                    { '!transition-none !transform-none !w-screen !h-screen !max-h-full !top-0 !left-0': props.position == 'full' },

                    // Size
                    {
                        'h-full w-[510px]': props.position == 'left' || props.position == 'right',
                        'h-auto w-full': props.position == 'top' || props.position == 'bottom'
                    },

                    // Shape
                    'border-0 rounded-l-[5px]',

                    // Colors
                    'bg-white/90 text-grey-700 shadow-[0px_24px_40px_0px_rgba(0,0,0,0.25)]',

                    // Transitions
                    'transition-transform duration-300',

                    // Misc
                    'pointer-events-auto backdrop-blur-3xl'
                ]
            }),
            mask: ({ props }) => ({
                class: [
                    'transition-all duration-300 !z-[1106]',
                    { 'p-5': !props.position == 'full' },

                    // Background and Effects
                    { 'has-[.mask-active]:bg-transparent bg-black/40': props.modal }
                ]
            }),
            transition: ({ props }) => {
                return props.position === 'top'
                    ? {
                        enterFromClass: 'translate-x-0 -translate-y-full translate-z-0 mask-active',
                        leaveToClass: 'translate-x-0 -translate-y-full translate-z-0 mask-active'
                    }
                    : props.position === 'bottom'
                    ? {
                        enterFromClass: 'translate-x-0 translate-y-full translate-z-0 mask-active',
                        leaveToClass: 'translate-x-0 translate-y-full translate-z-0 mask-active'
                    }
                    : props.position === 'left'
                    ? {
                        enterFromClass: '-translate-x-full translate-y-0 translate-z-0 mask-active',
                        leaveToClass: '-translate-x-full translate-y-0 translate-z-0 mask-active'
                    }
                    : props.position === 'right'
                    ? {
                        enterFromClass: 'translate-x-full translate-y-0 translate-z-0 mask-active',
                        leaveToClass: 'translate-x-full translate-y-0 translate-z-0 mask-active'
                    }
                    : {
                        enterFromClass: 'opacity-0 mask-active',
                        enterActiveClass: 'transition-opacity duration-400 ease-in',
                        leaveActiveClass: 'transition-opacity duration-400 ease-in',
                        leaveToClass: 'opacity-0 mask-active'
                    };
            }
        }"
    >
        <template #container="{ closeCallback }">
            <div class="flex flex-col justify-center">
                <div 
                    v-if="withHeader"
                    class="flex items-center px-6 pt-6 pb-3"
                    :class="{
                        'justify-between':!previousTab,
                        '': previousTab
                    }"
                >
                    <ArrowLeftIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" 
                        @click="closeCallback" 
                        v-if="previousTab"
                    />
                    <div class="flex items-center justify-center w-full" v-if="previousTab">
                        <span class="text-primary-950 text-center text-md font-medium">{{ header }}</span>
                    </div>
                    <span class="text-primary-950 text-center text-md font-medium" v-else>{{ header }}</span>
                    <TimesIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="closeCallback" 
                        v-if="!previousTab"
                    />
                </div>
                <slot></slot>
            </div>
        </template>
    </Sidebar>
</template>
