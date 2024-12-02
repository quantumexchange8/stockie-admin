<script setup>
import { TierIllust, UndetectableIllus } from '@/Components/Icons/illus';
import { CouponIcon, CrownImage, PointsIcon, ProductQualityIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import Toast from '@/Components/Toast.vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed, onMounted, ref } from 'vue';

const props = defineProps ({
    customer: Object
})

const rewards = ref([]);
const tabs = ref(['Active', 'Redeemed']);

const getTierRewards = async () => {
    try {
        const response = await axios.get(`customer/tierRewards/${props.customer.id}`);
        rewards.value = response.data;
    } catch (error) {
        console.error(error)
    } finally {

    }
}

onMounted(() => getTierRewards());

const getRewardTitle = (reward) => {
    switch (reward.ranking_reward.reward_type) {
        case 'Discount (Amount)': return `RM ${reward.ranking_reward.discount}  Discount`;
        case 'Discount (Percentage)': return `${reward.ranking_reward.discount} % Discount`;
        case 'Bonus Point': return `${reward.ranking_reward.bonus_point}  Bonus Point`;
        case 'Free Item': return `${reward.ranking_reward.item_qty} x ${reward.ranking_reward.product.product_name}`;
    }
};

const activeTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Active'));

const redeemedTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Redeemed'));

</script>

<template>
    <div class="flex flex-col p-6 items-center gap-y-9 shrink-0 max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium">Current Tier</span>
                <template v-if="customer.rank">
                    <div class="flex flex-col justify-center items-center gap-2">
                        <img 
                            :src="customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt=""
                            class="size-[48px]"
                        />
                        <span class="text-primary-950 text-base font-medium">{{ customer.rank.name }}</span>
                    </div>
                </template>
                <template v-else>
                    <span class="text-primary-900 text-lg font-medium"> - </span>
                </template>
                <TierIllust class="absolute flex-shrink-0"/>
            </div>
        </div>

        <div class="flex flex-col items-start gap-y-3 self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-semibold py-3">Tier Rewards</span>
            <TabView :tabs="tabs">
                <template #active>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24.7rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <Toast 
                            inline
                            severity="info"
                            summary="You can redeem the rewards only when the customer has checked-in to one table/room."
                            :closable="false"
                        />
                        <template v-if="activeTierRewards.length > 0">
                            <div class="p-6 items-center justify-between self-stretch rounded-[5px] bg-white" v-for="reward in activeTierRewards">
                                <div class="flex items-start gap-x-3 self-stretch">
                                    <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-primary-50">
                                        <CouponIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)'"/>
                                        <PointsIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Bonus Point'"/>
                                        <ProductQualityIcon class="text-primary-900" v-if="reward.ranking_reward.reward_type === 'Free Item'"/>
                                    </div>
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">Entry Reward for {{ customer.rank.name }}</span>
                                        <span class="self-stretch text-primary-950 text-base font-medium">{{ getRewardTitle(reward) }} </span>
                                        <div class="flex items-center gap-1 self-stretch">
                                            <template v-if="reward.ranking_reward.min_purchase === 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">Min spend: RM {{ reward.ranking_reward.min_purchase_amount }}</span>
                                            </template>
                                            <template v-if="reward.ranking_reward.min_purchase !== 'active' && (reward.ranking_reward.reward_type === 'Discount (Amount)'|| reward.ranking_reward.reward_type === 'Discount (Percentage)')">
                                                <span class="text-primary-900 text-2xs font-normal">No min. spend</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex w-full flex-col items-center justify-center gap-5">
                                <UndetectableIllus />
                                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                            </div>
                        </template>
                    </div>
                </template>

                <template #redeemed>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24.7rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <template v-if="redeemedTierRewards.length > 0">
                            <div class="grid grid-cols-12 p-6 items-center justify-between self-stretch rounded-[5px] bg-white" v-for="reward in redeemedTierRewards">
                                <div class="flex items-start gap-x-3 self-stretch col-span-8 border-r-[0.5px] border-grey-200">
                                    <div class="flex w-[60px] h-[60px] justify-center items-center rounded-[1.5px] border border-solid border-primary-100 bg-grey-100">
                                        <CouponIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Discount (Amount)' || reward.ranking_reward.reward_type === 'Discount (Percentage)'"/>
                                        <PointsIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Bonus Point'"/>
                                        <ProductQualityIcon class="text-grey-300" v-if="reward.ranking_reward.reward_type === 'Free Item'"/>
                                    </div>
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 self-stretch text-grey-900 text-ellipsis text-sm font-medium">Entry Reward for {{ customer.rank.name }}</span>
                                        <span class="self-stretch text-primary-950 text-base font-medium">{{ getRewardTitle(reward) }} </span>
                                        <span class="text-grey-600 text-2xs font-normal">Redeemed on {{ dayjs(reward.updated_at).format('DD/MM/YYYY') }}</span>
                                    </div>
                                </div>

                                <p class="text-grey-300 text-base font-semibold col-span-4 text-center">Redeemed</p>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex w-full flex-col items-center justify-center gap-5">
                                <UndetectableIllus />
                                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                            </div>
                        </template>
                    </div>
                </template>
            </TabView>
        </div>
    </div>
</template>
