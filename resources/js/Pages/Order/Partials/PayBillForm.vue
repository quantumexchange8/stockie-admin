<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Tag from '@/Components/Tag.vue';
import SearchBar from '@/Components/SearchBar.vue';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast } from '@/Composables';
import { useForm, usePage} from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { CheckCircleIcon, CheckIcon, CustomerIcon, CustomerIcon2, DeleteIcon2, DiscountIcon, MergedIcon, SplitBillIcon, TimesIcon, ToastSuccessIcon } from '@/Components/Icons/solid';
import { onMounted } from 'vue';
import { CardIcon, CashIcon, EWalletIcon } from '../../../Components/Icons/solid';
import SelectCustomer from './SelectCustomer.vue';
import MergeBill from './MergeBill.vue';
import SplitBill from './SplitBill.vue';
import ApplyDIscountForm from './ApplyDIscountForm.vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import isBetween from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import { MovingIllus } from '@/Components/Icons/illus';
import OrderReceipt from './OrderReceipt.vue';
import OrderInvoice from './OrderInvoice.vue';
import { wTrans, wTransChoice } from 'laravel-vue-i18n';

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

const props = defineProps({
    currentOrder: Object,
    currentTable: Object,
    isSplitBillMode: {
        type: Boolean,
        default: false
    },
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();
const emit = defineEmits(['close', 'isDirty', 'fetchZones', 'fetchOrderDetails', 'update:order-customer', 'closeDrawer','update:customer-point','update:customer-rank','fetchPendingServe', 'update:order']);

const order = ref(props.currentOrder);
const selectedCustomer = ref(order.value.customer);
const taxes = ref([]);
const billAmountKeyed = ref('0.00');
const change = ref('0.00');
const selectedMethod = ref('');
const isCustomerModalOpen = ref(false);
const isMergeBillModalOpen = ref(false);
const isSplitBillModalOpen = ref(false);
const isAddDiscountModalOpen = ref(false);
const isSuccessPaymentShow = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const paymentTransactions = ref([]); // Array to store payment transactions
const isSplitBillMode = ref(false);
const splitBillDetails = ref(null);
const selectedItems = ref([]);
const searchQuery = ref('');
const orderInvoice = ref(null);
const showOrderReceipt = ref(false);

const form = useForm({
    user_id: userId.value,
    order_id: props.currentOrder.id,
    change: 0,
    discounts: [],
    payment_methods: [],
    tables: props.currentOrder.order_table.filter(table => ['Pending Order', 'Order Placed', 'All Order Served'].includes(table.status)),
    split_bill_id: '',
    split_bill: {},
});

const fetchTaxes = async () => {
    form.processing = true;

    try {
        const response = await axios.get(route('orders.getAllTaxes'));
        taxes.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
};

const getDiscountAmount = (discount, type) => {
    if (discount) {
        if (type === 'bill') {
            return discount.discount_type === 'percentage'
                ? order.value.amount * (discount.discount_rate / 100)
                : discount.discount_rate;
        }
    
        if (type === 'voucher') {
            return discount.reward_type === 'Discount (Percentage)'
                ? order.value.amount * (discount.discount / 100)
                : discount.discount
        }
    } else {
        return 0.00;
    }
};

const totalItemQuantityOrdered = computed(() => {
    return order.value.order_items.reduce((total, item) => {
        return total + item.item_qty;
    }, 0);
})

const isBillDiscountApplicable = (discount, asToastMsg = false) => {
    // Early exit for inactive discounts
    if (discount.status === 'inactive') return false;

    const now = dayjs(order.value.created_at).tz("Asia/Kuala_Lumpur");
    const nowWithoutTime = dayjs(order.value.created_at).set('hour', 0).set('minute', 0).set('second', 0);
    const currentOrderTotal = Number(order.value.total_amount);
    const discountRequirement = Number(discount.requirement);
    const currentCustomerRanking = Number(order.value.customer?.ranking);
    
    // 1. Date Range Check
    if (!nowWithoutTime.isSameOrAfter(discount.discount_from) || !nowWithoutTime.isSameOrBefore(discount.discount_to)) {
        return asToastMsg ? wTrans('public.toast.date_range_check') : false;
    }

    // 2. Day of Week Check
    const dayOfWeek = now.get('day');
    if (discount.available_on === 'weekday' && ![1,2,3,4,5].includes(dayOfWeek)) {
        return asToastMsg ? wTransChoice('public.toast.day_of_week_check', 0) : false;
    }
    
    if (discount.available_on === 'weekend' && ![0,6].includes(dayOfWeek)) {
        return asToastMsg ? wTransChoice('public.toast.day_of_week_check', 1) : false;
    }

    // 3. Time Window Check
    if (discount.start_time && discount.end_time) {
        if (now.isBefore(discount.start_time) || now.isSameOrAfter(discount.end_time)) {
            return asToastMsg ? wTrans('public.toast.time_window_check') : false;
        }
    }

    // // 4. Stackability Check
    // if (!discount.is_stackable) {
    //     // If there are any applied discounts
    //     if (form.discounts.length > 0) {
    //         // Check if this discount is the currently selected one
    //         const isCurrentlySelected = form.discounts.some(
    //             d => d.id === discount.id && d.type === 'bill'
    //         );
            
    //         // If it's not the currently selected one, disable it
    //         if (!isCurrentlySelected) {
    //             return false;
    //         }
    //     }
    // }

    // 5. Criteria Check
    const discountCriteriaReq = discount.criteria === 'min_spend'
            ? discount.criteria === 'min_spend' && currentOrderTotal < discountRequirement
            : discount.criteria === 'min_quantity' && totalItemQuantityOrdered.value < discountRequirement;

    if (discountCriteriaReq) {
        // const criteriaName = discount.criteria === 'min_spend' ? 'min. spend of RM' : 'min. quantity of';

        return asToastMsg 
                ? wTransChoice('public.toast.criteria_check', discount.criteria === 'min_spend' ? 0 : 1, { discount_req: discountRequirement })
                : false;
    }
    
    // 6. Tier Check
    if (discount.tier?.length > 0 && !discount.tier.includes(currentCustomerRanking)) {
        return asToastMsg ? wTrans('public.toast.tier_check') : false;
    }
    
    // 7. Payment Method Check
    // if (discount.payment_method?.length > 0) {
        // return false;
    //     const requiredMethods = discount.payment_method.map(method => {
    //         switch (method) {
    //             case 'cash': return 'Cash';
    //             case 'card': return 'Card';
    //             case 'e-wallets': return 'E-Wallet';
    //             default: return method;
    //         }
    //     });

    //     // NEED TO CHECK WHETHER THIS CHECKING WORKS
    //     if (!paymentTransactions.value.some(pmu => requiredMethods.includes(pmu.method))) {
    //         if (!asToastMsg) return false;
    //     }
    // }

    // 8. Usage Limits Check
    const matchingDiscountUsage = props.currentOrder.customer?.bill_discount_usages?.find((d) => d.bill_discount_id === discount.id);
    
    if (discount.total_usage > 0 && discount.remaining_usage <= 0) {
        return asToastMsg ? wTrans('public.toast.total_remaining_usage_limit_check') : false;
    };
    
    if (
        discount.customer_usage > 0 &&
        props.currentOrder.customer && 
        matchingDiscountUsage &&
        matchingDiscountUsage.customer_usage >= discount.customer_usage
    ) {
        return asToastMsg ? wTrans('public.toast.customer_usage_limit_check') : false;
    };

    // if (discount.customer_usage > 0 && discount.total_usage > 0) {
    //     if (discount.current_total_usage_count >= discount.total_usage) {
    //         return asToastMsg ? "This discount's total accummulated number of usage has been exhausted thus, is currently unable to be applied." : false;
    //     }
        
    //     if (discount.current_customer_usage.customer_usage >= discount.customer_usage) {
    //         return asToastMsg ? "This discount's total number of usage for this customer has been exhausted thus, is currently unable to be applied." : false;
    //     }
    // };
    
    // if (discount.total_usage > 0) {
    //     if (discount.current_total_usage_count >= discount.total_usage) {
    //         return asToastMsg ? "This discount's total accummulated number of usage has been exhausted thus, is currently unable to be applied." : false;
    //     }
    // };

    // if (discount.customer_usage > 0) {
    //     if (discount.current_customer_usage.customer_usage >= discount.customer_usage) {
    //         return asToastMsg ? "This discount's total number of usage for this customer has been exhausted thus, is currently unable to be applied." : false;
    //     }
    // };

    return asToastMsg ? '' : true;
};

const processBillDiscounts = (billDiscounts) => {
    form.discounts = form.discounts.filter((d) => d.type !== 'bill');

    // Filter and process auto-applied discounts
    const autoAppliedDiscounts = billDiscounts
            .filter(discount => discount.is_auto_applied)
            .map(discount => ({ ...discount, type: 'bill' }));

    if (!autoAppliedDiscounts.length) return;

    // Handle stackable discounts
    const stackableDiscounts = autoAppliedDiscounts.filter(d => d.is_stackable);
    if (stackableDiscounts.length > 0) {
        // Apply all stackable discounts that pass conditions
        const applicableStackableDiscounts = stackableDiscounts.filter(d => isBillDiscountApplicable(d));
        const nonApplicableStackableDiscounts = stackableDiscounts.filter(d => !isBillDiscountApplicable(d));
        
        if (nonApplicableStackableDiscounts.length > 0) {
            nonApplicableStackableDiscounts.forEach(discount => {
                // Add the additional conditions and toast msg here (for not within date range, usage, etc)
                const toastDetails = isBillDiscountApplicable(discount, true);
                if (toastDetails) {
                    showMessage({ 
                        severity: 'warn',
                        summary: wTrans('public.toast.non_applicable_check', { discount_name: discount.name }),
                        detail: toastDetails,
                    });
                }
            });
        }

        if (applicableStackableDiscounts.length > 0) {
            // Add all stackable discounts (they can coexist with vouchers)
            form.discounts.push(...applicableStackableDiscounts);
            
            return;
        }
    }
    
    // Handle non-stackable discounts
    const nonStackableDiscounts = autoAppliedDiscounts.filter(d => !d.is_stackable);
    if (!nonStackableDiscounts.length) return;
    
    let selectedBillDiscount = nonStackableDiscounts[0];

    if (nonStackableDiscounts.length > 1) {
        selectedBillDiscount = nonStackableDiscounts.reduce((highest, current) => {
            const currentAmount = getDiscountAmount(current, 'bill');
            const highestAmount = getDiscountAmount(highest, 'bill');
            
            return currentAmount > highestAmount ? current : highest;
        });
    }

    const { conflict, name, criteria, requirement } = selectedBillDiscount;
    
    // Add the additional conditions and toast msg here (for not within date range, usage, etc)

    // if (!isBillDiscountApplicable(selectedBillDiscount)) {
    //     showMessage({ 
    //         severity: 'warn',
    //         summary: `'${name}' cannot be applied.`,
    //         detail: isBillDiscountApplicable(selectedBillDiscount, true),
    //     });
    //     return;
    // }

    if (conflict === 'keep') {
        showMessage({ 
            severity: 'warn',
            summary: wTrans('public.toast.non_applicable_check', { discount_name: name }),
            detail: wTrans('public.toast.conflict_keep_detail', { discount_name: name }),
        });
        return;
    }

    if (conflict === 'maximum_value') {
        const existingVoucher = form.discounts.find((discount) => discount.type === 'voucher');

        if (!existingVoucher) {
            form.discounts = [selectedBillDiscount];
            return;
        };
        
        const voucherAmount = getDiscountAmount(existingVoucher, 'voucher');
        const billAmount = getDiscountAmount(selectedBillDiscount, 'bill');
        
        if (billAmount > voucherAmount) {
            form.discounts = [selectedBillDiscount];
            showMessage({ 
                severity: 'warn',
                summary: wTrans('public.toast.conflict_max_summary', { old_discount: `'${existingVoucher?.ranking?.name ?? wTrans('public.voucher')} ${wTrans('public.entry_reward')}'`, new_discount: name }),
                detail: wTrans('public.toast.conflict_max_detail'),
            });
        }
    }
};

const fetchAutoAppliedDiscounts = async () => {
    form.processing = true;

    try {
        const response = await axios.get('/order-management/getBillDiscount', {
            params: { current_customer_id: order.value.customer_id }
        });

        processBillDiscounts(response.data);
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
};

onMounted(() => {
    fetchTaxes();
    fetchAutoAppliedDiscounts();
});

const splitBillsState = ref({
  currentBill: null,
  splitBills: []
});

const resetAppliedDiscounts = () => {
    form.discounts = [];
    fetchAutoAppliedDiscounts();
};

// Update the paySplitBill function
const paySplitBill = (bill) => {
    if (!isSplitBillMode.value) {
        resetAppliedDiscounts();
    }

    splitBillDetails.value = bill;
    selectedCustomer.value = splitBillDetails.value.customer;
    order.value = splitBillDetails.value;
    isSplitBillMode.value = true;
    closeModal('leave');
};

const openSuccessPaymentModal = () => {
    isSuccessPaymentShow.value = true;
};

const closeSuccessPaymentModal = () => {
    form.processing = true;
    isSuccessPaymentShow.value = false;
    setTimeout(() => showOrderReceipt.value = false, 3000);
    form.reset();

    if (splitBillDetails.value && splitBillDetails.value.id !== 'current') {
        paymentTransactions.value = [];
        clearInput();
        closeOrderDetails();

        // Handle split bill payment
        splitBillsState.value.splitBills = splitBillsState.value.splitBills.filter(
            bill => bill.id !== splitBillDetails.value.id
        );

        order.value.amount = 0.00;

        isSplitBillModalOpen.value = true;
        isDirty.value = false;

    } else {
        // For normal bills, close everything
        setTimeout(() => {
            emit('close', 'leave');
            emit('closeDrawer');
        }, 500)
    }

    form.processing = false;
};

const kickDrawer = (printer, base64) => {
    try {
        if (printer && !!printer.kick_cash_drawer == false) return;

        if (!printer || !printer.ip_address || !printer.port_number) {
            let summary = '';
            let detail = '';

            if (!printer) {
                summary = 'Unable to detect selected printer';
                detail = 'Please contact admin to setup the printer.';

            } else if (!printer.ip_address) {
                summary = 'Invalid printer IP address';
                detail = 'Please contact admin to setup the printer IP address.';

            } else if (!printer.port_number) {
                summary = 'Invalid printer port number';
                detail = 'Please contact admin to setup the printer port number.';
            }

            showMessage({
                severity: 'error',
                summary: summary,
                detail: detail,
            });

            return;
        }

        const url = `stockie-app://print?base64=${base64}&ip=${printer.ip_address}&port=${printer.port_number}`;

        try {
            window.location.href = url;

            showMessage({
                severity: 'success',
                summary: 'Cash drawer kicked!',
            });

        } catch (e) {
            console.error('Failed to open app:', e);
            alert(`Failed to open STOXPOS app \n ${e}`);

        }
        
    } catch (err) {
        console.error("Kick drawer failed:", err);
        showMessage({
            severity: 'error',
            summary: 'Kick drawer failed',
            detail: err.message
        });
    }
};

const submit = async () => {
    form.processing = true;
    form.payment_methods = paymentTransactions.value.filter((transaction => transaction.amount >= 0));
    form.change = change.value;

    let cashMethod = form.payment_methods.find(pm => pm.method === 'Cash');
    if (cashMethod && form.change > 0) {
        cashMethod.amount = cashMethod.amount - form.change;
    };

    if (splitBillDetails.value && isSplitBillMode.value) {
        form.split_bill_id = splitBillDetails.value.id;
        form.split_bill = splitBillDetails.value;
    }

    try {
        const response = await axios.post(`/order-management/orders/updateOrderPayment`, form);

        if (splitBillDetails.value && response.data.updatedCurrentBill) {
            // Update the local state with the backend's response
            splitBillsState.value.order_items = response.data.updatedCurrentBill.order_items;
            splitBillsState.value.amount = response.data.updatedCurrentBill.amount;
            exactBillAmount();
        }
        
        if ((splitBillDetails.value?.id === 'current' && splitBillDetails.value?.order_id === props.currentOrder.id) || !splitBillDetails.value) {
            let customerPointBalance = response.data.newPointBalance;
            // let customerRanking = response.data.newRanking;
    
            if (customerPointBalance !== undefined) emit('update:customer-point', customerPointBalance);
            // if (customerRanking !== undefined) emit('update:customer-rank', customerRanking);
        }
        
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: wTrans('public.toast.payment_completed'),
            });
        }, 200);

        kickDrawer(response.data.printer, response.data.data);

        emit('fetchZones');
        emit('fetchPendingServe');
        
        openSuccessPaymentModal();
        
    } catch (error) {
        console.error(error);
        setTimeout(() => {
            showMessage({ 
                severity: 'error',
                summary: wTrans('public.toast.payment_unsuccessful'),
            });
        }, 200);
    } finally {
        form.processing = false;
    }
};

const showCustomerModal = () => {
    isCustomerModalOpen.value = true;
    isDirty.value = false;
};

const showSplitBillModal = () => {
    if (!isSplitBillMode.value) {
        isSplitBillModalOpen.value = true;
        isDirty.value = false;
    }
};

const showMergeBillModal = () => {
    isMergeBillModalOpen.value = true;
    isDirty.value = false;
};

const showAddDiscountModal = () => {
    isAddDiscountModalOpen.value = true;
    isDirty.value = false;
};

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCustomerModalOpen.value = false;
                isMergeBillModalOpen.value = false;
                isSplitBillModalOpen.value = false;
                isAddDiscountModalOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isCustomerModalOpen.value = false;
            isMergeBillModalOpen.value = false;
            isSplitBillModalOpen.value = false;
            isAddDiscountModalOpen.value = false;
            break;
        }
    }
};

const closeOrderDetails = () => {
    setTimeout(() => emit('fetchZones'), 200);
    setTimeout(() => emit('fetchOrderDetails'), 300);
};

// const formDiscountsCount = computed(() => {
//     let count = 0;

//     if (form.discounts.voucher) count++;

//     count += form.discounts.bill.length;

//     return count;
// });

const sstAmount = computed(() => {
    const sstTax = Object.keys(taxes.value).length > 0 ? taxes.value['SST'] : 0;
    const result = (Number(order.value.amount ?? 0) * (sstTax / 100)) ?? 0;

    return result.toFixed(2);
});

const serviceTaxAmount = computed(() => {
    const serviceTax = Object.keys(taxes.value).length > 0 ? taxes.value['Service Tax'] : 0;
    const result = (Number(order.value.amount ?? 0) * (serviceTax / 100)) ?? 0;

    return result.toFixed(2);
});

const voucherDiscountedAmount = computed(() => {
    const selectedVoucher = form.discounts.find((discount) => discount.type === 'voucher');
    if (!selectedVoucher) return 0.00;

    const discount = selectedVoucher.discount;
    const discountedAmount = selectedVoucher.reward_type === 'Discount (Percentage)'
            ? order.value.amount * (discount / 100)
            : discount;

    return Number(discountedAmount).toFixed(2);

});

const billDiscountedAmount = computed(() => {
    const billDiscounts = form.discounts.filter((discount) => ['bill', 'manual'].includes(discount.type));
    if (billDiscounts.length === 0) return 0.00;

    const totalBillDiscountAmount = billDiscounts.reduce((total, discount) => {
        const discountRate = discount.type === 'bill' ? discount.discount_rate : discount.rate;
        const discountedAmount = discount.type === 'bill'
                ? discount.discount_type === 'percentage'
                        ? order.value.amount * (discountRate / 100)
                        : discountRate
                : order.value.amount * (discountRate / 100);

        return total + discountedAmount;
    }, 0);


    return Number(totalBillDiscountAmount).toFixed(2);

});

// Rounds off the amount based on the Malaysia Bank Negara rounding mechanism.
const priceRounding = (amount) => {
    // Get the decimal part in cents
    let cents = Math.round((amount - Math.floor(amount)) * 100);

    // Determine rounding based on the last digit of cents
    let lastDigit = cents % 10;

    if ([1, 2, 6, 7].includes(lastDigit)) {
        // Round down to the nearest multiple of 5
        cents = (cents - lastDigit) + (lastDigit < 5 ? 0 : 5);
    } else if ([3, 4, 8, 9].includes(lastDigit)) {
        // Round up to the nearest multiple of 5
        cents = (cents + 5) - (lastDigit % 5);
    }

    // Calculate the final rounded amount
    let roundedAmount = Math.floor(amount) + cents / 100;

    return roundedAmount;
};

const grandTotalAmount = computed(() => {
    const totalTaxableAmount = (Number(sstAmount.value) + Number(serviceTaxAmount.value)) ?? 0;
    const voucherDiscountAmount = voucherDiscountedAmount.value > 0 ? voucherDiscountedAmount.value : 0.00;
    const billDiscountAmount = billDiscountedAmount.value > 0 ? billDiscountedAmount.value : 0.00;
    const grandTotal = priceRounding(Number(order.value.amount) + totalTaxableAmount - voucherDiscountAmount - billDiscountAmount);

    return grandTotal >= 0 ? grandTotal.toFixed(2) : '0.00';
});

const roundingAmount = computed(() => {
    const totalTaxableAmount = (Number(sstAmount.value) + Number(serviceTaxAmount.value)) ?? 0;
    const voucherDiscountAmount = order.value.voucher ? voucherDiscountedAmount.value : 0.00;
    const totalAmount = Number(order.value.amount) + totalTaxableAmount - voucherDiscountAmount;
    const rounding = priceRounding(totalAmount) - totalAmount;

    return rounding.toFixed(2);
});

const totalAmountPaid = computed(() => {
    return paymentTransactions.value.reduce((total, transaction) => total + transaction.amount, 0);
});

// Function to handle number pad input
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

// Function to handle payment method clicks
const handlePaymentMethod = (method) => {
    // Get all bill-type discounts with payment method requirements
    const discountsWithMethodsReq = form.discounts.filter(
        d => d.type === 'bill' && d.payment_method?.length > 0
    );

    // If no payment method requirements, proceed normally
    if (discountsWithMethodsReq.length === 0) {
        proceedWithPayment(method);
        return;
    }

    // Map all required methods to consistent format
    const mapMethodFormat = (method) => {
        switch (method.toLowerCase()) {
            case 'cash': return 'Cash';
            case 'card': return 'Card';
            case 'e-wallets': return 'E-Wallet';
        }
    };

    // Get all required methods in consistent format
    const allRequiredMethods = discountsWithMethodsReq.flatMap(d => 
        d.payment_method.map(mapMethodFormat)
    );

    // Check for conflicts (different required methods between discounts)
    const hasConflictingMethods = !discountsWithMethodsReq.every(
        d => {
            const currentMethods = d.payment_method.map(mapMethodFormat).sort();
            const firstMethods = discountsWithMethodsReq[0].payment_method.map(mapMethodFormat).sort();
            return JSON.stringify(currentMethods) === JSON.stringify(firstMethods);
        }
    );

    if (hasConflictingMethods) {
        showMessage({ 
            severity: 'warn',
            summary: wTrans('public.toast.conflict_methods_summary'),
            detail: wTrans('public.toast.conflict_methods_detail'),
        });
        return;
    }

    // Check if selected method is in the required methods (using mapped format)
    const requiredMethods = discountsWithMethodsReq[0].payment_method.map(mapMethodFormat);
    
    if (!requiredMethods.includes(method)) {
        const readableMethods = requiredMethods.map((method) => getTranslatedMethod(method, true)).join(', ');
        showMessage({ 
            severity: 'warn',
            summary: wTrans('public.toast.required_methods_summary'),
            detail: wTrans('public.toast.required_methods_detail', { methods: readableMethods }),
        });
        return;
    }

    // If we get here, method is valid
    proceedWithPayment(method);
};

const proceedWithPayment = (method) => {
    const amount = Number(billAmountKeyed.value);

    if (amount >= 0) {
        // Check if the payment method already exists
        const existingTransaction = paymentTransactions.value.find(
            (transaction) => transaction.method === method
        );

        if (existingTransaction) {
            if (selectedMethod.value === existingTransaction.method) {
                let updatedPaidAmount;

                if (hasCashMethod.value || method === 'Cash') {
                    updatedPaidAmount = amount;
                } else {
                    const otherMethodsTotalPaid = paymentTransactions.value.reduce((total, transaction) => {
                        return transaction.method !== method 
                            ? total + transaction.amount
                            : total + 0;
                    }, 0);

                    if (amount + otherMethodsTotalPaid <= grandTotalAmount.value) {
                        updatedPaidAmount = amount;
                        
                    } else {
                        updatedPaidAmount = Number((amount - ((amount + otherMethodsTotalPaid) - grandTotalAmount.value)).toFixed(2));
                        setTimeout(() => {
                            showMessage({ 
                                severity: 'warn',
                                summary: wTrans('public.toast.total_amount_exceeded'),
                            });
                        }, 200);
                    }
                }

                existingTransaction.amount = updatedPaidAmount;
                billAmountKeyed.value = remainingBalanceDue.value;
                selectedMethod.value = '';

            } else {
                let updatedPaidAmount;

                if (hasCashMethod.value || method === 'Cash') {
                    updatedPaidAmount = amount;

                } else {
                    if (amount + totalAmountPaid.value <= grandTotalAmount.value) {
                        updatedPaidAmount = amount;
                        
                    } else {
                        updatedPaidAmount = Number((amount - ((amount + totalAmountPaid.value) - grandTotalAmount.value)).toFixed(2));
                        setTimeout(() => {
                            showMessage({ 
                                severity: 'warn',
                                summary: wTrans('public.toast.total_amount_exceeded'),
                            });
                        }, 200);
                    }
                }

                // Update the amount for the existing payment method
                existingTransaction.amount = Number((existingTransaction.amount + updatedPaidAmount).toFixed(2));
                billAmountKeyed.value = remainingBalanceDue.value >= 0 ? remainingBalanceDue.value : '0.00';
            }
            
        } else {
            let paidAmount;

            if (hasCashMethod.value || method === 'Cash') {
                paidAmount = amount;
            
            } else {
                if (amount + totalAmountPaid.value <= grandTotalAmount.value) {
                    paidAmount = amount;

                } else {
                    paidAmount = Number((amount - ((amount + totalAmountPaid.value) - grandTotalAmount.value)).toFixed(2));
                    setTimeout(() => {
                        showMessage({ 
                            severity: 'warn',
                            summary: wTrans('public.toast.total_amount_exceeded'),
                        });
                    }, 200);
                }
            }

            if (paidAmount >= 0) {
                // Add a new payment transaction
                paymentTransactions.value.push({
                    method,
                    amount: paidAmount,
                });
            }

            const balance = (Number(grandTotalAmount.value) - totalAmountPaid.value).toFixed(2);
            // Deduct the amount from the balance due
            billAmountKeyed.value = balance >= 0 ? balance : '0.00';
        }
    }
};

// Computed property to calculate the remaining balance due
const remainingBalanceDue = computed(() => {
    return (Number(grandTotalAmount.value) - totalAmountPaid.value).toFixed(2);
});

const exactBillAmount = () => {    
    let selectedPaymentTransaction = paymentTransactions.value.find((transaction) => transaction.method === selectedMethod.value);
    let remainingAmount = remainingBalanceDue.value >= 0 ? remainingBalanceDue.value : '0.00';

    billAmountKeyed.value = selectedMethod.value
            ? (selectedPaymentTransaction.amount + Number(remainingAmount)).toFixed(2)
            : remainingAmount;
};

const selectMethod = (transaction) => {
    let selectedPaymentTransaction = paymentTransactions.value.find((pay) => pay.method === transaction.method);

    if (selectedMethod.value === transaction.method) {
        let updatedPaidAmount;
        let formattedKeyedAmount = Number(billAmountKeyed.value);

        if (hasCashMethod.value || transaction.method === 'Cash') {
            updatedPaidAmount = formattedKeyedAmount;
        } else {
            const otherMethodsTotalPaid = paymentTransactions.value.reduce((total, paidTransaction) => {
                return paidTransaction.method !== transaction.method 
                    ? total + paidTransaction.amount
                    : total + 0;
            }, 0);

            if (formattedKeyedAmount + otherMethodsTotalPaid <= grandTotalAmount.value) {
                updatedPaidAmount = formattedKeyedAmount;
                
            } else {
                updatedPaidAmount = Number((formattedKeyedAmount - ((formattedKeyedAmount + otherMethodsTotalPaid) - grandTotalAmount.value)).toFixed(2));
                setTimeout(() => {
                    showMessage({ 
                        severity: 'warn',
                        summary: wTrans('public.toast.total_amount_exceeded'),
                    });
                }, 200);
            }
        }
        selectedPaymentTransaction.amount = updatedPaidAmount;
        billAmountKeyed.value = remainingBalanceDue.value;
        selectedMethod.value = '';

    } else {
        billAmountKeyed.value = transaction.amount.toFixed(2);
        selectedMethod.value = transaction.method;
    }
};

const updateOrderCustomer = (customer) => {
    selectedCustomer.value = customer;

    if (isSplitBillMode.value) {
        order.value.customer === customer;
        splitBillDetails.value.customer === customer;
        
        // Remove the paid bill from the state
        if (splitBillDetails.value.id === 'current') {
            // Handle current bill payment
            splitBillsState.value.currentBill.customer = customer;

        } else {
            // Handle split bill payment
            let splitBill = splitBillsState.value.splitBills.find(
                bill => bill.id === splitBillDetails.value.id
            );

            splitBill.customer = customer;
        }

    } else {
        emit('update:order-customer', customer);
    }
};

// Check if there's a cash payment
const hasCashMethod = computed(() => {
    return paymentTransactions.value.some(transaction => transaction.method === 'Cash');
});

const isValidated = computed(() => {
    const grandTotal = Number(grandTotalAmount.value);
    const cardMethod = paymentTransactions.value.find(transaction => transaction.method === 'Card');
    const eWalletMethod = paymentTransactions.value.find(transaction => transaction.method === 'E-Wallet');
    
    const withCashCondition = ((cardMethod?.amount ?? 0.00) + (eWalletMethod?.amount ?? 0.00)) <= grandTotal;
    const withoutCashCondition = totalAmountPaid.value == grandTotal;

    return isSplitBillMode.value
            ? !form.processing &&
                remainingBalanceDue.value <= 0 &&
                (!hasCashMethod.value ? withoutCashCondition : withCashCondition) &&
                paymentTransactions.value.length > 0 &&
                (splitBillsState.value.splitBills.find(bill => bill.id === splitBillDetails.value.id) 
                    || splitBillDetails.value.id === splitBillsState.value.currentBill?.id)
            : !form.processing &&
                remainingBalanceDue.value <= 0 &&
                (!hasCashMethod.value ? withoutCashCondition : withCashCondition) &&
                paymentTransactions.value.length > 0;
});

const displayExceedBalanceToast = () => {
    const grandTotal = Number(grandTotalAmount.value);
    const cardMethod = paymentTransactions.value.find(transaction => transaction.method === 'Card');
    const eWalletMethod = paymentTransactions.value.find(transaction => transaction.method === 'E-Wallet');
    
    const withCashCondition = hasCashMethod.value && ((cardMethod?.amount ?? 0.00) + (eWalletMethod?.amount ?? 0.00)) > grandTotal;
    const withoutCashCondition = !hasCashMethod.value && totalAmountPaid.value > grandTotal;

    if (withoutCashCondition || withCashCondition) {
        setTimeout(() => {
            showMessage({ 
                severity: 'warn',
                summary: wTrans('public.toast.total_amount_exceeded'),
            });
        }, 200);
    }
};

const updateOrder = (updatedOrder) => {
    order.value = updatedOrder;
    form.order_id = order.value.id;
    form.tables = order.value.order_table.filter(table => ['Pending Order', 'Order Placed', 'All Order Served'].includes(table.status));

    emit('update:order', updatedOrder);
};

const sortedTransactionMethods = computed(() => {
    return paymentTransactions.value.toSorted((a, b) => {
        // Get the indices of the objects
        const indexA = paymentTransactions.value.indexOf(a);
        const indexB = paymentTransactions.value.indexOf(b);

        // Sort in descending order
        return indexB - indexA;
    });
});

const removeMethod = (transaction) => {
    const indexOfTransaction = paymentTransactions.value.indexOf(transaction);
    
    if (paymentTransactions.value[indexOfTransaction].method === selectedMethod.value) {
        selectedMethod.value = '';
    }

    paymentTransactions.value.splice(indexOfTransaction, 1);
    
    exactBillAmount();
};

const tableNames = computed(() => {
    return order.value.order_table
            ?.map((orderTable) => orderTable.table.table_no)
            .sort((a, b) => a.localeCompare(b))
            .join(', ') ?? '';
});

const isMethodDisabled = (method) => {
    const paymentMethodRequirements = form.discounts
            .filter((d) => d.type === 'bill')
            .map((d) => d.payment_method);

}

const getTranslatedMethod = (method, value = false) => {
    switch (method) {
        case 'Cash': return value ? wTrans('public.cash').value : wTrans('public.cash')
        case 'Card': return value ? wTrans('public.card').value : wTrans('public.card')
        case 'E-Wallet': return value ? wTrans('public.e_wallet').value : wTrans('public.e_wallet')
    }
};

// watch(voucherDiscountedAmount, (newValue) => {
//     billAmountKeyed.value = newValue;
// });

watch(grandTotalAmount, (newValue) => {
    billAmountKeyed.value = newValue > 0 ? newValue : '0.00';
});

watch(remainingBalanceDue, (newValue) => {
    change.value = newValue < 0 ? Math.abs(newValue) : '0.00';
});
watch(() => form.discounts, (newValue) => {
    paymentTransactions.value = [];
    selectedMethod.value = '';
    exactBillAmount();

}, { deep: true });

watch(() => props.currentOrder, (newValue) => {
    let currentOrderVoucher = newValue.voucher ?? null;

    if (currentOrderVoucher) {
        currentOrderVoucher['type'] = 'voucher'
        form.discounts.push(currentOrderVoucher);
    };
}, { immediate: true });

watch(() => order.value.customer_id, (newValue, oldValue) => {
    // Only proceed if the customer_id actually changed
    if (newValue !== oldValue) {
        if (!isSplitBillMode.value) {
            // Find index of voucher discount (if any)
            const voucherIndex = form.discounts.findIndex(d => d.type === 'voucher');
            
            // If voucher exists, remove it
            if (voucherIndex !== -1) {
                // Create a new array without the voucher for better reactivity
                form.discounts = form.discounts.filter((_, index) => index !== voucherIndex);
            }
        }

        // Recheck the applied bill disocunts if the customer_id actually changed
        fetchAutoAppliedDiscounts();
    }
});

// watch(isSplitBillMode, (newValue) => {
//     if (newValue) {
//         form.discounts = form.discounts.filter((_, index) => index !== voucherIndex);
//         order.value = splitBillDetails.value;
//     }
// });

const printInvoiceReceipt = () => {
    form.processing = true;
    showOrderReceipt.value = true;
    setTimeout(() => orderInvoice.value.testPrintReceipt(), 200);
    closeSuccessPaymentModal();
    form.processing = false;
}

const printPreviewReceipt = async () => {
    form.processing = true;

    try {
        const params = { 
            order: order.value,
            taxes: taxes.value,
            applied_discounts: form.discounts,
            table_names: tableNames.value,
        };

        const response = await axios.post('/order-management/orders/getPreviewReceipt', params);
        const printer = response.data.printer;

        if (!printer || !printer.ip_address || !printer.port_number) {
            let summary = '';
            let detail = '';

            if (!printer) {
                summary = 'Unable to detect selected printer';
                detail = 'Please contact admin to setup the printer.';

            } else if (!printer.ip_address) {
                summary = 'Invalid printer IP address';
                detail = 'Please contact admin to setup the printer IP address.';

            } else if (!printer.port_number) {
                summary = 'Invalid printer port number';
                detail = 'Please contact admin to setup the printer port number.';
            }

            showMessage({
                severity: 'error',
                summary: summary,
                detail: detail,
            });

            return;
        }

        const base64 = response.data.data;
        const url = `stockie-app://print?base64=${base64}&ip=${printer.ip_address}&port=${printer.port_number}`;

        try {
            window.location.href = url;

        } catch (e) {
            console.error('Failed to open app:', e);
            alert(`Failed to open STOXPOS app \n ${e}`);

        }

        // window.location.href = `rawbt:base64,${base64}`;

        
    } catch (err) {
        console.error("Print failed:", err);
        showMessage({
            severity: 'error',
            summary: 'Print failed',
            detail: err.message
        });
    } finally {
        form.processing = false;
    }
}

</script>

<template>
    <form @submit.prevent="submit" class="flex flex-col gap-y-6 justify-between h-full">
        <div class="flex flex-col items-start gap-y-6 h-full">
            <!-- Actions -->
            <div class="flex w-full items-start gap-4 self-stretch">
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border cursor-pointer"
                    :class="selectedCustomer ? 'border-green-200 bg-green-50 text-green-900' : 'border-grey-100 bg-grey-50 text-grey-950'"
                    @click="showCustomerModal"
                >
                    <CustomerIcon2 />
                    <p class="text-base font-medium">{{ selectedCustomer?.full_name ?? $t('public.customer_header') }}</p>
                </div>
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] cursor-pointer border"
                    :class="form.discounts.length > 0 ? 'border-green-200 bg-green-50 text-green-900' : 'border-grey-100 bg-grey-50 text-grey-950'"
                    @click="showAddDiscountModal"
                >
                    <DiscountIcon />
                    <p class="text-base font-medium">{{ form.discounts.length > 0 ? $t('public.order.discount_count', { count: form.discounts.length }) : $t('public.add_discount')}}</p>
                </div>
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border"
                    :class="isSplitBillMode ? 'text-grey-300 bg-grey-25 border-grey-50 cursor-not-allowed' : 'text-grey-950 border-grey-100 bg-grey-50 cursor-pointer'"
                    @click="showSplitBillModal"
                >
                    <SplitBillIcon />
                    <p class="text-base font-medium">{{ $tChoice('public.order.split_bill', 1) }}</p>
                </div>
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border"
                    :class="isSplitBillMode 
                            ? 'text-grey-300 bg-grey-25 border-grey-50 cursor-not-allowed' 
                            : props.currentOrder.order_table.filter(table => ['Pending Order', 'Order Placed', 'All Order Served'].includes(table.status)).length > 1
                                ? 'border-green-200 bg-green-50 text-green-900'
                                : 'text-grey-950 border-grey-100 bg-grey-50 cursor-pointer'"
                    @click="showMergeBillModal"
                >
                    <template v-if="props.currentOrder.order_table.filter(table => ['Pending Order', 'Order Placed', 'All Order Served'].includes(table.status)).length > 1">
                        <CheckIcon class="text-green-500" />
                        <p class="text-base font-medium">{{ tableNames }}</p>
                    </template>
                    <template v-else>
                        <MergedIcon />
                        <p class="text-base font-medium">{{ $tChoice('public.order.merge_bill', 1) }}</p>
                    </template>
                </div>
            </div>

            <!-- Main -->
            <div class="flex items-start size-full gap-4 self-stretch">
                <!-- Bill Overview -->
                <div class="flex w-1/3 flex-col items-start gap-y-8 self-stretch">
                    <div class="flex flex-col items-start gap-y-8 self-stretch">
                        <div class="flex flex-col py-2 px-3 gap-y-1 items-center self-stretch">
                            <p class="text-grey-900 text-md font-normal">{{ $t('public.order.balance_due') }}</p>
                            <p class="text-grey-900 text-[40px] font-bold">RM {{ remainingBalanceDue >= 0 ? remainingBalanceDue : '0.00' }}</p>
                        </div>

                        <div class="flex flex-col gap-y-1 items-start self-stretch">
                            <div class="flex flex-row justify-between items-start self-stretch">
                                <p class="text-grey-900 text-base font-normal">{{ $t('public.sub_total') }}</p>
                                <p class="text-grey-900 text-base font-bold">RM {{ Number(order.amount ?? 0).toFixed(2) }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch" v-if="voucherDiscountedAmount > 0">
                                <p class="text-grey-900 text-base font-normal">{{ $t('public.voucher_discount') }} {{ form.discounts.find((discount) => discount.type === 'voucher').reward_type === 'Discount (Percentage)' ? `(${form.discounts.find((discount) => discount.type === 'voucher').discount}%)` : `` }}</p>
                                <p class="text-grey-900 text-base font-bold">- RM {{ voucherDiscountedAmount }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch" v-if="billDiscountedAmount > 0">
                                <p class="text-grey-900 text-base font-normal">{{ $t('public.bill_discount') }}</p>
                                <p class="text-grey-900 text-base font-bold">- RM {{ billDiscountedAmount }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch" v-if="taxes['SST'] && taxes['SST'] > 0">
                                <p class="text-grey-900 text-base font-normal">SST ({{ Math.round(taxes['SST']) }}%)</p>
                                <p class="text-grey-900 text-base font-bold">RM {{ sstAmount }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch" v-if="taxes['Service Tax']  && taxes['Service Tax'] > 0">
                                <p class="text-grey-900 text-base font-normal">Service Tax ({{ Math.round(taxes['Service Tax']) }}%)</p>
                                <p class="text-grey-900 text-base font-bold">RM {{ serviceTaxAmount }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch">
                                <p class="text-grey-900 text-base font-normal">{{ $t('public.rounding') }}</p>
                                <p class="text-grey-900 text-base font-bold">{{ Math.sign(roundingAmount) === -1 ? '-' : '' }} RM {{ Math.abs(roundingAmount).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Looped entered payment method and amount -->
                    <div class="flex flex-col items-start gap-4 self-stretch max-h-[calc(100dvh-36rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                        <template v-for="(transaction, index) in sortedTransactionMethods" :key="index">
                            <div 
                                class="flex p-4 flex-col self-stretch gap-2 items-start rounded-[5px] border border-grey-100 shadow-sm cursor-pointer"
                                :class="selectedMethod === transaction.method ? 'bg-primary-25' : 'bg-white'"
                                @click="selectMethod(transaction)"
                            >
                                <div class="flex justify-between items-center self-stretch">
                                    <div class="flex items-center gap-x-2">
                                        <CashIcon v-if="transaction.method === 'Cash'" />
                                        <CardIcon v-if="transaction.method === 'Card'" />
                                        <EWalletIcon v-if="transaction.method === 'E-Wallet'" />
                                        <p class="text-grey-500 text-sm font-normal">{{ getTranslatedMethod(transaction.method) }}</p>
                                    </div>
                                    <TimesIcon @click.stop="removeMethod(transaction)" class="text-primary-900 hover:text-primary-800 hover:cursor-pointer" />
                                </div>
                                <p class="text-grey-950 text-lg font-semibold self-stretch cursor-pointer">RM {{ transaction.amount.toFixed(2) }}</p>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Inputs -->
                <div class="flex flex-col h-full justify-between w-2/3 items-start gap-y-5">
                    <!-- Payment Inputs -->
                    <div class="flex flex-col items-end h-full gap-y-5 self-stretch">
                        <!-- Payment Amount -->
                        <div class="flex justify-center items-center gap-x-4 flex-shrink-0 self-stretch rounded-[5px] bg-grey-25">
                            <p class="text-grey-950 text-[64px] font-normal">{{ billAmountKeyed >= 0 ? billAmountKeyed : '0.00' }}</p>
                            <Button
                                type="button"
                                size="lg"
                                class="!w-fit"
                                @click="exactBillAmount"
                            >
                                {{ $t('public.order.exact') }}
                            </Button>
                        </div>

                        <!-- Number Pad -->
                        <div class="flex flex-col items-start h-full gap-3 self-stretch">
                            <!-- Row 1 -->
                            <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                                <div @click="handleNumberInput('1')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">1</p>
                                </div>
                                <div @click="handleNumberInput('2')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">2</p>
                                </div>
                                <div @click="handleNumberInput('3')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">3</p>
                                </div>
                                <div @click="addPredefinedAmount(10)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">$10</p>
                                </div>
                            </div>
                            
                            <!-- Row 2 -->
                            <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                                <div @click="handleNumberInput('4')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">4</p>
                                </div>
                                <div @click="handleNumberInput('5')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">5</p>
                                </div>
                                <div @click="handleNumberInput('6')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">6</p>
                                </div>
                                <div @click="addPredefinedAmount(20)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">$20</p>
                                </div>
                            </div>
                            
                            <!-- Row 3 -->
                            <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                                <div @click="handleNumberInput('7')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">7</p>
                                </div>
                                <div @click="handleNumberInput('8')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">8</p>
                                </div>
                                <div @click="handleNumberInput('9')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">9</p>
                                </div>
                                <div @click="addPredefinedAmount(30)" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">$30</p>
                                </div>
                            </div>
                            
                            <!-- Row 4 -->
                            <div class="flex w-full h-1/4 items-start gap-3 self-stretch">
                                <div @click="clearInput" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">C</p>
                                </div>
                                <div @click="handleNumberInput('0')" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">0</p>
                                </div>
                                <div @click="handleDecimal" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <p class="text-grey-950 font-medium text-lg">.</p>
                                </div>
                                <div @click="deleteLastCharacter" class="flex w-1/4 h-full flex-col justify-center items-center gap-2.5 rounded-[5px] border border-grey-100 bg-grey-25 cursor-pointer">
                                    <DeleteIcon2 class="text-grey-950" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Selections -->
                    <div class="flex w-full items-start gap-x-4 self-stretch">
                        <div
                            @click="handlePaymentMethod('Cash')"
                            :class="[
                                'relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border cursor-pointer',
                                {
                                    'border-primary-900 bg-primary-25': selectedMethod === 'Cash' && !isMethodDisabled('Cash'),
                                    'border-grey-100': selectedMethod !== 'Cash' && !isMethodDisabled('Cash'),
                                    'bg-grey-200 ': isMethodDisabled('Cash'),
                                }
                            ]"
                        >
                            <CashIcon />
                            <p class="text-grey-950 font-medium text-base">{{ $t('public.cash') }}</p>
                            <CheckCircleIcon v-if="selectedMethod === 'Cash'" class="absolute -top-[6px] -right-[6px] text-primary-900" />
                        </div>
                        <div
                            @click="handlePaymentMethod('Card')"
                            :class="[
                                'relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border cursor-pointer',
                                {
                                    'border-primary-900 bg-primary-25': selectedMethod === 'Card' && !isMethodDisabled('Card'),
                                    'border-grey-100': selectedMethod !== 'Card' && !isMethodDisabled('Card'),
                                    'bg-grey-200 ': isMethodDisabled('Card'),
                                }
                            ]"
                        >
                            <CardIcon />
                            <p class="text-grey-950 font-medium text-base">{{ $t('public.card') }}</p>
                            <CheckCircleIcon v-if="selectedMethod === 'Card'" class="absolute -top-[6px] -right-[6px] text-primary-900" />
                        </div>
                        <div
                            @click="handlePaymentMethod('E-Wallet')"
                            :class="[
                                'relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border cursor-pointer',
                                {
                                    'border-primary-900 bg-primary-25': selectedMethod === 'E-Wallet' && !isMethodDisabled('E-Wallet'),
                                    'border-grey-100': selectedMethod !== 'E-Wallet' && !isMethodDisabled('E-Wallet'),
                                    'bg-grey-200 ': isMethodDisabled('E-Wallet'),
                                }
                            ]"
                        >
                            <EWalletIcon />
                            <p class="text-grey-950 font-medium text-base">{{ $t('public.e_wallet') }}</p>
                            <CheckCircleIcon v-if="selectedMethod === 'E-Wallet'" class="absolute -top-[6px] -right-[6px] text-primary-900" />
                        </div>
                    </div>

                    <!-- Payment Rows 
                    <div class="flex flex-col w-full gap-y-2">
                        <div
                            v-for="(transaction, index) in paymentTransactions"
                            :key="index"
                            class="flex justify-between items-center self-stretch"
                        >
                            <p class="text-grey-900 text-base font-normal">{{ transaction.method }}</p>
                            <p class="text-grey-900 text-base font-bold">RM {{ transaction.amount.toFixed(2) }}</p>
                        </div>
                    </div>

                     Remaining Balance Due 
                    <div class="flex justify-between items-center self-stretch">
                        <p class="text-grey-900 text-base font-normal">Remaining Balance Due</p>
                        <p class="text-grey-900 text-base font-bold">RM {{ remainingBalanceDue.toFixed(2) }}</p>
                    </div>-->
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 items-center gap-3 self-stretch">
            <Button
                variant="tertiary"
                type="button"
                size="lg"
                class="col-span-1"
                @click="printPreviewReceipt"
            >
                    {{ $t('public.action.print') }}
            </Button>
            <div class="relative col-span-1 flex items-center justify-center rounded-b-[5px]">
                <Button
                    variant="primary"
                    size="lg"
                    :disabled="!isValidated"
                >
                    {{ $t('public.action.confirm') }}
                </Button>
                <div v-if="!isValidated" class="absolute inset-0 ">
                    <div class="h-full cursor-not-allowed" @click="displayExceedBalanceToast"></div>
                </div>
            </div>
        </div>
    </form>
   
    <Modal
        :title="$t('public.checked_in_customer')"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isCustomerModalOpen"
        @close="closeModal('close')"
    >
        <SelectCustomer
            :currentOrder="order"
            :origin="'pay-bill'"
            :isSplitBillMode="isSplitBillMode"
            @update:order-customer="updateOrderCustomer"
            @closeOrderDetails="closeOrderDetails"
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>
   
    <Modal
        :title="$tChoice('public.order.merge_bill', 0)"
        :maxWidth="'sm'"
        :closeable="true"
        :show="isMergeBillModalOpen"
        @close="closeModal('close')"
    >
        <MergeBill
            :currentOrder="order"
            :currentTable="currentTable"
            :currentHasVoucher="form.discounts.some((discount) => discount.type === 'voucher')"
            @update:order="updateOrder($event)"
            @closeOrderDetails="closeOrderDetails"
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
            @update:reset-applied-discounts="resetAppliedDiscounts"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>
   
    <Modal
        :title="$tChoice('public.order.split_bill', 0)"
        :maxWidth="'full'"
        :closeable="true"
        :show="isSplitBillModalOpen"
        @close="closeModal('close')"
    >
        <SplitBill
            :currentOrder="order"
            :currentTable="currentTable"
            :splitBillsState="splitBillsState"
            :currentSplitBillMode="isSplitBillMode"
            :currentHasVoucher="form.discounts.some((discount) => discount.type === 'voucher')"
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
            @payBill="paySplitBill"
            @updateState="(newState) => splitBillsState = newState"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>
   
    <Modal
        :title="$t('public.discount')"
        :maxWidth="'md'"
        :closeable="true"
        :show="isAddDiscountModalOpen"
        @close="closeModal('close')"
    >
        <ApplyDIscountForm
            :currentOrder="order"
            :currentTable="currentTable"
            :paymentTransactions="paymentTransactions"
            :billAppliedDiscounts="form.discounts"
            @update:discounts="form.discounts = $event"
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

    <Modal
        :show="isSuccessPaymentShow"
        :closeable="true"
        :maxWidth="'sm'"
        :withHeader="false"
        @close="closeSuccessPaymentModal"
    >
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <div class="flex flex-col items-center gap-y-2 self-stretch">
                <p class="self-stretch text-grey-900 text-base text-center font-medium">
                    {{ $t('public.order.paid_by', { method: form.payment_methods.map((transaction) => transaction.method === 'Card' ? $t('public.order.credit_debit_card') : getTranslatedMethod(transaction.method)).join(" & ") }) }}
                </p>
                <div class="flex items-center justify-center gap-x-3 self-stretch">
                    <div class="flex items-center gap-x-3 self-stretch">
                        <CashIcon v-if="hasCashMethod" />
                        <ToastSuccessIcon class="flex-shrink-0 size-8" v-else />
                        <p class="text-grey-950 text-xl font-normal" v-if="hasCashMethod" >{{ $t('public.order.change') }}: </p>
                    </div>
                    <p v-if="hasCashMethod" class="text-grey-950 text-xl font-semibold">{{ `RM ${form.change > 0 ? form.change.toFixed(2) : form.change}` }}</p>
                    <p v-else class="text-grey-950 text-xl font-semibold">{{ $t('public.order.payment_success') }}</p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-4 self-stretch">
                <div 
                    class="flex py-3 px-4 items-center justify-center self-stretch rounded-[5px] border border-grey-200 h-36"
                    :class="form.processing ? 'cursor-not-allowed pointer-events-none bg-grey-100' : 'cursor-pointer'"
                    @click="printInvoiceReceipt"
                >
                    <p class="text-grey-950 text-md font-medium">{{ $t('public.print_receipt') }}<</p>
                </div>
                <div 
                    class="flex py-3 px-4 items-center justify-center self-stretch rounded-[5px] h-36 border border-grey-200"
                    :class="form.processing ? 'cursor-not-allowed pointer-events-none bg-grey-100' : 'cursor-pointer'"
                    @click="closeSuccessPaymentModal"
                >
                    <p class="text-grey-950 text-md font-medium">{{ $t('public.order.no_receipt') }}</p>
                </div>
            </div>
        </div>
    </Modal>

    <div class="hidden">
        <template v-if="showOrderReceipt">
            <OrderInvoice ref="orderInvoice" :orderId="order.id" @close="" />
        </template>
    </div>
</template>