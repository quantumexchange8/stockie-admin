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
import Checkbox from '@/Components/Checkbox.vue';
import { DeleteIllus } from '@/Components/Icons/illus';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    order: {
        type: Object,
        default: () => {}
    },
    orderItems: {
        type: Array,
        default: () => []
    },
})

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'closeDrawer']);

const removeOrderItemModalIsOpen = ref(false);

const form = useForm({
    items: [],
});

onMounted(async() => {
});

const close = (all = false) => {
    emit('close');
    if (all) {
        emit('closeDrawer');
    }
}

const showRemoveOrderItemForm = () => {
    removeOrderItemModalIsOpen.value = true;
}

const hideRemoveOrderItemModal = () => {
    removeOrderItemModalIsOpen.value = false;
}

// Calculate if there are any products left to be served for that order item
const getTotalServedQty = (item, rounded_off = true) => {
    let count = 0;
    const totalServedQty = [];

    item.sub_items.forEach((subItem) => {
        const servedQty = rounded_off ? Math.ceil(subItem.serve_qty / subItem.item_qty) : subItem.serve_qty / subItem.item_qty;
        count++;

        return servedQty > 0 || count > 1 ? totalServedQty.push(servedQty) : totalServedQty.push(0);
    });

    return Math.max(...totalServedQty);
};

const addItemToList = (item) => {
    const index = form.items.findIndex(i => i.order_item_id === item.id);

    if (index !== -1) {
        form.items.splice(index, 1);
    } else {
        form.items.push({
            order_item_id: item.id,
            has_products_left: false,
            remove_qty: getLeftoverQuantity(item),
        });
    }
}

// Get the 'untouched' quantity leftover after removing total served quantity from the total order item quantity
const getLeftoverQuantity = (item) => {
    if (!item.sub_items || item.sub_items.length === 0) return item.item_qty;
    
    const untouchedQty = item.item_qty - getTotalServedQty(item);

    return untouchedQty > 0 ? untouchedQty : 0;
};

const submit = () => { 
    form.items.forEach(item => {
        filteredOrderItems.value.forEach(orderItem => {
            if (orderItem.id === item.order_item_id) {
                item.has_products_left = getTotalServedQty(orderItem, false) % 1 !== 0 || (orderItem.item_qty - item.remove_qty) > getTotalServedQty(orderItem);
            }
        });
    });

    form.put(route('orders.removeOrderItem', props.order.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'The selected order item has been deleted.',
                });
            }, 200);
            form.reset();
            close(true);
        },
    })
};

const filteredOrderItems = computed(() => props.orderItems.filter((item) => getLeftoverQuantity(item) !== 0 && item.type === 'Normal'));

const getAdjustedAmount = (amount, item, discount = false) => {
    return parseFloat((discount ? amount : amount / item.item_qty) * getLeftoverQuantity(item)).toFixed(2)
};

const isFormValid = computed(() => form.items.some(item => item.remove_qty > 0));

</script>

<template>
    <div class="flex flex-col gap-6 w-[526px]">
        <div class="flex items-center justify-between">
            <span class="text-primary-950 text-center text-md font-medium">Delete order</span>
            <TimesIcon class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" @click="close()" />
        </div>

        <!-- Iterate through sub_items of the selected order item -->
        <template v-if="filteredOrderItems.length > 0">
            <div class="max-h-96 pr-2 overflow-y-auto scrollbar-thin scrollbar-webkit">
                <template v-for="(item, index) in filteredOrderItems" :key="index">
                    <div class="flex flex-col gap-3 pb-3 items-start self-stretch">
                        <div class="w-full flex justify-between gap-3 items-center py-3">
                            <div class="flex items-center justify-start gap-3">
                                <div class="size-[30px] flex items-center justify-center bg-primary-900 rounded-[5px] text-primary-25 text-2xs font-semibold">x{{ getLeftoverQuantity(item) }}</div>
                                <img 
                                    :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="OrderItemImage"
                                    class="col-span-3 p-2 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                                >
                                <div class="flex flex-col gap-2 items-start justify-center self-stretch">
                                    <p class="text-base font-medium text-grey-900 self-stretch truncate flex-shrink">{{ item.product.product_name }}</p>
                                    <div class="flex flex-nowrap gap-2 items-center">
                                        <Tag value="Set" v-if="item.product.bucket === 'set'"/>
                                        <template v-if="item.type === 'Normal'">
                                            <div v-if="item.discount_id" class="flex items-center gap-x-1.5">
                                                <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ getAdjustedAmount(item.amount_before_discount, item, true) }}</span>
                                                <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ getAdjustedAmount(item.amount, item) }}</span>
                                            </div>
                                            <span class="text-base font-medium text-primary-950 self-stretch truncate flex-shrink" v-else>RM {{ getAdjustedAmount(item.amount, item) }}</span>
                                        </template>
                                        <Tag :value="item.type" variant="blue" v-else/>
                                    </div>
                                </div>
                            </div>
                            <Checkbox 
                                :checked="false"
                                @change="addItemToList(item)"
                            />
                        </div>

                        <div class="flex justify-between items-center self-stretch py-3 pl-3" v-if="form.items.find(i => i.order_item_id === item.id)">
                            <p class="text-base text-grey-900 font-normal">Delete Quantity</p>
                            <NumberCounter
                                :inputName="`item_${item.id}.remove_qty`"
                                :maxValue="getLeftoverQuantity(item)"
                                v-model="form.items.find(i => i.order_item_id === item.id).remove_qty"
                                class="!w-36"
                            />
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template v-else>
            <p class="text-base font-medium text-grey-900">No pending item to be removed.</p>
        </template>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
            <Button
                type="button"
                variant="tertiary"
                size="lg"
                @click="close()"
            >
                Cancel
            </Button>
            <Button
                type="button"
                size="lg"
                :disabled="!isFormValid"
                @click="showRemoveOrderItemForm"
            >
                Delete
            </Button>
        </div>
    </div>

    <Modal 
        maxWidth="2xs" 
        :closeable="true"
        :show="removeOrderItemModalIsOpen"
        :withHeader="false"
        class="z-[1106] [&>div:nth-child(2)>div>div]:p-0"
        @close="hideRemoveOrderItemModal"
    >
        <form novalidate @submit.prevent="submit">
            <div class="flex flex-col justify-center gap-9">
                <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                    <slot name="deleteimage">
                        <DeleteIllus/>
                    </slot>
                </div>
                <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6">
                    <p class="text-center text-primary-900 text-lg font-medium self-stretch">Delete order?</p>
                    <p class="text-center text-grey-900 text-base font-medium self-stretch">Are you sure you want to delete the selected order? This action cannot be undone.</p>
                </div>
                <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="tertiary"
                        size="lg"
                        @click="hideRemoveOrderItemModal"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        :disabled="form.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>