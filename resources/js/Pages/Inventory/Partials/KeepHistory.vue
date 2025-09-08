<script setup>
import axios from 'axios';
import dayjs from 'dayjs';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import SearchBar from '@/Components/SearchBar.vue';
import DateInput from '@/Components/Date.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import KeepHistoryTable from './KeepHistoryTable.vue';
import TabView from '@/Components/TabView.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import { useFileExport } from '@/Composables';
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Toast from '@/Components/Toast.vue';
import { wTrans, wTransChoice } from 'laravel-vue-i18n';

const props = defineProps({
    keepHistories: Array
})
const home = ref({
    label: wTrans('public.inventory_header'),
    route: '/inventory/inventory'
});
const items = ref([
    { label: wTrans('public.inventory.active_kept_item'), route: '/inventory/inventory/activeKeptItem' },
    { label: wTrans('public.inventory.keep_history') }
]);

// refs
const searchQuery = ref('');
const keepHistories = ref(props.keepHistories);
const initialKeepHistories = ref(props.keepHistories);
const defaultLatest3Months = computed(() => {
    let currentDate = dayjs();
    let last3Months = currentDate.subtract(3, 'month');

    return [last3Months.toDate(), currentDate.toDate()];
});
const date_filter = ref(defaultLatest3Months.value);  

// arrays
const tabs = ref([
    { key: 'Keep', title: wTrans('public.keep'), disabled: false },
    { key: 'Served/Returned', title: wTrans('public.served_returned'), disabled: false },
    { key: 'Expired', title: wTrans('public.expired'), disabled: false },
]);
const tranformedTabs = computed(() => {
    return tabs.value.map((tab) => {
        return tab.key.toLowerCase().replace(/[/\s_]+/g,"-");
    });
});
const selectedTab = ref(tranformedTabs.value[0]);

const checkedFilters = ref({
    expiresIn: [],
    keptIn: [],
})

const expireInDays = computed(() => [
    { text: `3 ${wTransChoice('public.day', 1).value}`, value: 3},
    { text: `7 ${wTransChoice('public.day', 1).value}`, value: 7},
    { text: `14 ${wTransChoice('public.day', 1).value}`, value: 14},
    { text: `30 ${wTransChoice('public.day', 1).value}`, value: 30}
]);

const keptInCategory = ref([
    { text: wTrans('public.cm'), value: 'cm'},
    { text: wTrans('public.quantity'), value: 'qty'}
]);

const { exportToCSV } = useFileExport();

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

const getKeepHistories = async (filters = {}, checkedFilters = {}) => {
    try {
        const keepHistoryResponse = await axios.post('/inventory/inventory/getAllKeepHistory', {
            dateFilter: filters,
            checkedFilters: checkedFilters,
        });
        keepHistories.value = keepHistoryResponse.data;
        initialKeepHistories.value = keepHistoryResponse.data;
        getFilteredRows(tranformedTabs.value[selectedTab.value]);
    } catch (error) {
        console.error(error);
    } finally {
    }
};

const getFilteredRows = (tab) => {
    switch(tab) {
        case 'served-returned': return keepHistories.value.filter((history) => history.status == 'Returned' || history.status == 'Served');
        case 'keep': return keepHistories.value.filter((history) => history.status == 'Keep');
        case 'expired': return keepHistories.value.filter((history) => history.status == 'Expired');
    }
}

const csvExport = (action_type) => {
    const startDate = dayjs(date_filter.value[0]).format('DD/MM/YYYY');
    const endDate = date_filter.value[1] != null ? dayjs(date_filter.value[1]).format('DD/MM/YYYY') : dayjs(date_filter.value[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `${wTrans('public.date_range').value}: ${startDate} - ${endDate}`;
    let rows = [];
    let translatedAction = '';

    switch (action_type) {
        case 'all':
            rows = initialKeepHistories.value;
            translatedAction = wTrans('public.all').value;
            break;
        case 'keep':
            rows = keepHistories.value.filter((history) => history.status == 'Keep');
            translatedAction = wTrans('public.keep').value;
            break;
        case 'served-returned':
            rows = keepHistories.value.filter((history) => history.status == 'Returned' || history.status == 'Served');
            translatedAction = wTrans('public.served_returned').value;
            break;
        case 'expired':
            rows = keepHistories.value.filter((history) => history.status == 'Expired');
            translatedAction = wTrans('public.expired').value;
            break;
    }

    const title = `${wTrans('public.inventory.keep_history').value} - (${translatedAction})`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { 'Item Name': title, 'Quantity': '', 'Date': '', 'Keep For': '' },
        { 'Item Name': dateRange, 'Quantity': '', 'Date': '', 'Keep For': '' },
        { 'Item Name': wTrans('public.inventory.item_name').value, 'Quantity': wTrans('public.quantity').value, 'Date': wTrans('public.date').value, 'Keep For': wTrans('public.field.keep_for').value },
        ...rows.map(row => ({
            'Item Name': row.item_name,
            'Quantity': row.qty,
            'Date': dayjs(row.created_at).format('DD/MM/YYYY'),
            'Keep For': row.keep_item.customer.full_name
        })),
    ];

    exportToCSV(formattedRows, wTrans('public.inventory.keep_history').value);
}

const changeActiveTab = (event) => {
    selectedTab.value = tranformedTabs.value[event];
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
    getKeepHistories(date_filter.value, checkedFilters.value);
    close();
}

const applyCheckedFilters = (close) => {
    getKeepHistories(date_filter.value, checkedFilters.value);
    close();
}

watch(() => date_filter.value, () => {
    getKeepHistories(date_filter.value, checkedFilters.value);
})

watch(() => keepHistories.value, () => {
    getFilteredRows(tranformedTabs.value[selectedTab.value])
})

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        keepHistories.value = [...initialKeepHistories.value];
        return;
    }

    const query = newValue.toLowerCase();

    keepHistories.value = initialKeepHistories.value.filter(history => {
        const item_name = history.item_name.toLowerCase();
        const qty = parseInt(history.qty) > 0 ? history.qty.toString() : null;
        const cm = parseInt(history.cm) > 0 ? history.cm.toString() : null;
        const customer = history.keep_item.customer.full_name.toLowerCase();
        const waiter = history.keep_item.waiter.full_name.toLowerCase();

        return item_name.includes(query) ||
            (qty && qty.includes(query)) ||
            (cm && cm.includes(query)) ||
            customer.includes(query) ||
            waiter.includes(query);
    });
}, { immediate: true });

</script>

<template>
    <Head :title="$t('public.inventory.keep_history')" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <Toast />

        <div class="flex flex-col p-6 items-start gap-6 rounded-[5px] border-solid border border-primary-100">
            <div class="flex items-start gap-2.5 self-stretch">
                <div class="flex justify-between items-center flex-[1_0_0] self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium">{{ $t('public.inventory.keep_history') }}</span>
                    <Menu as="div" class="relative inline-block text-left">
                        <div>
                            <MenuButton
                                class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-2 text-sm font-medium text-primary-900 hover:text-primary-800">
                                {{ $t('public.action.export') }}
                                <UploadIcon class="size-4 cursor-pointer" />
                            </MenuButton>
                        </div>

                        <transition 
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0" 
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100" 
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems class="absolute z-10 right-0 mt-2 w-40 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg">
                                <MenuItem v-slot="{ active }">
                                    <button type="button" :class="[
                                        { 'bg-primary-100': active },
                                        { 'bg-grey-50 pointer-events-none': initialKeepHistories.length === 0 },
                                        'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                    ]" :disabled="initialKeepHistories.length === 0" @click="csvExport('all')">
                                        {{ $t('public.all') }}
                                    </button>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button type="button" :class="[
                                        { 'bg-primary-100': active },
                                        { 'bg-grey-50 pointer-events-none': keepHistories.filter((history) => history.status == 'Keep').length === 0 },
                                        'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                    ]" :disabled="keepHistories.filter((history) => history.status == 'Keep').length === 0" @click="csvExport('keep')">
                                        {{ $t('public.keep') }}
                                    </button>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button type="button" :class="[
                                        { 'bg-primary-100': active },
                                        { 'bg-grey-50 pointer-events-none': keepHistories.filter((history) => history.status == 'Returned' || history.status == 'Served').length === 0 },
                                        'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                    ]" :disabled="keepHistories.filter((history) => history.status == 'Returned' || history.status == 'Served').length === 0" @click="csvExport('served-returned')">
                                        {{ $t('public.served_returned') }}
                                    </button>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button type="button" :class="[
                                        { 'bg-primary-100': active },
                                        { 'bg-grey-50 pointer-events-none': keepHistories.filter((history) => history.status == 'Expired').length === 0 },
                                        'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                                    ]" :disabled="keepHistories.filter((history) => history.status == 'Expired').length === 0" @click="csvExport('expired')">
                                        {{ $t('public.expired') }}
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div class="flex flex-col items-start gap-6 self-stretch">
                <TabView :tabs="tabs" @onChange="changeActiveTab($event)">
                    <template
                        v-for="tab in tranformedTabs"
                        :key="tab"
                        v-slot:[tab]
                    >
                    
                        <div class="flex flex-col pb-6 gap-6 flex-[1_0_0] w-full">
                            <div class="flex items-start gap-5 self-stretch">
                                <SearchBar
                                    :placeholder="$t('public.search')"
                                    :showFilter="true"
                                    v-model="searchQuery"
                                >
                                    <template #default="{ hideOverlay }">
                                        <!-- <div class="flex flex-col self-stretch gap-4 items-start" v-if="selectedTab === 'keep'">
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
                                        </div> -->
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
                                                {{ $t('public.action.clear_all') }}
                                            </Button>
                                            <Button
                                                :size="'lg'"
                                                @click="applyCheckedFilters(hideOverlay)"
                                            >
                                                {{ $t('public.action.apply') }}
                                            </Button>
                                        </div>
                                    </template>
                                </SearchBar>
                                <DateInput
                                    :inputName="'date_filter'"
                                    :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                    :range="true"
                                    class="!w-fit"
                                    v-model="date_filter"
                                />
                            </div>

                            <KeepHistoryTable 
                                :rows="getFilteredRows(tab)"
                                :rowType="rowType"
                                :tab="tab"
                                :selectedTab="tranformedTabs.indexOf(tab)+1"
                                @getKeepHistories="getKeepHistories"
                            />
                        </div>
                    </template>
                </TabView>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
