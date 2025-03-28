<script setup>
import axios from 'axios';
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { useCustomToast } from '@/Composables/index.js';
import { EmptyProfilePic } from '@/Components/Icons/solid';
import PayBillForm from './PayBillForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    selectedTable: Object,
    currentOrder: Object
})

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'update:customer-point', 'update:customer-rank', 'fetchPendingServe', 'fetchOrderDetails']);

const order = ref(props.currentOrder);
// const paymentDetails = ref({});
const taxes = ref([]);
const payBillFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    user_id: userId.value,
    order_id: props.currentOrder.id,
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

const openModal = () => {
    isDirty.value = false;
    payBillFormIsOpen.value = true;
}

const closeModal = (status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                payBillFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            payBillFormIsOpen.value = false;

            break;
        }
    }
}

const formSubmit = async () => { 
    // form.put(route('orders.updateOrderPayment', paymentDetails.value.id), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'success',
    //                 summary: 'Payment Completed.',
    //             });
    //         }, 200);
    //         form.reset();
    //         emit('close', true);
    //         emit('fetchZones');
    //     },
    //     onError: () => {
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'error',
    //                 summary: 'Payment Unsuccessful.',
    //             });
    //         }, 200);
    //     }
    // })

    // MOVE TO PAY BILL FORM CONFIRM ACTION BUTTON
    // try {
    //     const response = await axios.put(`/order-management/orders/updateOrderPayment/${paymentDetails.value.id}`, form);
    //     let customerPointBalance = response.data.newPointBalance;
    //     let customerRanking = response.data.newRanking;
        
    //     setTimeout(() => {
    //         showMessage({ 
    //             severity: 'success',
    //             summary: 'Payment Completed.',
    //         });
    //     }, 200);
    //     form.reset();
    //     emit('close', true);
    //     emit('fetchZones');
    //     emit('fetchPendingServe');
    //     if (customerPointBalance !== undefined) emit('update:customer-point', customerPointBalance);
    //     if (customerRanking !== undefined) emit('update:customer-rank', customerRanking);
    // } catch (error) {
    //     console.error(error);
    //     setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'error',
    //                 summary: 'Payment Unsuccessful.',
    //             });
    //         }, 200);
    // } finally {

    // }
};

const sstAmount = computed(() => {
    const sstTax = Object.keys(taxes.value).length > 0 ? taxes.value['SST'] : 0;
    const result = (parseFloat(order.value.amount ?? 0) * (sstTax / 100)) ?? 0;

    return result.toFixed(2);
});

const serviceTaxAmount = computed(() => {
    const serviceTax = Object.keys(taxes.value).length > 0 ? taxes.value['Service Tax'] : 0;
    const result = (parseFloat(order.value.amount ?? 0) * (serviceTax / 100)) ?? 0;

    return result.toFixed(2);
});

const voucherDiscountedAmount = computed(() => {
    if (!order.value.voucher) return 0.00;

    const discount = order.value.voucher.discount;
    const discountedAmount = order.value.voucher.reward_type === 'Discount (Percentage)'
            ? order.value.amount * discount
            : discount;

    return parseFloat(discountedAmount).toFixed(2);

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
    const rounding = priceRounding(totalAmount) - totalAmount;

    return rounding.toFixed(2);
});

const getItemTypeName = (type) => {
    switch (type) {
        case 'Keep': return 'Keep item';
        case 'Redemption': return 'Redeemed product'
        case 'Reward': return 'Entry reward'
    };
};
</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-6 items-start rounded-[5px]">
            <div class="flex flex-col items-start gap-10 px-6 py-3 w-full self-stretch max-h-[calc(100dvh-11.5rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                <div class="flex flex-col gap-y-4 items-start self-stretch">
                    <div class="flex flex-col py-2 gap-y-1 items-center self-stretch">
                        <p class="text-primary-900 text-md font-normal">Total Due</p>
                        <p class="text-primary-900 text-[40px] font-bold">RM {{ grandTotalAmount }}</p>
                    </div>

                    <div class="flex flex-col gap-x-2 items-start self-stretch">
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">Order No.</p>
                            <p class="text-grey-900 text-base font-bold">#{{ order.order_no }}</p>
                        </div>
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">No. of pax</p>
                            <p class="text-grey-900 text-base font-bold">{{ order.pax }}</p>
                        </div>
                        <div class="flex flex-row justify-between items-start self-stretch">
                            <p class="text-grey-900 text-base font-normal">Customer Name</p>
                            <div class="flex flex-row gap-x-2 items-center">
                                <img 
                                    :src="order.customer && order.customer.image ? order.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="CustomerProfilePic"
                                    class="size-6"
                                >
                                <p class="text-grey-900 text-base font-bold">{{ order.customer?.full_name ?? 'Guest' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full flex flex-col gap-y-3">
                    <p class="text-grey-950 text-md font-bold">Bill </p>
                    <div class="flex flex-col gap-y-3 items-start self-stretch">
                        <div class="flex flex-row items-center self-stretch gap-x-3">
                            <p class="w-8/12 text-grey-950 text-sm font-normal">Product Name & Quantity </p>
                            <p class="w-1/12 text-grey-950 text-sm font-normal">Qty</p>
                            <p class="w-3/12 text-grey-950 text-sm font-normal text-right">Price</p>
                        </div>

                        <div class="flex flex-col gap-y-6 items-start self-stretch">
                            <div class="flex flex-col gap-y-6 items-start self-stretch">
                                <div class="flex flex-col gap-y-4 items-start self-stretch">
                                    <template v-for="row in order.order_items.filter((item) => item.status !== 'Cancelled' && item.item_qty > 0)">
                                        <div class="flex flex-col gap-y-2 self-stretch">
                                            <div class="flex flex-row items-center self-stretch gap-x-3">
                                                <p class="w-8/12 text-grey-950 text-base font-medium self-stretch"> {{ row.type === 'Normal' ? '' : `(${getItemTypeName(row.type)})`  }} {{ row.product.product_name }}</p>
                                                <p class="w-1/12 text-grey-950 text-base font-medium text-center">{{ row.item_qty }}</p>
                                                <p class="w-3/12 text-grey-950 text-base font-medium text-right">RM {{ parseFloat(row.type === 'Normal' ? row.amount_before_discount : 0).toFixed(2) }}</p>
                                            </div>
                                            <div class="flex flex-row items-center self-stretch gap-x-3 pl-16" v-if="row.discount_id">
                                                <ul class="w-8/12 list-disc"><li class="text-grey-950 text-base font-medium self-stretch">{{ `${row.product_discount.discount.name} Discount` }}</li></ul>
                                                <p class="w-4/12 text-grey-950 text-base font-medium text-right">- RM {{ parseFloat(row.discount_amount).toFixed(2) }}</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex flex-col gap-y-1 items-start self-stretch">
                                <div class="flex flex-row justify-between items-start self-stretch">
                                    <p class="text-grey-900 text-base font-normal">Sub-total</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(order.amount ?? 0).toFixed(2) }}</p>
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
                    </div>
                </div>
            </div>

            <div class="fixed bottom-0 w-full flex px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <Button
                    type="button"
                    size="lg"
                    @click="openModal"
                >
                    Pay this Bill
                </Button>
            </div>
        </div>
    </form>

    <Modal
        :title="'Pay Bill'"
        :maxWidth="'xl'" 
        :closeable="true"
        :show="payBillFormIsOpen"
        @close="closeModal('close')"
    >
        <PayBillForm
            :currentOrder="order"
            :currentTable="selectedTable"
            @update:order="order = $event"
            @update:order-customer="order.customer = $event"
            @close="closeModal"
            @fetchZones="$emit('fetchZones')"
            @fetchOrderDetails="$emit('fetchOrderDetails')"
            @closeDrawer="$emit('close', true)"
            @update:customer-point="$emit('update:customer-point', $event)"
            @update:customer-rank="$emit('update:customer-rank', $event)"
            @fetchPendingServe="$emit('fetchPendingServe')"
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
</template>
    