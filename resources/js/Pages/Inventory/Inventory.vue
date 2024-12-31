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
import { EmptyIllus, OrderDeliveredIllus } from '@/Components/Icons/illus'
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    inventories: Array,
    recentKeepHistories: Array,
    categories: Array,
    itemCategories: Array,
    keepItemsCount: Number,
});

const home = ref({
    label: 'Inventory',
});

const keepHistoryColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'keep_date', header: 'Date', width: '13.3', sortable: false},
    {field: 'keep_item.customer.full_name', header: 'Customer', width: '25', sortable: false},
    {field: 'item_name', header: 'Item Name', width: '38', sortable: false},
    {field: 'qty', header: 'Qty', width: '11.2', sortable: false},
    {field: 'keep_item.expired_to', header: 'Expire On', width: '12.5', sortable: false},
]);

const { flashMessage } = useCustomToast();

const inventories = ref(props.inventories);
const initialInventories = ref(props.inventories);
const inventoriesTotalPages = ref(Math.ceil(props.inventories.length / 4));
const categoryArr = ref(props.categories);
const itemCategoryArr = ref(props.itemCategories);
const addStockFormIsOpen = ref(false);
const selectedGroup = ref(null);
const selectedGroupItems = ref(null);
const selectedCategory = ref(0);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

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

onMounted(() => {
    flashMessage();
});

const allInventoryItems = computed(() => {
    return initialInventories.value.flatMap(group => group.inventory_items);
    // return initialInventories.value.reduce((total, group) => total + group.inventory_items.length, 0);
})

const totalStock = ref(
    initialInventories.value.reduce((overallTotal, group) => {
        return overallTotal + group.inventory_items.reduce((totalStock, item) => {
            return totalStock + item.stock_qty;
        }, 0);
    }, 0)
);

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

const hideAddStockForm = (status) => {
    // addStockFormIsOpen.value = false;
    // setTimeout(() => {
    //     selectedGroupItems.value = null;
    //     selectedGroup.value = null;
    // }, 300);
    switch(status){
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                addStockFormIsOpen.value = false;
                setTimeout(() => {
                    selectedGroupItems.value = null;
                    selectedGroup.value = null;
                }, 300);
            }
            break;
        }
        case 'stay':{
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave':{
            isUnsavedChangesOpen.value = false;
            addStockFormIsOpen.value = false;
        }
    }
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
            <div class="grid grid-cols-12 sm:grid-cols-12 gap-5">
                <div class="col-span-6 flex flex-col gap-5">
                    <div class="col-span-full flex gap-5">
                        <div class="col-span-3 flex flex-col p-5 justify-center items-start gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 h-full">
                            <div class="bg-primary-50 rounded-[5px] flex items-center justify-center gap-2.5 w-16 h-16">
                                <TotalGroupsIcon class="text-primary-900"/>
                            </div>
                            <div class="flex flex-col items-start gap-2 self-stretch">
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Total Group</span>
                                <span class="text-lg font-medium text-primary-900">{{ initialInventories.length }}</span>
                            </div>
                        </div>
                        <div class="col-span-3 flex flex-col p-5 justify-center items-start gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 h-full">
                            <div class="bg-primary-50 rounded-[5px] flex items-center justify-center gap-2.5 w-16 h-16">
                                <TotalItemsIcon class="text-primary-900"/>
                            </div>
                            <div class="flex flex-col items-start gap-2 self-stretch">
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Total Item</span>
                                <span class="text-lg font-medium text-primary-900">{{ allInventoryItems.length }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-full flex gap-5">
                        <div class="col-span-3 flex flex-col p-5 justify-center items-start gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 h-full">
                            <div class="bg-primary-50 rounded-[5px] flex items-center justify-center gap-2.5 w-16 h-16">
                                <TotalStockIcon class="text-primary-900"/>
                            </div>
                            <div class="flex flex-col items-start gap-2 self-stretch">
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Total Stock</span>
                                <span class="text-lg font-medium text-primary-900">{{ totalStock }}</span>
                            </div>
                        </div>
                        <div class="col-span-3 flex flex-col p-5 justify-center items-start gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 h-full">
                            <div class="bg-primary-50 rounded-[5px] flex items-center justify-center gap-2.5 w-16 h-16">
                                <EmptyStockIcon class="text-primary-900"/>
                            </div>
                            <div class="flex flex-col items-start gap-2 self-stretch">
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Out of Stock</span>
                                <span class="text-lg font-medium text-primary-600">{{ totalOutofStockItems }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6">
                    <InventorySummaryChart
                        :inventories="allInventoryItems"
                />
                </div>
                <!-- <div class="col-span-full sm:col-span-3 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
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
                </div> -->
            </div>

            <RecentKeepHistoryTable
                :columns="keepHistoryColumns"
                :rows="recentKeepHistories"
                :rowType="rowType"
                :actions="actions"
            />

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <TotalStockChart
                    :inventories="allInventoryItems"
                    :keepItemsCount="keepItemsCount"
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
                                        <!-- <div class="w-10 h-10 rounded-sm border border-grey-100 bg-white" v-for="(product, subIndex) in item.products" :key="subIndex"></div> -->
                                         <template v-for="(productItem, index) in item.products" :key="index">
                                            <img class="bg-grey-50 border border-grey-200 h-10 w-10" 
                                                :src="productItem.image ? productItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                                alt=""
                                            />
                                         </template>
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
                        <EmptyIllus />
                    </div>
                </div>
                <Modal 
                    :title="'Add New Stock'"
                    :show="addStockFormIsOpen" 
                    :maxWidth="'lg'" 
                    :closeable="true" 
                    @close="hideAddStockForm('close')"
                >
                    <template v-if="selectedGroup">
                        <AddStockForm 
                            :selectedGroup="selectedGroup" 
                            :selectedGroupItems="selectedGroupItems"
                            @close="hideAddStockForm"
                            @isDirty = "isDirty = $event"
                        />
                    </template>
                    <Modal
                        :unsaved="true"
                        :maxWidth="'2xs'"
                        :withHeader="false"
                        :show="isUnsavedChangesOpen"
                        @close="hideAddStockForm('stay')"
                        @leave="hideAddStockForm('leave')"
                    />
                </Modal>
            </div>
            <InventoryTable
                :rows="inventories"
                :itemCategoryArr="itemCategoryArr"
                :categoryArr="categoryArr"
                :totalPages="inventoriesTotalPages"
                @applyCategoryFilter="applyCategoryFilter"
                @applyCheckedFilters="applyCheckedFilters"
            />
        </div>
    </AuthenticatedLayout>
</template>
