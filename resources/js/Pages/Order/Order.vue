<script setup>
import { FilterMatchMode } from 'primevue/api';
import { onMounted, ref, watch, computed } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import TabView from '@/Components/TabView.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { SquareStickerIcon } from '@/Components/Icons/solid';
import OrderTables from './Partials/OrderTables.vue';
import OrderHistory from './Partials/OrderHistory.vue';

const home = ref({
    label: 'Order Management',
});

const props = defineProps({
    zones: Array,
    users: Array,
    // orders: Array,
    occupiedTables: Array,
    customers: Array,
    merchant: Object
});

const zones = ref(props.zones);
const tabs = ref([]);
const orderHistoryIsOpen = ref(false);

const showOrderHistory = () => {
    orderHistoryIsOpen.value = true;
};

const hideOrderHistory = () => {
    orderHistoryIsOpen.value = false;
};

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const fetchZones = async () => {
    try {
        const zonesResponse = await axios.get(route('orders.getAllZones'));
        zones.value = zonesResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
};

const populateTabs = () => {
    tabs.value = ['All'];
    for (const zone of zones.value) {
        if (zone.text) { 
            tabs.value.push(zone.text);
        }
    }
};

watch(() => zones.value, populateTabs, { immediate: true });

// Transform the zones instance's zone text to be lower case and separated by hyphens (-) instead
const tranformedZones = computed(() => {
    return zones.value.map((zone) => {
        zone.name = zone.text.toLowerCase().replace(/ /g,"-");
        
        return zone;
    });
});

const filteredZones = computed(() => {
    // If search is empty then return initial zones
    if (!filters.value['global'].value) {
        return zones.value;
    }

    const searchValue = filters.value['global'].value.toLowerCase();
    
    // Filter by zone tables' table_no
    return zones.value
        .map(zone => {
            const matchingTables = zone.tables.filter(table => 
                table.table_no.toLowerCase().includes(searchValue)
            );
            return matchingTables.length > 0 ? { ...zone, tables: matchingTables } : null;
        })
        .filter(zone => zone !== null);
});

</script>

<template>
    <Head title="Order Management" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <div class="flex flex-col gap-6 justify-center p-1">
            <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-3 rounded-[5px]">
                <SearchBar 
                    :placeholder="'Search'"
                    :inputName="'searchbar'" 
                    :showFilter="false"
                    v-model="filters['global'].value"
                />
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="md:!w-fit"
                    @click="showOrderHistory"
                >
                    <template #icon>
                        <SquareStickerIcon class="w-6 h-6" />
                    </template>
                    View Order History
                </Button>
            </div>

            <TabView :tabs="tabs">
                <template #all>
                    <OrderTables 
                        isMainTab 
                        :zones="filteredZones" 
                        :users="users"
                        :customers="customers"
                        :occupiedTables="occupiedTables"
                        @fetchZones="fetchZones()"
                    />
                </template>
                <template 
                    v-for="zone in tranformedZones" 
                    :key="zone.id" 
                    v-slot:[`${zone.name}`]
                >
                    <OrderTables 
                        :zones="filteredZones" 
                        :activeTab="zone.value" 
                        :zoneName="zone.text"
                        :users="users"
                        :customers="customers"
                        :occupiedTables="occupiedTables"
                        @fetchZones="fetchZones"
                    />
                </template>
            </TabView>
        </div>

        <Modal 
            :maxWidth="'lg'" 
            :closeable="true"
            :title="'Order History'"
            :show="orderHistoryIsOpen"
            @close="hideOrderHistory"
        >
            <OrderHistory
                :merchant="merchant"
            />
        </Modal>
    </AuthenticatedLayout>
</template>
