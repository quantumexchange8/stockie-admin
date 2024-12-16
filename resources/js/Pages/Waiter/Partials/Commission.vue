<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { UploadIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import { transactionFormat, useFileExport } from '@/Composables';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import dayjs from 'dayjs';
import { FilterMatchMode } from 'primevue/api';
import { ref, watch } from 'vue';

const props = defineProps({
    data: Object,
    rowType: Object,
    columns: Array,
    waiter: Object,
    totalPages: Number,
    rowsPerPage: Number,
})

const { formatAmount } = transactionFormat();
const { exportToCSV } = useFileExport();


const data = ref(props.data)
const searchQuery = ref('');

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const csvExport = () => {
    const waiterName = props.waiter.full_name || 'Unknown Waiter';
    const dataArr = data.value.map(data => ({
        'Date': data.created_at,
        'Monthly Sale': `RM ${data.monthly_sale}`,
        'Commission': `RM ${data.commissionAmt}`,        
    }));
    exportToCSV(dataArr, `${waiterName}_Monthly Commission Report`)
};

watch(() => searchQuery.value, (newValue) => {
    if(newValue === '') {
        data.value = props.data;
        return;
    }

    const query = newValue.toLowerCase();

    data.value = props.data.filter(filteredData => {
        const dataMonth = filteredData.created_at.toLowerCase();
        const dataSale = filteredData.monthly_sale.toString().toLowerCase();
        const dataCommission = filteredData.commissionAmt.toString().toLowerCase();

        return  dataMonth.includes(query) ||
                dataSale.includes(query) ||
                dataCommission.includes(query);
    });
})

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Monthly Commission Report</span>
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
                            { 'bg-grey-50 pointer-events-none': data.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="data.length === 0" @click="csvExport">
                            CSV
                        </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                        <button type="button" :class="[
                            // { 'bg-primary-100': active },
                            { 'bg-grey-50 pointer-events-none': data.length === 0 },
                            'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="data.length === 0">
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
                v-model="searchQuery"
            />
        </div>

            <Table
                :columns="columns"
                :variant="'list'"
                :rows="data"
                :rowType="rowType"
                :searchFilter="true"
                :filters="filters"
                :totalPages="totalPages"
                :rowsPerPage="rowsPerPage"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #created_at="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">{{ data.created_at }}</span>
                </template>
                <template #total_sales="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ formatAmount(data.monthly_sale) }}</span>
                </template>
                <template #commission="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ formatAmount(data.commissionAmt) }}</span>
                </template>
            </Table>
    </div>
</template>
