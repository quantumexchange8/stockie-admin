<script setup>
// import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from "@/Components/Sidebar/SidebarLink.vue";
// import { DashboardIcon } from '@/Components/Icons/outline'
// import { TemplateIcon } from '@heroicons/vue/outline'
import { sidebarState } from "@/Composables";
import {
    HomeIcon,
    OrderIcon,
    MenuIcon,
    SummaryReportIcon,
    ConfigurationIcon,
    WaiterIcon,
    InventoryIcon,
    CustomerIcon,
    TableRoomIcon,
    LoyaltyIcon,
    ReservationIcon,
    UsersIcon,
    ActivityLogsIcon,
    ShiftManagementIcon,
    TransactionListingIcon,
    EInvoiceIcon,
    AllReportIcon,
} from "@/Components/Icons/solid";
import { usePage } from "@inertiajs/vue3";
import SidebarTree from "./SidebarTree.vue";
import { ref } from "vue";
import { wTrans } from "laravel-vue-i18n";

const page = usePage();
const existingPermissions = page.props.auth.user.data.permission;
const expandedKeys = ref({});
const nodes = ref([
            {
                key: '0',
                label: 'Shift Management',
                children: [
                    { key: '1-0', label: 'Shift Control', data: route('shift-management.control'), type: 'url' },
                    { key: '1-1', label: 'Shift Report', data: route('shift-management.record'), type: 'url' },
                ]
            }])

const getShiftManagementNodes = () => {
    const nodeChildren = [];

    if (existingPermissions?.includes('shift-control')) {
        nodeChildren.push({ key: '1-0', label: wTrans('public.shift_control_header'), data: route('shift-management.control'), type: 'url' });
    }

    if (existingPermissions?.includes('shift-record')) {
        nodeChildren.push({ key: nodeChildren.length > 0 ? '1-1' : '1-0', label: wTrans('public.shift_record_header'), data: route('shift-management.record'), type: 'url' });
    }

    const nodeTree = [{
        key: '0',
        label: wTrans('public.shift_management_header'),
        children: nodeChildren
    }];

    return nodeTree;
};

const toggleNode = (node) => {
    if (node.children && node.children.length) {
        // If the node is already expanded, collapse it
        if (expandedKeys.value[node.key]) {
            delete expandedKeys.value[node.key];
        } else {
            // Otherwise, expand the node
            expandedKeys.value[node.key] = true;
        }

        // Recursively toggle children
        for (let child of node.children) {
            toggleNode(child);
        }
    }
};

</script>


<template>
    <div
        v-show="sidebarState.isOpen"
        class="flex flex-col gap-[32px] overflow-auto"
    >
        <SidebarLink
            :title="$t('public.dashboard_header')"
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
                        existingPermissions?.includes('shift-control') ||
                        existingPermissions?.includes('shift-record') ||
                        existingPermissions?.includes('menu-management')"
            >
                {{ $t('public.general_header') }}
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    :title="$t('public.order_management_header')"
                    :href="route('orders')"
                    :active="route().current('orders')"
                    v-if="existingPermissions?.includes('order-management')" 
                >
                    <template #icon>
                        <OrderIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarTree 
                    :value="getShiftManagementNodes()" 
                    :active="route().current('shift-management.control')"
                    :expandedKeys="expandedKeys"
                    v-if="existingPermissions?.includes('shift-control') || existingPermissions?.includes('shift-record')" 
                    @expand="toggleNode"
                >
                    <template #togglericon="slotProps">
                        <ShiftManagementIcon v-if="slotProps.node.key === '0'"/>
                        <div class="size-[25px]" v-else></div>
                    </template>
                </SidebarTree>
                <SidebarLink 
                    :title="$t('public.menu_management_header')" 
                    :href="route('products')"
                    :active="route().current('products') || route().current('products.showProductDetails')"
                    v-if="existingPermissions?.includes('menu-management')" 
                >
                    <template #icon>
                        <MenuIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <!-- Work In Progress -->
                <SidebarLink 
                    :title="$t('public.all_report_header')" 
                    :href="route('report')"
                    :active="route().current('report')"
                    v-if="existingPermissions?.includes('all-report')" 
                >
                    <template #icon>
                        <AllReportIcon aria-hidden="true" />
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
                {{ $t('public.operation_header') }}
            </p>
            <div class="flex flex-col">
                <SidebarLink 
                    :title="$t('public.inventory_header')" 
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
                    :title="$t('public.waiter_header')" 
                    :href="route('waiter')"
                    :active="route().current('waiter') || route().current('waiter.waiter-details')"
                    v-if="existingPermissions?.includes('waiter')" 
                >
                    <template #icon>
                        <WaiterIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    :title="$t('public.customer_header')" 
                    :href="route('customer')"
                    :active="route().current('customer')"
                    v-if="existingPermissions?.includes('customer')" 
                >
                    <template #icon>
                        <CustomerIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    :title="$t('public.table_room_header')" 
                    :href="route('table-room')"
                    :active="route().current('table-room')"
                    v-if="existingPermissions?.includes('table-room')" 
                >
                    <template #icon>
                        <TableRoomIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    :title="$t('public.reservation_header')" 
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
                v-if="existingPermissions?.includes('transaction-listing') || existingPermissions?.includes('admin-user') ||
                existingPermissions?.includes('einvoice-submission') || existingPermissions?.includes('configuration')"
            >
                {{ $t('public.sales_management_header') }}
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    :title="$t('public.transaction_listing_header')"
                    :href="route('transactions.transaction-listing')"
                    :active="route().current('transactions.transaction-listing')"
                    v-if="existingPermissions?.includes('transaction-listing')" 
                >
                    <template #icon>
                        <TransactionListingIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <!-- <SidebarLink
                    :title="$t('public.einvoice_submission_header')"
                    :href="route('e-invoice.einvoice-listing')"
                    :active="route().current('e-invoice.einvoice-listing')"
                    v-if="existingPermissions?.includes('einvoice-submission')" 
                >
                    <template #icon>
                        <EInvoiceIcon aria-hidden="true" />
                    </template>
                </SidebarLink> -->
            </div>
        </div>

        <div class="flex flex-col gap-[24px]">
            <p class="text-grey-300 text-xs tracking-[4.32px] uppercase"
                v-if="existingPermissions?.includes('loyalty-programme') || existingPermissions?.includes('admin-user') ||
                existingPermissions?.includes('sales-analysis') || existingPermissions?.includes('configuration')"
            >
                {{ $t('public.others_header') }}
            </p>
            <div class="flex flex-col">
                <SidebarLink
                    :title="$t('public.loyalty_programme_header')"
                    :href="route('loyalty-programme')"
                    :active="route().current('loyalty-programme') || route().current('loyalty-programme.tiers.show') || route().current('loyalty-programme.points.show')"
                    v-if="existingPermissions?.includes('loyalty-programme')" 
                >
                    <template #icon>
                        <LoyaltyIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink
                    :title="$t('public.admin_user_header')"
                    :href="route('admin-user')"
                    :active="route().current('admin-user')"
                    v-if="existingPermissions?.includes('admin-user')" 
                >
                    <template #icon>
                        <UsersIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    :title="$t('public.sales_analysis_header')" 
                    :href="route('summary.report')" 
                    :active="route().current('summary.report')"
                    v-if="existingPermissions?.includes('sales-analysis')" 
                >
                    <template #icon>
                        <SummaryReportIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink 
                    :title="$t('public.activity_logs_header')" 
                    :href="route('activity-logs')" 
                    :active="route().current('activity-logs')"
                    v-if="existingPermissions?.includes('activity-logs')" 
                >
                    <template #icon>
                        <ActivityLogsIcon aria-hidden="true" />
                    </template>
                </SidebarLink>
                <SidebarLink
                    :title="$t('public.configuration_header')"
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
