<script setup>
import axios from 'axios';
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { useCustomToast } from '@/Composables/index.js';
import { EmptyProfilePic } from '@/Components/Icons/solid';

const props = defineProps({
    selectedTable: Object,
    order: Object
})

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'update:customer-point', 'update:customer-rank', 'fetchPendingServe']);

const order = ref(props.order);
const paymentDetails = ref({});
const taxes = ref([]);

const form = useForm({
    user_id: userId.value,
    order_id: props.order.id,
});

const fetchOrderDetails = async () => {
    try {
        const response = await axios.get(route('orders.getOrderPaymentDetails', props.order.id));
        paymentDetails.value = response.data.payment_details;
        taxes.value = response.data.taxes;
    } catch (error) {
        console.error(error);
    } finally {

    }
};

onMounted(() => fetchOrderDetails());

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

    try {
        const response = await axios.put(`/order-management/orders/updateOrderPayment/${paymentDetails.value.id}`, form);
        let customerPointBalance = response.data.newPointBalance;
        let customerRanking = response.data.newRanking;
        
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Payment Completed.',
            });
        }, 200);
        form.reset();
        emit('close', true);
        emit('fetchZones');
        emit('fetchPendingServe');
        if (customerPointBalance !== undefined) emit('update:customer-point', customerPointBalance);
        if (customerRanking !== undefined) emit('update:customer-rank', customerRanking);
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

// const sstAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (6 / 100));
// });

// const serviceChargeAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (10 / 100)) ?? 0.00;
// });

// const totalEarnedPoints = computed(() => {
//     return order.value.order_items.filter((item) => item.status === 'Served').reduce((total, item) => total + item.point_earned, 0) ?? 0.00;
// });

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
                        <p class="text-primary-900 text-[40px] font-bold">RM {{ parseFloat(paymentDetails.grand_total ?? 0).toFixed(2) }}</p>
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
                    <p class="text-grey-950 text-md font-bold">Bill #{{ paymentDetails.receipt_no }}</p>
                    <div class="flex flex-col gap-y-3 items-start self-stretch">
                        <div class="flex flex-row items-center self-stretch gap-x-3">
                            <p class="w-8/12 text-grey-950 text-sm font-normal">Product Name & Quantity </p>
                            <p class="w-1/12 text-grey-950 text-sm font-normal">Qty</p>
                            <p class="w-3/12 text-grey-950 text-sm font-normal text-right">Price</p>
                        </div>

                        <div class="flex flex-col gap-y-6 items-start self-stretch">
                            <div class="flex flex-col gap-y-6 items-start self-stretch">
                                <div class="flex flex-col gap-y-4 items-start self-stretch">
                                    <template v-for="row in order.order_items.filter((item) => item.status !== 'Cancelled')">
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
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.total_amount ?? 0).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch" v-if="order.voucher">
                                    <p class="text-grey-900 text-base font-normal">Voucher Discount {{ order.voucher.reward_type === 'Discount (Percentage)' ? `(${order.voucher.discount}%)` : `` }}</p>
                                    <p class="text-grey-900 text-base font-bold">- RM {{ parseFloat(paymentDetails.discount_amount ?? 0).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch" v-if="taxes['SST'] && taxes['SST'] > 0">
                                    <p class="text-grey-900 text-base font-normal">SST ({{ Math.round(taxes['SST']) }}%)</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.sst_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch" v-if="taxes['Service Tax']  && taxes['Service Tax'] > 0">
                                    <p class="text-grey-900 text-base font-normal">Service Tax ({{ Math.round(taxes['Service Tax']) }}%)</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.service_tax_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch">
                                    <p class="text-grey-900 text-base font-normal">Rounding</p>
                                    <p class="text-grey-900 text-base font-bold">{{ Math.sign(paymentDetails.rounding) === -1 ? '-' : '' }} RM {{ parseFloat(Math.abs(paymentDetails.rounding ?? 0)).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fixed bottom-0 w-full flex px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <Button
                    size="lg"
                    :disabled="form.processing || !paymentDetails.id"
                >
                    Pay this Bill
                </Button>
            </div>
        </div>
    </form>
</template>
    