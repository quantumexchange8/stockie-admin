<script setup>
import { computed, ref } from "vue";
import { EmptyIllus } from "@/Components/Icons/illus.jsx";
import Modal from "@/Components/Modal.vue";
import Table from "@/Components/Table.vue";
import { EditIcon, DeleteIcon } from '@/Components/Icons/solid';
import EditReservationForm from "./EditReservationForm.vue";

const props = defineProps({
    table: Object,
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
    actions: {
        type: Object,
        default: () => {},
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
});

const editReservationFormIsOpen = ref(false);
const deleteReservationFormIsOpen = ref(false);
const selectedReservation = ref(null);

const reservationsTotalPages = computed(() => {
    return Math.ceil(props.rows.length / props.rowsPerPage);
});

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showEditReservationForm = (event, reservation) => {
    handleDefaultClick(event);
    selectedReservation.value = reservation;
    editReservationFormIsOpen.value = true;
}

const hideEditReservationForm = () => {
    editReservationFormIsOpen.value = false;
    setTimeout(() => {
        selectedReservation.value = null;
    }, 300);
}

const showDeleteReservationForm = (event, id) => {
    handleDefaultClick(event);
    selectedReservation.value = id;
    deleteReservationFormIsOpen.value = true;
}

const hideDeleteReservationForm = () => {
    deleteReservationFormIsOpen.value = false;
    setTimeout(() => {
        selectedReservation.value = null;
    }, 300);
}
</script>

<template>
    <div class="flex flex-col gap-5 rounded-[5px] overflow-y-auto">
        <Table
            :variant="'list'"
            :rows="rows"
            :totalPages="reservationsTotalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :actions="actions"
            :rowType="rowType"
            minWidth="min-w-[526px]"
        >
            <template #empty>
                <div class="flex flex-col gap-5 items-center">
                    <EmptyIllus/>
                    <span class="text-primary-900 text-sm font-medium">You haven't added any redeemable item yet...</span>
                </div>
            </template>
            <template #editAction="row">
                <EditIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="showEditReservationForm($event, row)"
                />
            </template>
            <template #deleteAction="row">
                <DeleteIcon
                    class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="showDeleteReservationForm($event, row.id)"
                />
            </template>
        </Table>
        
        <Modal 
            :title="'Edit Reservation Detail'"
            :show="editReservationFormIsOpen" 
            :maxWidth="'xs'" 
            @close="hideEditReservationForm"
        >
            <template v-if="selectedReservation">
                <EditReservationForm
                    :reservationTable="selectedReservation"
                    @close="hideEditReservationForm"
                />
            </template>
        </Modal>
        <Modal 
            :show="deleteReservationFormIsOpen" 
            :closeable="true" 
            :deleteConfirmation="true"
            :deleteUrl="actions.delete(selectedReservation)"
            maxWidth="2xs" 
            confirmationTitle="Delete Reservation"
            confirmationMessage="Are you sure you want to delete this reservation? The action cannot be undone."
            toastMessage="Selected reservation has been deleted successfully."
            @close="hideDeleteReservationForm"
            v-if="selectedReservation"
        />
    </div>
</template>
