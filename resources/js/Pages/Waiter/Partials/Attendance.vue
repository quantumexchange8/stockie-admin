<script setup>
import { UploadIcon } from '@/Components/Icons/solid';
import DateInput from '@/Components/Date.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import Table from '@/Components/Table.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { computed, onMounted, ref, watch } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import { transactionFormat, useFileExport } from '@/Composables';
import Button from '@/Components/Button.vue';
import duration from 'dayjs/plugin/duration';
import dayjs from 'dayjs';
import Modal from '@/Components/Modal.vue';
import AttendanceDetail from './AttendanceDetail.vue';
dayjs.extend(duration)

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
    // attendance: {
    //     type: Array,
    //     required: true,
    // },
    rowType: Object,
})

const { exportToCSV } = useFileExport();
const { formatAmount } = transactionFormat();

const waiter = ref(props.waiter);
const tableColumns = ref(props.columns);
const attendances = ref([]);
const attendanceRowsPerPage = ref(11);
const isAttendanceDetailModalOpen = ref(false);
const selectedAttendance = ref('');
const searchQuery = ref('');

const defaultLatest30Days = computed(() => {
    let currentDate = dayjs();
    let last30Days = currentDate.subtract(30, 'day');

    return [last30Days.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatest30Days.value);

watch(() => date_filter.value, () => {
    viewAttendance(date_filter.value, props.waiter.id);
});

const viewAttendance = async (filters = {}, id) => {
    try {
        const attendanceResponse = await axios.get(`/waiter/getAttendanceList/${id}`, {
            method: 'GET',
            params: {
                date_filter: filters,
                with_breaks: true
            }
        });

        attendances.value = attendanceResponse.data;

    } catch (error) {
        console.error(error);
    } finally {

    }
};

const csvExport = () => {
    const waiterName = waiter.value?.full_name || 'Unknown Waiter';
    const title = `Waiter_${waiterName}_Attendance Report`;
    const startDate = dayjs(date_filter.value[0]).format('DD/MM/YYYY');
    const endDate = date_filter.value[1] != null ? dayjs(date_filter.value[1]).format('DD/MM/YYYY') : dayjs(date_filter.value[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    let formattedRows = [
        { Date: title, 'Working Duration': '', 'Break Duration': '' },
        { Date: dateRange, 'Working Duration': '', 'Break Duration': '' },
        { Date: 'Date', 'Working Duration': 'Working Duration', 'Break Duration': 'Break Duration' },
        ...attendances.value.map(row => {
            let data = {
                'Date': row.date,
                'Working Duration': row.work_duration,
                'Break Duration': row.break_duration ?? '-',
            };

            if (waiter.value.employment_type === 'Part-time' && tableColumns.value.find((c) => c.field === 'earnings')) {
                data['Est. Rate (RM/hour)'] = row.earnings;
            }

            return data;
        }),
    ];

    if (waiter.value.employment_type === 'Part-time' && tableColumns.value.find((c) => c.field === 'earnings')) {
        formattedRows.forEach((row, index) => {
            if ([0, 1].includes(index)) {
                row['Est. Rate (RM/hour)'] = '';

            } else if (index == 2) {
                row['Est. Rate (RM/hour)'] = 'Est. Rate (RM/hour)';
            }
        });
    }

    exportToCSV(formattedRows, `Waiter_${waiterName}_Attendance Report`);
};

const openModal = (attendance) => {
    selectedAttendance.value = attendance;
    isAttendanceDetailModalOpen.value = true;
};

const closeModal = () => {
    isAttendanceDetailModalOpen.value = false;
    setTimeout(()=> selectedAttendance.value = '', 300);
};

const updateAttendance = (updatedAttendance) => {
    const targetAttendance = attendances.value.find((a) => a.date === updatedAttendance.date);

    targetAttendance.break_duration = updatedAttendance.break_duration;
    targetAttendance.check_in = updatedAttendance.check_in;
    targetAttendance.check_out = updatedAttendance.check_out;
    targetAttendance.status = updatedAttendance.status;
    targetAttendance.earnings = updatedAttendance.earnings;
    targetAttendance.work_duration = updatedAttendance.work_duration;
};

const filteredAttendances = computed(() => {
    if (!searchQuery.value) return attendances.value;

    const query = searchQuery.value.toLowerCase();
    
    return attendances.value.filter(row => row.date.toLowerCase().includes(query));
});

const attendanceTotalPages = computed(() => {
    return Math.ceil(filteredAttendances.value.length / attendanceRowsPerPage.value);
});

onMounted(() => {
    viewAttendance(date_filter.value, props.waiter.id);
    if (waiter.value.employment_type === 'Part-time' && !tableColumns.value.find((c) => c.field === 'earnings')) {
        // tableColumns.value[0]
        tableColumns.value.push({
            field: 'earnings', 
            header: 'Est. Rate (RM/hour)', 
            width: '30', 
            sortable: true
        });
    }
});

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Attendance Report</span>
            
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit"
                :disabled="attendances.length === 0"
                @click="csvExport"
            >
                <template #icon >
                    <UploadIcon class="size-4 cursor-pointer flex-shrink-0"/>
                </template>
                Export
            </Button>

            <!-- <Menu as="div" class="relative inline-block text-left">
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
                            { 'bg-grey-50 pointer-events-none': attendances.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="attendances.length === 0" @click="csvExport">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': attendances.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="attendances.length === 0">
                            PDF
                        </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu> -->
        </div>
        <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap items-center justify-between">
            <SearchBar 
                placeholder="Search"
                :showFilter="false"
                v-model="searchQuery"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="!w-max"
                v-model="date_filter"
            />
        </div>
        <div class="w-full" v-if="filteredAttendances">
            <Table
                :columns="tableColumns"
                :rows="filteredAttendances"
                :variant="'list'"
                :rowType="rowType"
                :totalPages="attendanceTotalPages"
                :rowsPerPage="attendanceRowsPerPage"
                @onRowClick="openModal($event.data)"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #date="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.date }}</span>
                </template>
                <template #work_duration="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.work_duration }}</span>
                    <!-- <span class="text-grey-900 text-sm font-medium">{{ dayjs.duration(2, 'minutes').humanize() }}</span> -->
                </template>
                <template #break_duration="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.break_duration ?? '-' }}</span>
                </template>
            </Table>
        </div>
    </div>

    <Modal
        :title="'Attendance Detail'"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isAttendanceDetailModalOpen"
        @close="closeModal"
    >
        <template v-if="selectedAttendance">
            <AttendanceDetail
                :selectedAttendance="selectedAttendance"
                :selectedWaiter="waiter"
                @update:attendance="updateAttendance"
                @close="closeModal"
            />
        </template>
    </Modal>
</template>
