<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import Tag from '@/Components/Tag.vue';
import Button from '@/Components/Button.vue';
import AddOrderItems from './AddOrderItems.vue';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { DeleteIcon, PlusIcon, TimesIcon } from '@/Components/Icons/solid.jsx';
import axios from 'axios';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import RemoveOrderItem from './RemoveOrderItem.vue';
import KeepItem from './KeepItem.vue';
import Dropdown from '@/Components/Dropdown.vue';
import SelectCustomer from './SelectCustomer.vue';

const props = defineProps({
    errors: Object,
    customers: Array,
    users: Array,
    tableStatus: String,
    selectedTable: {
        type: Object,
        default: () => {}
    },
    order: {
        type: Object,
        default: () => {}
    },
    matchingOrderDetails: {
        type: Object,
        default: () => {}
    }
})
const emit = defineEmits(['fetchOrderDetails', 'fetchZones', 'update:customerKeepItems', 'fetchPendingServe']);

const order = computed(() => props.order);
const drawerIsVisible = ref(false);
const actionType = ref(null);
// const categoryArr = ref([]);
const op = ref(null);
const op2 = ref(null);
const selectedItem = ref();
const orderCustomer = ref(order.value.customer);
const isCustomerModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const deleteItemType = ref('');

watch(order, (newValue) => orderCustomer.value = newValue.customer);

const form = useForm({
    current_order_id: order.value.id,
    order_id: order.value.id,
    order_item_id: '',
    point: 0,
    items: [],
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

const openOverlay = (event, item) => {
    selectedItem.value = item;

    if (selectedItem.value) {
        form.order_item_id = selectedItem.value.id;
        form.point = selectedItem.value.product.point * selectedItem.value.item_qty;

        selectedItem.value.sub_items.forEach(sub_item => {
            form.items.push({ 
                sub_item_id: sub_item.id,
                serving_qty: (sub_item.item_qty * selectedItem.value.item_qty) - sub_item.serve_qty,
            })
        });
        op.value.show(event);
    }
};

const closeOverlay = () => {
    selectedItem.value = null;
    form.order_item_id = '';
    form.point = 0;
    form.items = [];
    
    if (op.value) {
        op.value.hide();  // Ensure op.value is not null before calling hide
    }
};

const closeOrderDetails = () => {
    closeOverlay();
    setTimeout(() => emit('fetchZones'), 200);
    setTimeout(() => emit('fetchOrderDetails'), 300);
    setTimeout(() => emit('fetchPendingServe'), 400);
};

const showDeleteOrderItemOverlay = (event, type) => {
    deleteItemType.value = type;
    op2.value.show(event);
};

const hideDeleteOrderItemOverlay = () => {
    if (op2.value) op2.value.hide();
};

const formSubmit = () => { 
    form.put(route('orders.items.update', form.order_item_id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            closeOrderDetails();
        },
    })
};

// onMounted(async() => {
//     try {
//         const categoryResponse = await axios.get(route('orders.getAllCategories'));
//         categoryArr.value = categoryResponse.data;
//     } catch (error) {
//         console.error(error);
//     } finally {

//     }
// });

const computedOrder = computed(() => {
    if (!order.value || !order.value.order_items || props.tableStatus === 'Pending Clearance') return [];
    
    return {
        ...order.value,
        order_items: order.value.order_items
                .filter((item) => item.item_qty > 0)
                .map((item) => {
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

const pendingServeItems = computed(() => {
    return computedOrder.value?.order_items?.length 
            ? computedOrder.value.order_items.filter((item) => item.status === 'Pending Serve') 
            : [];
});

const servedItems = computed(() => {
    return computedOrder.value?.order_items?.length 
            ? computedOrder.value.order_items.filter((item) => item.status === 'Served') 
            : [];
});

// Calculate total subitem quantity by multiplying subitem item qty by order item item qty
const totalSubItemQty = (subItem) => {
    if (!selectedItem.value) return 0;
    return subItem.item_qty * selectedItem.value.item_qty;  // Multiply subitem qty by the order item's qty
};

const getKeepItemName = (item) => {
    var itemName = '';
    item.sub_items.forEach(subItem => {
        itemName = item.product.product_items.find(productItem => productItem.id === subItem.product_item_id).inventory_item.item_name;
    });
    if (itemName) return itemName;
};

const orderedBy = computed(() => {
    if (!order.value?.order_items?.length || !props.users?.length || props.tableStatus === 'Pending Clearance') return [];

    const userMap = new Map(props.users.map(({ id, full_name, image }) => [id, {full_name, image}]));

    const uniqueUsers = [...new Set(order.value.order_items.map(({ user_id }) => user_id))];

    return uniqueUsers
        .map((id) => userMap.get(id))
        .filter(Boolean); // Filter out undefined or null values
});

const updateOrderCustomerForm = useForm({ customer_id: '' });

const updateOrderCustomer = (customerItem) => {
    updateOrderCustomer.customer_id = customerItem;
    updateOrderCustomerForm.put(route('orders.updateOrderCustomer', order.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            closeOrderDetails();
        },
    })
}

const getItemTypeName = (type) => {
    switch (type) {
        case 'Keep': return 'Keep Item';
        case 'Redemption': return 'Redeemed Product'
        case 'Reward': return 'Entry Reward'
    }
}

const showCustomerModal = () => {
    isCustomerModalOpen.value = true;
    isDirty.value = false;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCustomerModalOpen.value = false;
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
            break;
        }
    }
}

const isFormValid = computed(() => form.items.some(item => item.serving_qty > 0) && !form.processing);

</script>

<template>
    <RightDrawer 
        :header="actionType === 'keep' ? 'Keep Item' : ''" 
        :withHeader="actionType === 'keep'"
        previousTab
        v-model:show="drawerIsVisible"
        @close="closeDrawer"
    >
        <template v-if="actionType === 'keep'">
            <KeepItem 
                :order="order" 
                :selectedTable="selectedTable"
                @update:customerKeepItems="$emit('update:customerKeepItems', $event)"
                @close="closeDrawer();closeOrderDetails()"
            />
        </template>
        <template v-if="actionType === 'add'">
            <AddOrderItems 
                :order="computedOrder" 
                :selectedTable="selectedTable"
                :matchingOrderDetails="matchingOrderDetails"
                @fetchZones="$emit('fetchZones')"
                @fetchOrderDetails="$emit('fetchOrderDetails')"
                @fetchPendingServe="$emit('fetchPendingServe')"
                @close="closeDrawer();closeOverlay()"
            />
        </template>
    </RightDrawer>
    <div class="w-full flex flex-col gap-6 items-start rounded-[5px] pr-1 max-h-[calc(100dvh-28.4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex flex-col items-start gap-y-4 py-4 self-stretch">
                <div class="w-full flex flex-row gap-x-3 items-start">
                    <div class="basis-1/2 flex flex-col gap-2 items-start">
                        <p class="text-grey-900 text-sm font-medium">{{ $t('public.order_no') }}</p>
                        <p class="text-grey-800 text-md font-semibold" v-if="tableStatus !== 'Pending Clearance'">#{{ order.order_no }}</p>
                        <p class="text-grey-800 text-md font-semibold" v-else>--</p>
                    </div>
                    <div class="basis-1/2 flex flex-col gap-2 items-start">
                        <p class="text-grey-900 text-sm font-medium">{{ $t('public.no_of_pax') }}</p>
                        <p class="text-grey-800 text-md font-semibold">{{ order.pax }}</p>
                    </div>
                </div>
                <div class="w-full flex flex-row gap-x-3 items-start">
                    <div class="basis-1/2 flex flex-col gap-2 items-start">
                        <p class="text-grey-900 text-sm font-medium">{{ $t('public.order.ordered_by') }}</p>
                        <div class="flex whitespace-nowrap items-center gap-2">
                            <!-- <div class="size-6 bg-primary-100 rounded-full" v-for="user in orderedBy"></div> -->
                             <template v-for="user in orderedBy" >
                                <img 
                                    :src="user.image ? user.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-6 rounded-full object-contain"
                                >
                             </template>
                            <p class="text-grey-900 text-base font-medium" :class="{'!text-grey-300': orderedBy.length === 0}" v-if="orderedBy.length <= 1">{{ orderedBy && orderedBy.length > 0 ? orderedBy[0].full_name : 'No order yet' }}</p>
                            <!-- <p class="text-grey-800 text-sm font-semibold">{{ order.waiter?.full_name ?? '' }}</p> -->
                        </div>
                    </div>
                    <div class="basis-1/2 flex flex-col gap-2 items-start">
                        <p class="text-grey-900 text-sm font-medium">{{ $t('public.customer_header') }}</p>
                        <!-- <Dropdown
                            inputName="customer_id"
                            imageOption
                            plainStyle
                            :inputArray="customers"
                            :dataValue="order.customer_id"
                            :errorMessage="updateOrderCustomerForm.errors?.customer_id || ''"
                            :disabled="tableStatus === 'Pending Clearance'"
                            v-model="updateOrderCustomerForm.customer_id"
                            @onChange="updateOrderCustomer"
                        /> -->
                        <div 
                            :class="[
                                'flex justify-between items-center gap-x-2 w-full', 
                                tableStatus !== 'Pending Clearance' ? 'cursor-pointer' : 'cursor-not-allowed'
                            ]"
                            @click="tableStatus !== 'Pending Clearance' ? showCustomerModal() : ''"
                        >
                            <div class="flex gap-x-2 w-full">
                                <img 
                                    :src="orderCustomer ? orderCustomer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="WaiterProfileImage"
                                    class="size-5 rounded-full"
                                    v-if="orderCustomer"
                                >
                                <p 
                                    :class="[
                                        'text-base font-normal', 
                                        orderCustomer ? 'text-grey-700' : 'text-grey-300',
                                        tableStatus !== 'Pending Clearance' ? 'cursor-pointer' : 'cursor-not-allowed'
                                    ]"
                                >
                                    {{ orderCustomer?.full_name ?? $t('public.select') }}
                                </p>
                            </div>
                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_4705_26673)">
                                    <path d="M4.26826 7.61216H11.7331C12.0352 7.61216 12.3123 7.44478 12.4533 7.17785C12.5941 6.91038 12.5755 6.58772 12.4048 6.33789L8.62642 0.81698C8.49115 0.619372 8.2672 0.501073 8.02765 0.500002C7.78836 0.499418 7.5637 0.616258 7.42734 0.813282L3.59911 6.33429C3.42657 6.58302 3.40649 6.90726 3.54698 7.17575C3.68747 7.44378 3.96533 7.61216 4.26826 7.61216Z" fill="#7E171B"/>
                                    <path d="M11.7331 9.38867H4.26825C3.96532 9.38867 3.68746 9.55712 3.54697 9.82512C3.40648 10.0936 3.42659 10.4178 3.5991 10.6665L7.42733 16.1875C7.56369 16.3845 7.78835 16.5014 8.02764 16.5009C8.26719 16.4999 8.49114 16.3814 8.62641 16.1839L12.4048 10.6629C12.5755 10.4131 12.594 10.0904 12.4533 9.82291C12.3123 9.55605 12.0352 9.38867 11.7331 9.38867Z" fill="#7E171B"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_4705_26673">
                                        <rect width="16" height="16" fill="white" transform="translate(0 0.5)"/>
                                    </clipPath>
                                </defs>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">{{ $t('public.order.pending_serve') }}</p>
                        <button @click="showDeleteOrderItemOverlay($event, 'pending')">
                            <DeleteIcon class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"/>
                        </button>
                    </div>
                    <template v-if="pendingServeItems.length > 0">
                        <div class="flex flex-col divide-y-[0.5px] divide-grey-200">
                            <div class="grid grid-cols-12 gap-3 items-start py-3" v-for="(item, index) in pendingServeItems" :key="index">
                                <div class="col-span-1"><div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ item.item_qty }}</div></div>
                                <div class="col-span-8 grid grid-cols-12 gap-3 items-start">
                                    <!-- <div class="col-span-3 p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div> -->
                                    <img 
                                        :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="OrderItemImage"
                                        class="col-span-3 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                                    >
                                    <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch">
                                        <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                            <Tag value="Set" v-if="item.product.bucket === 'set' && item.type === 'Normal'"/> {{ item.type === 'Keep' ? getKeepItemName(item) : item.product.product_name }}
                                        </p>
                                        <template v-if="item.type === 'Normal'">
                                            <div class="flex flex-nowrap gap-2 items-center">
                                                <div v-if="item.discount_id" class="flex items-center gap-x-1.5">
                                                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ parseFloat(item.amount_before_discount).toFixed(2) }}</span>
                                                    <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                                </div>
                                                <span class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-else>RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                            </div>
                                        </template>
                                        <div class="flex flex-nowrap gap-2 items-center">
                                            <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                                <span class="text-primary-800">({{ item.total_served_qty }}/{{ item.total_qty }})</span>
                                            </p>
                                            <Tag :value="getItemTypeName(item.type)" variant="blue" v-if="item.type !== 'Normal'"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-3 flex items-center h-full">
                                    <Button
                                        type="button"
                                        class="!w-fit"
                                        :disabled="form.processing"
                                        @click="openOverlay($event, item)"
                                    >
                                        {{ $t('public.order.serve_now') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-300 py-6 self-stretch truncate">{{ $t('public.empty.no_pending_item') }}</p>
                    </template>
                </div>

                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">{{ $t('public.order_header') }}</p>
                        <button @click="showDeleteOrderItemOverlay($event, 'served')">
                            <DeleteIcon class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"/>
                        </button>
                    </div>
                    <template v-if="servedItems.length > 0">
                        <div class="flex flex-col divide-y-[0.5px] divide-grey-200">
                            <div class="grid grid-cols-12 gap-3 items-start py-3" v-for="(item, index) in servedItems" :key="index">
                                <div class="col-span-1"><div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ item.item_qty }}</div></div>
                                <div class="col-span-8 grid grid-cols-12 gap-3 items-start">
                                    <img 
                                        :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="OrderItemImage"
                                        class="col-span-3 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                                    >
                                    <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch w-full">
                                        <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                            <Tag value="Set" v-if="item.product.bucket === 'set' && item.type === 'Normal'"/> {{ item.type === 'Keep' ? getKeepItemName(item) : item.product.product_name }}
                                        </p>
                                        <div class="flex flex-nowrap gap-2 items-center">
                                            <template v-if="item.type === 'Normal'">
                                                <div v-if="item.discount_id" class="flex items-center gap-x-1.5">
                                                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ parseFloat(item.amount_before_discount).toFixed(2) }}</span>
                                                    <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                                </div>
                                                <span class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-else>RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                            </template>
                                            <template v-else>
                                                <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                                    <span class="text-grey-600">({{ item.total_served_qty }}/{{ item.total_qty }})</span>
                                                </p>
                                                <Tag :value="getItemTypeName(item.type)" variant="blue"/>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-3 flex flex-col justify-center items-end gap-2 self-stretch">
                                    <div class="flex flex-nowrap gap-1 items-center">
                                        <img 
                                            :src="item.handled_by.image ? item.handled_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="p-2 size-4 rounded-full border-[0.3px] border-grey-100 object-contain"
                                        >
                                        <p class="text-xs text-grey-900 font-medium">{{ item.handled_by.full_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-300 py-6 self-stretch truncate">{{ $t('public.empty.no_item_added') }}</p>
                    </template>
                </div>

                <!-- <div class="flex gap-2.5 pb-6 items-center self-stretch">
                    <Button
                        v-if="order.customer_id"
                        type="button"
                        variant="tertiary"
                        size="lg"
                        :disabled="!order.id || tableStatus === 'Pending Clearance' || !matchingOrderDetails.tables"
                        @click="openDrawer('keep')"
                    >
                        Keep Item
                    </Button>
                    <Button
                        type="button"
                        iconPosition="left"
                        size="lg"
                        :disabled="!order.id || !matchingOrderDetails.tables"
                        @click="openDrawer('add')"
                    >
                        <template #icon>
                            <PlusIcon class="w-6 h-6 text-white" />
                        </template>
                        Place Order
                    </Button>
                </div> -->
            </div>
        </div>
    </div>

    <!-- Remove order item -->
    <OverlayPanel ref="op2" @close="hideDeleteOrderItemOverlay">
        <template v-if="pendingServeItems && deleteItemType ===  'pending'">
            <RemoveOrderItem 
                :order="order" 
                :orderItems="pendingServeItems" 
                @close="hideDeleteOrderItemOverlay" 
                @closeDrawer="closeOrderDetails"
            />
        </template>
        <template v-if="servedItems && deleteItemType ===  'served'">
            <RemoveOrderItem 
                :order="order" 
                :orderItems="servedItems" 
                @close="hideDeleteOrderItemOverlay" 
                @closeDrawer="closeOrderDetails"
            />
        </template>
    </OverlayPanel>
    
    <!-- Serve order item -->
    <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="formSubmit">
                <div class="flex flex-col gap-6 w-[300px]">
                    <div class="flex items-center justify-between">
                        <span class="text-primary-950 text-center text-md font-medium">{{ $t('public.order.serve_now') }}</span>
                        <TimesIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="closeOverlay"
                        />
                    </div>

                    <!-- Iterate through sub_items of the selected order item -->
                    <template v-if="selectedItem.sub_items.length > 0">
                        <template v-for="(subItem, index) in selectedItem.sub_items" :key="index">
                            <!-- Find the related product_item from product.product_items -->
                            <template v-for="productItem in selectedItem.product.product_items" :key="productItem.id">
                                <template v-if="subItem.product_item_id === productItem.id">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex justify-between items-center self-stretch">
                                            <p class="text-base text-grey-900 font-medium">
                                                {{ productItem.inventory_item.item_name }}
                                            </p>
                                            <div class="flex items-center gap-2 self-stretch text-primary-900 text-xs font-medium">
                                                <p>{{ $t('public.order.served') }}:</p>
                                                <p>{{ subItem.serve_qty }}/{{ totalSubItemQty(subItem) }}</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-12 items-center self-stretch">
                                            <p class="col-span-6 text-xs text-grey-900 font-medium">
                                                {{ $t('public.order.serve_now_qty') }}
                                            </p>
                                            <NumberCounter
                                                :inputName="`subItem_${subItem.id}.serving_qty`"
                                                :maxValue="totalSubItemQty(subItem) - subItem.serve_qty"
                                                v-model="form.items[index].serving_qty"
                                                class="col-span-6"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </template>
                    </template>

                    <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            @click="closeOverlay"
                        >
                            {{ $t('public.action.cancel') }}
                        </Button>
                        <Button
                            :size="'lg'"
                            :disabled="!isFormValid"
                        >
                            {{ $t('public.action.serve') }}
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </OverlayPanel>
    
    <Modal
        :title="'Checked-in customer'"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isCustomerModalOpen"
        @close="closeModal('close')"
    >
        <SelectCustomer
            :currentOrder="order"
            :customers="customers"
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
</template>
