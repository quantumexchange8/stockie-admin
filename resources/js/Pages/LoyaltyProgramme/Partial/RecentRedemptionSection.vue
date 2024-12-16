<script setup>
import { Link } from '@inertiajs/vue3';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import { GiftsIllus, UndetectableIllus } from '@/Components/Icons/illus';
import dayjs from 'dayjs';
import { computed } from 'vue';

const props = defineProps({
    redeemableItems: Array,
});

const recentRedemptions = computed(() => {
    return props.redeemableItems
            .flatMap(row =>
                row.point_histories.map(record => ({
                    ...record,
                    product_name: row.product_name,
                }))
            )
            .sort((a, b) => dayjs(b.redemption_date).diff(dayjs(a.redemption_date)))
            .slice(0, 3)
});
</script>

<template>
    <div class="flex flex-col justify-start items-center gap-6 border border-primary-100 p-6 rounded-[5px]">
        <div class="flex items-center justify-between w-full">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Recent Redemption</span>
            <Link :href="route('loyalty-programme.points.showRecentRedemptions')">
                <CircledArrowHeadRightIcon2  
                    class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                />
            </Link>
        </div>

        <div class="flex flex-col gap-3 self-stretch" v-if="recentRedemptions.length > 0">
            <div class="flex flex-col justify-between items-start self-stretch gap-1 overflow-x-auto">
                <div 
                    class="w-full grid grid-cols-12 whitespace-nowrap items-center justify-between p-2 min-w-[440px] odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 gap-x-4"
                    v-for="(item, index) in recentRedemptions" :key="index"
                >
                    <span class="text-sm text-grey-900 font-medium col-span-3">{{ dayjs(item.redemption_date).format('YYYY/MM/DD') }}</span>
                    <span class="text-sm text-grey-900 font-medium col-span-5 truncate">{{ item.product_name }}</span>  
                    <span class="text-sm text-grey-900 font-medium col-span-1 text-center">x {{ item.qty }}</span>
                    <span class="text-sm text-grey-900 font-medium col-span-3 text-center">{{ item.handled_by.name }}</span>
                </div>
            </div>

            <div class="w-full flex flex-nowrap items-center gap-6 px-6 py-4 self-stretch bg-primary-50 rounded-[5px]">
                <div class="flex flex-col items-start justify-center self-stretch gap-2">
                    <span class="text-primary-900 text-base font-semibold self-stretch">Maximise Customer Delight</span>
                    <span class="text-grey-500 text-xs font-normal self-stretch">
                        Introducing more <span class="text-primary-900">enchanting redeemable item</span> to your customer is one of the way to keep them engaged. 
                    </span>
                </div>
                <GiftsIllus class="flex-shrink-0 w-40 h-24"/>
            </div>
        </div>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template>
    </div>
</template>
