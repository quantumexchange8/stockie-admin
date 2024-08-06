<script setup>
import { ref, onMounted, watch } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import Slider from "@/Components/Slider.vue";
import Button from '@/Components/Button.vue';
import Tapbar from '@/Components/Tapbar.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SearchBar from "@/Components/SearchBar.vue";
import { EmptyIllus } from '@/Components/Icons/illus.jsx';
import { PlusIcon, EditIcon, DeleteIcon, ListViewIcon, GridViewIcon } from '@/Components/Icons/solid';
import CreateProductForm from './CreateProductForm.vue';
import EditProductForm from './EditProductForm.vue';

const props = defineProps({
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
    inventoriesArr: {
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
});

const emit = defineEmits(["applyCategoryFilter", "applyCheckedFilters"]);

const createFormIsOpen = ref(false);
const editProductFormIsOpen = ref(false);
const deleteProductFormIsOpen = ref(false);
const selectedProduct = ref(null);
const categories = ref([]);
const selectedCategory = ref(0);
const selectedLayout = ref('grid');

const checkedFilters = ref({
    keepStatus: [],
    stockLevel: [],
    priceRange: [0, 5000],
});

const stockLevels = ref(['In Stock', 'Low Stock', 'Out of Stock']);
const keepStatusArr = ref(['Active', 'Inactive']);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const showCreateForm = () => {
    createFormIsOpen.value = true;
};

const hideCreateForm = () => {
    createFormIsOpen.value = false;
};

const showEditGroupForm = (event, product) => {
    handleDefaultClick(event);
    selectedProduct.value = product;
    editProductFormIsOpen.value = true;
}

const hideEditProductForm = () => {
    editProductFormIsOpen.value = false;
    setTimeout(() => {
        selectedProduct.value = null;
    }, 300);
}

const showDeleteGroupForm = (event, id) => {
    handleDefaultClick(event);
    selectedProduct.value = id;
    deleteProductFormIsOpen.value = true;
}

const hideDeleteProductForm = () => {
    deleteProductFormIsOpen.value = false;
    setTimeout(() => {
        selectedProduct.value = null;
    }, 300);
}

const handleDefaultClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
    // window.location.href = event.currentTarget.href;  // Manually handle the link navigation
};

const resetFilters = () => {
    return {
        keepStatus: [],
        stockLevel: [],
        priceRange: [0, 5000],
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

const toggleKeepStatus = (value) => {
    const index = checkedFilters.value.keepStatus.indexOf(value);
    if (index > -1) {
        checkedFilters.value.keepStatus.splice(index, 1);
    } else {
        checkedFilters.value.keepStatus.push(value);
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

const handleLayoutChange = (layout) => {
    selectedLayout.value = layout;
};

const baselayoutIconClasses = [
    'size-auto flex-shrink-0 active:text-primary-100 active:fill-primary-900 [&>rect]:active:stroke-primary-900 focus:text-primary-100 focus:fill-primary-900 [&>rect]:focus:stroke-primary-900',
]

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
        <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-6 rounded-[5px]">
            <div class="w-full flex flex-wrap sm:flex-nowrap items-center justify-center gap-3">
                <SearchBar
                    placeholder="Search"
                    :showFilter="true"
                    v-model="filters['global'].value"
                >
                    <template #default="{ hideOverlay }">
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
                            <span class="text-grey-900 text-base font-semibold">Price Range</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div class="flex items-center w-full">
                                    <Slider 
                                        :minValue="0"
                                        :maxValue="5000"
                                        v-model="checkedFilters.priceRange"
                                    />
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
                <GridViewIcon
                    :class="[
                        baselayoutIconClasses,
                        {
                            'text-primary-100 fill-primary-900 [&>rect]:stroke-primary-900' : selectedLayout === 'grid',
                            'text-primary-900 fill-white [&>rect]:stroke-primary-900 hover:text-primary-800 hover:fill-primary-25 [&>rect]:hover:stroke-primary-800' : selectedLayout === 'list',
                        }
                    ]"
                    @click="handleLayoutChange('grid')"
                />
                <ListViewIcon
                    :class="[
                        baselayoutIconClasses,
                        {
                            'text-primary-100 fill-primary-900 [&>rect]:stroke-primary-900' : selectedLayout === 'list',
                            'text-primary-900 fill-white [&>rect]:stroke-primary-900 hover:text-primary-800 hover:fill-primary-25 [&>rect]:hover:stroke-primary-800' : selectedLayout === 'grid',
                        }
                    ]"
                    @click="handleLayoutChange('list')"
                />
            </div>
            <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit"
                @click="showCreateForm"
            >
                <template #icon>
                    <PlusIcon
                        class="w-6 h-6"
                    />
                </template>
                New Product
            </Button>
        </div>
        <div class="flex flex-col gap-4">
            <Tapbar
                :optionArr="categories"
                :checked="selectedCategory"
                @update:checked="handleFilterChange"
            />
            <Table 
                v-if="selectedLayout === 'grid'"
                :variant="'grid'"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :rowType="rowType"
                :actions="actions"
                :searchFilter="true"
                :filters="filters"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center gap-5">
                        <EmptyIllus />
                        <span class="text-primary-900 text-sm font-medium">You haven't added any products yet...</span>
                    </div>
                </template>
                <template #editAction="product">
                    <Button
                        :type="'button'"
                        :size="'md'"
                        @click="showEditGroupForm($event, product)"
                        class="!bg-primary-100 hover:!bg-primary-200 rounded-tl-none rounded-tr-none rounded-br-none rounded-bl-[5px]"
                    >
                        <EditIcon
                            class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                        />
                    </Button>
                </template>
                <template #deleteAction="product">
                    <Button
                        :type="'button'"
                        :size="'md'"
                        @click="showDeleteGroupForm($event, product.id)"
                        class="!bg-primary-600 hover:!bg-primary-700 rounded-tl-none rounded-tr-none rounded-bl-none rounded-br-[5px]"
                    >
                        <DeleteIcon
                            class="w-5 h-5 text-primary-100 hover:text-primary-50 cursor-pointer pointer-events-none"
                        />
                    </Button>
                </template>
            </Table>
            <Table 
                v-else
                :variant="'list'"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :rowType="rowType"
                :actions="actions"
                :searchFilter="true"
                :filters="filters"
            >
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #empty>
                    <EmptyIllus />
                    <span class="text-primary-900 text-sm font-medium">You haven't added any products yet...</span>
                </template>
                <template #editAction="row">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditGroupForm($event, row)"
                    />
                </template>
                <template #deleteAction="row">
                    <DeleteIcon
                        class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="showDeleteGroupForm($event, row.id)"
                    />
                </template>
                <template #product_name="row">
                    <div class="flex flex-nowrap items-center gap-3">
                        <div class="bg-grey-50 border border-grey-200 h-14 w-14"></div>
                        <span class="text-grey-900 text-sm font-medium">{{ row.product_name }}</span>
                    </div>
                </template>
                <template #price="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ row.price }}</span>
                </template>
                <template #point="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.point || 0 }}</span>
                </template>
                <template #keep="row">
                    <Tag
                        :variant="row.keep ? 'green' : 'yellow'"
                        :value="row.keep === 'Active' ? 'Keep is allowed' : 'Keep is not allowed'"
                    />
                </template>
                <template #stock_left="row">
                    <span class="text-primary-600 inline-block align-middle">{{ row.stock_left }}</span>
                </template>
            </Table>
        </div>
        <Modal 
            :title="'Add New Product'"
            :show="createFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="hideCreateForm"
        >
            <CreateProductForm 
                :categoryArr="categoryArr"
                :inventoriesArr="inventoriesArr"
                @close="hideCreateForm"
            />
        </Modal>
        <Modal 
            :title="'Edit Product'"
            :show="editProductFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="hideEditProductForm"
        >
            <template v-if="selectedProduct">
                <EditProductForm 
                    :product="selectedProduct"
                    :categoryArr="categoryArr"
                    :inventoriesArr="inventoriesArr"
                    @close="hideEditProductForm"
                />
            </template>
        </Modal>
        <Modal 
            :show="deleteProductFormIsOpen" 
            :maxWidth="'2xs'" 
            :closeable="true" 
            :deleteConfirmation="true"
            :deleteUrl="`/menu-management/products/deleteProduct/${selectedProduct}`"
            :confirmationTitle="'Delete this product?'"
            :confirmationMessage="'Are you sure you want to delete the selected product? This action cannot be undone.'"
            @close="hideDeleteProductForm"
            v-if="selectedProduct"
        />
    </div>
</template>