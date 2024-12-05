<script setup>
import { PointsIllust } from '@/Components/Icons/illus';
import { HistoryIcon, TimesIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { computed, onMounted, ref, watch } from 'vue';
import CustomerRedemptionHistory from './CustomerRedemptionHistory.vue';
import Button from '@/Components/Button.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    customer:{
        type: Object,
        default: () => {},
    },
    matchingOrderDetails: {
        type: Object,
        default: () => {}
    },
    orderId: Number,
    tableStatus: String,
    customerPoint: Number
})

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'update:customerPoint']);

const customer = ref(props.customer);
const selectedCustomer = ref(null);
const redeemables = ref([]);
const isPointHistoryDrawerOpen = ref(false);
const selectedItem = ref();
const op = ref(null);
const customerNewPoint = ref(props.customer.point);

const form = useForm({
    // order_id: props.orderId,
    user_id: userId.value,
    customer_id: customer.value.id,
    // redeemable_item_id: '',
    redeem_qty: 0,
    matching_order_details: props.matchingOrderDetails,
    selected_item: null
});

const submit = async () => { 
    form.processing = true;
    try {
        const redemptionResponse = await axios.post(`/order-management/orders/customer/point/redeemItemToOrder/${props.orderId}`, form);
        let pointsUsed = redemptionResponse.data.pointSpent;
        customerNewPoint.value = redemptionResponse.data.customerPoint;

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Product redeemed successfully.',
                detail: `${pointsUsed} pts has been deducted from customer's point wallet.`,
            });
        }, 200);

        emit('fetchZones');
        emit('update:customerPoint', customerNewPoint.value);
        emit('close');
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }


    // form.post(route('orders.customer.point.redeemItemToOrder', customer.value.id), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'success',
    //                 summary: 'Product redeemed successfully.',
    //                 detail: "20 pts has been deducted from customer's point wallet.",
    //             });
    //         }, 200);
    //         emit('fetchZones');
    //         emit('fetchOrderDetails');
    //         emit('close');
    //         form.reset();
    //     },
    // })
};

const openHistoryDrawer = (id) => {
    isPointHistoryDrawerOpen.value = true;
    selectedCustomer.value = id;
}

const closeDrawer = () => isPointHistoryDrawerOpen.value = false;

const openOverlay = (event, item) => {
    selectedItem.value = item;
    form.selected_item = selectedItem.value;
    if (selectedItem.value) op.value.show(event);
};

const closeOverlay = () => {
    selectedItem.value = null;
    // form.order_id = props.orderId;
    form.user_id = userId.value;
    // form.redeemable_item_id = '';
    form.redeem_qty = 0;
    form.selected_item = null;

    if (op.value) op.value.hide();
};

const formatPoints = (points) => {
  const pointsStr = points.toString();
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const getRedeemables = async () => {
    try {
        const response = await axios.get('/order-management/getRedeemableItems');
        redeemables.value = response.data;
    } catch(error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => getRedeemables())

const getRedeemableMaxQuantity = () => {
    let stockLeft = selectedItem.value.stock_left;
    let maxCustomerRedeemCount = Math.floor(customer.value.point / selectedItem.value.point);

    return maxCustomerRedeemCount >= stockLeft ? stockLeft : maxCustomerRedeemCount;
};

const isFormValid = computed(() => ['redeem_qty'].every(field => form[field]) && !form.processing);
</script>

<template>
    <div class="flex flex-col p-6 items-center gap-6 shrink-0 max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <!-- Current points -->
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base font-medium">Current Points</span>
                <div class="flex flex-col justify-center items-center gap-2">
                    <span class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-[40px] font-normal">{{ formatPoints(customer.point ?? 0) }}</span>
                    <span class="text-primary-950 text-base font-medium">pts</span>
                </div>
                <PointsIllust class="absolute"/>
            </div>
        </div>

        <!-- Redeem product -->
         <div class="flex flex-col items-center gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Redeem Product</span>
                <div class="flex items-center gap-2 cursor-pointer" @click="openHistoryDrawer(customer.id)">
                    <HistoryIcon class="w-4 h-4" />
                    <div class="text-primary-900 text-sm font-medium">View History</div>
                </div>
            </div>

            <div class="flex flex-col items-center self-stretch divide-y-[0.5px] divide-grey-200 max-h-[calc(100dvh-22.5rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pb-6">
                <div class="flex flex-col justify-end items-start self-stretch" v-for="item in redeemables" :key="item.id">
                    <div class="flex items-center justify-between p-3 gap-3 self-stretch">
                        <div class="flex items-center gap-3">
                            <img 
                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt="RedeemableItemImage"
                                class="size-[60px] bg-primary-25 rounded-[1.5px] border-[0.3px] border-solid border-grey-100"
                                :class="{ 'opacity-30': item.stock_left == 0 }"
                            >
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0] self-stretch">
                                <span class="line-clamp-1 overflow-hidden ellipsis text-base font-medium" :class="item.stock_left == 0 ? 'text-grey-300' : 'text-grey-900'">{{ item.product_name }}</span>
                                <span class="overflow-hidden text-base font-medium" :class="item.stock_left == 0 ? 'text-grey-300' : 'text-primary-950'">{{ formatPoints(item.point) }} pts</span>
                            </div>
                        </div>
                        <!-- tableStatus === 'Pending Clearance' -->
                        <Button 
                            :type="'button'" 
                            :size="'md'" 
                            :disabled="customer.point < item.point || item.stock_left == 0"
                            class="!w-fit"
                            @click="openOverlay($event, item)"
                        >
                            Redeem Now
                        </Button>
                    </div>
                </div>
            </div>
         </div>
    </div>

    <!-- history drawer -->
     <RightDrawer
        :header="'History'"
        :previousTab='true'
        :show="isPointHistoryDrawerOpen"
        @close="closeDrawer"
    >
        <template v-if="customer">
            <CustomerRedemptionHistory :customerId="customer.id"/>
        </template>
    </RightDrawer>

    <!-- Redeem item and add to order -->
    <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="submit">
                <div class="flex flex-col gap-6 w-96">
                    <div class="flex items-center justify-between">
                        <span class="text-primary-950 text-center text-md font-medium">Select Redeem Quantity</span>
                        <TimesIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="closeOverlay"
                        />
                    </div>
                    <div class="flex flex-col gap-y-6">
                        <div class="w-full flex gap-x-2 items-center justify-between">
                            <div class="flex gap-x-3 items-center">
                                <img 
                                    :src="selectedItem.image ? selectedItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="rounded-[1.5px] size-[60px]"
                                >
                                <p class="text-base text-grey-900 font-medium">
                                    {{ selectedItem.product_name }}
                                </p>
                            </div>
                            <p class="text-primary-800 text-md font-medium pr-3">{{ selectedItem.point }} pts</p>
                        </div>

                        <NumberCounter
                            :inputName="`redeem_qty`"
                            :maxValue="getRedeemableMaxQuantity()"
                            v-model="form.redeem_qty"
                        />
                    </div>

                    <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            @click="closeOverlay"
                        >
                            Cancel
                        </Button>
                        <Button
                            :size="'lg'"
                            :disabled="!isFormValid || !matchingOrderDetails.tables"
                        >
                            Redeem Now
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </OverlayPanel>
</template>
