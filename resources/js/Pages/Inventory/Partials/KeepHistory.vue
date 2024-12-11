<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StockHistoryTable from './StockHistoryTable.vue'
import { FilterMatchMode } from 'primevue/api';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import KeepHistoryTable from './KeepHistoryTable.vue';

const props = defineProps({
    keepHistories: Array
})

const home = ref({
    label: 'Inventory',
    route: '/inventory/inventory'
});
const items = ref([
    { label: 'Keep History' },
]);
// only for 'list' variant of table component
const columns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'item_name', header: 'Item Name', width: '40', sortable: true},
    {field: 'quantity', header: 'Quantity', width: '18', sortable: true},
    {field: 'keep_date', header: 'Date', width: '20', sortable: true},
    {field: 'keep_for', header: 'Keep For', width: '22', sortable: true},
]);

const keepHistories = ref(props.keepHistories);

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
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

// Get filtered inventories
const getKeepHistories = async (filters = {}) => {
    try {
        const keepHistoryResponse = await axios.get('/inventory/inventory/getAllKeepHistory', {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });
        keepHistories.value = keepHistoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

watch(() => date_filter.value, () => {
    getKeepHistories(date_filter.value);
})

</script>

<template>
    <Head title="Keep History" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
                class="col-span-full md:col-span-7"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="col-span-full md:col-span-5"
                v-model="date_filter"
            />
        </div>
        
        <div class="flex flex-col justify-center gap-5">
            <KeepHistoryTable
                :columns="columns"
                :rows="keepHistories"
                :rowType="rowType"
                :filters="filters"
            />
        </div>
    </AuthenticatedLayout>
</template>
