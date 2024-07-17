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

// only for 'list' variant of table component
const inventoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'inventory.name', header: '', width: '9', sortable: false},
    {field: 'item_name', header: 'Item Name', width: '38', sortable: true},
    {field: 'item_code', header: 'Code', width: '7', sortable: true},
    {field: 'item_cat_id', header: 'Unit', width: '7', sortable: true},
    {field: 'stock_qty', header: 'Stock', width: '7', sortable: true},
    {field: 'keep', header: 'Keep', width: '7', sortable: true},
    {field: 'status', header: 'Status', width: '10', sortable: true},
    {field: 'action', header: '', width: '15', sortable: false}
]);

const keepHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'item', header: 'Item Name', width: '35', sortable: false},
    {field: 'qty', header: 'Quantity', width: '15', sortable: true},
    {field: 'keep_date', header: 'Date', width: '20', sortable: true},
    {field: 'keep_for', header: 'Keep For', width: '30', sortable: true},
]);

const inventories = ref([]);
const initialInventories = ref([]);
const recentKeepHistories = ref([]);
const inventoriesTotalPages = ref(1);
const recentKeepHistoriesTotalPages = ref(1);
const rowsPerPage = ref(8);
const itemCategoryArr = ref([]);
const addStockFormIsOpen = ref(false);
const selectedGroup = ref(null);

// Define row type with its options for 'list' variant
const rowType = [
    {
        rowGroups: true,
        expandable: true,
        groupRowsBy: 'inventory.name',
    },
    {
        rowGroups: false,
        expandable: false,
        groupRowsBy: '',
    }
]

// When declaring the actions, make sure to set the column property with the same action name to true to display the action button ('list' variant) 
const actions = [
    {
        view: (productId) => `/menu-management/products_details/${productId}`,
        replenish: (productId) => `/menu-management/products_details/${productId}`,
        // For 'grid' variant only has below two
        edit: (productId) => `/menu-management/products/${productId}/edit`,
        delete: (productId) => `/menu-management/products/${productId}/delete`,
    },
    {
        view: (userId) => ``,
        replenish: (userId) => ``,
        edit: (userId) => ``,
        delete: (userId) => ``,
    }
];

// Get filtered inventories
const getInventories = async (filters = {}) => {
    try {
        const inventoriesResponse = await axios.get('/inventory/inventory/getInventories', {
            method: 'GET',
            params: {
                checkedFilters: filters,
            }
        });
        inventories.value = inventoriesResponse.data;
        inventoriesTotalPages.value = Math.ceil(inventories.value.length / rowsPerPage.value);
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const applyFilters = (filters) => {
    getInventories(filters);
};

onMounted(async () => {
    try {
        // Get initial inventories
        const inventoriesResponse = await axios.get('/inventory/inventory/getInventories');
        initialInventories.value = inventoriesResponse.data;
        inventories.value = inventoriesResponse.data;
        inventoriesTotalPages.value = Math.ceil(inventories.value.length / rowsPerPage.value);

        const recentKeepHistoryResponse = await axios.get('/inventory/inventory/getRecentKeepHistory');
        recentKeepHistories.value = recentKeepHistoryResponse.data;
        recentKeepHistoriesTotalPages.value = Math.ceil(recentKeepHistories.value.length / rowsPerPage.value);

        const itemCategoryResponse = await axios.get('/inventory/inventory/getAllItemCategories');
        itemCategoryArr.value = itemCategoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
});

const totalGroups = computed(() => {
    var groups = [];

    initialInventories.value.forEach(item => {
        groups.push(item.inventory.id);
    });

    return [...new Set(groups)].length;
})

const totalItems = computed(() => {
    return initialInventories.value.length;
})

const totalStock = computed(() => {
    var stock = 0;

    initialInventories.value.forEach(item => {
        stock += item.stock_qty;
    });

    return stock;
})

const totalEmptyStockGroups = computed(() => {
    var groups = [];

    initialInventories.value.forEach(item => {
        if (item.stock_qty === 0) {
            groups.push(item.id);
        }
    });

    return groups.length;
})

const outOfStockItems = computed(() => {
    return initialInventories.value.filter(item => item.stock_qty < 1);
})

const showAddStockForm = (group) => {
    selectedGroup.value = group;
    addStockFormIsOpen.value = true;
}

const hideAddStockForm = () => {
    addStockFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroup.value = null;
    }, 300);
}
</script>

<template>
    <Head title="Inventory" />

    <AuthenticatedLayout>
        <template #header>
            Inventory
        </template>

        <div class="flex flex-col justify-center gap-5">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Total Group</span>
                        <span class="text-lg font-medium text-primary-900">{{ totalGroups }}</span>
                    </div>
                    <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                        <TotalGroupsIcon class="text-primary-900"/>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                    <div class="flex flex-col gap-2 items-center md:items-start">
                        <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Total Item</span>
                        <span class="text-lg font-medium text-primary-900">{{ totalItems }}</span>
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
                        <span class="text-lg font-medium text-primary-600">{{ totalEmptyStockGroups }}</span>
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
                    :rowType="rowType[1]"
                    :actions="actions[1]"
                    :totalPages="recentKeepHistoriesTotalPages"
                    :rowsPerPage="rowsPerPage"
                    class="col-span-full md:col-span-8"
                />
    
                <InventorySummaryChart
                    :inventories="initialInventories"
                    class="col-span-full md:col-span-4"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <TotalStockChart
                    :inventories="initialInventories"
                    class="col-span-full md:col-span-4"
                />
                    
                <div class="col-span-full md:col-span-8 flex flex-col p-6 gap-6 items-center rounded-[5px] border border-red-100 overflow-y-auto">
                    <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Out of Stock Item</span>
                    <div class="flex items-start justify-between self-stretch gap-6">
                        <div class="flex flex-col gap-4 p-3 min-w-48 rounded-[5px] border border-primary-50" v-for="(item, index) in outOfStockItems" :key="index">
                            <div class="flex flex-col items-start self-stretch gap-2">
                                <span class="text-base font-medium text-grey-900">{{ item.item_name }}</span>
                                <div class="flex flex-col gap-2 p-3">
                                    <span class="text-sm font-medium text-primary-900">Product affected</span>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-sm border border-grey-100 bg-white"></div>
                                    </div>
                                </div>
                            </div>
                            <Button
                                :type="'button'"
                                :size="'md'"
                                @click="showAddStockForm(item.inventory)"
                            >
                                Replenish
                            </Button>
                            <Modal 
                                :title="'Add New Stock'"
                                :show="addStockFormIsOpen" 
                                :maxWidth="'lg'" 
                                :closeable="true" 
                                @close="hideAddStockForm"
                            >
                                <template v-if="selectedGroup">
                                    <AddStockForm :group="selectedGroup" @close="hideAddStockForm"/>
                                </template>
                            </Modal>
                        </div>
                    </div>
                </div>
            </div>
            <InventoryTable
                :columns="inventoryColumns"
                :rows="inventories"
                :itemCategoryArr="itemCategoryArr"
                :rowType="rowType[0]"
                :actions="actions[0]"
                :totalPages="inventoriesTotalPages"
                :rowsPerPage="rowsPerPage"
                @applyFilters="applyFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>
