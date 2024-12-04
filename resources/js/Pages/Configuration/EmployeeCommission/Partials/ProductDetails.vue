<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import { RemoveProductIllust, UndetectableIllus } from '@/Components/Icons/illus';
import { DeleteIcon, PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import Tag from '@/Components/Tag.vue';
import Toast from '@/Components/Toast.vue';
import { transactionFormat, useCustomToast } from '@/Composables';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { FilterMatchMode } from 'primevue/api';
import { computed, onMounted, ref } from 'vue';
import CommissionDetails from './CommissionDetails.vue';
import AddProduct from './AddProduct.vue';

const props = defineProps({
    productDetails: {
        type: Object,
        default: () => {},
    },
    commissionDetails: {
        type: Object,
        required: true,
    },
    productsToAdd: {
        type: Array,
        default: () => {},
    }
})
const home = ref({
    label: 'Configuration',
    route: '/configurations/configurations'
});

const items = ref([
    { label: 'Commission Detail' },
]);

const { flashMessage } = useCustomToast();
const { formatAmount } = transactionFormat();
const rowsPerPage = ref(11);
const totalPages = computed(() => {
    return Math.ceil(props.productDetails.productDetails.length / rowsPerPage.value);
})
const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const commDetailsColumn = ref([
    {field: 'product_name', header: 'Product Name', width: '40', sortable: true},
    {field: 'price', header: 'Price', width: '20', sortable: true},
    {field: 'commission', header: 'Commission', width: '30', sortable: true},
    {field: 'action', header: '', width: '10', sortable: false},
])

const actions = {
    delete: () => ``,
};

const selectedProduct = ref(null);
const removingFrom = ref(props.commissionDetails.id);
const isDeleteModalOpen = ref(false);
const isProductModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);

const showProductModal = () => {
    isProductModalOpen.value = true;
    isDirty.value = false;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isProductModalOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isProductModalOpen.value = false;
            break;
        }
    }
}

const showDeleteModal = (id) => {
    isDeleteModalOpen.value = true;
    form.id = id;
}

const hideDeleteModal = () => {
    isDeleteModalOpen.value = false;
}

const form = useForm({
    id: '',
    commissionId: removingFrom,
})
const submit = () => {
    form.delete(route(`configurations.deleteProduct`), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            hideDeleteModal();
        },
        onError: (error) => {
            console.error(error);
        }
    })
}

onMounted(() => {
    flashMessage();
});

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
</script>

<template>
    <Head title="Commission Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home"
                :items="items"
            />
        </template>

        <Toast />

        <div class="w-full grid justify-center items-start gap-5 flex-[1_0_0] self-stretch grid-cols-12 h-full">
            <div class="w-full flex flex-col p-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100 col-span-7 ">
                <div class="flex flex-col items-center gap-6 self-stretch">
                    <div class="flex flex-col justify-center items-start gap-[10px] self-stretch">
                        <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium">Product with this commission type</span>
                    </div>

                    <div class="flex items-start gap-5 self-stretch">
                        <SearchBar 
                            placeholder="Search"
                            :showFilter="false"
                            v-model="filters['global'].value"
                        />

                        <Button
                            :type="'button'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            class="!w-fit flex items-center gap-2"
                            @click="showProductModal"
                        >
                            <template #icon>
                                <PlusIcon />
                            </template>
                            Product
                        </Button>
                    </div>
                    <Table
                        :columns="commDetailsColumn"
                        :rows="props.productDetails.productDetails"
                        :actions="actions"
                        :variant="'list'"
                        :searchFilter="true"
                        :filters="filters"
                        :rowType="rowType"
                        :totalPages="totalPages"
                        :rowsPerPage="rowsPerPage"
                        class="!min-h-full"
                    >
                        <template #empty>
                            <UndetectableIllus class="w-44 h-44"/>
                            <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                        </template>
                        <template #product_name="rows">
                            <div class="flex items-center gap-3">
                                <!-- <div class="size-10 rounded-[1px] border-[0.2px] border-solid border-grey-100 bg-white"></div> -->
                                <img 
                                    :src="rows.image ? rows.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-10 rounded-[1px] border-[0.2px] border-solid border-grey-100 object-contain"
                                >
                                <Tag
                                    :variant="'default'"
                                    :value="'Set'"
                                    v-if="rows.bucket === 'set'"
                                >
                                </Tag>
                                <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ rows.product_name }}</span>
                            </div>
                        </template>
                        <template #price="rows">
                            <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(rows.price) }}</span>
                        </template>
                        <template #commission="rows">
                            <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(rows.commission) }}</span>
                        </template>
                        <template #deleteAction="rows">
                            <DeleteIcon
                                class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                @click="showDeleteModal(rows.id)"
                            />
                        </template>
                    </Table>
                </div>
            </div>

            <div class="w-full flex flex-col p-6 justify-between items-center rounded-[5px] border border-solid border-primary-100 col-span-5 h-full">
                <CommissionDetails 
                    :productDetails="productDetails"
                    :commissionDetails="commissionDetails"                
                />
            </div>
        </div>

        <Modal
            :show="isDeleteModalOpen"
            :maxWidth="'2xs'"
            :withHeader="false"
            :deleteUrl="`/configurations/deleteProduct`"
            @close="hideDeleteModal"
            v-if="isDeleteModalOpen"
        >
            <form @submit.prevent="submit">
                <div class="flex flex-col items-center gap-9 rounded-[5px] border border-solid border-primary-200 bg-white m-[-24px]">
                    <div class="w-full flex flex-col items-center gap-[10px] bg-primary-50">
                        <div class="w-full flex pt-2 justify-center items-center">
                            <RemoveProductIllust />
                        </div>
                    </div>
                    <div class="flex flex-col px-6 items-center gap-1 self-stretch">
                        <span class="self-stretch text-primary-900 text-center text-lg font-medium ">Remove this product?</span>
                        <span class="self-stretch text-grey-900 text-center text-base font-medium">Are you sure you want to remove the selected product from this commission type?</span>
                    </div>

                    <div class="flex px-6 pb-6 justify-center items-start gap-3 self-stretch">
                        <Button
                            variant="tertiary"
                            size="lg"
                            type="button"
                            @click="hideDeleteModal"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="red"
                            size="lg"
                            type="submit"
                            :disabled="form.processing"
                            @click="submit(selectedProduct, removingFrom)"
                        >
                            Remove
                        </Button>
                    </div>
                </div>
            </form>
        </Modal>

        <Modal
            :title="'Select Product'"
            :maxWidth="'md'"
            :closeable="true"
            :show="isProductModalOpen"
            @close="() => closeModal('close')"
        >
            <AddProduct
                :id="commissionDetails.id"
                :productsToAdd="productsToAdd"
                @closeModal="closeModal"
                @isDirty="isDirty = $event"
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
        
    </AuthenticatedLayout>
</template>
