<script setup>
import { ref, watch } from 'vue';
import { FilterMatchMode } from 'primevue/api';
import Table from '@/Components/Table.vue';
import DateInput from '@/Components/Date.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import dayjs from 'dayjs';

const props = defineProps({
    dateFilter: Array,
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
    actions: {
        type: Object,
        default: () => {},
    },
    totalPages: {
        type: Number,
        required: true,
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
});

const date_filter = ref(props.dateFilter); 
const searchQuery = ref('');    
const rows = ref(props.rows);

const emit = defineEmits(["applyDateFilter"]);

watch(() => date_filter.value, (newValue) => {
    emit('applyDateFilter', newValue);
})

watch(() => props.rows, (newValue) => {
    rows.value = newValue;
}, { immediate: true })

watch(() => searchQuery.value, (newValue) => {
    if(newValue === '') {
        rows.value = props.rows;
        return;
    }
    const query = newValue.toLowerCase();

    rows.value = props.rows.filter(row => {
        const redeemedItem = row.redeemable_item.product_name.toLowerCase();
        const redeemedBy = row.handled_by.name.toLowerCase();

        return  redeemedItem.includes(query) ||
                redeemedBy.includes(query);
    });
}, { immediate: true });
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
            <SearchBar
                placeholder="Search"
                :showFilter="false"
                v-model="searchQuery"
                class="col-span-full md:col-span-7"
            />
            <DateInput
                :inputName="'date_filter'"
                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                :range="true"
                class="col-span-full md:col-span-5"
                v-model="date_filter"
            />
        </div>

        <Table 
            :variant="'list'"
            :rows="rows"
            :totalPages="totalPages"
            :columns="columns"
            :rowsPerPage="rowsPerPage"
            :rowType="rowType"
            :actions="actions"
            :searchFilter="true"
            minWidth="min-w-[810px]"
        >
            <template #empty>
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </template>
            <template #redemption_date="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ dayjs(row.redemption_date).format('YYYY/MM/DD') }}</span>
            </template>
            <template #qty="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">x {{ row.qty }}</span>
            </template>
            <template #redeemed_qty="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ row.qty }}</span>
            </template>
            <template #redeemable_item="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ row.redeemable_item.product_name }}</span>
            </template>
            <template #handled_by="row">
                <span class="text-grey-900 text-sm font-medium whitespace-nowrap">{{ row.handled_by.name }}</span>
            </template>
        </Table>
    </div>
</template>
