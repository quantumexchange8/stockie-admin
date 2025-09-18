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
            <div class="max-w-[324px] w-full text-sm text-gray-950 font-semibold">{{ $t('public.product_name') }}</div>
            <div class="max-w-[30px] w-full text-sm text-gray-950 font-semibold">{{ $t('public.qty') }}</div>
            <div class="max-w-[102px] w-full text-sm text-gray-950 font-semibold text-right">{{ $t('public.price') }}</div>
        </div>
        <div v-if="props.selectedVal.refund_details" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
            <div v-for="item in props.selectedVal.refund_details" :key="item.id" >
                <div class="flex flex-col gap-2 w-full">
                    <div class="flex items-center gap-3">
                        <div class="max-w-[324px] w-full">{{ item.filter_order_items.product.product_name }}</div>
                        <div class="max-w-[30px] w-full text-right">{{ item.refund_qty }}</div>
                        <div class="max-w-[102px] w-full text-right">RM{{ formatAmount(item.refund_amount) }}</div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
</template>