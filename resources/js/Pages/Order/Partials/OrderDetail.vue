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
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import RemoveOrderItem from './RemoveOrderItem.vue';

const props = defineProps({
    errors: Object,
    selectedTable: {
        type: Object,
        default: () => {}
    },
    order: {
        type: Object,
        default: () => {}
    }
})

const emit = defineEmits(['close']);

const drawerIsVisible = ref(false);
const actionType = ref(null);
const categoryArr = ref([]);
const op = ref(null);
const op2 = ref(null);
const selectedItem = ref();

const form = useForm({
    order_id: props.order.id,
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
}

const openOverlay = (event, item) => {
    selectedItem.value = item;
    
    if (selectedItem.value) {
        form.order_item_id = selectedItem.value.id;
        form.point = selectedItem.value.product.point * selectedItem.value.item_qty;

        selectedItem.value.sub_items.forEach(sub_item => {
            form.items.push({ 
                sub_item_id: sub_item.id,
                serving_qty: 0,
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
    setTimeout(() => {
        emit('close');
    }, 300);
}

const showDeleteOrderItemOverlay = (event) => {
    op2.value.show(event);
}

const hideDeleteOrderItemOverlay = () => {
    if (op2.value) {
        op2.value.hide();
    }
}

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

onMounted(async() => {
    try {
        const categoryResponse = await axios.get(route('orders.getAllCategories'));
        categoryArr.value = categoryResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
});

const pendingServeItems = computed(() => {
    if (!props.order || !props.order.order_items) {
        return [];
    }

    return props.order 
        ? props.order.order_items
                .map((item) => {
                    return {
                        ...item,
                        total_qty: item.sub_items.reduce((total_qty, sub_item) => total_qty + (item.item_qty * sub_item.item_qty), 0 ),
                        total_served_qty: item.sub_items.reduce((total_served_qty, sub_item) => total_served_qty + sub_item.serve_qty, 0 )
                    };
                }).filter((item) => item.status === 'Pending Serve') 
        : [];
});

const servedItems = computed(() => {
    if (!props.order || !props.order.order_items) {
        return [];
    }

    return props.order 
        ? props.order.order_items
                .map((item) => {
                    return {
                        ...item,
                        total_qty: item.sub_items.reduce((total_qty, sub_item) => total_qty + (item.item_qty * sub_item.item_qty), 0 ),
                        total_served_qty: item.sub_items.reduce((total_served_qty, sub_item) => total_served_qty + sub_item.serve_qty, 0 )
                    };
                }).filter((item) => item.status === 'Served') 
        : [];
});

// Calculate total subitem quantity by multiplying subitem item qty by order item item qty
const totalSubItemQty = (subItem) => {
    if (!selectedItem.value) return 0;
    return subItem.item_qty * selectedItem.value.item_qty;  // Multiply subitem qty by the order item's qty
};

const isFormValid = computed(() => {
    return form.items.some(item => item.serving_qty > 0);
});
</script>

<template>
    <RightDrawer 
        :header="actionType === 'keep' ? 'Keep Item' : `Order for ${selectedTable.table_no}`" 
        previousTab
        v-model:show="drawerIsVisible"
        @close="closeDrawer"
    >
        <template v-if="actionType === 'keep'">
        </template>

        <template v-if="actionType === 'add'">
            <AddOrderItems 
                :categoryArr="categoryArr" 
                :order="order" 
                :selectedTable="selectedTable"
                @close="closeDrawer();closeOrderDetails()"
            />
        </template>
    </RightDrawer>
    <div class="w-full flex flex-col gap-6 items-start rounded-[5px] pr-1 max-h-[calc(100dvh-23rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col items-start gap-4 self-stretch">
            <div class="flex gap-3 py-3 items-start justify-between self-stretch">
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">Order No.</p>
                    <p class="text-grey-800 text-md font-semibold">#{{ order.order_no }}</p>
                </div>
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">No. of pax</p>
                    <p class="text-grey-800 text-md font-semibold">{{ order.pax }}</p>
                </div>
                <div class="flex flex-col gap-2 items-start">
                    <p class="text-grey-900 text-sm font-medium">Ordered by</p>
                    <div class="size-6 bg-primary-100 rounded-full"></div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">Pending Serve</p>
                        <button @click="showDeleteOrderItemOverlay" v-if="pendingServeItems.length > 0">
                            <DeleteIcon class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"/>
                        </button>
                    </div>
                    <template v-if="pendingServeItems.length > 0">
                        <div class="grid grid-cols-12 gap-3 items-center py-3" v-for="(item, index) in pendingServeItems" :key="index">
                            <div class="col-span-1"><div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ item.item_qty }}</div></div>
                            <div class="col-span-8 grid grid-cols-12 gap-3 items-center">
                                <div class="col-span-3 p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                                <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch">
                                    <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                        <span class="text-primary-800">({{ item.total_served_qty }}/{{ item.total_qty }})</span> {{ item.product.product_name }}
                                    </p>
                                    <div class="flex flex-nowrap gap-2 items-center">
                                        <Tag value="Set" v-if="item.product.bucket === 'set'"/>
                                        <p class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-if="item.type === 'Normal'">RM {{ parseFloat(item.amount).toFixed(2) }}</p>
                                        <Tag :value="item.type" variant="blue" v-else/>
                                    </div>
                                </div>
                            </div>
                            <Button
                                type="button"
                                class="!w-fit col-span-3"
                                @click="openOverlay($event, item)"
                            >
                                Serve Now
                            </Button>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-900">No pending item to be served.</p>
                    </template>
                </div>

                <div class="flex flex-col gap-2 justify-start self-stretch">
                    <div class="flex items-center justify-between">
                        <p class="text-primary-950 text-md font-medium">Served</p>
                    </div>
                    <template v-if="servedItems.length > 0">
                        <div class="grid grid-cols-12 gap-3 items-center py-3" v-for="(item, index) in servedItems" :key="index">
                            <div class="col-span-1"><div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ item.item_qty }}</div></div>
                            <div class="col-span-8 grid grid-cols-12 gap-3 items-center">
                                <div class="col-span-3 p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                                <div class="col-span-8 flex flex-col gap-2 items-start justify-center self-stretch w-full">
                                    <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">
                                        <span class="text-grey-600">({{ item.total_served_qty }}/{{ item.total_qty }})</span> {{ item.product.product_name }}
                                    </p>
                                    <div class="flex flex-nowrap gap-2 items-center">
                                        <Tag value="Set" v-if="item.product.bucket === 'set'"/>
                                        <p class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-if="item.type === 'Normal'">RM {{ parseFloat(item.amount).toFixed(2) }}</p>
                                        <Tag :value="item.type" variant="blue" v-else/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-3 flex flex-col justify-center items-end gap-2 self-stretch">
                                <p class="text-md font-medium text-primary-800 self-stretch truncate flex-shrink text-end">+{{ item.point_earned }}pts</p>
                                <div class="flex flex-nowrap gap-1 items-center">
                                    <div class="p-2 size-4 bg-primary-100 rounded-full border-[0.3px] border-grey-100"></div>
                                    <p class="text-xs text-grey-900 font-medium">{{ item.ordered_by.name }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-900">No item served.</p>
                    </template>
                </div>

                <div class="flex gap-2.5 items-center self-stretch" v-if="selectedTable.status !== 'Pending Clearance'">
                    <Button
                        type="button"
                        variant="tertiary"
                        size="lg"
                        :disabled="!order.id"
                        @click=""
                    >
                        Keep Item
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        iconPosition="left"
                        size="lg"
                        :disabled="!order.id"
                        @click="openDrawer('add')"
                    >
                        <template #icon>
                            <PlusIcon class="w-6 h-6 text-primary-900 hover:text-primary-800" />
                        </template>
                        More Order
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove order item -->
    <OverlayPanel ref="op2" @close="hideDeleteOrderItemOverlay">
        <template v-if="pendingServeItems">
            <RemoveOrderItem 
                :order="order" 
                :orderItems="pendingServeItems" 
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
                        <span class="text-primary-950 text-center text-md font-medium">Serve Now</span>
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
                    </template>

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
