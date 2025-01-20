<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import { ref } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Tag from '@/Components/Tag.vue';
import { Link } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { UploadIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import { transactionFormat, useFileExport } from '@/Composables';

const props = defineProps({
    waiter: {
        type: String,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    dateFilter: {
        type: Array,
        required: true,
    },
    incentiveData: {
        type: Array,
        default: () => {},
    },
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
})

const incentiveData = ref(props.incentiveData);
const { formatAmount } = transactionFormat();
const { exportToCSV } = useFileExport();

const csvExport = () => {
    const waiterName = props.waiter || 'Unknown Waiter';
    const mappedData = incentiveData.value.map(data => ({
        'Date': data.period_start,
        'Total': data.amount,
        'Incentive': data.sales_target,
        'Status': data.status,        
    }));
    exportToCSV(mappedData, `${waiterName}_Monthly Incentive Report`);
}

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Monthly Incentive Report</span>
            <Menu as="div" class="relative inline-block text-left">
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
                            <button type="button" :class="[
                                { 'bg-primary-100': active },
                                { 'bg-grey-50 pointer-events-none': incentiveData.length === 0 },
                                'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                            ]" :disabled="incentiveData.length === 0" @click="csvExport">
                                CSV
                            </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                            <button type="button" :class="[
                                // { 'bg-primary-100': active },
                                { 'bg-grey-50 pointer-events-none': incentiveData.length === 0 },
                                'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                            ]" :disabled="incentiveData.length === 0">
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
        </div>
        <div class="w-full" v-if="incentiveData">
            <Table
                :columns="columns"
                :rows="incentiveData"
                :variant="'list'"
                :searchFilter="true"
                :filters="filters"
                :rowType="rowType"
                :totalPages="totalPages"
                :rowsPerPage="rowsPerPage"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #period_start="row">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">{{ dayjs(row.period_start).format('MMMM YYYY') }}</span>
                </template>
                <template #amount="row">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(row.amount) }}</span>
                </template>
                <template #sales_target="row">
                    <div v-if="row.sales_target != 0" class="inline-flex items-center whitespace-nowrap gap-0.5">
                        <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(row.sales_target) }} </span>
                        <span class="line-clamp-1 text-primary-900 text-ellipsis text-sm font-medium">
                            ({{ row.type == 'percentage' ? `${parseInt(row.rate * 100)}%` : `RM ${row.rate}` }} of total sales)
                        </span>
                    </div>
                </template>
                <template #status="row">
                    <Link :href="route('configuration.incentCommDetail', row.incentive_id)">
                        <Tag :variant="'green'" :value="row.status" />
                    </Link>
                </template>
            </Table>
        </div>
        </div>
</template>

