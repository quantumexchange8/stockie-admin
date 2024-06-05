<script setup>
import { Link } from '@inertiajs/vue3'
import { sidebarState, rightSidebarState } from '@/Composables'
</script>

<template>
    <div class="flex items-center justify-between flex-shrink-0 p-4" v-show="sidebarState.isOpen">
        <div class="flex gap-[16px]">
            <div 
                class="rounded-[100px] bg-red-900 shadow-[0px_0px_24.2px_0px_rgba(203,60,60,0.30)] 
                        flex w-[46px] pt-[7px] pr-[1.38px] pl-[2px] justify-center items-center
                        hover:cursor-pointer"
                @click="rightSidebarState.isOpen = !rightSidebarState.isOpen"
            >
            </div>
            <div class="flex flex-col">
                <p class="self-stretch text-red-900 text-md font-medium">Bar Admin 1</p>
                <p class="self-stretch text-red-950 text-xs">Bar Admin 1</p>
            </div>
        </div>

        <Link :href="route('dashboard')" class="inline-flex items-center gap-2">
            <span class="sr-only">K-UI</span>
        </Link>
    </div>
</template>
