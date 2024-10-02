<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { TableSortIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FilterMatchMode } from 'primevue/api';
import { ref } from 'vue';
import { EmptyIllus } from '@/Components/Icons/illus';
import AchievementDetails from './AchievementDetails.vue';
import Toast from '@/Components/Toast.vue';

const home = ref({
    label: 'Configuration',
    route: '/configurations/configurations'
});

const items = ref([
    { label: 'Incentive Commission Detail'},
])

const props = defineProps({
    details: {
        type: Object,
        required: true,
    },
    achievementDetails: {
        type: Object,
        required: true,
    },
    waiterName: {
        type: Array,
        required: true,
    }
})
// console.log(props.details);

const sortInventories = (field) => {
    if (sortField.value === field) {
        sortOrder.value = -sortOrder.value;
    } else {
        sortField.value = field;
        sortOrder.value = 1;
    }


    // Helper function to compare values based on type (string, number, date)
    const compareValues = (valueA, valueB) => {
        if (typeof valueA === 'string') {
            return valueA.localeCompare(valueB) * sortOrder.value;
        } else if (typeof valueA === 'number') {
            return (valueA - valueB) * sortOrder.value;
        } else if (Date.parse(valueA)) {
            return (new Date(valueA) - new Date(valueB)) * sortOrder.value;
        }
        return 0; // Default case if no specific comparison is needed
    };

    // Sort function for 'stock_qty', 'item_category', or other fields
    switch (field) {
        case 'stock_qty':
            // Sort groups by total stock quantity
            props.rows.sort((a, b) => {
                const totalStockA = a.inventory_items.reduce((sum, item) => sum + item.stock_qty, 0);
                const totalStockB = b.inventory_items.reduce((sum, item) => sum + item.stock_qty, 0);
                return compareValues(totalStockA, totalStockB);
            });

            // Sort items within each group by individual stock quantity
            props.rows.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.stock_qty, b.stock_qty));
            });
            break;

        case 'item_category':
            // Sort items within each group by item category name
            props.rows.forEach(group => {
                group.inventory_items.sort((a, b) =>
                    compareValues(a.item_category.name.toLowerCase(), b.item_category.name.toLowerCase())
                );
            });
            break;

        case 'status':
            // Sort items within each group by status
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.status.toLowerCase(), b.status.toLowerCase()));
            });
            break;

        case 'item_code':
            // Sort items within each group by item_code alphabetically
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.item_code.toLowerCase(), b.item_code.toLowerCase()));
            });
            break;

        case 'name':
            // Sort items within each group by item_code alphabetically
            rows.value.forEach(group => {
                group.inventory_items.sort((a, b) => compareValues(a.item_name.toLowerCase(), b.item_name.toLowerCase()));
            });
            props.details.sort((a, b) => {
                const valueA = a.inventory_items[0][field]?.toLowerCase?.() || a[field]?.toLowerCase?.() || '';
                const valueB = b.inventory_items[0][field]?.toLowerCase?.() || b[field]?.toLowerCase?.() || '';
                return compareValues(valueA, valueB);
            });
            break;
    }
};

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home"
                :items="items"
            />
        </template>

        <Toast />

        <div class="w-full flex p-5 items-start gap-5 flex-[1_0_0] self-stretch">
            <div class="w-full flex flex-col p-6 justify-between items-center rounded-[5px] border border-solid border-primary-100">
                <div class="w-full flex flex-col items-center gap-6 self-stretch">
                    <div class="flex justify-between items-center flex-[1_0_0] self-stretch">
                        <span class="text-primary-900 text-md font-medium">Past Achiever</span>
                    </div>

                    <SearchBar
                        placeholder="Search"
                        :showFilter="false"
                        v-model="filters['global'].value"
                    />

                    <table class="w-full border-spacing-3 border-collapse min-w-[755px]">
                        <thead class="w-full bg-primary-50">
                            <tr>
                                <th class="w-[13%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('achiever')">
                                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                        Achiever
                                        <TableSortIcon class="w-4 text-primary-800 flex-shrink-0"/>
                                    </span>
                                </th>
                                <th class="w-[11%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('sales')">
                                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                        Sales
                                        <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                    </span>
                                </th>
                                <th class="w-[10%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('earned')">
                                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                        Earned
                                        <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                    </span>
                                </th>
                                <th class="w-[11%] py-2 px-3 cursor-pointer transition ease-in-out hover:bg-primary-200" @click="sortInventories('status')">
                                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                                        Status
                                        <TableSortIcon class="w-4 text-primary-800 flex-shrink-0" />
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="w-full before:content-['@'] before:table-row before:leading-3 before:indent[-99999px] before:invisible">
                            <tr 
                                v-if="props.details.length > 0" 
                                v-for="(group, groupIndex) in computedRowsPerPage" :key="groupIndex" 
                                class="rounded-[5px]"
                                :class="groupIndex % 2 === 0 ? 'bg-white' : 'bg-primary-25'"
                            >
                                <td colspan="8" class="p-0">
                                    <Disclosure 
                                        as="div" 
                                        :defaultOpen="false"
                                        v-slot="{ open }" 
                                        class="flex flex-col justify-center"
                                    >
                                        <DisclosureButton class="flex items-center justify-between gap-2.5 rounded-sm py-1 hover:bg-primary-50">
                                            <table class="w-full border-spacing-1 border-separate">
                                                <tbody class="w-full">
                                                    <tr>
                                                        <td class="w-[5%]">
                                                            <svg 
                                                                width="20" 
                                                                height="20" 
                                                                viewBox="0 0 20 20" 
                                                                fill="none" 
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="inline-block text-grey-900 transition ease-in-out"
                                                                :class="[open ? '' : '-rotate-90']"
                                                            >
                                                                <path d="M15.8337 7.08337L10.0003 12.9167L4.16699 7.08337" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                                                            </svg>
                                                        </td>
                                                        <td class="w-[23%]">
                                                            <div class="flex items-center gap-3">
                                                                <span class="w-[60px] h-[60px] flex-shrink-0 rounded-full bg-primary-700"></span>
                                                                <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ props.details.name }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="w-[56%]"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="flex items-center justify-between">
                                            </div>
                                        </DisclosureButton>
                                        <transition
                                            enter-active-class="transition duration-100 ease-out"
                                            enter-from-class="transform scale-95 opacity-0"
                                            enter-to-class="transform scale-100 opacity-100"
                                            leave-active-class="transition duration-100 ease-in"
                                            leave-from-class="transform scale-100 opacity-100"
                                            leave-to-class="transform scale-95 opacity-0"
                                        >
                                            <DisclosurePanel class="bg-white pt-2 pb-3">
                                                <div 
                                                    class="w-full flex items-center gap-x-3 rounded-[5px] text-sm text-grey-900 font-medium odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50" 
                                                    v-for="(item, index) in props.details" :key="index"
                                                >
                                                    <div class="w-[27%] py-2 px-3 truncate">{{ item.name }}</div>
                                                    <div class="w-[11%] py-2 px-3">{{ item.total_sales }}</div>
                                                    <div class="w-[10%] py-2 px-3">{{ item.earned }}</div>
                                                    <div class="w-[11%] py-2 px-3">{{ item.status }}</div>
                                                    <div class="w-[11%] py-2 px-3">{{ 0 }}</div>
                                                    <div class="w-[12%] py-2 px-3"></div>
                                                </div>
                                                <div class="flex justify-end pr-[50px] bg-white mt-3">
                                                    <span>Total Stock: {{ totalInventoryItemStock(group.inventory_items) }}</span>
                                                </div>
                                            </DisclosurePanel>
                                        </transition>
                                    </Disclosure>
                                </td>
                            </tr>
                            <tr v-else>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center gap-5">
                                        <EmptyIllus />
                                        <span class="text-primary-900 text-sm font-medium">We couldn't find any result...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AchievementDetails 
                :achievementDetails="achievementDetails"
                :waiterName="waiterName"
            />
        </div>
    </AuthenticatedLayout>
</template>

