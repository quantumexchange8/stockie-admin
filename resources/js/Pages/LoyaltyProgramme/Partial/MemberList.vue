<script setup>
import Table from "@/Components/Table.vue";
import { defineProps } from "vue";
import Button from "@/Components/Button.vue";
import dayjs from "dayjs";

const props = defineProps({
    id: String,
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowType: {
        type: Object,
        default: () => {},
    },
    actions: {
        type: Object,
        default: () => {},
    },
    totalPages: Number,
    rowsPerPage: Number,
});

const formatAmount = (num) => {
    var str = num.toString().split('.');

    if (str[0].length >= 4) {
        str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1,');
    }

    if (str[1] && str[1].length >= 5) {
        str[1] = str[1].replace(/(\d{3})/g, '$1 ');
    }

    return str.join('.');
}
</script>

<template>
    <div class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100 overflow-y-auto">
        <div class="flex gap-[10px]">
            <span class="w-full text-primary-900 text-md font-medium whitespace-nowrap">
                Member List ({{ rows.length }})
            </span>
            <div class="w-fit">
                <Button
                    variant="tertiary"
                    :type="'button'"
                    :size="'md'"
                    :iconPosition="'right'"
                >
                    Export
                    <template #icon>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 16 16"
                            fill="none"
                        >
                            <path
                                d="M14 10V10.8C14 11.9201 14 12.4802 13.782 12.908C13.5903 13.2843 13.2843 13.5903 12.908 13.782C12.4802 14 11.9201 14 10.8 14H5.2C4.07989 14 3.51984 14 3.09202 13.782C2.71569 13.5903 2.40973 13.2843 2.21799 12.908C2 12.4802 2 11.9201 2 10.8V10M11.3333 5.33333L8 2M8 2L4.66667 5.33333M8 2V10"
                                stroke="#7E171B"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </template>
                </Button>
            </div>
        </div>
        <Table
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :actions="actions"
            :rowType="rowType"
            minWidth="min-w-[470px]"
        >
            <template #full_name="row">
                <div class="flex gap-[10px]">
                    <div class="w-[32px] h-[32px] rounded-full bg-gray-500"></div>
                    <span class="">{{ row.full_name }}</span>
                </div>
            </template>
            <template #created_at="row">
                <span class=""> {{ dayjs(row.created_at).format('DD/MM/YYYY') }}</span>
            </template>
            <template #total_spend="row">
                <span class=""> RM {{ formatAmount(row.total_spend) }}</span>
            </template>
        </Table>
    </div>
</template>
