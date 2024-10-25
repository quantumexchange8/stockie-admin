<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { CommentIcon } from '@/Components/Icons/solid';
import TabView from '@/Components/TabView.vue';
import Tag from '@/Components/Tag.vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { onMounted, ref } from 'vue';

const props = defineProps({
    customer: {
        type: Object,
        default: () => {}
    },
});

const tabs = ref(['All', 'Keep', 'Served/Returned', 'Expired']);
const keepHistory = ref([]);

const formatHistoryStatus = (status) => {
    return status.toLowerCase().replace(/[/_]+/g, "-").replace(/^-+|-+$/g, " "); // Replace spaces, '/', and '_' with '-' | Remove leading or trailing '-'
};

const tabsSlug = ref(
  tabs.value
    .map((tab) => formatHistoryStatus(tab))
    .filter((slug) => slug !== 'all')
);

const getKeepHistory = async () => {
    try {
        const response = await axios.get(route('orders.customer.keep.getCustomerKeepHistories', props.customer.id));
        keepHistory.value = response.data;
    }
    catch(errors){
        console.error(errors)
    } finally {

    }
};

const filteredHistoryByStatus = (status) => {
    if(!keepHistory.value) return [];
    const filteredArr = keepHistory.value.filter(history => {
        if (status === 'served-returned') {
            return formatHistoryStatus(history.status) === 'served' || formatHistoryStatus(history.status) === 'returned';
        } else {
            return formatHistoryStatus(history.status) === status;
        }
    });
    return filteredArr;
};

onMounted(() => {
    getKeepHistory();
});
</script>

<template>
    <div class="w-full p-6 h-[800px] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <TabView :tabs="tabs">
            <template #all>         
                <div class="w-full flex flex-col items-start py-6 self-stretch rounded-[5px]">
                    <template v-if="keepHistory.length > 0">
                        <div class="w-full flex items-center gap-3 self-stretch" v-for="item in keepHistory" :key="item.id">
                            <div class="w-full flex flex-col items-center gap-2 flex_[1_0_0] pb-6">
                                <div class="flex w-full px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="rounded-[1.5px] size-[60px] bg-primary-25">
                                            <Tag 
                                                :variant="item.status === 'Keep' 
                                                                ? 'default' 
                                                                : item.status === 'Returned' || item.status === 'Served'
                                                                        ? 'green' 
                                                                        : 'grey'"
                                                :value="item.status"
                                                class="!px-1.5 !py-1 !text-[6.8px] !m-0.5"
                                            />
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">{{ item.keep_item.expired_to ? `Expire on ${dayjs(item.keep_item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">
                                                    {{ item.status === 'Returned' ? 'Returned by' : item.status === 'Expired' ? 'Expired' : 'Kept by' }}
                                                </span>
                                                <span class="w-3 h-3 bg-primary-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">{{ item.keep_item.waiter.name }}</span>
                                            </div>
                                            <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.keep_item.item_name }}</div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.keep_item.remark">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-sm font-normal">{{ item.keep_item.remark }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
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
            <template
                v-for="tab in tabsSlug"
                :key="tab"
                v-slot:[tab]
            >
                <div class="w-full flex flex-col items-start py-6 self-stretch rounded-[5px]">
                    <template v-if="filteredHistoryByStatus(tab).length > 0">
                        <div class="w-full flex items-center gap-3 self-stretch" v-for="item in filteredHistoryByStatus(tab)" :key="item.id">
                            <div class="w-full flex flex-col items-center gap-2 flex_[1_0_0] pb-6">
                                <div class="flex w-full px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="rounded-[1.5px] size-[60px] bg-primary-25">
                                            <Tag 
                                                v-if="item.status === 'Returned' || item.status === 'Served'"
                                                :variant="item.status === 'Keep' 
                                                                ? 'default' 
                                                                : item.status === 'Returned' || item.status === 'Served'
                                                                        ? 'green' 
                                                                        : 'grey'"
                                                :value="item.status"
                                                class="!px-1.5 !py-1 !text-[6.8px] !m-0.5"
                                            />
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">{{ item.keep_item.expired_to ? `Expire on ${dayjs(item.keep_item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">
                                                    {{ item.status === 'Returned' ? 'Returned by' : item.status === 'Expired' ? 'Expired' : 'Kept by' }}
                                                </span>
                                                <span class="w-3 h-3 bg-primary-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">{{ item.keep_item.waiter.name }}</span>
                                            </div>
                                            <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.keep_item.item_name }}</div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.keep_item.remark">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-sm font-normal">{{ item.keep_item.remark }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
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
        </TabView>
    </div>
</template>
