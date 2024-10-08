<script setup>
import { PointsIllust } from '@/Components/Icons/illus';
import { HistoryIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import Toast from '@/Components/Toast.vue';
import { onMounted, ref } from 'vue';
import RedeemHistory from './RedeemHistory.vue';

const props = defineProps({
    customers:{
        type: Object,
        required: true,
    }
})

const selectedCustomer = ref(null);
const redeemables = ref([]);
const isPointHistoryDrawerOpen = ref(false);

const openHistoryDrawer = (id) => {
    isPointHistoryDrawerOpen.value = true;
    selectedCustomer.value = id;
}

const closeDrawer = () => {
    isPointHistoryDrawerOpen.value = false;
}

const formatPoints = (points) => {
  const pointsStr = points.toString();
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};


const getRedeemables = async () => {
    try {
        const response = await axios.get('customer/points');
        redeemables.value = response.data;
    } catch(error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getRedeemables();
})
</script>

<template>
    <div class="flex flex-col p-6 items-center gap-6 shrink-0 overflow-y-auto">
        <!-- current points -->
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium">Current Points</span>
                <div class="flex flex-col justify-center items-center gap-2">
                    <span class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-[40px] font-normal">{{ formatPoints(customers.points) }}</span>
                    <span class="text-primary-950 text-base font-medium">pts</span>
                </div>
                <PointsIllust class="absolute"/>
            </div>
        </div>

        <!-- Redeem product -->
         <div class="flex flex-col items-center gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-medium font-semibold ">Redeem Product</span>
                <div class="flex items-center gap-2 cursor-pointer" @click="openHistoryDrawer(customers.id)">
                    <HistoryIcon class="w-4 h-4" />
                    <div class="text-primary-900 text-sm font-medium">View History</div>
                </div>
            </div>

            <Toast 
                inline
                severity="info"
                summary="You can redeem the product only when the customer has checked-in to one table/room."
                :closable="false"
            />

            <div class="flex flex-col items-center self-stretch divide-y-[0.5px] divide-grey-200">
                <div class="flex flex-col justify-end items-start self-stretch" v-for="items in redeemables" :key="items.id">
                    <div class="flex items-center p-3 gap-3 self-stretch">
                        <div class="flex items-center gap-3">
                            <div class="w-[60px] h-[60px] bg-primary-25 rounded-[1.5px] border-[0.3px] border-solid border-grey-100"></div>
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0] self-stretch">
                                <span class="line-clamp-1 overflow-hidden text-grey-900 ellipsis text-base font-medium">{{ items.name }}</span>
                                <span class="overflow-hidden text-red-950 text-base font-medium">{{ formatPoints(items.point) }} pts</span>
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
        :previousTab='true'
        :show="isPointHistoryDrawerOpen"
        @close="closeDrawer"
    >
        <RedeemHistory :customer="selectedCustomer"/>
    </RightDrawer>
</template>
