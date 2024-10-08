<script setup>
import Button from "@/Components/Button.vue";
import { EmptyCommissionIllust } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import { Head } from "@inertiajs/vue3";
import { FilterMatchMode } from "primevue/api";
import { onMounted, ref } from "vue";
import AddCommission from "./Partials/AddCommission.vue";
import EditCommission from "./Partials/EditCommission.vue";
import axios from "axios";

const isNewCommissionOpen = ref(false);
const isEditCommOpen = ref(false);
const isDeleteCommOpen = ref(false);
const selectedProduct = ref();
const isLoading = ref(false);
const productNames = ref([]);
const productToAdd = ref([]);
const rows = ref([]);

const actions = {
    view: (productId) => `/configurations/productDetails/${productId}`,
    edit: () => ``,
    delete: () => ``,
};

const columns = ref([
    { field: 'comm_type', header: 'Type', width: '35', sortable: true},
    { field: 'rate', header: 'Rate', width: '15', sortable: true},
    { field: 'product', header: 'Product with this commission', width: '40', sortable: true},
    { field: 'action', header: '', width: '10', sortable: false},
])

const viewEmployeeComm = async () => {
        isLoading.value=true
    try {
        const response = await axios.get('/configurations/configurations/commission');
        rows.value = response.data.commission;
        productNames.value = response.data.productNames;
        productToAdd.value = response.data.productToAdd;
    } catch(error){
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const openEditComm = (event, rows) => {
    handleDefaultClick(event);
    isEditCommOpen.value = true;
    selectedProduct.value = rows;
}

const openDeleteComm = (event, id) => {
    handleDefaultClick(event);
    isDeleteCommOpen.value = true;
    selectedProduct.value = id;
}

const openModal = () => {
    isNewCommissionOpen.value = true;
}

const closeModal = () => {
    isNewCommissionOpen.value = false;
    isDeleteCommOpen.value = false;
    isEditCommOpen.value = false;
}

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
}

onMounted (() => {
    viewEmployeeComm();
});
</script>

<template>
    <Head title="Configuration" />

    <div class="flex flex-col p-6 items-center self-stretch gap-6 border border-primary-100 rounded-[5px]">
        <div class="flex flex-col justify-center flex-start gap-2.5 self-stretch">
            <span class="text-md font-medium text-primary-900">
                Employee Commision
            </span>
        </div>
        <div class="w-full flex items-center gap-5">
            <SearchBar
                placeholder="Search"
                :show-filter="false"
                v-model="filters['global'].value"
            >
            </SearchBar>

            <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="flex items-center gap-2 !w-fit"
                @click="openModal"
                ><template #icon>
                    <PlusIcon />
                </template>

                Commission Type
                <Modal
                    :show="isNewCommissionOpen"
                    @close="closeModal"
                    :title="'Add Commission Type'"
                    :maxWidth="'md'"
                >
                    <AddCommission 
                        :productNames="productNames"
                        :productToAdd="productToAdd"
                        @closeModal="closeModal"
                        @viewEmployeeComm="viewEmployeeComm"
                    />
                </Modal>
            </Button>
        </div>

        <Table
            :columns="columns"
            :rows="rows"
            :actions="actions"
            :variant="'list'"
            :searchFilter="true"
            :filters="filters"
        >
            <template #empty>
                <div class="flex w-full flex-col items-center justify-center gap-5">
                    <EmptyCommissionIllust />
                    <span class="text-primary-900 text-sm font-medium">You haven’t added any commission type yet...</span>
                </div>
            </template>
            <template #editAction="rows">
                <EditIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="openEditComm($event, rows)"
                />
            </template>
            <template #deleteAction="rows">
                <DeleteIcon
                    class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                    @click="openDeleteComm($event, rows.id)"
                />
            </template>
            <template #comm_type="rows">
                <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ rows.comm_type }}</span>
            </template>
            <template #rate="rows">
                <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ rows.rate }}</span>
            </template>
            <template #product="rows">
                <div class="flex gap-2">
                    <template v-for="(product, index) in rows.product" :key="index">
                        <div class="bg-primary-25 border-[0.2px] border-solid border-grey-100 rounded-[1px] size-10"></div>
                    </template>
                </div>
            </template>
        </Table>
    </div>

    <Modal 
        :show="isDeleteCommOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/deleteCommission/${selectedProduct}`"
        :confirmationTitle="'Delete this commission type?'"
        :confirmationMessage="'Are you sure you want to delete the selected commission type? This action cannot be undone.'"
        @close="closeModal"
        v-if="selectedProduct"
    />

    <Modal
        :show="isEditCommOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Edit Commission Type'"
        @close="closeModal"
    >
        <EditCommission 
            :productNames="productNames"
            :commisionDetails="selectedProduct"
            @closeModal="closeModal"
            @viewEmployeeComm="viewEmployeeComm"
        />
    </Modal>

</template>
