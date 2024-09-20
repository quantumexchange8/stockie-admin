<script setup>
import Button from "@/Components/Button.vue";
import { EmptyCommissionIllust } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import { Head } from "@inertiajs/vue3";
import { FilterMatchMode } from "primevue/api";
import { ref } from "vue";
import AddCommission from "./AddCommission.vue";
import EditCommission from "./EditCommission.vue";

const props = defineProps ({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    actions: {
        type: Object,
        default: () => {},
    },
    productNames: {
        type: Array,
        required: true,
    }
})
const isNewCommissionOpen = ref(false);
const isEditCommOpen = ref(false);
const isDeleteCommOpen = ref(false);
const selectedProduct = ref();

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
                        @closeModal="closeModal"
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
        />
    </Modal>

</template>
