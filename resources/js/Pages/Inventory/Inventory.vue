<script setup>
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue'
import { TotalGroupsIcon, TotalItemsIcon, TotalStockIcon, EmptyStockIcon } from '@/Components/Icons/solid.jsx';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue'
import InventoryTable from './Partials/InventoryTable.vue'
import InventorySummaryChart from './Partials/InventorySummaryChart.vue'
import RecentKeepHistoryTable from './Partials/RecentKeepHistoryTable.vue'
import TotalStockChart from './Partials/TotalStockChart.vue'
import AddStockForm from './Partials/AddStockForm.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { OrderDeliveredIllus } from '@/Components/Icons/illus'
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    inventories: Array,
    recentKeepHistories: Array,
    categories: Array,
    itemCategories: Array
});

const home = ref({
    label: 'Inventory',
});

const keepHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'item', header: 'Item Name', width: '35', sortable: false},
    {field: 'qty', header: 'Quantity', width: '15', sortable: true},
    {field: 'keep_date', header: 'Date', width: '20', sortable: true},
    {field: 'keep_for', header: 'Keep For', width: '30', sortable: true},
]);

const { flashMessage } = useCustomToast();

const inventories = ref(props.inventories);
const initialInventories = ref(props.inventories);
const rowsPerPage = ref(8);
const inventoriesTotalPages = ref(Math.ceil(props.inventories.length / 4));
const categoryArr = ref(props.categories);
const itemCategoryArr = ref(props.itemCategories);
const addStockFormIsOpen = ref(false);
const selectedGroup = ref(null);
const selectedGroupItems = ref(null);
const selectedCategory = ref(0);

const checkedFilters = ref({
    itemCategory: [],
    stockLevel: [],
});

// Define row type with its options for 'list' variant
const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
};

// When declaring the actions, make sure to set the column property with the same action name to true to display the action button ('list' variant) 
const actions = {
    view: () => ``,
    replenish: () => ``,
    edit: () => ``,
    delete: () => ``,
};

// Get filtered inventories
const getInventories = async (filters = {}, selectedCategory = 0) => {
    try {
        const inventoriesResponse = await axios.get('/inventory/inventory/getInventories', {
            method: 'GET',
            params: {
                checkedFilters: filters,
                selectedCategory: selectedCategory,
            }
        });
        inventories.value = inventoriesResponse.data;
        inventoriesTotalPages.value = Math.ceil(inventories.value.length / 4);
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const applyCategoryFilter = (category) => {
    selectedCategory.value = category;
    getInventories(checkedFilters.value, category);
};

const applyCheckedFilters = (filters) => {
    checkedFilters.value = filters;
    getInventories(filters, selectedCategory.value);
};

onMounted(async () => {
    flashMessage();
});

const recentKeepHistoriesTotalPages = computed(() => {
    return Math.ceil(props.recentKeepHistories.length / rowsPerPage.value);
})

const allInventoryItems = computed(() => {
    return initialInventories.value.flatMap(group => group.inventory_items);
    // return initialInventories.value.reduce((total, group) => total + group.inventory_items.length, 0);
})

const totalStock = computed(() => {
    return initialInventories.value.reduce((overallTotal, group) => {
        return overallTotal + group.inventory_items.reduce((totalStock, item) => totalStock + item.stock_qty, 0);
    }, 0);
})

const totalOutofStockItems = computed(() => {
    return initialInventories.value.reduce((total, group) => {
        return total + group.inventory_items.filter((item) => item.status === 'Out of stock').length;
    }, 0);
})

const outOfStockItems = computed(() => {
    return initialInventories.value.flatMap(group =>
        group.inventory_items.filter(item => item.status === 'Out of stock')
    );
})

const showAddStockForm = (id) => {
    selectedGroup.value = initialInventories.value.find((group) => group.id === id);
    selectedGroupItems.value = selectedGroup.value.inventory_items;
    addStockFormIsOpen.value = true;
}

const hideAddStockForm = () => {
    addStockFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroupItems.value = null;
        selectedGroup.value = null;
    }, 300);
}
</script>

<template>
    <Head title="Inventory" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <div class="flex flex-col justify-center gap-5">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Total Group</span>
                        <span class="text-lg font-medium text-primary-900">{{ initialInventories.length }}</span>
                    </div>
                    <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                        <TotalGroupsIcon class="text-primary-900"/>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Total Item</span>
                        <span class="text-lg font-medium text-primary-900">{{ allInventoryItems.length }}</span>
                    </div>
                    <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                        <TotalItemsIcon class="text-primary-900"/>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Total Stock</span>
                        <span class="text-lg font-medium text-primary-900">{{ totalStock }}</span>
                    </div>
                    <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                        <TotalStockIcon class="text-primary-900"/>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Out of Stock</span>
                        <span class="text-lg font-medium text-primary-600">{{ totalOutofStockItems }}</span>
                    </div>
                    <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                        <EmptyStockIcon class="text-primary-900"/>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <RecentKeepHistoryTable
                    :columns="keepHistoryColumns"
                    :rows="recentKeepHistories"
                    :rowType="rowType"
                    :actions="actions"
                    :totalPages="recentKeepHistoriesTotalPages"
                    :rowsPerPage="rowsPerPage"
                    class="col-span-full md:col-span-8"
                />
    
                <InventorySummaryChart
                    :inventories="allInventoryItems"
                    class="col-span-full md:col-span-4"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <TotalStockChart
                    :inventories="allInventoryItems"
                    class="col-span-full md:col-span-4"
                />
                    
                <div class="col-span-full md:col-span-8 flex flex-col p-6 gap-6 items-center rounded-[5px] border border-red-100 overflow-x-auto">
                    <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Out of Stock Item</span>
                    <div class="flex items-start justify-start self-stretch gap-6" v-if="outOfStockItems.length > 0">
                        <div class="flex flex-col justify-between gap-4 p-3 min-w-48 rounded-[5px] border self-stretch border-primary-50" v-for="(item, index) in outOfStockItems" :key="index">
                            <span class="text-base font-medium text-grey-900">{{ item.item_name }}</span>
                            <div class="flex flex-col items-start self-stretch gap-2">
                                <div class="flex flex-col gap-2 p-3">
                                    <span class="text-sm font-medium text-primary-900">Product affected</span>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-sm border border-grey-100 bg-white"></div>
                                    </div>
                                </div>
                                <Button
                                    :type="'button'"
                                    :size="'md'"
                                    @click="showAddStockForm(item.inventory_id)"
                                >
                                    Replenish
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center items-center gap-2" v-else>
                        <span class="text-sm font-medium text-primary-900">Seems like everything is in stock!</span>
                        <OrderDeliveredIllus />
                    </div>
                </div>
                <Modal 
                    :title="'Add New Stock'"
                    :show="addStockFormIsOpen" 
                    :maxWidth="'lg'" 
                    :closeable="true" 
                    @close="hideAddStockForm"
                >
                    <template v-if="selectedGroup">
                        <AddStockForm 
                            :selectedGroup="selectedGroup" 
                            :selectedGroupItems="selectedGroupItems"
                            @close="hideAddStockForm"
                        />
                    </template>
                </Modal>
            </div>
            <InventoryTable
                :rows="inventories"
                :categoryArr="categoryArr"
                :itemCategoryArr="itemCategoryArr"
                :totalPages="inventoriesTotalPages"
                @applyCategoryFilter="applyCategoryFilter"
                @applyCheckedFilters="applyCheckedFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>
