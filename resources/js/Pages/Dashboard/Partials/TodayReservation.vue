<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import Table from '@/Components/Table.vue';
import { EmptyIllus, UndetectableIllus } from '@/Components/Icons/illus';
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';
import CheckInGuestForm from '@/Pages/Reservation/Partials/CheckInGuestForm.vue';
import ReservationDetail from '@/Pages/Reservation/Partials/ReservationDetail.vue';
import DelayReservationForm from '@/Pages/Reservation/Partials/DelayReservationForm.vue';
import CancelReservationForm from '@/Pages/Reservation/Partials/CancelReservationForm.vue';
import { CheckCircleIcon, CircledArrowHeadRightIcon2, CircledTimesIcon, DefaultIcon, DelayedIcon, DinnerTableIcon, HorizontalDotsIcon, HourGlassIcon, NoShowIcon, UserIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
    customers: Array,
    tables: Array,
    occupiedTables: Array,
    waiters: Array,
    columns: Array,
    rows: {
        type: Array,
        default: []
    },
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
    actions: {
        type: Object,
        default: () => {},
    },
})

const emit = defineEmits(['fetchReservations']);

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id);

const selectedReservation = ref(null);
const actionType = ref('');
const reservationDetailIsOpen = ref(false);
const checkInFormIsOpen = ref(false);
const delayReservationFormIsOpen = ref(false);
const cancelReservationFormIsOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const closeType = ref(null);
const op = ref(null);

const form = useForm({ handled_by: userId.value });

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

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
            
            closeOverlay();
            emit('fetchReservations')
        },
    })
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
}

// const showReservationDetailForm = (reservation) => {
//     selectedReservation.value = reservation;
//     reservationDetailIsOpen.value = true;
// }

// const hideReservationDetailForm = () => {
//     reservationDetailIsOpen.value = false;
//     setTimeout(() => selectedReservation.value = null, 300);
// }

// const showCheckInForm = (reservation) => {
//     selectedReservation.value = reservation;
//     checkInFormIsOpen.value = true;
// }

// const hideCheckInForm = () => {
//     checkInFormIsOpen.value = false;
//     setTimeout(() =>selectedReservation.value = null, 300);
// }

// const showDelayReservationForm = (reservation) => {
//     selectedReservation.value = reservation;
//     delayReservationFormIsOpen.value = true;
// }

// const hideDelayReservationForm = () => {
//     delayReservationFormIsOpen.value = false;
//     setTimeout(() =>selectedReservation.value = null, 300);
// }

// const showCancelReservationForm = (reservation) => {
//     selectedReservation.value = reservation;
//     cancelReservationFormIsOpen.value = true;
// }

// const hideCancelReservationForm = () => {
//     cancelReservationFormIsOpen.value = false;
//     setTimeout(() =>selectedReservation.value = null, 300);
// }

const getTableNames = (table_no) => table_no.map(selectedTable => selectedTable.name).join(', ');

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

const openActionMenu = (event, reservation) => {
    selectedReservation.value = reservation;
    handleDefaultClick(event);
    openOverlay(event);
}

const openOverlay = (event) =>  op.value.show(event);

const closeOverlay = () => {
    if (op.value) op.value.hide();
};
</script>

<template>
    <div class="flex flex-col p-6 gap-y-6 items-center shrink-0 rounded-[5px] border border-solid border-primary-100">
        <div class="flex justify-between items-center self-stretch">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Today's Reservation</span>
            <Link :href="route('reservations')">
                <CircledArrowHeadRightIcon2  
                    class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                />
            </Link>
        </div>
        
        <!-- <template v-if="rows.length > 0">
            <Table
                :variant="'list'"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :actions="actions"
                :rowType="rowType"
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
                <template #res_time="row">{{ dayjs(row.reservation_date).format('HH:mm') }}</template>
                <template #name="row">
                    <div class="flex items-center gap-x-2">
                        <img 
                            :src="row.reserved_for.image ? row.reserved_for.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="CustomerImage"
                            class="size-4 rounded-full"
                            v-if="row.customer_id"
                        >
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
        </template>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template> -->
        <div class="grid xl:grid-cols-3 grid-cols-3 items-start gap-2.5 self-stretch" v-if="rows.length > 0">
                <div class="items-start content-start gap-5 self-stretch flex-wrap" v-for="reservation in rows" @click="openForm('show', reservation)">
                    <div class="flex flex-col p-4 items-start gap-4 flex-[1_0_0] rounded-[5px] border border-solid border-grey-100 bg-white shadow-sm">
                        <div class="flex items-start gap-2 self-stretch">
                            <div class="flex items-center gap-2 flex-[1_0_0]">
                                <span class="text-grey-950 text-base font-bold line-clamp-1">{{ dayjs(reservation.reservation_date).format('YYYY-MM-DD') }}</span>
                                <span class="text-grey-950 text-base font-bold">{{ dayjs(reservation.reservation_date).format('HH:mm') }}</span>
                                <DelayedIcon class="size-5" v-if="reservation.status === 'Delayed'"/>
                            </div>
                            <div class="flex-shrink-0 cursor-pointer size-5" v-if="['Pending', 'Delayed', 'Checked in'].includes(reservation.status)" @click="openActionMenu($event, reservation)">
                                <HorizontalDotsIcon />
                            </div>
                        </div>
                        <div class="w-full h-[1px] bg-[#eceff2]"></div>
                        <div class="flex items-start gap-2 self-stretch">
                            <div class="flex items-center gap-2 flex-[1_0_0] self-stretch">
                                <div class="flex flex-col justify-between items-start flex-[1_0_0] self-stretch">
                                    <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-bold">{{ reservation.name }}</span>
                                    <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-normal">{{ formatPhone(reservation.phone) }}</span>
                                </div>
                                <img
                                    :src="reservation.reserved_for.image"
                                    alt="CustomerIcon"
                                    class="rounded-full size-[38px]"
                                    v-if="reservation.reserved_for && reservation.reserved_for.image"
                                >
                                <DefaultIcon v-else />
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-3 self-stretch">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex items-start gap-2">
                                    <DinnerTableIcon class="size-[18px]"/>
                                    <span class="text-grey-950 text-sm font-semibold">{{ getTableNames(reservation.table_no) }}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <UserIcon class="size-[18px]"/>
                                    <span class="text-grey-950 text-sm font-semibold">{{ reservation.pax }}</span>
                                </div>
                            </div>
                        </div>
                        <Button
                            :variant="'primary'"
                            :type="'submit'"
                            :disabled="reservation.status === 'Checked in'"
                            @click="openForm('check-in', selectedReservation)"
                        >
                            {{ reservation.status === 'Checked in' ?  'Customer checked-in' : 'Check-in customer' }}
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-5 items-center"  v-else>
                <EmptyIllus />
                <span class="text-primary-900 text-sm font-medium">You haven't added any reservation yet...</span>
            </div>
        </div>
    
    <!-- Open reservation action menu -->
    <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-1">
        <div class="flex flex-col gap-y-0.5 bg-white min-w-[247px]">
            <!-- <div class="p-3 flex items-center justify-between" @click="openForm('check-in', selectedReservation)">
                <p class="text-grey-900 text-base font-medium ">Check in customer </p>
                <CheckCircleIcon class="flex-shrink-0 size-5 text-primary-900" />
            </div> -->

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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeForm('stay')"
            @leave="closeForm('leave')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeForm('stay')"
            @leave="closeForm('leave')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeForm('stay')"
            @leave="closeForm('leave')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeForm('stay')"
            @leave="closeForm('leave')"
        />
    </Modal>
</template>

