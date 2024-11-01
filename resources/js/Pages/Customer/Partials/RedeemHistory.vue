<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { ReceiptIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    customer: {
        type: Number,
        required: true
    },
})

const redeemHistory = ref([]);
const tabs = ref(['All', 'Earned', 'Used']);

const getRedeemHistory = async (id) => {
        try {
            const response = await axios.get(`customer/redeemHistory/${id}`);
            redeemHistory.value = response.data;
        } catch(error){
            console.error(error)
        } finally {
    }
}

const formatPoints = (points) => {
  const pointsStr = points.toString();
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const noneEarned = computed(() => redeemHistory.value.every(item => item.earned === 0));
const noneRedeemed = computed(() => redeemHistory.value.every(item => item.redeemed === 0));



onMounted(() => {
    getRedeemHistory(props.customer);
})


</script>

<template>
    <div class="!w-full flex flex-col p-6 self-stretch max-h-[800px] overflow-y-scroll scrollbar-thin scrollbar-webkit">
        <TabView :tabs="tabs">
            <template #all>
                <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="items in redeemHistory" :key="items.id">
                    <div class="flex items-center gap-3 self-stretch rounded-[5px] pt-6" v-if="items.earned > 0">
                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-[2px] bg-primary-25">
                                <span class="text-primary-900 text-sm font-medium">{{ items.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex flex-col w-full items-start gap-3">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <div class="flex w-[60px] h-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border-[1.5px] border-solid border-primary-200 bg-primary-50">
                                            <ReceiptIcon />
                                        </div>
                                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                            <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ items.subject }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-4">
                                    <span class="text-green-700 text-base font-medium whitespace-nowrap">+ {{ formatPoints(items.earned) }} pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 self-stretch" v-for="item in items.used" :key="item.id">
                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0] pt-6">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                <span class="text-primary-900 text-sm font-medium">{{ items.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex flex-col items-start gap-3">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <div class="w-[60px] h-[60px] rounded-[4.5px] border-[0.3px] border-solid border-grey-100 bg-primary-25"></div>
                                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                            <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ item.name }}</span>
                                            <span class="text-primary-950 text-base font-medium">x{{ item.qty }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-3">
                                    <span class="text-primary-700 text-base font-medium">- {{ formatPoints(item.redeemed) }} pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <template v-if="noneEarned && noneRedeemed">
                    <div class="flex w-full flex-col items-center justify-center gap-5">
                        <UndetectableIllus />
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </div>
                </template>
            </template>
            <template #earned>
                <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="items in redeemHistory" :key="items.id">
                    <div class="flex items-center gap-3 pt-6 self-stretch rounded-[5px]" v-if="items.earned > 0">
                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-[2px] bg-primary-25">
                                <span class="text-primary-900 text-sm font-medium">{{ items.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex flex-col w-full items-start gap-3">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <div class="flex w-[60px] h-[60px] justify-center items-center gap-[15px] rounded-[4.5px] border-[1.5px] border-solid border-primary-200 bg-primary-50">
                                            <ReceiptIcon />
                                        </div>
                                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                            <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ items.subject }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-4">
                                    <span class="text-green-700 text-base font-medium whitespace-nowrap">+ {{ formatPoints(items.earned) }} pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <template v-if="noneEarned">
                    <div class="flex w-full flex-col items-center justify-center gap-5">
                        <UndetectableIllus />
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </div>
                </template>
            </template>
            <template #used>
                <div class="flex flex-col items-start self-stretch rounded-[5px]" v-for="items in redeemHistory" :key="items.id">
                    <div class="flex items-center gap-3 pt-6 self-stretch" v-for="item in items.used" :key="item.id">
                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                            <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                <span class="text-primary-900 text-sm font-medium">{{ items.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex flex-col items-start gap-3">
                                    <div class="flex items-start gap-3 self-stretch">
                                        <div class="w-[60px] h-[60px] rounded-[4.5px] border-[0.3px] border-solid border-grey-100 bg-primary-25"></div>
                                        <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0] self-stretch">
                                            <span class="overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ item.name }}</span>
                                            <span class="text-primary-950 text-base font-medium">x{{ item.qty }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col justify-center items-end gap-3">
                                    <span class="text-primary-700 text-base font-medium">- {{ formatPoints(item.redeemed) }} pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <template v-if="noneRedeemed">
                    <div class="flex w-full flex-col items-center justify-center gap-5">
                        <UndetectableIllus />
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </div>
                </template>
            </template>
        </TabView>
    </div>
</template>
