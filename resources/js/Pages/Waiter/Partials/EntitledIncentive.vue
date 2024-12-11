<script setup>
import Button from '@/Components/Button.vue';
import { EmptyEntitledIncentive } from '@/Components/Icons/illus.jsx';
import { CircledArrowHeadRightIcon2, TargetIcon } from '@/Components/Icons/solid';
import { transactionFormat } from '@/Composables';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    configIncentive: Array,
});

const { formatAmount } = transactionFormat();


</script>

<template>
    <div class="flex flex-col gap-4 h-full">
        <div class="relative flex gap-5">
            <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Entitled Incentive</span>
            <Link :href="route('waiter.viewEmployeeIncentive')">
                <CircledArrowHeadRightIcon2  
                    class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                />
            </Link>
        </div>
        <div class="flex flex-col px-0.5 items-start gap-4 self-stretch" v-if="configIncentive.length > 0" v-for="data in configIncentive" :key="data.incentive_id">
            <Link 
            :href="route('configuration.incentCommDetail', data.incentive_id)"
            class="w-full flex p-3 flex-col items-start gap-0.5 self-strech rounded-[5px] bg-primary-25">
                <span v-if="data.config_incentive.type === 'fixed'" class="text-primary-900 text-md font-semibold">RM {{ data.config_incentive.rate }}</span>
                <span v-if="data.config_incentive.type === 'percentage'" class="text-primary-900 text-md font-semibold">{{ parseInt(data.config_incentive.rate*100) }}%</span>
                <span class="text-grey-900 text-sm font-normal">of total monthly sales</span>
                <div class="flex items-center gap-1">
                    <TargetIcon />
                    <span class="text-2xs font-normal text-primary-800">RM {{ formatAmount(data.config_incentive.monthly_sale) }}</span>
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
                    :href="route('waiter.viewEmployeeIncentive')"
                    class="!w-max"
                >
                    Go to Add
                </Button>
            </div>
        </div>
    </div>
</template>
