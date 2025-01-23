<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { CommentIcon, DefaultIcon, EditIcon, PointsIcon, ReceiptIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    customerId: Number,
})
const customerId = ref(props.customerId);
const redeemHistory = ref([]);
const tabs = ref(['All', 'Earned', 'Used', 'Adjusted']);
const isLoading = ref(false);

const getRedeemHistory = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`customer/getCustomerPointHistories/${props.customerId}`);
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
const adjustedHistories = computed(() => redeemHistory.value.filter(item => item.type === 'Adjusted'));

const getRecordTitle = (item) => {
    switch (item.type) {
        case 'Earned': return item.point_type === 'Order' ? item.payment.order.order_no : `${item.customer.rank.name} Entry Reward`;
        case 'Used': return item.point_type === 'Redeem' ? item.redeemable_item.product_name : '';
    }
}

onMounted(() => getRedeemHistory());
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
                                <div class="flex justify-between items-center self-stretch">
                                    <div class="w-3/4 flex flex-col items-start gap-3">
                                        <div class="flex items-start gap-3 self-stretch">
                                            <div class="flex w-[60px] h-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border-[1.5px] border-solid border-primary-200 bg-primary-50"  v-if="item.type === 'Earned'">
                                                <ReceiptIcon class="text-primary-900" v-if="item.point_type === 'Order'" />
                                                <PointsIcon class="text-primary-900" v-else/>
                                            </div>
                                            <img
                                                v-if="item.type === 'Used'"
                                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="RedeemedItemImage"
                                                class="w-[60px] h-[60px] rounded-[4.5px] border-[0.3px] border-solid border-grey-100 bg-primary-25"
                                            >
                                            <div class="flex size-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border border-solid border-primary-200 bg-primary-50" v-if="item.type === 'Adjusted'">
                                                <EditIcon class="size-[34px] text-primary-900" />
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                                <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium" v-if="item.type !== 'Adjusted'">{{ getRecordTitle(item) }}</span>
                                                <div class="flex flex-col justify-center items-start flex-[1_0_0] self-stretch" v-else>
                                                    <div class="flex items-center gap-1 self-stretch">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-primary-900 text-2xs font-normal">Adjusted by</span>
                                                            <div class="flex items-center gap-1.5">
                                                                <img
                                                                    :src="item.waiter_image"
                                                                    alt="WaiterImage"
                                                                    class="size-3"
                                                                    v-if="item.waiter_image"
                                                                >
                                                                <DefaultIcon v-else />
                                                                <span class="text-primary-900 text-2xs font-normal">{{ item.handled_by.full_name }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 self-stretch">
                                                        <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">Point Adjustment</span>
                                                    </div>
                                                    <div class="flex items-start gap-1 self-stretch" v-if="item.remark">
                                                        <CommentIcon class="size-[15px]" />
                                                        <span class="flex-[1_0_0] self-stretch text-grey-900 text-xs font-normal">{{ item.remark }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-primary-950 text-base font-medium" v-if="item.type === 'Used' && item.point_type === 'Redeem'">x{{ item.qty }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-1/4 flex flex-col justify-center items-end gap-4">
                                        <span class="text-green-700 text-base font-medium whitespace-nowrap" v-if="item.type === 'Earned'">+ {{ formatPoints(item.amount) }} pts</span>
                                        <span class="text-primary-700 text-base font-medium" v-if="item.type === 'Used'">- {{ formatPoints(item.amount) }} pts</span>
                                        <span class="text-green-700 text-base font-medium whitespace-nowrap" v-if="item.type === 'Adjusted' && item.new_balance > item.old_balance">+ {{ formatPoints(item.amount) }} pts</span>
                                        <span class="text-primary-700 text-base font-medium" v-if="item.type === 'Adjusted' && item.new_balance < item.old_balance">- {{ formatPoints(item.amount) }} pts</span>
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
                                <div class="flex justify-between items-center self-stretch">
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
                                <div class="flex justify-between items-center self-stretch">
                                    <div class="flex flex-col items-start gap-3">
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
                                    <div class="flex flex-col justify-center items-end gap-3">
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

            <template #adjusted>
                <template v-if="adjustedHistories.length > 0">
                    <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="item in adjustedHistories" :key="item.id">
                        <div class="flex items-center gap-3 pt-6 self-stretch">
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                                <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="flex justify-between items-center self-stretch">
                                    <div class="flex flex-col items-start gap-3">
                                        <div class="flex items-start gap-3 self-stretch">
                                            <div class="flex size-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border border-solid border-primary-200 bg-primary-50">
                                                <EditIcon class="size-[34px] text-primary-900" />
                                            </div>
                                            <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                                <div class="flex flex-col justify-center items-start flex-[1_0_0] self-stretch">
                                                    <div class="flex items-center gap-1 self-stretch">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-primary-900 text-2xs font-normal">Adjusted by</span>
                                                            <div class="flex items-center gap-1.5">
                                                                <img
                                                                    :src="item.waiter_image"
                                                                    alt="WaiterImage"
                                                                    class="size-3"
                                                                    v-if="item.waiter_image"
                                                                >
                                                                <DefaultIcon v-else />
                                                                <span class="text-primary-900 text-2xs font-normal">{{ item.handled_by.full_name }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 self-stretch">
                                                        <span class="line-clamp-1 text-grey-900 text-ellipsis text-sm font-medium">Point Adjustment</span>
                                                    </div>
                                                    <div class="flex items-start gap-1 self-stretch" v-if="item.remark">
                                                        <CommentIcon class="size-[15px]" />
                                                        <span class="flex-[1_0_0] self-stretch text-grey-900 text-xs font-normal">{{ item.remark }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3">
                                        <span class="text-green-700 text-base font-medium whitespace-nowrap" v-if="item.new_balance >= item.old_balance">+ {{ formatPoints(item.amount) }} pts</span>
                                        <span class="text-primary-700 text-base font-medium" v-if="item.new_balance < item.old_balance">- {{ formatPoints(item.amount) }} pts</span>
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
