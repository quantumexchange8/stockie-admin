<script setup>
import dayjs from 'dayjs';
import { ref, computed, onMounted, watch } from 'vue'
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import { EmptyIllus2 } from '@/Components/Icons/illus';
import { usePhoneUtils } from '@/Composables/index.js';
import EditReservationForm from './EditReservationForm.vue';

const props = defineProps({
    reservation: {
        type: Object,
        default: () => {}
    },
    customers: Array,
    tables: Array,
    viewOnly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'fetchReservations']);

const { formatPhone } = usePhoneUtils();

const selectedReservation = ref(props.reservation);
const editReservationIsOpen = ref(false);
const customers = computed(() => props.customers ?? []);
const tables = computed(() => props.tables);
const deleteReservationFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showEditReservationForm = () => {
    editReservationIsOpen.value = true; 
    isDirty.value = false;
}

const unsaved = (status) => {
    emit('close', status)
}

const hideEditReservationForm = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value) isUnsavedChangesOpen.value = true;
            else setTimeout(() => editReservationIsOpen.value = false, 100);
            break;
        }
        case 'stay': isUnsavedChangesOpen.value = false; break;
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            setTimeout(() => editReservationIsOpen.value = false, 100);
            break;
        }
    }
};

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

const getTableNames = (table_no) => {
    return table_no.reduce((tables, table) => {
        if (!tables.includes(table.name)) tables.push(table.name);
        return tables;
    }, []).join(', ');
}; 

const getStatusLabel = (status) => {
    switch (status) {
        case 'Checked in': return 'Check in time';
        case 'No show':
        case 'Delayed': return 'Delayed to';
        case 'Completed': return 'Completed time';
        case 'Cancelled': return 'Cancelled on';
    }
}; 

const getHandledByLabel = (status) => {
    switch (status) {
        case 'Checked in':
        case 'Completed': return 'Checked in by';
        case 'No show':
        case 'Delayed': return 'Update by';
        case 'Cancelled': return 'Cancelled by';
    }
}; 

const fetchReservations = () => {
    emit('fetchReservations');
    emit('close');
}; 

const showReservationForm = (event, id) => {
    handleDefaultClick(event);
    deleteReservationFormIsOpen.value = true;
}

const hideReservationForm = () => deleteReservationFormIsOpen.value = false;

watch(() => props.reservation, (newValue) => {
    selectedReservation.value = newValue;
}, { immediate: true });

</script>

<template>
    <div class="flex flex-col gap-y-6 items-start">
        <div class="flex flex-col items-start gap-y-4 self-stretch max-h-[calc(100dvh-15rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <Tag :value="selectedReservation.status" :variant="getStatusVariant(selectedReservation.status)" />
            <div class="flex flex-col gap-y-5 items-start self-stretch">
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Reservation date & time</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ dayjs(selectedReservation.reservation_date).format('DD/MM/YYYY, HH:mm') }}</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">No.of pax</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.pax }}</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Guest name</p>
                        <div class="flex items-center gap-x-2">
                            <!-- <div class="size-4 bg-primary-100 rounded-full" v-if="reservation.reserved_for"></div> -->
                            <img 
                                :src="selectedReservation.reserved_for?.image ? selectedReservation.reserved_for.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                                alt="" 
                                class="size-4 bg-primary-100 rounded-full"
                                v-if="selectedReservation.reserved_for"
                            />
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.name }}</p>
                        </div>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Contact no.</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ formatPhone(selectedReservation.phone) }}</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Table no.</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ getTableNames(selectedReservation.table_no) }}</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Table lock before</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.lock_before_minutes }} minutes</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Grace period</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.grace_period}} minutes</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">Reserved by</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.reserved_by.full_name }}</p>
                    </div>
                </div>

                <template v-if="['Checked in', 'Delayed', 'Completed', 'Cancelled', 'No show'].includes(selectedReservation.status)">
                    <hr class="w-full border-b border-grey-100">

                    <div class="w-full flex gap-x-5 items-start">
                        <div class="w-1/2 flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ selectedReservation.status === 'Completed' ? getStatusLabel('Checked in') : getStatusLabel(selectedReservation.status) }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">
                                {{ selectedReservation.status === 'Cancelled' 
                                    ? dayjs(selectedReservation.updated_at).format('DD/MM/YYYY, HH:mm') 
                                    : selectedReservation.action_date
                                        ? dayjs(selectedReservation.action_date).format('DD/MM/YYYY, HH:mm')
                                        : '-' }}
                            </p>
                        </div>
                        
                        <div class="w-1/2 flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ getHandledByLabel(selectedReservation.status) }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.handled_by.full_name }}</p>
                        </div>
                    </div>

                    <div class="w-full flex gap-x-5 items-start" v-if="selectedReservation.status === 'Completed' || selectedReservation.status === 'Cancelled'">
                        <div class="w-1/2 flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ selectedReservation.status === 'Completed' ? getStatusLabel(selectedReservation.status) : 'Cancellation Reason' }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.status === 'Completed' ? dayjs(selectedReservation.updated_at).format('DD/MM/YYYY, HH:mm') : selectedReservation.cancel_type }}</p>
                        </div>
                        
                        <div class="w-1/2 flex flex-col gap-y-1 items-start" v-if="selectedReservation.status === 'Cancelled'">
                            <p class="text-grey-900 text-base font-normal self-stretch">Remarks</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.remark || '-' }}</p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-col gap-y-3 p-3 items-start content-start self-stretch rounded-[2px] bg-primary-25">
                <p class="text-primary-950 text-base font-bold self-stretch">Guest History</p>
                <div class="flex flex-col gap-y-1 items-start self-stretch" v-if="selectedReservation.reserved_for">
                    <p class="text-grey-900 text-base font-normal self-stretch"><span class="font-bold">{{ selectedReservation.reserved_for.reservations.length }}</span> reservation has been made by this guest</p>
                    <p class="text-grey-900 text-base font-normal self-stretch"><span class="font-bold">{{ selectedReservation.reserved_for.reservation_cancelled.length }}</span> reservation has been cancelled by this guest</p>
                    <p class="text-grey-900 text-base font-normal self-stretch"><span class="font-bold">{{ selectedReservation.reserved_for.reservation_abandoned.length }}</span> times the guest did not show up</p>
                </div>
                <div class="flex items-start justify-between self-stretch" v-else>
                    <p class="text-primary-950 text-xs font-normal text-center">No reservation history for this guest</p>
                    <EmptyIllus2 class="flex flex-shrink-0" />
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch" v-if="!viewOnly">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                :disabled="selectedReservation.status === 'Checked in'"
                @click="showReservationForm"
            >
                Delete
            </Button>
            <Button
                :type="'button'"
                :size="'lg'"
                :disabled="selectedReservation.status === 'Checked in'"
                @click="showEditReservationForm"
            >
                Edit
            </Button>
        </div>
    </div>
            
    <!-- Edit reservation -->
    <Modal 
        v-if="!viewOnly"
        :title="'Edit Reservation'"
        :show="editReservationIsOpen" 
        :maxWidth="'sm'" 
        @close="hideEditReservationForm('close')"
    >
        <template v-if="selectedReservation">
            <EditReservationForm 
                :reservation="selectedReservation" 
                :customers="customers" 
                :tables="tables" 
                @isDirty="isDirty=$event"
                @close="hideEditReservationForm" 
                @fetchReservations="fetchReservations"
                @update:reservation="selectedReservation = $event"
            />
        </template>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="hideEditReservationForm('stay')"
            @leave="hideEditReservationForm('leave')"
        >
        </Modal>
    </Modal>

    <Modal 
        :show="deleteReservationFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`reservation/${selectedReservation.id}`"
        :confirmationTitle="'Delete this reservation?'"
        :confirmationMessage="'Are you sure you want to delete the selected reservation? This action cannot be undone.'"
        @close="hideReservationForm"
        v-if="selectedReservation"
    />
</template>
