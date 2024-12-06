<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SalesProductOrder from './Partials/SalesProductOrder.vue';
import SalesGraph from './Partials/SalesGraph.vue';
import TableRoomActivity from './Partials/TableRoomActivity.vue';
import ProductLowStock from './Partials/ProductLowStock.vue';
import OnDutyToday from './Partials/OnDutyToday.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';
import TodayReservation from './Partials/TodayReservation.vue';

const home = ref({
    label: 'Dashboard',
});

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
    sales: {
        type: Number,
        required: true,
    },
    productSold: {
        type: Number,
        required: true,
    },
    order: {
        type: Number,
        required: true,
    },
    compareSold: {
        type: Number,
        required: true,
    },
    compareSale: {
        type: Number,
        required: true,
    },
    compareOrder: {
        type: Number,
        required: true,
    },
    onDuty: {
        type: Array,
        required: true,
    },
    salesGraph: {
        type: Array,
        required: true,
    },
    monthly: {
        type: Array,
        required: true,
    },
    activeTables: {
        type: Array,
        required: true,
    },
    reservations: Array,
    customers: Array,
    tables: Array,
    occupiedTables: Array,
    waiters: Array,
})
const { flashMessage } = useCustomToast();

const activeFilter = ref('month');
const salesGraph = ref(props.salesGraph);
const monthly = ref(props.monthly);
const reservations = ref(props.reservations);
const reservationRowsPerPage = ref(10);
const isLoading = ref(false);

const stockColumn = ref([
    { field: 'product_name', header: 'Product Name', width: '33', sortable: false},    
    { field: 'category', header: 'Category', width: '25', sortable: false},
    { field: 'item_name', header: 'Low in', width: '27', sortable: false},
    { field: 'stock_qty', header: 'Left', width: '15', sortable: false},
])

const reservationColumns = ref([
    { field: 'reservation_no', header: 'No.', width: '9', sortable: false},
    { field: 'res_time', header: 'Time', width: '7', sortable: false},
    { field: 'name', header: 'Name', width: '26', sortable: false},
    { field: 'pax', header: 'Pax', width: '6', sortable: false},
    { field: 'table_no', header: 'Table / Room', width: '19', sortable: false},
    { field: 'phone', header: 'Contact No.', width: '15', sortable: false},
    { field: 'status', header: 'Status', width: '10', sortable: false},
    { field: 'action', header: 'Action', width: '8', edit: false, delete: false, sortable: false},
])

const rowType = {
    rowGroups: false,
    expandable: false, 
    groupRowsBy: "",
};

const actions = {
    edit: () => ``,
    delete: () => ``,
};

const reservationTotalPages = computed(() => Math.ceil(props.reservations.length / reservationRowsPerPage.value))

const filterSales = async (filters = {}) => {
    isLoading.value = true;
    try {
        const response = await axios.get('/dashboard/filterSale', {
            method: 'GET',
            params: {
                activeFilter: filters,
            }
        });
        const totalSales = response.data.totalSales;
        const labelData = response.data.labels;

        salesGraph.value = totalSales;  
        monthly.value = labelData;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

const applyTimeFilter = (filter) => {
    activeFilter.value = filter;
    filterSales(activeFilter.value);
}

const fetchReservations = async () => {
    try {
        const response = await axios.get(route('reservations.getReservations'));
        reservations.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {

    }
};

onMounted(async()=> {
    flashMessage();
})
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <div class="w-full flex flex-col gap-5 p-5">
            <div class="gap-5 md:grid grid-cols-12">
                <div class="flex flex-col gap-5 col-span-full md:col-span-8">
                    <!-- sales, product sold, order today -->
                    <SalesProductOrder 
                        :sales="sales" 
                        :productSold="productSold" 
                        :order="order"
                        :compareSold="compareSold" 
                        :compareSale="compareSale"
                        :compareOrder="compareOrder"
                    />

                    <!-- sales graph -->
                    <SalesGraph 
                        :salesGraph="salesGraph" 
                        :monthly="monthly" 
                        :activeFilter="activeFilter"
                        :isLoading="isLoading"
                        @isLoading="isLoading=$event"
                        @applyTimeFilter="applyTimeFilter"
                    />

                    <!-- product low at stock -->
                    <ProductLowStock 
                        :columns="stockColumn"
                        :rows="products"
                        :rowType="rowType"
                    />
                </div>
                <div class="flex flex-col gap-5 col-span-full md:col-span-4">
                    <!-- table / room activity -->
                    <TableRoomActivity :activeTables="activeTables"/>

                    <!-- on duty today -->
                    <OnDutyToday :onDuty="onDuty"/>
                </div>
            </div>

            <TodayReservation 
                :customers="customers" 
                :tables="tables" 
                :occupiedTables="occupiedTables" 
                :waiters="waiters" 
                :columns="reservationColumns" 
                :rows="reservations" 
                :actions="actions" 
                :rowType="rowType" 
                :totalPages="reservationTotalPages" 
                :rowsPerPage="reservationRowsPerPage"
                @fetchReservations="fetchReservations"
            />
        </div>
    </AuthenticatedLayout>
</template>
