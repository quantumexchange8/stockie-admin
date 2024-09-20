<script setup>
import dayjs from 'dayjs';
import { ref, computed } from 'vue'
import Table from '@/Components/Table.vue';
import { WineBackgroundIllus1 } from '@/Components/Icons/illus';
import MerchantLogo from "../../../../assets/images/MerchantLogo.svg";
import StockieLogo from "../../../../../public/favicon.ico";

const props = defineProps({
    errors: Object,
    order: Object,
});

const invoiceOrderItemsColumns = ref([
    {field: 'item_qty', header: 'Qtm', width: '10', sortable: false},
    {field: 'product_name', header: 'Item', width: '60', sortable: false},
    {field: 'amount', header: 'Amt (RM)', width: '30', sortable: false},
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
};

const sstAmount = computed(() => {
    return (parseFloat(props.order.amount ?? 0) * (6 / 100));
})

const serviceChargeAmount = computed(() => {
    return (parseFloat(props.order.amount ?? 0) * (10 / 100)) ?? 0.00;
})

const grandTotal = computed(() => {
    return (parseFloat(props.order.total_amount ?? 0) + sstAmount.value + serviceChargeAmount.value) ?? 0.00;
})

const totalEarnedPoints = computed(() => {
    return props.order.order_items.reduce((total, item) => total + item.point_earned, 0) ?? 0.00;
})
</script>

<template>
    <div class="w-full relative flex flex-col max-h-[calc(100dvh-6rem)] overflow-auto scrollbar-thin scrollbar-webkit gap-[120px]">
        <WineBackgroundIllus1 class="absolute top-0 right-0 w-full z-0"/>
        <div class="relative z-10 flex flex-col pl-5 pr-6 pt-6 gap-y-8">
            <div class="flex flex-col gap-y-10 justify-center">
                <div class="flex items-start px-1">
                    <div class="flex flex-col justify-center items-start gap-y-1 pr-1">
                        <p class="text-primary-100 text-md font-medium self-stretch">88BAR</p>
                        <p class="text-primary-25 text-sm font-normal self-stretch">32, Jalan Jejaka, Maluri, 55100 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur</p>
                        <p class="text-primary-25 text-sm font-normal self-stretch">+6011-2367 8790</p>
                    </div>
                    <img :src="MerchantLogo" alt="MerchantLogo" width="64" height="64" class="flex-shrink-0 rounded-full"/>
                </div>

                <div class="flex flex-col bg-primary-25 rounded-md items-center px-4 py-6">
                    <p class="text-primary-800 text-base font-medium">TOTAL SPENT</p>
                    <p class="text-primary-800 text-[28px] font-medium whitespace-nowrap !leading-none">RM <span class="text-[56px]">{{ grandTotal.toFixed(2) }}</span></p>
                </div>
            </div>

            <div class="flex flex-col gap-y-4 items-start self-stretch">
                <p class="text-primary-950 text-md font-medium self-stretch">{{ dayjs(order.created_at).format('DD/MM/YYYY, HH:mm') }}</p>
                <div class="flex gap-x-6 py-4 items-start self-stretch">
                    <div class="w-1/3 flex flex-col justify-between items-start border-r-2 border-primary-100">
                        <p class="text-primary-950 text-xs font-light">Order No.</p>
                        <p class="text-primary-950 text-md font-medium">#{{ order.order_no }}</p>
                    </div>
                    <div class="w-1/3 flex flex-col justify-between items-start border-r-2 border-primary-100">
                        <p class="text-primary-950 text-xs font-light">Pax</p>
                        <p class="text-primary-950 text-md font-medium">{{ order.pax }}</p>
                    </div>
                    <div class="w-1/3 flex flex-col justify-between items-start">
                        <p class="text-primary-950 text-xs font-light">Table No.</p>
                        <p class="text-primary-950 text-md font-medium">{{ order.order_table.table.table_no }}</p>
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
                <template #product_name="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.product.product_name }}</span>
                </template>
                <template #amount="row">
                    <span class="text-grey-900 text-sm font-medium">{{ parseFloat(row.amount).toFixed(2) }}</span>
                </template>
            </Table>

            <div class="flex flex-col gap-4 items-center self-stretch">
                <div class="flex items-start justify-between self-stretch">
                    <p class="text-primary-950 text-md font-light">Subtotal</p>
                    <p class="text-primary-950 text-md font-normal">{{ parseFloat(order.amount ?? 0).toFixed(2) }}</p>
                </div>
                <div class="flex items-start justify-between self-stretch">
                    <p class="text-primary-950 text-md font-light">Service Charge (10%)</p>
                    <p class="text-primary-950 text-md font-normal">{{ serviceChargeAmount.toFixed(2) }}</p>
                </div>
                <div class="flex items-start justify-between self-stretch">
                    <p class="text-primary-950 text-md font-light">SST (6%)</p>
                    <p class="text-primary-950 text-md font-normal">{{ sstAmount.toFixed(2) }}</p>
                </div>
            </div>

            <template v-if="order.customer">
                <div class="border-y-2 border-dashed border-primary-100 h-2"></div>
                <div class="flex flex-col gap-4 items-center self-stretch">
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-md font-light">Points Earned</p>
                        <p class="text-primary-950 text-md font-normal">{{ totalEarnedPoints }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-md font-light">Points Balance</p>
                        <p class="text-primary-950 text-md font-normal">{{ order.customer.point + totalEarnedPoints }}</p>
                    </div>
                </div>
            </template>
        </div>
        <div class="flex flex-col gap-2 py-6 rounded-md justify-center items-center bg-primary-900">
            <p class="text-primary-50 text-[28px] font-light">Thank you for your visit!</p>
            <div class="flex items-center text-white text-xs font-light gap-1">
                <p>Order invoice generated by</p>
                <img :src="StockieLogo" alt="StockieLogo" width="16" height="16" class="ml-1 mr-1 flex-shrink-0"/>
                <span class="font-extrabold">stockie</span>
            </div>
        </div>
    </div>
</template>
