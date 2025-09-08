<script setup>
import { GiftCardIllus, TierIllust, UndetectableIllus } from '@/Components/Icons/illus';
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
import Modal from '@/Components/Modal.vue';
import dayjs from 'dayjs';
import { wTrans } from 'laravel-vue-i18n';

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
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'fetchOrderDetails']);

const tabs = ref([
    { key: 'Active', title: wTrans('public.active'), disabled: false },
    { key: 'Redeemed', title: wTrans('public.redeemed'), disabled: false },
]);
const customer = ref(props.customer);
const selectedCustomer = ref(null);
const rewards = ref([]);
const isPointHistoryDrawerOpen = ref(false);
const isRedeemRewardOpen = ref(false);
const selectedItem = ref();
const op = ref(null);
const selectedReward = ref(null);

const getCustomerRewards = async () => {
    try {
        const rewardsResponse = await axios.get(route('orders.customer.tier.getCustomerTierRewards', props.customer.id));
        rewards.value = rewardsResponse.data;
    } catch(error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => getCustomerRewards())

const form = useForm({
    user_id: userId.value,
    customer_id: customer.value.id,
    matching_order_details: props.matchingOrderDetails,
    customer_reward_id: ''
});

const submit = async () => { 
    form.processing = true;
    form.customer_reward_id = selectedReward.value.id;

    try {
        const redemptionResponse = await axios.post(`/order-management/orders/customer/reward/redeemEntryRewardToOrder/${props.orderId}`, form);

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: redemptionResponse.data,
            });
        }, 200);

        emit('fetchZones');
        emit('fetchOrderDetails');
        emit('close', true);
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

const getRewardTitle = (reward) => {
    switch (reward.ranking_reward.reward_type) {
        case 'Discount (Amount)': return `RM ${reward.ranking_reward.discount} ${wTrans('public.discount').value}`;
        case 'Discount (Percentage)': return `${reward.ranking_reward.discount} % ${wTrans('public.discount').value}`;
        case 'Bonus Point': return `${reward.ranking_reward.bonus_point} ${wTrans('public.bonus_point').value}`;
        case 'Free Item': return `${reward.ranking_reward.item_qty} x ${reward.ranking_reward.product.product_name}`;
    }
};

const openModal = (reward) => {
    // if (reward.ranking_reward.min_purchase === 'inactive' 
    //     || (reward.ranking_reward.min_purchase === 'active' 
    //     && reward.ranking_reward.min_purchase_amount <= parseFloat(props.matchingOrderDetails.amount))
    //     && !(tableStatus === 'Pending Clearance' && reward.ranking_reward.reward_type !== 'Free Item')) {
    //     selectedReward.value = reward;
    //     isRedeemRewardOpen.value = true;
    // }
    
    if (props.tableStatus === 'Pending Clearance') {
        if (reward.ranking_reward.reward_type === 'Free Item') {
            selectedReward.value = reward;
            isRedeemRewardOpen.value = true;
        } 
    } else {
        if (reward.ranking_reward.min_purchase === 'inactive' || reward.ranking_reward.min_purchase_amount <= parseFloat(props.matchingOrderDetails.amount)) {
            selectedReward.value = reward;
            isRedeemRewardOpen.value = true;
        }
    }
};

const closeModal = () => {
    isRedeemRewardOpen.value = false;
    setTimeout(() => selectedReward.value = null, 200);
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

const activeTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Active'));

const redeemedTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Redeemed'));

const isNotRedeemable = (reward) => {
    return (reward.ranking_reward.min_purchase === 'active' && reward.ranking_reward.min_purchase_amount > parseFloat(props.matchingOrderDetails.amount)) 
        || (props.tableStatus === 'Pending Clearance' && reward.ranking_reward.reward_type !== 'Free Item') 
        || (reward.ranking_reward.reward_type === 'Free Item' && reward.ranking_reward.product.availability === 'Unavailable')
        || (reward.ranking_reward.reward_type === 'Free Item' && reward.ranking_reward.product.stock_left < reward.ranking_reward.item_qty);
}

// const isFormValid = computed(() => ['redeem_qty'].every(field => form[field]) && !form.processing);
</script>

<template>
    <div class="flex flex-col max-h-[calc(100dvh-4rem)] p-6 items-center shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium text-center">{{ $t('public.current_tier') }}</span>
                <div class="flex flex-col justify-center items-center gap-2">
                    <template v-if="customer.rank">
                        <div class="flex flex-col justify-center items-center gap-[10px]">
                            <img 
                                :src="customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="size-[48px]"
                            >
                        </div>
                        <span class="text-primary-950 text-base font-medium">{{ customer.rank.name }}</span>
                    </template>
                    <template v-else>
                        <span class="text-primary-900 text-lg font-medium"> - </span>
                    </template>
                </div>
                <TierIllust class="absolute flex-shrink-0"/>
            </div>
        </div>

        <!-- Tier Rewards -->
        <div class="flex flex-col items-start self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-semibold py-3">{{ $t('public.tier_rewards') }}</span>

            <TabView :tabs="tabs">
                <template #active>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <template  v-if="activeTierRewards.length > 0">
                            <div class="grid grid-cols-12 p-6 items-center justify-between self-stretch rounded-[5px] bg-white" v-for="reward in activeTierRewards">
                                <div class="flex items-start gap-x-3 self-stretch col-span-8 border-r-[0.5px] border-grey-200">
                                    <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-primary-50">
                                        <CouponIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)'"/>
                                        <PointsIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Bonus Point'"/>
                                        <ProductQualityIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Free Item'"/>
                                    </div>
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">
                                            {{ $t('public.entry_reward_for', { rank_name: reward.ranking_reward.ranking.name }) }}
                                        </span>
                                        <span class="self-stretch text-primary-950 text-base font-medium">{{ getRewardTitle(reward) }} </span>
                                        <div class="flex items-center gap-1 self-stretch">
                                            <template v-if="reward.ranking_reward.min_purchase === 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">{{ $t('public.min_spend') }}: RM {{ reward.ranking_reward.min_purchase_amount }}</span>
                                            </template>
                                            <template v-if="reward.ranking_reward.min_purchase !== 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)'|| reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">{{ $t('public.no_min_spend') }}</span>
                                            </template>
                                        </div>
                                        <!-- *DNR* -->
                                        <!-- <template v-if="reward.reward_type !== 'Bonus Point'">
                                            <span class="self-stretch text-grey-400 text-2xs font-normal">Valid Period: {{ calculateValidPeriod(reward.valid_period_from, reward.valid_period_to) }}</span>
                                        </template> -->
                                    </div>
                                </div>

                                <p 
                                    :class="[
                                        'text-base font-semibold col-span-4 text-center',
                                        isNotRedeemable(reward) ? 'text-grey-300 cursor-not-allowed' : 'text-primary-900 cursor-pointer'
                                    ]" 
                                    @click="isNotRedeemable(reward) ? '' : openModal(reward)"
                                >
                                    {{ $t('public.action.redeem_now') }}
                                </p>
                            </div>
                        </template>
                        <template v-else>
                            <UndetectableIllus class="flex-shrink-0" />
                            <span class="text-sm font-medium text-primary-900">{{ $t('public.empty.no_data') }}</span>
                        </template>
                    </div>
                </template>

                <template #redeemed>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <template  v-if="redeemedTierRewards.length > 0">
                            <div class="grid grid-cols-12 p-6 items-center justify-between self-stretch rounded-[5px] bg-white" v-for="reward in redeemedTierRewards">
                                <div class="flex items-start gap-x-3 self-stretch col-span-8 border-r-[0.5px] border-grey-200">
                                    <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-grey-100">
                                        <CouponIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)'"/>
                                        <PointsIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Bonus Point'"/>
                                        <ProductQualityIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Free Item'"/>
                                    </div>
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">
                                            {{ $t('public.entry_reward_for', { rank_name: reward.ranking_reward.ranking.name }) }}
                                        </span>
                                        <span class="self-stretch text-primary-950 text-base font-medium">{{ getRewardTitle(reward) }} </span>
                                        <span class="text-grey-600 text-2xs font-normal">{{ $t('public.redeemed_on') }} {{ dayjs(reward.updated_at).format('DD/MM/YYYY') }}</span>
                                        <!-- *DNR*
                                        <template v-if="reward.reward_type !== 'Bonus Point'">
                                            <span class="self-stretch text-grey-400 text-2xs font-normal">Valid Period: {{ calculateValidPeriod(reward.valid_period_from, reward.valid_period_to) }}</span>
                                        </template> -->
                                    </div>
                                </div>

                                <p class="text-grey-300 text-base font-semibold col-span-4 text-center">{{ $t('public.redeemed') }}</p>
                            </div>
                        </template>
                        <template v-else>
                            <UndetectableIllus class="flex-shrink-0" />
                            <span class="text-sm font-medium text-primary-900">{{ $t('public.empty.no_data') }}</span>
                        </template>
                    </div>
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

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :withHeader="false"
        :show="isRedeemRewardOpen"
        class="[&>div>div>div]:!p-0"
        @close="closeModal"
    >
        <template v-if="selectedReward">
            <form novalidate @submit.prevent="submit">
                <div class="flex flex-col gap-9">
                    <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                        <GiftCardIllus/>
                    </div>
                    <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6">
                        <p class="text-center text-primary-900 text-lg font-medium self-stretch">
                            {{ 
                                matchingOrderDetails.voucher_id && ['Discount (Percentage)', 'Discount (Amount)'].includes(selectedReward.ranking_reward.reward_type)
                                    ? $t('public.order.existing_applied_reward')  
                                    : $t('public.order.redeem_reward') 
                            }}
                        </p>
                        <p class="text-center text-grey-900 text-base font-medium self-stretch">
                            {{ 
                                matchingOrderDetails.voucher_id && ['Discount (Percentage)', 'Discount (Amount)'].includes(selectedReward.ranking_reward.reward_type)
                                    ? `${$t('public.order.replace_redeem_reward_message1')} [${getRewardTitle(selectedReward)}] ${$t('public.order.redeem_reward_message2')}?`
                                    : `${$t('public.order.redeem_reward_message1')} [${getRewardTitle(selectedReward)}] ${$t('public.order.redeem_reward_message2')}?`
                            }}
                        </p>
                    </div>
                    <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            @click="closeModal"
                        >
                            {{ $t('public.action.cancel') }}
                        </Button>
                        <Button 
                            :size="'lg'"
                            :disabled="form.processing"
                        >
                            {{ $t('public.action.yes_sure') }}
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </Modal>
</template>
