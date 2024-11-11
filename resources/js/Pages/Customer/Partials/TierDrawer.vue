<script setup>
import { TierIllust } from '@/Components/Icons/illus';
import { CrownImage } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import Toast from '@/Components/Toast.vue';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps ({
    customers: {
        type: Object,
        required: true,
    }
})

const customerTier = ref([]);
const tabs = ref(['Active', 'Redeemed', 'Expired']);

const getTierRewards = async (id) => {
    try {
        const response = await axios.get(`customer/tierRewards/${id}`);
        customerTier.value = response.data;
    } catch (error) {
        console.error(error)
    } finally {

    }
}

onMounted(() => {
    getTierRewards(props.customers.id);
})
</script>

<template>
    <div class="flex flex-col p-6 items-center gap-6 shrink-0 overflow-y-auto">
        <!-- current tier -->
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="text-grey-900 text-base font-medium">Current Tier</span>
                <template v-if="customers.tier !== 'No Tier'">
                    <div class="flex flex-col justify-center items-center gap-2">
                        <img 
                            :src="customers.tier_image ? customers.tier_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt=""
                            class="h-12 w-full"
                        />
                        <span class="text-primary-950 text-base font-medium">{{ customers.tier }}</span>
                    </div>
                </template>
                <div v-else>
                    <span class="text-primary-900 text-lg font-medium"> - </span>
                </div>
                <TierIllust class="absolute"/>
            </div>
        </div>

        <div class="w-full flex flex-col gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Tier Rewards</span>
            </div>
            <TabView :tabs="tabs">
                <template #active>
                    <Toast 
                        inline
                        severity="info"
                        summary="You can redeem the rewards only when the customer has checked-in to one table/room."
                        :closable="false"
                    />
                </template>
                <template #redeemed>
                    <div>
                        redeemed
                    </div>
                </template>
                <template #expired>
                    <div>
                        expired
                    </div>
                </template>
            </TabView>
        </div>

        
    </div>
</template>
