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
    attendance: Array,
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
    label: 'Waiter',
    route: '/waiter'
});

const items = ref([
    { label: 'Waiter Detail'},
]);

const { formatAmount } = transactionFormat();
const waiter = ref(props.waiter);
const attendance = ref(props.attendance);
const commissionRowsPerPage = ref(11);
const incentiveRowsPerPage = ref(11);
const tabs = ref(['Sales', 'Commission', 'Incentive', 'Attendance']);

const salesColumn = ref([
    {field: 'created_at', header: 'Date', sortable: true},
    {field: 'order_no', header: 'Order',sortable: true},
    {field: 'total_amount', header: 'Sales', sortable: true},
    {field: 'commission', header: 'Commission', sortable: true},
    {field: 'action', header: '',  sortable: false},
]);

const commissionColumn = ref([
    {field: 'created_at', header: 'Month', width: '60', sortable: true},
    {field: 'total_sales', header: 'Total Sales', width: '20', sortable: true},
    {field: 'commission', header: 'Commission', width: '20', sortable: true},
]);

const incentiveColumn = ref([
    {field: 'period_start', header: 'Month', width: '23', sortable: true},
    {field: 'amount', header: 'Incentive', width: '23', sortable: true},
    {field: 'sales_target', header: 'Total Sales', width: '34', sortable: true},
    {field: 'status', header: 'Status', width: '20', sortable: true},
]);

const attendanceColumn = ref([
    {field: 'check_in', header: 'Check in', width: '35', sortable: true},
    {field: 'check_out', header: 'Check out', width: '35', sortable: true},
    {field: 'duration', header: 'Duration', width: '30', sortable: true},
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

const commissionTotalPages = computed(() => {
    return Math.ceil(props.incentiveData.length / commissionRowsPerPage.value);
})

const incentiveTotalPages = computed(() => {
    return Math.ceil(props.commissionData.length / incentiveRowsPerPage.value);
})

</script>

<template>
    <Head title="Waiter Detail" />

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
                                <span class="text-primary-100 text-sm font-medium whitespace-nowrap w-full">Total sales this month</span>
                                <span class="text-primary-25 text-lg font-medium ">RM {{ formatAmount(props.total_sales) }}</span>
                            </div>
                            <div class="absolute bottom-0 right-0">
                                <WaiterSalesIcon />
                            </div>
                        </div>
                        <div class="w-full flex p-5 flex-col items-start gap-2.5 grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100">
                            <div class="flex flex-col gap-1 self-stretch">
                                <CommissionIcon />
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Commission in this month</span>
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
                                :totalPages="commissionTotalPages"
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
                                :totalPages="incentiveTotalPages"
                                :rowsPerPage="incentiveRowsPerPage"
                            />
                        </template>

                        <template #attendance>
                            <Attendance 
                                :waiter="waiter" 
                                :columns="attendanceColumn" 
                                :dateFilter="defaultDateFilter" 
                                :attendance="attendance"
                                :rowType="rowType"
                            />
                        </template>

                    </TabView>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
