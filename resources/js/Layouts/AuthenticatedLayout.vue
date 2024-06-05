<script setup>
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import { sidebarState, rightSidebarState } from '@/Composables'
import { NotificationIcon, LanguageIcon, LogOutIcon } from '@/Components/Icons/solid';

defineProps({
    title: String
})

</script>

<template>
    <Head :title="title"></Head>
    
    <div>
        <div class="h-[1024px] max-h-[1174px] bg-[#FFF]">
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
                    'flex flex-col backdrop-blur-[25.700000762939453px] my-[33px] mr-[25px] h-full',
                    'shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] rounded-[8px] bg-white',
                    {
                        'lg:ml-[315px]': sidebarState.isOpen,
                        'md:ml-0': !sidebarState.isOpen,
                    },
                ]"
            >
                <!-- Page Heading -->
                <header class="pl-6" v-if="$slots.header">
                    <div class="flex flex-col items-start gap-[19px] self-stretch pt-8">
                        <div class="flex items-center justify-between self-stretch pr-6">
                            <div class="flex items-center gap-[20px]">
                                <button
                                    @click="sidebarState.isOpen = !sidebarState.isOpen"
                                    :srText="sidebarState.isOpen ? 'Close sidebar' : 'Open sidebar'"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 
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
                                <p class="font-medium text-xl text-red-800">
                                    <slot name="header"/>
                                </p>
                            </div>
                            <div class="flex items-start gap-3">
                                <NotificationIcon 
                                    :withNotification="true"
                                    :notificationValue="2"
                                    aria-hidden="true" 
                                />
                                <LanguageIcon 
                                    aria-hidden="true" 
                                />
                                <LogOutIcon 
                                    aria-hidden="true" 
                                />
                            </div>
                        </div>
                        <div class="bg-red-900 w-full h-[0.5px] mx-[9px]"></div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="max-w-[1440px] flex-1 w-full self-center flex justify-center items-start gap-[10px] flex-shrink">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
