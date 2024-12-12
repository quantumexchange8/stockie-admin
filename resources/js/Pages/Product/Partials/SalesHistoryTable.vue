<script setup>
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue'
import Table from '@/Components/Table.vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import DateInput from '@/Components/Date.vue';
import { useFileExport } from '@/Composables/index.js';
import { UploadIcon } from '@/Components/Icons/solid';

const props = defineProps({
    errors: Object,
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
})
const saleHistories = ref(props.rows)

const { exportToCSV } = useFileExport();

const emit = defineEmits(["applyDateFilter"]);

const date_filter = ref(props.dateFilter); 

watch(() => date_filter.value, (newValue) => {
    emit('applyDateFilter', newValue);
})

const csvExport = () => {
    const mappedData =  saleHistories.value.map(salesHistory => ({
        'Date': dayjs(salesHistory.created_at).format('DD/MM/YYYY'),
        'Time': dayjs(salesHistory.created_at).format('hh:mm A'),
        'Amount': 'RM ' + salesHistory.total_price.toFixed(2),
        'Quantity': salesHistory.qty,
    }));
    exportToCSV(mappedData, 'Sale History');
}
</script>

<template>
    <div class="flex flex-col w-full gap-6">
        <div class="w-full flex flex-row gap-x-4 justify-between">
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="w-60"
                v-model="date_filter"
            />
            <Menu as="div" class="relative inline-block text-left col-span-full lg:col-span-2">
                <div>
                    <MenuButton
                        class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800"
                    >
                        Export
                        <UploadIcon class="size-4 cursor-pointer flex-shrink-0"/>
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
                                    { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                                    'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]"
                                :disabled="saleHistories.length === 0"
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
                                    { 'bg-grey-50 pointer-events-none': saleHistories.length === 0 },
                                    'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                ]"
                                :disabled="saleHistories.length === 0"
                            >
                                PDF
                            </button>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>

        <Table 
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :rowType="rowType"
            minWidth="min-w-[465px]"
        >
            <template #date="row">
                <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.created_at).format('DD/MM/YYYY') }}</span>
            </template>
            <template #time="row">
                <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.created_at).format('hh:mm A') }}</span>
            </template>
            <template #total_price="row">
                <span class="text-grey-900 text-sm font-medium">RM {{ parseFloat(row.total_price).toFixed(2) }}</span>
            </template>
        </Table>
    </div>
</template>
