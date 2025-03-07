<script setup>
import Button from '@/Components/Button.vue';
import dayjs from 'dayjs';
import { ref, watchEffect } from 'vue';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import { WarningIcon } from '@/Components/Icons/solid';
import { transactionFormat } from '@/Composables';
import RadioButton from 'primevue/radiobutton';

dayjs.extend(weekOfYear);
const today = dayjs().startOf('week').add(1, 'day');
const currentWeek = ref(today.week());

const emit = defineEmits(["close", "fetchShift"]);
const waiters = ref([]);
const Waitershifts = ref([]);
const shifts = ref([]);
const editShiftIsOpen = ref(false);
const { formatTimeHM } = transactionFormat();
const form = ref({ assign_shift: {} });

const props = defineProps({
    selectedWaiter: [Object, Number],
    weekNo: Number,
});

const fetchGetShift = async () => {

    try {
        const response = await axios.get('/configurations/getWaiterShift', {
            params: { 
                waiter_id: props.selectedWaiter,
                weeks: props.weekNo,
            }, 
        });
        Waitershifts.value = response.data;

        // Initialize assigned shifts from backend data
        response.data.forEach((waiterShift) => {
            form.value.assign_shift[waiterShift.id] = waiterShift.shift_id; // Set initial selection
        });

    } catch (error) {
        console.error(error);
    }
};

console.log('this ', form.value)

const fetchShift = async () => {

    try {
        const response = await axios.get('/configurations/getShift');
        shifts.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

watchEffect(() => {
    fetchGetShift();
    fetchShift();
});

const updateShift = async () => {

    try {
        
        const response = await axios.post("/configurations/update-shift", {
            waiter_id: props.selectedWaiter,
            weeks: props.weekNo,
            assign_shift: form.value.assign_shift, 
        });

        if (response.status === 200) {
            // toast.add({ severity: 'success', summary: 'Success', detail: 'Shifts updated!', life: 3000 });
            emit('close');
            emit('fetchShift')
        }

    } catch (error) {
        console.error(error);
    }
}

</script>

<template>

        <div class="flex flex-col gap-6 max-h-[70vh] overflow-y-scroll">
            <div class="p-3 flex gap-3 bg-[#FDFBED] rounded-[5px]">
                <div class="max-w-6 w-full">
                    <WarningIcon />
                </div>
                <div class="flex flex-col gap-1 w-full">
                    <div class="text-yellow-700 font-bold text-base">You can only edit shifts scheduled for future dates.</div>
                    <div class="text-yellow-950 text-sm">Past shifts cannot be edited at this moment.</div>
                </div>
            </div>
            <div class="flex flex-col gap-4 py-1">
                <div v-for="Waitershift in Waitershifts" :key="Waitershift.id" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-3 text-sm font-bold text-gray-950">
                            <div class="w-1.5 bg-primary-800 h-[18px] max-h-[18px]"></div>
                            <div>{{ Waitershift.days }}</div>
                            <div>({{ dayjs(Waitershift.date).format("DD MMM YYYY") }})</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 w-full">
                            <div v-for="shift in shifts" :key="shift.id" 
                                :class="[
                                    'p-4 rounded-[5px] max-w-[246px] w-1/2 flex gap-4 border-[1.5px]',
                                    Number(form.assign_shift[Waitershift.id]) === Number(shift.id) 
                                        ? 'border-primary-900 bg-primary-25' 
                                        : 'border-gray-100 shadow-shift'
                                ]"
                            >
                                <div class="border " :style="{ borderColor: `${shift.color}` }"></div>
                                <div class="flex flex-col max-w-[165px] w-full">
                                    <div class="text-gray-950 text-base font-semibold">{{ shift.shift_name }}</div>
                                    <div class="text-gray-700 text-base font-normal">
                                        {{ shift.shift_start.slice(0,5) }} - {{ shift.shift_end.slice(0,5) }}
                                    </div>
                                </div>

                                <!-- Radio Button to select one shift per day -->
                                <div>
                                    <RadioButton 
                                        v-model="form.assign_shift[Waitershift.id]" 
                                        :inputId="`shift-${Waitershift.id}-${shift.id}`" 
                                        :name="`shift-${Waitershift.id}`" 
                                        :value="Number(shift.id)" 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="pt-3 flex items-center gap-4 w-full sticky bottom-0 bg-white">
            <Button :type="'button'" :variant="'tertiary'" :size="'lg'" @click="emit('close')">
                Cancel
            </Button>
            <Button :size="'lg'" @click="updateShift">
                Save changes
            </Button>
        </div>
</template>