<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DateInput from '@/Components/Date.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { SquareStickerIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import { usePhoneUtils } from '@/Composables';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomerDetail from '@/Pages/Customer/Partials/CustomerDetail.vue';
import ViewHistory from '@/Pages/Customer/Partials/ViewHistory.vue';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    activeKeptItem: {
        type: Array,
        required: true,
    }
})
const { formatPhone } = usePhoneUtils();

const home = ref({
    label: 'Inventory',
    route: '/inventory/inventory'
});

const items = ref([
    { label: 'Kept Item'},
]);

const defaultLatest6Months = computed(() => {
    let currentDate = dayjs();
    let last6Months = currentDate.subtract(6, 'month');

    return [last6Months.toDate(), currentDate.toDate()];
});

// refs
const date_filter = ref(defaultLatest6Months.value);
const rowsPerPage = ref(12);
const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}
const totalPages = computed(() => {
    return Math.ceil(props.activeKeptItem.length / rowsPerPage.value);
})
const columns = ref([
    { field: 'created_at', header: 'Kept Date', width: '20', sortable: true},
    { field: 'customer.full_name', header: 'Customer', width: '25', sortable: true},
    { field: 'order_item_subitem.product_item.inventory_item.item_name', header: 'Item Name', width: '25', sortable: true},
    { field: 'qty', header: 'Qty', width: '11.2', sortable: true},
    { field: 'expired_to', header: 'Expire in', width: '18.8', sortable: true},
])
const searchQuery = ref('');
const rows = ref(props.activeKeptItem);
const isLoading = ref(false);
const isDrawerOpen = ref(false);
const selectedCustomer = ref(null);
const checkedFilters = ref({
    expiresIn: [],
    keptIn: [],
})
const expireInDays = ref([
    { text: '3 days', value: 3},
    { text: '7 days', value: 7},
    { text: '14 days', value: 14},
    { text: '30 days', value: 30}
]);
const keptInCategory = ref([
    { text: 'CM', value: 'cm'},
    { text: 'Quantity', value: 'qty'}
]);

// functions
const getTimeDifference = (date) => {
    const createdDate = new Date(date);
    const now = new Date();
    
    let months = createdDate.getMonth() - now.getMonth() + (12 * (createdDate.getFullYear() - now.getFullYear()));
    let days = createdDate.getDate() - now.getDate();

    // Adjust for negative days
    if (days < 0) {
        months -= 1;
        const previousMonth = new Date(now.getFullYear(), now.getMonth(), 0);
        days += previousMonth.getDate();
    }

    if (months > 0) {
        return `${months} months`;
    } else if (days > 0) {
        return `${days} days`;
    } else {
        return `${days} day`;
    }
};

const filterKeptItems = async (filters = {}, checkedFilters = {}) => {
    isLoading.value = true;
    try {
        const response = await axios.get('/inventory/inventory/filterKeptItem', {
            params: {
                date_filter: filters,
                checkedFilters: checkedFilters,
            }
        });
        rows.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

const openDrawer = (row) => {
    isDrawerOpen.value = true;
    selectedCustomer.value = row;
}

const closeDrawer = () => {
    isDrawerOpen.value = false;
}

const toggleExpiresIn = (value) => {
    const index = checkedFilters.value.expiresIn.indexOf(value);
    if (index > -1 ) {
        checkedFilters.value.expiresIn.splice(index, 1);
    } else {
        checkedFilters.value.expiresIn.push(value);
    }
};

const resetFilters = () => {
    return {
        expiresIn: [],
        keptIn: [],
    };
}

const toggleKeptIn = (category) => {
    const index = checkedFilters.value.keptIn.indexOf(category);
    if(index > -1){
        checkedFilters.value.keptIn.splice(index, 1);
    } else {
        checkedFilters.value.keptIn.push(category);
    }
}

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    filterKeptItems(date_filter.value, checkedFilters.value);
    close();
}

const applyCheckedFilters = (close) => {
    filterKeptItems(date_filter.value, checkedFilters.value);
    close();
}

watch(() => date_filter.value, (newValue) => {
    filterKeptItems(newValue, checkedFilters.value);
})

watch(() => searchQuery.value, (newValue) => {
    if(newValue === ''){
        rows.value = props.activeKeptItem;
        return;
    }

    const query = newValue.toLowerCase();

    rows.value = props.activeKeptItem.filter(item => {
        const keep_date = dayjs(item.created_at).format('DD/MM/YYYY').toString().toLowerCase();
        const customer = item.customer.full_name.toLowerCase();
        const item_name = item.order_item_subitem.product_item.inventory_item.item_name.toLowerCase();
        const qty = parseInt(item.qty) > 0 ? item.qty.toString() : null;
        const cm = parseInt(item.cm) > 0 ? item.cm.toString() : null;
        const expired_in = item.expired_to ? getTimeDifference(item.expired_to) : null;

        return  keep_date.includes(query) ||
                customer.includes(query) ||
                item_name.includes(query) ||
                (qty && qty.includes(query)) ||
                (cm && cm.includes(query)) ||
                (expired_in && expired_in.includes(query));
    });
})
</script>

<template>
    <Head title="Active Kept Items" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home" 
                :items="items"
            />
        </template>
        
        <div class="flex flex-col items-start gap-6 p-6 rounded-[5px] border border-solid border-primary-100">
            <div class="flex items-center gap-2.5 self-stretch">
                <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium">Active Kept Item</span>
                <Button
                    :variant="'tertiary'"
                    :type="'button'"
                    :size="'lg'"
                    :href="route('inventory.viewKeepHistories')"
                    :iconPosition="'left'"
                    class="!w-fit"
                >
                    <template #icon>
                        <SquareStickerIcon class="w-6 h-6" />
                    </template>
                    View Keep History
                </Button>
            </div>

            <div class="flex items-start gap-5 self-stretch">
                <SearchBar
                    :showFilter="true"
                    :placeholder="'Search'"
                    v-model="searchQuery"
                >
                    <template #default="{ hideOverlay }">
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Expire in</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(expire, index) in expireInDays" 
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox
                                        :checked="checkedFilters.expiresIn.includes(expire.value)"
                                        @click="toggleExpiresIn(expire.value)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ expire.text }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Kept in</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(kept, index) in keptInCategory" 
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox
                                        :checked="checkedFilters.keptIn.includes(kept.value)"
                                        @click="toggleKeptIn(kept.value)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ kept.text }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                            <Button
                                :type="'button'"
                                :variant="'tertiary'"
                                :size="'lg'"
                                @click="clearFilters(hideOverlay)"
                            >
                                Clear All
                            </Button>
                            <Button
                                :size="'lg'"
                                @click="applyCheckedFilters(hideOverlay)"
                            >
                                Apply
                            </Button>
                        </div>
                    </template>
                </SearchBar>

                <DateInput 
                    :range="true"
                    v-model="date_filter"
                    class="!w-fit"
                />
            </div>

            <Table
                :variant="'list'"
                :rows="rows"
                :columns="columns"
                :rowType="rowType"
                :rowsPerPage="rowsPerPage"
                :paginator="true"
                :totalPages="totalPages"
                @onRowClick="openDrawer($event.data.customer)"
                minWidth="min-w-[544px]"
                class="[&>div>div>table>tbody>tr>td]:px-3 [&>div>div>table>tbody>tr>td]:py-2"
                v-if="rows.length > 0 && !isLoading"
            >
                <template #created_at="rows">
                    <span class="text-grey-900 text-sm font-medium">{{ dayjs(rows.created_at).format('DD/MM/YYYY') }}</span>
                </template>
                <template #customer.full_name="rows">
                    <div class="flex items-center gap-2">
                        <img 
                            :src="rows.customer.image ? rows.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="CustomerImage"
                            class="size-9 object-contain rounded-full"
                        >
                        <div class="flex flex-col justify-center items-start flex-[1_0_0]">
                            <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-sm font-semibold">{{ rows.customer.full_name }}</span>
                            <span class="self-stretch text-grey-900 text-sm font-normal line-clamp-1">{{ formatPhone(rows.customer.phone) }}</span>
                        </div>
                    </div>
                </template>
                <template #order_item_subitem.product_item.inventory_item.item_name="rows">
                    <span class="flex-[1_0_0] text-grey-900 text-sm font-semibold">{{ rows.order_item_subitem.product_item.inventory_item.item_name }}</span>
                </template>
                <template #qty="rows">
                    <span class="text-grey-900 text-sm font-medium">{{ parseFloat(rows.qty) > parseFloat(rows.cm) ? `x ${rows.qty}` : `${parseInt(rows.cm)} cm` }}</span>
                </template>
                <template #expired_to="rows">
                    <span class="text-grey-900 text-sm font-medium">{{ rows.expired_to ? getTimeDifference(rows.expired_to) : '-' }}</span>
                </template>
            </Table>

            <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </div>

        <!-- customer keep item drawer -->
        <RightDrawer
            :title="''"
            :header="'Customer Detail'"
            :show="isDrawerOpen"
            @close="closeDrawer"
        >
            <CustomerDetail
                :customer="selectedCustomer" 
            />
        </RightDrawer>
    </AuthenticatedLayout>
</template>

