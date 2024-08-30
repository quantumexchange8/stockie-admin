<script setup>
import { watch, onMounted, onUnmounted, ref } from 'vue'
import Sidebar from 'primevue/sidebar';
import { ArrowLeftIcon, TimesIcon } from '../Icons/solid';
// import { rightSidebarState } from '@/Composables'
// import RightSidebarHeader from '@/Components/RightSidebar/RightSidebarHeader.vue'

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

// onMounted(() => {
//     // rightSidebarState.isOpen = true
// })
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
                    'border-0 shadow-lg rounded-l-[5px]',

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
                    // Transitions
                    'transition-all',
                    'duration-300',
                    { 'p-5': !props.position == 'full' },

                    // Background and Effects
                    { 'has-[.mask-active]:bg-transparent bg-black/40': props.modal, 'has-[.mask-active]:backdrop-blur-none': props.modal }
                ]
            }),
        }"
    >
        <template #container="{ closeCallback }">
            <div class="flex flex-col justify-center">
                <div class="flex items-center justify-between px-6 pt-6 pb-3">
                    <ArrowLeftIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" 
                        @click="closeCallback" 
                        v-if="previousTab"
                    />
                    <span class="text-primary-950 text-center text-md font-medium">{{ header }}</span>
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
    <!-- <Teleport to="body">
        <aside
            style="
                transition-property: width, transform;
                transition-duration: 1150ms;
            "
            :class="[
                'fixed inset-y-0 right-0 flex flex-col items-start gap-[8px] w-[507px] max-h-[1024px] bg-[#FFF] self-stretch',
                {
                    'translate-x-0 w-[283px] z-20':
                        rightSidebarState.isOpen,
                    'translate-x-full -right-10 w-0 md:translate-x-0 hidden':
                        !rightSidebarState.isOpen,
                },
            ]"
        >
            <RightSidebarHeader
                :title="title"
                :previousTab="previousTab"
             />
            <slot></slot>
        </aside>
    </Teleport> -->
</template>
