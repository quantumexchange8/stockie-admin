<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { ref, onMounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateInput from '@/Components/Date.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { UploadIcon } from '@/Components/Icons/solid.jsx';
import SaleHistoryTable from './SalesHistoryTable.vue'
import ProductInfoSection from './ProductInfoSection.vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import Toast from '@/Components/Toast.vue';
import { useCustomToast, useFileExport } from '@/Composables/index.js';
import TabView from '@/Components/TabView.vue';
import RedemptionHistoryTable from './RedemptionHistoryTable.vue';

const props = defineProps({
    product: Object,
    saleHistories: Array,
    redemptionHistories: Array,
    defaultDateFilter: Array,
    inventories: Array,
    categories: Array,
})

const home = ref({
    label: 'Menu Management',
    route: '/menu-management/products'
});

const items = ref([
    { label: 'Product Detail' },
]);

const saleHistoriesColumns = ref([
    {field: 'date', header: 'Date', width: '25', sortable: true},
    {field: 'time', header: 'Time', width: '25', sortable: true},
    {field: 'total_price', header: 'Amount', width: '25', sortable: true},
    {field: 'qty', header: 'Quantity', width: '25', sortable: true},
]);

const redemptionHistoriesColumns = ref([
    {field: 'redemption_date', header: 'Date', width: '20', sortable: false},
    {field: 'amount', header: 'Redeemed with', width: '45', sortable: true},
    {field: 'qty', header: 'Quantity', width: '15', sortable: false},
    {field: 'handled_by.name', header: 'Redeemed by', width: '20', sortable: false},
]);

const productItemsColumns = ref([
    {field: 'item', header: 'Item', width: '70', sortable: false},
    {field: 'inventory_item.stock_qty', header: 'Stock', width: '30', sortable: false},
]);

// const defaultLatest30Days = computed(() => {
//     let currentDate = dayjs();
//     let last30Days = currentDate.subtract(30, 'day');

//     return [last30Days.toDate(), currentDate.toDate()];
// });

const { flashMessage } = useCustomToast();
const { exportToCSV } = useFileExport();

const tabs = ref(["Sale Histories", "Redemption Histories"]);
const product = ref(props.product);
const saleHistories = ref(props.saleHistories);
const saleHistoriesRowsPerPage = ref(16);
const redemptionHistories = ref(props.redemptionHistories);
const redemptionHistoriesRowsPerPage = ref(16);
const productItems = ref(props.product.product_items);
const productItemsRowsPerPage = ref(4);
const categoryArr = ref(props.categories);
const inventoriesArr = ref(props.inventories);
// const date_filter = ref(defaultLatest30Days.value);  

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

// Get filtered sale histories
const getProductSaleHistories = async (filters = {}) => {
    try {
        const saleHistoriesResponse = await axios.get(`/menu-management/products/getProductSaleHistories/${props.product.id}`, {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });

        saleHistories.value = saleHistoriesResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const getRedemptionHistories = async (filters = []) => {
    try {
        const redemptionHistoriesResponse = await axios.get(route('products.getRedemptionHistories', props.product.id), {
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

// const csvExport = (type) => {
//     const mappedData =  type === 'sale' 
//             ?   saleHistories.value.map(salesHistory => ({
//                     'Date': dayjs(salesHistory.created_at).format('DD/MM/YYYY'),
//                     'Time': dayjs(salesHistory.created_at).format('hh:mm A'),
//                     'Amount': 'RM ' + salesHistory.total_price.toFixed(2),
//                     'Quantity': salesHistory.qty,
//                 }))
//             :   redemptionHistories.map(redemptionHistory => ({
//                     'Date': dayjs(redemptionHistory.redemption_date).format('DD/MM/YYYY'),
//                     'Redeemable_Item': redemptionHistory.Redeemable_Item.product_name,
//                     'Quantity': redemptionHistory.qty,
//                     'Redeemed_By': redemptionHistory.handled_by,
//                 }));
//     exportToCSV(mappedData, 'Sale History');
// }

const productItemsTotalPages = computed(() => {
    return Math.ceil(product.value.length / productItemsRowsPerPage.value);
})

const saleHistoriesTotalPages = computed(() => {
    return Math.ceil(saleHistories.value.length / saleHistoriesRowsPerPage.value);
})

const redemptionHistoriesTotalPages = computed(() => {
    return Math.ceil(props.redemptionHistories.length / redemptionHistoriesRowsPerPage.value);
});

// watch(() => date_filter.value, () => {
//     getProductSaleHistories(date_filter.value);
// })

onMounted(() => {
    flashMessage();
});

</script>

<template>
    <Head title="Product Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <Toast />

        <div class="grid grid-cols-1 lg:grid-cols-12 justify-center gap-5 w-full">
            <div class="col-span-full lg:col-span-8 flex flex-col p-6 gap-6 items-start rounded-[5px] border border-red-100">
                <TabView :tabs="tabs">
                    <template #sale-histories>
                        <!-- <div class="w-full flex flex-row gap-x-4 justify-between">
                            <DateInput
                                :inputName="'date_filter'"
                                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                :range="true"
                                class="col-span-full lg:col-span-6 w-60"
                                v-model="date_filter"
                            />
                            <Menu as="div" class="relative inline-block text-left col-span-full lg:col-span-2">
                                <div>
                                    <MenuButton
                                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800"
                                    >
                                        Export
                                        <UploadIcon class="size-4 cursor-pointer flex-shrink-0"/>
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
                                            <button
                                                type="button"
                                                :class="[
                                                    { 'bg-primary-100': active },
                                                    { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                                ]"
                                                :disabled="saleHistories.length === 0"
                                                @click="csvExport('sale')"
                                            >
                                                CSV
                                            </button>
                                        </MenuItem>
        
                                        <MenuItem v-slot="{ active }">
                                            <button
                                                type="button"
                                                :class="[
                                                    // { 'bg-primary-100': active },
                                                    { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                                ]"
                                                :disabled="saleHistories.length === 0"
                                            >
                                                PDF
                                            </button>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div> -->
        
                        <SaleHistoryTable
                            :dateFilter="defaultDateFilter.map((date) => { return new Date(date) })"
                            :columns="saleHistoriesColumns"
                            :rows="saleHistories"
                            :totalPages="saleHistoriesTotalPages"
                            :rowsPerPage="saleHistoriesRowsPerPage"
                            :rowType="rowType"
                            class="w-full"
                            @applyDateFilter="getProductSaleHistories($event)"
                        />
                    </template>

                    <template #redemption-histories>
                        <!-- <div class="flex items-center justify-between w-full">
                            <span class="w-full text-start text-md font-medium text-primary-900 whitespace-nowrap">Redemption History</span>
                            <Menu as="div" class="relative inline-block text-left">
                                <div>
                                    <MenuButton
                                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800"
                                    >
                                        Export
                                        <UploadIcon class="size-4 cursor-pointer"/>
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
                                            <button
                                                type="button"
                                                :class="[
                                                    { 'bg-primary-100': active },
                                                    { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                                ]"
                                                :disabled="redemptionHistories.length === 0"
                                                @click="csvExport('redemption')"
                                            >
                                                CSV
                                            </button>
                                        </MenuItem>

                                        <MenuItem v-slot="{ active }">
                                            <button
                                                type="button"
                                                :class="[
                                                    // { 'bg-primary-100': active },
                                                    { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                                ]"
                                                :disabled="redemptionHistories.length === 0"
                                            >
                                                PDF
                                            </button>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div> -->

                        <RedemptionHistoryTable
                            :dateFilter="defaultDateFilter.map((date) => { return new Date(date) })"
                            :columns="redemptionHistoriesColumns"
                            :rows="redemptionHistories"
                            :totalPages="redemptionHistoriesTotalPages"
                            :rowsPerPage="redemptionHistoriesRowsPerPage"
                            :rowType="rowType"
                            class="w-full"
                            @applyDateFilter="getRedemptionHistories($event)"
                        />
                    </template>
                </TabView>

            </div>
            <ProductInfoSection 
                :product="product"
                :columns="productItemsColumns"
                :rows="productItems"
                :totalPages="productItemsTotalPages"
                :rowsPerPage="productItemsRowsPerPage"
                :rowType="rowType"
                :categoryArr="categoryArr"
                :inventoriesArr="inventoriesArr"
            />
        </div>
    </AuthenticatedLayout>
</template>
