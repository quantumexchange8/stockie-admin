<script setup>
import { ArrowDownIcon, ArrowUpIcon, OrderToday, ProductSoldToday, SalesTodayIcon } from '@/Components/Icons/solid';

const props = defineProps({
    sales: {
        type: Number,
        default: 0,
    },
    productSold: {
        type: Number,
        default: 0,
    },
    order: {
        type: Number,
        required: true,
    },
    compareSold: {
        type: Number,
        default: 0,
    },
    compareSale: {
        type: Number,
        default: 0,
    },
    compareOrder: {
        type: Number,
        required: true,
    }
})
</script>

<template>
    <div class="w-full flex items-start gap-5">
        <!-- Sales today -->
        <div class="w-full h-full flex flex-col p-6 items-start gap-3 rounded-[5px] border-[1.333px] border-solid border-primary-100 bg-gradient-to-br from-primary-900 from-[2.42%] to-[#5E0A0E] to-[102.62%]">
            <SalesTodayIcon class="flex-shrink-0 size-[60px]" />
            <div class="flex flex-col items-start gap-1.5 self-stretch">
                <span class="text-primary-200 text-xs font-normal">Sales Today</span>
                <div class="flex justify-center items-center">
                    <span class="text-primary-25 text-base font-semibold whitespace-nowrap">RM {{ parseFloat(props.sales).toFixed(2) }}</span>
                </div>
                <div class="flex items-center gap-0.5">
                    <template v-if="props.compareSale < 0 ">
                        <ArrowDownIcon />
                        <span class="text-primary-400 text-[13.333px] font-normal">{{ props.compareSale }}%</span>
                    </template>
                    <template v-if="props.compareSale > 0">
                        <ArrowUpIcon />
                        <span class="text-green-600 text-[13.333px] font-normal">{{ props.compareSale }}%</span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Products Sold today -->
        <div class="w-full h-full flex flex-col p-6 items-start gap-3 rounded-[5px] border-[1.333px] border-solid border-primary-100 bg-[rgba(255,255,255,0.60)]">
            <ProductSoldToday class="flex-shrink-0 size-[60px]" />
            <div class="flex flex-col items-start gap-1.5 self-stretch">
                <span class="text-grey-900 text-xs font-medium">Products Sold Today</span>
                <div class="flex justify-center items-center gap-[13.333px]">
                    <span class="text-primary-900 text-base font-semibold">{{ props.productSold }}</span>
                </div>
                <div class="flex items-center gap-0.5">
                    <template v-if="props.compareSold < 0 ">
                        <ArrowDownIcon />
                        <span class="text-primary-400 text-[13.333px] font-normal">{{ props.compareSold }}%</span>
                    </template>
                    <template v-if="props.compareSold > 0">
                        <ArrowUpIcon />
                        <span class="text-green-600 text-[13.333px] font-normal">{{ props.compareSold }}%</span>
                    </template>
                </div>
            </div>
        </div>

        <!-- order today -->
        <div class="w-full h-full flex flex-col p-6 items-start gap-3 rounded-[5px] border-[1.333px] border-solid border-primary-100 bg-[rgba(255,255,255,0.60)]">
            <OrderToday class="flex-shrink-0 size-[60px]" />
            <div class="flex flex-col items-start gap-1.5 self-stretch">
                <span class="text-grey-900 text-xs font-medium">Order Today</span>
                <div class="flex justify-center items-center gap-[13.333px]">
                    <span class="text-primary-900 text-base font-semibold">{{ props.order }}</span>
                </div>
                <div class="flex items-center gap-0.5">
                    <template v-if="props.compareOrder < 0 ">
                        <ArrowDownIcon />
                        <span class="text-primary-400 text-[13.333px] font-normal">{{ props.compareOrder }}%</span>
                    </template>
                    <template v-if="props.compareOrder > 0 ">
                        <ArrowUpIcon />
                        <span class="text-green-600 text-[13.333px] font-normal">{{ props.compareOrder }}%</span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

