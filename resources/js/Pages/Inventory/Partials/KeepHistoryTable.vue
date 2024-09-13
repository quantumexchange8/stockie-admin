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

    // Step 2: Group rows by inventory_item within each date group and merge `in` and `out` values
    const result = Object.keys(dateGroups).map(date => {
        const inventoryGroups = {};

        dateGroups[date].forEach(row => {
            const itemName = row.inventory_item;

            if (!inventoryGroups[itemName]) {
                // Initialize the grouped object with the first row's values
                inventoryGroups[itemName] = {
                    ...row,
                    min_created_at: row.created_at,
                    max_created_at: row.created_at,
                };
            } else {
                // Sum the `in` and `out` values if the item already exists
                inventoryGroups[itemName].in += row.in;
                inventoryGroups[itemName].out += row.out;

                // Update the old_stock and current_stock based on created_at dates
                if (dayjs(row.created_at).isBefore(inventoryGroups[itemName].min_created_at)) {
                    inventoryGroups[itemName].old_stock = row.old_stock;
                    inventoryGroups[itemName].min_created_at = row.created_at;
                }

                if (dayjs(row.created_at).isAfter(inventoryGroups[itemName].max_created_at)) {
                    inventoryGroups[itemName].current_stock = row.current_stock;
                    inventoryGroups[itemName].max_created_at = row.created_at;
                }
            }
        });

        // Convert inventoryGroups to an array
        const mergedRows = Object.values(inventoryGroups).map(item => {
            // Remove temporary min_created_at and max_created_at properties
            delete item.min_created_at;
            delete item.max_created_at;
            
            return item;
        });

        const filteredRows = mergedRows.filter(row => {
            if (props.category === 'All') return true;
            if (props.category === 'In' && row.in > 0) return true;
            if (props.category === 'Out' && row.out > 0) return true;
            return false;
        });

        return {
            date,
            rows: filteredRows,
        };
    });

    return result;
});

</script>

<template>
    <div class="flex flex-col">
        <div class="flex flex-col gap-4 justify-center">
            <Table 
                :variant="'list'"
                :rows="group.rows"
                :paginator="false"
                :columns="columns"
                :rowType="rowType"
                :searchFilter="true"
                :filters="filters"
                v-for="(group, index) in rowGroupedByDates" 
                :key="index"
            >
                <template #header>
                    <div class="flex items-center justify-between gap-2.5 rounded-sm bg-grey-50 border-t border-grey-200 py-1 px-2.5">
                        <span class="text-grey-900 text-sm font-medium">{{ group.date }}</span>
                    </div>
                </template>
                <template #groupheader="row">
                    <div class="flex justify-start items-center w-full">
                        <div class="flex items-center gap-3">
                            <span class="w-[60px] h-[60px] flex-shrink-0 rounded-full bg-primary-700"></span>
                            <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ row.inventory.name }}</span>
                        </div>
                    </div>
                </template>
                <template #groupfooter="row">
                    <div></div>
                </template>
                <!-- Only 'list' variant has individual slots while 'grid' variant has an 'item-body' slot -->
                <template #in="row">
                    <span class="text-green-600 text-sm font-medium !whitespace-nowrap" v-if="row.in">+ {{ row.in }}</span>
                    <span class="text-grey-300 text-sm font-medium whitespace-nowrap" v-else>-</span>
                </template>
                <template #out="row">
                    <span class="text-primary-600 text-sm font-medium !whitespace-nowrap" v-if="row.out">- {{ row.out }}</span>
                    <span class="text-grey-300 text-sm font-medium whitespace-nowrap" v-else>-</span>
                </template>
                <template #current_stock="row">
                    <span class="text-grey-900 text-sm font-medium">
                        {{ row.current_stock }}
                        <span class="text-primary-600 text-sm font-medium" v-if="category === 'In' && row.out > 0"> (- {{ row.out }})</span>
                        <span class="text-green-600 text-sm font-medium" v-else-if="category === 'Out' && row.in > 0"> (+ {{ row.in }})</span>
                    </span>
                </template>
            </Table>
        </div>
    </div>
</template>
