<script setup>
import { ref, onMounted, watch, reactive, computed } from 'vue';
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
import { PlusIcon, EditIcon, DeleteIcon, ListViewIcon, GridViewIcon, GearIcon } from '@/Components/Icons/solid';
import CreateProductForm from './CreateProductForm.vue';
import EditProductForm from './EditProductForm.vue';
import Toggle from '@/Components/Toggle.vue';
import { useForm } from '@inertiajs/vue3';
import { transactionFormat, useCustomToast } from '@/Composables';
import TabView from '@/Components/TabView.vue';
import ManageCategory from './ManageCategory.vue';

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

const emit = defineEmits(["applyCategoryFilter", "applyCheckedFilters", "update:categories"]);

const createFormIsOpen = ref(false);
const editProductFormIsOpen = ref(false);
const deleteProductFormIsOpen = ref(false);
const manageCategoryFormIsOpen = ref(false);
const selectedProduct = ref(null);
const categories = ref([]);
const tabCategories = ref([]);
const selectedCategory = ref(0);
const selectedLayout = ref('grid');
const searchQuery = ref('');
const rows = ref([]);
const forms = reactive({});
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const maxProductPrice = ref(Math.max(...props.rows.map((row) => row.price)));

const { showMessage } = useCustomToast();
const { formatAmount } = transactionFormat();

const checkedFilters = ref({
    isRedeemable: [],
    stockLevel: [],
    priceRange: [0, maxProductPrice.value],
});

const stockLevels = ref(['In stock', 'Low in stock', 'Out of stock']);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

props.rows.forEach(item => {
    forms[item.id] = useForm({
        id: item.id,
        product_name: item.product_name,
        availability: item.availability === 'Available' ? true : false,
        availabilityWord: '',
    });
});

const toggleAvailability = (event, item) => {
    handleDefaultClick(event);
    if (forms[item.id]) {
        forms[item.id].availability = !forms[item.id].availability;  
        forms[item.id].id = item.id;
        forms[item.id].product_name = item.product_name;
        forms[item.id].availabilityWord = forms[item.id].availability ? 'Available' : 'Unavailable';
        
        updateAvailability(item.id); 
    } else {
        console.error(`Item with id ${item.id} not found in forms.`);
    }
};

const updateAvailability = (id) => {
    const form = forms[id];
    if (form) {
        const availability = form.availability === true ? 'available' : 'unavailable';
        
        form.post(route('products.updateAvailability'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: `${form.product_name} is now ${availability}.`
                    });
                }, 200);
            },
            onError: () => {
                setTimeout(() => {
                    showMessage({
                        severity: 'danger',
                        summary: 'Error occurred. Please contact the administrator.'
                    });
                });
            }
        });
    } else {
        console.error(`Form for item ID ${id} does not exist.`);
    }
};

const showCreateForm = () => {
    isDirty.value = false;
    createFormIsOpen.value = true;
};

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                createFormIsOpen.value = false;
                editProductFormIsOpen.value = false;
                manageCategoryFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            createFormIsOpen.value = false;
            editProductFormIsOpen.value = false;
            deleteProductFormIsOpen.value = false;
            manageCategoryFormIsOpen.value = false;
            isDirty.value = false;
        }
    }
}

const showEditGroupForm = (event, product) => {
    handleDefaultClick(event);
    isDirty.value = false;
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


const showManageCategoryForm = (event) => {
    handleDefaultClick(event);
    manageCategoryFormIsOpen.value = true;
}

const handleDefaultClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
};

const resetFilters = () => {
    return {
        isRedeemable: [],
        stockLevel: [],
        priceRange: [0, 5000],
    };
};

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    searchQuery.value = '';
    rows.value = props.rows;
    emit('applyCategoryFilter', selectedCategory.value);
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const applyCheckedFilters = (close) => {
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const toggleRedeemableStatus = () => {
    checkedFilters.value.isRedeemable = 
        checkedFilters.value.isRedeemable.includes(true) ? [] : [true];
}

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

// const maxProductPrice = computed(() => Math.max(...rows.value.map((row) => row.price)));

watch(() => props.categoryArr, (newValue) => {
    categories.value = [...newValue];
    categories.value.unshift({
        text: 'All',
        value: 0
    });

    tabCategories.value = categories.value.map((item) => item.text);
}, { immediate: true });

watch([() => props.rows, searchQuery], ([newRows, newValue]) => {
    if (!newValue) {
        // If no search query, reset rows to props.rows
        rows.value = newRows;
        return;
    }

    const query = newValue.toLowerCase();

    rows.value = props.rows.filter(product => {
        const productName = product.product_name.toLowerCase();
        const categoryName = product.category.name.toLowerCase();
        const isSingle = product.bucket.toLowerCase();
        const productPrice = product.price.toString().toLowerCase();

        return  productName.includes(query) ||
                categoryName.includes(query) ||
                isSingle.includes(query) ||
                productPrice.includes(query);
    });
}, { immediate: true });

onMounted(() => {
    categories.value = [...props.categoryArr];
    categories.value.unshift({
        text: 'All',
        value: 0
    });

    tabCategories.value = categories.value.map((item) => item.text);
});

const getCategoryFilteredRows = (category) => {
    return category === 'all' ? rows.value : rows.value.filter((row) => row.category.name.toLowerCase().replace(/[/\s_]+/g, "-") === category);
};

const getCategoryFilteredRowsLength = (category) => {
    return Math.ceil(getCategoryFilteredRows(category).length / props.rowsPerPage);
};

const updateCategories = (event) => {
    categories.value = event;
    emit('update:categories', event);
};

</script>
<template>
    <div class="flex flex-col p-6 gap-6 justify-center rounded-[5px] border border-red-100">
        <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-3 rounded-[5px]">
            <SearchBar
                placeholder="Search"
                :showFilter="true"
                v-model="searchQuery"
            >
                <template #default="{ hideOverlay }">
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
                    <div class="flex flex-col self-stretch gap-y-14 items-start">
                        <span class="text-grey-900 text-base font-semibold">Price Range</span>
                        <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                            <div class="flex items-center w-full">
                                <Slider 
                                    :minValue="0"
                                    :maxValue="maxProductPrice"
                                    v-model="checkedFilters.priceRange"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col self-stretch gap-4 items-start">
                        <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                            <div class="inline-flex w-full gap-2 justify-between border border-grey-100 rounded-[5px]">
                                <span>Show redeemable products only</span>
                                <Checkbox 
                                    :checked="checkedFilters.isRedeemable.includes(true)"
                                    @click="toggleRedeemableStatus()"
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
            <div class="flex items-center max-md:w-full md:justify-center gap-6">
                <div class="flex items-center justify-center gap-2">
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
                    class="md:!w-fit"
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
        </div>
        <div class="flex flex-col gap-4">
            <!-- <Tapbar
                :optionArr="categories"
                :checked="selectedCategory"
                @update:checked="handleFilterChange"
            /> -->
            <TabView :tabs="tabCategories">
                <template #endheader>
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        @click="showManageCategoryForm"
                        variant="tertiary"
                        class="md:!w-fit !border-0 text-wrap"
                    >
                        <template #icon>
                            <GearIcon
                                class="w-[20px] h-[20px]"
                            />
                        </template>
                        <span class="hidden sm:flex whitespace-nowrap">Manage Category</span>
                    </Button>
                    <Modal
                        :show="manageCategoryFormIsOpen"
                        @close="closeModal('close')"
                        :title="'Manage Category'"
                        :maxWidth="'md'"
                    >
                        <ManageCategory
                            :categoryArr="categories"
                            @close="closeModal" 
                            @isDirty="isDirty = $event"
                            @update:categories="updateCategories($event)"
                        />
                        <Modal
                            :unsaved="true"
                            :maxWidth="'2xs'"
                            :withHeader="false"
                            :show="isUnsavedChangesOpen"
                            @close="closeModal('stay')"
                            @leave="closeModal('leave')"
                        />
                    </Modal>
                </template>
                <template
                    v-for="tab in tabCategories.map((item) => item.toLowerCase().replace(/[/\s_]+/g, '-'))"
                    :key="tab"
                    v-slot:[tab]
                >
                    <Table 
                        v-if="selectedLayout === 'grid'"
                        :variant="'grid'"
                        :rows="getCategoryFilteredRows(tab)"
                        :totalPages="getCategoryFilteredRowsLength(tab)"
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
                        :rows="getCategoryFilteredRows(tab)"
                        :totalPages="getCategoryFilteredRowsLength(tab)"
                        :columns="columns"
                        :rowsPerPage="rowsPerPage"
                        :rowType="rowType"
                        :actions="actions"
                        :searchFilter="true"
                        :filters="filters"
                        minWidth="min-w-[878px]"
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
                        <template #availability="row">
                            <Toggle 
                                :checked="row.availability === 'Available'" 
                                :inputName="'availability'"
                                :inputId="'availability'"
                                :disabled="forms[row.id].processing"
                                v-model="forms[row.id].availability"
                                @change="toggleAvailability($event, row)"
                            />
                        </template>
                        <template #product_name="row">
                            <div class="flex flex-nowrap items-center gap-3">
                                <!-- <div class="bg-black/50"></div> -->
                                <!-- <img class="border border-grey-200 h-14 w-14 object-contain" 
                                    :src="row.image ? row.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="ProductImage"
                                    :class="{ 'opacity-50': row.stock_left == 0 }"
                                /> -->
                                <div class="relative rounded-[5px] border border-grey-100]">
                                    <div :class="['absolute size-14 bg-black', row.stock_left == 0 ? 'opacity-50' : 'opacity-0']"></div>
                                    <span class="absolute top-[calc(50%-0.5rem)] left-[calc(50%-1.45rem)] bottom-0 text-white text-[8px] font-medium" v-if="row.stock_left === 0">Out of Stock</span>
                                    <img 
                                        :src="row.image ? row.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="ProductImage" 
                                        class="size-14 object-contain"
                                    />
                                </div>
                                <span class="text-grey-900 text-sm font-medium">{{ row.product_name }}</span>
                            </div>
                        </template>
                        <template #price="row">
                            <div v-for="items in row.discountItems" v-if="row.discount_id !== null" class="flex flex-col items-start">
                                <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-bold ">RM {{ formatAmount(items.price_after) }}</span>
                                <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ formatAmount(items.price_before) }}</span>
                            </div>
                            <span class="text-grey-900 text-sm font-bold" v-else>RM {{ formatAmount(row.price) }}</span>
                        </template>
                        <template #stock_left="row">
                            <span class="inline-block align-middle"
                                    :class="row.status === 'In stock' ? 'text-green-700' : row.status === 'Low in stock' ? 'text-yellow-700' : 'text-primary-600'">
                                <p v-if="row.status === 'Out of stock'">{{ row.status }}</p>
                                <p v-else>{{ row.bucket === 'set' ? `${row.stock_left} set` : row.stock_left }}</p>
                            </span>
                        </template>
                    </Table>
                </template>
            </TabView>
        </div>
        <Modal 
            :title="'Add New Product'"
            :show="createFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="closeModal('close')"
        >
            <CreateProductForm 
                :categoryArr="categoryArr"
                :inventoriesArr="inventoriesArr"
                @close="closeModal"
                @isDirty="isDirty = $event"
            />
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeModal('stay')"
                @leave="closeModal('leave')"
            >
            </Modal>
        </Modal>
        <Modal 
            :title="'Edit Product'"
            :show="editProductFormIsOpen" 
            :maxWidth="'lg'" 
            :closeable="true" 
            @close="closeModal('close')"
        >
            <template v-if="selectedProduct">
                <EditProductForm 
                    :product="selectedProduct"
                    :categoryArr="categoryArr"
                    :inventoriesArr="inventoriesArr"
                    @close="closeModal"
                    @isDirty="isDirty = $event"
                />
                <Modal
                    :unsaved="true"
                    :maxWidth="'2xs'"
                    :withHeader="false"
                    :show="isUnsavedChangesOpen"
                    @close="closeModal('stay')"
                    @leave="closeModal('leave')"
                >
                </Modal>
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
            @close="closeModal('leave')"
            v-if="selectedProduct"
        />
    </div>
</template>