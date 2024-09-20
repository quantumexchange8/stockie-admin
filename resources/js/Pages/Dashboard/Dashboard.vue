<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SalesProductOrder from './Partials/SalesProductOrder.vue';
import SalesGraph from './Partials/SalesGraph.vue';
import TableRoomActivity from './Partials/TableRoomActivity.vue';
import ProductLowStock from './Partials/ProductLowStock.vue';
import OnDutyToday from './Partials/OnDutyToday.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';

const home = ref({
    label: 'Dashboard',
});

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    sales: {
        type: String,
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
    }
})
const stockColumn = ref([
    { field: 'product_name', header: 'Product Name', width: '33', sortable: false},    
    { field: 'category', header: 'Category', width: '25', sortable: false},
    { field: 'item_name', header: 'Low in', width: '27', sortable: false},
    { field: 'stock_qty', header: 'Left', width: '15', sortable: false},
])

const rowType = {
    rowGroups: false,
    expandable: false, 
    groupRowsBy: "",
};
const activeFilter = ref('month');
const salesGraph = ref(props.salesGraph);
const monthly = ref(props.monthly);
const { flashMessage } = useCustomToast();


const filterSales = async (filters = {}) => {
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

    }
};

const applyTimeFilter = (filter) => {
    activeFilter.value = filter;
    filterSales(activeFilter.value);
}

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

        <div class="w-full flex flex-col gap-[20px] p-[20px]">
            <div class="gap-[20px] grid grid-cols-12 ">
                <div class="flex flex-col gap-[20px] col-span-8">
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
                        @applyTimeFilter="applyTimeFilter"
                    />
                </div>
                <div class="flex col-span-4">
                    <!-- table / room activity -->
                    <TableRoomActivity />
                </div>
            </div>
            <div class="grid grid-cols-12 gap-[20px]">
                <!-- product low at stock -->
                 <div class="col-span-8">
                    <ProductLowStock 
                        :columns="stockColumn"
                        :rows="products"
                        :rowType="rowType"
                    />
                </div>

                <!-- on duty today -->
                 <div class="col-span-4">
                    <OnDutyToday :onDuty="onDuty"/>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
