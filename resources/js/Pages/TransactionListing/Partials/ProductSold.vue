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
        <div v-if="props.selectedVal.order" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
            <div v-for="item in props.selectedVal.order.filter_order_items" :key="item.id" >
                <div class="flex flex-col gap-2 w-full">
                    <div class="flex items-center gap-3">
                        <div class="max-w-[324px] w-full">{{ item.product.product_name }}</div>
                        <div class="max-w-[30px] w-full text-right">{{ item.item_qty }}</div>
                        <div class="max-w-[102px] w-full text-right">RM{{ formatAmount(item.amount_before_discount) }}</div>
                    </div>
                    <div v-if="item.discount_amount > 0" class="flex items-center gap-3">
                        <div class="max-w-[324px] w-full list-disc"><ul class="list-disc list-inside ml-3"><li>{{ $t('public.special_discount') }}</li></ul></div>
                        <div class="max-w-[30px] w-full text-right"></div>
                        <div class="max-w-[102px] w-full text-right">-{{ item.discount_amount }}</div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>


</template>