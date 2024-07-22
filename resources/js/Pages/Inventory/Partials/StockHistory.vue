<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StockHistoryTable from './StockHistoryTable.vue'
import TabView from "@/Components/TabView.vue";
import { FilterMatchMode } from 'primevue/api';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';

const tabs = ref(['All', 'In', 'Out']);

// only for 'list' variant of table component
const allStockHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'inventory.name', header: '', width: '0', sortable: false},
    {field: 'inventory_item', header: 'Item Name', width: '40', sortable: true},
    {field: 'old_stock', header: 'Previous Stock', width: '20', sortable: true},
    {field: 'in', header: 'In', width: '10', sortable: true},
    {field: 'out', header: 'Out', width: '10', sortable: true},
    {field: 'current_stock', header: 'Current Stock', width: '20', sortable: true},
]);

const allInStockHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'inventory.name', header: '', width: '0', sortable: false},
    {field: 'inventory_item', header: 'Item Name', width: '40', sortable: true},
    {field: 'old_stock', header: 'Previous Stock', width: '20', sortable: true},
    {field: 'in', header: 'In', width: '20', sortable: true},
    {field: 'current_stock', header: 'Current Stock', width: '20', sortable: true},
]);

const allOutStockHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'inventory.name', header: '', width: '0', sortable: false},
    {field: 'inventory_item', header: 'Item Name', width: '40', sortable: true},
    {field: 'old_stock', header: 'Previous Stock', width: '20', sortable: true},
    {field: 'out', header: 'Out', width: '20', sortable: true},
    {field: 'current_stock', header: 'Current Stock', width: '20', sortable: true},
]);

const stockHistories = ref([]);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const defaultLatest7Days = computed(() => {
    let currentDate = dayjs();
    let last7Days = currentDate.subtract(7, 'day');

    return [last7Days.toDate(), currentDate.toDate()];
});

const date_filter = ref(defaultLatest7Days.value);  

// Define row type with its options for 'list' variant
const rowType = {
    rowGroups: true,
    expandable: false,
    groupRowsBy: 'inventory.name',
}

// Get filtered inventories
const getStockHistories = async (filters = {}) => {
    try {
        const stockHistoryResponse = await axios.get('/inventory/inventory/getAllStockHistory', {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });
        stockHistories.value = stockHistoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(async () => {
    getStockHistories(date_filter.value);
});

watch(() => date_filter.value, () => {
    getStockHistories(date_filter.value);
})

</script>

<template>
    <Head title="Inventory - Stock History" />

    <AuthenticatedLayout>
        <template #header>
            Inventory > Stock History
        </template>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
                class="col-span-full sm:col-span-8"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="col-span-full sm:col-span-4"
                v-model="date_filter"
            />
        </div>
        <TabView :tabs="tabs">
            <template #tab-0>
                <div class="flex flex-col justify-center gap-5">
                    <StockHistoryTable
                        :columns="allStockHistoryColumns"
                        :rows="stockHistories"
                        :rowType="rowType"
                        :filters="filters"
                    />
                </div>
            </template>

            <template #tab-1>
                <div class="flex flex-col justify-center gap-5">
                    <StockHistoryTable
                        :columns="allInStockHistoryColumns"
                        :rows="stockHistories"
                        :rowType="rowType"
                        :filters="filters"
                        :category="'In'"
                    />
                </div>
            </template>

            <template #tab-2>
                <div class="flex flex-col justify-center gap-5">
                    <StockHistoryTable
                        :columns="allOutStockHistoryColumns"
                        :rows="stockHistories"
                        :rowType="rowType"
                        :filters="filters"
                        :category="'Out'"
                    />
                </div>
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
