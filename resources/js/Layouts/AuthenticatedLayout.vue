<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import { sidebarState } from '@/Composables'

defineProps({
    title: String
})

const showingNavigationDropdown = ref(false);
</script>

<template>
    <Head :title="title"></Head>
    
    <div>
        <div class="min-h-fit max-h-[1174px] bg-[#FFF]">
            <Sidebar />

            <div
                style="transition-property: margin; transition-duration: 150ms"
                :class="[
                    'flex flex-col backdrop-blur-[25.700000762939453px] rounded-[8px] bg-white py-[33px] px-[25px]',
                    {
                        'lg:ml-[315px]': sidebarState.isOpen,
                        'md:ml-0': !sidebarState.isOpen,
                    },
                ]"
            >
                <!-- Page Heading -->
                <header class="shadow-[-4px_-9px_36.4px_0px_rgba(199,57,42,0.05)] px-6 pt-8" v-if="$slots.header">
                    <div class="max-w-7xl flex gap-[10px]">
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
                        <slot name="header" />
                    </div>
                    <div class="bg-red-900 w-full h-[0.5px] mx-[9px]"></div>
                </header>

                <!-- Page Content -->
                <main class="max-w-[1440px] flex-1 w-full self-center flex justify-center items-start gap-[10px] flex-shrink">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
