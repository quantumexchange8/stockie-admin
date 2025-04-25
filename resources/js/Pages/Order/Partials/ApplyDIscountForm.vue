<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast } from '@/Composables';
import { useForm, usePage} from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { CheckCircleIcon, CustomerIcon, CustomerIcon2, DeleteIcon2, DiscountIcon, MergedIcon, SplitBillIcon, TimesIcon, ToastSuccessIcon } from '@/Components/Icons/solid';
import { onMounted } from 'vue';
import TabView from '@/Components/TabView.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import Order from '../Order.vue';
import dayjs from 'dayjs';
import isBetween from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

const props = defineProps({
    currentOrder: Object,
    currentTable: Object,
    paymentTransactions: Array,
    billAppliedDiscounts: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();
const emit = defineEmits(['close', 'isDirty', 'update:discounts']);

const order = ref(props.currentOrder);
const billDiscounts = ref([]);
const customerTierRewards = ref([]);
const selectedVoucherDiscount = ref(props.billAppliedDiscounts.find((discount) => discount.type === 'voucher') ?? null);
const billAmountKeyed = ref('0.00');
const paymentMethodsUsed = ref(props.paymentTransactions);
const appliedDiscounts = ref(props.billAppliedDiscounts);
const hoveredDiscount = ref('');
const tabs = ref(['Bill Discount', 'Tier Rewards']);

const fetchBillDiscounts = async () => {
    try {
        const response = await axios.get('/order-management/getBillDiscount', {
            params: { 
                current_customer_id: props.currentOrder.customer_id,
            }, 
        });
        billDiscounts.value = response.data;

        billDiscounts.value.forEach((discount) => discount['type'] = 'bill');
    } catch (error) {
        console.error(error);
    } finally {

    }
};

const fetchCustomerRewards = async () => {
    try {
        const rewardsResponse = await axios.get(route('orders.customer.tier.getCustomerTierRewards', props.currentOrder.customer_id));
        customerTierRewards.value = rewardsResponse.data
                .filter((reward) => {
                    return ['Discount (Amount)', 'Discount (Percentage)'].includes(reward.ranking_reward.reward_type) 
                            && (reward.status === 'Active' || (reward.status === 'Redeemed' && reward.ranking_reward_id === props.currentOrder.voucher_id));
                })
                .map((reward) => {
                    reward.ranking_reward['type'] = 'voucher';
                    reward.ranking_reward['customer_reward_id'] = reward.id;
                    return reward.ranking_reward;
                });

    } catch(error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    fetchBillDiscounts();
    if (props.currentOrder.customer_id) fetchCustomerRewards();
});

// Selects discount and update pay bill form's form discounts
const selectDiscount = (discount, type) => {
    if (type === 'bill') {
        const index = appliedDiscounts.value.findIndex(d => 
            d.id === discount.id && d.type === discount.type
        );

        if (index > -1) {
            appliedDiscounts.value.splice(index, 1);
        } else {
            appliedDiscounts.value.push(discount); 
        };

    } else if (type === 'voucher') {
        const index = appliedDiscounts.value.findIndex(d => d.type === 'voucher');

        if (index > -1) {
            appliedDiscounts.value.splice(index, 1);
        }
        appliedDiscounts.value.push(discount); 
        selectedVoucherDiscount.value = discount;
    }

    emit('update:discounts', appliedDiscounts.value);
};

const removeAppliedDIscount = (discount) => {
    if (discount.type === 'voucher') {
        const index = appliedDiscounts.value.findIndex(d => d.type === 'voucher');

        if (index > -1) {
            appliedDiscounts.value.splice(index, 1);
        }

        selectedVoucherDiscount.value = '';

    } else {
        const index = appliedDiscounts.value.findIndex(d => 
            d.id === discount.id && d.type === discount.type
        );

        if (index > -1) {
            appliedDiscounts.value.splice(index, 1);
        }
    }

    hoveredDiscount.value = '';
};

const applyManualDiscount = () => {
    appliedDiscounts.value.push({
        type: 'manual',
        rate: billAmountKeyed.value,
    }); 

    billAmountKeyed.value = '0.00';
    emit('update:discounts', appliedDiscounts.value);
};

// // Function to handle number pad input
const handleNumberInput = (value) => {
    // Check if the billAmountKeyed already has a decimal point
    if (billAmountKeyed.value.includes('.')) {
        // Split the value into integer and decimal parts
        const [integerPart, decimalPart] = billAmountKeyed.value.split('.');

        // If the decimal part already has 2 digits, ignore further input
        if (decimalPart.length >= 2) {
            // If the current value is '0.00', reset it before appending
            if (billAmountKeyed.value === '0.00') {
                billAmountKeyed.value = '';
            } else {
                return;
            }
        }
    }

    // If the current value is '0.00', reset it before appending
    if (billAmountKeyed.value === '0') {
        billAmountKeyed.value = '';
    }

    // Append the new value
    billAmountKeyed.value += value;
};

// Function to handle decimal point
const handleDecimal = () => {
    if (!billAmountKeyed.value.includes('.')) {
        billAmountKeyed.value += '.';
    }
};

// Function to clear the input
const clearInput = () => {
    billAmountKeyed.value = '0';
};

// Function to delete the last character
const deleteLastCharacter = () => {
    if (billAmountKeyed.value.length > 1) {
        billAmountKeyed.value = billAmountKeyed.value.slice(0, -1);
    } else {
        billAmountKeyed.value = '0';
    }
};

// Function to add predefined amounts
const addPredefinedAmount = (amount) => {
    const currentAmount = Number(billAmountKeyed.value);
    const newAmount = currentAmount + amount;
    billAmountKeyed.value = newAmount.toFixed(2);
};

const totalItemQuantityOrdered = computed(() => {
    return props.currentOrder.order_items.reduce((total, item) => {
        return total + item.item_qty;
    }, 0);
})

const isBillDiscountApplicable = (discount) => {
    // Early exit for inactive discounts
    if (discount.status === 'inactive') return false;

    const now = dayjs();
    const currentOrderTotal = Number(order.value.total_amount);
    const discountRequirement = Number(discount.requirement);
    const currentCustomerRanking = Number(order.value.customer?.ranking);
    
    // 1. Date Range Check
    if (!now.isSameOrAfter(discount.discount_from) || !now.isSameOrBefore(discount.discount_to)) {
        return false;
    }

    // 2. Day of Week Check
    const dayOfWeek = now.get('day');
    if (discount.available_on === 'weekday' && ![1,2,3,4,5].includes(dayOfWeek)) return false;
    if (discount.available_on === 'weekend' && ![0,6].includes(dayOfWeek)) return false;

    // 3. Time Window Check
    if (discount.start_time && discount.end_time) {
        if (now.isBefore(discount.start_time) || now.isSameOrAfter(discount.end_time)) {
            return false;   
        }
    }

    // 4. Stackability Check
    if (!discount.is_stackable) {
        // If there are any applied discounts
        if (appliedDiscounts.value.length > 0) {
            // Check if this discount is the currently selected one
            const isCurrentlySelected = appliedDiscounts.value.some(
                d => d.id === discount.id && d.type === 'bill'
            );
            
            // If it's not the currently selected one, disable it
            return isCurrentlySelected;
        }
    } else {
        // If there are any applied discounts
        if (appliedDiscounts.value.length > 0) {
            // Check if this discount is the currently selected one
            const currentlySelected = appliedDiscounts.value.some(
                d => d.type === 'bill' && !d.is_stackable
            );
            
            // If it's not the currently selected one, disable it
            if (currentlySelected) return false;
        }
    }

    // 5. Criteria Check
    const discountCriteriaReq = discount.criteria === 'min_spend'
            ? discount.criteria === 'min_spend' && currentOrderTotal < discountRequirement
            : discount.criteria === 'min_quantity' && totalItemQuantityOrdered.value < discountRequirement;

    // console.log(discountCriteriaReq);
    if (discountCriteriaReq) return false;
    
    // 6. Tier Check
    if (discount.tier?.length > 0 && !discount.tier.includes(currentCustomerRanking)) {
        return false;
    }
    
    // if (discount.payment_method?.length > 0) {
    //     const requiredMethods = discount.payment_method.map(method => {
    //         switch (method) {
    //             case 'cash': return 'Cash';
    //             case 'card': return 'Card';
    //             case 'e-wallets': return 'E-Wallet';
    //             default: return method;
    //         }
    //     });

    //     if (!paymentMethodsUsed.value.some(pmu => requiredMethods.includes(pmu.method))) {
    //         return false;
    //     }
    // }

    const matchingDiscountUsage = order.value.customer?.bill_discount_usages?.find((d) => d.bill_discount_id === discount.id);

    if (discount.total_usage > 0 && discount.remaining_usage <= 0) return false;
    
    if (
        discount.customer_usage > 0 &&
        order.value.customer && 
        matchingDiscountUsage &&
        matchingDiscountUsage.customer_usage >= discount.customer_usage
    ) return false;

    return true;
};

const isVoucherApplicable = (discount) => {
    // If min purchase is not active, it's always applicable
    if (discount.min_purchase !== 'active') return true;
    
    // If min purchase is active but amount is missing/zero, treat as applicable
    if (!discount.min_purchase_amount || discount.min_purchase_amount <= 0) return true;
    
    // Compare with order total (ensure both are numbers)
    const orderTotal = Number(props.currentOrder.total_amount) || 0;
    const minAmount = Number(discount.min_purchase_amount) || 0;
    
    return orderTotal >= minAmount;
};

const handleVoucherChange = (voucher) => {
    if (isVoucherApplicable(voucher)) {
        selectedVoucherDiscount.value = voucher;
    }
};

const formatPaymentMethodReq = (methods) => {
    if (methods) {
        let paymentMethods = Object.values(methods);

        if (paymentMethods.length > 0) {
            const formattedMethods = paymentMethods.map(method => {
                switch (method) {
                    case 'cash': return 'Cash';
                    case 'card': return 'Card';
                    case 'e-wallets': return 'E-Wallet';
                }
            });
            // console.log(formattedMethods);
    
            if (formattedMethods.length === 1) {
                return `${formattedMethods[0]} only`;
            } else if (formattedMethods.length === 2) {
                return `${formattedMethods.join('/')} only`;
            } else {
                return 'All methods';
            }
        }
    }

    return '';
}

watch(() => props.billAppliedDiscounts, (newValue) => {
    appliedDiscounts.value = newValue; 
}, { immediate: true });

watch(() => props.currentOrder, (newValue) => {
    order.value = newValue; 
}, { immediate: true });

</script>

<template>
    <div class="flex flex-col h-[calc(100dvh-10rem)] items-start gap-y-6 self-stretch">
        <!-- Actions -->
        <div class="flex w-full items-center gap-4 py-3 self-stretch">
            <p>Applied:</p>
            <div class="flex w-full items-center self-stretch py-1 gap-x-3 overflow-x-auto scrollbar-thin scrollbar-webkit min-h-10">
                <template v-for="(discount, index) in appliedDiscounts" :key="index">
                    <div 
                        class="flex items-center rounded-[2px] py-1 px-3 gap-x-2 border border-dashed border-grey-300 bg-grey-50"
                        @mouseover="hoveredDiscount = discount"
                        @mouseleave="hoveredDiscount = ''"
                    >
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.42 9.72098L9.72 3.42098C9.965 3.14098 10.315 3.00098 10.7 3.00098H15.6C16.37 3.00098 17 3.63098 17 4.40098V9.30098C17 9.68598 16.86 10.036 16.58 10.281L10.28 16.581C10.035 16.826 9.685 17.001 9.3 17.001C8.915 17.001 8.565 16.826 8.32 16.581L3.42 11.681C3.175 11.436 3 11.086 3 10.701C3 10.316 3.175 9.96598 3.42 9.72098ZM14.55 6.50098C15.145 6.50098 15.6 6.04598 15.6 5.45098C15.6 4.85598 15.145 4.40098 14.55 4.40098C13.955 4.40098 13.5 4.85598 13.5 5.45098C13.5 6.04598 13.955 6.50098 14.55 6.50098Z" fill="#45535F"/>
                        </svg>
                        <p class="text-grey-950 text-sm font-medium text-nowrap	">
                            <template v-if="discount.type === 'bill'">
                                {{ discount.discount_type === 'percentage' ? `${discount.discount_rate}%` : `RM ${discount.discount_rate}` }} {{ `(${discount.name})` }}
                            </template>

                            <template v-else-if="discount.type === 'voucher'">
                                <template v-if="discount.reward_type === 'Discount (Amount)'">
                                        {{ `RM ${discount.discount}` }}
                                </template>
                                <template v-if="discount.reward_type === 'Discount (Percentage)'">
                                        {{ `${discount.discount}%` }}
                                </template>
                                {{ `(${discount.ranking.name} ENTRY REWARD)` }}
                            </template>

                            <template v-else>
                                {{ `${discount.rate}%` }}
                            </template>
                        </p>
                        <TimesIcon 
                            v-if="hoveredDiscount === discount"
                            class="size-4 text-primary-500 hover:text-primary-600 cursor-pointer shrink-0" 
                            @click="removeAppliedDIscount(discount)"
                        />
                    </div>
                </template>
            </div>
        </div>

        <!-- Main -->
        <div class="flex size-full gap-4 self-stretch">
            <!-- Bill Overview -->
            <div class="flex w-1/2 h-full flex-col items-start gap-y-8 self-stretch">
                <TabView :tabs="tabs">
                    <template #tabFooter>
                        <div class="flex flex-col size-4 items-center justify-center rounded-full bg-primary-600" v-if="tabs[1] && pending > 0">
                            <span class="text-white text-center text-[8px] font-bold">{{ pending }}</span>
                        </div>
                    </template>

                    <template #bill-discount>
                        <div class="flex flex-col items-center self-stretch">
                            <div class="flex flex-col items-center gap-4 self-stretch max-h-[calc(100dvh-22rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                                <template v-if="billDiscounts.length > 0">
                                    <template v-for="(discount, index) in billDiscounts" :key="index">
                                        <div 
                                            class="flex p-3 flex-col self-stretch gap-2 items-start rounded-[5px] shadow-sm"
                                            :class="[{
                                                'border border-primary-900 bg-primary-25 cursor-pointer': !!appliedDiscounts.find((selectedDiscount) => selectedDiscount.id === discount.id && selectedDiscount.type === 'bill') && isBillDiscountApplicable(discount),
                                                'border border-grey-100 bg-white cursor-pointer': !!!appliedDiscounts.find((selectedDiscount) => selectedDiscount.id === discount.id && selectedDiscount.type === 'bill') && isBillDiscountApplicable(discount),
                                                'bg-grey-25 cursor-not-allowed': !isBillDiscountApplicable(discount),
                                            }]"
                                            @click="isBillDiscountApplicable(discount) ? selectDiscount(discount, 'bill') : ''"
                                        >
                                            <div class="flex gap-x-5 justify-between items-start self-stretch">
                                                <div class="flex flex-col items-start gap-y-1">
                                                    <p class="text-base font-bold self-stretch" :class="isBillDiscountApplicable(discount) ? 'text-grey-950' : 'text-grey-500'">
                                                        {{ discount.name }}
                                                    </p>
                                                    <p class="text-sm font-normal self-stretch" :class="isBillDiscountApplicable(discount) ? 'text-grey-900' : 'text-grey-400'">
                                                        {{ discount.discount_type === 'percentage' ? `${discount.discount_rate}%` : `RM ${discount.discount_rate}` }} off
                                                    </p>
                                                </div>

                                                <Checkbox 
                                                    :checked="!!appliedDiscounts.find((selectedDiscount) => selectedDiscount.id === discount.id && selectedDiscount.type === 'bill')"
                                                    :disabled="!isBillDiscountApplicable(discount)"
                                                />
                                            </div>

                                            <hr class="w-full text-grey-100">

                                            <div class="flex items-center gap-x-1 self-stretch">
                                                <span class="text-2xs font-normal" :class="isBillDiscountApplicable(discount) ? 'text-grey-800' : 'text-grey-300'">
                                                    Min. {{ discount.criteria === 'min_spend' ? `spend RM ${discount.requirement}` : `${discount.requirement} item purchased` }}
                                                </span>
                                                <span :class="isBillDiscountApplicable(discount) ? 'text-grey-200' : 'text-grey-100'">&#x2022;</span>
                                                <span class="text-2xs font-normal" :class="isBillDiscountApplicable(discount) ? 'text-grey-800' : 'text-grey-300'">
                                                    {{ discount.is_stackable ? 'Stackable' : 'Not Stackable' }}
                                                </span>
                                                <span v-if="formatPaymentMethodReq(discount.payment_method) !== ''" :class="isBillDiscountApplicable(discount) ? 'text-grey-200' : 'text-grey-100'">&#x2022;</span>
                                                <span class="text-2xs font-normal" :class="isBillDiscountApplicable(discount) ? 'text-grey-800' : 'text-grey-300'">
                                                    {{ formatPaymentMethodReq(discount.payment_method) }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                
                                <div class="flex flex-col items-center justify-center" v-else>
                                    <UndetectableIllus />
                                    <span class="text-primary-900 text-sm font-medium pb-5">No data can be shown yet...</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #tier-rewards>
                        <div class="flex flex-col items-center self-stretch">
                            <div class="flex flex-col items-center gap-4 self-stretch max-h-[calc(100dvh-22rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                                <template v-if="customerTierRewards.length > 0">
                                    <template v-for="(voucher, index) in customerTierRewards" :key="index">
                                        <div 
                                            class="flex p-3 flex-col self-stretch gap-2 items-start rounded-[5px] shadow-sm"
                                            :class="[{
                                                'border border-primary-900 bg-primary-25 cursor-pointer': selectedVoucherDiscount?.id === voucher.id && isVoucherApplicable(voucher),
                                                'border border-grey-100 bg-white cursor-pointer': selectedVoucherDiscount?.id !== voucher.id && isVoucherApplicable(voucher),
                                                'bg-grey-25 cursor-not-allowed': !isVoucherApplicable(voucher),
                                            }]"
                                            @click="isVoucherApplicable(voucher) ? selectDiscount(voucher, 'voucher') : ''"
                                        >
                                            <div class="flex gap-x-5 justify-between items-start self-stretch">
                                                <div class="flex flex-col items-start gap-y-1">
                                                    <p class="text-base font-bold self-stretch" :class="isVoucherApplicable(voucher) ? 'text-grey-950' : 'text-grey-500'">
                                                        {{ voucher.ranking.name }} Entry Rewards
                                                    </p>
                                                    <p class="text-sm font-normal self-stretch" :class="isVoucherApplicable(voucher) ? 'text-grey-900' : 'text-grey-400'">
                                                        <!-- {{ voucher.discount_type === 'percentage' ? `${voucher.discount_rate}%` : `RM ${voucher.discount_rate}` }} off -->
                                                        
                                                        <template v-if="voucher.reward_type === 'Discount (Amount)'">
                                                            {{ `RM ${voucher.discount}` }}
                                                        </template>
                                                        <template v-if="voucher.reward_type === 'Discount (Percentage)'">
                                                            {{ `${voucher.discount}%` }}
                                                        </template>
                                                            off
                                                    </p>
                                                </div>

                                                <RadioButton
                                                    :name="'voucher'"
                                                    :dynamic="false"
                                                    :value="voucher"
                                                    class="!w-fit"
                                                    :errorMessage="''"
                                                    :disabled="!isVoucherApplicable(voucher)"
                                                    v-model:checked="selectedVoucherDiscount"
                                                    @onChange="handleVoucherChange(voucher)"
                                                />  
                                            </div>

                                            <hr class="w-full text-grey-100">

                                            <div class="flex items-center gap-x-2 self-stretch">
                                                <span class="text-2xs font-normal" :class="isVoucherApplicable(voucher) ? 'text-grey-800' : 'text-grey-300'">
                                                    <template v-if="voucher.min_purchase === 'active' && (voucher.reward_type === 'Discount (Amount)' || voucher.reward_type === 'Discount (Percentage)')">
                                                        Min spend: RM {{ voucher.min_purchase_amount }}
                                                    </template>
                                                    <template v-if="voucher.min_purchase !== 'active' && (voucher.reward_type === 'Discount (Amount)'|| voucher.reward_type === 'Discount (Percentage)')">
                                                        No min. spend
                                                    </template>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                
                                <div class="flex flex-col items-center justify-center" v-else>
                                    <UndetectableIllus />
                                    <span class="text-primary-900 text-sm font-medium pb-5">No data can be shown yet...</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </TabView>
            </div>

            <!-- Inputs -->
            <div class="flex flex-col h-full justify-between w-1/2 items-start gap-y-5 p-5 shadow-md rounded-[5px] border border-grey-100 bg-white">
                <!-- Payment Inputs -->
                <div class="flex flex-col items-end h-full gap-y-5 self-stretch">
                    <!-- Payment Amount -->
                    <div class="flex justify-center items-center gap-x-4 flex-shrink-0 self-stretch rounded-[5px] bg-grey-25">
                        <p class="text-[48px] font-normal" :class="billAmountKeyed == 0 || billAmountKeyed < 0 ? 'text-grey-200' : 'text-grey-950'">
                            {{ billAmountKeyed >= 0 ? billAmountKeyed : '0.00' }} 
                            <span class="text-grey-950"> %</span>
                        </p>
                    </div>

                    <!-- Number Pad -->
                    <div class="flex flex-col items-start h-full gap-3 self-stretch">
                        <!-- Row 1 -->
                        <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                            <div @click="handleNumberInput('1')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">1</p>
                            </div>
                            <div @click="handleNumberInput('2')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">2</p>
                            </div>
                            <div @click="handleNumberInput('3')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">3</p>
                            </div>
                            <div @click="addPredefinedAmount(5)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">5%</p>
                            </div>
                        </div>
                        
                        <!-- Row 2 -->
                        <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                            <div @click="handleNumberInput('4')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">4</p>
                            </div>
                            <div @click="handleNumberInput('5')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">5</p>
                            </div>
                            <div @click="handleNumberInput('6')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">6</p>
                            </div>
                            <div @click="addPredefinedAmount(10)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">10%</p>
                            </div>
                        </div>
                        
                        <!-- Row 3 -->
                        <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                            <div @click="handleNumberInput('7')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">7</p>
                            </div>
                            <div @click="handleNumberInput('8')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">8</p>
                            </div>
                            <div @click="handleNumberInput('9')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">9</p>
                            </div>
                            <div @click="addPredefinedAmount(20)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">20%</p>
                            </div>
                        </div>
                        
                        <!-- Row 4 -->
                        <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                            <div @click="clearInput" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">C</p>
                            </div>
                            <div @click="handleNumberInput('0')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">0</p>
                            </div>
                            <div @click="handleDecimal" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <p class="text-grey-950 font-medium text-md">.</p>
                            </div>
                            <div @click="deleteLastCharacter" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                <DeleteIcon2 class="text-grey-950" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <Button
                    type="button"
                    variant="primary"
                    size="lg"
                    :disabled="billAmountKeyed == 0"
                    @click="applyManualDiscount"
                >
                    Apply discount
                </Button>
            </div>
        </div>
    </div>
</template>