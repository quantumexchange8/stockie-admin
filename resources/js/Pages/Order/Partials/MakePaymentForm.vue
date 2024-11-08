<script setup>
import axios from 'axios';
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    selectedTable: Object,
    order: Object
})

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones']);

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

const formSubmit = () => { 
    form.put(route('orders.updateOrderPayment', paymentDetails.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Payment Completed.',
                });
            }, 200);
            form.reset();
            emit('close', true);
            emit('fetchZones');
        },
        onError: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'error',
                    summary: 'Payment Unsuccessful.',
                });
            }, 200);
        }
    })
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
            <div class="flex flex-col items-start gap-10 px-6 py-3 w-full self-stretch">
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
                                <div class="size-6 bg-primary-100 rounded-full" v-if="order.customer"></div>
                                <p class="text-grey-900 text-base font-bold">{{ order.customer?.full_name ?? 'Guest' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full flex flex-col gap-y-3 max-h-[calc(100dvh-20.5rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <p class="text-grey-950 text-md font-bold">Bill #{{ paymentDetails.receipt_no }}</p>
                    <div class="flex flex-col gap-y-3 items-start self-stretch">
                        <div class="flex flex-row items-center self-stretch gap-x-3">
                            <p class="w-8/12 text-grey-950 text-sm font-normal">Product Name & Quantity </p>
                            <p class="w-1/12 text-grey-950 text-sm font-normal">Qty</p>
                            <p class="w-3/12 text-grey-950 text-sm font-normal text-right">Price</p>
                        </div>

                        <div class="flex flex-col gap-y-6 items-start self-stretch">
                            <div class="flex flex-col gap-y-2 items-start self-stretch">
                                <div class="flex flex-row items-center self-stretch gap-x-3" v-for="row in order.order_items.filter((item) => item.status === 'Served')">
                                    <p class="w-8/12 text-grey-950 text-base font-medium truncate self-stretch"> {{ row.type === 'Normal' ? '' : `(${getItemTypeName(row.type)})`  }} {{ row.product.product_name }}</p>
                                    <p class="w-1/12 text-grey-950 text-base font-medium text-center">{{ row.item_qty }}</p>
                                    <p class="w-3/12 text-grey-950 text-base font-medium text-right">RM {{ parseFloat(row.amount ?? 0).toFixed(2) }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-y-1 items-start self-stretch">
                                <div class="flex flex-row justify-between items-start self-stretch">
                                    <p class="text-grey-900 text-base font-normal">Sub-total</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.total_amount ?? 0).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch">
                                    <p class="text-grey-900 text-base font-normal">SST ({{ Math.round(taxes['SST'] ?? 0) }}%)</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.sst_amount ?? 0).toFixed(2) }}</p>
                                </div>
                                <div class="flex flex-row justify-between items-start self-stretch">
                                    <p class="text-grey-900 text-base font-normal">Service Tax ({{ Math.round(taxes['Service Tax'] ?? 0) }}%)</p>
                                    <p class="text-grey-900 text-base font-bold">RM {{ parseFloat(paymentDetails.service_tax_amount ?? 0).toFixed(2) }}</p>
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
    