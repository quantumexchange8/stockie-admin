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
import { ExpandIcon, FlipBackwardIcon, SquareStickerIcon, TableSortIcon, WarningIcon } from '@/Components/Icons/solid';
import OrderTables from './Partials/OrderTables.vue';
import OrderHistory from './Partials/OrderHistory.vue';
import { sidebarState } from '@/Composables';
import { TransitionChild, TransitionRoot } from '@headlessui/vue';

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
const isFullScreen = ref(false);

const showOrderHistory = () => {
    orderHistoryIsOpen.value = true;
};

const hideOrderHistory = () => {
    orderHistoryIsOpen.value = false;
};

const enterFullScreen = () => {
    sidebarState.isOpen = !sidebarState.isOpen;
    isFullScreen.value = !isFullScreen.value;
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

    <AuthenticatedLayout v-if="!isFullScreen">
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
                    :variant="'tertiary'"
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
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="md:!w-fit"
                    @click="enterFullScreen"
                >
                    <template #icon>
                        <ExpandIcon />
                    </template>
                    Enter Full Screen
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
                        :isFullScreen="isFullScreen"
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
                        :isFullScreen="isFullScreen"
                        @fetchZones="fetchZones"
                    />
                </template>
            </TabView>
        </div>

    </AuthenticatedLayout>

    <!-- <TransitionRoot
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-300 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
        :show="isFullScreen"
    > -->
        <div class="p-5 bg-[#fcebecd4] h-screen" v-else>
            <div class="inline-flex p-6 flex-col rounded-lg bg-white/70 shadow-[-4px_-9px_36.4px_0_rgba(199,57,42,0.05)] backdrop-blur-[25.700000762939453px] w-full"
            >
                <div class="flex flex-col items-start gap-6">
                    <div class="flex justify-between items-center self-stretch">
                        <div class="flex items-center gap-7">
                            <Button
                                :variant="'tertiary'"
                                :type="'button'"
                                :iconPosition="'left'"
                                iconOnly
                                @click="enterFullScreen"
                                class="!px-6 !py-3"
                            >
                                <template #icon>
                                    <FlipBackwardIcon class="size-6 text-primary-900 " />
                                </template>
                            </Button>
                            <span class="text-primary-900 text-lg font-medium whitespace-nowrap">Order Management</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Button
                                :type="'button'"
                                :variant="'tertiary'"
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
                    </div>

                    <TabView :tabs="tabs">
                        <template #all>
                            <OrderTables 
                                isMainTab 
                                :zones="filteredZones" 
                                :users="users"
                                :customers="customers"
                                :occupiedTables="occupiedTables"
                                :isFullScreen="isFullScreen"
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
                                :isFullScreen="isFullScreen"
                                :customers="customers"
                                :occupiedTables="occupiedTables"
                                @fetchZones="fetchZones"
                            />
                        </template>
                    </TabView>
                </div>
            </div>
        </div>
  <!-- </TransitionRoot> -->

    <!-- <div class="p-5 bg-[#fcebecd4] h-screen" v-else>
        <div class="inline-flex p-6 flex-col rounded-lg bg-white/70 shadow-[-4px_-9px_36.4px_0_rgba(199,57,42,0.05)] backdrop-blur-[25.700000762939453px] w-full"
        >
            <div class="flex flex-col items-start gap-6">
                <div class="flex justify-between items-center self-stretch">
                    <div class="flex items-center gap-7">
                        <Button
                            :variant="'tertiary'"
                            :type="'button'"
                            :iconPosition="'left'"
                            iconOnly
                            @click="enterFullScreen"
                            class="!px-6 !py-3"
                        >
                            <template #icon>
                                <FlipBackwardIcon class="size-6 text-primary-900 " />
                            </template>
                        </Button>
                        <span class="text-primary-900 text-lg font-medium whitespace-nowrap">Order Management</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
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
                </div>

                <TabView :tabs="tabs">
                    <template #all>
                        <OrderTables 
                            isMainTab 
                            :zones="filteredZones" 
                            :users="users"
                            :customers="customers"
                            :occupiedTables="occupiedTables"
                            :isFullScreen="isFullScreen"
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
                            :isFullScreen="isFullScreen"
                            :customers="customers"
                            :occupiedTables="occupiedTables"
                            @fetchZones="fetchZones"
                        />
                    </template>
                </TabView>
            </div>
        </div>
    </div> -->

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
</template>
