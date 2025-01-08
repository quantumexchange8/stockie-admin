<script setup>
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue'
import Table from '@/Components/Table.vue'
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import ViewHistory from '@/Pages/Customer/Partials/ViewHistory.vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import DateInput from '@/Components/Date.vue';
import Button from '@/Components/Button.vue';
import { useCustomToast } from '@/Composables';

const props = defineProps({
    rows: Array,
    rowType: Object,
    tab: String,
    selectedTab: Number,
})

const emit = defineEmits(['getKeepHistories']);

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
    { field: 'keep_item.expired_to', header: 'Expired Date', width: '24', sortable: true},
    { field: 'item_name', header: 'Item', width: '23', sortable: true},
    { field: 'qty', header: 'Qty', width: '10', sortable: true},
    { field: 'keep_item.customer.full_name', header: 'Customer', width: '23', sortable: true},
    { field: 'action', header: 'Action', width: '20', sortable: true},
])

const rowsPerPage = ref(12);
const isDrawerOpen = ref(false);
const selectedCustomer = ref(null);
const isReactivateOpen = ref(false);
const initialExpiry = ref('');
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    id: '',
    expiry_date: '',
    expiry_date_object: '',
})

const { showMessage } = useCustomToast();

const reactivateExpiredItems = () => {
    try {
        form.post(route('reactivateExpiredItems'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeModal('leave');
                showMessage({ 
                    severity: 'success',
                    summary: 'Kept item has been reactivated successfully',
                    detail: "You’ve extended the expiration date for selected kept item.",
                });
                form.reset();
                emit('getKeepHistories');
            },
        })
    } catch (error) {
        console.error(error);
    }
}

const openModal = (row) => {
    form.id = row.id;
    form.expiry_date = row.keep_item.expired_to ? dayjs(row.keep_item.expired_to).format('DD/MM/YYYY') : '';
    form.expiry_date_object = row.keep_item.expired_to ? dayjs(row.keep_item.expired_to).format('MM/DD/YYYY') : '';
    initialExpiry.value = form.expiry_date;

    isReactivateOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(form.expiry_date !== initialExpiry.value) isUnsavedChangesOpen.value = true;
            else isReactivateOpen.value = false; 
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        };
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isReactivateOpen.value = false;
            form.reset();
            break;
        }
    }
}

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

const isLatestRecord = (keep_item_id, id) => {
    return id === (props.rows.filter((row) => row.keep_item_id === keep_item_id))[0].id;
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
                <span class="text-grey-900 text-sm font-medium">{{ rows.keep_date ? dayjs(rows.keep_date).format('DD/MM/YYYY') : '-' }}</span>
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
            <template #action="rows">
                <span class="line-clamp-1 overflow-visible flex-[1_0_0] text-ellipsis text-sm font-semibold decoration-solid decoration-auto"
                    :class="isLatestRecord(rows.keep_item.id, rows.id) ? 'text-primary-900 cursor-pointer underline underline-offset-auto' : 'text-grey-200 cursor-not-allowed'"
                    @click.stop="openModal(rows)"
                >
                    Reactivate
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

     <!-- Extend expiration date -->
    <Modal
        :title="'Extend to'"
        :maxWidth="'xs'"
        :show="isReactivateOpen"
        @close="closeModal('close')"
    >
        <form @submit.prevent="reactivateExpiredItems">
            <div class="flex flex-col items-start gap-6">
                <DateInput
                    :placeholder="'DD/MM/YYYY'"
                    :minDate="new Date(form.expiry_date_object)"
                    v-model="form.expiry_date"
                />

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :variant="'tertiary'"
                        :type="'button'"
                        :size="'lg'"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="'primary'"
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="form.expiry_date === ''"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </form>

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

</template>
