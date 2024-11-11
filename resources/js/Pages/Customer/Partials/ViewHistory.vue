<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import TabView from '@/Components/TabView.vue';
import Tag from '@/Components/Tag.vue';
import axios from 'axios';
import { capitalize, onMounted, ref } from 'vue';

const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },
})
const tabs = ref(['All', 'Keep', 'Returned', 'Expired']);
const tabsSlug = ref(
  tabs.value
    .map((tab) => tab.toLowerCase().replace(/[^a-z0-9]+/g, '-'))
    .filter((slug) => slug !== 'all')
);

const keepHistory = ref([]);
const formatStatus = (status) => {
    return capitalize(status);
}

const getKeepHistory = async (id) => {
    try {
        const response = await axios.get(`customer/keepHistory/${id}`);
        keepHistory.value = response.data;
    }
    catch(errors){
        console.error(errors)
    } finally {

    }
};


const filteredHistoryByStatus = (status) => {
    if(!keepHistory.value) return [];
    const filteredArr = keepHistory.value.filter(keepHistory => keepHistory.status === status);
    return filteredArr;
}

onMounted(() => {
    getKeepHistory(props.customers.id);
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
                                    <span class="text-primary-900 text-sm font-medium">{{ item.created_at }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="w-[60px] h-[60px] relative bg-primary-25">
                                            <Tag 
                                                :variant="item.status === 'keep' ? 'default' : item.status === 'returned' ? 'green' : 'grey'"
                                                :value="formatStatus(item.status)"
                                                class="!px-1.5 !py-1 !text-[6.8px] !m-0.5 absolute"
                                            />
                                            <img 
                                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="h-full w-full"
                                            >
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">Expire on {{ item.expired_date }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="flex items-center gap-2">
                                                    <span v-if="item.status === 'returned'" class="text-primary-900 text-2xs font-light">
                                                        Returned by
                                                    </span>
                                                    <span v-if="item.status === 'keep'" class="text-primary-900 text-2xs font-light">
                                                        Kept by
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <span class="w-3 h-3 bg-primary-900 rounded-full"></span>
                                                        <span class="text-primary-900 text-2xs font-light">{{ item.waiter_name }}</span>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.item_name }}</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3 w-fit">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">x {{ item.qty }}</span>
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
                                    <span class="text-primary-900 text-sm font-medium">{{ item.created_at }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="w-[60px] h-[60px] relative bg-primary-25">
                                            <Tag 
                                                :variant="item.status === 'keep' ? 'default' : item.status === 'returned' ? 'green' : 'grey'"
                                                :value="formatStatus(item.status)"
                                                class="!px-1.5 !py-1 !text-[6.8px] !m-0.5 absolute"
                                            />
                                             <img 
                                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="h-full w-full"
                                            >
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">Expire on {{ item.expired_date }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="flex items-center gap-2">
                                                    <span v-if="item.status === 'returned'" class="text-primary-900 text-2xs font-light">
                                                        Returned by
                                                    </span>
                                                    <span v-if="item.status === 'keep'" class="text-primary-900 text-2xs font-light">
                                                        Kept by
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <span class="w-3 h-3 bg-primary-900 rounded-full"></span>
                                                        <span class="text-primary-900 text-2xs font-light">{{ item.waiter_name }}</span>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.item_name }}</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3 w-fit">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">x {{ item.qty }}</span>
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
