<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SalesProductOrder from './SalesProductOrder.vue';
import SalesGraph from './SalesGraph.vue';
import TableRoomActivity from './TableRoomActivity.vue';
import ProductLowStock from './ProductLowStock.vue';
import OnDutyToday from './OnDutyToday.vue';

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

</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

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
                    <SalesGraph />
                </div>
                <div class="flex col-span-4">
                <!-- table / room activity -->
                <TableRoomActivity /></div>
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
                    <OnDutyToday />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
