<script setup>
import { transactionFormat } from '@/Composables';


const props = defineProps({
    selectedVal: Object,
});

const { formatDateTime } = transactionFormat();

</script>

<template>

    <div class="grid grid-cols-2 gap-1 grid-rows-2 py-2 px-3 bg-grey-25">
        <div class="text-grey-900 text-base">MyInvois Status</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ selectedVal.status }}</div>

        <div class="text-grey-900 text-base">Submission UUID</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ selectedVal.submitted_uuid ? selectedVal.submitted_uuid : '-' }}</div>

        <div class="text-grey-900 text-base">UUID</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ selectedVal.uuid ? selectedVal.uuid : '-' }}</div>

        <div class="text-grey-900 text-base">Issued at</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ formatDateTime(selectedVal.created_at) }}</div>

        <div class="text-grey-900 text-base">Validated at</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ formatDateTime(selectedVal.updated_at) }}</div>
    </div>

</template>