<script setup>
import dayjs from 'dayjs';
import { ref, computed } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import ReservationDetail from './ReservationDetail.vue';
import { useCustomToast, usePhoneUtils, useFileExport } from '@/Composables/index.js';
import { EmptyIllus } from '@/Components/Icons/illus.jsx';

const props = defineProps({
    tables: Array,
    columns: Array,
    rows: Array,
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
    actions: {
        type: Object,
        default: () => {},
    },
});

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();
const { exportToCSV } = useFileExport();

const selectedReservation = ref(null);
const reservationDetailIsOpen = ref(false);


const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showReservationDetailForm = (reservation) => {
    selectedReservation.value = reservation;
    reservationDetailIsOpen.value = true;
}

const hideReservationDetailForm = () => {
    reservationDetailIsOpen.value = false;
    setTimeout(() => {
        selectedReservation.value = null;
    }, 300);
}

const getTableNames = (table_no) => table_no.map(selectedTable => selectedTable.name).join(', ');

const csvExport = () => { 
    const mappedReservations = props.rows.map(row => ({
        'No.': row.reservation_no,
        'Date': dayjs(row.reservation_date).format('DD/MM/YYYY'),
        'Time': dayjs(row.reservation_date).format('HH:mm'),
        'Name': row.name,
        'Pax': row.pax,
        'Table / Room': `"${getTableNames(row.table_no)}"`,
        'Contact No.': formatPhone(row.phone),
        'Status': row.status,
    }));
    exportToCSV(mappedReservations, 'Reservation History List');
}

const getStatusVariant = (status) => {
    switch (status) {
        case 'Pending': return 'yellow';
        case 'Checked in': return 'default'
        case 'Delayed': return 'red'
        case 'Completed': return 'green'
        case 'No show': return 'blue'
        case 'Cancelled': return 'grey'
    }
}; 
</script>

<template>
    <div class="flex flex-col p-6 gap-y-6 rounded-[5px] border border-red-100 overflow-y-auto">
        <div class="flex justify-between items-center">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">History</span>
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
                        Export
                        <UploadIcon class="size-4 cursor-pointer" />
                    </MenuButton>
                </div>

                <transition 
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0" 
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100" 
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <MenuItems class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg">
                        <MenuItem v-slot="{ active }">
                            <button 
                                type="button" 
                                :class="[
                                    { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': rows.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" 
                                :disabled="rows.length === 0" 
                                @click="csvExport"
                            >
                                CSV
                            </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                            <button 
                                type="button" 
                                :class="[
                                    { 'bg-grey-50 pointer-events-none': rows.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" 
                                :disabled="rows.length === 0"
                            >
                                PDF
                            </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>

        <Table
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :actions="actions"
            :rowType="rowType"
            minWidth="min-w-[915px]"
            @onRowClick="showReservationDetailForm($event.data)"
        >
            <template #empty>
                <div class="flex flex-col gap-5 items-center">
                    <EmptyIllus/>
                    <span class="text-primary-900 text-sm font-medium">You haven't added any reservation yet...</span>
                </div>
            </template>
            <template #reservation_no="row">{{ row.reservation_no }}</template>
            <template #res_date="row">{{ dayjs(row.reservation_date).format('DD/MM/YYYY') }}</template>
            <template #res_time="row">{{ dayjs(row.reservation_date).format('HH:mm') }}</template>
            <template #name="row">
                <div class="flex items-center gap-x-2">
                    <div class="size-4 bg-primary-100 rounded-full" v-if="row.customer_id"></div>
                    {{ row.name }}
                </div>
            </template>
            <template #table_no="row">{{ getTableNames(row.table_no) }}</template>
            <template #phone="row">{{ formatPhone(row.phone) }}</template>
            <template #status="row"><Tag :value="row.status" :variant="getStatusVariant(row.status)" /></template>
        </Table>
    </div>

    <!-- View reservation detail -->
    <Modal 
        :title="'Reservation Detail'"
        :show="reservationDetailIsOpen" 
        :maxWidth="'sm'" 
        @close="hideReservationDetailForm"
    >
        <ReservationDetail :reservation="selectedReservation" :tables="tables" viewOnly @close="hideReservationDetailForm" />
    </Modal>
</template>
