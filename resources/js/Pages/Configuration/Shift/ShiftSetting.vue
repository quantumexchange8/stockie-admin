<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from '@/Components/Button.vue';
import { ChevronLeft, ChevronRight, EditIcon, PlusIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs'; // Using dayjs for date manipulation
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Modal from '@/Components/Modal.vue';
import AddShift from './Partials/AddShift.vue';
import AssignShift from './Partials/AssignShift.vue';
import EditShift from './Partials/EditShift.vue';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import Edit from './Partials/Edit.vue';

dayjs.extend(weekOfYear);

const today = dayjs().startOf('week').add(1, 'day'); // Get the start of this week
const thisWeek = ref(today); // Default to current week
const thisWeekVal = ref(today.week()); // Get the current week number
const shifts = ref([]);
const waiterShifts = ref([]);
const newShiftIsOpen = ref(false);
const assignShiftIsOpen = ref(false);
const editShiftIsOpen = ref(false);
const editIsOpen = ref(false);
const selectedShift = ref(null);
const selectedDay = ref(null);
const shiftVal = ref(null)

const fetchShift = async () => {
    try {

        const response = await axios.get('/configurations/getShift');
        shifts.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

const fetchWaiterShift = async () => {
    try {

        const response = await axios.get('/configurations/getWaiterShift');
        waiterShifts.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

onMounted(() => {
    fetchShift();
    fetchWaiterShift();
});

const goNextWeek = () => {
    thisWeek.value = thisWeek.value.add(7, 'day');
    thisWeekVal.value += 1;
};

const goLastWeek = () => {
    thisWeek.value = thisWeek.value.subtract(7, 'day');
    thisWeekVal.value -= 1; // Decrement week number
};

const weekLabel = computed(() => {
    if (thisWeek.value.isSame(today, 'week')) {
        return 'This Week';
    } else if (thisWeek.value.isBefore(today, 'week')) {
        return 'Last Week';
    } else {
        return 'Next Week';
    }
});

const formattedWeekRange = computed(() => {
    const start = thisWeek.value.format('DD MMM'); // Start date (e.g., "23 Feb")
    const end = thisWeek.value.add(6, 'day').format('DD MMM YYYY'); // End date (e.g., "01 Mar 2025")
    return `${start} - ${end}`;
});

const weekDays = computed(() => {
    return Array.from({ length: 7 }, (_, i) => ({
        dayName: thisWeek.value.add(i, 'day').format('dddd'), // Monday, Tuesday, etc.
        date: thisWeek.value.add(i, 'day').format('DD MMM YYYY') // 23 Feb 2025, etc.
    }));
});

const openNewShift = () => {
    newShiftIsOpen.value = true;
}

const closeNewShift = () => {
    newShiftIsOpen.value = false;
}

const openEditShift = () => {
    editShiftIsOpen.value = true;
}

const closeEditShift = () => {
    editShiftIsOpen.value = false;
}

const openAssignShift = () => {
    assignShiftIsOpen.value = true;
}

const closeAssignShift = () => {
    assignShiftIsOpen.value = false;
}

const handleCellClick = (day, shift) => {
    console.log("Clicked Cell for:", day, shift);
    selectedShift.value = shift;
    selectedDay.value = day;
    editShiftIsOpen.value = true;
};

const edit = (shift) => {
    editIsOpen.value = true;
    shiftVal.value = shift;
}

const closeEdit = () => {
    editIsOpen.value = false;
}

</script>

<template>

    <div class="flex flex-col gap-5">
        <div class="p-6 flex flex-col gap-6 border border-primary-100 rounded-[5px]">
            <div class="flex justify-between">
                <div class="text-primary-900 text-md font-medium">Shift Type</div>
                <div>
                    <Button
                        type="button"
                        size="lg"
                        iconPosition="left"
                        class="!w-fit"
                        @click="openNewShift"
                    >
                        <template #icon>
                            <PlusIcon />
                        </template>
                        New Shift
                    </Button>
                </div>
            </div>
            <div class="flex flex-wrap gap-5">
                <div v-for="shift in shifts" :key="shift.id" class="border border-gray-100  max-w-[270px] w-full">
                    <div 
                        :class="['py-3 px-6 flex flex-col gap-1 border-l-[3px] cursor-pointer']" 
                        :style="{ borderLeftColor: `${shift.color}` }"
                        @click="edit(shift)"
                    >
                        <div class="text-gray-900 text-base font-medium">
                            {{ shift.shift_name }}
                        </div>
                        <div class="text-gray-500 text-sm font-normal">
                            {{ shift.shift_start }} - {{ shift.shift_end }}
                        </div>
                    </div>
                </div>
            </div>

            <Modal 
                :title="'Edit Shift'"
                :show="editIsOpen" 
                :maxWidth="'sm'" 
                :closeable="true" 
                @close="closeEdit"
            >
                <Edit @close="closeEdit" @fetch-shift="fetchShift" :shift="shiftVal" />
            </Modal>

            <Modal 
                :title="'Add New Shift'"
                :show="newShiftIsOpen" 
                :maxWidth="'sm'" 
                :closeable="true" 
                @close="closeNewShift"
            >
                <AddShift @close="closeNewShift" @shift-added="fetchShift" />
            </Modal>
        </div>
        <div class="p-6 flex flex-col gap-6 border border-primary-100 rounded-[5px]">
            <div class="flex justify-between">
                <div class="text-primary-900 text-md font-medium">Timetable</div>
                <div class="flex items-center gap-3">
                    <!-- <div>
                        <Button
                            type="button"
                            size="lg"
                            iconPosition="left"
                            class="!w-fit"
                            @click="openEditShift"
                            variant="tertiary"
                        >
                            <template #icon>
                                <EditIcon />
                            </template>
                            Edit
                        </Button>
                    </div> -->

                    <Modal 
                        :title="'Shift Assignment Detail'"
                        :show="editShiftIsOpen" 
                        :maxWidth="'sm'" 
                        :closeable="true" 
                        @close="closeEditShift"
                    >
                        <EditShift @close="closeEditShift" :selectedShift="selectedShift" :selectedDay="selectedDay" :thisWeekVal="thisWeekVal" />
                    </Modal>

                    <div>
                        <Button
                            type="button"
                            size="lg"
                            iconPosition="left"
                            class="!w-fit"
                            @click="openAssignShift"
                        >
                            <template #icon>
                                <PlusIcon />
                            </template>
                            Assign Shift
                        </Button>
                    </div>

                    <Modal 
                        :title="'Assign Shift'"
                        :show="assignShiftIsOpen" 
                        :maxWidth="'sm'" 
                        :closeable="true" 
                        @close="closeAssignShift"
                    >
                        <AssignShift @close="closeAssignShift" />
                    </Modal>
                </div>
            </div>
            <div class="w-full flex border border-gray-200 rounded-[5px]">
                <div class="py-3 px-4 border-r border-gray-200 cursor-pointer" @click="goLastWeek">
                    <ChevronLeft />
                </div>
                <div class="w-full py-3 px-4 text-center text-gray-900 text-base font-semibold ">
                    {{ weekLabel }} <!-- Display current week -->
                </div>
                <div class="py-3 px-4 border-l border-gray-200 cursor-pointer" @click="goNextWeek">
                    <ChevronRight />
                </div>
            </div>
            <div>
                <DataTable :value="weekDays" tableStyle="min-width: 50rem"> 
                    <!-- Days Column (Frozen First Column) -->
                    <Column field="date" header="" frozen style="max-width: 156px;">
                        <template #header>
                            <div class="flex flex-col gap-1">
                                <div class="text-primary-900 text-sm font-semibold">Days</div>
                                <div class="text-primary-900 text-sm font-normal">
                                    {{ formattedWeekRange }}
                                </div>
                            </div>
                        </template>
                        <template #body="slotProps">
                            <div class="flex flex-col gap-1">
                                <div class="text-gray-900 text-sm font-bold">{{ slotProps.data.dayName }}</div>
                                <div class="text-gray-900 text-sm font-medium">{{ slotProps.data.date }}</div>
                            </div>
                        </template>
                    </Column>

                    <!-- Shift Columns -->
                    <Column v-for="shift in shifts" :key="shift.id">
                        <template #header>
                            <div class="flex flex-col gap-1">
                                <div class="text-primary-900 text-sm font-semibold">
                                    {{ shift.shift_name }}
                                </div>
                                <div class="text-primary-900 text-sm font-normal">
                                    {{ shift.shift_start.slice(0,5) }} - {{ shift.shift_end.slice(0,5) }}
                                </div>
                            </div>
                        </template>
                        <template #body="slotProps">
                            <div 
                                class="cursor-pointer flex items-center hover:bg-primary-25 hover:rounded-[5px] w-full h-full"
                                @click="handleCellClick(slotProps.data, shift)"
                            >
                                {{
                                    waiterShifts.filter(ws => 
                                        ws.shift_id == shift.id && 
                                        dayjs(slotProps.data.date, 'DD MMM YYYY').format('YYYY-MM-DD') === ws.date
                                    ).length
                                }}
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>


</template>