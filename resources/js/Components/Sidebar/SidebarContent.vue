<script setup>
// import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
// import { DashboardIcon } from '@/Components/Icons/outline'
// import { TemplateIcon } from '@heroicons/vue/outline'
import DropdownLink from '@/Components/DropdownLink.vue';
import { sidebarState } from '@/Composables'
// import { HomeIcon } from '@/Components/Icons/solid';
</script>

<template>
    <div 
        v-show="sidebarState.isOpen"
        class="flex flex-col gap-[32px] overflow-auto"
    >
        <SidebarLink 
            title="Dashboard" 
            :href="route('dashboard')" 
            :active="route().current('dashboard')"
        >
            <template #icon>
                <!-- <HomeIcon /> -->
            </template>
        </SidebarLink>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">General</p>
            <div class="flex flex-col">
                <SidebarLink 
                    title="Order Management" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Menu Management" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
            </div>
        </div>
        
        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">Operation</p>
            <div class="flex flex-col">
                <SidebarLink 
                    title="Inventory" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Waiter" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Customer" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Table & Room" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">Others</p>
            <div class="flex flex-col">
                <SidebarLink 
                    title="Loyalty Programme" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Summary Reports" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Configuration" 
                    :href="route('dashboard')" 
                >
                    <template #icon>
                        <!-- <DashboardIcon 
                            class="flex-shrink-0 w-6 h-6" 
                            aria-hidden="true" 
                        /> -->
                    </template>
                </SidebarLink>
            </div>
        </div>
        
        <!-- <SidebarLink 
            title="Log Out" 
            :href="route('logout')" 
            method="post" 
            as="button"
        >
            <template #icon>
            </template>
        </SidebarLink> -->
    </div>
</template>
