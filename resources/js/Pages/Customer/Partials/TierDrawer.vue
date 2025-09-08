<script setup>
import { TierIllust, UndetectableIllus } from '@/Components/Icons/illus';
import { CouponIcon, CrownImage, PointsIcon, ProductQualityIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import Toast from '@/Components/Toast.vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed, onMounted, ref } from 'vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps ({
    customer: Object
})

const rewards = ref([]);
const tabs = ref([
    { key: 'Active', title: wTrans('public.active'), disabled: false },
    { key: 'Redeemed', title: wTrans('public.redeemed'), disabled: false },
]);

const getTierRewards = async () => {
    try {
        const response = await axios.get(`/customer/tierRewards/${props.customer.id}`);
        rewards.value = response.data;
    } catch (error) {
        console.error(error)
    } finally {

    }
}

onMounted(() => getTierRewards());

const getRewardTitle = (reward) => {
    switch (reward.ranking_reward.reward_type) {
        case 'Discount (Amount)': return `RM ${reward.ranking_reward.discount} ${wTrans('public.discount').value}`;
        case 'Discount (Percentage)': return `${reward.ranking_reward.discount} % ${wTrans('public.discount').value}`;
        case 'Bonus Point': return `${reward.ranking_reward.bonus_point} ${wTrans('public.bonus_point').value}`;
        case 'Free Item': return `${reward.ranking_reward.item_qty} x ${reward.ranking_reward.product.product_name}`;
    }
};

const activeTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Active'));

const redeemedTierRewards = computed(() => rewards.value.filter((reward) => reward.status === 'Redeemed'));

</script>

<template>
    <div class="flex flex-col p-6 items-center shrink-0 max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium">{{ $t('public.current_tier') }}</span>
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

        <div class="flex flex-col items-start self-stretch">
            <span class="flex-[1_0_0] text-primary-900 text-md font-semibold py-3">{{ $t('public.tier_rewards') }}</span>
            <TabView :tabs="tabs">
                <template #active>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <Toast 
                            inline
                            severity="info"
                            :summary="$t('public.customer.redeem_reward_info')"
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
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex w-full flex-col items-center justify-center gap-5">
                                <UndetectableIllus />
                                <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                            </div>
                        </template>
                    </div>
                </template>

                <template #redeemed>
                    <div class="flex flex-col items-center self-stretch max-h-[calc(100dvh-24rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pr-1 gap-y-4">
                        <template v-if="redeemedTierRewards.length > 0">
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
                                    </div>
                                </div>

                                <p class="text-grey-300 text-base font-semibold col-span-4 text-center">{{ $t('public.redeemed') }}</p>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex w-full flex-col items-center justify-center gap-5">
                                <UndetectableIllus />
                                <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                            </div>
                        </template>
                    </div>
                </template>
            </TabView>
        </div>
    </div>
</template>
