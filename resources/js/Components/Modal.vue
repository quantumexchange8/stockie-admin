<script setup>
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { TimesIcon } from '@/Components/Icons/solid';
import { DeleteIllus } from '@/Components/Icons/illus';
import { useCustomToast } from '@/Composables/index.js';
import Button from './Button.vue';
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
    deleteConfirmation: {
        type: Boolean,
        default: false,
    },
    confirmationTitle: {
        type: String,
        default: '',
    },
    confirmationMessage: {
        type: String,
        default: '',
    },
    deleteUrl: {
        type: String,
        default: '',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    toastMessage: {
        type: String,
        default: '',
    },
});

const { flashMessage } = useCustomToast();

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

const deleteRecord = () => {
    router.delete(props.deleteUrl, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                flashMessage({ 
                    severity: 'success',
                    summary: props.toastMessage,
                });
            }, 200);
            close();
        }
    });
};

</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="close" class="relative z-[1106]">
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
                    class="flex min-h-dvh items-center justify-center p-4 text-center"
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
                            class="transform w-full max-h-[95%] fixed rounded-[5px] bg-white text-left align-middle shadow-xl transition-all"
                            :class="[
                                maxWidthClass,
                                !props.deleteConfirmation ? 'p-6' : 'flex flex-col gap-9',
                            ]"
                        >
                            <template v-if="!props.deleteConfirmation">
                                <DialogTitle
                                    :as="'div'"
                                    class="flex justify-between items-start self-stretch pb-6"
                                    v-if="props.withHeader"
                                >
                                    <p class="text-center text-primary-950 text-md font-medium">{{ title }}</p>
                                    <TimesIcon
                                        class="text-primary-900 hover:text-primary-800 hover:cursor-pointer"
                                        @click="close"
                                    />
                                </DialogTitle>
                                <slot></slot>
                            </template>
                            <template v-else>
                                <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                                    <slot name="deleteimage">
                                        <DeleteIllus/>
                                    </slot>
                                </div>
                                <DialogTitle
                                    :as="'div'"
                                    class="flex flex-col justify-center items-center self-stretch gap-1 px-6"
                                >
                                    <p class="text-center text-primary-900 text-lg font-medium self-stretch">{{ props.confirmationTitle }}</p>
                                    <p class="text-center text-grey-900 text-base font-medium self-stretch">{{ props.confirmationMessage }}</p>
                                </DialogTitle>
                                <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'lg'"
                                        @click="close"
                                    >
                                        Keep
                                    </Button>
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'lg'"
                                        @click="deleteRecord"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </template>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
