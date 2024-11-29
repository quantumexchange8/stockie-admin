<script setup>
import Button from '@/Components/Button.vue';
import { CircledArrowHeadRightIcon2, CommentIcon, CrownImage, EmptyProfilePic, GiftImage, HistoryIcon, MedalImage } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { ref } from 'vue';
import ViewHistory from './ViewHistory.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import ReturnItem from './ReturnItem.vue';
import PointsDrawer from './PointsDrawer.vue';
import TierDrawer from './TierDrawer.vue';
import { usePhoneUtils } from '@/Composables';
import dayjs from 'dayjs';

const props = defineProps ({
    customer: Object
})
const selectedCustomer = ref(null);
const selectedItem = ref(null);
const returnItemOverlay = ref(null);
const isPointDrawerOpen = ref(false);
const isTierDrawerOpen = ref(false);
const isHistoryDrawerOpen = ref(false);

const { formatPhone } = usePhoneUtils();
const openHistoryDrawer = (customer) => {
    isHistoryDrawerOpen.value = true;
    selectedCustomer.value = customer;
}

const openPointsDrawer = (customer) => {
    isPointDrawerOpen.value = true;
    selectedCustomer.value = customer;
}

const openTierDrawer = (customer) => {
    isTierDrawerOpen.value = true;
    selectedCustomer.value = customer;
}

const closeDrawer = () => {
    isHistoryDrawerOpen.value = false;
    isTierDrawerOpen.value = false;
    isPointDrawerOpen.value = false;
}

const openItemOverlay = (event, item) => {
    selectedItem.value = item;
    returnItemOverlay.value.show(event);
};

const closeItemOverlay = () => {
    selectedItem.value = null;
    returnItemOverlay.value.hide();
};

const formatKeepItems = (keepItems) => {
    return keepItems.map((item) => {
        item.qty = parseFloat(item.qty);
        item.cm = parseFloat(item.cm);
        return item;
    }).sort((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)));
};

const formatPoints = (points) => {
    const pointsStr = points.toString();
    return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

</script>

<template>
    <div class="flex flex-col items-center gap-3 self-stretch max-h-[calc(100dvh-4rem)] p-6 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col py-6 gap-6 self-stretch rounded-[5px]">
            <!-- avatar -->
            <div class="w-full flex flex-col items-center gap-3">
                <img 
                    :src="customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                    alt="" 
                    class="size-[80px] rounded-full"
                />
                <div class="w-full flex flex-col items-center gap-2">
                    <span class="text-primary-900 text-base font-semibold">{{ customer.full_name }}</span>
                    <div class="flex justify-center items-center gap-2">
                        <span class="text-primary-950 text-sm font-medium">{{ customer.email }}</span>
                        <span class="w-1 h-1 rounded-full bg-grey-300"></span>
                        <span class="text-primary-950 text-sm font-medium">({{ formatPhone(customer.phone) }})</span>
                    </div>
                </div>
            </div>

            <!-- current points and current tier -->
            <div class="w-full flex justify-center items-start gap-6 self-stretch">
                <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] rounded-[5px] bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative">
                    <div class="w-full flex justify-between items-center">
                        <span class="text-base font-semibold text-primary-25 whitespace-nowrap w-full">Current Points</span>
                        <CircledArrowHeadRightIcon2
                            class="w-6 h-6 text-primary-900 [&>rect]:fill-primary-25 [&>rect]:hover:fill-primary-800 cursor-pointer z-10"
                            @click="openPointsDrawer(customer)" />
                    </div>
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-white text-[36px] leading-normal font-light tracking-[-0.72px]">{{ formatPoints(customer.point) }}</span>
                        <span class="text-white text-lg font-normal">pts</span>
                    </div>
                    <div class="absolute bottom-0 right-0">
                        <GiftImage />
                    </div>
                </div>
                <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] self-stretch rounded-[5px] border border-solid border-primary-50 
                            bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-[#FFFAFA] to-[#FFFFFF] relative">
                    <div class="w-full flex justify-between items-center">
                        <span class="text-base font-semibold text-primary-900 whitespace-nowrap w-full">Current Tier</span>
                        <CircledArrowHeadRightIcon2
                            class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer z-10"
                            @click="openTierDrawer(customer)" />
                    </div>
                    <div class="w-full flex flex-col justify-center items-start gap-1 self-stretch">
                        <template v-if="customer.rank && customer.rank.name !== 'No Tier'">
                            <div class="flex flex-col justify-center items-start gap-[10px]">
                                <img 
                                    :src="customer.rank && customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="CustomerRankImage"
                                    class="size-[48px]"
                                />
                            </div>
                            <div class="flex flex-col justify-center items-center gap-2 !z-10">
                                <span class="text-primary-900 text-lg font-medium">{{ customer.rank.name }}</span>
                            </div>
                        </template>
                        <div v-else>
                            <span class="text-primary-900 text-lg font-medium"> - </span>
                        </div>
                        <div class="absolute bottom-0 right-0">
                            <MedalImage />
                        </div>
                    </div>
                </div>
            </div>

            <!-- keep item -->
            <div class="w-full flex flex-col items-center gap-3 self-stretch">
                <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                    <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Keep Item ({{ customer.keep_items.length }})</span>
                    <div class="flex items-center gap-2 cursor-pointer" @click="openHistoryDrawer(customer)">
                        <HistoryIcon class="w-4 h-4" />
                        <div class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-sm font-medium">View History</div>
                    </div>
                </div>
                <div class="flex flex-col justify-end items-start gap-2 self-stretch">
                    <div class="flex py-3 items-center gap-3 self-stretch" v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
                        <div class="flex flex-col justify-center items-start gap-3 flex-[1_0_0]">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm">
                                <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                            </div>
                            <div class="flex items-center gap-3 self-stretch">
                                <div class="flex flex-col items-start gap-3 flex-[1_0_0]">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <img 
                                            :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="CustomerKeepItem"
                                            class="flex rounded-[1.5px] overflow-x-hidden size-[60px] object-cover"
                                        />
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal" v-if="item.expired_to">{{ item.expired_to ? `Expire on ${dayjs(item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                                <span class="size-1 bg-grey-900 rounded-full" v-if="item.expired_to"></span>
                                                <span class="text-primary-900 text-2xs font-normal">Kept by</span>
                                                <img 
                                                    :src="item.waiter && item.waiter.image ? item.waiter.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                                                    alt="" 
                                                    class="size-3 bg-red-900 rounded-full"
                                                />
                                                <span class="text-primary-900 text-2xs font-normal">{{ item.waiter.full_name }}</span>
                                            </div>
                                            <span class="text-grey-900 line-clamp-1 self-stretch overflow-hidden text-ellipsis text-sm font-medium">{{ item.item_name }}</span>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.remark">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-sm font-normal">{{ item.remark }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-3">
                                    <span class="text-primary-900 text-base font-medium">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
                                    <Button 
                                        :type="'button'" 
                                        :size="'md'" 
                                        @click="openItemOverlay($event, item)"
                                    >
                                        Return Item
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- history drawer -->
    <RightDrawer
        :header="'History'"
        :previousTab="true"
        :show="isHistoryDrawerOpen"
        @close="closeDrawer"
    >
        <ViewHistory :customer="selectedCustomer" />
    </RightDrawer>

    <!-- points drawer -->
    <RightDrawer
        :header="'Points'"
        :previousTab="true"
        :show="isPointDrawerOpen"
        @close="closeDrawer"
    >
        <PointsDrawer :customer="selectedCustomer"/>
    </RightDrawer>

    <!-- tier drawer -->
    <RightDrawer
        :header="'Tier'"
        :previousTab="true"
        :show="isTierDrawerOpen"
        @close="closeDrawer"
    >
        <TierDrawer :customer="selectedCustomer"/>
    </RightDrawer>

    <OverlayPanel ref="returnItemOverlay">
        <ReturnItem
            :item="selectedItem"
            @update:keepList="customer.keep_items = $event"
            @close="closeItemOverlay"
        />
    </OverlayPanel>
</template>