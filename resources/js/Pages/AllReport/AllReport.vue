<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { computed, onMounted, ref, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import Dropdown from '@/Components/Dropdown.vue';
import dayjs from 'dayjs';

const home = ref({
    label: 'Report',
});

const salesTypes = ref([
    { text: 'Sales Summary', value: 'sales_summary'},
    { text: 'Payment Method', value: 'payment_method'},
    { text: 'Product Sales', value: 'product_sales'},
    { text: 'Category Sales', value: 'category_sales'},
    { text: 'Employee Earnings', value: 'employee_earning'},
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
    { title: 'Method', width: '14' },
    { title: 'No.of Sales', width: '14' },
    { title: 'Sales (RM)', width: '14' },
    { title: 'No.of Refund', width: '16' },
    { title: 'Refund (RM)', width: '14' },
    { title: 'Balance (RM)', width: '14' },
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
    { title: 'Customer', width: '14' },
    { title: 'Total Purchase (RM)', width: '14' },
    { title: 'No.of Purchase(RM)', width: '14' },
    { title: 'Avg. Amt. Spent (RM)', width: '16' },
    { title: 'Points Earned', width: '14' },
]);

const currentStockHeader = ref([
    { title: 'Item', width: '14' },
    { title: 'Stock Qty', width: '14' },
    { title: 'Unit', width: '14' },
    { title: 'Kept Qty', width: '16' },
]);

const rows = ref([]);
const sales_type = ref('sales_summary');
const isLoading = ref(false);
const defaultLatestMonth = computed(() => {
    let currentDate = dayjs();
    let lastMonth = currentDate.subtract(1, 'month');

    return [lastMonth.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatestMonth.value); 

const getTypeData = (type) => {
    // if (type === 'sales_summary') {
    //         $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
    //                             $query->whereDate('receipt_start_date', '>=', $startDate)
    //                                     ->whereDate('receipt_start_date', '<=', $endDate);
    //                         })
    //                         ->where('status', 'Successful')
    //                         ->with([
    //                             'order.orderItems', 
    //                         ])
    //                         ->orderBy('id')
    //                         ->get();
                            
    // } elseif (type === 'payment_method') {
    //     $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
    //                             $query->whereDate('receipt_start_date', '>=', $startDate)
    //                                     ->whereDate('receipt_start_date', '<=', $endDate);
    //                         })
    //                         ->where('status', 'Successful')
    //                         ->with([
    //                             'order', 
    //                             'paymentMethods',
    //                             'paymentRefunds',
    //                         ])
    //                         ->orderBy('id')
    //                         ->get();

    // } elseif (type === 'product_sales') {
    //     $data = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
    //                         $query->whereHas('payment', function ($subquery) use ($startDate, $endDate) {
    //                             $subquery->whereDate('receipt_start_date', '>=', $startDate)
    //                                     ->whereDate('receipt_start_date', '<=', $endDate);
    //                         });
    //                     })
    //                     ->where('status', 'Served')
    //                     ->with([
    //                         'order', 
    //                         'product', 
    //                     ])
    //                     ->orderBy('id')
    //                     ->get();

    // } elseif (type === 'category_sales') {
    //     $data = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
    //                         $query->whereHas('payment', function ($subquery) use ($startDate, $endDate) {
    //                             $subquery->whereDate('receipt_start_date', '>=', $startDate)
    //                                     ->whereDate('receipt_start_date', '<=', $endDate);
    //                         });
    //                     })
    //                     ->where('status', 'Served')
    //                     ->with([
    //                         'order', 
    //                         'product', 
    //                     ])
    //                     ->orderBy('id')
    //                     ->get();

    // } elseif (type === 'employee_earning') {
    //     $data = User::where('position', 'waiter')
    //                 ->with([
    //                     'incentives', 
    //                 ])
    //                 ->orderBy('id')
    //                 ->get()
    //                 ->map(function ($user) use ($startDate, $endDate) {
    //                     $user['sales'] = $user->sales()
    //                                         ->with(['order','product.commItem.configComms'])
    //                                         ->whereDate('orders.created_at', '>=', $startDate)
    //                                         ->whereDate('orders.created_at', '<=', $endDate)
    //                                         ->select('order_items.*')
    //                                         ->orderByDesc('orders.created_at')
    //                                         ->get();

    //                     $user['commission'] = $user['sales']->copy()
    //                                                         ->reduce(function ($total, $order) {
    //                                                             $product = $order->product;
    //                                                             $commItem = $product->commItem;
                                                                
    //                                                             $commissionAmt = $commItem 
    //                                                                 ? ($commItem->configComms->comm_type === 'Fixed amount per sold product'
    //                                                                     ? $commItem->configComms->rate * $order->item_qty
    //                                                                     : $product->price * $order->item_qty * ($commItem->configComms->rate / 100))
    //                                                                 : 0;
                                                        
    //                                                             return $total + round($commissionAmt, 2);
    //                                                         }, 0);

    //                     return $user;
    //                 });

    // } elseif (type === 'member_purchase') {
    //     $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
    //                         $query->whereDate('receipt_start_date', '>=', $startDate)
    //                                 ->whereDate('receipt_start_date', '<=', $endDate);
    //                     })
    //                     ->where('status', 'Successful')
    //                     ->with([
    //                         'order', 
    //                         'customer', 
    //                     ])
    //                     ->orderBy('id')
    //                     ->get();

    // } elseif (type === 'current_stock') {
    //     $data = Iventory::where('status', 'Active')
    //                     ->with([
    //                         'inventoryItems.itemCategory', 
    //                     ])
    //                     ->orderBy('id')
    //                     ->get();

    // }
}



const getReport = async (filters = {}, typeFilter = '') => {
    isLoading.value = true;
    try {
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

const currentHeader = computed(() => {
    switch (sales_type.value) {
        case 'sales_summary':
            return salesSummaryHeader.value;
            break;
        case 'payment_method':
            return paymentMethodHeader.value;
            break;
        case 'product_sales':
            return productSalesHeader.value;
            break;
        case 'category_sales':
            return categorySalesHeader.value;
            break;
        case 'employee_earning':
            return employeeEarningsHeader.value;
            break;
        case 'member_purchase':
            return memberPurchaseHeader.value;
            break;
        case 'current_stock':
            return currentStockHeader.value;
            break;
    }
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
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
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
                        <div>
                            
                        </div>
                    </div>
                    <div>
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            class="!w-fit"
                        >
                            <template #icon >
                                <UploadIcon class="size-6" />
                            </template>
                            Export
                        </Button>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="flex flex-col w-[595px] p-3 items-center h-[710px] gap-5 rounded-[5px] bg-white shadow-[0px_3.361px_13.277px_0px_rgba(13,13,13,0.08)]">
                        <div class="flex flex-col self-stretch items-start gap-1">
                            <p class="text-center text-grey-950 text-base font-bold self-stretch">{{ currentTitle }}</p>
                            <p class="text-center text-grey-950 text-xs font-normal self-stretch">01/12/2024 - 31/12/2024</p>
                        </div>
                        
                        <div class="w-full max-h-[calc(100dvh-7rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                            <table class="w-full border-spacing-3 border-collapse">
                                <thead class="bg-grey-100">
                                    <tr>
                                        <template v-for="record in currentHeader">
                                            <th :class="`w-[${record}%] py-2 px-3`">
                                                <span class="flex justify-between items-center text-2xs text-grey-950 font-semibold">
                                                    {{ record.title }}
                                                </span> 
                                            </th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <tr v-for="(record, index) in stockHistories" :key="index" class="border-b border-grey-100"> -->
                                    <template v-for="(row, index) in rows" :key="index">
                                        <template v-if="sales_type === 'sales_summary'">
                                            <tr class="border-b border-grey-100">
                                                <td class="w-[14%]">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">{{ dayjs(row.receipt_start_date).format('DD/MM/YYYY') }}</span>
                                                </td>
                                                <td class="w-[14%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ row.grand_total }}</span>
                                                    </div>
                                                </td>
                                                <td class="w-[14%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) }}</span>
                                                    </div>
                                                </td>
                                                <td class="w-[16%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) }}</span>
                                                    </div>
                                                </td>
                                                <td class="w-[14%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">0.00</span>
                                                    </div>
                                                </td>
                                                <td class="w-[14%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2) }}</span>
                                                    </div>
                                                </td>
                                                <td class="w-[14%]">
                                                    <div class="flex justify-start items-center gap-3 px-3">
                                                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.grand_total - (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) - (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) - (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2)).toFixed(2) }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>

                                    <!-- <template v-if="sales_type === 'payment_method'">
                                        <tr class="border-b border-grey-100">
                                            <td class="w-[14%]">
                                                <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Cash</span>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ rows.filter((p) => p.payment_methods.filter((pd) => pd.payment_method === 'Cash').length > 0).length }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ rows.filter((p) => p.payment_methods.filter((pd) => pd.payment_method === 'Cash').length > 0).reduce((total, p) => total + p.payment_methods.find((pd) => pd.payment_method === 'Cash').amount, 0.00) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[16%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ rows.reduce((total, row) => total + row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00), 0) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ rows.reduce((total, p) => total + p.payment_refunds.filter((pf) => pf.refund_method === 'Cash').length > 0, 0) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ rows.reduce((total, p) => total + p.payment_refunds.filter((pf) => pf.refund_method === 'Cash').reduce((pfTotal, pfr) => pfTotal + pfr.total_refund_amount, 0.00), 0) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (rows.filter((p) => p.payment_methods.filter((pd) => pd.payment_method === 'Cash').length > 0).reduce((total, p) => total + p.payment_methods.find((pd) => pd.payment_method === 'Cash').amount, 0.00)) - (rows.reduce((total, p) => total + p.payment_refunds.filter((pf) => pf.refund_method === 'Cash').reduce((pfTotal, pfr) => pfTotal + pfr.total_refund_amount, 0.00), 0)) }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-grey-100">
                                            <td class="w-[14%]">
                                                <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Credit/Debit Card</span>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ row.grand_total }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[16%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">0.00</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.grand_total - (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) - (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) - (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-grey-100">
                                            <td class="w-[14%]">
                                                <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">E-Wallets</span>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ row.grand_total }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[16%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">0.00</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                            <td class="w-[14%]">
                                                <div class="flex justify-start items-center gap-3 px-3">
                                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ (row.grand_total - (row.order.order_items.filter((item) => item.refund_qty > 0).reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00)).toFixed(2) - (Number(row.sst_amount) + Number(row.service_tax_amount)).toFixed(2) - (Number(row.discount_amount) + Number(row.bill_discount_total)).toFixed(2)).toFixed(2) }}</span>
                                                </div>
                                            </td>
                                        </tr> 
                                    </template> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
    
</template>