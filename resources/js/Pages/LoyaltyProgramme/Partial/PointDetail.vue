<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { UploadIcon } from '@/Components/Icons/solid.jsx';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import RedemptionHistoryTable from './RedemptionHistoryTable.vue';
import PointInfoSection from './PointInfoSection.vue';
import Toast from '@/Components/Toast.vue'
import { useCustomToast, useFileExport } from '@/Composables/index.js';

const props = defineProps({
    redemptionHistories: Array,
    defaultDateFilter: Array,
    redeemableItem: Object,
    inventoryItems: Array
})

const home = ref({
    label: 'Loyalty Programme',
    route: '/loyalty-programme/loyalty-programme'
});

const items = ref([
    { label: 'Redeemable Item Detail' },
]);

const redemptionHistoriesColumns = ref([
    {field: 'redemption_date', header: 'Date', width: '20', sortable: false},
    {field: 'amount', header: 'Redeemed with', width: '45', sortable: true},
    {field: 'redeemed_qty', header: 'Quantity', width: '15', sortable: false},
    {field: 'handled_by', header: 'Redeemed by', width: '20', sortable: false},
]);

const pointItemsColumns = ref([
    {field: 'item', header: 'Item', width: '70', sortable: false},
    {field: 'inventory_item.stock_qty', header: 'Stock', width: '30', sortable: false},
]);

const { flashMessage } = useCustomToast();
const { exportToCSV } = useFileExport();

const redemptionHistories = ref(props.redemptionHistories);
const redemptionHistoriesRowsPerPage = ref(16);
const pointItemsRowsPerPage = ref(4);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

const actions = {
    view: () => ``,
    replenish: () => '',
    edit: () => '',
    delete: (id) => `/loyalty-programme/points/${id}`,
};

const getRecentRedemptionHistories = async (filters = []) => {
    try {
        const pointHistoriesResponse = await axios.get(route('loyalty-programme.getRecentRedemptionHistories', props.redeemableItem.id), {
            params: {
                dateFilter: filters,
            }
        });

        redemptionHistories.value = pointHistoriesResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const csvExport = () => {
    const mappedRedemptions = props.redemptionHistories.map(redemptionHistory => ({
        'Date': dayjs(redemptionHistory.redemption_date).format('DD/MM/YYYY'),
        'Redeemable_Item': redemptionHistory.point.name,
        'Quantity': redemptionHistory.qty,
        'Redeemed_By': redemptionHistory.handled_by,
    }));
    exportToCSV(mappedRedemptions, 'Redeemable Item');
}

const redemptionHistoriesTotalPages = computed(() => {
    return Math.ceil(props.redemptionHistories.length / redemptionHistoriesRowsPerPage.value);
});

const pointItemsTotalPages = computed(() => {
    return Math.ceil(props.redeemableItem.point_items.length / pointItemsRowsPerPage.value);
});

onMounted(async() => {
    flashMessage();
});
</script>

<template>
    <Head title="Redeemable Item Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <Toast />

        <div class="grid grid-cols-1 lg:grid-cols-12 justify-center gap-5 w-full">
            <div class="col-span-full lg:col-span-8 flex flex-col p-6 gap-2.5 items-center rounded-[5px] border border-red-100">
                <div class="flex items-center justify-between w-full">
                    <span class="w-full text-start text-md font-medium text-primary-900 whitespace-nowrap">Redemption History</span>
                    <Menu as="div" class="relative inline-block text-left">
                        <div>
                            <MenuButton
                                class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800"
                            >
                                Export
                                <UploadIcon class="size-4 cursor-pointer"/>
                            </MenuButton>
                        </div>

                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems
                                class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg"
                            >
                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        :class="[
                                            { 'bg-primary-100': active },
                                            { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                        ]"
                                        :disabled="redemptionHistories.length === 0"
                                        @click="csvExport"
                                    >
                                        CSV
                                    </button>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        :class="[
                                            // { 'bg-primary-100': active },
                                            { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                        ]"
                                        :disabled="redemptionHistories.length === 0"
                                    >
                                        PDF
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>

                <RedemptionHistoryTable
                    :dateFilter="defaultDateFilter.map((date) => { return new Date(date) })"
                    :columns="redemptionHistoriesColumns"
                    :rows="redemptionHistories"
                    :totalPages="redemptionHistoriesTotalPages"
                    :rowsPerPage="redemptionHistoriesRowsPerPage"
                    :rowType="rowType"
                    :actions="actions"
                    @applyDateFilter="getRecentRedemptionHistories($event)"
                    class="w-full"
                />
            </div>
            <PointInfoSection
                :redeemableItem="redeemableItem"
                :columns="pointItemsColumns"
                :rows="redeemableItem.point_items"
                :totalPages="pointItemsTotalPages"
                :rowsPerPage="pointItemsRowsPerPage"
                :rowType="rowType"
                :actions="actions"
                :inventoryItems="inventoryItems"
            />
        </div>
    </AuthenticatedLayout>
</template>
