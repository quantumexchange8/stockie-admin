<script setup>
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { PercentageIcon } from '@/Components/Icons/solid';
import MultiSelect from '@/Components/MultiSelect.vue';
import RadioButton from '@/Components/RadioButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Toggle from '@/Components/Toggle.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    discount: {
        type: Object,
        required: true,
    }
})

const emit = defineEmits(['isDirty', 'close', 'getBillDiscounts']);
const { showMessage } = useCustomToast();

const available_on = ref([
    { text: 'Everyday', value: 'everyday' },
    { text: 'Every weekday', value: 'weekday' },
    { text: 'Every weekend', value: 'weekend' }
]);

const criteria = ref([
    { text: 'Min. spend', value: 'min_spend' },
    { text: 'Min. quantity purchased', value: 'min_quantity' }
]);

const conflict = ref([
    { text: 'Keep existing discount', value: 'keep' },
    { text: 'Discount with maximum value', value: 'maximum_value'}
]);

const usage_renew = ref([
    { text: 'Daily', value: 'daily' },
    { text: 'Weekly', value: 'weekly' },
    { text: 'Monthly', value: 'monthly' },
]);

const eligible_method = ref([
    { text: 'Cash', value: 'cash' },
    { text: 'Card', value: 'card' },
    { text: 'E-Wallets', value: 'e-wallets' },
])

const discount = ref(props.discount);
const isLoading = ref(false);
const isTimePeriod = ref(!!discount.value.start_time && !!discount.value.end_time);
const isCustomerUsage = ref(discount.value.customer_usage > 0);
const isTotalUsage = ref(discount.value.total_usage > 0);
const isCustomerUsageRenew = ref(!!discount.value.customer_usage_renew);
const isTotalUsageRenew = ref(!!discount.value.total_usage_renew);
const isMemberExclusive = ref(!!discount.value.tier && discount.value.tier.length > 0);
const isEligibleMethod = ref(!!discount.value.payment_method && discount.value.payment_method.length > 0);
const tiers = ref(null);

const form = useForm({
    discount_name: discount.value.name,
    discount_isRate: !!discount.value.discount_type === 'percentage',
    discount_rate: discount.value.discount_rate.toString(),
    discount_period: [new Date(discount.value.discount_from), new Date(discount.value.discount_to)],
    available_on: discount.value.available_on,
    time_period_from: discount.value.start_time ? (discount.value.start_time).slice(0,-3) : '',
    time_period_to: discount.value.end_time ? (discount.value.end_time).slice(0,-3) : '',
    discount_criteria: discount.value.criteria,
    discount_requirement: discount.value.criteria === 'min_spend' ? parseFloat(discount.value.requirement).toFixed(2) : discount.value.requirement.toString(),
    is_stackable: discount.value.is_stackable,
    conflict: discount.value.conflict ?? 'keep',
    customer_usage: discount.value.customer_usage > 0 ? discount.value.customer_usage.toString() : '0',
    customer_usage_renew: discount.value.customer_usage_renew ?? '',
    total_usage: discount.value.total_usage > 0 ? discount.value.total_usage.toString() : '0',
    remaining_usage: discount.value.remaining_usage > 0 ? discount.value.remaining_usage.toString() : '0',
    total_usage_renew: discount.value.total_usage_renew ?? '',
    tier: discount.value.tier,
    payment_method: discount.value.payment_method,
    is_auto_applied: discount.value.is_auto_applied,
})

const initialForm = JSON.parse(JSON.stringify(form));

const deepCompare = (obj1, obj2) => {
  return JSON.stringify(obj1) === JSON.stringify(obj2);
};

const isDirty = computed(() => !deepCompare(initialForm, form));


const toggleTimePeriod = () => {
    isTimePeriod.value = !isTimePeriod.value;
    if (!isTimePeriod.value) {
        form.time_period_from = '';
        form.time_period_to = '';
    }
}

const getAllTiers = async() => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('configurations.getAllTiers'));
        tiers.value = response.data.map(tier => ({
            text: tier.name,
            value: tier.id,
        }));
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const editBillDiscount = () => {
    // console.log(form.data());
    form.put(route('configurations.editBillDiscount', discount.value.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: 'Bill Discount has been edited successfully.'
            })
            emit('close', 'leave');
            emit('getBillDiscounts');
            form.reset();
        }
    })
}

const isFormValid = computed(() => {
    return ['discount_name', 'discount_rate', 'discount_period', 'available_on', 'discount_requirement'].every(field => form[field]) && 
            form.total_usage >= (discount.value.total_usage - form.remaining_usage) &&
            (form.customer_usage > 0 ? form.customer_usage >= discount.value.highest_customer_used_count : true);
})

const customerUsageErrorMsg = computed(() => {
    if (!form.errors) return '';
    if (form.errors && form.errors.customer_usage) return form.errors.customer_usage;

    const usedCount = discount.value.highest_customer_used_count;
    return form.customer_usage < usedCount
            ? `New customer usage cannot be less than highest amount of redeemed count of the customers (${usedCount})`
            : '';
});

const totalUsageErrorMsg = computed(() => {
    if (!form.errors) return '';
    if (form.errors && form.errors.total_usage) return form.errors.total_usage;

    const usedCount = discount.value.total_usage - form.remaining_usage;
    return form.total_usage < usedCount
            ? `New total usage cannot be less than already redeemed count (${usedCount})`
            : '';
});

onMounted(() => {
    getAllTiers();
})

watch(
  () => isDirty.value,
  (newVal) => {
    emit('isDirty', newVal);
  }
);


</script>

<template>
    <form novalidate @submit.prevent="editBillDiscount">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 items-start gap-6 self-stretch">
                        <div class="flex col-span-1 flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <span class="self-stretch text-grey-950 text-base font-bold">Discount Name</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Give this discount a catchy name.</span>
                            </div>
                            <TextInput 
                                :inputName="'discount_name'"
                                :errorMessage="form.errors?.discount_name"
                                :disabled="true"
                                :placeholder="'e.g. 20% Discount for all'"
                                v-model="form.discount_name"
                            />
                        </div>
                        <div class="flex col-span-1 flex-col items-start gap-4 flex-[1_0_0]">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <span class="self-stretch text-grey-950 text-base font-bold">Discount Rate</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Enter the percentage/amount of this discount.</span>
                            </div>
                            <div class="flex items-start gap-2 self-stretch">
                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col size-[47px] justify-center items-center gap-2.5 rounded-[5px]" 
                                        :class="form.discount_isRate === true ? 'bg-primary-50' : 'bg-grey-50 border border-solid border-grey-50'"
                                    >
                                        <PercentageIcon class="size-5" :class="!!form.discount_isRate ? '!text-primary-900' : '!text-grey-300'" />
                                    </div>
                                    <div class="flex flex-col size-[47px] justify-center items-center gap-2.5 rounded-[5px]" 
                                        :class="form.discount_isRate === false ? 'bg-primary-50' : 'bg-grey-50 border border-solid border-grey-50'"
                                    >
                                        <span class="text-base font-normal" :class="!form.discount_isRate ? '!text-primary-900' : '!text-grey-300'">RM</span>
                                    </div>
                                </div>
                                <TextInput 
                                    :inputName="'discount_rate'"
                                    :inputType="'number'"
                                    withDecimal
                                    :errorMessage="form.errors?.discount_rate"
                                    :placeholder="'10'"
                                    :disabled="true"
                                    :iconPosition="!!form.discount_isRate ? 'right' : 'left'"
                                    :class="!form.discount_isRate ? '[&>div:nth-child(1)>input]:text-right [&>div:nth-child(1)>input]:pr-4 [&>div:nth-child(1)>input]:mb-0' : '[&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pl-4 [&>div:nth-child(1)>input]:mb-0'"
                                    v-model="form.discount_rate"
                                >
                                    <template #prefix>
                                        <span>{{ form.discount_isRate ? '%' : 'RM' }}</span>
                                    </template>
                                </TextInput>
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
                            <DateInput 
                                :inputName="'discount_period'"
                                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                :errorMessage="form.errors?.discount_period"
                                :range="true"
                                :minDate="new Date()"
                                v-model="form.discount_period"
                            />
                        </div>
                    </div>

                    <!-- Basic detail - form section 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 items-start gap-6 self-stretch">
                        <div class="flex flex-col col-span-1 items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-4 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Available on</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Select the day(s) which the discount is available.</span>
                                </div>
                            </div>
                            <Dropdown 
                                :errorMessage="form.errors?.available_on"
                                :dataValue="discount.available_on"
                                :inputName="'available_on'"
                                :inputArray="available_on"
                                v-model="form.available_on"
                            />
                        </div>
                        <div class="flex flex-col col-span-1 items-start gap-4 flex-[1_0_0]">
                            <div class="flex items-start gap-1 self-stretch">
                                <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                    <span class="self-stretch text-grey-950 text-base font-bold">Time Period</span>
                                    <span class="self-stretch text-grey-950 text-sm font-normal">Indicate the time period</span>
                                </div>
                                <Toggle 
                                    :checked="isTimePeriod"
                                    @update:checked="toggleTimePeriod"
                                />
                            </div>
                            <div class="flex items-start gap-4">
                                <DateInput 
                                    :timeOnly="true"
                                    :errorMessage="form.errors?.time_period_from"
                                    :disabled="!isTimePeriod"
                                    v-model="form.time_period_from"
                                    class="[&>span>input]:min-w-0 [&>span>input]:max-w-[135px] md:[&>span>input]:max-w-[100px]"
                                />
                                <span :class="['text-base font-normal', isTimePeriod ? 'text-grey-950' : 'text-grey-300']">to</span>
                                <DateInput 
                                    :timeOnly="true"
                                    :errorMessage="form.errors?.time_period_to"
                                    :disabled="!isTimePeriod"
                                    v-model="form.time_period_to"
                                    class="[&>span>input]:min-w-0 [&>span>input]:max-w-[135px] md:[&>span>input]:max-w-[100px]"
                                />
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
                    <RadioButton
                        :optionArr="criteria"
                        :checked="form.discount_criteria"
                        v-model:checked="form.discount_criteria"
                        class="[&>div]:gap-5 [&>div>div>label]:whitespace-nowrap"
                    />

                    <div class="flex items-start gap-6 self-stretch">
                        <div class="flex flex-col items-start gap-4 flex-[1_0_0] self-stretch">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <span class="self-stretch text-grey-950 text-base font-bold">{{ form.discount_criteria === 'min_spend' ? 'Min. Spend' : 'Min. Item Purchased' }}</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">
                                    {{ form.discount_criteria === 'min_spend' 
                                        ? 'Orders that meet the minimum spending requirement will receive this discount at the time of payment.' 
                                        : 'Orders that meet the minimum item purchased requirement will receive this discount at the time of payment.' 
                                    }}
                                </span>
                            </div>
                            <TextInput
                                :inputType="'number'"
                                withDecimal
                                :errorMessage="form.errors?.discount_requirement"
                                :iconPosition="form.discount_criteria === 'min_spend' ? 'left' : 'right'"
                                :inputName="'discount_requirement'"
                                v-model="form.discount_requirement"
                            >
                                <template #prefix>
                                    <span>{{ form.discount_criteria === 'min_spend' ? 'RM' : 'item' }}</span>
                                </template>
                            </TextInput>
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
                    <div class="flex items-start gap-6 w-full">
                        <div class="flex items-center justify-between flex-[1_0_0]">
                            <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                <span class="self-stretch text-grey-950 text-base font-bold">Stackable</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Allow this bill discount to be combined with member tier reward discounts and other bill discount</span>
                            </div>
                            <RadioButton 
                                :dynamic="false"
                                :name="'stackable'"
                                :value="true"
                                :checked="form.is_stackable"
                                @update:checked="form.is_stackable = true"
                                class="!w-fit"
                            />
                        </div>
                    </div>
                    <div class="flex items-start gap-6 w-full">
                        <div class="flex items-center justify-between flex-[1_0_0]">
                            <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                <span class="self-stretch text-grey-950 text-base font-bold">Not stackable</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Don’t allow this bill discount to be combined with member tier reward discounts and other bill discount</span>
                            </div>
                            <RadioButton 
                                :dynamic="false"
                                :name="'stackable'"
                                :value="false"
                                :checked="form.is_stackable"
                                @update:checked="form.is_stackable = false"
                                class="!w-fit"
                            />
                        </div>
                    </div>
                    <transition
                        enter-active-class="transition duration-300 ease"
                        leave-active-class="transition duration-300 ease"
                        enter-from-class="opacity-0 translate-y-8"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-from-class="opacity-100 duration-300 translate-y-0"
                        leave-to-class="opacity-0 translate-y-8"
                        mode="out-in"
                    >
                    
                        <div class="flex flex-col p-5 items-start gap-6 self-stretch rounded-[5px] bg-grey-25" v-if="!form.is_stackable">
                            <div class="flex flex-col items-start gap-6 self-stretch">
                                <div class="flex items-start gap-3 self-stretch">
                                    <div class="flex flex-col items-start gap-1 pl-3 flex-[1_0_0] border-l-[6px] border-primary-800">
                                        <span class="self-stretch text-grey-950 text-md font-semibold">Conflict Resolution</span>
                                        <span class="self-stretch text-grey-950 text-sm font-normal">Choose how to handle conflicts when multiple discounts are applicable.</span>
                                    </div>
                                </div>
                                <RadioButton
                                    :optionArr="conflict"
                                    :errorMessage="form.errors?.conflict"
                                    :checked="form.conflict"
                                    v-model:checked="form.conflict"
                                    class="[&>div]:gap-6"
                                />
                            </div>
                        </div>
                    </transition>
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
                    <div class="flex items-start content-start gap-5 self-stretch flex-wrap">
                        <div class="flex items-center gap-2">
                            <RadioButton 
                                :dynamic="false"
                                :name="'usage'"
                                :value="true"
                                :checked="isCustomerUsage && !isTotalUsage"
                                @update:checked="isCustomerUsage = true; isTotalUsage = false;"
                                class="!w-fit"
                            />
                            <span class="text-grey-950 text-base font-normal">Customer Usage</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton 
                                :dynamic="false"
                                :name="'usage'"
                                :value="true"
                                :checked="isTotalUsage && !isCustomerUsage"
                                @update:checked="isTotalUsage = true; isCustomerUsage = false;"
                                class="!w-fit"
                            />
                            <span class="text-grey-950 text-base font-normal">Total Usage</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton 
                                :dynamic="false"
                                :name="'usage'"
                                :value="true"
                                :checked="isCustomerUsage && isTotalUsage"
                                @update:checked="isCustomerUsage = true; isTotalUsage = true"
                                class="!w-fit"
                            />
                            <span class="text-grey-950 text-base font-normal">Customer & Total Usage</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-6 self-stretch">
                        <div class="flex flex-col items-start w-full gap-4 flex-[1_0_0]" v-if="isCustomerUsage">
                            <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                                <div class="flex items-start gap-4 self-stretch">
                                    <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                        <span class="self-stretch text-grey-950 text-base font-bold">Customer Usage</span>
                                        <span class="self-stretch text-grey-950 text-sm font-normal">Limit the discount to number of uses per customer.</span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 self-stretch">
                                    <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                        <span class="self-stretch text-grey-950 text-base font-bold">Customer Usage Renew Every</span>
                                        <span class="self-stretch text-grey-950 text-sm font-normal">Frequency for customer usage renewal.</span>
                                    </div>
                                    <Toggle 
                                        :checked="isCustomerUsageRenew"
                                        @update:checked="isCustomerUsageRenew = !isCustomerUsageRenew; if(!isCustomerUsageRenew) form.reset('customer_usage_renew')"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                                <TextInput
                                    :errorMessage="customerUsageErrorMsg"
                                    :inputName="'customer_usage'"
                                    :placeholder="'100'"
                                    :iconPosition="'right'"
                                    v-model="form.customer_usage"
                                    class="[&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pl-4 [&>div:nth-child(1)>input]:mb-0"
                                >
                                    <template #prefix>
                                        <span class="text-grey-900 text-base font-normal">use per customer</span>
                                    </template>
                                </TextInput>
                                <Dropdown 
                                    :inputArray="usage_renew"
                                    :dataValue="form.customer_usage_renew ?? 'None'"
                                    :errorMessage="form.errors?.customer_usage_renew"
                                    :placeholder="isCustomerUsageRenew ? 'Select' : 'None'"
                                    :disabled="!isCustomerUsageRenew"
                                    v-model="form.customer_usage_renew"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col items-start w-full gap-4 flex-[1_0_0]" v-if="isTotalUsage">
                            <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                                <div class="flex items-start gap-4 self-stretch">
                                    <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                        <span class="self-stretch text-grey-950 text-base font-bold">Total Usage</span>
                                        <span class="self-stretch text-grey-950 text-sm font-normal">Limit the discount to a specific number of total uses.</span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 self-stretch">
                                    <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                        <span class="self-stretch text-grey-950 text-base font-bold">Total Usage Renew Every</span>
                                        <span class="self-stretch text-grey-950 text-sm font-normal">Frequency for total usage renewal.</span>
                                    </div>
                                    <Toggle 
                                        :checked="isTotalUsageRenew"
                                        @update:checked="isTotalUsageRenew = !isTotalUsageRenew; if(!isTotalUsageRenew) form.reset('total_usage_renew')"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 items-start gap-6 self-stretch">
                                <TextInput
                                    :errorMessage="totalUsageErrorMsg"
                                    :inputName="'total_usage'"
                                    :placeholder="'100'"
                                    :iconPosition="'right'"
                                    v-model="form.total_usage"
                                    class="[&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pl-4 [&>div:nth-child(1)>input]:mb-0"
                                >
                                    <template #prefix>
                                        <span class="text-grey-900 text-base font-normal">bills</span>
                                    </template>
                                </TextInput>
                                <Dropdown 
                                    :inputArray="usage_renew"
                                    :dataValue="form.total_usage_renew ?? 'None'"
                                    :errorMessage="form.errors?.total_usage_renew"
                                    :placeholder="isTotalUsageRenew ? 'Select' : 'None'"
                                    :disabled="!isTotalUsageRenew"
                                    v-model="form.total_usage_renew"
                                />
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
                            <Toggle
                                :checked="isMemberExclusive"
                                @update:checked="isMemberExclusive = !isMemberExclusive; form.tier = []"
                            />
                        </div>
                        <MultiSelect 
                            :inputArray="tiers ?? []"
                            :labelText="''"
                            :dataValue="form.tier ?? ''"
                            :placeholder="!tiers || isLoading || !isMemberExclusive ? 'None' : 'Select'"
                            :errorMessage="form.errors?.tier"
                            :filter="false"
                            :disabled="!tiers || isLoading ||!isMemberExclusive"
                            v-model="form.tier"
                        />
                    </div>
                </div>
            </div>

            <!-- Eligible Payment Method -->
            <div class="grid grid-cols-12 p-6 items-start gap-7 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)]">
                <div class="col-span-3 flex justify-between items-start">
                    <div class="flex items-start gap-3 flex-[1_0_0] self-stretch">
                        <span class="pl-3 flex-[1_0_0] text-grey-950 text-md font-semibold border-l-[5px] border-primary-800">Eligible Payment Method</span>
                    </div>
                </div>
                <div class="col-span-9 w-1/2 flex items-start gap-6 flex-[1_0_0]">
                    <div class="flex flex-col items-start gap-4 flex-[1_0_0]">
                        <div class="flex items-start gap-4 self-stretch">
                            <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                                <span class="self-stretch text-grey-950 text-base font-bold">Select Eligible Method</span>
                                <span class="self-stretch text-grey-950 text-sm font-normal">Payment method(s) which is eligible.</span>
                            </div>
                            <Toggle 
                                :checked="isEligibleMethod"
                                @update:checked="isEligibleMethod = !isEligibleMethod; form.payment_method = []"
                            />
                        </div>
                        <MultiSelect 
                            :inputArray="eligible_method"
                            :labelText="''"
                            :dataValue="form.payment_method ?? ''"
                            :placeholder="!isEligibleMethod ? 'None' : 'Select'"
                            :errorMessage="form.errors?.payment_method"
                            :disabled="!isEligibleMethod"
                            :filter="false"
                            v-model="form.payment_method"
                        />
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
                            :checked="form.is_auto_applied"
                            @update:checked="form.is_auto_applied = !form.is_auto_applied"
                            v-model:checked="form.is_auto_applied"
                        />
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
            <Button
                :variant="'tertiary'"
                :size="'lg'"
                :type="'button'"
                @click="emit('close', 'close')"
            >
                Cancel
            </Button>
            <Button
                :variant="'primary'"
                :size="'lg'"
                :disabled="form.processing || isLoading || !isFormValid"
                :type="'submit'"
            >
                Confirm
            </Button>
        </div>
    </form>
</template>

