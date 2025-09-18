<script setup>
import { CardIcon, CashIcon, EWalletIcon } from '@/Components/Icons/solid';
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    selectedVal: Object,
});

const { formatAmount } = transactionFormat();

const getTranslatedMethod = (method) => {
    switch (method) {
        case 'Cash': return wTrans('public.cash');
        case 'Card': return wTrans('public.card');
        case 'E-Wallet': return wTrans('public.e_wallet');
    }
};

</script>

<template>

    <div class="flex flex-col bg-[#FCFCFC]">
        <div class="flex items-center py-2 px-3 gap-3 bg-gray-100">
            <div class="max-w-[193px] w-full text-sm text-gray-950 font-semibold">{{ $t('public.method') }}</div>
            <div class="max-w-[102px] w-full text-sm text-gray-950 font-semibold text-right">{{ $t('public.amount') }}</div>
            <div class="max-w-[151px] w-full text-sm text-gray-950 font-semibold text-right">{{ $t('public.date_time') }}</div>
        </div>
        <div v-if="props.selectedVal.order" class="flex flex-col p-3 gap-4 max-h-64 overflow-y-scroll">
            <template v-for="(item, index) in props.selectedVal.payment_methods" :key="item.id" >
                <hr v-if="index > 0" class="w-full h-[1px] bg-grey-100">
                <div class="flex flex-col gap-2 w-full">
                    <div class="flex items-center gap-3">
                        <div class="max-w-[193px] w-full flex gap-x-2 items-center">
                            <CashIcon v-if="item.payment_method === 'Cash'" />
                            <CardIcon v-else-if="item.payment_method === 'Card'" />
                            <EWalletIcon v-else-if="item.payment_method === 'E-Wallet'" />
                            {{ getTranslatedMethod(item.payment_method) }}
                        </div>
                        <div class="max-w-[102px] w-full text-right">RM {{ formatAmount(item.amount) }}</div>
                        <div class="max-w-[151px] w-full text-right">{{ dayjs(item.created_at).format('DD/MM/YYYY, HH:mm') }}</div>
                    </div>
                </div>
            </template>
        </div>
    </div>

</template>