<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TabView from '@/Components/TabView.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EntitledIncentive from './EntitledIncentive.vue';
import WaiterDetailCard from './WaiterDetailCard.vue';
import Commission from './Commission.vue';
import { CommissionIcon, GrowthIcon, WaiterSalesIcon } from '@/Components/Icons/solid';
import Sales from './Sales.vue';
import Attendance from './Attendance.vue';
import Incentive from './Incentive.vue';
import { transactionFormat } from '@/Composables';
import Toast from '@/Components/Toast.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    id: [Number, String],
    defaultDateFilter: Array,
    order: Array,
    waiter: {
        type: Object,
        required: true,
    },
    total_sales: {
        type: Number,
        default: 0,
    },
    // attendance: Array,
    commissionData: {
        type: Array,
        default: () => {},
    },
    incentiveData: Object,
    configIncentive: Object,
    commissionThisMonth: {
        type: Number,
        default: 0,
    },
})
const home = ref({
    label: wTrans('public.waiter_header'),
    route: '/waiter'
});

const items = ref([
    { label: wTrans('public.waiter.waiter_detail')},
]);

const { formatAmount } = transactionFormat();
const waiter = ref(props.waiter);
// const attendance = ref(props.attendance);
const commissionRowsPerPage = ref(11);
const incentiveRowsPerPage = ref(11);
const tabs = ref([
    { key: 'Sales', title: wTrans('public.sales'), disabled: false },
    { key: 'Commission', title: wTrans('public.commission'), disabled: false },
    { key: 'Incentive', title: wTrans('public.incentive'), disabled: false },
    { key: 'Attendance', title: wTrans('public.waiter.attendance'), disabled: false },
]);

const salesColumn = ref([
    {field: 'created_at', header: wTrans('public.date'), sortable: true},
    {field: 'order_no', header: wTrans('public.order_no'),sortable: true},
    {field: 'total_amount', header: wTrans('public.sales'), sortable: true},
    {field: 'commission', header: wTrans('public.commission'), sortable: true},
    {field: 'action', header: '',  sortable: false},
]);

const commissionColumn = ref([
    {field: 'created_at', header: wTrans('public.month_header'), width: '60', sortable: true},
    {field: 'total_sales', header: wTrans('public.total_sales'), width: '20', sortable: true},
    {field: 'commission', header: wTrans('public.commission'), width: '20', sortable: true},
]);

const incentiveColumn = ref([
    {field: 'period_start', header: wTrans('public.month_header'), width: '23', sortable: true},
    {field: 'amount', header: wTrans('public.total_sales'), width: '23', sortable: true},
    {field: 'sales_target', header: wTrans('public.incentive'), width: '34', sortable: true},
    {field: 'status', header: wTrans('public.status'), width: '20', sortable: true},
]);

const attendanceColumn = ref([
    {field: 'date', header: wTrans('public.date'), width: '35', sortable: true},
    {field: 'work_duration', header: wTrans('public.waiter.working_duration_header'), width: '30', sortable: true},
    {field: 'break_duration', header: wTrans('public.waiter.break_duration_header'), width: '35', sortable: true},
]);


const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const actions = {
    view: () => ``,
    edit: () => ``,
    delete: () => ``,
};

</script>

<template>
    <Head :title="$t('public.waiter.waiter_detail')" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home"
                :items="items"
            />
        </template>

        <Toast />

        <div class="w-full">
            <div class="w-full flex flex-col gap-5 justify-center">
                <div class="w-full flex md:flex-row gap-5 flex-col">
                    <!-- sales and commission -->
                    <div class="flex md:flex-col gap-6 flex-row">
                        <div 
                            class="w-full flex p-5 flex-col items-start gap-2.5 
                            grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100
                            bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative"
                        >
                            <div class="flex flex-col gap-1 self-stretch"> 
                                <GrowthIcon />
                                <span class="text-primary-100 text-sm font-medium whitespace-nowrap w-full">{{ $t('public.waiter.total_sales_this_month') }}</span>
                                <span class="text-primary-25 text-lg font-medium ">RM {{ formatAmount(props.total_sales) }}</span>
                            </div>
                            <div class="absolute bottom-0 right-0">
                                <WaiterSalesIcon />
                            </div>
                        </div>
                        <div class="w-full flex p-5 flex-col items-start gap-2.5 grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100">
                            <div class="flex flex-col gap-1 self-stretch">
                                <CommissionIcon />
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ $t('public.waiter.commission_this_month') }}</span>
                                <span class="text-primary-900 text-lg font-medium whitespace-nowrap">RM {{ formatAmount(props.commissionThisMonth) }}</span>
                                <!-- <span class="text-primary-300 text-sm font-normal whitespace-nowrap">(RM {{ formatAmount(props.commissionThisMonth) }} + RM {{ formatAmount(props.incentiveThisMonth) }})</span> -->
                            </div>
                        </div>
                    </div>

                    <!-- entitled incentive -->
                    <div class="w-full p-4 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100 max-h-[500px] overflow-y-auto scrollbar-webkit scrollbar-thin">
                        <EntitledIncentive 
                            :data="incentiveData" 
                            :configIncentive="configIncentive"
                        />
                    </div>

                    <!-- waiter detail -->
                    <div class="w-full p-6 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100 min-w-[315px]">
                        <WaiterDetailCard 
                            :waiter="waiter" 
                        />
                    </div>
                </div>
                
                <!-- Daily Sales Report -->
                <div class="w-full">
                    <TabView :tabs="tabs">

                        <template #sales>
                            <Sales 
                                :order="order" 
                                :waiter="waiter" 
                                :dateFilter="defaultDateFilter" 
                                :columns="salesColumn" 
                                :actions="actions"
                                :rowType="rowType"
                            />
                        </template>

                        <template #commission>
                            <Commission 
                                :data="commissionData" 
                                :columns="commissionColumn"
                                :waiter="waiter"
                                :rowType="rowType"
                                :rowsPerPage="commissionRowsPerPage"
                            />
                        </template>

                        <template #incentive>
                            <Incentive 
                                :waiter="waiter.full_name" 
                                :columns="incentiveColumn" 
                                :dateFilter="defaultDateFilter" 
                                :incentiveData="incentiveData"
                                :rowType="rowType"
                                :rowsPerPage="incentiveRowsPerPage"
                            />
                        </template>

                        <template #attendance>
                            <Attendance 
                                :waiter="waiter" 
                                :columns="attendanceColumn" 
                                :dateFilter="defaultDateFilter" 
                                :rowType="rowType"
                            />
                        </template>

                    </TabView>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
