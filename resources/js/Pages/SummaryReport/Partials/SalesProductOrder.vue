<script setup>
import { BottleIcon, GrowthIcon, ListIcon, WaiterSalesIcon } from '@/Components/Icons/solid';

const props = defineProps({
    totalSales: {
        type: [String, Number],
        default: 0,
    },
    totalProducts: {
        type: Number,
        default: 0,
    },
    totalOrders: {
        type: Number,
        default: 0,
    }
})

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '0.00';

    const formatter = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return formatter.format(value);
};

const formatNumbers = (value) => {
    if (value === null || value === undefined) return '0';

    const num = parseFloat(value);

    const hasDecimals = num % 1 !== 0;

    const formatter = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: hasDecimals ? 2 : 0,
        maximumFractionDigits: 2,
    });

    return formatter.format(num);
};


</script>

<template>
    <div class="w-full">
        <div class="flex md:flex-col gap-5 flex-row">
            <div 
                class="w-full flex p-5 flex-col items-start gap-2.5 
                grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100
                bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative"
            >
                <div class="flex flex-col gap-1 self-stretch"> 
                    <GrowthIcon />
                    <span class="text-primary-100 text-sm font-medium whitespace-nowrap w-full">Total sales</span>
                    <span class="text-primary-25 text-lg font-medium ">RM {{ formatCurrency(props.totalSales) }}</span>
                </div>
                <div class="absolute bottom-0 right-0">
                    <WaiterSalesIcon />
                </div>
            </div>

            <div 
                class="w-full flex p-5 flex-col items-start gap-2 justify-center bg-[#ffffff4d]
                grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100"
            >
                <BottleIcon />
                <div class="flex flex-col gap-1 self-stretch">
                    <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Total Product Sold</span>
                    <span class="text-primary-900 text-lg font-medium whitespace-nowrap">{{ formatNumbers(props.totalProducts) }}</span>
                </div>
            </div>

            <div 
                class="w-full flex p-5 flex-col items-start gap-2.5 justify-center bg-[#ffffff4d]
                grow shrink-0 basis-0 self-stretch rounded-[5px] border border-solid border-primary-100"
            >
                <div class="flex flex-col gap-1 self-stretch">
                    <ListIcon />
                    <span class="text-grey-900 text-sm font-medium whitespace-nowrap">Total Order</span>
                    <span class="text-primary-900 text-lg font-medium whitespace-nowrap">{{ formatNumbers(props.totalOrders ) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
