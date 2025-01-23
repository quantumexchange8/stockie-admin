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
    ReservationIcon,
    UsersIcon,
    ActivityLogsIcon,
} from "@/Components/Icons/solid";
import { usePage } from "@inertiajs/vue3";
import SidebarTree from "./SidebarTree.vue";

const page = usePage();
const existingPermissions = page.props.auth.user.data.permission;

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
            v-if="existingPermissions?.includes('dashboard')" 

        >
            <template #icon>
                <HomeIcon aria-hidden="true" />
            </template>
        </SidebarLink>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase"
                v-if="existingPermissions?.includes('order-management') || 
                        existingPermissions?.includes('menu-management')"
            >
                General
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    title="Order Management"
                    :href="route('orders')"
                    :active="route().current('orders')"
                    v-if="existingPermissions?.includes('order-management')" 
                >
                    <template #icon>
                        <OrderIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarTree />
                <SidebarLink 
                    title="Menu Management" 
                    :href="route('products')"
                    :active="route().current('products') || route().current('products.showProductDetails')"
                    v-if="existingPermissions?.includes('menu-management')" 
                >
                    <template #icon>
                        <MenuIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase"
                v-if="existingPermissions?.includes('inventory') || 
                    existingPermissions?.includes('waiter') ||
                    existingPermissions?.includes('customer') || 
                    existingPermissions?.includes('table-room') || 
                    existingPermissions?.includes('reservation')"
            >
                Operation
            </p>
            <div class="flex flex-col">
                <SidebarLink 
                    title="Inventory" 
                    :href="route('inventory')"
                    :active="route().current('inventory') || route().current('inventory.viewStockHistories') || 
                    route().current('inventory.viewKeepHistories') || route().current('activeKeptItem')"
                    v-if="existingPermissions?.includes('inventory')" 
                    >
                    <template #icon>
                        <InventoryIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Waiter" 
                    :href="route('waiter')"
                    :active="route().current('waiter') || route().current('waiter.waiter-details')"
                    v-if="existingPermissions?.includes('waiter')" 
                >
                    <template #icon>
                        <WaiterIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Customer" 
                    :href="route('customer')"
                    :active="route().current('customer')"
                    v-if="existingPermissions?.includes('customer')" 
                >
                    <template #icon>
                        <CustomerIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Table & Room" 
                    :href="route('table-room')"
                    :active="route().current('table-room')"
                    v-if="existingPermissions?.includes('table-room')" 
                >
                    <template #icon>
                        <TableRoomIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Reservation" 
                    :href="route('reservations')"
                    :active="route().current('reservations') || route().current('reservations.viewReservationHistory')"
                    v-if="existingPermissions?.includes('reservation')" 
                >
                    <template #icon>
                        <ReservationIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase"
                v-if="existingPermissions?.includes('loyalty-programme') || existingPermissions?.includes('admin-user') ||
                existingPermissions?.includes('sales-analysis') || existingPermissions?.includes('configuration')"
            >
                Others
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    title="Loyalty Programme"
                    :href="route('loyalty-programme')"
                    :active="route().current('loyalty-programme') || route().current('loyalty-programme.tiers.show') || route().current('loyalty-programme.points.show')"
                    v-if="existingPermissions?.includes('loyalty-programme')" 
                >
                    <template #icon>
                        <LoyaltyIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink
                    title="Admin User"
                    :href="route('admin-user')"
                    :active="route().current('admin-user')"
                    v-if="existingPermissions?.includes('admin-user')" 
                >
                    <template #icon>
                        <UsersIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Sales Analysis" 
                    :href="route('summary.report')" 
                    :active="route().current('summary.report')"
                    v-if="existingPermissions?.includes('sales-analysis')" 
                >
                    <template #icon>
                        <SummaryReportIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    title="Activity Logs" 
                    :href="route('activity-logs')" 
                    :active="route().current('activity-logs')"
                    v-if="existingPermissions?.includes('activity-logs')" 
                >
                    <template #icon>
                        <ActivityLogsIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink
                    title="Configuration"
                    :href="route('configurations')"
                    :active="route().current('configurations') || route().current('configurations.productDetails') || route().current('configuration.incentCommDetail')"
                    v-if="existingPermissions?.includes('configuration')" 
                >
                    <template #icon>
                        <ConfigurationIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
            </div>
        </div>
    </div>
</template>
