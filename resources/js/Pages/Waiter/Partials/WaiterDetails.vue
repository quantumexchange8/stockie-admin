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

const props = defineProps({
    id: [Number, String],
    defaultDateFilter: Array,
    order: Array,
    waiter: {
        type: Object,
        required: true,
    },
    attendance: Array,
    incentiveData: Object,
    configIncentive: Object,
})

const home = ref({
    label: 'Waiter',
    route: '/waiter'
});

const items = ref([
    { label: 'Waiter Detail'},
]);

const waiter = ref(props.waiter);
const attendance = ref(props.attendance);
const data = ref(props.incentiveData);
const salesRowsPerPage = ref(11);
const commissionRowsPerPage = ref(11);
const attendanceRowsPerPage = ref(11);
const tabs = ref(['Sales', 'Commission', 'Attendance']);
const salesColumn = ref([
    {field: 'created_at', header: 'Date', sortable: true},
    {field: 'order_no', header: 'Order',sortable: true},
    {field: 'total_amount', header: 'Sales', sortable: true},
    {field: 'commission', header: 'Commission', sortable: true},
    {field: 'action', header: '',  sortable: false},
]);

const commissionColumn = ref([
    {field: 'created_by', header: 'Month', width: '15', sortable: true},
    {field: 'total_sales', header: 'Total', width: '12', sortable: true},
    {field: 'commission', header: 'Commission', width: '18', sortable: true},
    {field: 'incentive', header: 'Incentive', width: '27', sortable: true},
    {field: 'total_commission', header: 'Total Commission', width: '18', sortable: true},
    {field: 'status', header: 'Status', width: '10', sortable: true},
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

const salesTotalPages = computed(() => {
    return Math.ceil(props.order.length / salesRowsPerPage.value);
})

const commissionTotalPages = computed(() => {
    return Math.ceil(props.incentiveData.length / commissionRowsPerPage.value);
})

const attendanceTotalPages = computed(() => {
    return Math.ceil(props.attendance.length / attendanceRowsPerPage.value);
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
                                <span class="text-primary-25 text-lg font-medium ">RM {{ incentiveData.length ? incentiveData.slice(-1)[0].total_sales : 0 }}</span>
                            </div>
                            <div class="absolute bottom-0 right-0">
                                <WaiterSalesIcon />
                            </div>
                        </div>
                        <div 
                            class="w-full flex p-5 flex-col items-start gap-2.5 
                            grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100"
                        >
                            <div class="flex flex-col gap-1 self-stretch">
                                <CommissionIcon />
                                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Commission in this month</span>
                                <span class="text-primary-900 text-lg font-medium whitespace-nowrap">RM {{ incentiveData.length ? incentiveData.slice(-1)[0].total_commission : 0 }}</span>
                                <span class="text-primary-300 text-sm font-normal whitespace-nowrap">(RM {{ incentiveData.length ? incentiveData.slice(-1)[0].commission : 0 }} + RM {{ incentiveData.length ? incentiveData.slice(-1)[0].incentive : 0 }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- entitled incentive -->
                    <div 
                        class="w-full p-4 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100"
                    >
                        <EntitledIncentive :data="incentiveData" :configIncentive="configIncentive"/>
                    </div>

                    <!-- waiter detail -->
                    <div 
                        class="w-full p-6 bg-white rounded-[5px] col-span-4 border border-solid border-primary-100 min-w-[315px]"
                    >
                        <WaiterDetailCard :waiter="waiter" />
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
                                :totalPages="salesTotalPages"
                                :rowsPerPage="salesRowsPerPage"
                            />
                        </template>

                        <template #commission>
                            <Commission 
                                :order="order" 
                                :data="incentiveData" 
                                :columns="commissionColumn"
                                :waiter="waiter"
                                :rowType="rowType"
                                :totalPages="commissionTotalPages"
                                :rowsPerPage="commissionRowsPerPage"
                            />
                        </template>

                        <template #attendance>
                            <Attendance 
                                :waiter="waiter" 
                                :columns="attendanceColumn" 
                                :dateFilter="defaultDateFilter" 
                                :attendance="attendance"
                                :rowType="rowType"
                                :totalPages="attendanceTotalPages"
                                :rowsPerPage="attendanceRowsPerPage"
                            />
                        </template>

                    </TabView>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
