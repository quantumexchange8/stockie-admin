<script setup>
import { ref, onMounted, computed } from 'vue';
import TabView from '@/Components/TabView.vue';
import OrderDetail from './OrderDetail.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import { CancelIllus, OrderCompleteIllus } from '@/Components/Icons/illus';
import { useForm, usePage } from '@inertiajs/vue3';
import OrderInvoice from './OrderInvoice.vue';
import { useCustomToast } from '@/Composables/index.js';
import CustomerDetail from './CustomerDetail.vue';
import { TimesIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import MakePaymentForm from './MakePaymentForm.vue';

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
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones']);

const tabs = ref(['Order Detail']);
const order = ref({});
const customer = ref({});
const cancelOrderFormIsOpen = ref(false);
const orderCompleteModalIsOpen = ref(false);
const orderInvoiceModalIsOpen = ref(false);
const drawerIsVisible = ref(false);
const selectedTab = ref(0);

const fetchOrderDetails = async () => {
    try {
        const orderResponse = await axios.get(route('orders.getOrderWithItems', props.selectedTable.order_table.order_id));
        order.value = orderResponse.data;

        if (order.value.customer_id) {
            const customerResponse = await axios.get(route('orders.customer', order.value.customer_id));
            customer.value = customerResponse.data;
        }
        
        if (order.value) {
            form.order_id = order.value.id;
            form.customer_id = order.value.customer_id;
            if (order.value.customer_id && !tabs.value.includes('Customer Detail')) tabs.value.push('Customer Detail');
        }
    } catch (error) {
        console.error(error);
    } finally {

    }
};

onMounted(() => fetchOrderDetails());

const form = useForm({
    user_id: userId.value,
    order_id: order.value.id,
    customer_id: '',
    action_type: ''
});

const closeDrawer = () => {
    emit('close');
}

const openPaymentDrawer = () => {
    if (!drawerIsVisible.value) drawerIsVisible.value = true;
};

const closePaymentDrawer = () => {
    drawerIsVisible.value = false;
    selectedTab.value = 1;
};

const showOrderCompleteModal = () => {
    orderCompleteModalIsOpen.value = true;
}

const hideOrderCompleteModal = () => {
    setTimeout(() => {
        showMessage({ 
            severity: 'success',
            summary: 'Selected order has been completed successfully.',
        });
    }, 200);
    closeDrawer();
    orderCompleteModalIsOpen.value = false;
}

const showCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = true;
}

const hideCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = false;
}

const showOrderInvoiceModal = () => {
    orderCompleteModalIsOpen.value = false;

    setTimeout(() => {
        orderInvoiceModalIsOpen.value = true;
    }, 100);
};

const hideOrderInvoiceModal = () => {
    setTimeout(() => {
        showMessage({ 
            severity: 'success',
            summary: 'Selected order has been completed successfully.',
        });
    }, 200);
    closeDrawer();
    orderInvoiceModalIsOpen.value = false;
};

const submit = (action) => { 
    if (order.value.id) {
        if (action === 'complete') {
            form.put(route('orders.complete', order.value.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    if (form.action_type === 'complete') {
                        showOrderCompleteModal(); 
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
                    closeDrawer();
                },
            })
        }
        emit('fetchZones');
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

    return mappedOrder.every((item) => item.status === 'Served' || item.status === 'Cancelled') && !form.processing;
});

const formattedOrder = computed(() => {
    order.value['order_table'] = {
        id: props.selectedTable.order_table.id,
        order_id: props.selectedTable.order_table.order_id,
        table: {
            id: props.selectedTable.id,
            table_no: props.selectedTable.table_no
        },
        table_id: props.selectedTable.order_table.table_id
    };

    return order.value;
});

const hasServedItem = computed(() => {
    return !order.value.id 
        || order.value?.order_items?.some(item => item.sub_items.some((subItem) => subItem.serve_qty > 0))
        || order.value?.order_items?.some(item => item.type === 'Keep' || item.type === 'Expired');
});

const orderTableNames = computed(() => order.value.order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '');

</script>

<template>
    <RightDrawer 
        :header="'Make Payment'" 
        previousTab
        v-model:show="drawerIsVisible"
        @close="closePaymentDrawer"
    >
        <MakePaymentForm 
            :order="order" 
            :selectedTable="selectedTable"
            @close="closePaymentDrawer"
        />
    </RightDrawer>

    <div class="flex flex-col gap-6 items-start rounded-[5px]">
        <div class="w-full flex items-center px-6 pt-6 pb-3 justify-between">
            <span class="text-primary-950 text-center text-md font-medium">Detail - {{ orderTableNames  }}</span>
            <TimesIcon class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" @click="closeDrawer" />
        </div>

        <div class="px-6 py-2 w-full">
            <TabView :tabs="tabs" :selectedTab="selectedTab">
                <template #order-detail>
                    <OrderDetail 
                        :selectedTable="selectedTable" 
                        :order="order" 
                        :customers="customers" 
                        :users="users"
                        @fetchZones="$emit('fetchZones')" 
                        @close="fetchOrderDetails" 
                    />
                </template>
                <template #customer-detail v-if="order.customer_id">
                    <CustomerDetail 
                        :customer="customer" 
                        :orderId="order.id" 
                        :tableStatus="selectedTable.status" 
                        @close="closeDrawer"
                    />
                </template>
            </TabView>
        </div>

        <form @submit.prevent="submit('complete')">
            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <p class="self-stretch text-grey-900 text-right text-md font-medium">Total: RM{{ order.total_amount }}</p>
                <div class="flex flex-col items-center self-stretch gap-3">
                    <div class="flex items-start self-stretch gap-3">
                        <Button
                            type="button"
                            variant="tertiary"
                            size="lg"
                            :disabled="hasServedItem"
                            @click="showCancelOrderForm"
                        >
                            Cancel Order
                        </Button>
                        <Button
                            size="lg"
                            variant="tertiary"
                            :disabled="selectedTable.status !== 'Pending Clearance'"
                            @click="form.action_type = 'clear'"
                        >
                            Free Up Table
                        </Button>
                    </div>
                    <!-- <Button
                        size="lg"
                        :disabled="!isOrderCompleted"
                        @click="form.action_type = 'complete'"
                    >
                        Make Payment
                    </Button> -->
                    <Button
                        type="button"
                        size="lg"
                        :disabled="!isOrderCompleted"
                        @click="openPaymentDrawer"
                    >
                        Make Payment
                    </Button>
                </div>
            </div>
        </form>
    </div>
    
    <Modal 
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
    </Modal>

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
    </Modal>
</template>
