<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import TabView from '@/Components/TabView.vue';
import OrderDetail from './OrderDetail.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import { CancelIllus, MovingIllus, OrderCompleteIllus } from '@/Components/Icons/illus';
import { useForm, usePage } from '@inertiajs/vue3';
import OrderInvoice from './OrderInvoice.vue';
import { useCustomToast } from '@/Composables/index.js';
import CustomerDetail from './CustomerDetail.vue';
import { KeepItemIcon, MergedIcon, PlaceOrderIcon, TimesIcon, TransferIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import MakePaymentForm from './MakePaymentForm.vue';
import PaymentHistory from './PaymentHistory.vue';
import PendingServe from './PendingServe.vue';
import AddOrderItems from './AddOrderItems.vue';
import KeepItem from './KeepItem.vue';
import Checkbox from '@/Components/Checkbox.vue';
import MergeTableForm from './MergeTableForm.vue';

const props = defineProps({
    errors: Object,
    customers: Array,
    users: Array,
    selectedTable: {
        type: Object,
        default: () => {},
    },
})
const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones']);

const tabs = ref([
    { title: 'Order Detail', disabled: false },
    { title: 'Pending Serve', disabled: false },
    { title: 'Customer Detail', disabled: true }, 
    { title: 'Payment History', disabled: false }
]);
const order = ref({});
const pending = ref(0);
const customer = ref({});
const cancelOrderFormIsOpen = ref(false);
const removeRewardFormIsOpen = ref(false);
const orderCompleteModalIsOpen = ref(false);
const mergeTableIsOpen = ref(false);
const drawerIsVisible = ref(false);
const selectedTab = ref(0);
const currentOrderTable = ref({});
const pendingServeItems = ref([]);
const actionType = ref();
const op = ref(null);

const computedOrder = computed(() => {
    if (!order.value || !order.value.order_items || props.tableStatus === 'Pending Clearance') return [];
    
    return {
        ...order.value,
        order_items: order.value.order_items.map((item) => {
            // Calculate total quantities
            const total_qty = item.sub_items.reduce((total, sub_item) => total + (item.item_qty * sub_item.item_qty), 0);
            const total_served_qty = item.sub_items.reduce((total, sub_item) => total + sub_item.serve_qty, 0);
            
            return {
                ...item,
                total_qty,
                total_served_qty,
            };
        }),
    };
});

const fetchOrderDetails = async () => {
    try {
        const currentOrderTableResponse = await axios.get(route('orders.getCurrentTableOrder', props.selectedTable.id));
        currentOrderTable.value = currentOrderTableResponse.data.currentOrderTable;
        order.value = currentOrderTableResponse.data.order;
        
        if (order.value) {
            form.order_id = order.value.id;
            form.customer_id = order.value.customer_id ?? '';
            matchingOrderDetails.value.tables = order.value.order_table.map((orderTable) => orderTable.table.id);
            matchingOrderDetails.value.pax = order.value.pax;
            matchingOrderDetails.value.amount = order.value.amount;
            matchingOrderDetails.value.customer_id = order.value.customer_id;
            matchingOrderDetails.value.assigned_waiter = order.value.user_id; 
            matchingOrderDetails.value.current_order_completed = order.value.status === 'Order Completed' && order.value.order_table.every((table) => table.status === 'Pending Clearance');

            // if (order.value.customer_id && !tabs.value.includes('Customer Detail')) tabs.value.splice(1, 0, 'Customer Detail');
            // if (order.value.customer_id) tabs.value[1].disabled = false;
        }
    } catch (error) {
        console.error(error);
    } finally {

    }
};

const fetchCustomerDetails = async () => {
    try {
        const customerResponse = await axios.get(route('orders.customer', order.value.customer_id));
        customer.value = customerResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
};

const fetchPendingServe = async () => {
    try {
        const pendingServeResponse = await axios.get(route('orders.pendingServe', currentOrderTable.value.id));
        pendingServeItems.value = pendingServeResponse.data;
        // pending.value = pendingServeItems.value.reduce((sum, item) => sum + item.order.order_items.reduce((total_qty, items) => items.item_qty + total_qty , 0), 0);
        pending.value = pendingServeItems.value.reduce((sum, pending) => {
            return sum + pending.order.order_items.reduce((orderSum, items) => {
                return orderSum + items.sub_items.reduce((subItemSum, sub_item) => {
                    return subItemSum + items.item_qty * sub_item.item_qty - sub_item.serve_qty;
                }, 0);
            }, 0);
        }, 0);
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const closeOverlay = () => {
    if (op.value) {
        op.value.hide();  // Ensure op.value is not null before calling hide
    }
};

const closeOrderDetails = () => {
    closeOverlay();
    setTimeout(() => emit('fetchZones'), 200);
    setTimeout(() => fetchOrderDetails(), 300);
    setTimeout(() => fetchPendingServe(), 400);
};

onMounted(async () => {
    await fetchOrderDetails();
    await fetchPendingServe();
});


const matchingOrderDetails = ref({
    tables: '',
    pax: '',
    customer_id: '',
    assigned_waiter: '',
    current_order_completed: ''
})

const form = useForm({
    user_id: userId.value,
    order_id: order.value.id,
    customer_id: '',
    action_type: ''
});

const openDrawer = (action) => {
    actionType.value = action;

    if (!drawerIsVisible.value) {
        drawerIsVisible.value = true;
    }
};

const closeDrawer = () => {
    drawerIsVisible.value = false;
    actionType.value = null;
};

const openPaymentDrawer = () => {
    if (!drawerIsVisible.value) drawerIsVisible.value = true;
};

const closePaymentDrawer = (redirect = false) => {
    drawerIsVisible.value = false;
    if (redirect) {
        fetchOrderDetails();
        selectedTab.value = order.value.customer_id ? 3 : 2;
    }
};

const showOrderCompleteModal = () => {
    orderCompleteModalIsOpen.value = true;
}

// const hideOrderCompleteModal = () => {
//     setTimeout(() => {
//         showMessage({ 
//             severity: 'success',
//             summary: 'Selected order has been completed successfully.',
//         });
//     }, 200);
//     closeDrawer();
//     orderCompleteModalIsOpen.value = false;
// }

const showMergeTableForm = () => {
    mergeTableIsOpen.value = true;
};

const hideMergeTableForm = () => {
    mergeTableIsOpen.value = false;
}

const showCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = true;
};

const hideCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = false;
};

const showRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = true;
};

const hideRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = false;
};

// const showOrderInvoiceModal = () => {
//     orderCompleteModalIsOpen.value = false;

//     setTimeout(() => {
//         orderInvoiceModalIsOpen.value = true;
//     }, 100);
// };

// const hideOrderInvoiceModal = () => {
//     setTimeout(() => {
//         showMessage({ 
//             severity: 'success',
//             summary: 'Selected order has been completed successfully.',
//         });
//     }, 200);
//     closeDrawer();
//     orderInvoiceModalIsOpen.value = false;
// };

const submit = (action) => { 
    if (order.value.id) {
        if (action === 'complete') {
            // if (form.action_type === 'complete' && order.value.payment)  {
            //     openPaymentDrawer();
            // } else {
            form.put(route('orders.complete', order.value.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    emit('fetchZones');
                    if (form.action_type === 'complete') {
                        openPaymentDrawer(); 
                    } else {
                        setTimeout(() => {
                            showMessage({ 
                                severity: 'success',
                                summary: 'Selected table is now available for next customers.',
                            });
                        }, 200);
                        closeDrawer();
                    }
                    form.reset();
                },
            })
        } else if (action === 'remove-voucher') {
            removeReward();
        } else {
            form.put(route('orders.cancel', order.value.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setTimeout(() => {
                        showMessage({ 
                            severity: 'success',
                            summary: 'Selected order has been cancelled successfully.',
                        });
                    }, 200);
                    form.reset();
                    emit('fetchZones');
                    closeDrawer();
                },
            })
        }
    }
};

const removeReward = async () => { 
    form.processing = true;

    try {
        const removalResponse = await axios.put(`/order-management/orders/removeOrderVoucher/${order.value.id}`);

        if (removalResponse.status === 200) { 
            setTimeout(() => { 
                showMessage({ 
                    severity: 'success', 
                    summary: 'Reward removed', 
                    detail: "Reward has been removed from customer's order.", 
                }); 
            }, 200); 
            
            order.value.voucher_id = null; 
            order.value.voucher = null; 
        } else { 
            // Handle unexpected response status 
            showMessage({ 
                severity: 'error', 
                summary: 'Error', 
                detail: "Failed to remove the reward. Please try again.", 
            }); 
        }
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
        hideRemoveRewardForm()
    }
};

const isOrderCompleted = computed(() => {
    if (!order.value || !order.value.order_items || order.value.order_items.length === 0) return false;

    const mappedOrder = order.value.order_items 
                            ? order.value.order_items
                                    .map((item) => {
                                        return {
                                            ...item,
                                            total_qty: item.sub_items.reduce((total_qty, sub_item) => total_qty + (item.item_qty * sub_item.item_qty), 0 ),
                                            total_served_qty: item.sub_items.reduce((total_served_qty, sub_item) => total_served_qty + sub_item.serve_qty, 0 )
                                        };
                                    })
                            : [];

    return !form.processing && currentOrderTable.value.status !== 'Pending Clearance';
});

// const formattedOrder = computed(() => {
//     order.value['order_table'] = {
//         id: props.selectedTable.order_table.filter((table) => table.status !== 'Pending Clearance')[0].id,
//         order_id: props.selectedTable.order_table.filter((table) => table.status !== 'Pending Clearance')[0].order_id,
//         table: {
//             id: props.selectedTable.id,
//             table_no: props.selectedTable.table_no
//         },
//         table_id: props.selectedTable.order_table.filter((table) => table.status !== 'Pending Clearance')[0].table_id
//     };

//     return order.value;
// });

const hasServedItem = computed(() => {
    return !order.value.id 
        || order.value?.order_items?.some(item => item.sub_items.some((subItem) => subItem.serve_qty > 0))
        || order.value?.order_items?.some(item => item.type === 'Keep' || item.type === 'Expired');
});

const hasPreviousPending = computed(() => {
    return pendingServeItems.value.some((item) => item.order.id === props.selectedTable.order_id)
});

const orderTableNames = computed(() => {
    return order.value.order_table
            ?.map((orderTable) => orderTable.table.table_no)
            .sort((a, b) => a.localeCompare(b))
            .join(', ') ?? '';
});

const currentViewedTab = computed(() => tabs.value[selectedTab.value]);

const getVoucherDiscountedPrice = (subtotal, voucher) => {
    if (['Discount (Amount)', 'Discount (Percentage)'].includes(voucher.reward_type)) {
        return (voucher.reward_type === 'Discount (Amount)'
            ? subtotal - voucher.discount
            : subtotal - (subtotal * (voucher.discount / 100))).toFixed(2);
    }
}

watch(
    () => order.value.customer_id,
    async (newCustomerId) => {
        if (newCustomerId) {
            await fetchCustomerDetails();
            tabs.value[2].disabled = !newCustomerId;
        }
    },
    { immediate: true } // Run the watcher immediately on mount
);

// watch(paymentInfo, (newValue) => tabs.value[2].disabled = newValue);

watch(selectedTab, (newValue) => {
    if (tabs.value[newValue].title === 'Order Detail') fetchOrderDetails();
});

watch(order.value, () => {
    fetchPendingServe();
});
</script>

<template>
    <RightDrawer 
        :header="actionType === 'keep' ? 'Keep Item' : actionType === 'add' ? '' : 'Make Payment'" 
        :withHeader="actionType !== 'add'"
        previousTab
        v-model:show="drawerIsVisible"
        @close="actionType ? closeDrawer() : closePaymentDrawer()"
    >
        <template v-if="actionType === 'keep'">
            <KeepItem
                :order="order" 
                :selectedTable="selectedTable"
                @update:customerKeepItems="$emit('update:customerKeepItems', $event)"
                @close="closeDrawer();closeOrderDetails()"
            />
        </template>
        <template v-else-if="actionType === 'add'">
            <AddOrderItems
                :order="computedOrder" 
                :selectedTable="selectedTable"
                :matchingOrderDetails="matchingOrderDetails"
                @fetchZones="$emit('fetchZones')"
                @fetchOrderDetails="fetchOrderDetails"
                @fetchPendingServe="fetchPendingServe"
                @close="closeDrawer();closeOverlay()"
            />
        </template>
        <template v-else>
            <MakePaymentForm 
                :order="order" 
                :selectedTable="selectedTable"
                @fetchZones="$emit('fetchZones')"
                @fetchPendingServe="fetchPendingServe"
                @update:customer-point="customer.point = $event"
                @update:customer-rank="customer.rank = $event"
                @close="closePaymentDrawer"
            />
        </template>
    </RightDrawer>
<!-- 
    <RightDrawer 
        :header="'Make Payment'" 
        previousTab
        v-model:show="drawerIsVisible"
        @close="closePaymentDrawer"
    >
        <MakePaymentForm 
            :order="order" 
            :selectedTable="selectedTable"
            @fetchZones="$emit('fetchZones')"
            @fetchPendingServe="fetchPendingServe"
            @update:customer-point="customer.point = $event"
            @update:customer-rank="customer.rank = $event"
            @close="closePaymentDrawer"
        />
    </RightDrawer> -->

    <div class="flex flex-col gap-4 items-start rounded-[5px]">
        <div class="w-full flex items-center px-6 pt-6 pb-3 justify-between">
            <span class="text-primary-950 text-center text-md font-medium">Detail - {{ orderTableNames }}</span>
            <TimesIcon class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" @click="closeDrawer" />
        </div>

        <div class="flex px-5 items-start gap-4 self-stretch">
            <div class="flex p-4 h-16 justify-center items-center gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-grey-50 cursor-pointer"
                @click="openDrawer('add')"
            >
                <PlaceOrderIcon class="text-primary-950 size-6" />
            </div>

            <div class="flex p-4 h-16 justify-center items-center gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-grey-50 cursor-pointer"
                @click="openDrawer('keep')"
            >
                <KeepItemIcon class="text-primary-950 size-6" />
            </div>
            
            <div class="relative flex p-4 h-16 justify-center items-center gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-grey-50 cursor-pointer"
                @click="showMergeTableForm()"
            >
                <MergedIcon class="size-6" :class="props.selectedTable.order_tables.length > 1 ? 'text-primary-800' : 'text-primary-950'" />
                <Checkbox :checked="true" class="absolute size-4 top-1.5 right-1.5" v-if="props.selectedTable.order_tables.length > 1" />
            </div>

            <div class="flex p-4 h-16 justify-center items-center gap-3 flex-[1_0_0] rounded-[5px] border border-solid border-primary-100 bg-grey-50 cursor-pointer"
                @click="console.log(props.selectedTable.order_tables.length)"
            >
                <TransferIcon class="text-primary-950 size-6" />
            </div>

        </div>

        <div 
            class="py-2 w-full" 
            :class="[
                { 'px-6': currentViewedTab !== 'Payment History' },
                { '[&>div:first-child]:px-6': currentViewedTab === 'Payment History' }
            ]"
        >
            <TabView 
                :withDisabled="true"
                :tabs="tabs" 
                :selectedTab="selectedTab" 
                :tabFooter="tabs[1]"
                @onChange="selectedTab = $event"
            >
                <template #tabFooter>
                    <div class="flex flex-col size-4 items-center justify-center rounded-full bg-primary-600" v-if="tabs[1] && pending > 0">
                        <span class="text-white text-center text-[8px] font-bold">{{ pending }}</span>
                    </div>
                </template>
                <template #order-detail>
                    <OrderDetail 
                        :selectedTable="selectedTable" 
                        :order="order" 
                        :customers="customers" 
                        :users="users"
                        :tableStatus="currentOrderTable.status" 
                        :matchingOrderDetails="matchingOrderDetails"
                        @fetchZones="$emit('fetchZones')" 
                        @fetchOrderDetails="fetchOrderDetails" 
                        @fetchPendingServe="fetchPendingServe"
                        @update:customerKeepItems="customer.keep_items = $event"
                    />
                </template>
                <template #pending-serve>
                    <PendingServe 
                        :order="order"
                        :pendingServeItems="pendingServeItems"
                        @fetchZones="$emit('fetchZones')" 
                        @fetchOrderDetails="fetchOrderDetails" 
                        @fetchPendingServe="fetchPendingServe"
                    />
                </template>
                <template #customer-detail v-if="order.customer_id">
                    <CustomerDetail 
                        :customer="customer" 
                        :orderId="order.id" 
                        :tableStatus="currentOrderTable.status" 
                        :matchingOrderDetails="matchingOrderDetails"
                        @fetchZones="$emit('fetchZones')" 
                        @fetchOrderDetails="fetchOrderDetails" 
                        @update:customerPoint="customer.point = $event"
                        @update:customerKeepItems="customer.keep_items = $event"
                        @close="selectedTab = 0"
                    />
                </template>
                <template #payment-history>
                    <PaymentHistory :selectedTable="selectedTable" />
                </template>
            </TabView>
        </div>

        <form @submit.prevent="submit('complete')" v-if="currentViewedTab !== 'Payment History'">
            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <div class="flex justify-end px-3 gap-x-2.5 self-stretch">
                    <p class="self-stretch text-grey-900 text-md font-medium">
                        Total: RM 
                        {{ currentOrderTable.status === 'Pending Clearance' ?  '0.00' : order.amount }}
                        <span class="text-primary-800">{{ currentOrderTable.status !== 'Pending Clearance' && order.voucher ? ` > RM ${getVoucherDiscountedPrice(order.amount, order.voucher)}` : '' }}</span>
                    </p>
                    <div class="flex items-center gap-1.5 px-3 py-0.5 rounded-[5px] border border-dashed border-primary-300 bg-primary-50" v-if="currentOrderTable.status !== 'Pending Clearance' && order.voucher">
                        <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.125 8.625C18.47 8.625 18.75 8.345 18.75 8V6.125C18.75 5.09125 17.9088 4.25 16.875 4.25H13.125V6.125C13.125 6.47 12.845 6.75 12.5 6.75C12.155 6.75 11.875 6.47 11.875 6.125V4.25H3.125C2.09125 4.25 1.25 5.09125 1.25 6.125V8C1.25 8.345 1.53 8.625 1.875 8.625C2.90875 8.625 3.75 9.46625 3.75 10.5C3.75 11.5338 2.90875 12.375 1.875 12.375C1.53 12.375 1.25 12.655 1.25 13V14.875C1.25 15.9087 2.09125 16.75 3.125 16.75H11.875V14.875C11.875 14.53 12.155 14.25 12.5 14.25C12.845 14.25 13.125 14.53 13.125 14.875V16.75H16.875C17.9088 16.75 18.75 15.9087 18.75 14.875V13C18.75 12.655 18.47 12.375 18.125 12.375C17.0913 12.375 16.25 11.5338 16.25 10.5C16.25 9.46625 17.0913 8.625 18.125 8.625ZM13.125 12.375C13.125 12.72 12.845 13 12.5 13C12.155 13 11.875 12.72 11.875 12.375V11.75C11.875 11.405 12.155 11.125 12.5 11.125C12.845 11.125 13.125 11.405 13.125 11.75V12.375ZM13.125 9.25C13.125 9.595 12.845 9.875 12.5 9.875C12.155 9.875 11.875 9.595 11.875 9.25V8.625C11.875 8.28 12.155 8 12.5 8C12.845 8 13.125 8.28 13.125 8.625V9.25Z" fill="#7E171B"/>
                        </svg>
                        <p class="text-primary-900 text-right text-base font-medium">-{{ order.voucher.reward_type === 'Discount (Amount)' ? `RM ${order.voucher.discount}` : `${order.voucher.discount}%` }}</p>
                        <TimesIcon class="size-4 text-primary-500 hover:text-primary-600 cursor-pointer" @click="showRemoveRewardForm"/>
                    </div>
                </div>
                <div class="flex flex-col items-center self-stretch gap-3">
                    <div class="flex items-start self-stretch gap-3">
                        <Button
                            type="button"
                            variant="tertiary"
                            size="lg"
                            :disabled="hasServedItem || hasPreviousPending"
                            @click="showCancelOrderForm"
                        >
                            Cancel Order
                        </Button>
                        <Button
                            size="lg"
                            variant="tertiary"
                            :disabled="currentOrderTable.status !== 'Pending Clearance' || pending > 0"
                            @click="form.action_type = 'clear'"
                        >
                            Free Up Table
                        </Button>
                    </div>
                    <Button
                        size="lg"
                        :disabled="!isOrderCompleted"
                        @click="form.action_type = 'complete'"
                    >
                        Make Payment
                    </Button>
                    <!-- <Button
                        type="button"
                        size="lg"
                        :disabled="!(order.order_items && order.order_items.length)"
                        @click="console.log(props)"
                    >
                        {{ props.selectedTable.status }}
                    </Button> -->
                </div>
            </div>
        </form>
    </div>
    
    <!-- <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="orderCompleteModalIsOpen"
        @close="hideOrderCompleteModal"
        :withHeader="false"
    >
        <div class="flex flex-col gap-9 pt-36">
            <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] fixed top-0 w-full left-0">
                <OrderCompleteIllus class="mt-2.5"/>
            </div>
            <div class="flex flex-col gap-1" >
                <div class="text-primary-900 text-2xl font-medium text-center">
                    Order Completed
                </div>
                <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                    A PDF will be sent to customer account. 
                </div>
            </div>
            <div class="flex item-center gap-3">
                <Button
                    variant="tertiary"
                    size="lg"
                    type="button"
                    @click="hideOrderCompleteModal"
                >
                    Close
                </Button>
                <Button
                    size="lg"
                    type="button"
                    @click="showOrderInvoiceModal"
                >
                    View Invoice
                </Button>
            </div>
        </div>
    </Modal> -->

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="cancelOrderFormIsOpen"
        @close="hideCancelOrderForm"
        :withHeader="false"
        v-if="order"
    >
        <form @submit.prevent="submit('cancel')">
            <div class="flex flex-col gap-9 pt-36">
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] fixed top-0 w-full left-0">
                    <CancelIllus class="mt-2.5"/>
                </div>
                <div class="flex flex-col gap-1" >
                    <div class="text-primary-900 text-2xl font-medium text-center">
                        Cancel Order
                    </div>
                    <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                        Are you sure you want to cancel all the existing and pending order in this table / room? The action cannot be undone.
                    </div>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="hideCancelOrderForm"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="removeRewardFormIsOpen"
        :withHeader="false"
        class="[&>div>div>div]:!p-0"
        @close="hideRemoveRewardForm"
    >
        <!-- <template v-if="order && order.voucher"> -->
            <form @submit.prevent="submit('remove-voucher')">
                <div class="flex flex-col gap-9">
                    <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                        <MovingIllus/>
                    </div>
                    <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6" >
                        <div class="text-center text-primary-900 text-lg font-medium self-stretch">Remove Reward</div>
                        <div class="text-center text-grey-900 text-base font-medium self-stretch" >The reward will be reinstated to customer's reward list and can be redeemed again next time.</div>
                    </div>
                    <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                        <Button
                            variant="tertiary"
                            size="lg"
                            type="button"
                            @click="hideRemoveRewardForm"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="red"
                            size="lg"
                            :disabled="form.processing"
                        >
                            Remove
                        </Button>
                    </div>
                </div>
            </form>
        <!-- </template> -->
    </Modal>
    
    <!-- <Modal
        maxWidth="sm" 
        closeable
        class="[&>div:nth-child(2)>div>div]:p-1 [&>div:nth-child(2)>div>div]:sm:max-w-[420px]"
        :withHeader="false"
        :show="orderInvoiceModalIsOpen"
        @close="hideOrderInvoiceModal"
    >
        <template v-if="selectedTable">
            <OrderInvoice :order="formattedOrder" />
        </template>
    </Modal> -->

    <Modal
        :title="'Merge with'"
        :maxWidth="'xl'" 
        :closeable="true"
        :show="mergeTableIsOpen"
        @close="hideMergeTableForm"
    >
        <MergeTableForm 
            :currentOrderTable="currentOrderTable"
            :currentOrderCustomer="order.customer"
            @close="hideMergeTableForm"
            @closeDrawer="$emit('close')"
            @fetchZones="$emit('fetchZones')"
        />
    </Modal>
</template>
