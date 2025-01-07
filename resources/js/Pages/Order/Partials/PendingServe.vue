<script setup>
import { ref, computed } from 'vue';
import Tag from '@/Components/Tag.vue';
import Button from '@/Components/Button.vue';
import { DefaultIcon, DeleteIcon, PlusIcon, TimesIcon } from '@/Components/Icons/solid.jsx';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import RemoveOrderItem from './RemoveOrderItem.vue';
import dayjs from 'dayjs';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    order: {
        type: Object,
        default: () => {}
    },
    pendingServeItems: {
        type: Array,
        default: () => []
    },
})
const emit = defineEmits(['fetchOrderDetails', 'fetchZones', 'fetchPendingServe']);
const order = computed(() => props.order);
const drawerIsVisible = ref(false);
const actionType = ref(null);
// const categoryArr = ref([]);
const op = ref(null);
const op2 = ref(null);
const selectedItem = ref();

// watch(props.order, (newValue) => order.value = newValue)

const form = useForm({
    current_order_id: order.value.id,
    order_id: '',
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
        form.order_id = selectedItem.value.order_id;
        form.order_item_id = selectedItem.value.id;
        form.point = parseInt(selectedItem.value.product.point) * selectedItem.value.item_qty;

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

const showDeleteOrderItemOverlay = (event) => {
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
            closeOrderDetails();
            form.reset();
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

const getServedQty = (item) => {
    return item.sub_items.reduce((sum, qty) => sum + qty.serve_qty, 0);
}

const getTotalQty = (item) => {
    return item.item_qty * item.sub_items.reduce((sum, subItem) => sum + subItem.item_qty, 0);
}

const isFormValid = computed(() => form.items.some(item => item.serving_qty > 0) && !form.processing);

</script>

<template>
    <!-- <div class="w-full flex flex-col gap-6 items-start rounded-[5px] pr-1 max-h-[calc(100dvh-23.4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex flex-col gap-3 items-center self-stretch">
                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">Pending Serve</p>
                        <button @click="showDeleteOrderItemOverlay">
                            <DeleteIcon class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"/>
                        </button>
                    </div>
                    <template v-if="pendingServeItems.length > 0">
                        <div class="flex flex-col divide-y-[0.5px] divide-grey-200">
                            <div class="grid grid-cols-12 gap-3 items-center py-3" v-for="(item, index) in pendingServeItems" :key="index">
                                <div class="col-span-1"><div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ item.item_qty }}</div></div>
                                <div class="col-span-8 grid grid-cols-12 gap-3 items-center">
                                    <img 
                                        :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="OrderItemImage"
                                        class="col-span-3 p-2 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                                    >
                                    <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch">
                                        <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                            <span class="text-primary-800">({{ item.total_served_qty }}/{{ item.total_qty }})</span> {{ item.type === 'Normal' ? item.product.product_name : getKeepItemName(item) }}
                                        </p>
                                        <div class="flex flex-nowrap gap-2 items-center">
                                            <Tag value="Set" v-if="item.product.bucket === 'set' && item.type === 'Normal'"/>
                                            <template v-if="item.type === 'Normal'">
                                                <div v-if="item.discount_id" class="flex items-center gap-x-1.5">
                                                    <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ parseFloat(item.amount_before_discount).toFixed(2) }}</span>
                                                    <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                                </div>
                                                <span class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-else>RM {{ parseFloat(item.amount).toFixed(2) }}</span>
                                            </template>
                                            <Tag :value="getItemTypeName(item.type)" variant="blue" v-else/>
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    class="!w-fit col-span-3"
                                    :disabled="form.processing"
                                    @click="openOverlay($event, item)"
                                >
                                    Serve Now
                                </Button>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-300 py-6 self-stretch truncate">No pending item to be served.</p>
                    </template>
                </div>
            </div>
        </div>
    </div> -->

    <div class="flex flex-col py-3 items-end gap-3 flex-[1_0_0] self-stretch overflow-auto scrollbar-webkit scrollbar-thin max-h-[calc(100dvh-25rem)]" v-if="props.pendingServeItems.length > 0">
        <div class="flex flex-col pt-4 pb-2 items-start gap-4 self-stretch rounded-[5px] bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]" v-for="table in props.pendingServeItems">
            <!-- item header -->
            <div class="flex px-4 justify-center items-center gap-2.5 self-stretch">
                <!-- <div class="w-1.5 h-[53px] bg-primary-800"></div> -->
                <div class="flex flex-col items-start gap-1 flex-[1_0_0] border-l-[6px] border-l-primary-800">
                    <span class="line-clamp-1 self-stretch text-ellipsis text-lg font-bold pl-2.5">{{ table.order.order_no }}</span>
                    <!-- item info -->
                    <div class="flex items-center gap-3 self-stretch pl-2.5">
                        <div class="flex items-center gap-2">
                            <img
                                :src="table.customer.image"
                                alt="CustomerIcon"
                                class="size-5 rounded-full"
                                v-if="table.customer && table.customer.image"
                            >
                            <DefaultIcon class="size-5" v-else />
                            <span class="text-grey-900 text-base font-normal">{{ table.customer ? table.customer.full_name : 'Guest' }}</span>
                        </div>
                        <p class="text-grey-200">&#x2022;</p>
                        <span class="text-grey-900 text-base font-normal">{{ dayjs(table.order.created_at).format('DD/MM/YYYY, HH:mm') }}</span>
                    </div>
                </div>
            </div>

            <!-- item list -->
            <div class="flex flex-col px-4 items-start self-stretch" v-for="item in table.order.order_items">
                <div class="flex py-3 items-center gap-5 self-stretch">
                    <div class="flex items-center gap-3 flex-[1_0_0]">
                        <img
                            :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                            alt="ProductIcon"
                            class="size-[60px] object-contain"
                        >
                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0] self-stretch">
                            <span class="text-primary-800 line-clamp-1 self-stretch text-ellipsis text-base font-medium flex gap-1">
                                ({{ getServedQty(item) }}/{{ getTotalQty(item) }}) 
                                <span class="line-clamp-1 text-grey-900 text-ellipsis text-base font-medium">{{ item.product.product_name }}</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <Tag 
                                    :value="'Set'"
                                    :variant="'default'"
                                    v-if="item.product.bucket === 'set'"
                                />
                                <span class="line-clamp-1 text-ellipsis text-xs font-medium text-grey-900 line-through" v-if="item.amount_before_discount !== item.amount">RM {{ item.amount_before_discount }}</span>
                                <span class="line-clamp-1 self-stretch text-ellipsis text-base font-medium text-primary-950">RM {{ item.amount }}</span>
                            </div>
                        </div>
                    </div>
                    <Button
                        type="button"
                        class="!w-fit col-span-3"
                        :disabled="form.processing"
                        @click="openOverlay($event, item)"
                    >
                        Serve Now
                    </Button>
                </div>
            </div>
        </div>
    </div>
    <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
        <UndetectableIllus />
        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
    </div>

    <!-- Remove order item -->
    <!-- <OverlayPanel ref="op2" @close="hideDeleteOrderItemOverlay">
        <template v-if="pendingServeItems">
            <RemoveOrderItem 
                :order="order" 
                :orderItems="pendingServeItems" 
                @close="hideDeleteOrderItemOverlay" 
                @closeDrawer="closeOrderDetails"
            />
        </template>
    </OverlayPanel> -->
    
    <!-- Serve order item -->
    <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="formSubmit">
                <div class="flex flex-col gap-6 w-[300px]">
                    <div class="flex items-center justify-between">
                        <span class="text-primary-950 text-center text-md font-medium">Serve Now</span>
                        <TimesIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="closeOverlay"
                        />
                    </div>

                    <!-- Iterate through sub_items of the selected order item -->
                    <!-- <template v-if="selectedItem.sub_items.length > 0">
                        <template v-for="(subItem, index) in selectedItem.sub_items" :key="index"> -->
                            <!-- Find the related product_item from product.product_items -->
                            <!-- <template v-for="productItem in selectedItem.product.product_items" :key="productItem.id">
                                <template v-if="subItem.product_item_id === productItem.id">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex justify-between items-center self-stretch">
                                            <p class="text-base text-grey-900 font-medium">
                                                {{ productItem.inventory_item.item_name }}
                                            </p>
                                            <div class="flex items-center gap-2 self-stretch text-primary-900 text-xs font-medium">
                                                <p>Served:</p>
                                                <p>{{ subItem.serve_qty }}/{{ totalSubItemQty(subItem) }}</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-12 items-center self-stretch">
                                            <p class="col-span-6 text-xs text-grey-900 font-medium">
                                                Serve Now Quantity
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
                    </template> -->
                    <div class="flex flex-col items-start gap-3 self-stretch" v-for="(item, index) in selectedItem.sub_items" :key="index">
                        <div class="flex justify-between items-center self-stretch">
                            <span class="text-grey-900 text-base font-medium">{{ item.product_item.inventory_item.item_name }}</span>
                            <div class="flex items-start gap-2">
                                <span class="text-primary-900 text-xs font-medium">Served:</span>
                                <span class="text-primary-900 text-xs font-medium">({{ item.serve_qty }}/{{ item.item_qty * selectedItem.item_qty }})</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 justify-between items-center self-stretch ">
                            <span class="text-grey-900 text-xs font-medium">Serve Now Quantity</span>
                            <NumberCounter
                                :inputName="`subItem_${item.id}.serving_qty`"
                                :maxValue="item.item_qty - item.serve_qty"
                                v-model="form.items[index].serving_qty"
                            />
                        </div>
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
                            :disabled="!isFormValid"
                        >
                            Serve
                        </Button>
                    </div>
                </div>
            </form>
        </template>
    </OverlayPanel>
</template>
