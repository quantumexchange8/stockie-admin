<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { UploadIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import Tag from '@/Components/Tag.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { FilterMatchMode } from 'primevue/api';
import { ref } from 'vue';

const props = defineProps({
    order: Array,
    data: Object,
    rowType: Object,
    columns: Array,
    waiter: Object,
    totalPages: Number,
    rowsPerPage: Number,
})
const data = ref(props.data)
const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

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
    const waiterName = props.waiter.name || 'Unknown_Waiter';
    const fileName = `Waiter_${waiterName}_Daily Sales Report_${currentDateTime}.csv`;
    const contentType = 'text/csv;charset=utf-8;';

    if (data.value && data.value.length > 0) {
        data.value.forEach(row => {
            dataArr.push({
                'Date': dayjs(row.created_at).format('DD/MM/YYYY'),
                'Time': dayjs(row.created_at).format('hh:mm A'),
                'Total': row.total_sales,
                'Commission': row.commission,
                'Incentive': row.incentive,
                'Total Commission': row.total_commission,
                'Status': row.status,
            })
        });

        const myLogs = arrayToCsv(dataArr);
        
        downloadBlob(myLogs, fileName, contentType);
    }
}

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
                        ]" :disabled="data.length === 0" @click="exportToCSV">
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
                v-model="filters['global'].value"
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
                minWidth="min-w-[1070px]"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #created_by="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">{{ data.created_by }}</span>
                </template>
                <template #total_sales="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ data.total_sales }}</span>
                </template>
                <template #commission="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ data.commission }}</span>
                </template>
                <template #incentive="data">
                    <div v-if="data.incentive != 0" class="inline-flex items-center text-nowrap gap-0.5">
                        <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ data.incentive }}</span> 
                        <Link :href="route('configurations')"><span class="text-primary-900 text-sm font-medium line-clamp-1 truncate">({{ data.rate }} of total sales)</span></Link>
                    </div>
                    <div v-else class="">
                        <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">-</span>
                    </div>
                </template>
                <template #total_commission="data">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 truncate">RM {{ data.total_commission }}</span>
                </template>
                <template #status="data">
                    <Tag
                        :variant="'green'"
                        :value="data.status"
                    />
                </template>
            </Table>
    </div>
</template>
