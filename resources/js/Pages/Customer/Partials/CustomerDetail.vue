<script setup>
import Button from '@/Components/Button.vue';
import { CircledArrowHeadRightIcon2, CrownImage, EmptyProfilePic, GiftImage, HistoryIcon, MedalImage } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { ref } from 'vue';
import ViewHistory from './ViewHistory.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import ReturnItem from './ReturnItem.vue';
import PointsDrawer from './PointsDrawer.vue';
import TierDrawer from './TierDrawer.vue';
import { usePhoneUtils } from '@/Composables';

const props = defineProps ({
    customers: {
        type: Object,
        required: true,
    }
})
const selectedCustomer = ref(null);
const selectedItem = ref(null);
const returnItemOverlay = ref(null);
const isPointDrawerOpen = ref(false);
const isTierDrawerOpen = ref(false);
const isHistoryDrawerOpen = ref(false);

const { formatPhone } = usePhoneUtils();
const openHistoryDrawer = (customers) => {
    isHistoryDrawerOpen.value = true;
    selectedCustomer.value = customers;
}

const openPointsDrawer = (customers) => {
    isPointDrawerOpen.value = true;
    selectedCustomer.value = customers;
}

const openTierDrawer = (customers) => {
    isTierDrawerOpen.value = true;
    selectedCustomer.value = customers;
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

const formatPoints = (points) => {
    const pointsStr = points.toString();
    return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};



</script>

<template>
    <div class="flex flex-col items-center gap-3 self-stretch max-h-[800px] p-6 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col py-6 gap-6 self-stretch rounded-[5px]">
            <!-- avatar -->
            <div class="w-full flex flex-col items-center gap-3">
                <!-- <div class="w-[80px] h-[80px]"> -->
                    <!-- <EmptyProfilePic /> -->
                <!-- </div> -->
                <img :src="customers.image ? customers.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="" 
                        class="size-[80px] rounded-full"/>
                <div class="w-full flex flex-col items-center gap-2">
                    <span class="text-primary-900 text-base font-semibold">{{ customers.name }}</span>
                    <div class="flex justify-center items-center gap-2">
                        <span class="text-primary-950 text-sm font-medium">{{ customers.email }}</span>
                        <span class="w-1 h-1 rounded-full bg-grey-300"></span>
                        <span class="text-primary-950 text-sm font-medium">({{ formatPhone(customers.phone) }})</span>
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
                            @click="openPointsDrawer(customers)" />
                    </div>
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-white text-[36px] leading-normal font-light tracking-[-0.72px]">{{ formatPoints(customers.points) }}</span>
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
                            @click="openTierDrawer(customers)" />
                    </div>
                    <div class="w-full flex flex-col justify-center items-start gap-1 self-stretch">
                        <div v-if="customers.tier !== 'No Tier'">
                            <div class="flex flex-col justify-center items-center gap-[10px]">
                                <img 
                                    :src="customers.tier_image ? customers.tier_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="h-12 w-full"
                                />
                            </div>
                            <div class="flex flex-col justify-center items-center gap-2">
                                <span class="text-primary-900 text-lg font-medium">{{ customers.tier }}</span>
                            </div>
                        </div>
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
                    <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Keep Item ({{ customers.keep }})</span>
                    <div class="flex items-center gap-2 cursor-pointer" @click="openHistoryDrawer(customers)">
                        <HistoryIcon class="w-4 h-4" />
                        <div class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-sm font-medium">View History</div>
                    </div>
                </div>
                <div class="flex flex-col justify-end items-start gap-2 self-stretch">
                    <div class="flex py-3 items-center gap-3 self-stretch" v-for="item in customers.keep_items" :key="item.id">
                        <div class="flex flex-col justify-center items-start gap-3 flex-[1_0_0]">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                <span class="text-primary-900 text-sm font-medium">{{ item.created_at }}</span>
                            </div>
                            <div class="flex items-center gap-3 self-stretch">
                                <div class="flex flex-col items-start gap-3 flex-[1_0_0]">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <!-- <div class="flex rounded-[1.5px] overflow-x-hidden w-[60px] h-[60px] bg-primary-900"></div> -->
                                        <img 
                                            :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="flex rounded-[1.5px] overflow-x-hidden w-[60px] h-[60px] bg-primary-900"
                                        />
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">Expire on {{ item.expired_to }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">by</span>
                                                <!-- <span class="w-3 h-3 bg-red-900 rounded-full"></span> -->
                                                <img 
                                                    :src="item.waiter_pic ? item.waiter_pic : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                                                    alt="" 
                                                    class="w-3 h-3 bg-red-900 rounded-full"
                                                />
                                                <span class="text-primary-900 text-2xs font-normal">{{ item.waiter_name }}</span>
                                            </div>
                                            <span
                                                class="text-grey-900 line-clamp-1 self-stretch overflow-hidden text-ellipsis text-sm font-medium">{{ item.item }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-3">
                                    <span class="text-primary-900 text-base font-medium">x {{ item.qty }}</span>
                                    <Button :type="'button'" :size="'lg'" @click="openItemOverlay($event, item)">
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
        <ViewHistory :customers="selectedCustomer" />
    </RightDrawer>

    <!-- points drawer -->
    <RightDrawer
        :header="'Points'"
        :previousTab="true"
        :show="isPointDrawerOpen"
        @close="closeDrawer"
    >
        <PointsDrawer :customers="selectedCustomer"/>
    </RightDrawer>

    <!-- tier drawer -->
    <RightDrawer
        :header="'Tier'"
        :previousTab="true"
        :show="isTierDrawerOpen"
        @close="closeDrawer"
    >
        <TierDrawer :customers="selectedCustomer"/>
    </RightDrawer>

    <OverlayPanel ref="returnItemOverlay">
        <ReturnItem
            :item="selectedItem"
            @close="closeItemOverlay"
        />
    </OverlayPanel>
</template>