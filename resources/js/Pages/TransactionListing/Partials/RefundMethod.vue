<script setup>
import { transactionFormat } from '@/Composables';

const props = defineProps({
    selectedVal: Object,
});

const { formatAmount } = transactionFormat();

</script>

<template>

    <div class="flex flex-col bg-[#FCFCFC]">
        <div class="flex items-center py-2 px-3 gap-3 bg-gray-100">
            <div class="max-w-[345px] text-sm text-gray-950 font-semibold w-full">Refund Method</div>
            <div class="max-w-[118px] text-sm text-gray-950 font-semibold w-full text-right">Refund Amount</div>
        </div>
        <div v-if="props.selectedVal" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
            <div class="flex flex-col gap-2 w-full">
                <div class="flex items-center gap-3">
                    <div class="max-w-[345px] text-base text-gray-950 w-full truncate">{{ props.selectedVal.refund_method }}</div>
                    <div class="max-w-[118px] text-base text-gray-950 w-full text-right truncate">RM{{ formatAmount(props.selectedVal.total_refund_amount) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>