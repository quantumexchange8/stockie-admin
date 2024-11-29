<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { ref, watch, computed, onMounted } from 'vue'
import { FilterMatchMode } from 'primevue/api';
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue';
import OrderInvoice from './OrderInvoice.vue';
import Dropdown from '@/Components/Dropdown.vue';
import SearchBar from '@/Components/SearchBar.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    // columns: Array,
    // rows: Array,
    // rowType: Object,
    // totalPages: Number,
    // rowsPerPage: Number,
    merchant: Object
});

const rows = ref([]);  
const date_filter = ref('');  
const status = ref('');
const selectedOrder = ref(null);
const orderInvoiceModalIsOpen = ref(false);
const op = ref(null);
const rowsPerPage = ref(6);
const taxes = ref([]);

const columns = ref([
    {field: 'updated_at', header: 'Date & Time', width: '15', sortable: false},
    {field: 'table_no', header: 'Table / Room', width: '14', sortable: false},
    {field: 'order_no', header: 'Order No.', width: '11', sortable: false},
    {field: 'total_amount', header: 'Total', width: '14', sortable: false},
    {field: 'waiter', header: 'Order Completed By', width: '21', sortable: false},
    {field: 'status', header: 'Order Status', width: '14', sortable: false},
    {field: 'action', header: '', width: '11', sortable: false},
]);

const totalPages = computed(() => Math.ceil(rows.value.length / rowsPerPage.value));

const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });

const orderStatuses = ref([...new Set(rows.value.map(row => row.status))].map(status => ({
    text: status,
    value: status
})));

const statusfilter = (statusText) => {
    rows.value = rows.value.filter((row) => row.status === statusText);
};

// Get filtered order histories
const getOrderHistories = async (filters = {}) => {
    try {
        const response = await axios.get('/order-management/getOrderHistories', {
            params: { dateFilter: filters }
        });
        rows.value = response.data.orders;
        taxes.value = response.data.taxes;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => getOrderHistories());

watch(date_filter, (newValue) => getOrderHistories(newValue));

const openOverlay = (event, item) => {
    selectedOrder.value = item;
    if (selectedOrder.value) op.value.show(event);
};

const closeOverlay = () => {
    if (op.value)  op.value.hide();
};

const showOrderInvoiceModal = () => {
    setTimeout(() => orderInvoiceModalIsOpen.value = true, 100);
    closeOverlay();
};

const hideOrderInvoiceModal = () => {
    setTimeout(() => selectedOrder.value = null, 200);
    orderInvoiceModalIsOpen.value = false;
};

const sortedRows = computed(() => {
    return rows.value
            .filter((row) => row.payment && row.payment.status === 'Successful') 
            .sort((a, b) => dayjs(b.updated_at).diff(dayjs(a.updated_at)));
});

const getOrderTableNames = (order_table) => order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '';
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
                    <!-- <span class="text-grey-900 text-sm font-medium">{{ row.order_table.table.table_no }}</span> -->
                    <span class="text-grey-900 text-sm font-medium">{{ getOrderTableNames(row.order_table) }}</span>
                </template>
                <template #order_no="row">
                    <span class="text-grey-900 text-sm font-medium">#{{ row.order_no }}</span>
                </template>
                <template #total_amount="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ row.total_amount }}</span>
                </template>
                <template #waiter="row">
                    <div class="flex whitespace-nowrap gap-1 items-center">
                        <!-- <div class="size-4 bg-primary-200 rounded-full"></div> -->
                        <img 
                            :src="row.waiter.image ? row.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt=""
                            class="size-4 rounded-full"
                        >
                        <span class="text-grey-900 text-sm font-medium">{{ row.waiter.full_name }}</span>
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
        <OrderInvoice 
            :order="selectedOrder" 
            :merchant="merchant" 
            :taxes="taxes" 
        />
    </Modal>
</template>
