<script setup>
import Button from '@/Components/Button.vue'
// import { MenuFoldLineLeftIcon, MenuFoldLineRightIcon } from '@/Components/Icons/outline'
import { sidebarState } from '@/Composables'
import DropdownLink from '@/Components/DropdownLink.vue';
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import { OutIcon } from '@/Components/Icons/solid';
</script>

<template>
    <div v-show="sidebarState.isOpen">
        <SidebarLink 
            title="Log Out" 
            :href="route('logout')"
            class="" 
        >
            <template #icon>
                <OutIcon 
                    aria-hidden="true" 
                />
            </template>
        </SidebarLink>
    </div>
</template>
