<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import { computed, ref } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Tag from '@/Components/Tag.vue';
import { Link } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { UploadIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import { transactionFormat, useFileExport } from '@/Composables';
import Button from '@/Components/Button.vue';
import { wTrans } from 'laravel-vue-i18n';

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
    rowsPerPage: Number,
})

const { formatAmount } = transactionFormat();
const { exportToCSV } = useFileExport();

const incentiveData = ref(props.incentiveData);
const searchQuery = ref('');

const csvExport = () => {
    const waiterName = props.waiter || wTrans('public.waiter.unknown_waiter').value;
    const title = `${waiterName}_${wTrans('public.waiter.monthly_incentive_report').value}`;
    const currentDate = dayjs().format('DD/MM/YYYY');

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Date: title, 'Total Sales': '', 'Incentive': '', 'Status': '' },
        { Date: currentDate, 'Total Sales': '', 'Incentive': '', 'Status': '' },
        { Date: wTrans('public.date').value, 'Total Sales': wTrans('public.total_sales').value, 'Incentive': wTrans('public.incentive').value, 'Status': wTrans('public.status').value },
        ...incentiveData.value.map(row => ({
            'Date': dayjs(row.period_start).format('MMMM YYYY'),
            'Total Sales': `RM ${formatAmount(row.amount)}`,
            'Incentive': `RM ${formatAmount(row.sales_target)} (` + 
                (row.type == 'percentage' 
                    ? wTrans('public.waiter.incentive_reward_percent', { percent: `${parseInt(row.rate * 100)}%` }).value
                    : wTrans('public.waiter.incentive_reward_amount', { amount: `RM ${row.rate}` }).value ) + ')',
            'Status': row.status === 'Pending' ? wTrans('public.pending').value : wTrans('public.paid').value,        
        })),
    ];

    exportToCSV(formattedRows, `${waiterName}_${wTrans('public.waiter.monthly_incentive_report').value}`);
}

const filteredIncentiveData = computed(() => {
    if (!searchQuery.value) return incentiveData.value;

    const query = searchQuery.value.toLowerCase();
    
    return incentiveData.value.filter(row =>
        dayjs(row.period_start).format('MMMM YYYY').toLowerCase().includes(query) ||
        row.amount.toLowerCase().includes(query) ||
        row.sales_target.toLowerCase().includes(query) ||
        row.status.toLowerCase().includes(query)
    );
});

const totalPages = computed(() => {
    return Math.ceil(filteredIncentiveData.value.length / props.rowsPerPage);
})

</script>

<template>
    <div class="w-full flex flex-col p-6 items-start justify-between gap-6 rounded-[5px] border border-solid border-red-100 overflow-y-auto">
        <div class="inline-flex items-center w-full justify-between gap-2.5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">{{ $t('public.waiter.monthly_incentive_report') }}</span>
            
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit"
                :disabled="incentiveData.length === 0"
                @click="csvExport"
            >
                <template #icon >
                    <UploadIcon class="size-4 cursor-pointer flex-shrink-0"/>
                </template>
                {{ $t('public.action.export') }}
            </Button>

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
            </Menu> -->
        </div>
        
        <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap items-center justify-between">
            <SearchBar 
                :placeholder="$t('public.search')"
                :showFilter="false"
                v-model="searchQuery"
            />
        </div>
        <div class="w-full" v-if="filteredIncentiveData">
            <Table
                :columns="columns"
                :rows="filteredIncentiveData"
                :variant="'list'"
                :rowType="rowType"
                :totalPages="totalPages"
                :rowsPerPage="rowsPerPage"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                </template>
                <template #period_start="row">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">{{ dayjs(row.period_start).format('MMMM YYYY') }}</span>
                </template>
                <template #amount="row">
                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ formatAmount(row.amount) }}</span>
                </template>
                <template #sales_target="row">
                    <div v-if="row.sales_target != 0" class="inline-flex items-center whitespace-nowrap gap-0.5">
                        <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">RM {{ row.type == 'percentage' ? formatAmount(row.rate * row.amount) : formatAmount(row.rate) }} </span>
                        <span class="line-clamp-1 text-primary-900 text-ellipsis text-sm font-medium">
                            ({{ 
                                row.type == 'percentage' 
                                    ? $t('public.waiter.incentive_reward_percent', { percent: `${parseInt(row.rate * 100)}%` })
                                    : $t('public.waiter.incentive_reward_amount', { amount: `RM ${row.rate}` }) 
                            }}) 
                        </span>
                    </div>
                </template>
                <template #status="row">
                    <Link :href="route('configuration.incentCommDetail', row.incentive_id)">
                        <Tag :variant="'green'" :value="row.status === 'Pending' ? $t('public.pending') : $t('public.paid')" />
                    </Link>
                </template>
            </Table>
        </div>
        </div>
</template>

