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
import { CheckCircleIcon, CustomerIcon, CustomerIcon2, DeleteIcon2, DiscountIcon, MergedIcon, SplitBillIcon, TimesIcon, ToastSuccessIcon } from '@/Components/Icons/solid';
import { onMounted } from 'vue';
import { CardIcon, CashIcon, EWalletIcon } from '../../../Components/Icons/solid';
import SelectCustomer from './SelectCustomer.vue';
import MergeBill from './MergeBill.vue';

const props = defineProps({
    currentOrder: Object,
    currentTable: Object,
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
const isSuccessPaymentShow = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const paymentTransactions = ref([]); // Array to store payment transactions
const selectedItems = ref([]);
const searchQuery = ref('');

const form = useForm({
    user_id: userId.value,
    order_id: props.currentOrder.id,
    change: 0,
    payment_methods: []
});

const fetchTaxes = async () => {
    try {
        const response = await axios.get(route('orders.getAllTaxes'));
        taxes.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
};

onMounted(() => fetchTaxes());

const openSuccessPaymentModal = () => {
    isSuccessPaymentShow.value = true;
}

const closeSuccessPaymentModal = () => {
    isSuccessPaymentShow.value = false;
    setTimeout(() => {
        emit('close', 'leave');
        emit('closeDrawer');
    }, 200)
}

const submit = async () => {
    form.payment_methods = paymentTransactions.value;
    form.change = change.value;
    try {
        const response = await axios.post(`/order-management/orders/updateOrderPayment`, form);
        let customerPointBalance = response.data.newPointBalance;
        let customerRanking = response.data.newRanking;
        
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Payment Completed.',
            });
        }, 200);
        form.reset();
        emit('fetchZones');
        emit('fetchPendingServe');
        if (customerPointBalance !== undefined) emit('update:customer-point', customerPointBalance);
        if (customerRanking !== undefined) emit('update:customer-rank', customerRanking);

        openSuccessPaymentModal();

    } catch (error) {
        console.error(error);
        setTimeout(() => {
            showMessage({ 
                severity: 'error',
                summary: 'Payment Unsuccessful.',
            });
        }, 200);
    } finally {

    }
};

const showCustomerModal = () => {
    isCustomerModalOpen.value = true;
    isDirty.value = false;
}

const showMergeBillModal = () => {
    isMergeBillModalOpen.value = true;
    isDirty.value = false;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCustomerModalOpen.value = false;
                isMergeBillModalOpen.value = false;
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
            break;
        }
    }
}

const closeOrderDetails = () => {
    setTimeout(() => emit('fetchZones'), 200);
    setTimeout(() => emit('fetchOrderDetails'), 300);
};

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
    if (!order.value.voucher) return 0.00;

    const discount = order.value.voucher.discount;
    const discountedAmount = order.value.voucher.reward_type === 'Discount (Percentage)'
            ? order.value.amount * discount
            : discount;

    return Number(discountedAmount).toFixed(2);

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
    const voucherDiscountAmount = order.value.voucher ? voucherDiscountedAmount.value : 0.00;
    const grandTotal = priceRounding(Number(order.value.amount) + totalTaxableAmount - voucherDiscountAmount);

    return grandTotal.toFixed(2);
});

const roundingAmount = computed(() => {
    const totalTaxableAmount = (Number(sstAmount.value) + Number(serviceTaxAmount.value)) ?? 0;
    const voucherDiscountAmount = order.value.voucher ? voucherDiscountedAmount.value : 0.00;
    const totalAmount = Number(order.value.amount) + totalTaxableAmount - voucherDiscountAmount;
    const rounding = totalAmount - priceRounding(totalAmount);

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
    const amount = Number(billAmountKeyed.value);

    if (amount > 0) {
        // Check if the payment method already exists
        const existingTransaction = paymentTransactions.value.find(
            (transaction) => transaction.method === method
        );

        if (existingTransaction) {
            if (selectedMethod.value === existingTransaction.method) {
                let updatedPaidAmount;
                let formattedKeyedAmount = Number(billAmountKeyed.value);

                if (hasCashMethod.value || method === 'Cash') {
                    updatedPaidAmount = formattedKeyedAmount;
                } else {
                    const otherMethodsTotalPaid = paymentTransactions.value.reduce((total, transaction) => {
                        return transaction.method !== method 
                            ? total + transaction.amount
                            : total + 0;
                    }, 0);

                    console.log('others: ' + otherMethodsTotalPaid);

                    if (formattedKeyedAmount + otherMethodsTotalPaid <= grandTotalAmount.value) {
                        updatedPaidAmount = formattedKeyedAmount;
                        
                    } else {
                        updatedPaidAmount = formattedKeyedAmount - ((formattedKeyedAmount + otherMethodsTotalPaid) - grandTotalAmount.value);
                        console.log('over: ' + updatedPaidAmount);
                        setTimeout(() => {
                            showMessage({ 
                                severity: 'warn',
                                summary: 'Entered amount exceeded total payable amount.',
                            });
                        }, 200);
                    }
                }

                existingTransaction.amount = updatedPaidAmount;
                billAmountKeyed.value = remainingBalanceDue.value;
                selectedMethod.value = '';

            } else {
                let updatedPaidAmount;
                let formattedKeyedAmount = Number(billAmountKeyed.value);

                if (hasCashMethod.value || method === 'Cash') {
                    updatedPaidAmount = formattedKeyedAmount;

                } else {
                    if (formattedKeyedAmount + totalAmountPaid.value <= grandTotalAmount.value) {
                        updatedPaidAmount = formattedKeyedAmount;
                        
                    } else {
                        updatedPaidAmount = formattedKeyedAmount - ((formattedKeyedAmount + totalAmountPaid.value) - grandTotalAmount.value);
                        setTimeout(() => {
                            showMessage({ 
                                severity: 'warn',
                                summary: 'Entered amount exceeded total payable amount.',
                            });
                        }, 200);
                    }
                }

                // Update the amount for the existing payment method
                existingTransaction.amount += updatedPaidAmount;
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
                    paidAmount = amount - ((amount + totalAmountPaid.value) - grandTotalAmount.value);
                    setTimeout(() => {
                        showMessage({ 
                            severity: 'warn',
                            summary: 'Entered amount exceeded total payable amount.',
                        });
                    }, 200);
                }
            }

            if (paidAmount > 0) {
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
    billAmountKeyed.value = remainingBalanceDue.value >= 0 ? remainingBalanceDue.value : '0.00';
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
                updatedPaidAmount = formattedKeyedAmount - ((formattedKeyedAmount + otherMethodsTotalPaid) - grandTotalAmount.value);
                setTimeout(() => {
                    showMessage({ 
                        severity: 'warn',
                        summary: 'Entered amount exceeded total payable amount.',
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

const getActionContainerClass = (action) => {
    switch (action) {
        case 'customer': 

            return 'Keep item';

        case 'discount': 

            return 'Redeemed product';

        case 'split-bill': 
        
            return 'Entry reward';

        case 'merge-bill': 
        
            return 'Entry reward';
    };
};

const updateOrderCustomer = (customer) => {
    selectedCustomer.value = customer;
    emit('update:order-customer', customer);
};

// Check if there's a cash payment
const hasCashMethod = computed(() => {
    return paymentTransactions.value.some(transaction => transaction.method === 'Cash');
});

// Check if there's an card payment
const hasCardMethod = computed(() => {
    return paymentTransactions.value.some(transaction => transaction.method === 'Card');
});

// Check if there's an ewallet payment
const hasEwalletMethod = computed(() => {
    return paymentTransactions.value.some(transaction => transaction.method === 'E-Wallet');
});

const isValidated = computed(() => {
    return !form.processing && remainingBalanceDue.value <= 0;
});

const updateOrder = (updatedOrder) => {
    order.value = $event;
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
    
};

watch(grandTotalAmount, (newValue) => {
    billAmountKeyed.value = newValue;
})

watch(remainingBalanceDue, (newValue) => {
    change.value = newValue < 0 ? Math.abs(newValue) : '0.00';
})
</script>

<template>
    <form @submit.prevent="submit" class="flex flex-col gap-y-6 justify-between h-[calc(100dvh-9rem)]">
        <div class="flex flex-col items-start gap-y-6 h-full">
            <!-- Actions -->
            <div class="flex w-full items-start gap-4 self-stretch">
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border cursor-pointer"
                    :class="selectedCustomer ? 'border-green-200 bg-green-50' : 'border-grey-100 bg-grey-50'"
                    @click="showCustomerModal"
                >
                    <CustomerIcon2 :class="{ 'text-green-900': selectedCustomer }" />
                    <p class="text-grey-950 text-base font-medium">{{ selectedCustomer?.full_name ?? 'Customer' }}</p>
                </div>
                <div class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border cursor-pointer border-grey-100">
                    <DiscountIcon />
                    <p class="text-grey-950 text-base font-medium">Add Discount</p>
                </div>
                <div class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border cursor-pointer border-grey-100 bg-grey-50">
                    <SplitBillIcon />
                    <p class="text-grey-950 text-base font-medium">Split Bill</p>
                </div>
                <div 
                    class="flex w-1/4 items-center gap-x-3 p-4 rounded-[5px] border cursor-pointer border-grey-100 bg-grey-50"
                    @click="showMergeBillModal"
                >
                    <MergedIcon />
                    <p class="text-grey-950 text-base font-medium">Merge Bill</p>
                </div>
            </div>

            <!-- Main -->
            <div class="flex items-start size-full max-h-[calc(100dvh-19rem)] gap-4 self-stretch">
                <!-- Bill Overview -->
                <div class="flex w-1/3 flex-col items-start gap-y-8 self-stretch">
                    <div class="flex flex-col items-start gap-y-8 self-stretch">
                        <div class="flex flex-col py-2 px-3 gap-y-1 items-center self-stretch">
                            <p class="text-grey-900 text-md font-normal">Balance Due</p>
                            <p class="text-grey-900 text-[40px] font-bold">RM {{ remainingBalanceDue >= 0 ? remainingBalanceDue : '0.00' }}</p>
                        </div>

                        <div class="flex flex-col gap-y-1 items-start self-stretch">
                            <div class="flex flex-row justify-between items-start self-stretch">
                                <p class="text-grey-900 text-base font-normal">Sub-total</p>
                                <p class="text-grey-900 text-base font-bold">RM {{ Number(order.amount ?? 0).toFixed(2) }}</p>
                            </div>
                            <div class="flex flex-row justify-between items-start self-stretch" v-if="order.voucher">
                                <p class="text-grey-900 text-base font-normal">Voucher Discount {{ order.voucher.reward_type === 'Discount (Percentage)' ? `(${order.voucher.discount}%)` : `` }}</p>
                                <p class="text-grey-900 text-base font-bold">- RM {{ voucherDiscountedAmount }}</p>
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
                                <p class="text-grey-900 text-base font-normal">Rounding</p>
                                <p class="text-grey-900 text-base font-bold">{{ Math.sign(roundingAmount) === -1 ? '-' : '' }} RM {{ Math.abs(roundingAmount).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Looped entered payment method and amount -->
                    <div class="flex flex-col items-start gap-4 self-stretch max-h-[calc(100dvh-36.6rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
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
                                        <p class="text-grey-500 text-sm font-normal">{{ transaction.method }}</p>
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
                                Exact
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
                            class="relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border cursor-pointer"
                            :class="selectedMethod === 'Cash' ? 'border-primary-900 bg-primary-25' : 'border-grey-100'"
                        >
                            <CashIcon />
                            <p class="text-grey-950 font-medium text-base">Cash</p>
                            <CheckCircleIcon v-if="selectedMethod === 'Cash'" class="absolute -top-[6px] -right-[6px] text-primary-900" />
                        </div>
                        <div
                            @click="handlePaymentMethod('Card')"
                            class="relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border border-grey-100 cursor-pointer"
                            :class="selectedMethod === 'Card' ? 'border-primary-900 bg-primary-25' : 'border-grey-100'"
                        >
                            <CardIcon />
                            <p class="text-grey-950 font-medium text-base">Card</p>
                            <CheckCircleIcon v-if="selectedMethod === 'Card'" class="absolute -top-[6px] -right-[6px] text-primary-900" />
                        </div>
                        <div
                            @click="handlePaymentMethod('E-Wallet')"
                            class="relative flex w-1/3 py-4 px-3 gap-x-3 items-center rounded-[5px] border border-grey-100 cursor-pointer"
                            :class="selectedMethod === 'E-Wallet' ? 'border-primary-900 bg-primary-25' : 'border-grey-100'"
                        >
                            <EWalletIcon />
                            <p class="text-grey-950 font-medium text-base">E-Wallet</p>
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

        <div class="flex px-6 pt-3 items-center justify-end gap-4 self-stretch rounded-b-[5px] mx-[-20px]">
            <Button
                variant="primary"
                type="submit"
                size="lg"
                :disabled="!isValidated"
            >
                Confirm
            </Button>
        </div>
    </form>
   
    <Modal
        :title="'Checked-in customer'"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isCustomerModalOpen"
        @close="closeModal('close')"
    >
        <SelectCustomer
            :orderId="order.id"
            :origin="'pay-bill'"
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
        :title="'Merge bill'"
        :maxWidth="'sm'"
        :closeable="true"
        :show="isMergeBillModalOpen"
        @close="closeModal('close')"
    >
        <MergeBill
            :currentOrder="order"
            :currentTable="currentTable"
            @update:order="updateOrder($event)"
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
        :show="isSuccessPaymentShow"
        :closeable="true"
        :maxWidth="'sm'"
        :withHeader="false"
        @close="closeSuccessPaymentModal"
    >
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <div class="flex flex-col items-center gap-y-2 self-stretch">
                <p class="self-stretch text-grey-900 text-base text-center font-medium">
                    Paid by {{ paymentTransactions.map((transaction) => transaction.method === 'Card' ? 'Credit/Debit Card' : transaction.method).join(" & ") }}
                </p>
                <div class="flex items-center justify-center gap-x-3 self-stretch">
                    <div class="flex items-center gap-x-3 self-stretch">
                        <CashIcon v-if="hasCashMethod" />
                        <ToastSuccessIcon class="flex-shrink-0 size-8" v-else />
                        <p class="text-grey-950 text-xl font-normal" v-if="hasCashMethod" >Change: </p>
                    </div>
                    <p v-if="hasCashMethod" class="text-grey-950 text-xl font-semibold">{{ `RM ${change > 0 ? change.toFixed(2) : change}` }}</p>
                    <p v-else class="text-grey-950 text-xl font-semibold">{{ 'Payment Successful' }}</p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-4 self-stretch">
                <div @click="" class="flex py-3 px-4 items-center justify-center self-stretch rounded-[5px] bg-grey-50 h-36">
                    <p class="text-grey-950 text-md font-medium">Print receipt</p>
                </div>
                <div @click="closeSuccessPaymentModal" class="flex py-3 px-4 items-center justify-center self-stretch rounded-[5px] h-36 border border-grey-200">
                    <p class="text-grey-950 text-md font-medium">No receipt</p>
                </div>
            </div>
        </div>
    </Modal>
</template>