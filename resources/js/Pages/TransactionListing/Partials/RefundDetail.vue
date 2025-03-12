<script setup>
import { transactionFormat } from '@/Composables';


const props = defineProps({
    selectedVal: Object,
});

const { formatAmount } = transactionFormat();

</script>

<template>

    <div class="flex flex-col gap-1 bg-[#FCFCFC] rounded-sm p-3">
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">Sub-total</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.subtotal_refund_amount) }}</div>
        </div>
        <div v-if="props.selectedVal.refund_sst > 0" class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">SST </div>
            <div class="text-gray-900 text-base text-left w-full">
                <span>RM {{ props.selectedVal.refund_sst }}</span>
            </div>
        </div>
        <div v-if="props.selectedVal.refund_service_tax" class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">Service Tax</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.refund_service_tax) }}</div>
        </div>
        <div v-if="props.selectedVal.refund_rounding" class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">Rounding</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.refund_rounding) }}</div>
        </div>
    </div>

</template>