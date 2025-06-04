<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { computed, onMounted, ref, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import Dropdown from '@/Components/Dropdown.vue';
import dayjs from 'dayjs';
import DateInput from '@/Components/Date.vue';
import SalesSummary from './Partials/SalesSummary.vue';
import PaymentMethod from './Partials/PaymentMethod.vue';
import MemberPurchase from './Partials/MemberPurchase.vue';
import CurrentStock from './Partials/CurrentStock.vue';
import { transactionFormat } from '@/Composables/index.js';

const { formatAmount } = transactionFormat();

const home = ref({
    label: 'Report',
});

const salesTypes = ref([
    { text: 'Sales Summary', value: 'sales_summary'},
    { text: 'Payment Method', value: 'payment_method'},
    // { text: 'Product Sales', value: 'product_sales'},
    // { text: 'Category Sales', value: 'category_sales'},
    // { text: 'Employee Earnings', value: 'employee_earning'},
    { text: 'Member Purchase', value: 'member_purchase'},
    { text: 'Current Stock', value: 'current_stock'},
]);

const salesSummaryHeader = ref([
    { title: 'Date', width: '14' },
    { title: 'Gross (RM)', width: '14' },
    { title: 'Tax (RM)', width: '14' },
    { title: 'Refunds (RM)', width: '16' },
    { title: 'Voids (RM)', width: '14' },
    { title: 'Disc. (RM)', width: '14' },
    { title: 'Net (RM)', width: '14' },
]);

const paymentMethodHeader = ref([
    { title: 'Method', width: '20' },
    { title: 'No.of Sales', width: '16' },
    { title: 'Sales (RM)', width: '16' },
    { title: 'No.of Refund', width: '16' },
    { title: 'Refund (RM)', width: '16' },
    { title: 'Balance (RM)', width: '16' },
]);

const productSalesHeader = ref([
    { title: 'Product', width: '14' },
    { title: 'Sold', width: '14' },
    { title: 'Gross (RM)', width: '14' },
    { title: 'Disc. (RM)', width: '14' },
    { title: 'Tax (RM)', width: '14' },
    { title: 'Refund (RM)', width: '16' },
    { title: 'Net (RM)', width: '14' },
]);

const categorySalesHeader = ref([
    { title: 'Category', width: '14' },
    { title: 'Gross (RM)', width: '14' },
    { title: 'Disc. (RM)', width: '14' },
    { title: 'Tax (RM)', width: '14' },
    { title: 'Refund (RM)', width: '16' },
    { title: 'Net (RM)', width: '14' },
]);

const employeeEarningsHeader = ref([
    { title: 'Employee', width: '14' },
    { title: 'Sales (RM)', width: '14' },
    { title: 'Incentive (RM)', width: '14' },
    { title: 'Commission (RM)', width: '16' },
]);

const memberPurchaseHeader = ref([
    { title: 'Customer', width: '22' },
    { title: 'Total Purchase(RM)', width: '21' },
    { title: 'No.of Purchase', width: '17' },
    { title: 'Avg. Amt. Spent(RM)', width: '23' },
    { title: 'Points Earned', width: '17' },
]);

const currentStockHeader = ref([
    { title: 'Item', width: '43' },
    { title: 'Stock Qty', width: '19' },
    { title: 'Unit', width: '19' },
    { title: 'Kept Qty', width: '19' },
]);

const reportComponents = {
    sales_summary: SalesSummary,
    payment_method: PaymentMethod,
    member_purchase: MemberPurchase,
    current_stock: CurrentStock,
};

const getHeaderForType = (type) => {
    const headerMap = {
        sales_summary: salesSummaryHeader.value,
        payment_method: paymentMethodHeader.value,
        member_purchase: memberPurchaseHeader.value,
        current_stock: currentStockHeader.value,
    };
    return headerMap[type] || [];
};

const rows = ref([]);
const sales_type = ref('current_stock');
const isLoading = ref(false);
const reportComponentRef = ref(null);

const defaultLatestMonth = computed(() => {
    let currentDate = dayjs();
    let lastMonth = currentDate.subtract(1, 'month');

    return [lastMonth.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatestMonth.value); 

const getReport = async (filters = {}, typeFilter = '') => {
    isLoading.value = true;
    try {
        rows.value = [];
        const response = await axios.get(route('report.getReport'), {
            params: {
                date_filter: filters,
                typeFilter: typeFilter,
            }
        });
        rows.value = response.data;
        
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const currentTitle = computed(() => {
    return salesTypes.value.find((type) => type.value === sales_type.value).text;
});

const exportCsv = () => {
    if (reportComponentRef.value?.csvExport) {
        reportComponentRef.value.csvExport();
    } else {
        console.warn('csvExport not available on current report component.');
    }
};

const dateRangeDisplay = computed(() => {
    const start = dayjs(date_filter.value[0]).format('DD/MM/YYYY');
    const end = date_filter.value.length > 1 ? dayjs(date_filter.value[1]).format('DD/MM/YYYY') : dayjs(date_filter.value[0]).format('DD/MM/YYYY');

    return sales_type.value === 'current_stock' ? dayjs().format('DD/MM/YYYY HH:mm:ss') : `${start} - ${end}`;
})

onMounted(() => {
    getReport(date_filter.value, sales_type.value);
});

watch(() => date_filter.value, (newValue) => {
    getReport(newValue, sales_type.value);
})

watch(sales_type, (newValue) => {
    getReport(date_filter.value, newValue);
})

</script>

<template>

    <Head title="Report" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <div class="flex flex-col max-w-full justify-end items-start gap-5 self-stretch">
            <div class="flex flex-col p-6 gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class=" w-full md:max-w-[218px] xl:max-w-[268px]">
                            <Dropdown
                                :inputName="'sale_type'"
                                :labelText="''"
                                :inputArray="salesTypes"
                                :dataValue="sales_type"
                                v-model="sales_type"
                                class="w-full"
                            />
                        </div>
                        <DateInput
                            v-if="sales_type !== 'current_stock'"
                            :inputName="'date_filter'"
                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                            :range="true"
                            class="w-60"
                            v-model="date_filter"
                        />
                    </div>
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        class="!w-fit"
                        @click="exportCsv"
                    >
                        <template #icon >
                            <UploadIcon class="size-6" />
                        </template>
                        Export
                    </Button>
                </div>
                <div class="flex justify-center">
                    <div class="flex flex-col w-[595px] p-3 items-center h-[710px] gap-5 rounded-[5px] bg-white shadow-[0px_3.361px_13.277px_0px_rgba(13,13,13,0.08)]">
                        <div class="flex flex-col self-stretch items-start gap-1">
                            <p class="text-center text-grey-950 text-base font-bold self-stretch">{{ currentTitle }}</p>
                            <p class="text-center text-grey-950 text-xs font-normal self-stretch">{{ dateRangeDisplay }}</p>
                        </div>
                        
                        <div class="w-full max-h-[calc(100dvh-7rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                            <component
                                ref="reportComponentRef"
                                :is="reportComponents[sales_type]"
                                :key="sales_type"
                                :rows="rows"
                                :columns="getHeaderForType(sales_type)"
                                :dateFilter="date_filter"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
    
</template>