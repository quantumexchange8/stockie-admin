<script setup>
import { DetailIcon, UploadIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref, watch } from 'vue';
import dayjs from 'dayjs';
import Table from '@/Components/Table.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import { transactionFormat, useFileExport } from '@/Composables';

const props = defineProps({
    dateFilter: Array,
    order: { 
        type:Array,
        required: true,
    },
    columns: { 
        type:Array,
        required: true,
    },
    actions: { 
        type:Object,
        default: () => {},
    },
    waiter: {
        type: Object,
        required: true,
    },
    rowType: Object,
})
const isDetailModalOpen = ref(false);
const selectedOrder = ref(null);
const order = ref(props.order);
const waiter = ref(props.waiter);
const isLoading = ref(false);
const salesRowsPerPage = ref(11);

const { formatAmount } = transactionFormat();
const { exportToCSV } = useFileExport();

const defaultLatest7Days = computed(() => {
    let currentDate = dayjs();
    let last7Days = currentDate.subtract(7, 'day');

    return [last7Days.toDate(), currentDate.toDate()];
});


const viewOrderDetail = (items) => {
    isDetailModalOpen.value = true;
    selectedOrder.value = items.filter((item) => item.commission > 0);
}

const viewSalesReport = async (filters = {}, id) => {
    isLoading.value = true;
    try {
        const salesReportResponse = await axios.get(`/waiter/salesReport/${id}`, {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });

        order.value = salesReportResponse.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const date_filter = ref(defaultLatest7Days.value);

watch(() => date_filter.value, () => {
    viewSalesReport(date_filter.value, props.waiter.id);
})

const orderColumn = ref([
    {field: 'product_name', header: 'Product Name', width: '37', sortable: true},
    {field: 'serve_qty', header: 'Quantity', width: '20', sortable: true},
    {field: 'price', header: 'Price', width: '20', sortable: true},
    {field: 'commission', header: 'Commission', width: '23', sortable: true},
])

const closeModal = () => {
    isDetailModalOpen.value = false;
}

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const csvExport = () => {
    const waiterName = waiter.value?.full_name || 'Unknown Waiter';
    const mappedOrders = order.value.map(order => ({
        'Date': order.created_at,
        'Amount': 'RM ' + order.total_amount.toFixed(2),
        'Commission': 'RM ' + order.commission,
    }));
    exportToCSV(mappedOrders, `${waiterName}_Daily Sales Report`);
}

const salesTotalPages = computed(() => {
    return Math.ceil(order.value.length / salesRowsPerPage.value);
})

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Daily Sales Report</span>
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton
                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
                        Export
                        <UploadIcon class="size-4 cursor-pointer" />
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
                        <button type="button" :class="[
                            { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': order.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="order.length === 0" @click="csvExport">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': order.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="order.length === 0">
                            PDF
                        </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
            <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap items-center justify-between">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="filters['global'].value"
                />
                <DateInput
                    :inputName="'date_filter'"
                    :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                    :range="true"
                    class="!w-max"
                    v-model="date_filter"
            />
            </div>

        <div class="w-full flex justify-between" v-if="order">
            <Table
                :columns="columns"
                :rows="order"
                :actions="actions"
                :variant="'list'"
                :searchFilter="true"
                :filters="filters"
                :rowType="rowType"
                :totalPages="salesTotalPages"
                :rowsPerPage="salesRowsPerPage"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #created_at="order">
                    <span class="text-grey-900 text-sm font-medium">{{ order.created_at }}</span>
                </template>
                <template #order_no="order">
                    <span class="text-primary-900 text-sm font-medium">{{ order.order_no }}</span>
                </template>
                <template #total_amount="order">
                    <span class="text-grey-900 text-sm font-medium">RM {{ order.total_amount }}</span>
                </template>
                <template #commission="order">
                    <span class="text-grey-900 text-sm font-medium">RM {{ order.commission }}</span>
                </template>
                <template #action="order">
                    <DetailIcon 
                        class="text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="viewOrderDetail(order.items)"
                    />
                </template>
            </Table>
        </div>
    </div>

    <!-- detail modal -->
     <Modal
        :title="'View Detail'"
        :max-width="'md'"
        :closeable="true"
        :show="isDetailModalOpen"
        @close="closeModal"
     >
     <div v-if="selectedOrder">
        <Table
            :columns="orderColumn"
            :rows="selectedOrder"
            :variant="'list'"
            :paginator="false"
        >
            <template #product_name="selectedOrder">
                <div class="flex gap-3 items-center">
                    <img 
                        :src="selectedOrder.image ? selectedOrder.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt=""
                        class="object-contain w-[40px] h-[40px]"
                    >
                    <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ selectedOrder.product_name }}</span>
                </div>
            </template>
            <template #serve_qty="selectedOrder">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ selectedOrder.serve_qty }}</span>
            </template>
            <template #price="selectedOrder">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">RM {{ formatAmount(selectedOrder.price) }}</span>
            </template>
            <template #commission="selectedOrder">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">RM {{ formatAmount(selectedOrder.commission) }}</span>
            </template>
        </Table>
    </div>
     </Modal>
</template>
