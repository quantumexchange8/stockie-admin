<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import { DotVerticleIcon, PlusIcon } from '@/Components/Icons/solid';
import DateInput from '@/Components/Date.vue';
import Table from '@/Components/Table.vue';
import { computed, onMounted, ref, watch, watchEffect } from 'vue';
import Modal from '@/Components/Modal.vue';
import dayjs from 'dayjs';
import ShiftRecordDetails from './Partials/ShiftRecordDetails.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { UploadIcon } from '@/Components/Icons/solid';
import { transactionFormat, useFileExport } from '@/Composables/index.js';

const props = defineProps({
    shiftTransactions: Array,
})

const home = ref({
    label: 'Shift Record',
})

const { exportToCSV } = useFileExport();
const { formatAmount } = transactionFormat();

const selectedShift = ref(null);
const shiftTransactionsList = ref(props.shiftTransactions);
const shiftRecordModalIsOpen = ref(false);
const isLoading = ref(false);
const shiftRowsPerPage = ref(12);

const defaultLatest2Months = computed(() => {
    let currentDate = dayjs().endOf('month');
    let lastMonth = currentDate.subtract(2, 'month').startOf('month');;

    return [lastMonth.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatest2Months.value); 

const columns = ref([
    {field: 'shift_no', header: 'Shift No.', width: '20', sortable: true},
    {field: 'shift_opened', header: 'Opening Time', width: '26', sortable: true},
    {field: 'shift_closed', header: 'Closing Time', width: '26', sortable: true},
    {field: 'net_sales', header: 'Net Sales', width: '28', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const filterShiftListing = async (filters = {}, checkedFilters = {}) => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('shift-management.record.getFilteredShiftTransactions'), {
            params: { date_filter: filters }
        });
        
        shiftTransactionsList.value = response.data;

    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

watch(date_filter, (newValue) => filterShiftListing(newValue));

watch(() => props.shiftTransactions, (newValue) => {
    shiftTransactionsList.value = newValue;
})

const shiftTotalPages = computed(() => {
    return Math.ceil(shiftTransactionsList.value.length / shiftRowsPerPage.value);
});

const openModal = (row) => {
    selectedShift.value = row;
    shiftRecordModalIsOpen.value = true;
};

const closeModal = () => {
    shiftRecordModalIsOpen.value = false;
    // setTimeout(() => {
    //     selectedShift.value = null;
    // }, 200);
};

const csvExport = () => { 
    const title = 'Shift Transaction List';
    const startDate = dayjs(date_filter.value[0]).format('DD/MM/YYYY');
    const endDate = date_filter.value[1] != null ? dayjs(date_filter.value[1]).format('DD/MM/YYYY') : dayjs(date_filter.value[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { 'Shift No.': title, 'Shift Opened': '', 'Shift Closed': '', 'Net Sales': '' },
        { 'Shift No.': dateRange, 'Shift Opened': '', 'Shift Closed': '', 'Net Sales': '' },
        { 'Shift No.': 'Shift No.', 'Shift Opened': 'Shift Opened', 'Shift Closed': 'Shift Closed', 'Net Sales': 'Net Sales' },
        ...shiftTransactionsList.value.map(row => ({
            'Shift No.': row.shift_no,
            'Shift Opened': dayjs(row.shift_opened).format('DD/MM/YYYY, HH:mm'),
            'Shift Closed': dayjs(row.shift_closed).format('DD/MM/YYYY, HH:mm'),
            'Net Sales': `RM ${formatAmount(row.net_sales)}`,
        })),
    ];

    exportToCSV(formattedRows, 'Shift Transaction List');
}

</script>

<template>
    <Head title="Shift Record" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <div class="flex flex-col p-6 gap-6 justify-center rounded-[5px] border border-primary-100">
            <span class="flex flex-col justify-center text-primary-900 text-md font-medium">Shift Listing</span>
            <div class="flex lg:flex-col xl:flex-row items-center gap-5">
                <div class="flex items-center flex-wrap justify-between gap-5 w-full">
                    <DateInput
                        :inputName="'date_filter'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        class="!w-fit"
                        v-model="date_filter"
                    />
            
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="!w-fit"
                        :disabled="shiftTransactionsList.length === 0"
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
                                <UploadIcon class="size-4 cursor-pointer" />
                                Bulk Export
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
                                    { 'bg-grey-50 pointer-events-none': shiftTransactionsList.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="shiftTransactionsList.length === 0" @click="csvExport">
                                    CSV
                                </button>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                <button type="button" :class="[
                                    // { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': shiftTransactionsList.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" :disabled="shiftTransactionsList.length === 0">
                                    PDF
                                </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu> -->
                </div>
            </div>

            <Table
                :columns="columns"
                :rows="shiftTransactionsList"
                :variant="'list'"
                :rowType="rowType"
                :totalPages="shiftTotalPages"
                :rowsPerPage="shiftRowsPerPage"
            >
                <template #shift_opened="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.shift_opened).format('DD/MM/YYYY, HH:mm') }}</span>
                </template>
                <template #shift_closed="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.shift_closed).format('DD/MM/YYYY, HH:mm') }}</span>
                </template>
                <template #net_sales="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ formatAmount(row.net_sales) }}</span>
                </template>
                <template #action="row">
                    <div @click="openModal(row)" class="cursor-pointer">
                        <DotVerticleIcon />
                    </div>
                </template>
            </Table>
        </div>

        <Modal 
            :title="'Shift Record'"
            :show="shiftRecordModalIsOpen"
            :maxWidth="'sm'" 
            :closeable="true"
            @close="closeModal"
        >
            <ShiftRecordDetails
                :currentSelectedShift="selectedShift"
            />
        </Modal>
    </AuthenticatedLayout>
</template>

