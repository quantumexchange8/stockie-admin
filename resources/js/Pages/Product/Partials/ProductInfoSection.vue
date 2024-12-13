<script setup>
import { ref, watch } from 'vue';
import { EditIcon, DeleteIcon, AppsIcon, PriceTagIcon, PointsIcon, PointsBoxIcon } from '@/Components/Icons/solid.jsx';
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue';
import EditProductForm from './EditProductForm.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    errors: Object,
    product: {
        type: Object,
        default: () => {},
    },
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    rowType: {
        type: Object,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
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
})

const productInfo = ref();
const category = ref();
const editProductFormIsOpen = ref(false);
const deleteProductFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const showEditGroupForm = (event) => {
    editProductFormIsOpen.value = true;
}

const hideEditProductForm = () => {
    editProductFormIsOpen.value = false;
}

const showDeleteGroupForm = () => {
    deleteProductFormIsOpen.value = true;
}

const hideDeleteProductForm = () => {
    deleteProductFormIsOpen.value = false;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                editProductFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            editProductFormIsOpen.value = false;
            deleteProductFormIsOpen.value = false;
        }
    }
}

watch( () => props.product, (newValue) => {
    productInfo.value = newValue ? newValue : {};
}, { immediate: true });

watch( () => props.product, (newValue) => {
    category.value = newValue ? newValue.category : '';
}, { immediate: true });

</script>

<template>
    <div class="col-span-full lg:col-span-4 flex flex-col p-6 gap-6 items-start rounded-[5px] border border-red-100">
        <div class="flex items-center justify-between w-full">
            <span class="w-full text-start text-md font-medium text-primary-900">Product Detail</span>
            <div class="flex flex-nowrap gap-2">
                <EditIcon 
                    class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="showEditGroupForm"
                />
                <DeleteIcon 
                    class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                    @click="showDeleteGroupForm"
                />
            </div>
        </div>

        <img 
            :src="productInfo.image ? productInfo.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
            alt=""
            class="w-[274px] h-[274px] object-contain"    
        >

        <div class="flex flex-col items-start self-stretch gap-4">
            <!-- <Tag
                :variant="productInfo.keep ? 'green' : 'yellow'"
                :value="productInfo.keep === 'Active' ? 'Keep is allowed' : 'Keep is not allowed'"
            /> -->
            <span class="text-md text-grey-900 font-medium">{{ productInfo.product_name }}</span>
        </div>

        <div class="flex flex-col gap-4 items-start self-stretch">
            <div class="grid grid-cols-12 gap-4 w-full">
                <div class="col-span-6 lg:col-span-full flex justify-start items-center w-full p-3 gap-4 border border-primary-100 bg-white rounded-[5px]">
                    <div class="bg-primary-50 rounded-[5px] flex items-center justify-center p-2">
                        <PriceTagIcon class="size-6 text-primary-900"/>
                    </div>
                    <span class="text-base text-grey-900 font-medium">RM {{ productInfo.price }}</span>
                </div>
                <div class="col-span-6 lg:col-span-full flex justify-start items-center w-full p-3 gap-4 border border-primary-100 bg-white rounded-[5px]">
                    <div class="bg-primary-50 rounded-[5px] flex items-center justify-center p-2">
                        <AppsIcon class="size-6 text-primary-900"/>
                    </div>
                    <span class="text-base text-grey-900 font-medium">{{ category.name }}</span>
                </div>
                <!-- <div class="col-span-full xl:col-span-6 flex justify-start items-center w-full p-3 gap-4 border border-primary-100 bg-white rounded-[5px]">
                    <div class="bg-primary-50 rounded-[5px] flex items-center justify-center p-2">
                        <PointsIcon class="size-6 text-primary-900"/>
                    </div>
                    <span class="text-base text-grey-900 font-medium">{{ productInfo.point }} pts</span>
                </div> -->
            </div>
            <!-- need to use the points from points table instead & only show when this item is redeemable -->
            <div class="flex justify-start items-center w-full p-3 gap-4 border border-primary-100 bg-white rounded-[5px]" v-if="productInfo.point > 0">
                <div class="bg-primary-50 rounded-[5px] flex items-center justify-center p-2">
                    <PointsBoxIcon class="size-6 text-primary-900"/>
                </div>
                <span class="text-base text-grey-900 font-medium">
                    Redeem with 
                    <span class="!font-semibold !font-primary-900">{{ productInfo.point }} pts</span>
                </span>
            </div>
            <div class="flex items-start w-full rounded-[5px]">
                <div class="w-full">
                    <Table 
                        :variant="'list'"
                        :rows="rows"
                        :columns="columns"
                        :rowType="rowType"
                        :paginator="false"
                        class="w-full"
                        minWidth="min-w-64"
                    >
                        <template #item="row">
                            <span class="text-grey-900 text-sm font-medium">{{ row.qty + ' x ' + row.inventory_item.item_name}}</span>
                        </template>
                        <template #inventory_item.stock_qty="row">
                            <span class="text-primary-600 text-base font-medium">{{ row.inventory_item.stock_qty }}</span>
                        </template>
                    </Table>    
                </div>
            </div>
        </div>
    </div>
    <Modal
        :title="'Edit Product'"
        :show="editProductFormIsOpen" 
        :maxWidth="'lg'" 
        :closeable="true" 
        @close="closeModal('close')"
    >
        <template v-if="product">
            <EditProductForm 
                :product="product"
                :categoryArr="categoryArr"
                :inventoriesArr="inventoriesArr"
                @close="closeModal"
                @isDirty="isDirty = $event"
            />
        </template>
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
        :show="deleteProductFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/menu-management/products/deleteProduct/${product.id}`"
        :confirmationTitle="'Delete this product?'"
        :confirmationMessage="'Are you sure you want to delete the selected product? This action cannot be undone.'"
        @close="closeModal('leave')"
        v-if="product"
    />
</template>
