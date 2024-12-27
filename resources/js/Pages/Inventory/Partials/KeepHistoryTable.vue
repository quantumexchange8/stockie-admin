<script setup>
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue'
import Table from '@/Components/Table.vue'
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import ViewHistory from '@/Pages/Customer/Partials/ViewHistory.vue';

const props = defineProps({
    rows: Array,
    rowType: Object,
    tab: String,
    selectedTab: Number,
})

// // Group the rows by the unique created date
// const rowGroupedByDates = computed(() => {
//     const dateGroups = {};

//     props.rows.forEach(row => {
//         const formattedDate = dayjs(row.created_at).format('DD/MM/YYYY');
        
//         // If the date key doesn't exist in the object, create an array for it
//         if (!dateGroups[formattedDate]) {
//             dateGroups[formattedDate] = [];
//         }

//         // Push the row into the corresponding date group
//         dateGroups[formattedDate].push(row);
//     });


//     // Step 2: Convert the dateGroups object into an array format with the grouped rows
//     const result = Object.keys(dateGroups).map(date => ({
//         date,               // The formatted date
//         rows: dateGroups[date] // The rows corresponding to that date
//     }));

//     return result;
// });

// const hasRow = (groups) => {
//     const totalRows = groups.reduce((total, group) => total + group.rows.length, 0)

//     return totalRows > 0;
// }
// console.log(props.rows);

const activeColumns = ref([
    {field: 'keep_date', header: 'Kept Date', width: '20.2', sortable: true},
    {field: 'item_name', header: 'Item Name', width: '29.3', sortable: true},
    {field: 'qty', header: 'Qty', width: '11.9', sortable: true},
    {field: 'keep_item.expired_to', header: 'Expire on', width: '20.1', sortable: true},
    {field: 'keep_item.customer.full_name', header: 'Keep For', width: '18.5', sortable: true},
]);

const servedColumns = ref([
    { field: 'updated_at', header: 'Served Date', width: '24.2', sortable: true},
    { field: 'item_name', header: 'Item', width: '26.2', sortable: true},
    { field: 'qty', header: 'Qty', width: '8.2', sortable: true},
    { field: 'keep_item.customer.full_name', header: 'Customer', width: '23.2', sortable: true},
    { field: 'keep_item.waiter.full_name', header: 'Served By', width: '23.2', sortable: true},
])

const expiredColumns = ref([
    { field: 'keep_item.expired_to', header: 'Expired Date', width: '16.2', sortable: true},
    { field: 'item_name', header: 'Item', width: '39.2', sortable: true},
    { field: 'qty', header: 'Qty', width: '8.2', sortable: true},
    { field: 'keep_item.customer.full_name', header: 'Customer', width: '39.4', sortable: true},
])

const rowsPerPage = ref(12);
const isDrawerOpen = ref(false);
const selectedCustomer = ref(null);

const openDrawer = (row) => {
    isDrawerOpen.value = true;
    selectedCustomer.value = row;
}

const closeDrawer = () => {
    isDrawerOpen.value = false;
}

const totalPages = computed(() => {
    return Math.ceil(props.rows.length / rowsPerPage.value);
})

const qtyColumn = (row) => {
    return parseInt(row.qty) === 0 ? `${row.cm} cm` : `x ${row.qty}`;
}

</script>

<template>
    <!-- <div class="flex flex-col">
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
    </div> -->

    <div class="flex flex-col gap-8">
        <Table
            :variant="'list'"
            :rows="rows"
            :columns="servedColumns"
            :rowType="props.rowType"
            :rowsPerPage="rowsPerPage"
            :paginator="true"
            :totalPages="totalPages"
            @onRowClick="openDrawer($event.data.keep_item.customer)"
            v-if="props.tab === 'served-returned'"
        >
            <template #updated_at="rows">
                <span class="text-grey-900 text-sm font-medium">{{ dayjs(rows.updated_at).format('DD/MM/YYYY') }}</span>
            </template>
            <template #item_name="rows">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-grey-900 text-sm font-semibold">{{ rows.item_name }}</span>
            </template>
            <template #qty="rows">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ qtyColumn(rows) }}</span>
            </template>
            <template #keep_item.customer.full_name="rows">
                <div class="flex items-center gap-2 flex-[1_0_0]">
                    <img 
                        :src="rows.customer_image ? rows.customer_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="CustomerImage"
                        class="size-4 object-contain rounded-full"
                    >
                    <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis text-sm font-semibold underline underline-offset-auto decoration-solid decoration-auto">
                        {{ rows.keep_item.customer.full_name }}
                    </span>
                </div>
            </template>
            <template #keep_item.waiter.full_name="rows">
                <div class="flex items-center gap-2 flex-[1_0_0]">
                    <img 
                        :src="rows.waiter_image ? rows.waiter_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="WaiterImage"
                        class="size-4 object-contain rounded-full"
                    >
                    <span class="line-clamp-1 flex-[1_0_0] text-grey-900 text-ellipsis text-sm font-semibold">{{ rows.keep_item.waiter.full_name }}</span>
                </div>
            </template>
        </Table>

        <Table
            :variant="'list'"
            :rows="rows"
            :columns="activeColumns"
            :rowType="props.rowType"
            :rowsPerPage="rowsPerPage"
            :paginator="true"
            :totalPages="totalPages"
            @onRowClick="openDrawer($event.data.keep_item.customer)"
            v-else-if="props.tab === 'active'"
        >
            <template #keep_date="rows">
                <span class="text-grey-900 text-sm font-medium">{{ dayjs(rows.keep_date).format('DD/MM/YYYY') }}</span>
            </template>
            <template #item_name="rows">
                <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-grey-900 text-sm font-semibold">{{ rows.item_name }}</span>
            </template>
            <template #qty="rows">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ qtyColumn(rows) }}</span>
            </template>
            <template #keep_item.expired_to="rows">
                <span class="text-grey-900 text-sm font-medium">{{ rows.keep_item.expired_to ? dayjs(rows.keep_item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
            </template>
            <template #keep_item.customer.full_name="rows">
                <div class="flex items-center gap-2">
                    <img 
                        :src="rows.customer_image ? rows.customer_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="CustomerImage"
                        class="size-4 object-contain rounded-full"
                    >
                    <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis text-sm font-semibold underline underline-offset-auto decoration-solid decoration-auto">
                        {{ rows.keep_item.customer.full_name }}
                    </span>
                </div>
            </template>
        </Table>

        <Table
            :variant="'list'"
            :rows="rows"
            :columns="expiredColumns"
            :rowsPerPage="rowsPerPage"
            :rowType="props.rowType"
            :paginator="true"
            :totalPages="totalPages"
            @onRowClick="openDrawer($event.data.keep_item.customer)"
            v-else-if="props.tab === 'expired'"
        >
            <template #keep_item.expired_to="rows">
                <span class="text-grey-900 text-sm font-medium">{{ rows.keep_item.expired_to ? dayjs(rows.keep_item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
            </template>
            <template #item_name="rows">
                <span class="line-clamp-1 text-ellipsis text-grey-900 text-sm font-semibold">{{ rows.item_name }}</span>
            </template>
            <template #qty="rows">
                <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ parseFloat(rows.qty) > parseFloat(rows.cm) ? `x ${rows.qty}` : `${rows.cm} cm` }}</span>
            </template>
            <template #keep_item.customer.full_name="rows">
                <span class="line-clamp-1 flex-[1_0_0] text-primary-900 text-ellipsis text-sm font-semibold underline underline-offset-auto decoration-solid decoration-auto">
                    {{ rows.keep_item.customer.full_name }}
                </span>
            </template>
        </Table>
    </div>

    <!-- customer keep item drawer -->
     <RightDrawer
        :title="''"
        :header="'Customer Detail'"
        :show="isDrawerOpen"
        @close="closeDrawer"
     >
        <ViewHistory 
            :customer="selectedCustomer" 
            :selectedTab="props.selectedTab"
        />
     </RightDrawer>

</template>
