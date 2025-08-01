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
import OrderReceipt from './OrderReceipt.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
});

const rows = ref([]);  
const initialRows = ref([]);  
const date_filter = ref('');  
const status = ref('');
const selectedOrderId = ref(null);
const orderInvoiceModalIsOpen = ref(false);
const orderReceiptModalIsOpen = ref(false);
const op = ref(null);
const rowsPerPage = ref(6);
const orderInvoice = ref(null);
const showOrderReceipt = ref(false);
const receiptImage = ref(null);
const searchQuery = ref('');

const columns = ref([
    {field: 'updated_at', header: wTrans('public.date_time'), width: '15', sortable: false},
    {field: 'table_no', header: wTrans('public.order.table_room'), width: '14', sortable: false},
    {field: 'order_no', header: wTrans('public.order_no'), width: '11', sortable: false},
    {field: 'total_amount', header: wTrans('public.total'), width: '14', sortable: false},
    {field: 'waiter.full_name', header: wTrans('public.order.order_completed_by'), width: '21', sortable: false},
    {field: 'status', header: wTrans('public.order.order_status'), width: '14', sortable: false},
    {field: 'action', header: '', width: '11', sortable: false},
]);

const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });

const orderStatuses = ref([
    { text: wTrans('public.order.all_orders'), value: 'All Orders' },
    { text: wTrans('public.order.order_completed'), value: 'Order Completed' },
    { text: wTrans('public.order.order_cancelled'), value: 'Order Cancelled' }
]);

const statusfilter = (statusText) => {
    rows.value = statusText === 'All Orders'
            ? initialRows.value
            : initialRows.value.filter((row) => row.status === statusText);
};

// Get filtered order histories
const getOrderHistories = async (filters = {}) => {
    try {
        const response = await axios.get('/order-management/getOrderHistories', {
            params: { dateFilter: filters }
        });
        initialRows.value = response.data;
        rows.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => getOrderHistories());

watch(date_filter, (newValue) => getOrderHistories(newValue));

const openOverlay = (event, item) => {
    selectedOrderId.value = item.id;
    if (selectedOrderId.value) op.value.show(event);
};

const closeOverlay = () => {
    if (op.value)  op.value.hide();
    // setTimeout(() => showOrderReceipt.value = false, 3000);
};

const showOrderInvoiceModal = () => {
    setTimeout(() => orderInvoiceModalIsOpen.value = true, 100);
    closeOverlay();
};

const hideOrderInvoiceModal = () => {
    setTimeout(() => selectedOrderId.value = null, 200);
    orderInvoiceModalIsOpen.value = false;
};

const showOrderReceiptModal = () => {
    setTimeout(() => orderReceiptModalIsOpen.value = true, 100);
    closeOverlay();
};

const hideOrderReceiptModal = () => {
    setTimeout(() => selectedOrderId.value = null, 200);
    orderReceiptModalIsOpen.value = false;
};

// const sortedRows = computed(() => {
//     return rows.value
//             .filter((row) => row.payment && row.payment.status === 'Successful') 
//             .sort((a, b) => dayjs(b.updated_at).diff(dayjs(a.updated_at)));
// });

const totalPages = computed(() => Math.ceil(rows.value.length / rowsPerPage.value));

const getOrderTableNames = (order_table) => order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '';

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        // If no search query, reset rows to props.rows
        rows.value = initialRows.value;
        return;
    }

    const query = newValue.toLowerCase();

    rows.value = initialRows.value.filter(row => {
        const updatedAt = dayjs(row.updated_at).format('DD/MM/YYYY, HH:mm').toLowerCase();
        const tableNo = getOrderTableNames(row.order_table).toLowerCase();
        const orderNo = row.order_no.toLowerCase();
        const totalAmount = row.total_amount.toString().toLowerCase();
        const waiterFullName = row.waiter.full_name.toLowerCase();
        const status = row.status.toLowerCase();

        return  updatedAt.includes(query) ||
                tableNo.includes(query) ||
                orderNo.includes(query) ||
                totalAmount.includes(query) ||
                waiterFullName.includes(query) ||
                status.includes(query);
    });
}, { immediate: true });

const printInvoiceReceipt = () => {
    showOrderInvoiceModal();
    setTimeout(() => orderInvoice.value.testPrintReceipt(), 200);
}
</script>

<template>
    <div class="flex flex-col items-centers gap-6 overflow-y-auto scrollbar-thin scrollbar-webkit h-full !max-h-[calc(100dvh-9rem)]">
        <div class="flex flex-wrap sm:flex-nowrap justify-between items-start gap-x-7 gap-y-6">
            <SearchBar
                :placeholder="$t('public.order.search_by_order')"
                :showFilter="false"
                v-model="searchQuery"
                class="sm:max-w-[309px]"
            />
            <div class="flex w-full items-start justify-end gap-x-7">
                <DateInput
                    :inputName="'date_filter'"
                    :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                    :range="true"
                    class="w-2/3 sm:w-auto sm:!max-w-[309px]"
                    v-model="date_filter"
                />
                <Dropdown
                    :inputName="'order_status'"
                    :inputArray="orderStatuses"
                    v-model="status"
                    class="w-1/3 sm:w-auto"
                    @onChange="statusfilter"
                />
            </div>
        </div>
        <div class="flex items-center">
            <Table 
                ref="orderHistoryTable"
                :variant="'list'"
                :rows="rows"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :totalPages="totalPages"
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
                <template #waiter.full_name="row">
                    <div class="flex whitespace-nowrap gap-1 items-center">
                        <img 
                            :src="row.waiter.image ? row.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="WaiterImage"
                            class="size-4 rounded-full"
                        >
                        <span class="text-grey-900 text-sm font-medium">{{ row.waiter.full_name }}</span>
                    </div>
                </template>
                <template #status="row">
                    <Tag
                        :variant="row.status === 'Order Completed' 
                                ? 'green' 
                                : row.status === 'Order Cancelled' 
                                    ? 'red'
                                    : 'blue'"
                        :value="row.status === 'Order Completed' 
                                ? $t('public.order.order_completed') 
                                : row.status === 'Order Cancelled' 
                                    ? $t('public.order.order_cancelled')
                                    : $t('public.order.order_merged')"
                    />
                </template>
                <template #action="row">
                    <Button
                        v-if="row.status === 'Order Completed'"
                        type="button"
                        variant="tertiary"
                        class="!w-fit col-span-3 hover:bg-primary-50"
                        @click="openOverlay($event, row)"
                    >
                        {{ $t('public.order.invoice') }}
                    </Button>
                </template>
            </Table>
        </div>
        <!-- <div v-if="receiptImage">
            <h3>Preview:</h3>
            <img :src="receiptImage" alt="Receipt Preview" class="w-full max-w-md" />
        </div>
        <div class="">
            <template v-if="showOrderReceipt">
                <OrderInvoice ref="orderInvoice" :orderId="selectedOrderId" @update:print-preview="receiptImage = $event" />
            </template>
        </div> -->
    </div>

    <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-0">
        <template v-if="selectedOrderId">
            <div class="flex flex-col items-center border-2 border-primary-50 rounded-md">
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="showOrderInvoiceModal"
                >
                    <span class="text-grey-700 font-normal">{{ $t('public.order.view') }}</span>
                </Button>
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="printInvoiceReceipt"
                >
                    <span class="text-grey-700 font-normal">{{ $t('public.print_receipt') }}</span>
                </Button>
            </div>
        </template>
    </OverlayPanel>

    <!-- <div class="hidden">
        <div id="receipt">
        <h2>My Receipt</h2>
        <p>Item 1 - $10</p>
        <p>Item 2 - $5</p>
        <p>Total - $15</p>
        </div>
    </div> -->

    <!-- <Modal
        maxWidth="sm" 
        closeable
        class="[&>div:nth-child(2)>div>div]:p-1 [&>div:nth-child(2)>div>div]:sm:max-w-[420px]"
        :withHeader="false"
        :show="orderReceiptModalIsOpen"
        @close="hideOrderReceiptModal"
    > -->
    <!-- </Modal> -->

    <Modal
        maxWidth="sm" 
        closeable
        class="[&>div:nth-child(2)>div>div]:p-1 [&>div:nth-child(2)>div>div]:sm:max-w-[420px]"
        :withHeader="false"
        :show="orderInvoiceModalIsOpen"
        @close="hideOrderInvoiceModal"
    >
        <OrderInvoice ref="orderInvoice" :orderId="selectedOrderId" @close="hideOrderInvoiceModal" />
    </Modal>
</template>
