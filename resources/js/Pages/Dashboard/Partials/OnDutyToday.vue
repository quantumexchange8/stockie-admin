<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import Tag from '@/Components/Tag.vue';

const props = defineProps ({
    onDuty: {
        type: Array,
        default: () => [],
    }
})

</script>

<template>
    <div class="w-full h-full flex flex-col items-end gap-6 py-6 rounded-[5px] bg-gradient-to-br from-primary-900 from-[2.42%] to-[#5E0A0E] to-[102.62%] ">
        <div class="flex flex-col pl-4 items-start gap-[10px] self-stretch">
            <div class="flex pl-3 pr-6 justify-between items-center self-stretch rounded-tl-[5px] rounded-bl-[5px]">
                <span class="flex flex-col justify-center flex-[1_0_0] text-primary-25 text-md font-medium">On Duty Today</span>
            </div>
        </div>
        <div class="flex pl-6 justify-end items-center gap-[10px] self-stretch">
            <div class="flex flex-col px-4 py-3 items-start flex-[1_0_0] rounded-tl-[5px] rounded-bl-[5px] bg-white shadow-[0_10px_11.2px_0px_rgba(75,12,14,0.52)]">
                <div class="flex flex-col items-start self-stretch rounded-[5px] overflow-x-auto scrollbar-webkit scrollbar-thin">
                    <template v-if="props.onDuty.length > 0">
                        <template v-for="waiter in props.onDuty" :key="waiter.id">
                            <div class="flex py-2 justify-between items-center self-stretch gap-2">
                                <div class="flex items-center gap-[10px]">
                                    <img 
                                        :src="waiter.image ? waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="WaiterImage"
                                        class="w-10 h-10 shrink-0 rounded-full"
                                    >
                                    <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                        <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ waiter.waiter_name }}</span>
                                        <template v-if="waiter.status !== 'No Record'">
                                            <span class="text-grey-500 text-xs font-medium">at Today, {{ waiter.time }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="text-grey-500 text-xs font-medium">N/A</span>
                                        </template>
                                    </div>
                                </div>
                                <Tag
                                    :variant="waiter.status === 'Checked in' ? 'green' : waiter.status === 'Checked out' ? 'red' : 'grey'"
                                    :value= waiter.status 
                                ></Tag>
                            </div>
                        </template>
                    </template>
                    <template v-else>
                        <div class="flex w-full flex-col items-center justify-center gap-5">
                            <UndetectableIllus />
                            <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>