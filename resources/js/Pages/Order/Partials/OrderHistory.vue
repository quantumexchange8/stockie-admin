<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { ref, watch, computed } from 'vue'
import { FilterMatchMode } from 'primevue/api';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import Table from '@/Components/Table.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import OrderInvoice from './OrderInvoice.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';

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
const selectedOrder = ref(null);
const orderInvoiceModalIsOpen = ref(false);
const op = ref(null);

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

const openOverlay = (event, item) => {
    selectedOrder.value = item;
    
    if (selectedOrder.value) {
        op.value.show(event);
    }
};

const closeOverlay = () => {
    selectedOrder.value = null;
    if (op.value) {
        op.value.hide();  // Ensure op.value is not null before calling hide
    }
};

const showOrderInvoiceModal = () => {
    setTimeout(() => {
        orderInvoiceModalIsOpen.value = true;
    }, 100);
};

const hideOrderInvoiceModal = () => {
    setTimeout(() => {
        selectedOrder.value = null;
    }, 200);
    orderInvoiceModalIsOpen.value = false;
};

const sortedRows = computed(() => {
    return rows.value.sort((a, b) => dayjs(b.updated_at).diff(dayjs(a.updated_at)));
});
</script>

<template>
    <div class="flex flex-col items-centers gap-6 overflow-y-auto scrollbar-thin scrollbar-webkit h-full !max-h-[calc(100dvh-17rem)]">
        <div class="flex justify-between items-start">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
                class="max-w-[309px]"
            />
            <div class="flex items-start gap-x-7">
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
        <div class="flex items-center">
            <Table 
                ref="orderHistoryTable"
                :variant="'list'"
                :rows="sortedRows"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :totalPages="totalPages"
                :searchFilter="true"
                :filters="filters"
                minWidth="min-w-[965px]"
            >
                <template #empty>
                    <UndetectableIllus />
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #updated_at="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.updated_at).format('DD/MM/YYYY, HH:mm') }}</span>
                </template>
                <template #table_no="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.order_table.table.table_no }}</span>
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
                <template #action="row">
                    <Button
                        type="button"
                        variant="tertiary"
                        class="!w-fit col-span-3 hover:bg-primary-50"
                        @click="openOverlay($event, row)"
                    >
                        Invoice
                    </Button>
                </template>
            </Table>
        </div>
    </div>

    <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-0">
        <template v-if="selectedOrder">
            <div class="flex flex-col items-center border-2 border-primary-50 rounded-md">
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="showOrderInvoiceModal"
                >
                    <span class="text-grey-700 font-normal">View</span>
                </Button>
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click=""
                >
                    <span class="text-grey-700 font-normal">Download</span>
                </Button>
            </div>
        </template>
    </OverlayPanel>

    <Modal
        maxWidth="sm" 
        closeable
        title="Order History"
        class="[&>div:nth-child(2)>div>div]:p-1 [&>div:nth-child(2)>div>div]:sm:max-w-[420px]"
        :withHeader="false"
        :show="orderInvoiceModalIsOpen"
        @close="hideOrderInvoiceModal"
    >
        <OrderInvoice :order="selectedOrder" />
    </Modal>
</template>
