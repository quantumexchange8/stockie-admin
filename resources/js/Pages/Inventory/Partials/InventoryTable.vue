<script setup>
import { ref, watch, onMounted } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import Table from '@/Components/Table.vue';
import Button from '@/Components/Button.vue';
import Tapbar from '@/Components/Tapbar.vue';
import AddStockForm from './AddStockForm.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SearchBar from "@/Components/SearchBar.vue";
import { EmptyIllus } from '@/Components/Icons/illus.jsx';
import { PlusIcon, ReplenishIcon, EditIcon, DeleteIcon } from '@/Components/Icons/solid';
import EditInventoryForm from './EditInventoryForm.vue';
import CreateInventoryForm from './CreateInventoryForm.vue';

const props = defineProps({
    errors: Object,
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    categoryArr: {
        type: Array,
        default: () => [],
    },
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
    rowType: {
        type: Object,
        required: true,
    },
    actions: {
        type: Object,
        default: () => {},
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
})

const emit = defineEmits(["applyCategoryFilter", "applyCheckedFilters"]);

const createFormIsOpen = ref(false);
const addStockFormIsOpen = ref(false);
const editGroupFormIsOpen = ref(false);
const deleteGroupFormIsOpen = ref(false);
const selectedGroupId = ref(null);
const selectedGroup = ref(null);
const categories = ref([]);
const selectedCategory = ref(0);

const checkedFilters = ref({
    itemCategory: [],
    stockLevel: [],
});

const stockLevels = ref(['In Stock', 'Low Stock', 'Out of Stock']);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const showCreateForm = () => {
    createFormIsOpen.value = true;
}

const hideCreateForm = () => {
    createFormIsOpen.value = false;
}

const showAddStockForm = (group) => {
    selectedGroupId.value = group;

    selectedGroup.value = props.rows.filter((row) => {
        if (row.inventory_id === selectedGroupId.value.id) return row;
    });

    addStockFormIsOpen.value = true;
}

const hideAddStockForm = () => {
    addStockFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroupId.value = null;
    }, 300);
}

const showEditGroupForm = (group) => {
    selectedGroupId.value = group;

    selectedGroup.value = props.rows.filter((row) => {
        if (row.inventory_id === selectedGroupId.value.id) return row;
    });

    editGroupFormIsOpen.value = true;
}

const hideEditGroupForm = () => {
    editGroupFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroupId.value = null;
    }, 300);
}

const showDeleteGroupForm = (group) => {
    selectedGroupId.value = group;
    deleteGroupFormIsOpen.value = true;
}

const hideDeleteGroupForm = () => {
    deleteGroupFormIsOpen.value = false;
    setTimeout(() => {
        selectedGroupId.value = null;
    }, 300);
}

const resetFilters = () => {
    return {
        itemCategory: [],
        stockLevel: [],
    };
};

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    emit('applyCategoryFilter', selectedCategory.value);
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

const handleFilterChange = (newFilter) => {
    selectedCategory.value = newFilter;
    emit('applyCategoryFilter', newFilter);
};

watch(
    () => props.categoryArr,
    (newValue) => {
        categories.value = [...newValue];
        categories.value.unshift({
            text: 'All',
            value: 0
        });
    },
    { immediate: true }
);

onMounted(() => {
    categories.value = [...props.categoryArr];
    categories.value.unshift({
        text: 'All',
        value: 0
    });
})
</script>

<template>
    <div class="flex flex-col p-6 gap-6 justify-center rounded-[5px] border border-red-100">
        <Tapbar
            :optionArr="categories"
            :checked="selectedCategory"
            @update:checked="handleFilterChange"
        />
        <div>
            <div class="flex flex-wrap lg:flex-nowrap items-center justify-between gap-6 rounded-[5px]">
                <SearchBar
                    placeholder="Search"
                    :showFilter="true"
                    v-model="filters['global'].value"
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
                            <svg 
                                width="20" 
                                height="20" 
                                viewBox="0 0 20 20" 
                                fill="none" 
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6"
                            >
                                <path d="M10.8333 2.91667V5.16667C10.8333 6.5668 10.8333 7.26686 11.1058 7.80164C11.3455 8.27205 11.728 8.6545 12.1984 8.89418C12.7331 9.16667 13.4332 9.16667 14.8333 9.16667H17.0833M17.5 10.8235V13.5C17.5 14.9001 17.5 15.6002 17.2275 16.135C16.9878 16.6054 16.6054 16.9878 16.135 17.2275C15.6002 17.5 14.9001 17.5 13.5 17.5H6.5C5.09987 17.5 4.3998 17.5 3.86502 17.2275C3.39462 16.9878 3.01217 16.6054 2.77248 16.135C2.5 15.6002 2.5 14.9001 2.5 13.5V6.5C2.5 5.09987 2.5 4.3998 2.77248 3.86502C3.01217 3.39462 3.39462 3.01217 3.86502 2.77248C4.3998 2.5 5.09987 2.5 6.5 2.5H9.17648C9.78796 2.5 10.0937 2.5 10.3814 2.56908C10.6365 2.63032 10.8804 2.73133 11.104 2.8684C11.3563 3.023 11.5725 3.23919 12.0049 3.67157L16.3284 7.9951C16.7608 8.42747 16.977 8.64366 17.1316 8.89595C17.2687 9.11963 17.3697 9.3635 17.4309 9.61859C17.5 9.90631 17.5 10.212 17.5 10.8235Z" stroke="#7E171B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </template>
                        View Stock History
                    </Button>
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="w-full"
                        @click="showCreateForm"
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
            <Table 
                :variant="'list'"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :rowType="rowType"
                :actions="actions"
                :searchFilter="true"
                :filters="filters"
                minWidth="min-w-[755px]"
            >
                <template #empty>
                    <EmptyIllus />
                    <span class="text-primary-900 text-sm font-medium">We couldn't find any result...</span>
                </template>
                <template #groupheader="row">
                    <div class="flex justify-between items-center w-full !z-0">
                        <div class="flex items-center gap-3">
                            <span class="w-[60px] h-[60px] flex-shrink-0 rounded-full bg-primary-700"></span>
                            <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ row.inventory.name }}</span>
                        </div>
                        <div class="flex justify-end items-start gap-2">
                            <ReplenishIcon
                                class="w-6 h-6 block transition duration-150 ease-in-out text-primary-900 hover:text-primary-800 cursor-pointer"
                                @click="() => showAddStockForm(row.inventory)"
                            />
                            <EditIcon
                                class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                @click="() => showEditGroupForm(row.inventory)"
                            />
                            <DeleteIcon
                                class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="() => showDeleteGroupForm(row.inventory)"
                            />
                        </div>
                    </div>
                </template>
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #item_cat_id="row">
                    {{ itemCategoryArr.find((category) => category.value === row.item_cat_id).text }}
                </template>
                <template #keep="row">
                    {{ row.keep ? row.keep : 0 }}
                </template>
                <template #status="row">
                    <Tag
                        :variant="'green'"
                        :value="row.status"
                    />
                </template>
            </Table>
        </div>
        <Modal 
            :title="'Add New Group'"
            :show="createFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="hideCreateForm"
        >
            <CreateInventoryForm 
                :itemCategoryArr="itemCategoryArr"
                @close="hideCreateForm" 
            />
        </Modal>
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
        <Modal 
            :title="'Edit Group'"
            :show="editGroupFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="hideEditGroupForm"
        >
            <template v-if="selectedGroupId">
                <EditInventoryForm 
                    :group="selectedGroupId" 
                    :selectedGroup="selectedGroup"
                    :itemCategoryArr="itemCategoryArr"
                    :categoryArr="categoryArr"
                    @close="hideEditGroupForm"
                />
            </template>
        </Modal>
        <Modal 
            :show="deleteGroupFormIsOpen" 
            :maxWidth="'2xs'" 
            :closeable="true" 
            :deleteConfirmation="true"
            :deleteUrl="`/inventory/inventory/deleteInventory/${selectedGroupId.id}`"
            :confirmationTitle="'Delete this group?'"
            :confirmationMessage="'All the item inside this group will be deleted altogether. Are you sure you want to delete this group?'"
            @close="hideDeleteGroupForm"
            v-if="selectedGroupId"
        />
    </div>
</template>
