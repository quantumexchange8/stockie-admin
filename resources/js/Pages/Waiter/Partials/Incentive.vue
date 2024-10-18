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
import { transactionFormat } from '@/Composables';

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

const arrayToCsv = (data) => {
    const array = [Object.keys(data[0])].concat(data)

    return array.map(it => {
        return Object.values(it).toString()
    }).join('\n');
};

const downloadBlob = (content, filename, contentType) => {
    // Create a blob
    var blob = new Blob([content], { type: contentType });
    var url = URL.createObjectURL(blob);

    // Create a link to download it
    var pom = document.createElement('a');
    pom.href = url;
    pom.setAttribute('download', filename);
    pom.click();
};

const exportToCSV = () => { 
    const dataArr = [];
    const currentDateTime = dayjs().format('YYYYMMDDhhmmss');
    const waiterName = props.waiter || 'Unknown_Waiter';
    const fileName = `Waiter_${waiterName}_Monthly Incentive Report_${currentDateTime}.csv`;
    const contentType = 'text/csv;charset=utf-8;';

    if (incentiveData.value && incentiveData.value.length > 0) {
        incentiveData.value.forEach(row => {
            dataArr.push({
                'Date': row.monthYear,
                'Total': row.totalSales,
                'Incentive': row.incentiveAmt,
                'Status': row.status,
            })
        });

        const myLogs = arrayToCsv(dataArr);
        
        downloadBlob(myLogs, fileName, contentType);
    }
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
                            { 'bg-grey-50 pointer-events-none': incentiveData.length === 0 },
                            'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                        ]" :disabled="incentiveData.length === 0" @click="exportToCSV">
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
                <template #monthYear="incentiveData">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">{{ incentiveData.monthYear }}</span>
                </template>
                <template #totalSales="incentiveData">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(incentiveData.totalSales) }}</span>
                </template>
                <template #incentiveAmt="incentiveData">
                    <div v-if="incentiveData.incentiveAmt != 0" class="inline-flex items-center whitespace-nowrap gap-0.5">
                        <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(incentiveData.incentiveAmt) }} </span>
                            <span class="line-clamp-1 text-primary-900 text-ellipsis text-sm font-medium">
                                <template v-if="incentiveData.type == 'fixed'">
                                    ( RM {{ incentiveData.rate }} of total sales )
                                </template>
                                <template v-if="incentiveData.type == 'percentage'">
                                     ( {{ incentiveData.rate * 100 }}% of total sales )
                                </template>
                            </span>
                    </div>
                </template>
                <template #status="incentiveData">
                    <Link :href="route('configuration.incentCommDetail', incentiveData.incentiveId)">
                        <Tag
                            :variant="'green'"
                            :value="incentiveData.status"
                        />
                    </Link>
                </template>
            </Table>
        </div>
        </div>
</template>

