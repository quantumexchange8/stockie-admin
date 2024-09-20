<script setup>
import { ref, onMounted, computed } from 'vue';
import TabView from '@/Components/TabView.vue';
import OrderDetail from './OrderDetail.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import { CancelIllus, OrderCompleteIllus } from '@/Components/Icons/illus';
import { useForm } from '@inertiajs/vue3';
import OrderInvoice from './OrderInvoice.vue';

const props = defineProps({
    errors: Object,
    selectedTable: {
        type: Object,
        default: () => {},
    },
})

const emit = defineEmits(['close']);

const tabs = ref(['Order Detail', 'Customer/Detail']);
const order = ref({});
const cancelOrderFormIsOpen = ref(false);
const orderCompleteModalIsOpen = ref(false);
const orderInvoiceModalIsOpen = ref(false);

onMounted(async() => {
    try {
        const orderResponse = await axios.get(route('orders.getOrderWithItems', props.selectedTable.order_table.order_id));
        order.value = orderResponse.data;

        if (order.value) form.order_id = order.value.id;
    } catch (error) {
        console.error(error);
    } finally {

    }
});

const form = useForm({
    order_id: order.value.id,
    action_type: ''
});

const closeDrawer = () => {
    emit('close');
}

const showOrderCompleteModal = () => {
    orderCompleteModalIsOpen.value = true;
}

const hideOrderCompleteModal = () => {
    orderCompleteModalIsOpen.value = false;
}

const showCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = true;
}

const hideCancelOrderForm = () => {
    cancelOrderFormIsOpen.value = false;
}

const showOrderInvoiceModal = () => {
    hideOrderCompleteModal();

    setTimeout(() => {
        orderInvoiceModalIsOpen.value = true;
    }, 100);
};

const hideOrderInvoiceModal = () => {
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
                    form.reset();
                    closeDrawer();
                },
            })
        }
    }
};

const isOrderCompleted = computed(() => {
    if (!order.value || !order.value.order_items) return false;

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

    return mappedOrder.every((item) => item.status === 'Served' || item.status === 'Cancelled');
})

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
</script>

<template>
    <div class="flex flex-col gap-6 items-start rounded-[5px]">
        <div class="px-6 py-2 w-full">
            <TabView :tabs="tabs">
                <template #order-detail>
                    <OrderDetail :selectedTable="selectedTable" :order="order" @close="closeDrawer" />
                </template>
                <template #customer-detail>
                    cuscus
                </template>
            </TabView>
        </div>

        <form @submit.prevent="submit('complete')">
            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <p class="self-stretch text-grey-900 text-right text-md font-medium">Total: RM{{ order.total_amount }}</p>
                <div class="flex flex-col gap-3">
                    <Button
                        size="lg"
                        :disabled="!isOrderCompleted"
                        v-if="selectedTable.status !== 'Pending Clearance'"
                        @click="form.action_type = 'complete'"
                    >
                        Complete Order
                    </Button>
                    <Button
                        size="lg"
                        :disabled="!isOrderCompleted"
                        @click="form.action_type = 'clear'"
                        v-else
                    >
                        Free Up Table
                    </Button>
                    <Button
                        type="button"
                        variant="tertiary"
                        size="lg"
                        @click="showCancelOrderForm"
                        v-if="selectedTable.status !== 'Pending Clearance'"
                    >
                        Cancel Order
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
                    @click="closeDrawer"
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
