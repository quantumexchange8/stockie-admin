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

const props = defineProps({
    product: Object,
    saleHistories: Array,
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

const productItemsColumns = ref([
    {field: 'item', header: 'Item', width: '70', sortable: false},
    {field: 'inventory_item.stock_qty', header: 'Stock', width: '30', sortable: false},
]);

const defaultLatest30Days = computed(() => {
    let currentDate = dayjs();
    let last30Days = currentDate.subtract(30, 'day');

    return [last30Days.toDate(), currentDate.toDate()];
});

const product = ref(props.product);
const saleHistories = ref(props.saleHistories);
const saleHistoriesRowsPerPage = ref(16);
const productItems = ref(props.product.product_items);
const productItemsRowsPerPage = ref(4);
const categoryArr = ref(props.categories);
const inventoriesArr = ref(props.inventories);
const date_filter = ref(defaultLatest30Days.value);  

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

// Get filtered sale histories
const getSaleHistories = async (filters = {}, id) => {
    try {
        const saleHistoriesResponse = await axios.get(`/menu-management/products/getProductSaleHistories/${id}`, {
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

const arrayToCsv = (data) => {
    const array = [Object.keys(data[0])].concat(data)

    return array.map(it => {
        return Object.values(it).toString()
    }).join('\n');
};

const downloadBlob = (content, filename, contentType) => {
    // Create a blob
    var blob = new Blob([content], { type: contentType });
    var url = URL.createObjectURL(blob);

    // Create a link to download it
    var pom = document.createElement('a');
    pom.href = url;
    pom.setAttribute('download', filename);
    pom.click();
};

const exportToCSV = () => { 
    const saleHistoriesArr = [];
    const currentDateTime = dayjs().format('YYYYMMDDhhmmss');
    const fileName = `Product_${props.product.id}_Sale History_${currentDateTime}.csv`;
    const contentType = 'text/csv;charset=utf-8;';

    if (saleHistories.value && saleHistories.value.length > 0) {
        saleHistories.value.forEach(row => {
            saleHistoriesArr.push({
                'Date': dayjs(row.created_at).format('DD/MM/YYYY'),
                'Time': dayjs(row.created_at).format('hh:mm A'),
                'Amount': 'RM ' + row.total_price.toFixed(2),
                'Quantity': row.qty,
            })
        });

        const myLogs = arrayToCsv(saleHistoriesArr);
        
        downloadBlob(myLogs, fileName, contentType);
        console.log("Sale Histories has been saved");
    }
}

const productItemsTotalPages = computed(() => {
    return Math.ceil(product.value.length / productItemsRowsPerPage.value);
})

const saleHistoriesTotalPages = computed(() => {
    return Math.ceil(saleHistories.value.length / saleHistoriesRowsPerPage.value);
})

watch(() => date_filter.value, () => {
    getSaleHistories(date_filter.value, props.product.id);
})

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
        <div class="grid grid-cols-1 lg:grid-cols-12 justify-center gap-5 w-full">
            <div class="col-span-full lg:col-span-8 flex flex-col p-6 gap-2.5 items-center rounded-[5px] border border-red-100">
                <div class="flex items-center justify-between w-full">
                    <span class="w-full text-start text-md font-medium text-primary-900 whitespace-nowrap">Sale Histories</span>
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
                                            { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                        ]"
                                        :disabled="saleHistories.length === 0"
                                        @click="exportToCSV"
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
                </div>
                <div class="w-full grid grid-cols-1 lg:grid-cols-12">
                    <DateInput
                        :inputName="'date_filter'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        class="col-span-full lg:col-span-6"
                        v-model="date_filter"
                    />
                </div>

                <SaleHistoryTable
                    :dateFilter="defaultDateFilter.map((date) => { return new Date(date) })"
                    :columns="saleHistoriesColumns"
                    :rows="saleHistories"
                    :totalPages="saleHistoriesTotalPages"
                    :rowsPerPage="saleHistoriesRowsPerPage"
                    :rowType="rowType"
                />
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

        

        <!-- <OverlayPanel 
            ref="op" 
            appendTo="body"
        :pt="{
            root: {
                class: [
                    // Shape
                    'rounded-[5px] shadow-lg border-0',

                    // Position
                    'absolute left-0 top-0 mt-2',
                    'z-40 transform origin-center',

                    // Color
                    'bg-white',
                ]
            },
            content: {
                class: 'p-1 w-full gap-0.5 min-w-24'
            },
            transition: {
                enterFromClass: 'opacity-0 scale-y-[0.8]',
                enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
                leaveActiveClass: 'transition-opacity duration-100 ease-linear',
                leaveToClass: 'opacity-0'
            }
        }"
        >
            <div class="flex flex-col items-center justify-start">
                <span class="text-grey-700 text-sm font-normal" @click="exportToCSV">CSV</span>
                <span class="text-grey-700 text-sm font-normal">PDF</span>
                <span class="text-grey-700 text-sm font-normal">Print</span>
            </div>
        </OverlayPanel> -->
    </AuthenticatedLayout>
</template>
