<script setup>
import { onMounted } from 'vue'
import { rightSidebarState } from '@/Composables'
import RightSidebarHeader from '@/Components/RightSidebar/RightSidebarHeader.vue'

defineProps({
    title: String,
    previousTab: {
        type: Boolean,
        default: false
    }
})

onMounted(() => {
    rightSidebarState.isOpen = true
})
</script>

<template>
    <Teleport to="body">
        <aside
            style="
                transition-property: width, transform;
                transition-duration: 1150ms;
            "
            :class="[
                'fixed inset-y-0 right-0 flex flex-col items-start gap-[8px] w-[507px] max-h-[1024px] bg-[#FFF] self-stretch',
                {
                    'translate-x-0 w-[283px] z-20':
                        rightSidebarState.isOpen,
                    'translate-x-full -right-10 w-0 md:translate-x-0 hidden':
                        !rightSidebarState.isOpen,
                },
            ]"
        >
            <RightSidebarHeader
                :title="title"
                :previousTab="previousTab"
             />
            <slot></slot>
        </aside>
    </Teleport>
</template>
