<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from '@/Components/TabView.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AllNotifications from './Partials/AllNotifications.vue';
import InventoryNotifications from './Partials/InventoryNotifications.vue';
import WaiterNotifications from './Partials/WaiterNotifications.vue';
import OrderNotifications from './Partials/OrderNotifications.vue';
import axios from 'axios';

const home = ref({
    label: 'All Notification',
});

const tabs = ref(["All", "Inventory", "Waiter Check in / out", "Table / Room Activity"]);

const props = defineProps({
    notifications: Object,
})

const notifications = ref(props.notifications)
const inventoryNotifications = ref(notifications.value.filter(notification => notification.type.includes('Inventory')));
const waiterNotifications = ref(notifications.value.filter(notification => notification.type.includes('Waiter')))
const orderNotifications = ref(notifications.value.filter(notification => notification.type.includes('Order')))
const checkedFilters = ref({
    dateFilter: [],
    category: [],
})
const filterNotification = async (filters = {}) => {
    try {
        const notificationResponse = await axios.get('/notifications/filterNotification', {
            method: 'GET',
            params: {
                checkedFilters: filters,
            }
        });
        notifications.value = notificationResponse.data;
        inventoryNotifications.value = notifications.value.filter(notification => notification.type.includes('Inventory'));
        waiterNotifications.value = notifications.value.filter(notification => notification.type.includes('Waiter'));
        orderNotifications.value = notifications.value.filter(notification => notification.type.includes('Order'));
    } catch (error) {
        console.error(error);
    }
}

const applyCheckedFilters = (filters) => {
    checkedFilters.value = filters;
    filterNotification(filters);
}
</script>

<template>
    <Head title="All Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <TabView :tabs="tabs">
            <template #all>
                <AllNotifications 
                    :notifications="notifications"
                    @applyCheckedFilters="applyCheckedFilters"
                />
            </template>
            <template #inventory>
                <InventoryNotifications 
                    :notifications="inventoryNotifications"
                />
            </template>
            <template #waiter-check-in-out>
                <WaiterNotifications 
                    :notifications="waiterNotifications"
                />
            </template>
            <template #table-room-activity>
                <OrderNotifications 
                    :notifications="orderNotifications"
                />
            </template>
        </TabView>
        
    </AuthenticatedLayout>
</template>

