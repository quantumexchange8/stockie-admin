<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import Tapbar from '@/Components/Tapbar.vue';
import AddStockForm from './AddStockForm.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SearchBar from "@/Components/SearchBar.vue";
import { EmptyIllus, OrderCompleteIllus } from '@/Components/Icons/illus.jsx';
import { PlusIcon, ReplenishIcon, EditIcon, DeleteIcon, SquareStickerIcon, TableSortIcon } from '@/Components/Icons/solid';
import EditInventoryForm from './EditInventoryForm.vue';
import CreateInventoryForm from './CreateInventoryForm.vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import Paginator from 'primevue/paginator';
import TextInput from '@/Components/TextInput.vue';
import AddItemToMenuForm from './AddItemToMenuForm.vue';

const props = defineProps({
    errors: Object,
    rows: Array,
    categoryArr: Array,
    itemCategoryArr: Array,
    totalPages: Number,
})

const emit = defineEmits(["applyCategoryFilter", "applyCheckedFilters"]);

const categories = ref([]);
const rows = ref(props.rows);

const sortField = ref('');
const sortOrder = ref(1);
const searchQuery = ref('');
const currentPage = ref(1);

const createFormIsOpen = ref(false);
const groupCreatedModalIsOpen = ref(false);
const addAsProductModalIsOpen = ref(false);
const addStockFormIsOpen = ref(false);
const editGroupFormIsOpen = ref(false);
const deleteGroupFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const selectedGroup = ref(null);
const selectedGroupItems = ref(null);
const inventoryToAdd = ref({});
const selectedCategory = ref(0);

const checkedFilters = ref({
    itemCategory: [],
    stockLevel: [],
    keepStatus: [],
});

const stockLevels = ref(['In Stock', 'Low Stock', 'Out of Stock']);
const keepStatusArr = ref(['Active', 'Inactive']);

const openForm = (action, id, event) => {
    isDirty.value = false;
    if (event.target) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    switch (action) {
        case 'create': 
            createFormIsOpen.value = true;
            break;
        case 'group-created': 
            inventoryToAdd.value = event;
            createFormIsOpen.value = false;
            groupCreatedModalIsOpen.value = true;
            break;
        case 'add-as-product': 
            groupCreatedModalIsOpen.value = false;
            setTimeout(() => addAsProductModalIsOpen.value = true, 200);
            break;
        case 'add': 
            addStockFormIsOpen.value = !!id;
            break;
        case 'edit': 
            editGroupFormIsOpen.value = !!id;
            break;
        case 'delete': 
            deleteGroupFormIsOpen.value = !!id;
            break;
    }

    if (id && action !== 'create' && action !== 'add-as-product' && action !== 'group-created') {
        selectedGroup.value = rows.value.find((group) => group.id === id);
        selectedGroupItems.value = action === 'add' || action === 'edit' ? selectedGroup.value.inventory_items : null;
    } 
}

const closeForm = (action, status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value || action === 'add-as-product'){
                isUnsavedChangesOpen.value = true;
            } else {
                if(action === 'create') createFormIsOpen.value = false;
                if(action === 'add-as-product') addAsProductModalIsOpen.value = false;
                if(action === 'add') addStockFormIsOpen.value = false;
                if(action === 'edit') editGroupFormIsOpen.value = false;
                if(action === 'delete') deleteGroupFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;

            if(action === 'create') createFormIsOpen.value = false;
            if(action === 'group-created') groupCreatedModalIsOpen.value = false;
            if(action === 'add-as-product') addAsProductModalIsOpen.value = false;
            if(action === 'add') addStockFormIsOpen.value = false;
            if(action === 'edit') editGroupFormIsOpen.value = false;
            if(action === 'delete') deleteGroupFormIsOpen.value = false;
            
            if (action !== 'create' || action !== 'add-as-product' || action !== 'group-created') {
                // setTimeout(() => {
                //     selectedGroup.value = null;
                //     selectedGroupItems.value = null;
                // }, 300);
        
                if (action === 'edit') selectedGroupItems.value = []; // Ensure form resets to initial state on edit
            }

            break;
        }
    }

}

const resetFilters = () => {
    return {
        itemCategory: [],
        stockLevel: [],
        keepStatus: [],
    };
};

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    // emit('applyCategoryFilter', selectedCategory.value);
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const applyCheckedFilters = (close) => {
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const toggleItemCategory = (value) => {
    const index = checkedFilters.value.itemCategory.indexOf(value);
    if (index > -1) {
        checkedFilters.value.itemCategory.splice(index, 1);
    } else {
        checkedFilters.value.itemCategory.push(value);
    }
};

const toggleStockLevel = (value) => {
    const index = checkedFilters.value.stockLevel.indexOf(value);
    if (index > -1) {
        checkedFilters.value.stockLevel.splice(index, 1);
    } else {
        checkedFilters.value.stockLevel.push(value);
    }
};

const toggleKeepStatus = (value) => {
    const index = checkedFilters.value.keepStatus.indexOf(value);
    if (index > -1) {
        checkedFilters.value.keepStatus.splice(index, 1);
    } else {
        checkedFilters.value.keepStatus.push(value);
    }
};

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        rows.value = props.rows; // If no query, return all rows
        return;
    }

    const query = newValue.toLowerCase(); // Normalize the query for case-insensitive search

    // Filter rows based on whether any inventory_item field matches the search query
    rows.value = props.rows
        .map(group => {
            // Filter inventory items in the group
            const filteredItems = group.inventory_items.filter(item => {
                const groupName = group.name.toLowerCase();
                const itemName = item.item_name.toLowerCase();
                const itemCode = item.item_code.toLowerCase();
                const itemCategory = item.item_category.name.toLowerCase();
                const itemStatus = item.status.toLowerCase();
                const stockQty = item.stock_qty.toString().toLowerCase();
                return groupName.includes(query) || 
                        itemName.includes(query) ||
                        itemCode.includes(query) ||
                        itemCategory.includes(query) ||
                        itemStatus.includes(query) ||
                        stockQty.includes(query);
            });

            // Return the group with only matching items
            return filteredItems.length > 0 ? { ...group, inventory_items: filteredItems } : null;
        })
        .filter(group => group !== null); // Remove null entries (groups with no matching items)
}, { immediate: true })

const sortInventories = (field) => {
    // Toggle or set the sort order
    if (sortField.value === field) {
        sortOrder.value = -sortOrder.value;
    } else {
        sortField.value = field;
        sortOrder.value = 1;
    }

    // Helper function to compare values based on type (string, number, date)
    const compareValues = (valueA, valueB) => {
        if (typeof valueA === 'string') {
            return valueA.localeCompare(valueB) * sortOrder.value;
        } else if (typeof valueA === 'number') {
            return (valueA - valueB) * sortOrder.value;
        } else if (Date.parse(valueA)) {
            return (new Date(valueA) - new Date(valueB)) * sortOrder.value;
        }
        return 0; // Default case if no specific comparison is needed
    };

    // Sort function for 'stock_qty', 'item_category', or other fields
    switch (field) {
        case 'stock_qty':
            // Sort groups by total stock quantity
            props.rows.sort((a, b) => {
                const totalStockA = a.inventory_items.reduce((sum, item) => sum + item.stock_qty, 0);
                const totalStockB = b.inventory_items.reduce((sum, item) => sum + item.stock_qty, 0);
                return compareValues(totalStockA, totalStockB);
            });

            // Sort items within each group by individual stock quantity
            props.rows.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.stock_qty, b.stock_qty));
            });
            break;

        case 'item_category':
            // Sort items within each group by item category name
            props.rows.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.item_category.name.toLowerCase(), b.item_category.name.toLowerCase()));
            });
            break;

        case 'status':
            // Sort items within each group by status
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.status.toLowerCase(), b.status.toLowerCase()));
            });
            break;

        case 'item_code':
            // Sort items within each group by item_code alphabetically
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.item_code.toLowerCase(), b.item_code.toLowerCase()));
            });
            break;

        case 'name':
            // Sort items within each group by item_code alphabetically
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.item_name.toLowerCase(), b.item_name.toLowerCase()));
            });
            props.rows.sort((a, b) => {
                const valueA = a.inventory_items[0][field]?.toLowerCase?.() || a[field]?.toLowerCase?.() || '';
                const valueB = b.inventory_items[0][field]?.toLowerCase?.() || b[field]?.toLowerCase?.() || '';
                return compareValues(valueA, valueB);
            });
            break;

        // default:
        //     // Default sorting for other fields (e.g., 'item_name', 'item_code', etc.)
        //     props.rows.sort((a, b) => {
        //         const valueA = a.inventory_items[0][field]?.toLowerCase?.() || a[field]?.toLowerCase?.() || '';
        //         const valueB = b.inventory_items[0][field]?.toLowerCase?.() || b[field]?.toLowerCase?.() || '';
        //         return compareValues(valueA, valueB);
        //     });
        //     break;
    }
};

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
};

const goToPage = (event) => {
    const page = parseInt(event.target.value);
    if (page > 0 && page <= props.totalPages) {
        currentPage.value = page;
    }
}

watch(() => props.rows, (newValue) => {
    rows.value = newValue;
}, { immediate: true });

const computedRowsPerPage = computed(() => {
    const start = (currentPage.value - 1) * 4;
    const end = start + 4;
    return rows.value.slice(start, end);
});

const totalInventoryItemStock = (items) => {
    // Check if items is an array and not empty
    if (!Array.isArray(items) || items.length === 0) {
        console.error('Invalid or empty items array');
        return 0;
    }

    // Calculate the total stock quantity
    const total = items.reduce((total, item) => {
        // Ensure stock_qty is a number before adding
        const stock = parseInt(item.stock_qty, 10) || 0;
        return total + stock;
    }, 0);
    
    return total;
};
</script>

<template>
    <div class="flex flex-col p-6 gap-6 justify-center rounded-[5px] border border-red-100">
        <!-- <Tapbar
            :optionArr="categories"
            :checked="selectedCategory"
            @update:checked="handleFilterChange"
        /> -->
        <div class="flex flex-col justify-center gap-4">
            <div class="flex flex-wrap lg:flex-nowrap items-center justify-between gap-6 rounded-[5px]">
                <SearchBar
                    placeholder="Search"
                    :showFilter="true"
                    v-model="searchQuery"
                >
                    <template #default="{ hideOverlay }">
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Unit Type</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(category, index) in itemCategoryArr" 
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox 
                                        :checked="checkedFilters.itemCategory.includes(category.value)"
                                        @click="toggleItemCategory(category.value)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ category.text }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Stock Level</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(level, index) in stockLevels"
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]" 
                                >
                                    <Checkbox 
                                        :checked="checkedFilters.stockLevel.includes(level)"
                                        @click="toggleStockLevel(level)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ level }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Keep</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(status, index) in keepStatusArr" 
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox 
                                        :checked="checkedFilters.keepStatus.includes(status)"
                                        @click="toggleKeepStatus(status)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ status }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                            <Button
                                :type="'button'"
                                :variant="'tertiary'"
                                :size="'lg'"
                                @click="clearFilters(hideOverlay)"
                            >
                                Clear All
                            </Button>
                            <Button
                                :size="'lg'"
                                @click="applyCheckedFilters(hideOverlay)"
                            >
                                Apply
                            </Button>
                        </div>
                    </template>
                </SearchBar>
                <div class="w-full flex items-center justify-center gap-3">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        :href="route('inventory.viewStockHistories')"
                        class="w-full"
                    >
                        <template #icon>
                            <SquareStickerIcon class="w-6 h-6" />
                        </template>
                        View Stock History
                    </Button>
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="w-full"
                        @click="openForm('create', null, $event)"
                    >
                        <template #icon>
                            <PlusIcon
                                class="w-6 h-6"
                            />
                        </template>
                        New Group
                    </Button>
                </div>
            </div>

            <div class="w-full overflow-auto">
                <table class="w-full border-spacing-3 border-collapse min-w-[755px]">
                    <thead class="w-full bg-primary-50">
                        <tr>
                            <th class="w-[5%] py-2 px-3 rounded-l-[5px]"></th>
                            <th class="w-[26%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('name')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Item Name
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0"/>
                                </span>
                            </th>
                            <th class="w-[11%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('item_code')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Code
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                </span>
                            </th>
                            <th class="w-[10%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('item_category')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Unit
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                </span>
                            </th>
                            <th class="w-[11%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('stock_qty')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Stock
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                </span>
                            </th>
                            <th class="w-[11%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('name')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Keep
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                </span>
                            </th>
                            <th class="w-[14%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('status')">
                                <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                    Status
                                    <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                </span>
                            </th>
                            <th class="w-[12%] py-2 px-3 rounded-r-[5px]"></th>
                        </tr>
                    </thead>
                    <tbody class="w-full before:content-['@'] before:table-row before:leading-3 before:indent[-99999px] before:invisible">
                        <tr 
                            v-if="rows.length > 0" 
                            v-for="(group, groupIndex) in computedRowsPerPage" :key="groupIndex" 
                            class="rounded-[5px]"
                            :class="groupIndex % 2 === 0 ? 'bg-white' : 'bg-primary-25'"
                        >
                            <td colspan="8" class="p-0">
                                <Disclosure 
                                    as="div" 
                                    :defaultOpen="false"
                                    v-slot="{ open }" 
                                    class="flex flex-col justify-center"
                                >
                                    <DisclosureButton class="flex items-center justify-between gap-2.5 rounded-sm py-1 hover:bg-primary-50">
                                        <table class="w-full border-spacing-1 border-separate">
                                            <tbody class="w-full">
                                                <tr>
                                                    <td class="w-[5%]">
                                                        <svg 
                                                            width="20" 
                                                            height="20" 
                                                            viewBox="0 0 20 20" 
                                                            fill="none" 
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="inline-block text-grey-900 transition ease-in-out"
                                                            :class="[open ? '' : '-rotate-90']"
                                                        >
                                                            <path d="M15.8337 7.08337L10.0003 12.9167L4.16699 7.08337" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                                                        </svg>
                                                    </td>
                                                    <td class="w-[79%]">
                                                        <div class="flex items-center gap-3">
                                                            <img :src="group.inventory_image ? group.inventory_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                                alt="" 
                                                                class="w-[60px] h-[60px] flex-shrink-0 rounded-full object-contain" 
                                                            />
                                                            <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ group.name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="w-[8%]">
                                                        <div class="flex justify-end items-start gap-2">
                                                            <ReplenishIcon
                                                                class="w-6 h-6 block transition duration-150 ease-in-out text-primary-900 hover:text-primary-800 cursor-pointer"
                                                                @click="openForm('add', group.id, $event)"
                                                            />
                                                            <EditIcon
                                                                class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                                                @click="openForm('edit', group.id, $event)"
                                                            />
                                                            <DeleteIcon
                                                                class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                                                @click="openForm('delete', group.id, $event)"
                                                            />
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="flex items-center justify-between">
                                        </div>
                                    </DisclosureButton>
                                    <transition
                                        enter-active-class="transition duration-100 ease-out"
                                        enter-from-class="transform scale-95 opacity-0"
                                        enter-to-class="transform scale-100 opacity-100"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="transform scale-100 opacity-100"
                                        leave-to-class="transform scale-95 opacity-0"
                                    >
                                        <DisclosurePanel class="bg-white pt-2 pb-3">
                                            <div 
                                                class="w-full flex items-center gap-x-3 rounded-[5px] text-sm text-grey-900 font-medium odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50" 
                                                v-for="(item, index) in group.inventory_items" :key="index"
                                            >
                                                <div class="w-[4%] py-2 px-3"></div>
                                                <div class="w-[27%] py-2 px-3 truncate">{{ item.item_name }}</div>
                                                <div class="w-[11%] py-2 px-3">{{ item.item_code }}</div>
                                                <div class="w-[10%] py-2 px-3">{{ item.item_category.name }}</div>
                                                <div class="w-[11%] py-2 px-3">{{ item.stock_qty }}</div>
                                                <div class="w-[11%] py-2 px-3">{{ item.keep === 'Active' ? item.total_keep_qty : 'Not Allowed' }}</div>
                                                <div class="w-[14%] py-2 px-3">
                                                    <Tag
                                                        :variant="item.status === 'In stock' 
                                                                        ? 'green'
                                                                        : item.status === 'Low in stock'
                                                                            ? 'yellow'
                                                                            : 'red'"
                                                        :value="item.status"
                                                    />
                                                </div>
                                                <div class="w-[12%] py-2 px-3"></div>
                                            </div>
                                            <div class="flex justify-end pr-[50px] bg-white mt-3">
                                                <span>Total Stock: {{ totalInventoryItemStock(group.inventory_items) }}</span>
                                            </div>
                                        </DisclosurePanel>
                                    </transition>
                                </Disclosure>
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0 && props.rows.length > 0">
                            <td colspan="8">
                                <div class="flex flex-col items-center justify-center gap-5">
                                    <EmptyIllus class="flex-shrink-0"/>
                                    <span class="text-primary-900 text-sm font-medium">We couldn't find any result...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-center gap-5" v-if="props.rows.length === 0">
                <EmptyIllus class="flex-shrink-0"/>
                <span class="text-primary-900 text-sm font-medium">You haven't added any group yet...</span>
            </div>
            
            <Paginator
                v-if="props.rows.length > 0"
                :rows="4" 
                :totalRecords="rows.length"
                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                @page="onPageChange"
                :pt="{
                    root: {
                        class: 'flex justify-center items-center flex-wrap bg-white text-grey-500 py-3'
                    },
                    start: {
                        class: 'mr-auto'
                    },
                    pages: {
                        class: 'flex justify-center items-center'
                    },
                    pagebutton: ({ context }) => {
                        return {
                            class: [
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                {
                                    'rounded-full bg-primary-900 text-primary-50': context.active,
                                    'hover:rounded-full hover:bg-primary-50 hover:text-primary-900': !context.active,
                                },
                            ]
                        };
                    },
                    end: {
                        class: 'ml-auto'
                    },
                    firstpagebutton: {
                        class: [
                            {
                                'hidden': totalPages < 5,
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',,
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    previouspagebutton: {
                        class: [
                            {
                                'hidden': totalPages === 1
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    nextpagebutton: {
                        class: [
                            {
                                'hidden': totalPages === 1
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    lastpagebutton: {
                        class: [
                            {
                                'hidden': totalPages < 5
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                }"
            >
                <template #start>
                    <div class="text-xs font-medium text-grey-500">
                        Showing: <span class="text-grey-900">{{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages }}</span>
                    </div>
                </template>
                <template #end>
                    <div class="flex justify-center items-center gap-2 text-xs font-medium text-grey-900 whitespace-nowrap">
                        Go to Page: 
                        <TextInput
                            :inputName="'go_to_page'"
                            :placeholder="'eg: 12'"
                            class="!w-20"
                            :disabled="true"
                            v-if="totalPages === 1"
                        />
                        <TextInput
                            :inputName="'go_to_page'"
                            :placeholder="'eg: 12'"
                            class="!w-20"
                            :disabled="false"
                            @input="goToPage($event)"
                            v-else
                        />
                    </div>
                </template>
                <template #firstpagelinkicon>
                    <svg 
                        width="15" 
                        height="12" 
                        viewBox="0 0 15 12" 
                        fill="none" 
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path 
                            d="M14 11L9 6L14 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>
                        <path
                            d="M6 11L1 6L6 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                        />
                    </svg>
                </template>
                <template #prevpagelinkicon>
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="7" 
                        height="12" 
                        viewBox="0 0 7 12" 
                        fill="none"
                    >
                        <path 
                            d="M6 11L1 6L6 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                        />
                    </svg>
                </template>
                <template #nextpagelinkicon>
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="7" 
                        height="12" 
                        viewBox="0 0 7 12" 
                        fill="none"
                    >
                        <path 
                            d="M1 11L6 6L1 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                        />
                    </svg>
                </template>
                <template #lastpagelinkicon>
                    <svg 
                        width="15" 
                        height="12" 
                        viewBox="0 0 15 12" 
                        fill="none" 
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path 
                            d="M1 11L6 6L1 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>
                        <path
                            d="M9 11L14 6L9 1" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                        />
                    </svg>
                </template>
            </Paginator>
        </div>

        <Modal 
            :title="'Add New Group'"
            :show="createFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="closeForm('create', 'close')"
        >
            <CreateInventoryForm 
                :itemCategoryArr="itemCategoryArr"
                :categoryArr="categoryArr"
                @addAsProducts="openForm('group-created', null, $event)" 
                @update:rows="rows = $event" 
                @close="closeForm" 
                @isDirty="isDirty=$event"
            />

            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeForm('create', 'stay')"
                @leave="closeForm('create', 'leave')"
            />
        </Modal>
        <Modal 
            :maxWidth="'2xs'" 
            :closeable="true"
            :withHeader="false"
            :show="groupCreatedModalIsOpen"
            @close="closeForm('group-created', 'close')"
        >
            <div class="flex flex-col gap-9 pt-36">
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] fixed top-0 w-full left-0">
                    <OrderCompleteIllus class="mt-2.5"/>
                </div>
                <div class="flex flex-col gap-1" >
                    <div class="text-primary-900 text-2xl font-medium text-center">
                        Group created!
                    </div>
                    <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                        Do you want to add the item in the group to your menu?
                    </div>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="groupCreatedModalIsOpen = false"
                    >
                        Maybe Later
                    </Button>
                    <Button
                        size="lg"
                        type="button"
                        @click="openForm('add-as-product', null, $event)"
                    >
                        Add Now
                    </Button>
                </div>
            </div>
        </Modal>
        <Modal 
            :title="'Add New Product'"
            :maxWidth="'lg'" 
            :closeable="true"
            :show="addAsProductModalIsOpen"
            @close="closeForm('add-as-product', 'close')"
        >
            <template v-if="inventoryToAdd">
                <AddItemToMenuForm 
                    :inventoryToAdd="inventoryToAdd" 
                    :categoryArr="categoryArr" 
                    @close="closeForm" 
                    @isDirty="isDirty=$event"
                />
                <Modal
                    :unsaved="true"
                    :maxWidth="'2xs'"
                    :withHeader="false"
                    :show="isUnsavedChangesOpen"
                    @close="closeForm('add-as-product', 'stay')"
                    @leave="closeForm('add-as-product', 'leave')"
                />
            </template>
        </Modal>

        <Modal 
            :title="'Add New Stock'"
            :show="addStockFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="closeForm('add', 'close')"
        >
            <template v-if="selectedGroup">
                <AddStockForm 
                    :selectedGroup="selectedGroup" 
                    :selectedGroupItems="selectedGroupItems"
                    @close="closeForm"
                    @isDirty="isDirty=$event"
                />
            </template>
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeForm('add', 'stay')"
                @leave="closeForm('add', 'leave')"
            />
        </Modal>

        <Modal 
            :title="'Edit Group'"
            :show="editGroupFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="closeForm('edit', 'close')"
        >
            <template v-if="selectedGroup">
                <EditInventoryForm 
                    :group="selectedGroup" 
                    :selectedGroup="selectedGroupItems"
                    :itemCategoryArr="itemCategoryArr"
                    @close="closeForm"
                    @isDirty="isDirty=$event"
                />
            </template>
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeForm('edit', 'stay')"
                @leave="closeForm('edit', 'leave')"
            />
        </Modal>

        <Modal 
            :show="deleteGroupFormIsOpen" 
            :maxWidth="'2xs'" 
            :closeable="true" 
            :deleteConfirmation="true"
            :deleteUrl="`/inventory/inventory/deleteInventory/${selectedGroup.id}`"
            :confirmationTitle="'Delete this group?'"
            :confirmationMessage="'All the item inside this group will be deleted altogether. Are you sure you want to delete this group?'"
            :toastMessage="'Selected group has been deleted successfully.'"
            @close="closeForm('delete', 'close')"
            v-if="selectedGroup"
        />
    </div>
</template>
