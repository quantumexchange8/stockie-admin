<script setup>
import dayjs from 'dayjs';
import { computed } from 'vue'
import Table from '@/Components/Table.vue'

const props = defineProps({
    errors: Object,
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    rowType: {
        type: Object,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
})
</script>

<template>
    <div class="flex flex-col w-full">
        <div class="flex flex-col gap-4 justify-center">
            <Table 
                :variant="'list'"
                :rows="rows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :rowType="rowType"
                minWidth="min-w-[465px]"
            >
                <template #date="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.created_at).format('DD/MM/YYYY') }}</span>
                </template>
                <template #time="row">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(row.created_at).format('hh:mm A') }}</span>
                </template>
                <template #total_price="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ row.total_price }}</span>
                </template>
            </Table>
        </div>
    </div>
</template>
