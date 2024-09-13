<script setup>
// import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from "@/Components/Sidebar/SidebarLink.vue";
// import { DashboardIcon } from '@/Components/Icons/outline'
// import { TemplateIcon } from '@heroicons/vue/outline'
import DropdownLink from "@/Components/DropdownLink.vue";
import { sidebarState } from "@/Composables";
import {
    HomeIcon,
    OrderIcon,
    MenuIcon,
    OperationIcon,
    SummaryReportIcon,
    BillingIcon,
    ConfigurationIcon,
    AdminUserIcon,
    OutIcon,
    WaiterIcon,
    InventoryIcon,
    CustomerIcon,
    TableRoomIcon,
    LoyaltyIcon,
} from "@/Components/Icons/solid";
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
                <HomeIcon aria-hidden="true" />
            </template>
        </SidebarLink>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">
                General
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    title="Order Management"
                    :href="route('orders')"
                    :active="route().current('orders')"
                >
                    <template #icon>
                        <OrderIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Menu Management" 
                    :href="route('products.index')"
                    :active="route().current('products.index')"
                >
                    <template #icon>
                        <MenuIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">
                Operation
            </p>
            <div class="flex flex-col">
                <SidebarLink 
                    title="Inventory" 
                    :href="route('inventory')"
                    :active="route().current('inventory')"
                >
                    <template #icon>
                        <InventoryIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Waiter" 
                    :href="route('waiter')"
                    :active="route().current('waiter')"
                >
                    <template #icon>
                        <WaiterIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Customer" 
                    :href="route('customer')"
                    :active="route().current('customer')">
                    <template #icon>
                        <CustomerIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Table & Room" 
                    :href="route('table-room')"
                    :active="route().current('table-room')"
                >
                    <template #icon>
                        <TableRoomIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase">
                Others
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    title="Loyalty Programme"
                    :href="route('loyalty-programme')"
                    :active="route().current('loyalty-programme')"
                >
                    <template #icon>
                        <LoyaltyIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Summary Reports" 
                    :href="route('summary.report')" 
                    :active="route().current('summary.report')"
                >
                    <template #icon>
                        <SummaryReportIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink
                    title="Configuration"
                    :href="route('configurations')"
                >
                    <template #icon>
                        <ConfigurationIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>
    </div>
</template>
