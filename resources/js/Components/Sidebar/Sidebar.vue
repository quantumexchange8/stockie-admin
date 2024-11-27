<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue'
import { sidebarState } from '@/Composables'
import SidebarHeader from '@/Components/Sidebar/SidebarHeader.vue'
import SidebarContent from '@/Components/Sidebar/SidebarContent.vue'

const screenWidth = ref(window.innerWidth);

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth)

    if (screenWidth.value <= 1024) {
        sidebarState.isOpen = false
    } else {
        sidebarState.isOpen = true
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScreenWidth);
});

// Define the resize handler
const updateScreenWidth = () => {
    screenWidth.value = window.innerWidth;
    sidebarState.handleWindowResize();
};

watch(screenWidth, (newValue) => {
    sidebarState.handleWindowResize();
});
</script>

<template>
    <aside
        style="
            transition-property: width, transform;
            transition-duration: 150ms;
        "
        :class="[
            'fixed inset-y-0 p-8 flex flex-col w-[251px] backdrop-blur-[25px] bg-white overflow-auto scrollbar-thin scrollbar-webkit',
            {
                'translate-x-0 w-[283px] z-20':
                    sidebarState.isOpen,
                '-translate-x-full w-0 md:translate-x-0 z-0 !hidden':
                    !sidebarState.isOpen,
            },
        ]"
    >
        <div class="flex justify-end" v-if="screenWidth <= 1024">
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
        </div>
        <div class="flex flex-col gap-8">
            <SidebarHeader />
            <SidebarContent/>
        </div>

        <!-- <SidebarFooter /> -->
    </aside>
</template>
