<script setup>
import dayjs from 'dayjs';
import { ref, computed } from 'vue'
import Table from '@/Components/Table.vue';
import { WineBackgroundIllus1 } from '@/Components/Icons/illus';
import StockieLogo from "../../../../../public/favicon.ico";
import QRCodeImage from "../../../../assets/images/stockie-admin-qr.png";
import { usePhoneUtils } from '@/Composables/index.js';

const props = defineProps({
    order: Object,
    merchant: Object,
    taxes: Object
});

const { formatPhone } = usePhoneUtils();

const order = ref(props.order);

const invoiceOrderItemsColumns = ref([
    {field: 'item_qty', header: 'Qty', width: '15', sortable: false},
    {field: 'product_name', header: 'Item', width: '55', sortable: false},
    {field: 'amount', header: 'Amt (RM)', width: '30', sortable: false},
]);

const discountSummaryColumns = ref([
    {field: 'summary', header: 'Discount Summary', width: '70', sortable: false},
    {field: 'discount_amount', header: 'Amt (RM)', width: '30', sortable: false},
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
};

const hasVoucherApplied = computed(() => {
    if (!props.order) return false;
    return props.order.payment.discount_id && props.order.payment.voucher;
})

const appliedVoucher = computed(() => (props.order.payment.voucher ? [props.order] : []));

// const sstAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (6 / 100));
// })

// const serviceChargeAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (10 / 100)) ?? 0.00;
// })

// const totalEarnedPoints = computed(() => {
//     return order.value.order_items.filter((item) => item.status === 'Served').reduce((total, item) => total + item.point_earned, 0) ?? 0.00;
// })

const orderTableNames = computed(() => order.value.order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '');
</script>

<template>
    <div class="w-full relative flex flex-col max-h-[calc(100dvh-6rem)] overflow-auto scrollbar-thin scrollbar-webkit gap-10">
        <div class="flex flex-col gap-[120px] pb-6">
            <WineBackgroundIllus1 class="absolute top-0 right-0 w-full z-0"/>
            <div class="relative z-10 flex flex-col pl-5 pr-6 pt-6 gap-y-8">
                <div class="flex flex-col gap-y-10 justify-center">
                    <div class="flex items-start px-1">
                        <div class="flex flex-col justify-center items-start gap-y-1 pr-1">
                            <p class="text-primary-100 text-md font-medium self-stretch">{{ merchant.merchant_name }}</p>
                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ merchant.merchant_address_line1 }}, {{ merchant.merchant_address_line2 }}</p>
                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ merchant.postal_code }} {{ merchant.area }}, {{ merchant.state }}</p>

                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ formatPhone(merchant.merchant_contact) }}</p>
                        </div>
                        <img 
                            :src="merchant.image ? merchant.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="MerchantLogo" 
                            width="64" 
                            height="64" 
                            class="flex-shrink-0 rounded-full"
                        />
                    </div>
    
                    <div class="flex flex-col bg-primary-25 rounded-md items-center px-4 py-6">
                        <p class="text-primary-800 text-base font-medium">TOTAL SPENT</p>
                        <p class="text-primary-800 text-[28px] font-medium whitespace-nowrap !leading-none">RM <span class="text-[56px]">{{ order.payment.grand_total }}</span></p>
                    </div>
                </div>
    
                <div class="flex flex-col gap-y-4 items-start self-stretch">
                    <p class="text-primary-950 text-md font-medium self-stretch">{{ dayjs(order.payment.receipt_end_date).format('DD/MM/YYYY, HH:mm') }}</p>
                    <div class="flex gap-x-3 py-4 items-start justify-evenly self-stretch">
                        <div class="w-5/12 flex flex-col justify-between items-start border-r-2 border-primary-100 gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Receipt No.</p>
                            <p class="text-primary-950 text-sm font-medium break-all">{{ order.payment.receipt_no }}</p>
                        </div>
                        <div class="w-2/12 flex flex-col justify-between items-start border-r-2 border-primary-100 gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Pax</p>
                            <p class="text-primary-950 text-sm font-medium">{{ order.payment.pax }}</p>
                        </div>
                        <div class="w-5/12 flex flex-col justify-between items-start gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Table No.</p>
                            <p class="text-primary-950 text-sm font-medium">{{ orderTableNames }}</p>
                        </div>
                    </div>
                </div>
    
                <Table 
                    :variant="'list'"
                    :rows="order.order_items.filter((item) => item.status === 'Served')"
                    :columns="invoiceOrderItemsColumns"
                    :rowType="rowType"
                    :paginator="false"
                >
                    <template #empty>
                        <span class="text-primary-900 text-sm font-medium">There is no item for this order.</span>
                    </template>
                    <template #item_qty="row">
                        <div class="flex items-start h-full text-grey-900 text-sm font-medium">{{ row.item_qty }}</div>
                    </template>
                    <template #product_name="row">
                        <div class="w-full flex flex-col items-start self-stretch gap-y-3">
                            <p class="text-grey-900 text-sm font-medium">
                                {{ row.type !== 'Normal' ? `(${row.type})` : '' }}
                                {{ row.product.bucket === 'set' ? '(Set) ' : '' }}
                                {{ row.product.product_name }}
                            </p>
                            <p class="text-grey-900 text-sm font-medium" v-if="row.discount_id">{{ `${row.product_discount.discount.name} Discount` }}</p>
                        </div>
                    </template>
                    <template #amount="row">
                        <div class="w-full flex flex-col items-end self-stretch gap-y-3" v-if="row.discount_id">
                            <p class="text-grey-900 text-sm font-medium text-right">{{ parseFloat(row.type === 'Normal' ? row.amount_before_discount : 0).toFixed(2) }}</p>
                            <p class="text-grey-900 text-sm font-medium">- {{ parseFloat(row.discount_amount).toFixed(2) }}</p>
                        </div>
                        <p class="text-grey-900 text-sm font-medium text-right w-full" v-else>{{ parseFloat(row.amount).toFixed(2) }}</p>
                    </template>
                </Table>
    
                <div class="flex flex-col gap-4 items-center self-stretch">
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-md font-light">Subtotal</p>
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment.total_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment.voucher">
                        <p class="text-primary-950 text-md font-light">Voucher Discount {{ order.payment.voucher.reward_type === 'Discount (Percentage)' ? `(${order.payment.voucher.discount}%)` : `` }}</p>
                        <p class="text-primary-950 text-md font-normal">- {{ parseFloat(order.payment.discount_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment.service_tax_amount > 0">
                        <p class="text-primary-950 text-md font-light">Service Tax ({{ Math.round((order.payment.service_tax_amount / order.payment.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment.service_tax_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment.sst_amount > 0">
                        <p class="text-primary-950 text-md font-light">SST ({{ Math.round((order.payment.sst_amount / order.payment.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment.sst_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-md font-light">Rounding</p>
                        <p class="text-primary-950 text-md font-normal">{{ Math.sign(order.payment.rounding) === -1 ? '-' : '' }} {{ parseFloat(Math.abs(order.payment.rounding ?? 0)).toFixed(2) }}</p>
                    </div>
                </div>
    
                <template v-if="order.payment.point_history">
                    <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                    <div class="flex flex-col gap-4 items-center self-stretch">
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-md font-light">Points Earned</p>
                            <p class="text-primary-950 text-md font-normal">{{ order.payment.point_history.amount }}</p>
                        </div>
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-md font-light">Points Balance</p>
                            <p class="text-primary-950 text-md font-normal">{{ order.payment.point_history.new_balance }}</p>
                        </div>
                    </div>
                </template>
    
                <template v-if="hasVoucherApplied">
                    <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                    <Table 
                        :variant="'list'"
                        :rows="appliedVoucher"
                        :columns="discountSummaryColumns"
                        :rowType="rowType"
                        :paginator="false"
                    >
                        <template #summary="row">
                            <p class="text-grey-900 text-sm font-medium">
                                {{ row.payment.voucher.reward_type === 'Discount (Percentage)' ? `${row.payment.voucher.discount}%` : `RM ${row.payment.voucher.discount}` }}
                                {{ ` Discount (Entry Reward for ${row.customer.rank.name})` }}
                            </p>
                        </template>
                        <template #discount_amount="row">
                            <div class="flex items-start h-full text-grey-900 text-sm font-medium">- RM {{ parseFloat(row.payment.discount_amount ?? 0).toFixed(2) }}</div>
                        </template>
                    </Table>
                </template>
    
                <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                <div class="flex items-center justify-center">
                    <img :src="QRCodeImage" alt="QRCodeImage" width="200" height="200"/>
                </div>
    
                <div class="flex flex-col items-center">
                    <p class="text-primary-950 text-md font-medium">Scan QR below to request your</p>
                    <p class="text-primary-950 text-md font-semibold">e-Invoice</p>
                </div>
    
            </div>
        </div>
        <div class="flex flex-col gap-2 py-6 rounded-md justify-center items-center bg-primary-900">
            <p class="text-primary-50 text-[28px] font-light">Thank you for your visit!</p>
            <div class="flex items-center text-white text-xs font-light gap-1">
                <p>Order invoice generated by</p>
                <img 
                    :src="StockieLogo" 
                    alt="StockieLogo" 
                    width="16" 
                    height="16" 
                    class="ml-1 mr-1 flex-shrink-0"
                />
                <span class="font-extrabold">stockie</span>
            </div>
        </div>
    </div>
</template>
