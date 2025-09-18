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
import EmployeeEarning from './Partials/EmployeeEarning.vue';
import { transactionFormat, useCustomToast } from '@/Composables/index.js';
import ProductSales from './Partials/ProductSales.vue';
import CategorySales from './Partials/CategorySales.vue';
import { wTrans, wTransChoice } from 'laravel-vue-i18n';

const { showMessage } = useCustomToast();
const { formatAmount } = transactionFormat();

const home = ref({
    label: wTrans('public.report_header'),
});

const salesTypes = computed(() => [
    { text: wTrans('public.report.sales_summary'), value: 'sales_summary'},
    { text: wTrans('public.payment_method'), value: 'payment_method'},
    { text: wTrans('public.report.product_sales'), value: 'product_sales'},
    { text: wTrans('public.report.category_sales'), value: 'category_sales'},
    { text: wTrans('public.report.employee_earnings'), value: 'employee_earning'},
    { text: wTrans('public.report.member_purchase'), value: 'member_purchase'},
    { text: wTrans('public.current_stock'), value: 'current_stock'},
]);

const salesSummaryHeader = computed(() => [
    { title: wTrans('public.date'), width: '14' },
    { title: `${wTrans('public.report.gross').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.tax').value} (RM)`, width: '14' },
    { title: `${wTransChoice('public.refund', 1).value} (RM)`, width: '16' },
    { title: `${wTransChoice('public.void', 1).value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.disc').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.net').value} (RM)`, width: '14' },
]);

const paymentMethodHeader = computed(() => [
    { title: wTrans('public.method'), width: '20' },
    { title: wTrans('public.report.no_of_sales'), width: '16' },
    { title: `${wTrans('public.sales').value} (RM)`, width: '16' },
    { title: wTrans('public.report.no_of_refund'), width: '16' },
    { title: `${wTransChoice('public.refund', 1).value} (RM)`, width: '16' },
    { title: `${wTrans('public.balance').value} (RM)`, width: '16' },
]);

const productSalesHeader = computed(() => [
    { title: wTrans('public.product'), width: '21' },
    { title: wTrans('public.sold'), width: '8' },
    { title: `${wTrans('public.report.gross').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.disc').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.tax').value} (RM)`, width: '14' },
    { title: `${wTransChoice('public.refund', 1).value} (RM)`, width: '15' },
    { title: `${wTrans('public.report.net').value} (RM)`, width: '14' },
]);

const categorySalesHeader = computed(() => [
    { title: wTrans('public.category'), width: '21' },
    { title: wTrans('public.sold'), width: '8' },
    { title: `${wTrans('public.report.gross').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.disc').value} (RM)`, width: '14' },
    { title: `${wTrans('public.report.tax').value} (RM)`, width: '14' },
    { title: `${wTransChoice('public.refund', 1).value} (RM)`, width: '15' },
    { title: `${wTrans('public.report.net').value} (RM)`, width: '14' },
]);

const employeeEarningsHeader = computed(() => [
    { title: wTrans('public.report.employee'), width: '43' },
    { title: `${wTrans('public.sales').value} (RM)`, width: '19' },
    { title: `${wTrans('public.incentive').value} (RM)`, width: '19' },
    { title: `${wTrans('public.commission').value} (RM)`, width: '19' },
]);

const memberPurchaseHeader = computed(() => [
    { title: wTrans('public.customer_header'), width: '25' },
    { title: `${wTrans('public.report.total_purchase').value} (RM)`, width: '22' },
    { title: wTrans('public.report.no_of_purchase'), width: '18' },
    { title: `${wTrans('public.report.avg_spent').value} (RM)`, width: '18' },
    { title: wTrans('public.points_earned'), width: '17' },
]);

const currentStockHeader = computed(() => [
    { title: wTrans('public.item'), width: '43' },
    { title: wTrans('public.report.stock_qty'), width: '19' },
    { title: wTrans('public.unit'), width: '19' },
    { title: wTrans('public.report.kept_qty'), width: '19' },
]);

const reportComponents = {
    sales_summary: SalesSummary,
    payment_method: PaymentMethod,
    product_sales: ProductSales,
    category_sales: CategorySales,
    employee_earning: EmployeeEarning,
    member_purchase: MemberPurchase,
    current_stock: CurrentStock,
};

const getHeaderForType = (type) => {
    const headerMap = {
        sales_summary: salesSummaryHeader.value,
        payment_method: paymentMethodHeader.value,
        product_sales: productSalesHeader.value,
        category_sales: categorySalesHeader.value,
        employee_earning: employeeEarningsHeader.value,
        member_purchase: memberPurchaseHeader.value,
        current_stock: currentStockHeader.value,
    };
    return headerMap[type] || [];
};

const rows = ref([]);
const sales_type = ref('sales_summary');
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
        showMessage({
            severity: 'error',
            summary: wTrans('public.toast.report_export_error'),
        });
    }
};

const dateRangeDisplay = computed(() => {
    const start = dayjs(date_filter.value[0]).format('DD/MM/YYYY');
    const end = date_filter.value[1] != null ? dayjs(date_filter.value[1]).format('DD/MM/YYYY') : dayjs(date_filter.value[0]).endOf('day').format('DD/MM/YYYY');

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

    <Head :title="$t('public.report_header')" />

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
                        {{ $t('public.action.export') }}
                    </Button>
                </div>
                <div class="flex justify-center">
                    <div class="flex flex-col w-[595px] p-3 items-center h-[calc(100dvh-19.6rem)] gap-5 rounded-[5px] bg-white shadow-[0px_3.361px_13.277px_0px_rgba(13,13,13,0.08)]">
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