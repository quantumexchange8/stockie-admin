<script setup>
import { EmptyActivityIllust, TableRoomActivityIllust } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    activeTables: {
        type: Array,
        required: true,
    }
})
const isLoading = ref(false);
const activities = ref([]);

const getActivities = async () => {
    isLoading.value = true;
    try {
        const activityResponse = await axios.get(route('dashboard.activity-log'));
        activities.value = activityResponse.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const getSubject = (event) => {
    switch(event.event){
        case 'assign to serve': return event.properties.assigned_by;
        case 'check in': return event.properties.table_name;
        case 'place to order': return event.properties.waiter_name;
    }
}

const calcTimeDiff = (created_at) => {
    const createdDate = new Date(created_at);
    const diffInMilliseconds = Date.now() - createdDate.getTime();
    const diffMinutes = Math.floor( diffInMilliseconds / ( 1000 * 60 ));
    const hours = Math.floor( diffMinutes / 60 );
    const minutes = diffMinutes % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
}


onMounted (() => {
    getActivities();
})

</script>

<template>
    <div class="w-full h-full flex flex-col py-6 item-center rounded-[5px] border border-solid border-primary-100">
        <div class="w-full flex flex-col items-start gap-[10px] self-stretch">
            <div class="w-full flex justify-between px-6 items-center self-stretch ">
                <span class="flex flex-col justify-between flex-[1_0_0] text-md font-medium text-primary-900 w-full">Table / Room Activity</span>
                <Link :href="route('orders')">
                        <CircledArrowHeadRightIcon2  
                            class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                        />
                </Link>
            </div>
        </div>

        <template v-if="activities.length > 0 && props.activeTables.length > 0 && !isLoading">
            <div class="flex pl-6 items-end gap-[10px] self-stretch overflow-x-auto scrollbar-webkit scrollbar-thin relative">
                <div 
                    class="flex p-3 pt-0 items-end gap-1 flex-[1_0_0] h-[102.231px] w-[270px] absolute
                            rounded-tl-[45px] rounded-tr-[5px] rounded-bl-[5px] rounded-br-[5px] 
                            bg-gradient-to-r from-[rgba(255,147,147,0.20)] from-[0.08%] via-[rgba(249,213,213,0.58)] via-[21.54%] to-[#FFF] to-[99.89%]"
                >
                </div>
                <div class="flex p-3 items-center gap-1 flex-[1_0_0] w-[270px] z-10">
                    <TableRoomActivityIllust class="flex-shrink-0"/>
                    <div class="flex flex-col justify-center items-start gap-[5px] flex-[1_0_0]">
                        <span class="text-primary-950 text-xs font-normal text-nowrap">Active Table / Room: </span>
                        <div class="flex items-center gap-3 self-stretch">
                            <template v-for="(table, index) in props.activeTables" :key="index">
                                <Link :href="route('orders')">
                                    <div class="flex w-9 h-9 px-[9px] py-2 flex-col justify-center items-center gap-[10px] rounded-full bg-primary-800 hover:bg-primary-900 hover:cursor-pointer">
                                        <span class="text-primary-25 text-base font-medium">{{ table.table_no }}</span>
                                    </div>
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full flex flex-col px-6 items-end gap-[10px] flex-[1_0_0] self-stretch min-h-[308px] overflow-y-auto scrollbar-webkit">
                <div class="w-full flex flex-col items-end self-stretch divide-y-[0.5px] divide-solid divide-grey-200">
                    <template v-for="activity in activities" :key="activity">
                        <div class="w-full h-full flex flex-col p-3 items-end gap-3 self-stretch rounded-[5px]">
                            <div class="w-full flex items-center gap-[10px] self-stretch justify-center">
                                <div class="h-9 w-9">
                                    <img 
                                        :src="activity.properties.waiter_image ? activity.properties.waiter_image 
                                                                                : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="WaiterImage"
                                        class="w-full h-full rounded-full"
                                        v-if="activity.event === 'place to order'"
                                    >
                                    <div class="flex relative w-full h-full" v-if="activity.event === 'check in'">
                                        <img 
                                            :src="activity.properties.waiter_image ? activity.properties.waiter_image
                                                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="WaiterImage"
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <div class="flex justify-center items-center border border-solid border-white bg-primary-800 
                                                    w-6 h-6 rounded-full absolute right-0 bottom-0"
                                        >
                                            <span class="text-primary-25 text-2xs font-medium">
                                                {{ activity.properties.table_name.split(',')[0].trim() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex relative w-full h-full" v-if="activity.event === 'assign to serve'">
                                        <img 
                                            :src="activity.properties.assigner_image ? activity.properties.assigner_image
                                                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="WaiterImage"
                                            class="w-6 h-6 rounded-full left-0 top-0 absolute"
                                        >
                                        <img 
                                            :src="activity.properties.waiter_image ? activity.properties.waiter_image
                                                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="WaiterImage"
                                            class="w-6 h-6 rounded-full absolute bottom-0 right-0"
                                        >
                                    </div>
                                </div>
                                <div class="w-full flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <div class="w-2 h-2 rounded-full bg-green-600" v-if="activity.event === 'place to order'"></div>
                                        <span class="w-full line-clamp-1 overflow-hidden text-primary-900 text-ellipsis text-xs font-medium">{{ getSubject(activity) }}</span>
                                        <span class="text-grey-300 text-2xs font-normal whitespace-nowrap">{{ calcTimeDiff(activity.created_at) }}</span>
                                    </div>
                                    <span class="text-grey-900 text-sm font-normal">
                                        <template v-if= "activity.event === 'check in'">
                                            New customer check-in by <span class="text-grey-900 text-sm font-semibold">{{ activity.properties.waiter_name }}</span>.    
                                        </template>
                                        <template v-else-if="activity.event === 'assign to serve'">
                                            Assigned 
                                                <span class="text-grey-900 text-sm font-semibold">
                                                    {{ activity.properties.waiter_name }}
                                                </span>
                                            to serve 
                                                <span class="text-grey-900 text-sm font-semibold">
                                                    {{ activity.properties.table_name }}
                                                </span>.
                                        </template>
                                        <template v-else-if="activity.event === 'place to order'">
                                            placed an order for <span class="text-grey-900 text-sm font-semibold">{{ activity.properties.table_name }}</span>.
                                        </template>
                                        <template v-else>
                                            {{ activity.description }}
                                        </template>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex flex-col px-6 justify-center items-center gap-2.5 flex-[1_0_0] self-stretch md:overflow-hidden">
                <EmptyActivityIllust class="flex-shrink-0" />
                <span class="text-primary-900 text-sm font-medium text-center">No activity can be shown yet...</span>
            </div>
        </template>
    </div>
</template>

