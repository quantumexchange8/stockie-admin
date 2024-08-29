<script setup>
import Button from '@/Components/Button.vue';
import { EmptyEntitledIncentive } from '@/Components/Icons/illus.jsx';
import { CircledArrowHeadRightIcon2, TargetIcon } from '@/Components/Icons/solid';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    data: Object,
    configIncentive: Array,
});

function formatMonthlySale (monthly_sale){
    return monthly_sale.substring(0, monthly_sale.length-3);
}

function formatPercentageRate (rate){
    return rate * 100 + '%';
}

</script>

<template>
    <div class="flex flex-col gap-4 h-full">
        <div class="relative flex gap-5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Entitled Incentive</span>
            <Link :href="route('configurations')">
                    <CircledArrowHeadRightIcon2  
                        class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                    />
            </Link>
        </div>
        <div class="flex flex-col px-0.5 items-start gap-4 self-stretch" v-if="configIncentive" v-for="data in configIncentive">
            <Link 
            :href="route('configurations')"
            class="w-full flex p-3 flex-col items-start gap-0.5 self-strech rounded-[5px] bg-primary-25">
                <span v-if="data.type === 'fixed'" class="text-primary-900 text-md font-semibold">RM {{ data.rate }}</span>
                <span v-if="data.type === 'percentage'" class="text-primary-900 text-md font-semibold">{{ formatPercentageRate(data.rate) }}</span>
                <span class="text-grey-900 text-sm font-normal">of total monthly sales</span>
                <div class="flex items-center gap-1">
                    <TargetIcon />
                    <span class="text-2xs font-normal text-primary-800">RM {{ formatMonthlySale(data.monthly_sale) }}</span>
                </div>
            </Link>
        </div>

        <!-- blank state -->
        <div class="p-2 flex flex-col items-center text-center h-full justify-between self-stretch" v-else>
            <EmptyEntitledIncentive />
            <div class="flex flex-col gap-4 items-center text-center">
                <span class="text-sm font-medium text-primary-900 text-balance">This employee does not entitled to any incentive commission yet.</span>
                <Button
                    :type="'button'"
                    :href="route('configurations')"
                    class="!w-max"
                >Go to Add
                </Button>
            </div>
        </div>
    </div>
</template>
