<script setup>
import dayjs from 'dayjs';
import { ref, computed, onMounted } from 'vue'
import Table from '@/Components/Table.vue';
import { WineBackgroundIllus1 } from '@/Components/Icons/illus';
import StockieLogo from "../../../../../public/favicon.ico";
import QRCodeImage from "../../../../assets/images/stockie-admin-qr.png";
import { usePhoneUtils } from '@/Composables/index.js';
import QRCodeVue3 from "qrcode-vue3";
import md5 from 'crypto-js/md5';

const props = defineProps({
    orderId: Number,
});

const { formatPhone } = usePhoneUtils();

const order = ref('');
const taxes = ref('');
const merchant = ref('');
const payout = ref();

const getOrderPaymentDetails = async () => {
    try {
        const response = await axios.get(`/order-management/getOrderPaymentDetails/${props.orderId}`);
        order.value = response.data.order;
        taxes.value = response.data.taxes;
        merchant.value = response.data.merchant;

    } catch (error) {
        console.error(error);
    } finally {

    }
}

const getPayoutDetails = async () => {
    try {
        const response = await axios.get('/getPayoutDetails');

        payout.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getOrderPaymentDetails();
    getPayoutDetails();
});

const invoiceOrderItemsColumns = ref([
    {field: 'item_qty', header: 'Qty', width: '15', sortable: false},
    {field: 'product_name', header: 'Item', width: '55', sortable: false},
    {field: 'amount', header: 'Amt (RM)', width: '30', sortable: false},
]);

const discountSummaryColumns = ref([
    {field: 'discount_summary', header: 'Discount Summary', width: '70', sortable: false},
    {field: 'discount_amount', header: 'Amt (RM)', width: '30', sortable: false},
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
};

const hasVoucherApplied = computed(() => {
    if (!order.value) return false;
    return (!!order.value.payment.discount_id && !!order.value.payment.voucher) || order.value.payment.applied_discounts?.length > 0;
})

const appliedDiscounts = computed(() => {
    let discountSummary = [];

    if (!!order.value.payment.discount_id && !!order.value.payment.voucher) {
        const voucherRate = order.value.payment.voucher.reward_type === 'Discount (Percentage)' ? `${order.value.payment.voucher.discount}%` : `RM ${order.value.payment.voucher.discount}`;
        discountSummary.push({
            discount_summary: `${voucherRate} Discount (Entry Reward for ${order.value.customer.rank.name})`,
            discount_amount: Number(order.value.payment.discount_amount ?? 0).toFixed(2)
        });
    }

    order.value.payment?.applied_discounts?.forEach((d) => {
        let discountedAmount = d.discount_type === 'amount'
            ? d.discount_rate
            : order.value.payment.total_amount * (d.discount_rate / 100);

        discountSummary.push({
            discount_summary: d.name,
            discount_amount: Number(discountedAmount ?? 0).toFixed(2)
        })
    });

    return discountSummary;
});

// const sstAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (6 / 100));
// })

// const serviceChargeAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (10 / 100)) ?? 0.00;
// })

// const totalEarnedPoints = computed(() => {
//     return order.value.order_items.filter((item) => item.status === 'Served').reduce((total, item) => total + item.point_earned, 0) ?? 0.00;
// })

const orderTableNames = computed(() => order.value.order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '-');

const generateECode = (receipt_no, merchant, secret) => {
    return md5(`${receipt_no}${merchant}${secret}`).toString();
}

function printReceipt() {
  window.print();
}

defineExpose({
    printReceipt
});

</script>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
    }
    .receipt-container {
        width: 80mm;
        font-size: 12px;
    }
    button {
        display: none;
    }
}
</style>

<template>
    <div class="w-full relative flex flex-col max-h-[calc(100dvh-6rem)] overflow-auto scrollbar-thin scrollbar-webkit gap-10">
        <div class="flex flex-col gap-[120px] pb-6">
            <WineBackgroundIllus1 class="absolute top-0 right-0 w-full z-0"/>
            <div class="relative z-10 flex flex-col pl-5 pr-6 pt-6 gap-y-8">
                <div class="flex flex-col gap-y-10 justify-center">
                    <div class="flex w-full items-start px-1">
                        <div class="flex flex-col w-10/12 justify-center items-start gap-y-1 pr-1">
                            <p class="text-primary-100 text-md font-medium self-stretch">{{ merchant?.merchant_name }}</p>
                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ merchant?.merchant_address_line1 }}, {{ merchant?.merchant_address_line2 }}</p>
                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ merchant?.postal_code }} {{ merchant?.area }}, {{ merchant?.state }}</p>

                            <p class="text-primary-25 text-sm font-normal self-stretch">{{ formatPhone(merchant?.merchant_contact) }}</p>
                        </div>
                        <img 
                            :src="merchant?.image ? merchant?.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="MerchantLogo" 
                            width="64" 
                            height="64" 
                            class="flex-shrink-0 w-2/12 rounded-full"
                        />
                    </div>
    
                    <div class="flex flex-col bg-primary-25 rounded-md items-center px-4 py-6">
                        <p class="text-primary-800 text-base font-medium">TOTAL SPENT</p>
                        <p class="text-primary-800 text-[28px] font-medium whitespace-nowrap !leading-none">RM <span class="text-[56px]">{{ order.payment?.grand_total ?? '0.00' }}</span></p>
                    </div>
                </div>
    
                <div class="flex flex-col gap-y-4 items-start self-stretch">
                    <p class="text-primary-950 text-md font-medium self-stretch">{{ dayjs(order.payment?.receipt_end_date).format('DD/MM/YYYY, HH:mm') ?? '00/00/0000, 00:00' }}</p>
                    <div class="flex gap-x-3 py-4 items-start justify-evenly self-stretch">
                        <div class="w-5/12 flex flex-col justify-between items-start border-r-2 border-primary-100 gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Receipt No.</p>
                            <p class="text-primary-950 text-sm font-medium break-all">{{ order.payment?.receipt_no ?? '-' }}</p>
                        </div>
                        <div class="w-2/12 flex flex-col justify-between items-start border-r-2 border-primary-100 gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Pax</p>
                            <p class="text-primary-950 text-sm font-medium">{{ order.payment?.pax ?? '-' }}</p>
                        </div>
                        <div class="w-5/12 flex flex-col justify-between items-start gap-y-2">
                            <p class="text-primary-950 text-xs font-light">Table No.</p>
                            <p class="text-primary-950 text-sm font-medium">{{ orderTableNames }}</p>
                        </div>
                    </div>
                </div>
    
                <Table 
                    :variant="'list'"
                    :rows="order.order_items?.filter((item) => item.status === 'Served' && item.item_qty > 0)"
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
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment?.total_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.bill_discounts && order.payment?.bill_discount_total > 0">
                        <p class="text-primary-950 text-md font-light">Bill Discount</p>
                        <p class="text-primary-950 text-md font-normal">- {{ parseFloat(order.payment?.bill_discount_total ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.voucher">
                        <p class="text-primary-950 text-md font-light">Voucher Discount {{ order.payment?.voucher.reward_type === 'Discount (Percentage)' ? `(${order.payment?.voucher.discount}%)` : `` }}</p>
                        <p class="text-primary-950 text-md font-normal">- {{ parseFloat(order.payment?.discount_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.service_tax_amount > 0">
                        <p class="text-primary-950 text-md font-light">Service Tax ({{ Math.round((order.payment?.service_tax_amount / order.payment?.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment?.service_tax_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.sst_amount > 0">
                        <p class="text-primary-950 text-md font-light">SST ({{ Math.round((order.payment?.sst_amount / order.payment?.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.payment?.sst_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-md font-light">Rounding</p>
                        <p class="text-primary-950 text-md font-normal">{{ Math.sign(order.payment?.rounding) === -1 ? '-' : '' }} {{ parseFloat(Math.abs(order.payment?.rounding ?? 0)).toFixed(2) }}</p>
                    </div>
                </div>
    
                <template v-if="order.payment?.point_history">
                    <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                    <div class="flex flex-col gap-4 items-center self-stretch">
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-md font-light">Points Earned</p>
                            <p class="text-primary-950 text-md font-normal">{{ order.payment?.point_history.amount }}</p>
                        </div>
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-md font-light">Points Balance</p>
                            <p class="text-primary-950 text-md font-normal">{{ order.payment?.point_history.new_balance }}</p>
                        </div>
                    </div>
                </template>
    
                <template v-if="hasVoucherApplied">
                    <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                    <Table 
                        :variant="'list'"
                        :rows="appliedDiscounts"
                        :columns="discountSummaryColumns"
                        :rowType="rowType"
                        :paginator="false"
                    >
                        <template #discount_summary="row">
                            <p class="text-grey-900 text-sm font-medium">
                                {{ row.discount_summary }}
                            </p>
                        </template>
                        <template #discount_amount="row">
                            <div class="flex items-start h-full text-grey-900 text-sm font-medium">- RM {{ row.discount_amount }}</div>
                        </template>
                    </Table>
                </template>
    
                <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                <div v-if="payout" class="flex items-center justify-center">
                    <!-- <img :src="QRCodeImage" alt="QRCodeImage" width="200" height="200"/> -->
                    <!-- generate qr -->
                    <QRCodeVue3 
                        :width="200"
                        :height="200"
                        :value="`${payout?.url}invoice?invoice_no=${order.payment?.receipt_no}&merchant_id=${payout?.merchant_id}&amount=${order.payment?.grand_total}&eCode=${generateECode(
                            order.payment?.receipt_no,
                            payout?.merchant_id,
                            payout?.api_key,
                        )}`"
                        :dotsOptions="{
                            type: 'square',
                            color: '#7e171b',
                        }"
                        :cornersSquareOptions="{ type: 'square', color: '#7e171b' }"
                        :cornersDotOptions="{ type: undefined, color: '#7e171b' }"
                    />
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
