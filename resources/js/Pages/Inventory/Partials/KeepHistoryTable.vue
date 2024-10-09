<script setup>
import dayjs from 'dayjs';
import { computed } from 'vue'
import Table from '@/Components/Table.vue'
import { Link } from '@inertiajs/vue3';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    errors: Object,
    columns: Array,
    rows: Array,
    rowType: Object,
    filters: {
        type: Object,
        default: () => {},
    },
    category: {
        type: String,
        default: 'All',
    },
})

// Group the rows by the unique created date
const rowGroupedByDates = computed(() => {
    const dateGroups = {};

    props.rows.forEach(row => {
        const formattedDate = dayjs(row.created_at).format('DD/MM/YYYY');
        
        // If the date key doesn't exist in the object, create an array for it
        if (!dateGroups[formattedDate]) {
            dateGroups[formattedDate] = [];
        }

        // Push the row into the corresponding date group
        dateGroups[formattedDate].push(row);
    });


    // Step 2: Convert the dateGroups object into an array format with the grouped rows
    const result = Object.keys(dateGroups).map(date => ({
        date,               // The formatted date
        rows: dateGroups[date] // The rows corresponding to that date
    }));

    return result;
});

const hasRow = (groups) => {
    const totalRows = groups.reduce((total, group) => total + group.rows.length, 0)

    return totalRows > 0;
}

</script>

<template>
    <div class="flex flex-col">
        <div class="flex flex-col gap-4 justify-center items-center">
            <template v-for="group in rowGroupedByDates" v-if="hasRow(rowGroupedByDates)">
                <Table 
                    :variant="'list'"
                    :rows="group.rows"
                    :paginator="false"
                    :columns="columns"
                    :rowType="rowType"
                    :searchFilter="true"
                    :filters="filters"
                >
                    <template #empty>
                        <UndetectableIllus class="w-44 h-44"/>
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </template>
                    <template #header>
                        <div class="flex items-center justify-between gap-2.5 rounded-sm bg-grey-50 border-t border-grey-200 py-1 px-2.5">
                            <span class="text-grey-900 text-sm font-medium">{{ group.date }}</span>
                        </div>
                    </template>
                    <template #quantity="row">
                        {{ parseFloat(row.qty) > parseFloat(row.cm) ? `x ${row.qty}` : `${row.cm} cm` }}
                    </template>
                    <template #keep_date="row">
                        {{ dayjs(row.keep_date).format('DD/MM/YYYY') }}
                    </template>
                    <template #keep_for="row">
                        <Link :href="route('customer')" class="line-clamp-1 underline text-ellipsis text-sm font-semibold text-primary-900 hover:text-primary-700">
                            {{ row.keep_item?.customer?.full_name ?? 0 }}
                        </Link>
                    </template>
                </Table>
            </template>
            <template v-else>
                <UndetectableIllus class="w-44 h-44"/>
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </template>
        </div>
    </div>
</template>
