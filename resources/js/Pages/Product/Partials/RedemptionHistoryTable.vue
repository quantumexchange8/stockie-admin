<script setup>
import { ref, watch } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Table from '@/Components/Table.vue';
import DateInput from '@/Components/Date.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import dayjs from 'dayjs';
import { useFileExport } from '@/Composables/index.js';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { UploadIcon } from '@/Components/Icons/solid';

const props = defineProps({
    dateFilter: Array,
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    rowType: {
        type: Object,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
});

const { exportToCSV } = useFileExport();

const emit = defineEmits(["applyDateFilter"]);

const date_filter = ref(props.dateFilter);
const redemptionHistories = ref(props.rows); 

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

watch(() => date_filter.value, (newValue) => {
    emit('applyDateFilter', newValue);
})

const csvExport = () => {
    const mappedData =  props.rows.map(redemptionHistory => ({
        'Date': dayjs(redemptionHistory.redemption_date).format('DD/MM/YYYY'),
        'Redeemable_Item': redemptionHistory.redeemable_item.product_name,
        'Quantity': redemptionHistory.qty,
        'Redeemed_By': redemptionHistory.handled_by.name,
    }));
    exportToCSV(mappedData, 'Redemption History');
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-end w-full">
            <Menu as="div" class="relative inline-block text-left">
                <div>
                    <MenuButton
                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800"
                    >
                        Export
                        <UploadIcon class="size-4 cursor-pointer"/>
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
                            <button
                                type="button"
                                :class="[
                                    { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]"
                                :disabled="redemptionHistories.length === 0"
                                @click="csvExport"
                            >
                                CSV
                            </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                            <button
                                type="button"
                                :class="[
                                    // { 'bg-primary-100': active },
                                    { 'bg-grey-50 pointer-events-none': redemptionHistories.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]"
                                :disabled="redemptionHistories.length === 0"
                            >
                                PDF
                            </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>

        <div class="flex items-start gap-5">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="filters['global'].value"
                class="col-span-full md:col-span-7"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="col-span-full md:col-span-5"
                v-model="date_filter"
            />
        </div>

        <Table 
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :rowType="rowType"
            :searchFilter="true"
            :filters="filters"
            minWidth="min-w-[675px]"
        >
            <template #empty>
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </template>
            <template #redemption_date="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ dayjs(row.redemption_date).format('YYYY/MM/DD') }}</span>
            </template>
            <template #amount="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ row.amount }} pts</span>
            </template>
            <template #handled_by.name="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ row.handled_by.name }}</span>
            </template>
        </Table>
    </div>
</template>
