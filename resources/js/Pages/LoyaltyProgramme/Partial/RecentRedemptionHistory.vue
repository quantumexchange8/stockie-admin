<script setup>
import axios from 'axios';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import RedemptionHistoryTable from './RedemptionHistoryTable.vue';

const props = defineProps({
    redemptionHistories: Array,
    defaultDateFilter: Array
});

const home = ref({
    label: 'Loyalty Programme',
    route: '/loyalty-programme/loyalty-programme'
});
const items = ref([
    { label: 'Recent Redemption' },
]);

const columns = ref([
    {field: 'redemption_date', header: 'Date', width: '20', sortable: false},
    {field: 'redeemable_item', header: 'Redeemed Item', width: '45', sortable: true},
    {field: 'qty', header: 'Quantity', width: '15', sortable: false},
    {field: 'handled_by', header: 'Redeemed by', width: '20', sortable: false},
]);

const redemptionHistories = ref(props.redemptionHistories);
// const totalPages = ref(0);
const rowsPerPage = ref(8);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

const actions = {
    view: () => ``,
    replenish: () => '',
    edit: () => '',
    delete: () => ``,
};

// Get filtered inventories
const getRecentRedemptionHistories = async (filters = {}) => {
    try {
        const redemptionHistoriesResponse = await axios.get(route('loyalty-programme.getRecentRedemptionHistories'), {
            params: {
                dateFilter: filters,
            }
        });

        redemptionHistories.value = redemptionHistoriesResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const totalPages = computed(() => {
    return Math.ceil(redemptionHistories.value.length / rowsPerPage.value);
})

// watch(() => pointHistories.value, (newValue) => {
//     totalPages.value = Math.ceil(newValue.length / rowsPerPage.value);
// })

</script>

<template>
    <Head title="Recent Redemption" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <RedemptionHistoryTable
            :dateFilter="defaultDateFilter.map((date) => { return new Date(date) })"
            :columns="columns"
            :rows="redemptionHistories"
            :rowType="rowType"
            :actions="actions"
            :totalPages="totalPages"
            :rowsPerPage="rowsPerPage"
            @applyDateFilter="getRecentRedemptionHistories($event)"
        />

    </AuthenticatedLayout>
</template>
