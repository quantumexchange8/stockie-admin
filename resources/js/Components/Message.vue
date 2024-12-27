<script setup>
import { ref } from 'vue';
import { TimesIcon, ToastErrorIcon, ToastInfoIcon, ToastSuccessIcon } from './Icons/solid';
import Message from 'primevue/message';

const props = defineProps({
    severity: {
        type: String,
        default: 'success'
    },
    title: String,
    closable: {
        type: Boolean,
        default: true,
    },
});

const visible = ref(true);

const onClose = () => (visible.value = false);

</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="transform opacity-0"
    >
        <Message 
            v-if="visible"
            :severity="severity"
            @close="onClose"
            :pt="{
                root: { 
                    class: [
                        'w-full rounded-[5px] p-3',
                        {
                            'bg-green-50': severity === 'success',
                            'bg-yellow-50': severity === 'warn',
                            'bg-primary-50': severity === 'error',
                            'bg-blue-50': severity === 'info',
                        }
                    ]
                },
                wrapper: { class: 'grid grid-cols-12 w-full items-center gap-x-3' },
                icon: { class: 'col-span-1 size-full flex items-start justify-start' },
                text: { class: 'col-span-10 w-full' },
                closebutton: { class: 'col-span-1 size-full flex items-center justify-center' }
            }"
        >
            <template #messageicon>
                <div class="flex h-full w-full items-start">
                    <ToastSuccessIcon class="w-full flex-shrink-0 size-6" v-if="severity === 'success'" />
                    <ToastInfoIcon class="w-full flex-shrink-0 size-6" v-if="severity === 'info'" />
                    <svg 
                        width="24" 
                        height="24" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="w-full flex-shrink-0 size-6" 
                        v-if="severity === 'warn'"
                    >
                        <path d="M12 2.00098C6.49094 2.00098 2 6.49052 2 12C2 17.5094 6.49094 21.9986 12 21.9986C17.5091 21.9986 22 17.5094 22 12C22 6.49052 17.5091 2.00098 12 2.00098ZM12 16.9992C11.4481 16.9992 10.999 16.5512 10.999 15.9992C10.999 15.4473 11.4481 14.9996 12 14.9996C12.5519 14.9996 13.001 15.4473 13.001 15.9992C13.001 16.5512 12.5519 16.9992 12 16.9992ZM13.001 12.9996C13.001 13.5515 12.5519 13.9996 12 13.9996C11.4481 13.9996 10.999 13.5515 10.999 12.9996V8.00028C10.999 7.44835 11.4481 7.00029 12 7.00029C12.5519 7.00029 13.001 7.44835 13.001 8.00028V12.9996Z" fill="#DEA622"/>
                    </svg>
                    <ToastErrorIcon class="w-full flex-shrink-0 size-6" v-if="severity === 'error'" />
                </div>
            </template>
            <template #closeicon>
                <div class="flex h-full w-full items-start" v-if="closable">
                    <TimesIcon 
                        :class="[
                            'size-5 flex-shrink-0 hover:cursor-pointer',
                            {
                                'text-green-700': severity === 'success',
                                'text-yellow-700': severity === 'warn',
                                'text-primary-700': severity === 'error',
                                'text-blue-700': severity === 'info',
                            }
                        ]"
                    />
                </div>
            </template>
            <p 
                :class="[
                    'text-base font-bold',
                    {
                        'text-green-700': severity === 'success',
                        'text-yellow-700': severity === 'warn',
                        'text-primary-700': severity === 'error',
                        'text-blue-700': severity === 'info',
                    }
                ]"
            >
                {{ title }}
            </p>
            <slot name="content"></slot>
        </Message>
    </Transition>
</template>
