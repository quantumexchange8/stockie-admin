<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { ref, watch } from 'vue'
import { FilterMatchMode } from 'primevue/api';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import Table from '@/Components/Table.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    errors: Object,
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    rowType: {
        type: Object,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
});


const rows = ref(props.rows);  
const date_filter = ref('');  
const status = ref('');

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const orderStatuses = ref([...new Set(props.rows.map(row => row.status))].map(status => ({
    text: status,
    value: status
})));

const statusfilter = (statusText) => {
    rows.value = props.rows.filter((row) => row.status === statusText);
};

// Get filtered order histories
const getOrderHistories = async (filters = {}) => {
    try {
        const orderHistoryResponse = await axios.get('/order-management/getOrderHistories', {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });
        rows.value = orderHistoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

watch(() => date_filter.value, () => {
    getOrderHistories(date_filter.value);
})

</script>

<template>
    <div class="flex flex-col items-centers gap-6">
        <div class="flex justify-between items-start self-stretch">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
                class="max-w-[309px]"
            />
            <div class="flex items-start gap-3">
                <DateInput
                    :inputName="'date_filter'"
                    :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                    :range="true"
                    class="max-w-[309px]"
                    v-model="date_filter"
                />
                <Dropdown
                    :inputName="'order_status'"
                    :inputArray="orderStatuses"
                    v-model="status"
                    @onChange="statusfilter"
                />
            </div>
        </div>
        <Table 
            ref="orderHistoryTable"
            :variant="'list'"
            :rows="rows"
            :columns="columns"
            :rowType="rowType"
            :searchFilter="true"
            :filters="filters"
            minWidth="min-w-[965px]"
        >
            <template #empty>
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </template>
            <template #created_at="row">
                <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.created_at).format('DD/MM/YYYY, HH:mm') }}</span>
            </template>
            <template #order_no="row">
                <span class="text-grey-900 text-sm font-medium">#{{ row.order_no }}</span>
            </template>
            <template #total_amount="row">
                <span class="text-grey-900 text-sm font-medium">RM {{ row.total_amount }}</span>
            </template>
            <template #waiter="row">
                <div class="flex whitespace-nowrap gap-1 items-center">
                    <div class="size-4 bg-primary-200 rounded-full"></div>
                    <span class="text-grey-900 text-sm font-medium">{{ row.waiter.name }}</span>
                </div>
            </template>
            <template #status="row">
                <Tag
                    :variant="row.status === 'Order Completed' ? 'green' : 'red'"
                    :value="row.status"
                />
            </template>
            <!-- <template #action="row">
                <span class="text-grey-900 text-sm font-medium">{{ row.action }}</span>
            </template> -->
        </Table>
    </div>
</template>
