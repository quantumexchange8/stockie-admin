<script setup>
import { Head } from "@inertiajs/vue3";
import { defineProps, ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { EditIcon, DeleteIcon, CouponIcon, PointsIcon, ProductQualityIcon, MinSpendIcon} from "@/Components/Icons/solid";
import EditTier from "./EditTier.vue";
import MemberList from "./MemberList.vue";
import Toast from '@/Components/Toast.vue'
import { transactionFormat } from '@/Composables';
import MemberSpending from "./MemberSpending.vue";

const props = defineProps({
    id: String,
    tier: Object,
    reward: Array,
    customers: Array,
    products: Array,
    logos: Array,
    inventoryItems: Array,
    names: Array,
    spendings: Array
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

const rowsPerPage = ref(11);
const editTierFormIsOpen = ref(false);
const deleteTierFormIsOpen = ref(false);
const selected = ref('This month');
const isLoading = ref(false);
const names = ref(props.names);
const spendings = ref(props.spendings);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const { formatAmount } = transactionFormat();

const totalPages = computed(() => {
    return Math.ceil(props.customers.length / rowsPerPage.value);
})

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "name",
};

const actions = {
    view: () => ``,
    edit: () => '',
    delete: () => ``,
};

const filterMemberSpending = async (filters) => {
    isLoading.value = true;
    try {
        const response = await axios.get('/loyalty-programme/filterMemberSpending', {
            method: 'GET',
            params: {
                id: props.id,
                selected: filters,
            }
        });
        names.value = response.data.names;
        spendings.value = response.data.spendings;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

const applyFilter = (filter) => {
    selected.value = filter;
    filterMemberSpending(selected.value);
};

const emit = defineEmits(["close"]);

const calculateValidPeriod = (validFrom, validTo) => {
    if (validFrom !== null && validTo !== null){
        const startDate = new Date(validFrom);
        const endDate = new Date(validTo);
    
        // Adjust for UTC+8 timezone
        startDate.setHours(startDate.getHours() + 8);
        endDate.setHours(endDate.getHours() + 8);
    
        const startDateString = startDate.toLocaleDateString("en-MY"); 
        const endDateString = endDate.toLocaleDateString("en-MY"); 
    
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

    }
};

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const showEditTierForm = (event) => {
    isDirty.value = false;
    handleDefaultClick(event);
    editTierFormIsOpen.value = true;
}

const closeModal = (status) => {
    switch(status) {
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                editTierFormIsOpen.value = false;
                deleteTierFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            editTierFormIsOpen.value = false;
            deleteTierFormIsOpen.value = false;
            break;
        }
    }
}

const showDeleteTierForm = (event) => {
    handleDefaultClick(event);
    deleteTierFormIsOpen.value = true;
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

        <Toast />

        <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-5">
            
            <!-- member list -->
            <div class="w-full col-span-full lg:col-span-7 flex flex-col p-6 gap-6 rounded-[5px] border border-red-100">
                <div class="flex flex-col p-6 gap-6 items-center rounded-[5px] border border-red-100 h-full">
                    <MemberSpending 
                        :names="names"
                        :spendings="spendings"
                        :isLoading="isLoading"
                        @isLoading="isLoading=$event"
                        @applyFilter="applyFilter"
                    />
                </div>
                <MemberList 
                    :id="id" 
                    :tierName="props.tier.name"
                    :columns="columns"
                    :rows="props.customers"
                    :rowType="rowType"
                    :actions="actions"
                    :totalPages="totalPages"
                    :rowsPerPage="rowsPerPage"
                    class="h-full"
                />
            </div>

            <!-- tier detail -->
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
                <div class="flex gap-4 items-center self-stretch">
                    <img 
                        :src="props.tier.icon ? props.tier.icon : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                        alt="TierIconImage" 
                        class="w-6 h-6 rounded-full"
                    />
                    <span class="flex-[1_0_0] text-grey-900 text-md font-medium">{{ props.tier.name }}</span>
                </div>
                <div class="rounded-[5px] border-primary-100 border-[1px] flex gap-4 p-3 items-center">
                    <div class="bg-primary-50 p-[9px] rounded-[2px]">
                        <MinSpendIcon />
                    </div>
                    <div class="flex gap-2">
                        <span class="text-grey-900 text-base font-medium">
                            Spend
                        </span>
                        <span class="text-primary-900 text-base font-semibold">
                            RM {{ formatAmount(props.tier.min_amount) }}
                        </span>
                    </div>
                </div>

                <div class="flex px-3 py-2 justify-between items-center self-stretch rounded-[5px] bg-primary-50" v-if="props.reward.length > 0">
                    <span class="flex-[1_0_0] text-primary-900 text-sm font-semibold">Entry Reward for this Tier</span>
                </div>

                <!-- entry reward for this tier -->
                <div class="flex flex-col items-center gap-6 self-stretch">
                    <div class="flex items-start gap-3 self-stretch" v-for="reward in props.reward">
                        <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-primary-50">
                            <template v-if="reward.reward_type === 'Discount (Amount)' || reward.reward_type === 'Discount (Percentage)'">
                                <CouponIcon class="text-primary-900" />
                            </template>
                            <template v-if="reward.reward_type === 'Bonus Point'">
                                <PointsIcon class="text-primary-900" />
                            </template>
                            <template v-if="reward.reward_type === 'Free Item'">
                                <ProductQualityIcon class="text-primary-900" />
                            </template>
                        </div>
                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                            <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">Entry Reward for {{ props.tier.name }}</span>
                            <span class="self-stretch text-primary-950 text-base font-medium">
                                <template v-if="reward.reward_type === 'Discount (Amount)'">
                                    RM {{ reward.discount }} Discount
                                </template>
                                <template v-if="reward.reward_type === 'Discount (Percentage)'">
                                    {{ reward.discount }}% Discount
                                </template>
                                <template v-if="reward.reward_type === 'Bonus Point'">
                                    {{ reward.bonus_point }} Bonus Point
                                </template>
                                <template v-if="reward.reward_type === 'Free Item'">
                                    {{ reward.item_qty }}x {{ reward.item_name }}
                                </template>
                            </span>
                            <div class="flex items-center gap-1 self-stretch">
                                <template v-if="reward.min_purchase === 'active' && (reward.reward_type === 'Discount (Amount)' || reward.reward_type === 'Discount (Percentage)')">
                                    <span class="text-primary-900 text-2xs font-normal">Min spend: RM {{ reward.min_purchase_amount }}</span>
                                </template>
                                <template v-if="reward.min_purchase !== 'active' && (reward.reward_type === 'Discount (Percentage)'|| reward.reward_type === 'Discount (Percentage)')">
                                    <span class="text-primary-900 text-2xs font-normal">No min. spend</span>
                                </template>
                            </div>
                            <!-- <template v-if="reward.reward_type !== 'Bonus Point'">
                                <span class="self-stretch text-grey-400 text-2xs font-normal">Valid Period: {{ calculateValidPeriod(reward.valid_period_from, reward.valid_period_to) }}</span>
                            </template> -->
                        </div>
                    </div>
                </div>
                
            </div>
            <Modal 
                :title="'Edit Tier'"
                :show="editTierFormIsOpen" 
                :maxWidth="'md'" 
                @close="closeModal('close')"
            >
                <template v-if="props.id">
                    <EditTier
                        :tier="props.tier"
                        :logos="logos"
                        :inventoryItems="props.reward"
                        :items="products"
                        @isDirty="isDirty=$event"
                        @close="closeModal"
                    />

                    <Modal
                        :unsaved="true"
                        :maxWidth="'2xs'"
                        :withHeader="false"
                        :show="isUnsavedChangesOpen"
                        @close="closeModal('stay')"
                        @leave="closeModal('leave')"
                    >
                    </Modal>
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
                @close="closeModal('leave')"
                v-if="props.id"
            />
        </div>
    </AuthenticatedLayout>
</template>
