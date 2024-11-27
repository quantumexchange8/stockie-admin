<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
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
import { PlusIcon, EditIcon, DeleteIcon, ListViewIcon, GridViewIcon, UploadIcon, SquareStickerIcon, HorizontalDotsIcon, CheckIcon, CheckCircleIcon, NoShowIcon, HourGlassIcon, CircledTimesIcon, TimesIcon } from '@/Components/Icons/solid';
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import dayjs from 'dayjs';
import MakeReservationForm from './MakeReservationForm.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import ReservationDetail from './ReservationDetail.vue';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import CheckInGuestForm from './CheckInGuestForm.vue';
import { usePhoneUtils, useCustomToast, useFileExport } from '@/Composables/index.js';
import CancelReservationForm from './CancelReservationForm.vue';
import DelayReservationForm from './DelayReservationForm.vue';

const props = defineProps({
    customers: Array,
    tables: Array,
    occupiedTables: Array,
    waiters: Array,
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

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();
const { exportToCSV } = useFileExport();

const emit = defineEmits(['fetchReservations']);

const op = ref(null);
const checkInOverlay = ref(null);
const filters = ref({'global': {value: null, matchMode: FilterMatchMode.CONTAINS}});
const selectedReservation = ref(null);
const actionType = ref('');
const makeReservationFormIsOpen = ref(false);
const reservationDetailIsOpen = ref(false);
const checkInFormIsOpen = ref(false);
const delayReservationFormIsOpen = ref(false);
const cancelReservationFormIsOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const closeType = ref(null);

const form = useForm({ handled_by: userId.value });

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const openOverlay = (event) =>  op.value.show(event);

const closeOverlay = () => op.value.hide();

const openActionMenu = (event, reservation) => {
    selectedReservation.value = reservation;
    handleDefaultClick(event);
    openOverlay(event);
}

const markReservationAsNoShow = (id) => { 
    form.put(route('reservations.markAsNoShow', id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: "Reservation has been marked as 'No show'.",
                });
            }, 200);
            emit('fetchReservations')
        },
    })
    closeOverlay();
};

const openForm = (action, reservation) => {
    actionType.value = action;
    isDirty.value = false;

    if (actionType.value === 'create') makeReservationFormIsOpen.value = true;
    if (reservation) {
        // Set value of selected reservation on open
        if (actionType.value !== 'create') selectedReservation.value = reservation;

        if (actionType.value === 'show') reservationDetailIsOpen.value = true;
        if (actionType.value === 'check-in') checkInFormIsOpen.value = true;
        if (actionType.value === 'delay') delayReservationFormIsOpen.value = true;
        if (actionType.value === 'cancel') cancelReservationFormIsOpen.value = true;

        setTimeout(() => closeOverlay(), 100);
    }
}

// const closeForm = (type) => {
//     closeType.value = type;

//     if (actionType.value === 'create') {
//         if(closeType === 'close') {
//             if(isDirty.value){
//                 isUnsavedChangesOpen.value = true;
//             } else {
//                 makeReservationFormIsOpen.value = false;
//             }
//         };
//         if(closeType === 'stay') {
//             isUnsavedChangesOpen.value = false;
//         };
//         if(closeType === 'leave') {
//             isUnsavedChangesOpen.value = false;
//             makeReservationFormIsOpen.value = false;
//         }
//     }
//     if (actionType.value === 'show') reservationDetailIsOpen.value = false;
//     if (actionType.value === 'check-in') checkInFormIsOpen.value = false;
//     if (actionType.value === 'delay') delayReservationFormIsOpen.value = false;
//     if (actionType.value === 'cancel') cancelReservationFormIsOpen.value = false;
    
//     // Reset value of selected reservation on close
//     if (actionType.value !== 'create') setTimeout(() => selectedReservation.value = null, 300);

//     actionType.value = '';
// }

const closeForm = (type) => {
    closeType.value = type;

    switch(type){
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                if (actionType.value === 'create') makeReservationFormIsOpen.value = false;
                if (actionType.value === 'show') reservationDetailIsOpen.value = false;
                if (actionType.value === 'check-in') checkInFormIsOpen.value = false;
                if (actionType.value === 'delay') delayReservationFormIsOpen.value = false;
                if (actionType.value === 'cancel') cancelReservationFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': isUnsavedChangesOpen.value = false; break;
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            if (actionType.value === 'create') makeReservationFormIsOpen.value = false;
            if (actionType.value === 'show') reservationDetailIsOpen.value = false;
            if (actionType.value === 'check-in') checkInFormIsOpen.value = false;
            if (actionType.value === 'delay') delayReservationFormIsOpen.value = false;
            if (actionType.value === 'cancel') cancelReservationFormIsOpen.value = false;
            break;
        }
    }

    // if (actionType.value !== 'create') setTimeout(() => selectedReservation.value = null, 300);
    // actionType.value = '';
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
    exportToCSV(mappedReservations, 'Upcoming Reservation List');
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
    <div class="flex flex-col p-6 gap-5 rounded-[5px] border border-red-100 overflow-y-auto">
        <div class="flex justify-between items-center">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Upcoming Reservation</span>
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

        <div class="flex flex-col gap-6">
            <div class="w-full flex gap-5 flex-wrap md:flex-nowrap">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="filters['global'].value"
                />

                <div class="w-full flex flex-col sm:flex-row items-center justify-end gap-5">
                    <Button
                        :type="'button'"
                        variant="tertiary"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="w-full sm:w-1/2 md:!w-fit flex items-center gap-2"
                        :href="route('reservations.viewReservationHistory')"
                    >
                        <template #icon><SquareStickerIcon class="flex-shrink-0 !size-5" /></template>
                        View Reservation History
                    </Button>

                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="w-full sm:w-1/2 md:!w-fit flex items-center gap-2"
                        @click="openForm('create')"
                    >
                        <template #icon><PlusIcon class="flex-shrink-0 !size-5" /></template>
                        Add Reservation
                    </Button>
                </div>
            </div>

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
                minWidth="min-w-[950px]"
                @onRowClick="openForm('show', $event.data)"
            >
                <template #empty>
                    <div class="flex flex-col gap-5 items-center">
                        <EmptyIllus/>
                        <span class="text-primary-900 text-sm font-medium">You haven't added any reservation yet...</span>
                    </div>
                </template>
                <template #reservation_no="row">{{ row.reservation_no }}</template>
                <template #res_date="row">{{ dayjs(row.status === 'Delayed' && row.action_date ? row.action_date : row.reservation_date).format('DD/MM/YYYY') }}</template>
                <template #res_time="row">{{ dayjs(row.status === 'Delayed' && row.action_date ? row.action_date : row.reservation_date).format('HH:mm') }}</template>
                <template #name="row">
                    <div class="flex items-center gap-x-2">
                        <!-- <div class="size-4 bg-primary-100 rounded-full" v-if="row.customer_id"></div> -->
                        <img 
                            :src="row.reserved_for?.image ? row.reserved_for.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                            alt="" 
                            class="w-3 h-3 bg-red-900 rounded-full"
                            v-if="row.customer_id"
                        />
                        {{ row.name }}
                    </div>
                </template>
                <template #table_no="row">{{ getTableNames(row.table_no) }}</template>
                <template #phone="row">{{ formatPhone(row.phone) }}</template>
                <template #status="row"><Tag :value="row.status" :variant="getStatusVariant(row.status)" /></template>
                <template #action="row">
                    <div class="w-full flex items-center justify-center" v-if="['Pending', 'Delayed', 'Checked in'].includes(row.status)" @click="openActionMenu($event, row)">
                        <HorizontalDotsIcon class="flex-shrink-0 cursor-pointer" />
                    </div>
                </template>
            </Table>
    
            <!-- Open reservation action menu -->
            <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-1">
                <div class="flex flex-col gap-y-0.5 bg-white min-w-[247px]">
                    <div class="p-3 flex items-center justify-between" @click="openForm('check-in', selectedReservation)">
                        <p class="text-grey-900 text-base font-medium ">Check in customer </p>
                        <CheckCircleIcon class="flex-shrink-0 size-5 text-primary-900" />
                    </div>

                    <div class="p-3 flex items-center justify-between" @click="markReservationAsNoShow(selectedReservation.id)">
                        <p class="text-grey-900 text-base font-medium ">Mark as no show </p>
                        <NoShowIcon class="flex-shrink-0 size-5 text-primary-900" />
                    </div>

                    <div class="p-3 flex items-center justify-between" @click="openForm('delay', selectedReservation)">
                        <p class="text-grey-900 text-base font-medium ">Delay reservation </p>
                        <HourGlassIcon class="flex-shrink-0 size-5 text-primary-900" />
                    </div>

                    <div class="p-3 flex items-center justify-between" @click="openForm('cancel', selectedReservation)">
                        <p class="text-primary-800 text-base font-medium ">Cancel reservation </p>
                        <CircledTimesIcon class="flex-shrink-0 size-5 fill-primary-600 text-white" />
                    </div>
                </div>
            </OverlayPanel>

            <!-- Make reservation -->
            <Modal
                :show="makeReservationFormIsOpen"
                :title="'Make Reservation'"
                :maxWidth="'sm'"
                @close="closeForm('close')"
            >
                <MakeReservationForm
                    :customers="customers" 
                    :tables="tables" 
                    @close="closeForm"
                    @isDirty="isDirty = $event" 
                    @fetchReservations="$emit('fetchReservations')"
                />

            </Modal>
            
            <!-- View reservation detail -->
            <Modal 
                :title="'Reservation Detail'"
                :show="reservationDetailIsOpen" 
                :maxWidth="'sm'" 
                @close="closeForm('close')"
            >
                <ReservationDetail
                    :reservation="selectedReservation" 
                    :customers="customers" 
                    :tables="tables" 
                    @close="closeForm" 
                    @fetchReservations="$emit('fetchReservations')"
                />
            </Modal>

            <!-- Check in customer -->
            <Modal 
                :title="'Assign Seat'"
                :show="checkInFormIsOpen" 
                :maxWidth="'xs'" 
                @close="closeForm('close')"
            >
                <CheckInGuestForm 
                    :reservation="selectedReservation" 
                    :waiters="waiters" 
                    :tables="tables" 
                    :occupiedTables="occupiedTables" 
                    @isDirty="isDirty=$event"
                    @close="closeForm" 
                />
            </Modal>

            <Modal 
                :title="'Delay Reservation'"
                :show="delayReservationFormIsOpen" 
                :maxWidth="'2xs'" 
                @close="closeForm('close')"
            >
                <DelayReservationForm 
                    :reservation="selectedReservation" 
                    @close="closeForm" 
                    @isDirty="isDirty=$event"
                    @fetchReservations="$emit('fetchReservations')"
                />
            </Modal>

            <Modal 
                :title="'Cancel Reservation'"
                :show="cancelReservationFormIsOpen" 
                :maxWidth="'sm'" 
                @close="closeForm('close')"
            >
                <CancelReservationForm 
                    :reservation="selectedReservation" 
                    @close="closeForm" 
                    @isDirty="isDirty=$event"
                    @fetchReservations="$emit('fetchReservations')"
                />
            </Modal>
        </div>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeForm('stay')"
            @leave="closeForm('leave')"
        />
    </div>
</template>
