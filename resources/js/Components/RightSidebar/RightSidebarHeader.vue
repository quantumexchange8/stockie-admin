<script setup>
import { rightSidebarState } from '@/Composables'

defineProps({
    title: String,
    previousTab: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div class="flex justify-between self-stretch px-6 pt-6 pb-3" v-show="rightSidebarState.isOpen">
        <button
            @click="rightSidebarState.isOpen = !rightSidebarState.isOpen"
            class="transition duration-150 ease-in-out"
            v-if="previousTab"
        >
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="24" 
                height="24" 
                viewBox="0 0 24 24" 
                fill="none"
            >
                <path 
                    d="M15 18L9 12L15 6" 
                    stroke="#7E171B" 
                    stroke-width="3" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                />
            </svg>
        </button>
        <p class="text-md text-red-950 font-medium text-center">
            {{ title }}
        </p>
        <div v-if="previousTab"></div>
        <button
            @click="rightSidebarState.isOpen = !rightSidebarState.isOpen"
            class="transition duration-150 ease-in-out"
            v-if="!previousTab"
        >
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="24" 
                height="24" 
                viewBox="0 0 24 24" 
                fill="none"
            >
                <path 
                    d="M17 7L7 17M7 7L17 17" 
                    stroke="#7E171B" 
                    stroke-width="3" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                />
            </svg>
        </button>
    </div>
</template>
