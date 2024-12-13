<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { PointsIcon, ReceiptIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    customerId: Number,
})
const customerId = ref(props.customerId);
const redeemHistory = ref([]);
const tabs = ref(['All', 'Earned', 'Used']);
const isLoading = ref(false);

const getRedeemHistory = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/order-management/orders/customer/point/getCustomerPointHistories/${customerId.value}`);
        redeemHistory.value = response.data;
    } catch(error){
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const formatPoints = (points) => {
    const pointsStr = points.toString();
    return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const earnedHistories = computed(() => redeemHistory.value.filter(item => item.type === 'Earned'));
const usedHistories = computed(() => redeemHistory.value.filter(item => item.type === 'Used'));

const getRecordTitle = (item) => {
    switch (item.type) {
        case 'Earned': return item.point_type === 'Order' ? item.payment.order.order_no : `${item.customer.rank.name} Entry Reward`;
        case 'Used': return item.point_type === 'Redeem' ? item.redeemable_item.product_name : '';
    }
}

onMounted(() => getRedeemHistory())
</script>

<template>
    <div class="!w-full flex flex-col p-6 self-stretch max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <TabView :tabs="tabs">
            <template #all>
                <template v-if="redeemHistory.length > 0">
                    <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="item in redeemHistory" :key="item.id">
                        <div class="flex items-center gap-3 self-stretch rounded-[5px] pt-6">
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                                <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-[2px] bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="flex justify-between items-center self-stretch gap-x-3">
                                    <div class="w-3/4 flex flex-col items-start gap-3">
                                        <div class="flex items-start gap-3 self-stretch">
                                            <div class="flex w-[60px] h-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border-[1.5px] border-solid border-primary-200 bg-primary-50" v-if="item.type === 'Earned'">
                                                <ReceiptIcon class="text-primary-900" v-if="item.point_type === 'Order'" />
                                                <PointsIcon class="text-primary-900" v-else/>
                                            </div>
                                            <img
                                                v-else
                                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="RedeemedItemImage"
                                                class="w-[60px] h-[60px] rounded-[4.5px] border-[0.3px] border-solid border-grey-100 bg-primary-25"
                                            >
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                                <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ getRecordTitle(item) }}</span>
                                                <span class="text-primary-950 text-base font-medium" v-if="item.type === 'Used' && item.point_type === 'Redeem'">x{{ item.qty }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-1/4 flex flex-col justify-center items-end gap-4">
                                        <span class="text-green-700 text-base font-medium whitespace-nowrap" v-if="item.type === 'Earned'">+ {{ formatPoints(item.amount) }} pts</span>
                                        <span class="text-primary-700 text-base font-medium" v-else>- {{ formatPoints(item.amount) }} pts</span>
                                    </div>
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
            </template>

            <template #earned>
                <template v-if="earnedHistories.length > 0">
                    <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="item in earnedHistories" :key="item.id">
                        <div class="flex items-center gap-3 pt-6 self-stretch rounded-[5px]">
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                                <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-[2px] bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="flex justify-between items-center self-stretch gap-x-3">
                                    <div class="w-3/4 flex flex-col items-start gap-3">
                                        <div class="flex items-start gap-3 self-stretch">
                                            <div class="flex w-[60px] h-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border-[1.5px] border-solid border-primary-200 bg-primary-50">
                                                <ReceiptIcon />
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                                <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ getRecordTitle(item) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-1/4 flex flex-col justify-center items-end gap-4">
                                        <span class="text-green-700 text-base font-medium whitespace-nowrap">+ {{ formatPoints(item.amount) }} pts</span>
                                    </div>
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
            </template>

            <template #used>
                <template v-if="usedHistories.length > 0">
                    <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="item in usedHistories" :key="item.id">
                        <div class="flex items-center gap-3 pt-6 self-stretch">
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                                <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="flex justify-between items-center self-stretch gap-x-3">
                                    <div class="w-3/4 flex flex-col items-start gap-3">
                                        <div class="flex items-start gap-3 self-stretch">
                                            <img 
                                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="RedeemedItemImage"
                                                class="w-[60px] h-[60px] rounded-[4.5px] border-[0.3px] border-solid border-grey-100 bg-primary-25"
                                            >
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                                <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ getRecordTitle(item) }}</span>
                                                <span class="text-primary-950 text-base font-medium" v-if="item.point_type === 'Redeem'">x{{ item.qty }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-1/4 flex flex-col justify-center items-end gap-3">
                                        <span class="text-primary-700 text-base font-medium">- {{ formatPoints(item.amount) }} pts</span>
                                    </div>
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
            </template>
        </TabView>
    </div>
</template>
