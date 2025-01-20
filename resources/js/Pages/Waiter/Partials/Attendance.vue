<script setup>
import { UploadIcon } from '@/Components/Icons/solid';
import DateInput from '@/Components/Date.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import Table from '@/Components/Table.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { computed, onMounted, ref, watch } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import dayjs from 'dayjs';
import { useFileExport } from '@/Composables';

const props = defineProps({
    dateFilter: Array,
    columns: {
        type:Array,
        required: true,
    },
    waiter: {
        type: Object,
        required: true,
    },
    attendance: {
        type: Array,
        required: true,
    },
    rowType: Object,
})

const { exportToCSV } = useFileExport();

const waiter = ref(props.waiter);
const attendance = ref(props.attendance);
const attendanceRowsPerPage = ref(11);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const defaultLatest30Days = computed(() => {
    let currentDate = dayjs();
    let last30Days = currentDate.subtract(30, 'day');

    return [last30Days.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatest30Days.value);

watch(() => date_filter.value, () => {
    viewAttendance(date_filter.value, props.waiter.id);
})

const viewAttendance = async (filters = {}, id) => {
    try {
        const attendanceResponse = await axios.get(`/waiter/viewAttendance/${id}`, {
            method: 'GET',
            params: {
                dateFilter: filters,
            }
        });
        attendance.value = attendanceResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const csvExport = () => {
    const waiterName = waiter.value?.full_name || 'Unknown Waiter';
    const mappedAttendance = attendance.value.map(attendance => ({
        'Check in': attendance.check_in,
        'Check out': attendance.check_out,
    }));
    exportToCSV(mappedAttendance, `Waiter_${waiterName}_Attendance Report`);
}

const attendanceTotalPages = computed(() => {
    return Math.ceil(attendance.value.length / attendanceRowsPerPage.value);
})

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Attendance Report</span>
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton
                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
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
                    <MenuItems
                        class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg"
                        >
                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': attendance.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="attendance.length === 0" @click="csvExport">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': attendance.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="attendance.length === 0">
                            PDF
                        </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
        <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap items-center justify-between">
            <SearchBar 
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="!w-max"
                v-model="date_filter"
        />
        </div>
        <div class="w-full" v-if="attendance">
            <Table
                :columns="columns"
                :rows="attendance"
                :variant="'list'"
                :searchFilter="true"
                :filters="filters"
                :rowType="rowType"
                :totalPages="attendanceTotalPages"
                :rowsPerPage="attendanceRowsPerPage"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #check_in="attendance">
                    <span class="text-grey-900 text-sm font-medium">{{ attendance.check_in }}</span>
                </template>
                <template #check_out="attendance">
                    <span class="text-grey-900 text-sm font-medium">{{ attendance.check_out ?? '-' }}</span>
                </template>
                <template #duration="attendance">
                    <span class="text-grey-900 text-sm font-medium">{{ attendance.duration }}</span>
                </template>
            </Table>
        </div>
    </div>
</template>
