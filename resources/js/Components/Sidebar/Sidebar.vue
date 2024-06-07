<script setup>
import { onMounted } from 'vue'
import { sidebarState } from '@/Composables'
import SidebarHeader from '@/Components/Sidebar/SidebarHeader.vue'
import SidebarContent from '@/Components/Sidebar/SidebarContent.vue'
import SidebarFooter from '@/Components/Sidebar/SidebarFooter.vue'

onMounted(() => {
    window.addEventListener('resize', sidebarState.handleWindowResize)

    if (window.innerWidth <= 1024) {
        sidebarState.isOpen = false
    } else {
        sidebarState.isOpen = true
    }
})
</script>

<template>
    <aside
        style="
            transition-property: width, transform;
            transition-duration: 150ms;
        "
        :class="[
            'fixed inset-y-0 p-8 flex flex-col justify-between w-[251px] overflow-auto scrollbar-thin scrollbar-webkit',
            {
                'translate-x-0 w-[283px] z-20':
                    sidebarState.isOpen,
                '-translate-x-full w-0 md:translate-x-0 z-0 !hidden':
                    !sidebarState.isOpen,
            },
        ]"
    >
        <div class="flex flex-col gap-8">
            <SidebarHeader />
            <SidebarContent />
        </div>

        <!-- <SidebarFooter /> -->
    </aside>
</template>
