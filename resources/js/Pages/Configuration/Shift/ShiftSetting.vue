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

const today = dayjs().startOf('week'); // Get the start of this week
const thisWeek = ref(today); // Default to current week
const shifts = ref([]);
const newShiftIsOpen = ref(false);
const assignShiftIsOpen = ref(false);

const fetchShift = async () => {
    try {

        const response = await axios.get('/configurations/getShift');
        shifts.value = response.data;

    } catch (error) {
        console.error(error)
    }
}

onMounted(() => {
    fetchShift();
});

const goNextWeek = () => {
    thisWeek.value = thisWeek.value.add(7, 'day');
};

const goLastWeek = () => {
    thisWeek.value = thisWeek.value.subtract(7, 'day');
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

}

const openAssignShift = () => {
    assignShiftIsOpen.value = true;
}

const closeAssignShift = () => {
    assignShiftIsOpen.value = false;
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
                        :class="['py-3 px-6 flex flex-col gap-1 border-l-[3px]']" 
                        :style="{ borderLeftColor: `${shift.color}` }"
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
                :title="'Add New Shift'"
                :show="newShiftIsOpen" 
                :maxWidth="'sm'" 
                :closeable="true" 
                @close="closeNewShift"
            >
                <AddShift @close="closeNewShift" />
            </Modal>
        </div>
        <div class="p-6 flex flex-col gap-6 border border-primary-100 rounded-[5px]">
            <div class="flex justify-between">
                <div class="text-primary-900 text-md font-medium">Timetable</div>
                <div class="flex items-center gap-3">
                    <div>
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
                    </div>
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
                    <Column v-for="shift in shifts" :key="shift.id">
                        <template #header>
                            <div class="flex flex-col gap-1">
                                <div class="text-primary-900 text-sm font-semibold">
                                    {{ shift.shift_name }}
                                </div>
                                <div class="text-primary-900 text-sm font-normal">
                                    {{ shift.shift_start }} - {{ shift.shift_end }}
                                </div>
                            </div>
                        </template>
                        <template #body="slotProps">
                            {{ shift.late }}
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>

</template>