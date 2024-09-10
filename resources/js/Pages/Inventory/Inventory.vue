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

// only for 'list' variant of table component
const inventoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'inventory.name', header: '', width: '0', sortable: false},
    {field: 'item_name', header: 'Item Name', width: '29', sortable: true},
    {field: 'item_code', header: 'Code', width: '11', sortable: true},
    {field: 'item_cat_id', header: 'Unit', width: '10', sortable: true},
    {field: 'stock_qty', header: 'Stock', width: '11', sortable: true},
    {field: 'keep', header: 'Keep', width: '12', sortable: true},
    {field: 'status', header: 'Status', width: '12', sortable: true},
    {field: 'action', header: '', width: '15', sortable: false}
]);

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
const inventoriesTotalPages = ref(Math.ceil(props.inventories.length / rowsPerPage.value));
const categoryArr = ref(props.categories);
const itemCategoryArr = ref(props.itemCategories);
const addStockFormIsOpen = ref(false);
const selectedGroupId = ref(null);
const selectedGroup = ref(null);
const selectedCategory = ref(0);

const checkedFilters = ref({
    itemCategory: [],
    stockLevel: [],
});

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
        inventoriesTotalPages.value = Math.ceil(inventories.value.length / rowsPerPage.value);
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
    selectedGroupId.value = group;

    selectedGroup.value = inventories.value.filter((row) => {
        if (row.inventory_id === selectedGroupId.value.id) return row;
    });

    addStockFormIsOpen.value = true;
}

const hideAddStockForm = () => {
    addStockFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroup.value = null;
        selectedGroupId.value = null;
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
                    :actions="actions"
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
                                    @click="showAddStockForm(item.inventory)"
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
                    <template v-if="selectedGroupId">
                        <AddStockForm 
                            :group="selectedGroupId" 
                            :selectedGroup="selectedGroup"
                            @close="hideAddStockForm"
                        />
                    </template>
                </Modal>
            </div>
            <InventoryTable
                :columns="inventoryColumns"
                :rows="inventories"
                :categoryArr="categoryArr"
                :itemCategoryArr="itemCategoryArr"
                :rowType="rowType[0]"
                :actions="actions"
                :totalPages="inventoriesTotalPages"
                :rowsPerPage="rowsPerPage"
                @applyCategoryFilter="applyCategoryFilter"
                @applyCheckedFilters="applyCheckedFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>
