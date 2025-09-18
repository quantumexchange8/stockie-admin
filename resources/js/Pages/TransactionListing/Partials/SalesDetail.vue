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
            <div class="text-gray-900 text-base text-left w-full">{{ $t('public.sub_total') }}</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.total_amount) }}</div>
        </div>
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">{{ $t('public.bill_discount') }}</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.bill_discount_total) }}</div>
        </div>
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">{{ $t('public.voucher_discount') }}</div>
            <div v-if="props.selectedVal.voucher" class="text-gray-900 text-base text-left w-full">
                <span v-if="props.selectedVal.voucher.reward_type === 'Discount (Amount)'">(RM{{ props.selectedVal.voucher.discount }})</span>
                <span v-if="props.selectedVal.voucher.reward_type === 'Discount (Percentage)'">({{ props.selectedVal.voucher.discount }}%)</span>
            </div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.discount_amount) }}</div>
        </div>
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">SST 
                <span v-if="props.selectedVal.voucher">
                    ({{ Math.round((props.selectedVal.sst_amount / props.selectedVal.total_amount) * 100) }}%)
                </span>
            </div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.sst_amount) }}</div>
        </div>
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">{{ $t('public.service_tax') }} 
                <span v-if="props.selectedVal.voucher">
                    ({{ Math.round((props.selectedVal.service_tax_amount / props.selectedVal.total_amount) * 100) }}%)
                </span>
            </div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.service_tax_amount) }}</div>
        </div>
        <div class="flex items-center w-full">
            <div class="text-gray-900 text-base text-left w-full">{{ $t('public.rounding') }}</div>
            <div class="text-gray-900 text-base font-bold text-right w-full">RM {{ formatAmount(props.selectedVal.rounding) }}</div>
        </div>
    </div>
</template>