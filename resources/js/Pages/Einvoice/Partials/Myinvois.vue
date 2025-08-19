<script setup>
import { ClipboardIcon } from '@/Components/Icons/solid';
import { transactionFormat } from '@/Composables';


const props = defineProps({
    selectedVal: Object,
});

const { formatDateTime } = transactionFormat();

const copyToClipboard = (field) => {
    // Get the text to be copied
    var copyText = field === 'submission_uuid'
        ? props.selectedVal.submitted_uuid 
            ? props.selectedVal.submitted_uuid 
            : '-'
        : props.selectedVal.uuid 
            ? props.selectedVal.uuid 
            : '-';

    // Write to clipboard
    navigator.clipboard.writeText(copyText);
}

</script>

<template>

    <div class="grid grid-cols-2 gap-1 grid-rows-2 py-2 px-3 bg-grey-25">
        <div class="text-grey-900 text-base">MyInvois Status</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ selectedVal.status }}</div>

        <div class="text-grey-900 text-base">Submission UUID</div>
        <div class="flex justify-end gap-x-2 items-center">
            <p class="text-grey-900 text-base font-bold text-right truncate w-32">
                {{ selectedVal.submitted_uuid ? selectedVal.submitted_uuid : '-' }}
            </p>
            <div class="cursor-pointer" @click="copyToClipboard('submission_uuid')">
                <ClipboardIcon class="w-4 h-4 flex-shrink-0" />
            </div>
        </div>

        <div class="text-grey-900 text-base">UUID</div>
        <div class="flex justify-end gap-x-2 items-center">
            <p class="text-grey-900 text-base font-bold text-right truncate w-32">
                {{ selectedVal.uuid ? selectedVal.uuid : '-' }}
            </p>
            <div class="cursor-pointer" @click="copyToClipboard('uuid')">
                <ClipboardIcon class="w-4 h-4 flex-shrink-0" />
            </div>
        </div>

        <div class="text-grey-900 text-base">Issued at</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ formatDateTime(selectedVal.created_at) }}</div>

        <div class="text-grey-900 text-base">Validated at</div>
        <div class="text-grey-900 text-base font-bold text-right">{{ formatDateTime(selectedVal.updated_at) }}</div>
    </div>

</template>