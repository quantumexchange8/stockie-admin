<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import { sidebarState, rightSidebarState } from '@/Composables'
import { NumberedNotificationIcon, LanguageIcon, LogOutIcon } from '@/Components/Icons/solid';

defineProps({
    title: String
})

onMounted(() => {
    rightSidebarState.isOpen = false
})
</script>

<template>
    <Head :title="title"></Head>
    
    
    <div class="min-h-screen">
        <!-- Sidebar Menu -->
        <Sidebar />

        <!-- Blur Overlay -->
        <div
            v-show="rightSidebarState.isOpen"
            class="fixed inset-0 transform transition-all z-10"
            @click="rightSidebarState.isOpen = !rightSidebarState.isOpen"
        >
            <div class="absolute inset-0 bg-[linear-gradient(0deg,rgba(0,0,0,0.20)_0%,rgba(0,0,0,0.20)_100%)] bg-cover bg-no-repeat bg-center"></div>
        </div>
        <div
            style="transition-property: margin; transition-duration: 150ms"
            :class="[
                'flex flex-col',
                'flex-shrink-0',
                'min-h-screen py-8 pr-6',
                {
                    'lg:ml-[283px]': sidebarState.isOpen,
                    'md:ml-0': !sidebarState.isOpen,
                },
            ]"
        >
            <!-- Page Heading -->
            <div class="flex flex-col backdrop-blur-[25px] shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] rounded-[8px] bg-white">
                <header class="pl-6 flex flex-col items-center gap-[10px]" v-if="$slots.header">
                    <div class="w-full flex flex-col items-end gap-[19px] pt-8">
                        <div class="flex items-center justify-between self-stretch pr-6">
                            <div class="flex items-center gap-[20px]">
                                <button
                                    @click="sidebarState.isOpen = !sidebarState.isOpen"
                                    :srText="sidebarState.isOpen ? 'Close sidebar' : 'Open sidebar'"
                                    class="inline-flex items-center justify-center rounded-md text-gray-400 
                                        hover:text-gray-500 transition duration-150 ease-in-out"
                                >
                                    <svg width="24" height="24" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path
                                            :class="{
                                                hidden: !sidebarState.isOpen,
                                                'inline-flex': sidebarState.isOpen,
                                            }"
                                            d="M3 12H21M3 6H21M3 18H15" 
                                            stroke="#7E171B" 
                                            stroke-width="3.5" 
                                            stroke-linecap="round" 
                                            stroke-linejoin="round"
                                        />
                                        <path 
                                            :class="{
                                                hidden: sidebarState.isOpen,
                                                'inline-flex': !sidebarState.isOpen,
                                            }"
                                            d="M4 6h16M4 12h16M4 18h16"
                                            stroke="#7E171B" 
                                            stroke-width="3.5" 
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </button>
                                <p class="font-medium text-lg text-red-800 leading-tight">
                                    <slot name="header"/>
                                </p>
                            </div>
                            <div class="flex items-start gap-3">
                                <NumberedNotificationIcon 
                                    class="text-red-900 hover:text-red-800"
                                    :notificationValue="10"
                                    aria-hidden="true" 
                                />
                                <LanguageIcon 
                                    class="text-red-900 hover:text-red-800"
                                    aria-hidden="true" 
                                />
                                <LogOutIcon 
                                    class="text-red-900 hover:text-red-800"
                                    aria-hidden="true" 
                                />
                            </div>
                        </div>
                        <div class="bg-red-900 w-full h-[0.5px]"></div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="w-full flex justify-center">
                    <div class="xl:max-w-[1440px] flex flex-col w-full self-center gap-[10px] flex-shrink-1 p-5">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
    
</template>
