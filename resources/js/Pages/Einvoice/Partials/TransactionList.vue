<script setup>
import { transactionFormat } from '@/Composables';
import { computed } from 'vue';


const props = defineProps({
    selectedVal: Object,
});

const { formatAmount, formatDate } = transactionFormat();

const total_amount = computed(() => {
    return props.selectedVal?.invoice_child?.reduce((sum, invoice) => {
        return sum + (Number(invoice.grand_total) || 0);
    }, 0).toFixed(2); // Optional: format to 2 decimals
});

</script>

<template>

<div class="flex flex-col bg-[#FCFCFC]">
    <div class="flex items-center py-2 px-3 gap-3 bg-gray-100">
        <div class="max-w-[105px] w-full">{{ $t('public.date') }}</div>
        <div class="max-w-[244px] w-full">{{ $t('public.transaction_no') }}</div>
        <div class="max-w-[102px] w-full text-right">{{ $t('public.amount') }}</div>
    </div>
    <div v-if="selectedVal.invoice_child.length > 0" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
        <div v-for="invoice in selectedVal.invoice_child">
            <div class="flex flex-col gap-2 w-full">
                <div class="flex items-center gap-3">
                    <div class="max-w-[105px] w-full">{{ formatDate(invoice.receipt_end_date) }}</div>
                    <div class="max-w-[244px] w-full">{{ invoice.receipt_no }}</div>
                    <div class="max-w-[102px] w-full text-right">RM {{ formatAmount(invoice.grand_total) }}</div>
                </div>
            </div>
        </div>
        <div class="flex justify-between items-center text-grey-950 text-sm font-semibold">
            <div class="flex items-center gap-3">
                <span>{{ $t('public.einvoice.transaction_total') }}</span>
                <span>{{ selectedVal.invoice_child.length }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span>{{ $t('public.einvoice.amount_total') }}</span>
                <span>RM {{ total_amount  }}</span>
            </div>
        </div>
    </div>

    <div v-if="selectedVal.invoice_child.length == 0" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
        <div class="flex flex-col gap-2 w-full">
            <div class="flex items-center gap-3">
                <div class="max-w-[105px] w-full">{{ formatDate(selectedVal.invoice_no.receipt_end_date) }}</div>
                <div class="max-w-[244px] w-full">{{ selectedVal.c_invoice_no }}</div>
                <div class="max-w-[102px] w-full text-right">RM {{ formatAmount(selectedVal.c_total_amount) }}</div>
            </div>
        </div>
    </div>
</div>
    
    
</template>