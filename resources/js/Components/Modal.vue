<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { TimesIcon } from '@/Components/Icons/solid';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'

const props = defineProps({
    title: String,
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    withHeader: {
        type: Boolean,
        default: true,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = null;
        }
    }
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        '2xs': 'sm:max-w-[333px]',
        'xs': 'sm:max-w-[370px]',
        'sm': 'sm:max-w-[574px]',
        'md': 'sm:max-w-[746px]',
        'lg': 'sm:max-w-[1080px]',
    }[props.maxWidth];
});
</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="close" class="relative z-20">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div
                    class="flex min-h-full items-center justify-center p-4 text-center"
                >
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="transform w-full fixed rounded-[5px] bg-white p-6 text-left align-middle shadow-xl transition-all"
                            :class="maxWidthClass"
                        >
                            <DialogTitle
                                :as="'div'"
                                class="flex justify-between items-start self-stretch pb-6"
                                v-if="props.withHeader"
                            >
                                <p class="text-center text-red-950 text-md font-medium">{{ title }}</p>
                                <TimesIcon
                                    class="text-red-900 hover:text-red-800 hover:cursor-pointer"
                                    @click="close"
                                />
                            </DialogTitle>
                            <slot></slot>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
