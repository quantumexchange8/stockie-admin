<script setup>
import { ref } from "vue";
import { EmptyIllus } from "@/Components/Icons/illus.jsx";
import Modal from "@/Components/Modal.vue";
import Table from "@/Components/Table.vue";
import Button from "@/Components/Button.vue";
import SearchBar from "@/Components/SearchBar.vue";
import { FilterMatchMode } from 'primevue/api';
import { PlusIcon, EditIcon, DeleteIcon } from '@/Components/Icons/solid';
import EditTier from "./EditTier.vue";
import CreatePointForm from "./CreatePointForm.vue";
import EditPointForm from "./EditPointForm.vue";

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    inventoryItems: {
        type: Array,
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

const isPointFormOpen = ref(false);
const editPointFormIsOpen = ref(false);
const deletePointFormIsOpen = ref(false);
const selectedPoint = ref(null);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showAddPointForm = () => {
    isPointFormOpen.value = true;
};

const hidePointForm = () => {
    isPointFormOpen.value = false;
};

const showEditPointForm = (event, tier) => {
    handleDefaultClick(event);
    selectedPoint.value = tier;
    editPointFormIsOpen.value = true;
}

const hideEditPointForm = () => {
    editPointFormIsOpen.value = false;
    setTimeout(() => {
        selectedPoint.value = null;
    }, 300);
}

const showDeletePointForm = (event, id) => {
    handleDefaultClick(event);
    selectedPoint.value = id;
    deletePointFormIsOpen.value = true;
}

const hideDeletePointForm = () => {
    deletePointFormIsOpen.value = false;
    setTimeout(() => {
        selectedPoint.value = null;
    }, 300);
}
</script>

<template>
    <div class="flex flex-col p-6 gap-5 rounded-[5px] border border-red-100 overflow-y-auto">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Redeemable Item List</span>
        <div class="flex flex-col gap-6">
            <div class="flex gap-5 flex-wrap sm:flex-nowrap">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="filters['global'].value"
                />

                <!-- <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="!w-fit flex items-center gap-2"
                    @click="showAddPointForm"
                >
                    <template #icon>
                        <PlusIcon />
                    </template>
                    Redeemable Item
                </Button> -->
            </div>
            <!--CurrentTierTable-->
            <Table
                :variant="'list'"
                :searchFilter="true"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :actions="actions"
                :rowType="rowType"
                :filters="filters"
                minWidth="min-w-[700px]"
            >
                <template #empty>
                    <div class="flex flex-col gap-5 items-center">
                        <EmptyIllus/>
                        <span class="text-primary-900 text-sm font-medium">You haven't added any redeemable item yet...</span>
                    </div>
                </template>
                <!-- <template #editAction="row">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditPointForm($event, row)"
                    />
                </template>
                <template #deleteAction="row">
                    <DeleteIcon
                        class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                            @click="showDeletePointForm($event, row.id)"
                    />
                </template> -->
                <template #product_name="row">
                    <div class="flex flex-nowrap items-center gap-3">
                        <!-- <div class="bg-grey-50 border border-grey-200 h-14 w-14"></div> -->
                        <img 
                            :src="row.image ? row.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="" 
                            class="bg-grey-50 border border-grey-200 h-14 w-14"
                        />
                        <span class="text-grey-900 text-sm font-medium">{{ row.product_name }}</span>
                    </div>
                </template>
                <template #point="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.point }} pts</span>
                </template>
                <template #stock_left="row">
                    <span class="text-primary-600 inline-block align-middle">{{ row.stock_left }}</span>
                </template>
            </Table>
            <!-- <Modal
                :show="isPointFormOpen"
                :title="'Add Redeemable Item'"
                :maxWidth="'lg'"
                @close="hidePointForm"
            >
                <CreatePointForm
                    :inventoriesArr="inventoryItems" 
                    @close="hidePointForm" 
                />
            </Modal>
            <Modal 
                :title="'Edit Redeemable Item'"
                :show="editPointFormIsOpen" 
                :maxWidth="'lg'" 
                @close="hideEditPointForm"
            >
                <template v-if="selectedPoint">
                    <EditPointForm
                        :point="selectedPoint"
                        :inventoriesArr="inventoryItems" 
                        @close="hideEditPointForm"
                    />
                </template>
            </Modal>
            <Modal 
                :show="deletePointFormIsOpen" 
                :maxWidth="'2xs'" 
                :closeable="true" 
                :deleteConfirmation="true"
                :deleteUrl="actions.delete(selectedPoint)"
                :confirmationTitle="'Delete this item?'"
                :confirmationMessage="'Are you sure you want to delete the selected redeemable item? This action cannot be undone.'"
                @close="hideDeletePointForm"
                v-if="selectedPoint"
            /> -->
        </div>
    </div>
</template>
