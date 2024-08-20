<script setup>
import axios from "axios";
import { Head } from "@inertiajs/vue3";
import { defineProps, ref, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { EditIcon, DeleteIcon, CouponIcon, PointsIcon, ProductQualityIcon} from "@/Components/Icons/solid";
import EditTier from "./EditTier.vue";
import MemberList from "./MemberList.vue";

const props = defineProps({
    id: String,
});

const home = ref({
    label: 'Loyalty Programme',
    route: '/loyalty-programme/loyalty-programme'
});

const items = ref([
    { label: 'Tier Detail' },
]);

const columns = ref([
    { field: "full_name", header: "Member name", width: "46", sortable: true },
    { field: "created_at", header: "Joined on", width: "26", sortable: true },
    { field: "total_spend", header: "Total spent",  width: "28", sortable: true },
]);

const customers = ref([]);
const ranking = ref([]);
const rankingRewards = ref([]);
const totalPages = ref(12);
const rowsPerPage = ref(11);
const inventoryItems = ref([]);
const editTierFormIsOpen = ref(false);
const deleteTierFormIsOpen = ref(false);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "name",
};

const actions = {
    view: () => ``,
    replenish: () => '',
    edit: () => '',
    delete: () => ``,
};

const emit = defineEmits(["close"]);

onMounted(async() => {
    try {
        const response = await axios.get(`/loyalty-programme/getMemberList?id=${props.id}`);
        
        customers.value = response.data.customers;
        ranking.value = response.data.ranking;
        rankingRewards.value = response.data.rankingRewards;
        totalPages.value = Math.ceil(customers.value.length / rowsPerPage.value);

        const inventoryItemsResponse = await axios.get('/loyalty-programme/getAllInventoryWithItems');
        inventoryItems.value = inventoryItemsResponse.data;
    } catch (error) {
        console.log("Error fetching data:", error);
    } finally {

    }
});

const calculateValidPeriod = (validFrom, validTo) => {
    const startDate = new Date(validFrom);
    const endDate = new Date(validTo);

    // Adjust for UTC+8 timezone
    startDate.setHours(startDate.getHours() + 8);
    endDate.setHours(endDate.getHours() + 8);

    const startDateString = startDate.toLocaleDateString("en-MY"); // Adjust locale as needed
    const endDateString = endDate.toLocaleDateString("en-MY"); // Adjust locale as needed

    const diffInMonths =
        (endDate.getFullYear() - startDate.getFullYear()) * 12 +
        (endDate.getMonth() - startDate.getMonth());

    if (diffInMonths === 1) {
        return "1 month starting from entry date";
    } else if (diffInMonths === 3) {
        return "3 months starting from entry date";
    } else if (diffInMonths === 6) {
        return "6 months starting from entry date";
    } else if (diffInMonths === 12) {
        return "12 months starting from entry date";
    } else {
        return `${startDateString} to ${endDateString}`;
    }
};

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showEditTierForm = (event) => {
    handleDefaultClick(event);
    editTierFormIsOpen.value = true;
}

const hideEditTierForm = () => {
    editTierFormIsOpen.value = false;
}

const showDeleteTierForm = (event) => {
    handleDefaultClick(event);
    deleteTierFormIsOpen.value = true;
}

const hideDeleteTierForm = () => {
    deleteTierFormIsOpen.value = false;
}
</script>

<template>
    <Head title="Tier Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-5">
            <div class="w-full col-span-full lg:col-span-7 flex flex-col p-6 gap-6 rounded-[5px] border border-red-100">
                <div class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100">
                    <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Member Spending</span>
                </div>
                <MemberList 
                    :id="id" 
                    :columns="columns"
                    :rows="customers"
                    :rowType="rowType"
                    :actions="actions"
                    :totalPages="totalPages"
                    :rowsPerPage="rowsPerPage"
                />
            </div>
            <div class="w-full col-span-full lg:col-span-5 flex flex-col p-6 gap-6 rounded-[5px] border border-red-100">
                <div class="flex justify-between">
                    <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Tier Detail</span>
                    <div class="flex gap-2">
                        <EditIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="showEditTierForm"
                        />
                        <DeleteIcon
                            class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                            @click="showDeleteTierForm"
                        />
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-6 h-6 rounded-full bg-gray-500"></div>
                    {{ ranking.name }}
                </div>
                <div class="rounded-[5px] border-primary-100 border-[1px] flex gap-4 p-3 items-center">
                    <div class="bg-primary-50 p-[9px] rounded-[2px]">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 16 16"
                            fill="none"
                        >
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M9.28528 15L8.44243 12.0388C8.73142 11.9694 8.99929 11.8323 9.22095 11.6368C9.3198 11.5496 9.42904 11.5221 9.56094 11.5511C10.4736 11.7515 11.3971 11.2521 11.6732 10.4093L12.7324 14.1305L10.748 13.6488L9.28528 15V15ZM7.27073 2.2713C6.99622 2.5134 6.64198 2.60277 6.27564 2.52231C5.72851 2.40217 5.17557 2.70268 5.01254 3.20884C4.90339 3.5477 4.64403 3.79186 4.28408 3.89461C3.74644 4.04809 3.42719 4.56863 3.55481 5.0837C3.64025 5.42855 3.54535 5.76205 3.28818 6.02048C2.90394 6.4066 2.90394 7.00738 3.28818 7.39354C3.54535 7.65196 3.64025 7.98547 3.55481 8.33032C3.42719 8.84538 3.74644 9.36592 4.28408 9.5194C4.64403 9.62218 4.90339 9.86631 5.01254 10.2052C5.17557 10.7113 5.72851 11.0118 6.27564 10.8917C6.64195 10.8113 6.99622 10.9006 7.27073 11.1427C7.68089 11.5044 8.31907 11.5045 8.72926 11.1427C9.00377 10.9006 9.35804 10.8113 9.72435 10.8917C10.2715 11.0118 10.8244 10.7113 10.9875 10.2052C11.0966 9.86631 11.356 9.62218 11.7159 9.5194C12.2535 9.36592 12.5728 8.84538 12.4452 8.33032C12.3597 7.98547 12.4546 7.65196 12.7118 7.39354C13.096 7.00741 13.0961 6.40663 12.7118 6.02048C12.4546 5.76205 12.3597 5.42855 12.4452 5.0837C12.5728 4.56863 12.2535 4.04809 11.7159 3.89461C11.356 3.79186 11.0966 3.5477 10.9875 3.20884C10.8244 2.70268 10.2715 2.40217 9.72435 2.52231C9.35804 2.60275 9.00377 2.5134 8.72926 2.2713C8.31907 1.90955 7.68092 1.90958 7.27073 2.2713ZM7.99999 4.16983L8.79344 5.94673L10.8338 6.10804L9.28384 7.36754L9.75137 9.24413L7.99999 8.24563L6.24862 9.24413L6.71615 7.36754L5.16622 6.10804L7.20655 5.94673L7.99999 4.16983ZM6.71471 15L5.25201 13.6488L3.26764 14.1305L4.3268 10.4092C4.60286 11.2521 5.52641 11.7515 6.43905 11.551C6.57095 11.5221 6.68019 11.5496 6.77904 11.6368C7.0007 11.8323 7.26857 11.9694 7.55756 12.0388L6.71471 14.9999V15Z"
                                fill="#7E171B"
                            />
                        </svg>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-grey-900 text-base font-medium">
                            Spend
                        </span>
                        <span class="text-primary-900 text-base font-semibold">
                            RM {{ ranking.min_amount }}
                        </span>
                    </div>
                </div>
                <div v-if="ranking.reward === 'active'" class="flex flex-col gap-6 overflow-y-auto" >
                    <div class="rounded-[5px] bg-primary-50 text-primary-900 text-sm font-semibold py-2 px-3">
                        Entry Reward for this Tier
                    </div>
                    <div
                        v-for="(props, index) in rankingRewards"
                        :key="index"
                        class="flex flex-col gap-6 min-w-80"
                    >
                        <!--Discount Amount || Discount Percentage -->
                        <div 
                            class="flex flex-col gap-6" 
                            v-if="props.reward_type === 'Discount (Amount)' || props.reward_type === 'Discount (Percentage)'"
                        >
                            <div class="flex gap-3">
                                <div class="rounded-[1.5px] w-[60px] h-[60px] bg-primary-50 p-[13px]">
                                    <CouponIcon class="size-full"/>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-grey-900 font-medium text-sm">
                                        Entry Reward for {{ ranking.name }}
                                    </span>

                                    <span class="text-primary-950 font-medium text-base">
                                        <template v-if="props.reward_type === 'Discount (Amount)'">
                                            RM {{ props.discount }} Discount
                                        </template>
                                        <template v-else>
                                            {{ props.discount }} % Discount
                                        </template>
                                    </span>
                                    <span class="text-primary-900 font-normal text-[10px]">
                                        <template v-if="props.min_purchase_amount !== null">
                                            Min. spend: RM {{ props.min_purchase_amount }}
                                        </template>
                                        <template v-else>
                                            No min. spend
                                        </template>
                                    </span>
                                    <span class="text-grey-400 font-normal text-[10px]">
                                        Valid Period:
                                        {{
                                            calculateValidPeriod(
                                                props.valid_period_from,
                                                props.valid_period_to // Ensure UTC format
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bonus Point -->
                        <div class="flex flex-col" v-if="props.reward_type === 'Bonus Point'">
                            <div class="flex gap-3">
                                <div class="rounded-[1.5px] w-[60px] h-[60px] bg-primary-50 p-[13px]">
                                    <PointsIcon class="size-full text-primary-900"/>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-grey-900 font-medium text-sm">
                                        Entry Reward for {{ ranking.name }}
                                    </span>
                                    <span class="text-primary-950 font-medium text-base">
                                        {{ props.bonus_point }} Bonus Point
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Free Item -->
                        <div class="flex flex-col" v-if="props.reward_type === 'Free Item'">
                            <div class="flex gap-3">
                                <div class="rounded-[1.5px] w-[60px] h-[60px] bg-primary-50 p-[13px]">
                                    <ProductQualityIcon class="size-full"/>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-grey-900 font-medium text-sm">
                                        Entry Reward for {{ ranking.name }}
                                    </span>
                                    <span class="text-primary-950 font-medium text-base">
                                        {{ props.item_qty }} x {{ props.free_item }}
                                    </span>
                                    <span class="text-grey-400 font-normal text-[10px]">
                                        Valid Period:
                                        {{
                                            calculateValidPeriod(
                                                props.valid_period_from,
                                                props.valid_period_to
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Modal 
                :title="'Edit Tier'"
                :show="editTierFormIsOpen" 
                :maxWidth="'md'" 
                @close="hideEditTierForm"
            >
                <template v-if="props.id">
                    <EditTier
                        :tier="ranking"
                        :inventoryItems="inventoryItems" 
                        @close="hideEditTierForm"
                    />
                </template>
            </Modal>
            <Modal 
                :show="deleteTierFormIsOpen" 
                :maxWidth="'2xs'" 
                :closeable="true" 
                :deleteConfirmation="true"
                :deleteUrl="`/loyalty-programme/tiers/destroy/${props.id}`"
                :confirmationTitle="'Delete this tier?'"
                :confirmationMessage="'All the member in this tier will not be entitled to any tier. Are you sure you want to delete this tier?'"
                @close="hideDeleteTierForm"
                v-if="props.id"
            />
        </div>
    </AuthenticatedLayout>
</template>
