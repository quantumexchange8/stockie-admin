<script setup>
import dayjs from 'dayjs';
import { ref, computed, onMounted, watch } from 'vue'
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import { EmptyIllus2 } from '@/Components/Icons/illus';
import { usePhoneUtils } from '@/Composables/index.js';
import EditReservationForm from './EditReservationForm.vue';
import { wTrans } from 'laravel-vue-i18n';

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

const getTranslatedStatus = (status) => {
    switch (status) {
        case 'Pending': return wTrans('public.pending').value;
        case 'Checked in': return wTrans('public.checked_in').value;
        case 'Delayed': return wTrans('public.delayed').value;
        case 'Completed': return wTrans('public.completed').value;
        case 'No show': return wTrans('public.no_show').value;
        case 'Cancelled': return wTrans('public.cancelled').value;
    }
}; 

const getTranslatedReason = (status) => {
    switch (status) {
        case 'Change of plan': return wTrans('public.reservation.change_plan').value;
        case 'Feeling unwell': return wTrans('public.reservation.feeling_unwell').value;
        case 'Bad weather': return wTrans('public.reservation.bad_weather').value;
        case 'Work conflicts': return wTrans('public.reservation.work_conflicts').value;
        case 'Family emergency': return wTrans('public.reservation.family_emergency').value;
        case 'Forgotten reservation': return wTrans('public.reservation.forgotten_reservation').value;
        case 'Others (specify under Remarks)': return wTrans('public.reservation.other_reason').value;
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
        case 'Checked in': return wTrans('public.reservation.check_in_time');
        case 'No show':
        case 'Delayed': return wTrans('public.reservation.delayed_to');
        case 'Completed': return wTrans('public.reservation.completed_time');
        case 'Cancelled': return wTrans('public.reservation.cancelled_on');
    }
}; 

const getHandledByLabel = (status) => {
    switch (status) {
        case 'Checked in':
        case 'Completed': return wTrans('public.reservation.checked_in_by');
        case 'No show':
        case 'Delayed': return wTrans('public.reservation.update_by');
        case 'Cancelled': return wTrans('public.reservation.cancelled_by');
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
            <Tag :value="getTranslatedStatus(selectedReservation.status)" :variant="getStatusVariant(selectedReservation.status)" />
            <div class="flex flex-col gap-y-5 items-start self-stretch">
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.reservation_date_time') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ dayjs(selectedReservation.reservation_date).format('DD/MM/YYYY, HH:mm') }}</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.no_of_pax') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.pax }}</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.guest_name') }}</p>
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
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.contact_no') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ formatPhone(selectedReservation.phone) }}</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.table_no') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ getTableNames(selectedReservation.table_no) }}</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.table_lock_before') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.lock_before_minutes }} {{ $tChoice('public.minute', 1) }}</p>
                    </div>
                </div>
                
                <div class="w-full flex gap-x-5 items-start">
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.grace_period') }}</p>
                        <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.grace_period}} {{ $tChoice('public.minute', 1) }}</p>
                    </div>
                    
                    <div class="w-1/2 flex flex-col gap-y-1 items-start">
                        <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.field.reserved_by') }}</p>
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
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ selectedReservation.status === 'Completed' ? getStatusLabel(selectedReservation.status) : $t('public.reservation.cancellation_reason') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.status === 'Completed' ? dayjs(selectedReservation.updated_at).format('DD/MM/YYYY, HH:mm') : getTranslatedReason(selectedReservation.cancel_type) }}</p>
                        </div>
                        
                        <div class="w-1/2 flex flex-col gap-y-1 items-start" v-if="selectedReservation.status === 'Cancelled'">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.remark') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedReservation.remark || '-' }}</p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-col gap-y-3 p-3 items-start content-start self-stretch rounded-[2px] bg-primary-25">
                <p class="text-primary-950 text-base font-bold self-stretch">{{ $t('public.reservation.guest_history') }}</p>
                <div class="flex flex-col gap-y-1 items-start self-stretch" v-if="selectedReservation.reserved_for">
                    <p class="text-grey-900 text-base font-normal self-stretch" v-html="$t('public.reservation.reservation_made_count', { count: selectedReservation.reserved_for.reservations.length })"></p>
                    <p class="text-grey-900 text-base font-normal self-stretch" v-html="$t('public.reservation.reservation_cancelled_count', { count: selectedReservation.reserved_for.reservation_cancelled.length })"></p>
                    <p class="text-grey-900 text-base font-normal self-stretch" v-html="$t('public.reservation.reservation_no_show_count', { count: selectedReservation.reserved_for.reservation_abandoned.length })"></p>
                </div>
                <div class="flex items-start justify-between self-stretch" v-else>
                    <p class="text-primary-950 text-xs font-normal text-center">{{ $t('public.empty.no_reservation_history') }}</p>
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
                {{ $t('public.action.delete') }}
            </Button>
            <Button
                :type="'button'"
                :size="'lg'"
                :disabled="selectedReservation.status === 'Checked in'"
                @click="showEditReservationForm"
            >
                {{ $t('public.action.edit') }}
            </Button>
        </div>
    </div>
            
    <!-- Edit reservation -->
    <Modal 
        v-if="!viewOnly"
        :title="$t('public.reservation.edit_reservation')"
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
        :confirmationTitle="$t('public.reservation.delete_reservation')"
        :confirmationMessage="$t('public.reservation.delete_reservation_message')"
        @close="hideReservationForm"
        v-if="selectedReservation"
    />
</template>
