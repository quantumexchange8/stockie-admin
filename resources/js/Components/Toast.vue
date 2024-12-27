<script setup>
import Toast from 'primevue/toast';
import Button from './Button.vue';
import { TimesIcon, ToastSuccessIcon, ToastInfoIcon, ToastWarnIcon, ToastErrorIcon } from './Icons/solid';
import { ref } from 'vue';
import { TransitionRoot } from '@headlessui/vue'

const props = defineProps({
    actionLabel: String,
    severity: String,
    summary: String,
    detail: String,
    group: String,
    inline: {
        type: Boolean,
        default: false,
    },
    closable: {
        type: Boolean,
        default: true,
    },
});

const inlineMessageIsOpen = ref(true);

</script>

<template>
    <Toast 
        position="top-center" 
        @close="visible = false"
        :pt="{
            root: ({ props, state }) => ({
                class: [
                    'w-[50%] rounded-md',
                    { 'hidden': state.messages.length === 0},
                    { '-translate-x-2/4 !top-20 !z-[1120]': props.position == 'top-center' || props.position == 'bottom-center' }
                ]
            }),
            message: ({ state }) => ({
                class: [
                    'my-4 rounded-[5px] w-full bg-white backdrop-blur-[20px] !gap-4'
                ]
            }),
            container: ({ state, props }) => {
                let severity = props.message ? props.message.severity : '';

                return {
                    class: [
                        '',
                        {
                            'border-green-50 shadow-[0px_4px_44.3px_0px_rgba(0,0,0,0.15),_0px_0px_16.3px_0px_#DDF5D2]': severity === 'success',
                            'border-yellow-50 shadow-[0px_4px_44.3px_0px_rgba(0,0,0,0.15),_0px_0px_16.3px_0px_#FBEAD9]': severity === 'warn',
                            'border-primary-50 shadow-[0px_4px_44.3px_0px_rgba(0,0,0,0.15),_0px_0px_16.3px_0px_#FFE1E2]': severity === 'error',
                            'border-blue-50 shadow-[0px_4px_44.3px_0px_rgba(0,0,0,0.15),_0px_0px_16.3px_0px_#E0EDFE]': severity === 'info',
                        }
                    ]
                }
            },
        }"
    >
        <template #container="{ message, closeCallback }">
            <div class="flex items-center justify-between p-4 gap-4 lg:gap-8 mt-4">
                <div class="flex gap-10 xl:gap-20 items-center justify-between w-full ">
                    <div class="flex items-center gap-3">
                        <ToastSuccessIcon class="flex-shrink-0 size-8" v-if="message.severity == 'success'" />
                        <ToastInfoIcon class="flex-shrink-0 size-8" v-if="message.severity == 'info'" />
                        <ToastWarnIcon class="flex-shrink-0 size-8" v-if="message.severity == 'warn'" />
                        <ToastErrorIcon class="flex-shrink-0 size-8" v-if="message.severity == 'error'" />

                        <!-- <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" v-if="message.severity == 'success'" class="flex-shrink-0">
                            <rect width="32" height="32" rx="16" fill="url(#paint0_linear_29_2289)" fill-opacity="0.6"/>
                            <g filter="url(#filter0_i_29_2289)">
                                <rect x="5" y="5" width="22" height="22" rx="11" fill="#388E22"/>
                                <path d="M20.7273 13L14.7273 19L12 16.2727" stroke="#F0FBEA" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                                <filter id="filter0_i_29_2289" x="5" y="5" width="22" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                    <feOffset dy="4"/>
                                    <feGaussianBlur stdDeviation="2"/>
                                    <feComposite in2="hardAlpha" operator="arithmetic" k2="-1" k3="1"/>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0.358572 0 0 0 0 0.735824 0 0 0 0 0.24664 0 0 0 1 0"/>
                                    <feBlend mode="normal" in2="shape" result="effect1_innerShadow_29_2289"/>
                                </filter>
                                <linearGradient id="paint0_linear_29_2289" x1="16" y1="0" x2="16" y2="32" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#DDF5D2"/>
                                    <stop offset="1" stop-color="#BCECAA"/>
                                </linearGradient>
                            </defs>
                        </svg>

                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" v-if="message.severity == 'warn'" class="flex-shrink-0">
                            <rect width="32" height="32" rx="16" fill="url(#paint0_linear_29_2312)" fill-opacity="0.6"/>
                            <g filter="url(#filter0_i_29_2312)">
                                <rect x="5" y="5" width="22" height="22" rx="11" fill="#D55121"/>
                                <path d="M16 12V16M16 20H16.01" stroke="#FDF6EF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                                <filter id="filter0_i_29_2312" x="5" y="5" width="22" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                    <feOffset dy="4"/>
                                    <feGaussianBlur stdDeviation="2"/>
                                    <feComposite in2="hardAlpha" operator="arithmetic" k2="-1" k3="1"/>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0.894118 0 0 0 0 0.415686 0 0 0 0 0.168627 0 0 0 1 0"/>
                                    <feBlend mode="normal" in2="shape" result="effect1_innerShadow_29_2312"/>
                                </filter>
                                <linearGradient id="paint0_linear_29_2312" x1="16" y1="0" x2="16" y2="32" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#FBEAD9"/>
                                    <stop offset="1" stop-color="#F0B281"/>
                                </linearGradient>
                            </defs>
                        </svg>

                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" v-if="message.severity == 'error'" class="flex-shrink-0">
                            <rect width="32" height="32" rx="16" fill="url(#paint0_linear_29_2336)" fill-opacity="0.6"/>
                            <g filter="url(#filter0_i_29_2336)">
                                <rect x="5" y="5" width="22" height="22" rx="11" fill="#E51D25"/>
                                <path d="M19 13L13 19M13 13L19 19" stroke="#FFF1F2" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                                <filter id="filter0_i_29_2336" x="5" y="5" width="22" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                    <feOffset dy="4"/>
                                    <feGaussianBlur stdDeviation="2"/>
                                    <feComposite in2="hardAlpha" operator="arithmetic" k2="-1" k3="1"/>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0.992627 0 0 0 0 0.273182 0 0 0 0 0.299829 0 0 0 1 0"/>
                                    <feBlend mode="normal" in2="shape" result="effect1_innerShadow_29_2336"/>
                                </filter>
                                <linearGradient id="paint0_linear_29_2336" x1="16" y1="0" x2="16" y2="32" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#FFE1E2"/>
                                    <stop offset="1" stop-color="#FFA1A5"/>
                                </linearGradient>
                            </defs>
                        </svg>

                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" v-if="message.severity == 'info'" class="flex-shrink-0">
                            <rect width="32" height="32" rx="16" fill="url(#paint0_linear_29_2353)" fill-opacity="0.6"/>
                            <g filter="url(#filter0_i_29_2353)">
                                <rect x="5" y="5" width="22" height="22" rx="11" fill="#0C82EB"/>
                                <path d="M16.0098 20V16M16.0098 12H15.9998" stroke="#F0F7FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                                <filter id="filter0_i_29_2353" x="5" y="5" width="22" height="26" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                    <feOffset dy="4"/>
                                    <feGaussianBlur stdDeviation="2"/>
                                    <feComposite in2="hardAlpha" operator="arithmetic" k2="-1" k3="1"/>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0.253935 0 0 0 0 0.655371 0 0 0 0 0.990902 0 0 0 1 0"/>
                                    <feBlend mode="normal" in2="shape" result="effect1_innerShadow_29_2353"/>
                                </filter>
                                <linearGradient id="paint0_linear_29_2353" x1="16" y1="0" x2="16" y2="32" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#E0EDFE"/>
                                    <stop offset="1" stop-color="#7CBEFD"/>
                                </linearGradient>
                            </defs>
                        </svg> -->

                        <div class="flex flex-col items-start justify-center gap-1">
                            <p 
                                class="text-base font-medium"
                                :class="{
                                    'text-green-500': message.severity == 'success',
                                    'text-orange-500': message.severity == 'warn',
                                    'text-primary-500': message.severity == 'error',
                                    'text-blue-500': message.severity == 'info',
                                }"
                            >
                                {{ message.summary ?? '' }}
                            </p>
                            <p class="text-sm font-normal text-grey-700" v-if="message.detail">{{ message.detail ?? '' }}</p>
                        </div>
                    </div>
                    <Button 
                        type="button"
                        size="md"
                        class="!w-fit"
                        :class="{
                            '!bg-green-100 !text-green-700 hover:bg-green-200': message.severity == 'success',
                            '!bg-yellow-100 !text-yellow-700 hover:bg-yellow-200': message.severity == 'warn',
                            '!bg-primary-100 !text-primary-700 hover:bg-primary-200': message.severity == 'error',
                            '!bg-blue-100 !text-blue-700 hover:bg-blue-200': message.severity == 'info',
                        }"
                        @click="closeCallback"
                        v-if="actionLabel"
                    >
                        {{ actionLabel ?? '' }}
                    </Button>
                </div>
                <TimesIcon 
                    class="size-4 text-grey-400 flex-shrink-0 hover:cursor-pointer" 
                    @click="closeCallback"
                    v-if="message.closable"
                />
            </div>
        </template>
    </Toast>

    <TransitionRoot
        :show="inline && inlineMessageIsOpen"
        enter="transition-opacity duration-75"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
    >
        <div 
            class="border rounded-[5px]"
            :class="{
                'border-green-50': severity === 'success',
                'border-yellow-50': severity === 'warn',
                'border-primary-50': severity === 'error',
                'border-blue-50': severity === 'info',
            }"
        >
            <div class="flex items-center justify-between p-4 gap-4 lg:gap-8 w-full rounded-[5px]">
                <div class="flex gap-10 xl:gap-20 items-center justify-between w-full">
                    <div class="flex items-center gap-3">
                        <ToastSuccessIcon class="flex-shrink-0 size-6" v-if="severity == 'success'" />
                        <ToastInfoIcon class="flex-shrink-0 size-6" v-if="severity == 'info'" />
                        <ToastWarnIcon class="flex-shrink-0 size-6" v-if="severity == 'warn'" />
                        <ToastErrorIcon class="flex-shrink-0 size-6" v-if="severity == 'error'" />

                        <div class="flex flex-col items-start justify-center gap-1">
                            <p 
                                class="text-base font-medium"
                                :class="{
                                    'text-green-500': severity == 'success',
                                    'text-orange-500': severity == 'warn',
                                    'text-primary-500': severity == 'error',
                                    'text-blue-500': severity == 'info',
                                }"
                            >
                                {{ summary ?? '' }}
                            </p>
                            <p class="text-sm font-normal text-grey-700" v-if="detail">{{ detail ?? '' }}</p>
                        </div>
                    </div>
                    <Button 
                        type="button"
                        size="md"
                        class="!w-fit"
                        :class="{
                            '!bg-green-100 !text-green-700 hover:bg-green-200': severity == 'success',
                            '!bg-yellow-100 !text-yellow-700 hover:bg-yellow-200': severity == 'warn',
                            '!bg-primary-100 !text-primary-700 hover:bg-primary-200': severity == 'error',
                            '!bg-blue-100 !text-blue-700 hover:bg-blue-200': severity == 'info',
                        }"
                        @click="inlineMessageIsOpen = false"
                        v-if="actionLabel"
                    >
                        {{ actionLabel ?? '' }}
                    </Button>
                </div>
                <TimesIcon 
                    class="size-4 text-grey-400 flex-shrink-0 hover:cursor-pointer" 
                    @click="inlineMessageIsOpen = false"
                    v-if="closable"
                />
            </div>
        </div>
    </TransitionRoot>
</template>
