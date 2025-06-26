<script setup>
import Button from '@/Components/Button.vue';
import dayjs from 'dayjs';
import { ref, watchEffect } from 'vue';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import { NoImgIcon, WaiterIcon, WarningIcon } from '@/Components/Icons/solid';
import { transactionFormat } from '@/Composables';
import RadioButton from 'primevue/radiobutton';
import UpdateShift from './UpdateShift.vue';

dayjs.extend(weekOfYear);
const today = dayjs().startOf('week').add(1, 'day');
const currentWeek = ref(today.week());

const emit = defineEmits(['close', 'update:waiter-shifts', 'updated:waiters']);
const waiters = ref([]);
const findWaiter = ref();
const editShiftIsOpen = ref(false);
const isLoading = ref(false);
const { formatTimeHM } = transactionFormat();
const form = ref({ assign_shift: {} });

const props = defineProps({
    selectedWaiter: [Object, Number],
    weekNo: Number,
});

const fetchWaiterShift = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get('/configurations/viewShift', {
            params: { 
                waiter_id: props.selectedWaiter,
                weeks: props.weekNo,
            }, 
        });

        waiters.value = response.data.waiters;
        findWaiter.value = response.data.findWaiter;

        emit('update:waiter-shifts');

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

watchEffect(() => {
    fetchWaiterShift();
});

const deleteShift = async () => {
    isLoading.value = true;

    try {
        
        const response = await axios.post('/configurations/delete-shift', {
            params: { 
                waiter_id: props.selectedWaiter,
                weeks: props.weekNo,
            }, 
        });

        if (response.status === 200) {
            // toast.add({ severity: 'success', summary: 'Success', detail: 'Shifts updated!', life: 3000 });
            emit('update:waiter-shifts');
            emit('updated:waiters');
            emit('close');
        }

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}


const editShift = () => {
    editShiftIsOpen.value = true;
}

const closeEditShift = () => {
    editShiftIsOpen.value = false;
}

</script>

<template>

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-5">
            <div class="flex items-center gap-4">
                <div class="w-full h-full max-w-10 max-h-10 rounded-full">
                    <img 
                        v-if="findWaiter && findWaiter.profile_photo_url" 
                        :src="findWaiter.profile_photo_url" 
                        alt="Profile Photo"
                        class="w-full h-full object-cover"
                    />
                    <WaiterIcon v-else class="w-full h-full" />
                </div>
                <div class="flex flex-col gap-1">
                    <div>{{ findWaiter ? findWaiter.full_name : '-'}}</div>
                    <!-- <div>Total:</div> -->
                </div>
            </div>
            <div class="w-full h-[1px] bg-[#ECEFF2]"></div>
            <div class="max-h-[calc(100dvh-17rem)] overflow-y-auto scrollbar-webkit scrollbar-thin" v-if="waiters.length > 0">
                <div v-for="waiter in waiters" :key="waiter.id">
                    <div class="flex items-center gap-2">
                        <div class="font-bold">{{ dayjs(waiter.date, 'YYYY-MM-DD').format('DD MMM') }}</div>
                        <div>
                            {{ waiter.shift_id === 'off' 
                                ? 'Off-day' 
                                : `${waiter.shifts.shift_name} (${waiter.shifts.shift_start.slice(0,5)} - ${waiter.shifts.shift_end.slice(0,5)})` 
                            }}
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="w-full">
                <Button
                    variant="red"
                    size="lg"
                    :disabled="currentWeek === props.weekNo || props.weekNo < currentWeek || isLoading"
                    @click="deleteShift"
                >
                    Delete
                </Button>
            </div>
            <div class="w-full">
                <Button
                    variant="tertiary"
                    size="lg"
                    @click="editShift"
                >
                    Edit
                </Button>
            </div>
        </div>
    </div>

    <Modal 
        :title="'Employee’s Shift'"
        :show="editShiftIsOpen" 
        :maxWidth="'sm'" 
        :closeable="true" 
        @close="closeEditShift"
    >
        <UpdateShift :selectedWaiter="props.selectedWaiter" :weekNo="props.weekNo" @close="closeEditShift" @fetchShift="fetchWaiterShift"  />
    </Modal>
</template>