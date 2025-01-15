<script setup>
import dayjs from 'dayjs';
import { ref, computed, watch } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import Tag from '@/Components/Tag.vue';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import { DefaultIcon, DelayedIcon, DinnerTableIcon, UploadIcon, UserIcon } from '@/Components/Icons/solid';
import ReservationDetail from './ReservationDetail.vue';
import { useCustomToast, usePhoneUtils, useFileExport } from '@/Composables/index.js';
import { EmptyIllus } from '@/Components/Icons/illus.jsx';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
    tables: Array,
    columns: Array,
    rows: Array,
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
    actions: {
        type: Object,
        default: () => {},
    },
});
const { formatPhone } = usePhoneUtils();
const { exportToCSV } = useFileExport();

const selectedReservation = ref(null);
const reservationDetailIsOpen = ref(false);
const searchQuery = ref('');
const rows = ref(props.rows);
const initialRows = ref(props.rows);
const defaultLatestMonth = computed(() => {
    let currentDate = dayjs();
    let lastMonth = currentDate.subtract(1, 'month');

    return [lastMonth.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatestMonth.value);
const isLoading = ref(false);
const reservationStatus = ref(['Completed', 'No Show', 'Cancelled']);
const checkedFilters = ref({
    status: [],
})

const filterReservationHistory = async (filters = {}, checkedFilters = {}) => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('reservations.filterReservationHistory'), {
            params: {
                date_filter: filters,
                checkedFilters: checkedFilters,
            }
        });
        rows.value = response.data;
        initialRows.value = response.data;
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const showReservationDetailForm = (reservation) => {
    selectedReservation.value = reservation;
    reservationDetailIsOpen.value = true;
}

const hideReservationDetailForm = () => {
    reservationDetailIsOpen.value = false;
    setTimeout(() => {
        selectedReservation.value = null;
    }, 300);
}

const resetFilters = () => {
    return {
        status: [],
    };
}

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    filterReservationHistory(date_filter.value, checkedFilters.value);
    close();
}

const toggleStatus = (status) => {
    const index = checkedFilters.value.status.indexOf(status);
    if(index > -1) {
        checkedFilters.value.status.splice(index, 1);
    } else {
        checkedFilters.value.status.push(status);
    }
};

const applyCheckedFilters = (close) => {
    filterReservationHistory(date_filter.value, checkedFilters.value);
    close();
}

const getTableNames = (table_no) => table_no.map(selectedTable => selectedTable.name).join(', ');

const csvExport = () => { 
    const mappedReservations = props.rows.map(row => ({
        'No.': row.reservation_no,
        'Date': dayjs(row.reservation_date).format('DD/MM/YYYY'),
        'Time': dayjs(row.reservation_date).format('HH:mm'),
        'Name': row.name,
        'Pax': row.pax,
        'Table / Room': `"${getTableNames(row.table_no)}"`,
        'Contact No.': formatPhone(row.phone),
        'Status': row.status,
    }));
    exportToCSV(mappedReservations, 'Reservation History List');
}

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

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        rows.value = [...initialRows.value];
        return;
    }

    const query = newValue.toLowerCase();

    rows.value = initialRows.value.filter(row => {
        const reservation_date = row.reservation_date.toString().toLowerCase();
        const name = row.name.toLowerCase();
        const phone = row.phone.toString().toLowerCase();
        const table_no = getTableNames(row.table_no).toLowerCase();
        const pax = row.pax.toString().toLowerCase();

        return reservation_date.includes(query) ||
            name.includes(query) ||
            phone.includes(query) ||
            table_no.includes(query) ||
            pax.includes(query);
    });
}, { immediate: true });

watch(() => date_filter.value, (newValue) => {
    filterReservationHistory(newValue);
})

</script>

<template>
    <div class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100 overflow-y-auto">
        <div class="flex justify-between items-center">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">History</span>
            <!-- <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
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
                    <MenuItems class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg">
                        <MenuItem v-slot="{ active }">
                            <button 
                                type="button" 
                                :class="[
                                    { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': rows.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" 
                                :disabled="rows.length === 0" 
                                @click="csvExport"
                            >
                                CSV
                            </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                            <button 
                                type="button" 
                                :class="[
                                    { 'bg-grey-50 pointer-events-none': rows.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]" 
                                :disabled="rows.length === 0"
                            >
                                PDF
                            </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu> -->
        </div>

        <div class="flex justify-end items-start gap-5 self-stretch">
            <SearchBar
                placeholder="Search"
                :showFilter="true"
                v-model="searchQuery"
            >
            <template #default="{ hideOverlay }">
                <div class="flex flex-col self-stretch gap-4 items-start">
                    <span class="text-grey-900 text-base font-semibold">Status</span>
                    <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                        <div 
                            v-for="(status, index) in reservationStatus" 
                            :key="index"
                            class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                        >
                            <Checkbox
                                :checked="checkedFilters.status.includes(status)"
                                @click="toggleStatus(status)"
                            />
                            <span class="text-grey-700 text-sm font-medium">{{ status }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearFilters(hideOverlay)"
                    >
                        Clear All
                    </Button>
                    <Button
                        :size="'lg'"
                        @click="applyCheckedFilters(hideOverlay)"
                    >
                        Apply
                    </Button>
                </div>
            </template>
            </SearchBar>

            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="!w-fit"
                v-model="date_filter"
            />
        </div>

        <div class="flex flex-col items-end gap-2.5 self-stretch" v-if="rows.length > 0 && !isLoading">
            <div class="grid lg:grid-cols-3 grid-cols-2 items-start content-start gap-5 self-stretch flex-wrap">
                <div class="col-span-1 flex flex-col p-4 items-start gap-4 flex-[1_0_0] rounded-[5px] border border-solid border-grey-100 bg-white shadow-sm" 
                    v-for="reservation in rows" 
                    @click="showReservationDetailForm(reservation)"
                >
                    <div class="flex items-start gap-2 self-stretch">
                        <div class="flex items-center gap-2 flex-[1_0_0]">
                            <span class="text-grey-950 text-base font-bold">{{ dayjs(reservation.reservation_date).format('YYYY-MM-DD') }}</span>
                            <span class="text-grey-950 text-base font-bold">{{ dayjs(reservation.reservation_date).format('HH:mm') }}</span>
                            <DelayedIcon class="size-5" v-if="reservation.status === 'No show' && reservation.action_date"/>
                        </div>
                    </div>
                    <div class="w-full h-[1px] bg-[#eceff2]"></div>
                    <div class="flex items-start gap-2 self-stretch">
                        <div class="flex items-center gap-2 flex-[1_0_0] self-stretch">
                            <div class="flex flex-col justify-between items-start flex-[1_0_0] self-stretch">
                                <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-bold">{{ reservation.name }}</span>
                                <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-normal">{{ formatPhone(reservation.phone) }}</span>
                            </div>
                            <!-- {{ reservation.reserved_for }} -->
                            <img
                                :src="reservation.reserved_for.image"
                                alt="CustomerIcon"
                                class="rounded-full size-[38px]"
                                v-if="reservation.reserved_for && reservation.reserved_for.image"
                            >
                            <DefaultIcon v-else />
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-start gap-4 self-stretch">
                            <div class="flex items-start gap-2">
                                <DinnerTableIcon class="size-[18px]"/>
                                <span class="text-grey-950 text-sm font-semibold">{{ getTableNames(reservation.table_no) }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <UserIcon class="size-[18px]"/>
                                <span class="text-grey-950 text-sm font-semibold">{{ reservation.pax }}</span>
                            </div>
                        </div>
                    </div>
                    <Tag :variant="getStatusVariant(reservation.status)" :value="reservation.status" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-center"  v-else>
            <EmptyIllus/>
            <span class="text-primary-900 text-sm font-medium">You haven't added any reservation yet...</span>
        </div>

        <!-- <Table
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :actions="actions"
            :rowType="rowType"
            minWidth="min-w-[915px]"
            @onRowClick="showReservationDetailForm($event.data)"
        >
            <template #empty>
                <div class="flex flex-col gap-5 items-center">
                    <EmptyIllus/>
                    <span class="text-primary-900 text-sm font-medium">You haven't added any reservation yet...</span>
                </div>
            </template>
            <template #reservation_no="row">{{ row.reservation_no }}</template>
            <template #res_date="row">{{ dayjs(row.reservation_date).format('DD/MM/YYYY') }}</template>
            <template #res_time="row">{{ dayjs(row.reservation_date).format('HH:mm') }}</template>
            <template #name="row">
                <div class="flex items-center gap-x-2">
                    <img 
                        :src="row.reserved_for?.image ? row.reserved_for.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                        alt="" 
                        class="size-4 bg-primary-100 rounded-full"
                        v-if="row.reserved_for"
                    />
                    {{ row.name }}
                </div>
            </template>
            <template #table_no="row">{{ getTableNames(row.table_no) }}</template>
            <template #phone="row">{{ formatPhone(row.phone) }}</template>
            <template #status="row"><Tag :value="row.status" :variant="getStatusVariant(row.status)" /></template>
        </Table> -->
    </div>

    <!-- View reservation detail -->
    <Modal 
        :title="'Reservation Detail'"
        :show="reservationDetailIsOpen" 
        :maxWidth="'sm'" 
        @close="hideReservationDetailForm"
    >
        <ReservationDetail :reservation="selectedReservation" :tables="tables" viewOnly @close="hideReservationDetailForm" />
    </Modal>
</template>
