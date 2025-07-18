<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
// import RadioButton from 'primevue/radiobutton';
import Dropdown from '@/Components/Dropdown.vue';
import dayjs from 'dayjs';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import { transactionFormat } from '@/Composables';
import RadioButton from '@/Components/RadioButton.vue';
import InputError from '@/Components/InputError.vue';

dayjs.extend(weekOfYear);

const days = [
    { name: 'Monday'},
    { name: 'Tuesday'},
    { name: 'Wednesday'},
    { name: 'Thursday'},
    { name: 'Friday'},
    { name: 'Saturday'},
    { name: 'Sunday'},
]

const weeks = ref([
    { text: 'This Week', value: 'this_week' },
    { text: 'Next Week', value: 'next_week' },
    { text: 'Next 2 Week', value: 'next_2_week' },
]);


const today = dayjs().startOf('week').add(1, 'day');
const thisWeek = ref(today);
const weekDays = computed(() => {
    return Array.from({ length: 7 }, (_, i) => ({
        dayName: thisWeek.value.add(i, 'day').format('dddd'), // Monday, Tuesday, etc.
        date: thisWeek.value.add(i, 'day').format('DD MMM YYYY') // 23 Feb 2025, etc.
    }));
});

const emit = defineEmits(['close', 'fetchshift']);
const selectDays = ref([]);
const shifts = ref([]);
const waiters = ref([]);
const assignedWaiters = ref([]);
const currentWeek = dayjs().week();
const { formatTimeHM } = transactionFormat();

const fetchShift = async () => {
    try {

        const response = await axios.get('/configurations/getShift');
        shifts.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

const fetchWaiter = async () => {
    try {
        const response = await axios.get('/configurations/getWaiter');
        waiters.value = response.data; // All available waiters

        const shiftResponse = await axios.get('/configurations/getWaiterShift'); // Fetch assigned shifts

        const nextWeek = currentWeek + 1;

        const nextWeekShifts = shiftResponse.data.filter(shift => shift.weeks === nextWeek); // Filter shifts for week 11

        // Extract unique waiter IDs who have a shift assigned next week
        assignedWaiters.value = [...new Set(nextWeekShifts.map(shift => shift.waiter_id))];

        // Filter waiters who are NOT assigned next week
        waiters.value = waiters.value.filter(waiter => !assignedWaiters.value.includes(waiter.id));

    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    fetchShift();
    fetchWaiter();
});

const dayIndexMap = weekDays.value.reduce((acc, d, index) => {
  acc[d.dayName] = index;
  return acc;
}, {});

const sortedSelectedDays = computed(() => {
  return form.days.slice().sort((a, b) => dayIndexMap[a] - dayIndexMap[b]);
});

const waitersArr = computed(() => {
    return waiters.value.map(waiter => ({
        text: waiter.full_name,
        value: waiter.id,
        image: waiter.image,
    }));
});

const allowedDaysThisWeek = computed(() => {
    if (form.week !== 'this_week') return days.map(d => d.name); // All days allowed

    const todayName = dayjs().format('dddd'); // e.g. "Wednesday"
    const todayIndex = days.findIndex(d => d.name === todayName);

    // Only allow days after today
    return days.slice(todayIndex + 1).map(d => d.name);
});

const form = useForm({
    waiter_id: '',
    days: [], // Holds selected days e.g., ["Monday", "Tuesday"]
    assign_shift: {}, // Object to store selected shift per day { "Monday": shift_id, "Tuesday": shift_id }
    week: '', // store weeks
});

watch(() => form.week, (newWeek) => {
    form.days.splice(0);
    form.assign_shift = {};

    if (newWeek === 'this_week') {
        form.days = form.days.filter(day => allowedDaysThisWeek.value.includes(day));
    } else {
        // Optional: Reset days when switching to other weeks
        form.days = [];
    }
});

watch(() => form.days, (newDays) => {
    // Remove unselected days from assign_shift
    Object.keys(form.assign_shift).forEach(day => {
        if (!newDays.includes(day)) {
            delete form.assign_shift[day];
        }
    });
});

const submit = () => {
    form.post('/configurations/assign-shift', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
            emit('fetchshift');
        }
    });
};

</script>

<template>

    <div class="flex flex-col gap-6 overflow-y-auto max-h-[75vh]">
        <div class="flex flex-col gap-4 w-full">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Select Employee</div>
                <div class="text-gray-950 text-sm font-normal">Assign shift to selected employee.</div>
            </div>
            <div>
                <Dropdown 
                    imageOption
                    :inputName="'waiter_id'" 
                    :labelText="'Assign Waiter'"
                    :inputArray="waitersArr"
                    :errorMessage="form.errors?.waiter_id || ''"
                    v-model="form.waiter_id"
                />
            </div>
        </div>

        <div class="flex flex-col gap-4 w-full">
            <div class="flex flex-col gap-1">
                <div class="text-gray-950 text-base font-bold">Select Week</div>
                <div class="text-gray-950 text-sm font-normal">Assign shift to selected week.</div>
            </div>
            <div>
                <Dropdown 
                    :inputName="'week'" 
                    :labelText="'Assign Week'"
                    :inputArray="weeks"
                    :errorMessage="form.errors?.week || ''"
                    :dataValue="form.week"
                    v-model="form.week"
                />
            </div>
        </div>
        <div class="flex flex-col gap-1">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <div class="text-gray-950 text-base font-bold">Select Day(s)</div>
                    <div class="text-gray-950 text-sm font-normal">Applicable days for this shift.</div>
                </div>
                <div class="flex flex-wrap gap-5">
                    <div v-for="day in weekDays" :key="day.dayName" class="flex items-center gap-2">
                        <Checkbox 
                            v-model:checked="form.days"
                            :value="day.dayName"
                            :disabled="!allowedDaysThisWeek.includes(day.dayName)"
                        />
                        <span :class="{
                            'text-gray-400': !allowedDaysThisWeek.includes(day.dayName),
                            'text-black': allowedDaysThisWeek.includes(day.dayName)
                        }">
                            {{ day.dayName }}
                        </span>
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.days ? form.errors.days : ''" />
        </div>
        <div class="flex flex-col gap-1">
            <div class="flex flex-col gap-6">
                <div class="flex items-center">
                    <div class="flex flex-col">
                        <div class="text-gray-950 text-base font-bold">Assign Shift</div>
                        <div class="text-gray-950 text-sm font-normal">Assign shift to different working day.</div>
                    </div>
                </div>

                <!-- loop -->
                <template v-if="sortedSelectedDays.length > 0">
                    <div class="flex flex-col gap-6">
                        <div v-for="selectedDay in sortedSelectedDays" :key="selectedDay" class=" flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 bg-primary-800 h-[18px] max-h-[18px]"></div>
                                <div class="text-gray-950 font-bold text-sm">
                                    {{ selectedDay }} 
                                </div>
                                <div></div>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 w-full">
                                <div v-for="shift in shifts" :key="shift.id" :class="[
                                        'p-4 rounded-[5px] max-w-[246px] w-1/2 flex gap-4 border',
                                        form.assign_shift[selectedDay] === shift.id 
                                            ? 'border-primary-900 bg-primary-25' 
                                            : 'border-gray-100'
                                    ]"
                                >
                                    <div class="border " :style="{ borderColor: `${shift.color}` }"></div>
                                    <div class="flex flex-col max-w-[165px] w-full">
                                        <div class="text-gray-950 text-base font-semibold">{{ shift.shift_name }}</div>
                                        <div class="text-gray-700 text-base font-normal">{{ formatTimeHM(shift.shift_start) }} - {{ formatTimeHM(shift.shift_end) }}</div>
                                    </div>

                                    <!-- Radio Button to select one shift per day -->
                                    <div>
                                        <RadioButton 
                                            v-model:checked="form.assign_shift[selectedDay]" 
                                            :inputId="`shift-${selectedDay}-${shift.id}`" 
                                            :dynamic="false"
                                            :name="selectedDay" 
                                            :value="shift.id" 
                                            :disabled="!shift.apply_days.find((availableDay) => availableDay === selectedDay)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <InputError :message="form.errors.assign_shift ? form.errors.assign_shift : ''" />
        </div>
        <div class="pt-3 flex items-center gap-4 w-full sticky bottom-0 bg-white">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="emit('close')"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                @click="submit"
            >
                Add
            </Button>
        </div>
    </div>

</template>