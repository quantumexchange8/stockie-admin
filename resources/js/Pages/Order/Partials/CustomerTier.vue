<script setup>
import { PointsIllust } from '@/Components/Icons/illus';
import { CouponIcon, HistoryIcon, PointsIcon, ProductQualityIcon, TimesIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { computed, onMounted, ref, watch } from 'vue';
import CustomerRedemptionHistory from './CustomerRedemptionHistory.vue';
import Button from '@/Components/Button.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useCustomToast } from '@/Composables/index.js';
import TabView from '@/Components/TabView.vue';

// *DNR*
// Calculates difference in month
// const calculateValidPeriod = (fromDate, toDate) => dayjs(toDate).diff(dayjs(fromDate), 'month');

const props = defineProps({
    customer: Object,
    matchingOrderDetails: Object,
    orderId: Number,
    tableStatus: String,
})

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'update:customerPoint']);

const tabs = ref(['Active', 'Redeemed']);
const customer = ref(props.customer);
const selectedCustomer = ref(null);
const rewards = ref([]);
const isPointHistoryDrawerOpen = ref(false);
const selectedItem = ref();
const op = ref(null);
const customerNewPoint = ref(props.customer.point);

const getCustomerRewards = async () => {
    try {
        const response = await axios.get(route('orders.customer.tier.getCustomerTierRewards', props.customer.id));
        rewards.value = response.data;
    } catch(error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => getCustomerRewards())

const form = useForm({
    user_id: userId.value,
    customer_id: customer.value.id,
    redeem_qty: 0,
    matching_order_details: props.matchingOrderDetails,
    selected_item: null
});

const submit = async () => { 
    form.processing = true;
    try {
        const redemptionResponse = await axios.post(`/order-management/orders/customer/point/redeemItemToOrder/${props.orderId}`, form);
        let pointsUsed = redemptionResponse.data.pointSpent;
        customerNewPoint.value = redemptionResponse.data.customerPoint;

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Product redeemed successfully.',
                detail: `${pointsUsed} pts has been deducted from customer's point wallet.`,
            });
        }, 200);

        emit('fetchZones');
        emit('update:customerPoint', customerNewPoint.value);
        emit('close');
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
};

const openHistoryDrawer = (id) => {
    isPointHistoryDrawerOpen.value = true;
    selectedCustomer.value = id;
}

const closeDrawer = () => isPointHistoryDrawerOpen.value = false;

const openOverlay = (event, item) => {
    selectedItem.value = item;
    form.selected_item = selectedItem.value;
    if (selectedItem.value) op.value.show(event);
};

const closeOverlay = () => {
    selectedItem.value = null;
    form.user_id = userId.value;
    form.redeem_qty = 0;
    form.selected_item = null;

    if (op.value) op.value.hide();
};

const getRewardTitle = (rankingReward) => {
    switch (rankingReward.reward_type) {
        case 'Discount (Amount)': return `RM ${rankingReward.discount}  Discount`;
        case 'Discount (Percentage)': return `${rankingReward.discount} % Discount`;
        case 'Bonus Point': return `${rankingReward.bonus_point}  Bonus Point`;
        case 'Free Item': return `${rankingReward.item_qty} x ${rankingReward.item_name}`;
    }
};

// *DNR*
// const calculateValidPeriod = (validFrom, validTo) => {
//     if (validFrom !== null && validTo !== null){
//         const startDate = new Date(validFrom);
//         const endDate = new Date(validTo);
    
//         // Adjust for UTC+8 timezone
//         startDate.setHours(startDate.getHours() + 8);
//         endDate.setHours(endDate.getHours() + 8);
    
//         const startDateString = startDate.toLocaleDateString("en-MY"); 
//         const endDateString = endDate.toLocaleDateString("en-MY"); 
    
//         const diffInMonths =
//             (endDate.getFullYear() - startDate.getFullYear()) * 12 +
//             (endDate.getMonth() - startDate.getMonth());

//         switch (diffInMonths) {
//             case 1: return "1 months starting from entry date";;
//             case 3: return "3 months starting from entry date";;
//             case 6: return "6 months starting from entry date";;
//             case 12: return "12 months starting from entry date";;
//             default: return `${startDateString} to ${endDateString}`;
//         }

//     }
// };

const isFormValid = computed(() => ['redeem_qty'].every(field => form[field]) && !form.processing);
</script>

<template>
    <div class="flex flex-col p-6 items-center gap-y-9 shrink-0 overflow-y-auto">
        <!-- Current points -->
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium">Current Tier</span>
                <div class="flex flex-col justify-center items-center gap-2">
                    <span class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-[40px] font-normal"></span>
                    <span class="text-primary-950 text-base font-medium">image</span>
                </div>
                <PointsIllust class="absolute flex-shrink-0"/>
            </div>
        </div>

        <!-- Redeem product -->
         <div class="flex flex-col items-start gap-y-3 self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-semibold py-3">Redeem Product</span>

            <TabView :tabs="tabs">
                <template #active>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24.7rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <!-- <div class="flex flex-col justify-end items-start self-stretch" v-for="item in rewards" :key="item.id"> -->
                            <div class="grid grid-cols-12 p-6 items-center justify-between self-stretch rounded-[5px] bg-white" v-for="reward in rewards">
                                <div class="flex items-start gap-x-3 self-stretch col-span-8 border-r-[0.5px] border-grey-200">
                                    <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-primary-50">
                                        <CouponIcon v-if="reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)'"/>
                                        <PointsIcon v-if="reward.ranking_reward.reward_type === 'Bonus Point'"/>
                                        <ProductQualityIcon v-if="reward.ranking_reward.reward_type === 'Free Item'"/>
                                    </div>
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">Entry Reward for {{ customer.rank.name }}</span>
                                        <span class="self-stretch text-primary-950 text-base font-medium">{{ getRewardTitle(reward.ranking_reward) }} </span>
                                        <div class="flex items-center gap-1 self-stretch">
                                            <template v-if="reward.ranking_reward.min_purchase === 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">Min spend: RM {{ reward.ranking_reward.min_purchase_amount }}</span>
                                            </template>
                                            <template v-if="reward.ranking_reward.min_purchase !== 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)'|| reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">No min. spend</span>
                                            </template>
                                        </div>
                                        <!-- *DNR* -->
                                        <!-- <template v-if="reward.reward_type !== 'Bonus Point'">
                                            <span class="self-stretch text-grey-400 text-2xs font-normal">Valid Period: {{ calculateValidPeriod(reward.valid_period_from, reward.valid_period_to) }}</span>
                                        </template> -->
                                    </div>
                                </div>

                                <p class="text-primary-900 text-base font-semibold col-span-4 text-center">Redeem Now</p>
                            </div>
                        <!-- </div> -->
                    </div>
                </template>
                <template #redeemed>
                </template>
            </TabView>

         </div>
    </div>

    <!-- history drawer -->
     <RightDrawer
        :header="'History'"
        :previousTab='true'
        :show="isPointHistoryDrawerOpen"
        @close="closeDrawer"
    >
        <template v-if="customer">
            <CustomerRedemptionHistory :customerId="customer.id"/>
        </template>
    </RightDrawer>

    <!-- Redeem item and add to order -->
    <!-- <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="submit">
                <div class="flex flex-col gap-6 w-96">
                    <div class="flex items-center justify-between">
                        <span class="text-primary-950 text-center text-md font-medium">Select Redeem Quantity</span>
                        <TimesIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="closeOverlay"
                        />
                    </div>
                    <div class="flex flex-col gap-y-6">
                        <div class="w-full flex gap-x-2 items-center justify-between">
                            <div class="flex gap-x-3 items-center">
                                <img 
                                    :src="selectedItem.image ? selectedItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="rounded-[1.5px] size-[60px]"
                                >
                                <p class="text-base text-grey-900 font-medium">
                                    {{ selectedItem.product_name }}
                                </p>
                            </div>
                            <p class="text-primary-800 text-md font-medium pr-3">{{ selectedItem.point }} pts</p>
                        </div>

                        <NumberCounter
                            :inputName="`redeem_qty`"
                            :maxValue="Math.floor(customer.point / selectedItem.point)"
                            v-model="form.redeem_qty"
                        />
                    </div>

                    <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            @click="closeOverlay"
                        >
                            Cancel
                        </Button>
                        <Button
                            :size="'lg'"
                            :disabled="!isFormValid || !matchingOrderDetails.tables"
                        >
                            Redeem Now
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </OverlayPanel> -->
</template>
