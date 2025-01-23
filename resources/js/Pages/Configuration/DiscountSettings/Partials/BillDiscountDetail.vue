<script setup>
import RadioButton from '@/Components/RadioButton.vue';
import Toggle from '@/Components/Toggle.vue';
import dayjs from 'dayjs';

const props = defineProps({
    discount: {
        type: Object,
        required: true
    }
})

const getAvailableOn = (available_on) => {
    switch(available_on){
        case 'everyday': return 'Everyday';
        case 'weekday': return 'Every Weekday';
        case 'weekend': return 'Every Weekend';
    }
}

const formatPaymentMethod = (payment_method) => {

    return payment_method.map(method => method.charAt(0).toUpperCase() + method.slice(1)).join(', ');
}
</script>

<template>
    <div class="flex flex-col items-start gap-6 pt-2 pb-8 self-stretch max-h-[calc(100dvh-12rem)] overflow-y-auto scrollbar-webkit scrollbar-thin">
        <!-- Basic Detail -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Basic Detail</span>
                </div>
            </div>
            <div class="col-span-9 flex flex-col items-start gap-6 flex-[1_0_0]">
                <!-- Basic detail - form section 1 -->
                <div class="flex items-start gap-6 self-stretch">
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <span class="self-stretch text-grey-950 text-base font-bold">Discount Name</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Give this discount a catchy name.</span>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.name }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <span class="self-stretch text-grey-950 text-base font-bold">Discount Rate</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Enter the percentage/amount of this discount.</span>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">
                                {{ props.discount.discount_type === 'percentage' ? props.discount.discount_rate + '%' : 'RM ' + props.discount.discount_rate }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Basic detail - form section 2 -->
                <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <span class="self-stretch text-grey-950 text-base font-bold">Date Period</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Date range during which this discount is applicable.</span>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ dayjs(props.discount.discount_from).format('DD/MM/YYYY') }} - {{ dayjs(props.discount.discount_to).format('DD/MM/YYYY') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Basic detail - form section 3 -->
                <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                        <div class="flex items-start gap-4 self-stretch">
                            <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                <span class="self-stretch text-grey-950 text-base font-bold">Available on</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Select the day(s) which the discount is available.</span>
                            </div>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ getAvailableOn(props.discount.available_on) }}</span>
                        </div>
                    </div>
                    <div class="col-span-1 flex flex-col items-start gap-4 flex-[1_0_0]" v-if="props.discount.start_time || props.discount.end_time">
                        <div class="flex items-start gap-1 self-stretch">
                            <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                <span class="self-stretch text-grey-950 text-base font-bold">Time Period</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Indicate the time period</span>
                            </div>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.start_time ? (props.discount.start_time).slice(0, -3) + ' - ' : '' }}{{ props.discount.end_time ? (props.discount.end_time).slice(0,-3) : '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Criteria -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Discount Criteria</span>
                </div>
            </div>
            <div class="col-span-9 w-1/2 flex flex-col items-start gap-7 flex-[1_0_0]">
                <div class="flex items-start gap-6 self-stretch">
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0] self-stretch">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <span class="self-stretch text-grey-950 text-base font-bold">{{ props.discount.criteria === 'min_spend' ? 'Min. Spend' : 'Min. Item Purchased' }}</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">
                                {{ props.discount.criteria === 'min_spend' 
                                    ? 'Orders that meet the minimum spending requirement will receive this discount at the time of payment.' 
                                    : 'Orders that meet the minimum item purchased requirement will receive this discount at the time of payment.' 
                                }}
                            </span>
                        </div>
                        <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                            <span class="flex-[1_0_0] text-grey-950 text-md font-medium">
                                {{ props.discount.criteria === 'min_spend' 
                                ? 'RM ' + parseFloat(props.discount.requirement).toFixed(2) : props.discount.requirement > 1 
                                                                    ? props.discount.requirement + ' items' : props.discount.requirement + ' item' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stacking Option -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Stacking Option</span>
                </div>
            </div>
            <div class="col-span-9 flex flex-col items-start gap-6">
                <div class="flex items-start gap-6 w-full" v-if="props.discount.is_stackable === true">
                    <div class="flex items-center justify-between flex-[1_0_0]">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="self-stretch text-grey-950 text-base font-bold">Stackable</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Allow this bill discount to be combined with member tier reward discounts and other bill discount</span>
                        </div>
                        <RadioButton 
                            :dynamic="false"
                            :value="true"
                            :checked="true"
                            class="!w-fit"
                        />
                    </div>
                </div>
                <div class="flex items-start gap-6 w-full" v-else>
                    <div class="flex items-center justify-between flex-[1_0_0]">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="self-stretch text-grey-950 text-base font-bold">Not stackable</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Don’t allow this bill discount to be combined with member tier reward discounts and other bill discount</span>
                        </div>
                        <RadioButton 
                            :dynamic="false"
                            :value="true"
                            :checked="true"
                            class="!w-fit"
                        />
                    </div>
                </div>
                <div class="flex flex-col p-5 items-start gap-6 self-stretch rounded-[5px] bg-grey-25" v-if="props.discount.is_stackable === false">
                    <div class="flex flex-col items-start gap-6 self-stretch">
                        <div class="flex items-start gap-3 self-stretch">
                            <div class="flex flex-col items-start gap-1 pl-3 flex-[1_0_0] border-l-[6px] border-primary-800">
                                <span class="self-stretch text-grey-950 text-md font-semibold">Conflict Resolution</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Choose how to handle conflicts when multiple discounts are applicable.</span>
                            </div>
                        </div>
                        <div class="flex pl-[14px] items-start gap-6 self-stretch">
                            <div class="flex items-center gap-4">
                                <RadioButton 
                                    :dynamic="false"
                                    :value="true"
                                    :checked="props.discount.conflict === 'keep'"
                                    :disabled="props.discount.conflict !== 'keep'"
                                    class="!w-fit"
                                />
                                <span class="text-grey-950 text-base font-medium">Keep existing discount</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <RadioButton 
                                    :dynamic="false"
                                    :value="true"
                                    :checked="props.discount.conflict === 'maximum_value'"
                                    :disabled="props.discount.conflict !== 'maximum_value'"
                                    class="!w-fit"
                                />
                                <span class="text-grey-950 text-base font-medium">Discount with maximum value</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Limits -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Usage Limits</span>
                </div>
            </div>
            <div class="col-span-9 flex flex-col items-start gap-7 flex-[1_0_0]">
                <div class="flex flex-col items-start gap-6 self-stretch">
                    <div class="grid grid-cols-2 items-start gap-6 self-stretch" v-if="props.discount.customer_usage > 0">
                        <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Customer Usage</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Limit the discount to number of uses per customer.</span>
                                </div>
                            </div>
                            <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                                <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.customer_usage }} use per customer</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Customer Usage Renew Every</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Frequency for customer usage renewal.</span>
                                </div>
                            </div>
                            <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                                <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.customer_usage_renew ? (props.discount.customer_usage_renew).charAt(0).toUpperCase() + props.discount.customer_usage_renew.slice(1) : 'Never' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 items-start gap-6 self-stretch" v-if="props.discount.total_usage > 0">
                        <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Total Usage</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Limit the discount to a specific number of total uses.</span>
                                </div>
                            </div>
                            <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                                <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.total_usage }} bills</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Total Usage Renew Every</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Frequency for total usage renewal.</span>
                                </div>
                            </div>
                            <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                                <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.total_usage_renew ? (props.discount.total_usage_renew).charAt(0).toUpperCase() + props.discount.total_usage_renew.slice(1) : 'Never' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Exclusive Only -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Member Exclusive Only</span>
                </div>
            </div>
            <div class="col-span-9 w-1/2 flex items-start gap-6 flex-[1_0_0]">
                <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                    <div class="flex items-start gap-4 self-stretch">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="self-stretch text-grey-950 text-base font-bold">Tier</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Tier(s) which eligible for this discount.</span>
                        </div>
                    </div>
                    <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                        <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.tier ? props.discount.tier_names.join(', ') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eligible Payment Method -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Member Exclusive Only</span>
                </div>
            </div>
            <div class="col-span-9 w-1/2 flex items-start gap-6 flex-[1_0_0]">
                <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                    <div class="flex items-start gap-4 self-stretch">
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="self-stretch text-grey-950 text-base font-bold">Select Eligible Method</span>
                            <span class="self-stretch text-grey-950 text-sm font-normal">Payment method(s) which is eligible.</span>
                        </div>
                    </div>
                    <div class="flex py-1 justify-center items-center gap-2.5 self-stretch">
                        <span class="flex-[1_0_0] text-grey-950 text-md font-medium">{{ props.discount.payment_method ? formatPaymentMethod(props.discount.payment_method) : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto Apply -->
        <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
            <div class="col-span-3 flex justify-between items-start">
                <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                    <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Auto Apply</span>
                </div>
            </div>
            <div class="col-span-9 flex items-start gap-6 self-stretch">
                <div class="flex items-center gap-4 flex-[1_0_0]">
                    <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                        <span class="self-stretch text-grey-950 text-base font-bold">Enable auto apply</span>
                        <span class="self-stretch text-grey-950 text-sm font-normal">Discount will be automatically apply when all discount criteria are met. </span>
                    </div>
                    <Toggle 
                        :checked="props.discount.is_auto_applied === true"
                        :disabled="true"
                        />
                </div>
            </div>
        </div>
    </div>
</template>

