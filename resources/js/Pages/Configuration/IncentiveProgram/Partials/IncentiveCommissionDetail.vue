<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { TableSortIcon } from '@/Components/Icons/solid';
import SearchBar from '@/Components/SearchBar.vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, ref, watch } from 'vue';
import { EmptyIllus } from '@/Components/Icons/illus';
import AchievementDetails from './AchievementDetails.vue';
import Toast from '@/Components/Toast.vue';
import { transactionFormat, useCustomToast } from '@/Composables';
import Paginator from 'primevue/paginator';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';

const home = ref({
    label: 'Configuration',
    route: '/configurations/configurations'
});

const items = ref([
    { label: 'Incentive Commission Detail'},
])

const props = defineProps({
    details: {
        type: Array,
        required: true,
    },
    achievementDetails: {
        type: Object,
        required: true,
    },
    waiterName: {
        type: Object,
        required: true,
    }
})
const detail = ref(props.details);
const sortField = ref('');
const sortOrder = ref(1);
const currentPage = ref(1);
const statuses = ['Paid', 'Pending'];
const isLoading = ref(false);
const totalPages = ref(Math.ceil(props.details.length /4));
const searchQuery = ref('');
const rows = ref([]);
const waiters = ref([]);
const incentiveDetails = ref(props.achievementDetails);

const { formatAmount } = transactionFormat();
const { showMessage } = useCustomToast();

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
};

const goToPage = (event) => {
    const page = parseInt(event.target.value);
    if (page > 0 && page <= props.totalPages) {
        currentPage.value = page;
    }
};

const computedRowsPerPage = computed(() => {
    const start = (currentPage.value - 1) * 4;
    const end = start + 4;
    return detail.value.slice(start, end);
});

const getEmployeeIncent = async (id) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/configurations/getIncentDetail/${id}`);
        // waiters.value = response.data.waiters;
        // detail.value = response.data.incentiveProg.map(incentive => {
        //     return {
        //         ...incentive, 
        //         isRate: incentive.type !== 'fixed' 
        //     };
        // });
        incentiveDetails.value = response.data.achievementDetails;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const updateStatus =  (selectedStatus, id) => {
    isLoading.value = true;
    try {
        axios.post(`/configurations/configurations/updateStatus/${id}`, { selectedStatus: selectedStatus });

        // Find and update the specific record directly without nested loops
        const targetGroup = detail.value.find(group => 
            group.data.some(data => data.id === id)
        );

        if (targetGroup) {
            const targetData = targetGroup.data.find(data => data.id === id);
            if (targetData) {
                targetData.status = selectedStatus;
            }
        }

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Achiever status has been updated.',
            });
        }, 200)
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

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
        return 0; 
    };

    // Sort function for 'stock_qty', 'item_category', or other fields
    switch (field) {
        case 'earned':
            // Sort groups by total stock quantity
            detail.value.forEach(group => {
                group.data.sort((a, b) =>
                    compareValues(a.incentive, b.incentive)
                );
            });
            break;

        case 'sales':
            // Sort items within each group by total sales
            detail.value.forEach(group => {
                group.data.sort((a, b) =>
                    compareValues(a.total_sales, b.total_sales)
                );
            });
            break;

        case 'status':
            // Sort items within each group by status
            detail.value.forEach(group => {
                group.data.sort((a, b) => compareValues(a.status.toLowerCase(), b.status.toLowerCase()));
            });
            break;

        case 'achiever':
            // Sort items within each group by name alphabetically
            detail.value.forEach(group => {
                group.data.sort((a, b) => compareValues(a.name.toLowerCase(), b.name.toLowerCase()));
            });
            detail.value.sort((a, b) => {
                const valueA = a.data[0][field]?.toLowerCase?.() || a[field]?.toLowerCase?.() || '';
                const valueB = b.data[0][field]?.toLowerCase?.() || b[field]?.toLowerCase?.() || '';
                return compareValues(valueA, valueB);
            });
            break;
    }
};

watch(() => detail.value, (newValue) => {
    detail.value = newValue ? newValue : {};
}, { immediate: true } );

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        detail.value = props.details;
        return;
    }

    const query = newValue.toLowerCase();

    detail.value = props.details.map(group => {
        const filteredDetails = group.data.filter(item => {
            const itemName = item.name.toLowerCase();
            const itemTotalSales = item.total_sales.toString().toLowerCase();
            const itemMonthlySale = item.monthly_sale.toString().toLowerCase();
            const itemIncentive = item.incentive.toString().toLowerCase();
            const itemStatus = item.status.toLowerCase();
            return  itemName.includes(query) ||
                    itemTotalSales.includes(query) ||
                    itemMonthlySale.includes(query) ||
                    itemIncentive.includes(query) ||
                    itemStatus.includes(query);
        });

        return filteredDetails.length > 0 ? {...group, data: filteredDetails } : null;
    })
    .filter(group => group !== null);
}, { immediate: true });

</script>

<template>
    <Head title="Incentive Commission Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb
                :home="home"
                :items="items"
            />
        </template>

        <Toast />

        <div class="w-full flex p-5 items-start gap-5 flex-[1_0_0] self-stretch flex-col md:flex-col lg:flex-row">
            <div class="w-full h-full flex flex-col p-6 justify-between items-center rounded-[5px] border border-solid border-primary-100 min-w-[700px] overflow-auto scrollbar-webkit">
                <div class="w-full flex flex-col items-center gap-6 self-stretch">
                    <div class="flex justify-between items-center flex-[1_0_0] self-stretch">
                        <span class="text-primary-900 text-md font-medium">Past Achiever</span>
                    </div>

                    <SearchBar
                        placeholder="Search"
                        :showFilter="false"
                        v-model="searchQuery"
                    />

                    <table class="w-full border-spacing-3 border-collapse min-w-[600px]">
                        <thead class="w-full bg-primary-50 overflow-hidden">
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
                                v-if="detail.length > 0" 
                                v-for="(group, groupIndex) in computedRowsPerPage" :key="groupIndex" 
                                class="rounded-[5px]"
                                :class="groupIndex % 2 === 0 ? 'bg-white' : 'bg-primary-25'"
                            >
                                <td colspan="8" class="p-0">
                                    <Disclosure 
                                        as="div" 
                                        :defaultOpen="false"
                                        v-slot="{ open }" 
                                        class="flex flex-col justify-center min-h-12"
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
                                                                <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ group.month }}</span>
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
                                            <DisclosurePanel class="bg-white pt-2 pb-3" >
                                                <div 
                                                    class="w-full flex items-center gap-x-3 rounded-[5px] text-sm text-grey-900 font-medium odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50" 
                                                    v-for="(data, index) in group.data" :key="index"
                                                >
                                                    <div class="w-[29%] py-2 px-3 truncate flex gap-2.5 items-center">
                                                        <!-- <div class="size-8 rounded-full bg-primary-300"></div> -->
                                                        <img 
                                                            :src="data.image ? data.image 
                                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                            alt=""
                                                            class="size-8 rounded-full"
                                                        >
                                                        {{ data.name }}
                                                    </div>
                                                    <div class="w-[24%] py-2 px-3">RM {{ formatAmount(data.total_sales) }}</div>
                                                    <div class="w-[23%] py-2 px-3">RM {{ formatAmount(data.incentive) }}</div>
                                                    <div class="w-[17%] py-2 px-3">
                                                        <Menu as="div" class="relative inline-block text-left">
                                                            <div>
                                                                <MenuButton
                                                                    class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 px-2.5 py-1.5 text-2xs font-semibold"
                                                                    :class="data.status === 'Paid' 
                                                                        ? 'bg-green-50 border border-green-100 text-green-700 hover:bg-green-100' 
                                                                        : 'bg-primary-50 border border-primary-200 text-primary-500 hover:bg-primary-100'"
                                                                >
                                                                    {{ data.status }}
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
                                                                <MenuItems class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg">
                                                                    <MenuItem v-slot="{ active }" v-for="status in statuses">
                                                                        <button 
                                                                            type="button" 
                                                                            :class="[
                                                                                { 'bg-primary-100': active },
                                                                                { 'group flex items-center gap-2.5 px-4 py-2 self-stretch rounded-[5px] bg-primary-50 text-primary-900' : (data.status === status)},

                                                                                'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',

                                                                            ]" 
                                                                            @click="updateStatus(status, data.id, props.achievementDetails.id)"
                                                                        >
                                                                            {{ status }}
                                                                        </button>
                                                                    </MenuItem>
                                                                </MenuItems>
                                                            </transition>
                                                        </Menu>
                                                    </div>
                                                    <div class="w-[7%] py-2 px-3"></div>
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
                <Paginator
                    :rows="4" 
                    :totalRecords="detail.length"
                    :class="'w-full'"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    @page="onPageChange"
                    v-if="detail.length > 0" 
                    :pt="{
                        root: {
                            class: 'flex justify-between items-center flex-wrap bg-white text-grey-500 py-3 !w-full '
                        },
                        start: {
                            class: 'mr-auto'
                        },
                        pages: {
                            class: 'flex justify-center items-center'
                        },
                        pagebutton: ({ context }) => {
                            return {
                                class: [
                                    'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                    {
                                        'rounded-full bg-primary-900 text-primary-50': context.active,
                                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900': !context.active,
                                    },
                                ]
                            };
                        },
                        end: {
                            class: 'ml-auto'
                        },
                        firstpagebutton: {
                            class: [
                                {
                                    'hidden': totalPages < 5,
                                },
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                                'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',,
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                                'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                            ]
                        },
                        previouspagebutton: {
                            class: [
                                {
                                    'hidden': totalPages === 1
                                },
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                                'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                            ]
                        },
                        nextpagebutton: {
                            class: [
                                {
                                    'hidden': totalPages === 1
                                },
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                                'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                            ]
                        },
                        lastpagebutton: {
                            class: [
                                {
                                    'hidden': totalPages < 5
                                },
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                                'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                            ]
                        },
                    }"
                >
                    <template #start>
                        <div class="text-xs font-medium text-grey-500">
                            Showing: <span class="text-grey-900">{{ totalPages === 0 ? 0 : currentPage }} of {{ totalPages }}</span>
                        </div>
                    </template>
                    <template #end>
                        <div class="flex justify-center items-center gap-2 text-xs font-medium text-grey-900 whitespace-nowrap">
                            Go to Page: 
                            <TextInput
                                :inputName="'go_to_page'"
                                :placeholder="'eg: 12'"
                                class="!w-20"
                                :disabled="true"
                                v-if="totalPages === 1"
                            />
                            <TextInput
                                :inputName="'go_to_page'"
                                :placeholder="'eg: 12'"
                                class="!w-20"
                                :disabled="false"
                                @input="goToPage($event)"
                                v-else
                            />
                        </div>
                    </template>
                    <template #firstpagelinkicon>
                        <svg 
                            width="15" 
                            height="12" 
                            viewBox="0 0 15 12" 
                            fill="none" 
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path 
                                d="M14 11L9 6L14 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"/>
                            <path
                                d="M6 11L1 6L6 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            />
                        </svg>
                    </template>
                    <template #prevpagelinkicon>
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="7" 
                            height="12" 
                            viewBox="0 0 7 12" 
                            fill="none"
                        >
                            <path 
                                d="M6 11L1 6L6 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            />
                        </svg>
                    </template>
                    <template #nextpagelinkicon>
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="7" 
                            height="12" 
                            viewBox="0 0 7 12" 
                            fill="none"
                        >
                            <path 
                                d="M1 11L6 6L1 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            />
                        </svg>
                    </template>
                    <template #lastpagelinkicon>
                        <svg 
                            width="15" 
                            height="12" 
                            viewBox="0 0 15 12" 
                            fill="none" 
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path 
                                d="M1 11L6 6L1 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"/>
                            <path
                                d="M9 11L14 6L9 1" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            />
                        </svg>
                    </template>
                </Paginator>
            </div>

            <AchievementDetails 
                :achievementDetails="incentiveDetails"
                :waiterName="waiterName"
                @getEmployeeIncent="getEmployeeIncent"
            />
        </div>
    </AuthenticatedLayout>
</template>

